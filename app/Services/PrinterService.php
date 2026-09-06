<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * PrinterService
 *
 * Responsabilidades:
 * - Generar comandos TSPL2 para TSC TE200
 * - Enviar trabajo de impresión a la impresora
 * - Gestionar la conexión (USB vía RAW/TCP, TCP/IP socket)
 *
 * TSPL2 Reference: TSC TE200 @ 203 DPI
 * Conversión: 1 mm = 8.0315 dots (203 dpi)
 * Etiqueta: 80 mm × 40 mm = 643 × 322 dots
 */
class PrinterService
{
    private array $config;

    // Conversión mm → dots @ 203 DPI
    private const MM_TO_DOTS = 8.0315;

    public function __construct(?array $config = null)
    {
        if ($config === null) {
            $config = Database::fetchOne(
                "SELECT * FROM configuracion_impresora WHERE activo = 1 ORDER BY id LIMIT 1"
            );
        }
        $this->config = $config ?: $this->defaultConfig();
    }

    private function defaultConfig(): array
    {
        return [
            'nombre'          => 'TSC TE200',
            'modelo'          => 'TE200',
            'dpi'             => 203,
            'ancho_mm'        => 80,
            'alto_mm'         => 40,
            'velocidad'       => 4,
            'densidad'        => 8,
            'orientacion'     => 'horizontal',
            'tipo_conexion'   => 'usb',
            'ip'              => null,
            'puerto'          => 9100,
        ];
    }

    /**
     * Genera el string TSPL2 completo para una etiqueta de producto.
     *
     * @param array $label Datos de la etiqueta
     * @param int   $copies Número de copias físicas
     * @param string|null $bitmapHex Logo en formato hexadecimal monocromo (opcional)
     */
    public function generateTspl(array $label, int $copies = 1, ?string $bitmapData = null): string
    {
        $ancho   = (float)$this->config['ancho_mm'];
        $alto    = (float)$this->config['alto_mm'];
        $velocidad = (int)$this->config['velocidad'];
        $densidad  = (int)$this->config['densidad'];

        // Construir comandos TSPL2
        $cmds = [];

        // === CONFIGURACIÓN DE ETIQUETA ===
        $cmds[] = "SIZE {$ancho} mm, {$alto} mm";
        $cmds[] = "GAP 2 mm, 0 mm";
        $cmds[] = "DIRECTION 0";            // 0 = normal
        $cmds[] = "REFERENCE 0,0";
        $cmds[] = "SPEED {$velocidad}";
        $cmds[] = "DENSITY {$densidad}";
        $cmds[] = "SET TEAR ON";
        $cmds[] = "CLS";

        // === LOGO (si existe y fue procesado) ===
        if ($bitmapData !== null) {
            // Logo en esquina superior izquierda: x=10, y=5, ~60x40 dots
            $cmds[] = $bitmapData; // Pre-generado por LogoProcessor
        }

        // === EMPRESA ===
        $empresa = $this->sanitizeTspl($label['empresa_nombre'] ?? 'EMPRESA');
        if ($bitmapData !== null) {
            // Con logo: nombre a la derecha del logo
            $cmds[] = "TEXT 80,8,\"3\",0,1,1,\"{$empresa}\"";
        } else {
            $cmds[] = "TEXT 10,5,\"3\",0,1,1,\"{$empresa}\"";
        }

        // Línea separadora
        $cmds[] = "BAR 0,52,643,2";

        // === PRODUCTO ===
        $productoLabel = "PRODUCTO:";
        $productoVal   = $this->sanitizeTspl($label['producto_nombre'] ?? '');
        $cmds[] = "TEXT 10,58,\"2\",0,1,1,\"{$productoLabel}\"";
        $cmds[] = "TEXT 10,76,\"3\",0,1,1,\"{$productoVal}\"";

        // === SUBPRODUCTO ===
        $subLabel = "SUBPRODUCTO:";
        $subVal   = $this->sanitizeTspl($label['subproducto_descripcion'] ?? '');
        $cmds[] = "TEXT 10,112,\"2\",0,1,1,\"{$subLabel}\"";

        // Ajustar tamaño si el subproducto es muy largo
        $subLen = mb_strlen($subVal);
        $subFont = $subLen > 25 ? '2' : '3';
        $cmds[] = "TEXT 10,130,\"{$subFont}\",0,1,1,\"{$subVal}\"";

        // === CANTIDAD, TURNO, FECHA ===
        $turnos = [1 => '1-MAÑANA', 2 => '2-TARDE', 3 => '3-NOCHE'];
        $turnoStr = $this->sanitizeTspl($turnos[$label['turno']] ?? (string)$label['turno']);
        $cantStr  = $this->sanitizeTspl((string)($label['cantidad'] ?? '0'));
        $fechaStr = $this->sanitizeTspl($label['fecha'] ?? date('d/m/Y'));

        // Línea separadora
        $cmds[] = "BAR 0,185,643,2";

        $cmds[] = "TEXT 10,192,\"2\",0,1,1,\"CANTIDAD: {$cantStr}\"";
        $cmds[] = "TEXT 340,192,\"2\",0,1,1,\"TURNO: {$turnoStr}\"";
        $cmds[] = "TEXT 10,218,\"2\",0,1,1,\"FECHA: {$fechaStr}\"";

        // Marco exterior
        $cmds[] = "BOX 2,2,641,320,2";

        // === IMPRIMIR ===
        $cmds[] = "PRINT {$copies},1";
        $cmds[] = "";

        return implode("\r\n", $cmds);
    }

