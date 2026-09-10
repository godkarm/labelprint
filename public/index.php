<?php

declare(strict_types=1);

// ============================================================
// LABELPRINT — Front Controller
// ============================================================

// Autoloader PSR-4 (debe ir primero para que funcionen los 'use')
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Configuración de entorno
$appConfig = require ROOT_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'America/Lima');

// Mostrar errores en modo debug (útil para XAMPP local)
if ($appConfig['debug'] ?? false) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Log de errores PHP
ini_set('error_log', ROOT_PATH . '/storage/logs/php_errors.log');

// Sesión
\App\Core\Session::start();

// BasePath (para vistas y redirects en subdirectorio)
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
\App\Core\ViewConfig::setBasePath($basePath);

// Routing
$router = new \App\Core\Router();
require ROOT_PATH . '/routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
