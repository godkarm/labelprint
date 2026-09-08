<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Services\PrinterService;

class ConfiguracionController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $impresora = Database::fetchOne("SELECT * FROM configuracion_impresora WHERE activo = 1 LIMIT 1");
        View::render('configuracion.index', compact('impresora'));
    }

    public function updateImpresora(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $tipoConexion = in_array($_POST['tipo_conexion'] ?? '', ['usb', 'tcp', 'shared'])
            ? $_POST['tipo_conexion'] : 'usb';

        $nombreCompartido = trim($_POST['nombre_compartido'] ?? '') ?: null;

        $data = [
            'nombre'            => trim($_POST['nombre']    ?? 'TSC TE200'),
            'modelo'            => trim($_POST['modelo']    ?? 'TE200'),
            'dpi'               => (int)($_POST['dpi']      ?? 203),
            'ancho_mm'          => (float)($_POST['ancho_mm'] ?? 80),
            'alto_mm'           => (float)($_POST['alto_mm']  ?? 40),
            'velocidad'         => max(1, min(14, (int)($_POST['velocidad'] ?? 4))),
            'densidad'          => max(0, min(15, (int)($_POST['densidad']  ?? 8))),
            'orientacion'       => in_array($_POST['orientacion'] ?? '', ['horizontal','vertical'])
                                    ? $_POST['orientacion'] : 'horizontal',
            'tipo_conexion'     => $tipoConexion,
            'ip'                => trim($_POST['ip'] ?? '') ?: null,
            'puerto'            => (int)($_POST['puerto'] ?? 9100),
            'nombre_compartido' => $nombreCompartido,
        ];

        $existing = Database::fetchOne("SELECT id FROM configuracion_impresora WHERE activo = 1 LIMIT 1");

        if ($existing) {
            Database::query(
                "UPDATE configuracion_impresora SET
                 nombre=?,modelo=?,dpi=?,ancho_mm=?,alto_mm=?,velocidad=?,densidad=?,
                 orientacion=?,tipo_conexion=?,ip=?,puerto=?,nombre_compartido=?
                 WHERE id=?",
                [...array_values($data), $existing['id']]
            );
        } else {
            Database::query(
                "INSERT INTO configuracion_impresora
                 (nombre,modelo,dpi,ancho_mm,alto_mm,velocidad,densidad,orientacion,tipo_conexion,ip,puerto,nombre_compartido)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                array_values($data)
            );
        }

        Session::flash('success', 'Configuración guardada correctamente.');
        $this->redirect('/configuracion');
    }

    public function prueba(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $service = new PrinterService();
        $tspl    = $service->generateTestTspl();
        $result  = $service->send($tspl);

        if ($this->isAjax()) {
            View::json($result);
            return;
        }

        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect('/configuracion');
    }

    public function calibracion(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $service = new PrinterService();
        $tspl    = $service->generateTestTspl();
        $result  = $service->send($tspl);
        View::json($result);
    }

    public function diagnostico(): void
    {
        $this->requireAuth();
        View::render('configuracion.diagnostico', []);
    }

    /**
     * Ejecuta diagnóstico paso a paso y devuelve JSON con resultado de cada capa.
     */
    public function diagnosticoRun(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $pasos  = [];
        $result = [];

        // PASO 1: Configuración en BD
        $cfg = Database::fetchOne("SELECT * FROM configuracion_impresora WHERE activo = 1 LIMIT 1");
        if ($cfg) {
            $pasos[] = [
                'n'      => 1,
                'estado' => 'OK',
                'detalle'=> "Nombre: '{$cfg['nombre']}' | Tipo: {$cfg['tipo_conexion']} | " .
                            ($cfg['tipo_conexion'] === 'tcp' ? "IP: {$cfg['ip']}:{$cfg['puerto']}" : "USB/compartido"),
            ];
        } else {
            $pasos[] = ['n'=>1,'estado'=>'ERROR','detalle'=>'No hay configuración de impresora en la BD. Vaya a Configuración → Impresora.'];
            View::json(['success'=>false,'message'=>'Sin configuración de impresora.','pasos'=>$pasos]);
            return;
        }

        // PASO 2: Generación TSPL
        $service = new PrinterService($cfg);
        try {
            $tspl = $service->generateTestTspl();
            $lines = substr_count($tspl, "\r\n") + 1;
            $pasos[] = ['n'=>2,'estado'=>'OK','detalle'=>"TSPL generado: {$lines} líneas, " . strlen($tspl) . " bytes"];
        } catch (\Throwable $e) {
            $pasos[] = ['n'=>2,'estado'=>'ERROR','detalle'=>'Error generando TSPL: '.$e->getMessage()];
            View::json(['success'=>false,'message'=>'Error en generación TSPL.','pasos'=>$pasos,'tspl'=>$tspl??'']);
            return;
        }

        // PASO 3: Guardado del .prn
        $prnDir  = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp';
        $prnFile = $prnDir . DIRECTORY_SEPARATOR . 'diag_' . date('Ymd_His') . '.prn';
        if (!is_dir($prnDir)) @mkdir($prnDir, 0755, true);
        $saved = file_put_contents($prnFile, $tspl);
        if ($saved !== false) {
            $pasos[] = ['n'=>3,'estado'=>'OK','detalle'=>"Guardado: " . basename($prnFile) . " ({$saved} bytes) en storage/temp/"];
        } else {
            $pasos[] = ['n'=>3,'estado'=>'WARN','detalle'=>'No se pudo guardar el .prn en storage/temp/ (verificar permisos)'];
        }

        // PASO 4: Envío a la impresora
        $pasos[] = ['n'=>4,'estado'=>'Ejecutando...','detalle'=>'Enviando a la impresora…'];
        $sendResult = $service->send($tspl);

        if ($sendResult['success']) {
            $pasos[3] = ['n'=>4,'estado'=>'OK','detalle'=>$sendResult['message']];
        } else {
            $pasos[3] = [
                'n'      => 4,
                'estado' => 'ERROR',
                'detalle'=> $sendResult['message'] . (isset($sendResult['detail']) ? ' | Debug: ' . $sendResult['detail'] : ''),
            ];
        }

        // PASO 5: Resumen de respuesta del backend
        $pasos[] = [
            'n'      => 5,
            'estado' => $sendResult['success'] ? 'OK' : 'ERROR',
            'detalle'=> $sendResult['success']
                ? 'Backend procesó correctamente y reportó éxito.'
                : 'Backend reportó fallo. Ver detalle en paso 4.',
        ];

        // OS + usuario de proceso (para diagnóstico de permisos)
        $osInfo = PHP_OS_FAMILY . ' | PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        if (PHP_OS_FAMILY === 'Windows' && function_exists('exec')) {
            exec('whoami 2>&1', $whoOut);
            $osInfo .= ' | Usuario Apache: ' . implode('', $whoOut);
        }

        View::json([
            'success'  => $sendResult['success'],
            'message'  => $sendResult['success']
                ? '✓ Impresión enviada correctamente. Verifique la impresora físicamente.'
                : 'La impresión no pudo enviarse. Revise los pasos marcados en ERROR.',
            'pasos'    => $pasos,
            'tspl'     => $tspl,
            'prn_file' => $sendResult['prn_file'] ?? (isset($prnFile) ? basename($prnFile) : null),
            'detail'   => 'Sistema: ' . $osInfo . "\n" . ($sendResult['detail'] ?? ''),
        ]);
    }

    /**
     * Descarga segura de archivos .prn desde storage/temp/
     */
    public function descargarPrn(string $file): void
    {
        $this->requireAuth();

        $file = basename($file);
        if (!preg_match('/^(label|diag)_[\w\-]+\.prn$/i', $file)) {
            http_response_code(400);
            echo 'Archivo no válido.';
            return;
        }

        $path = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . $file;
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'Archivo no encontrado.';
            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