    /**
     * Genera TSPL2 para etiqueta de prueba.
     */
    public function generateTestTspl(): string
    {
        $ancho = (float)$this->config['ancho_mm'];
        $alto  = (float)$this->config['alto_mm'];

        $cmds = [];
        $cmds[] = "SIZE {$ancho} mm, {$alto} mm";
        $cmds[] = "GAP 2 mm, 0 mm";
        $cmds[] = "DIRECTION 0";
        $cmds[] = "SPEED {$this->config['velocidad']}";
        $cmds[] = "DENSITY {$this->config['densidad']}";
        $cmds[] = "CLS";

        // Marco exterior
        $cmds[] = "BOX 2,2,641,320,4";

        // Textos de prueba
        $cmds[] = "TEXT 10,20,\"3\",0,1,1,\"TSC TE200 - PRUEBA DE IMPRESION\"";
        $cmds[] = "TEXT 10,60,\"2\",0,1,1,\"Ancho: {$ancho} mm\"";
        $cmds[] = "TEXT 10,82,\"2\",0,1,1,\"Alto: {$alto} mm\"";
        $cmds[] = "TEXT 10,104,\"2\",0,1,1,\"DPI: {$this->config['dpi']}\"";
        $cmds[] = "TEXT 10,126,\"2\",0,1,1,\"Velocidad: {$this->config['velocidad']}\"";
        $cmds[] = "TEXT 10,148,\"2\",0,1,1,\"Densidad: {$this->config['densidad']}\"";

        // Líneas de referencia (cada 10 mm = 80 dots)
        for ($mm = 10; $mm <= 70; $mm += 10) {
            $x = (int)round($mm * self::MM_TO_DOTS);
            $cmds[] = "BAR {$x},2,1,320";
        }

        $cmds[] = "TEXT 10,200,\"1\",0,1,1,\"labelprint v1.0\"";
        $cmds[] = "TEXT 10,240,\"1\",0,1,1,\"" . date('d/m/Y H:i:s') . "\"";

        $cmds[] = "PRINT 1,1";
        $cmds[] = "";

        return implode("\r\n", $cmds);
    }

    /**
     * Envía los comandos TSPL2 a la impresora.
     * Soporta TCP/IP socket (más estable en web) y archivo RAW para USB.
     */
    public function send(string $tsplData): array
    {
        $tipo = $this->config['tipo_conexion'] ?? 'usb';

        try {
            switch ($tipo) {
                case 'tcp':
                    return $this->sendTcp($tsplData);
                case 'usb':
                    return $this->sendUsb($tsplData);
                case 'shared':
                    return $this->sendShared($tsplData);
                default:
                    return ['success' => false, 'message' => "Tipo de conexión desconocido: {$tipo}"];
            }
        } catch (\Throwable $e) {
            error_log('[PrinterService] Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error de comunicación con la impresora: ' . $e->getMessage()];
        }
    }

    /**
     * Envío por TCP/IP (recomendado para uso en red).
     */
    private function sendTcp(string $data): array
    {
        $ip   = $this->config['ip'] ?? null;
        $port = (int)($this->config['puerto'] ?? 9100);

        if (empty($ip)) {
            return ['success' => false, 'message' => 'IP de impresora no configurada.'];
        }

        $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
        if (!$socket) {
            return ['success' => false, 'message' => "No se pudo conectar con la impresora ({$ip}:{$port}): {$errstr}"];
        }

        fwrite($socket, $data);
        fclose($socket);

        return ['success' => true, 'message' => 'Etiqueta enviada correctamente.'];
    }

