<?php

declare(strict_types=1);

// ============================================================
// LABELPRINT — Front Controller
// ============================================================

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH',  ROOT_PATH . '/app');

// Autoloader PSR-4 simple
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) return;

    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Configuración de errores
$appConfig = require ROOT_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'America/Lima');

if ($appConfig['debug'] ?? false) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Logs
ini_set('error_log', ROOT_PATH . '/storage/logs/php_errors.log');

// Iniciar sesión
use App\Core\Session;
use App\Core\Router;

Session::start();

// Calcular basePath para assets/links
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Hacer basePath disponible globalmente en vistas
// (se extrae en View::render via extract)

// Definir basePath como variable de vista global
// Truco: inyectarla como constante accesible en vistas
if (!defined('VIEW_BASE_PATH')) {
    define('VIEW_BASE_PATH', $basePath);
}

// Override View para incluir basePath automáticamente
// Se hace via el View class que ya extrae $data

// Monkey-patch: añadir basePath a todos los renders
$origViewRender = null; // No necesario, se inyecta en el render via data

// ---- Routing ----
$router = new Router();
require ROOT_PATH . '/routes/web.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

// Inyectar basePath en todas las vistas
// Modificamos View para que always incluya basePath
\App\Core\ViewConfig::setBasePath($basePath);

$router->dispatch($method, $uri);
