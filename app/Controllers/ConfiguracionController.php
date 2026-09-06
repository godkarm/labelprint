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

        $data = [
            'nombre'           => trim($_POST['nombre'] ?? 'TSC TE200'),
            'modelo'           => trim($_POST['modelo'] ?? 'TE200'),
            'dpi'              => (int)($_POST['dpi'] ?? 203),
            'ancho_mm'         => (float)($_POST['ancho_mm'] ?? 80),
            'alto_mm'          => (float)($_POST['alto_mm'] ?? 40),
            'velocidad'        => max(1, min(14, (int)($_POST['velocidad'] ?? 4))),
            'densidad'         => max(0, min(15, (int)($_POST['densidad'] ?? 8))),
            'orientacion'      => in_array($_POST['orientacion'] ?? '', ['horizontal', 'vertical']) ? $_POST['orientacion'] : 'horizontal',
            'tipo_conexion'    => in_array($_POST['tipo_conexion'] ?? '', ['usb', 'tcp', 'shared']) ? $_POST['tipo_conexion'] : 'usb',
            'ip'               => trim($_POST['ip'] ?? '') ?: null,
            'puerto'           => (int)($_POST['puerto'] ?? 9100),
            'nombre_compartido'=> trim($_POST['nombre_compartido'] ?? '') ?: null,
        ];

        $existing = Database::fetchOne("SELECT id FROM configuracion_impresora WHERE activo = 1 LIMIT 1");

        if ($existing) {
            Database::query(
                "UPDATE configuracion_impresora SET 
                 nombre=?, modelo=?, dpi=?, ancho_mm=?, alto_mm=?, velocidad=?, densidad=?,
                 orientacion=?, tipo_conexion=?, ip=?, puerto=?, nombre_compartido=?
                 WHERE id=?",
                [...array_values($data), $existing['id']]
            );
        } else {
            Database::query(
                "INSERT INTO configuracion_impresora 
                 (nombre, modelo, dpi, ancho_mm, alto_mm, velocidad, densidad, orientacion, tipo_conexion, ip, puerto, nombre_compartido)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                array_values($data)
            );
        }

        Session::flash('success', 'Configuración de impresora guardada.');
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
            View::json($result + ['tspl' => $tspl]);
            return;
        }

        if ($result['success']) {
            Session::flash('success', 'Etiqueta de prueba enviada.');
        } else {
            Session::flash('error', $result['message']);
        }

        $this->redirect('/configuracion');
    }

    public function calibracion(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        // Etiqueta de calibración con marco y líneas de referencia
        $service = new PrinterService();
        $tspl    = $service->generateTestTspl();
        $result  = $service->send($tspl);

        View::json([
            'success' => $result['success'],
            'message' => $result['message'],
            'tspl'    => $tspl,
        ]);
    }
}