    /**
     * Envío USB: escribe en archivo de dispositivo o usa lpr/rawprint.
     *
     * NOTA: En Windows con Apache/PHP, el método más fiable es usar
     * el comando `copy /b archivo_tspl \\.\lpt1` o enviar a la impresora compartida.
     * En Linux, se puede usar /dev/usb/lp0 o lpr -l.
     */
    private function sendUsb(string $data): array
    {
        // Guardar datos en archivo temporal
        $tmpFile = sys_get_temp_dir() . '/label_' . time() . '.prn';
        if (file_put_contents($tmpFile, $data) === false) {
            return ['success' => false, 'message' => 'No se pudo crear el archivo temporal de impresión.'];
        }

        // Intentar enviar al dispositivo USB de impresora en Linux
        $usbDevice = '/dev/usb/lp0';
        if (file_exists($usbDevice) && is_writable($usbDevice)) {
            $result = file_put_contents($usbDevice, $data);
            @unlink($tmpFile);
            if ($result !== false) {
                return ['success' => true, 'message' => 'Etiqueta enviada por USB correctamente.'];
            }
            return ['success' => false, 'message' => 'No se pudo escribir en el dispositivo USB.'];
        }

        // Windows (XAMPP): enviar a la impresora instalada en el sistema
        if (PHP_OS_FAMILY === 'Windows') {
            $printerName = $this->config['nombre'] ?? 'TSC TE200';

            // Método 1: copy /b al nombre de impresora Windows
            // Formato: copy /b "archivo.prn" "\\.\nombreImpresora"
            $tmpWin = str_replace('/', '\\', $tmpFile);
            $cmd = 'copy /b "' . $tmpWin . '" "' . $printerName . '" > nul 2>&1';
            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                // Método 2: usar el puerto de impresora (LPT1 o USB)
                $output = [];
                $cmd2 = 'copy /b "' . $tmpWin . '" LPT1 > nul 2>&1';
                exec($cmd2, $output, $returnCode);
            }

            @unlink($tmpFile);

            if ($returnCode === 0) {
                return ['success' => true, 'message' => 'Etiqueta enviada correctamente a ' . $printerName . '.'];
            }

            // Fallback: guardar .prn para impresión manual
            $fallbackFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'label_' . time() . '.prn';
            file_put_contents($fallbackFile, $data);

            return [
                'success' => false,
                'message' => 'No se pudo enviar directamente. Archivo guardado en storage/temp/ para impresión manual.',
                'detail'  => 'Asegúrese de que la impresora "' . $printerName . '" esté instalada en Windows.',
                'file'    => basename($fallbackFile),
            ];
        }

        // Guardar en storage/temp para descarga manual (fallback)
        $fallbackFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'label_' . time() . '.prn';
        @rename($tmpFile, $fallbackFile);

        return [
            'success' => false,
            'message' => 'No se detectó dispositivo USB. Se guardó el archivo en storage/temp/ para impresión manual.',
            'file'    => basename($fallbackFile)
        ];
    }

    /**
     * Envío a impresora compartida en red (Windows/Linux).
     */
    private function sendShared(string $data): array
    {
        $nombre = $this->config['nombre_compartido'] ?? $this->config['nombre'] ?? '';
        if (empty($nombre)) {
            return ['success' => false, 'message' => 'Nombre de impresora compartida no configurado.'];
        }

        $tmpFile = sys_get_temp_dir() . '/label_' . time() . '.prn';
        file_put_contents($tmpFile, $data);

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'copy /b "' . $tmpFile . '" "' . addslashes($nombre) . '" 2>&1';
        } else {
            $cmd = 'lpr -P "' . escapeshellarg($nombre) . '" -l "' . $tmpFile . '" 2>&1';
        }

        exec($cmd, $output, $returnCode);
        @unlink($tmpFile);

        if ($returnCode === 0) {
            return ['success' => true, 'message' => 'Etiqueta enviada correctamente.'];
        }

        return [
            'success' => false,
            'message' => 'Error al enviar a impresora compartida.',
            'detail'  => implode("\n", $output)
        ];
    }

    /**
     * Sanitiza texto para TSPL2 (escapa comillas dobles).
     */
    private function sanitizeTspl(string $text): string
    {
        // En TSPL2, las comillas dobles dentro del texto deben escaparse
        $text = str_replace('"', "'", $text);
        // Eliminar caracteres de control
        $text = preg_replace('/[\x00-\x1F\x7F]/', ' ', $text);
        return trim($text);
    }

    /**
     * Verifica si la impresora está disponible (solo TCP/IP).
     */
    public function checkStatus(): array
    {
        if (($this->config['tipo_conexion'] ?? 'usb') !== 'tcp') {
            return ['available' => true, 'message' => 'Modo USB/compartido: estado no verificable remotamente.'];
        }

        $ip   = $this->config['ip'] ?? null;
        $port = (int)($this->config['puerto'] ?? 9100);

        if (empty($ip)) {
            return ['available' => false, 'message' => 'IP no configurada.'];
        }

        $socket = @fsockopen($ip, $port, $errno, $errstr, 3);
        if ($socket) {
            fclose($socket);
            return ['available' => true, 'message' => "Impresora disponible en {$ip}:{$port}"];
        }

        return ['available' => false, 'message' => "Impresora no disponible ({$ip}:{$port}): {$errstr}"];
    }

    /**
     * Convierte milímetros a dots a 203 DPI.
     */
    public static function mmToDots(float $mm): int
    {
        return (int)round($mm * self::MM_TO_DOTS);
    }
}
