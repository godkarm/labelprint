<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Session;

/**
 * CheckController
 * Verificación del entorno XAMPP — accesible en /check
 */
class CheckController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();

        $checks = [];
        $allOk  = true;

        // PHP version
        $phpOk    = version_compare(PHP_VERSION, '8.1.0', '>=');
        $checks[] = ['label' => 'PHP ' . PHP_VERSION, 'ok' => $phpOk,
                     'msg'   => $phpOk ? 'Compatible (requiere 8.1+)' : 'Requiere PHP 8.1+'];
        if (!$phpOk) $allOk = false;

        // Extensiones
        foreach (['pdo', 'pdo_mysql', 'mbstring', 'fileinfo', 'json', 'session'] as $ext) {
            $loaded   = extension_loaded($ext);
            $checks[] = ['label' => "ext: $ext", 'ok' => $loaded,
                         'msg'   => $loaded ? 'Disponible' : 'FALTANTE'];
            if (!$loaded) $allOk = false;
        }
        $gd       = extension_loaded('gd');
        $checks[] = ['label' => 'ext: gd', 'ok' => $gd,
                     'msg'   => $gd ? 'Disponible (procesamiento de logo activo)' : 'No disponible — logo no se convierte a bitmap (opcional)'];

        // BD
        try {
            $dbConfig = require ROOT_PATH . '/config/database.php';
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
            $pdo = new \PDO($dsn, $dbConfig['user'], $dbConfig['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
            $checks[] = ['label' => 'MySQL/MariaDB', 'ok' => true, 'msg' => "Conectado · v$ver · BD: {$dbConfig['dbname']}"];

            $tables   = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            $required = ['empresa','usuarios','productos','subproductos','producto_subproducto','configuracion_impresora','impresiones'];
            $missing  = array_diff($required, $tables);
            if (empty($missing)) {
                $checks[] = ['label' => 'Tablas BD', 'ok' => true, 'msg' => count($tables) . ' tablas presentes'];
            } else {
                $checks[] = ['label' => 'Tablas BD', 'ok' => false,
                             'msg'   => 'Faltan: ' . implode(', ', $missing) . ' — Importar 001_initial_schema.sql'];
                $allOk = false;
            }
        } catch (\Exception $e) {
            $checks[] = ['label' => 'MySQL/MariaDB', 'ok' => false,
                         'msg'   => 'Sin conexión: ' . $e->getMessage() . ' — Editar config/database.php'];
            $allOk = false;
        }

        // Directorios
        $dirs = [
            ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'logos' => 'public/uploads/logos',
            ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs'  => 'storage/logs',
            ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'temp'  => 'storage/temp',
        ];
        foreach ($dirs as $path => $label) {
            if (!is_dir($path)) @mkdir($path, 0755, true);
            $w        = is_writable($path);
            $checks[] = ['label' => "Dir: $label", 'ok' => $w, 'msg' => $w ? 'Existe y es escribible' : 'Sin permisos de escritura'];
            if (!$w) $allOk = false;
        }

        // OS
        $checks[] = ['label' => 'Sistema operativo', 'ok' => true,
                     'msg'   => PHP_OS_FAMILY . ' · ' . php_uname('s') . ' ' . php_uname('r')];

        // URL base
        $basePath = $this->basePath();
        $checks[] = ['label' => 'URL base detectada', 'ok' => true,
                     'msg'   => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath . '/'];

        $pageTitle = 'Verificar Sistema';
        $activeNav = 'check';

        View::render('check.index', compact('checks', 'allOk', 'pageTitle', 'activeNav'));
    }
}
