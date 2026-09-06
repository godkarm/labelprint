<?php
/**
 * LABELPRINT — Verificación del entorno
 * Acceder via: http://localhost/labelprint/public/check.php
 *
 * Este archivo verifica que el entorno XAMPP esté configurado
 * correctamente antes de usar el sistema.
 *
 * ELIMINAR o PROTEGER este archivo en producción.
 */

define('ROOT_PATH', dirname(__DIR__));

$checks  = [];
$ok      = true;

// ---- PHP Version ----
$phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
$checks[] = [
    'label' => 'PHP ' . PHP_VERSION,
    'ok'    => $phpOk,
    'msg'   => $phpOk ? 'Compatible (requiere 8.1+)' : '⚠ Requiere PHP 8.1 o superior',
];
if (!$phpOk) $ok = false;

// ---- Extensiones ----
$exts = ['pdo', 'pdo_mysql', 'mbstring', 'fileinfo', 'json', 'session'];
foreach ($exts as $ext) {
    $loaded = extension_loaded($ext);
    $checks[] = ['label' => "Extensión: $ext", 'ok' => $loaded, 'msg' => $loaded ? 'Disponible' : '✗ FALTANTE'];
    if (!$loaded) $ok = false;
}

// GD (opcional pero recomendado para logo)
$gd = extension_loaded('gd');
$checks[] = ['label' => 'Extensión: gd', 'ok' => $gd, 'msg' => $gd ? 'Disponible (logo monocromo activo)' : '⚠ No disponible — el logo no se convertirá a bitmap (opcional)'];

// ---- Conexión BD ----
$dbConfig = require ROOT_PATH . '/config/database.php';
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
    $checks[] = ['label' => 'MySQL/MariaDB', 'ok' => true, 'msg' => "Conectado · $ver · BD: {$dbConfig['dbname']}"];

    // Verificar tablas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $required = ['empresa','usuarios','productos','subproductos','producto_subproducto','configuracion_impresora','impresiones'];
    $missing  = array_diff($required, $tables);
    if (empty($missing)) {
        $checks[] = ['label' => 'Tablas BD', 'ok' => true, 'msg' => count($tables) . ' tablas encontradas'];
    } else {
        $checks[] = ['label' => 'Tablas BD', 'ok' => false, 'msg' => '✗ Faltan tablas: ' . implode(', ', $missing) . ' — Importar database/migrations/001_initial_schema.sql'];
        $ok = false;
    }
} catch (Exception $e) {
    $checks[] = ['label' => 'MySQL/MariaDB', 'ok' => false, 'msg' => '✗ Sin conexión: ' . $e->getMessage() . ' — Editar config/database.php'];
    $ok = false;
}

// ---- Directorios con permisos de escritura ----
$dirs = [
    ROOT_PATH . '/public/uploads/logos' => 'public/uploads/logos (logos empresa)',
    ROOT_PATH . '/storage/logs'         => 'storage/logs (errores PHP)',
    ROOT_PATH . '/storage/temp'         => 'storage/temp (impresión temporal)',
];
foreach ($dirs as $path => $label) {
    if (!is_dir($path)) @mkdir($path, 0755, true);
    $writable = is_writable($path);
    $checks[] = ['label' => "Directorio: $label", 'ok' => $writable, 'msg' => $writable ? 'Existe y es escribible' : '✗ Sin permisos de escritura'];
    if (!$writable) $ok = false;
}

// ---- mod_rewrite ----
$rewrite = function_exists('apache_get_modules')
    ? in_array('mod_rewrite', apache_get_modules())
    : null;
if ($rewrite === true) {
    $checks[] = ['label' => 'Apache mod_rewrite', 'ok' => true, 'msg' => 'Habilitado'];
} elseif ($rewrite === false) {
    $checks[] = ['label' => 'Apache mod_rewrite', 'ok' => false, 'msg' => '✗ No habilitado — habilitar en httpd.conf'];
    $ok = false;
} else {
    $checks[] = ['label' => 'Apache mod_rewrite', 'ok' => true, 'msg' => 'No verificable desde PHP CLI (normal en XAMPP)'];
}

// ---- upload_max_filesize ----
$uploadMax = ini_get('upload_max_filesize');
$checks[] = ['label' => 'upload_max_filesize', 'ok' => true, 'msg' => $uploadMax . ' (recomendado: 8M o más)'];

// ---- Sistema operativo ----
$checks[] = ['label' => 'Sistema operativo', 'ok' => true, 'msg' => PHP_OS_FAMILY . ' — ' . php_uname('s') . ' ' . php_uname('r')];

// ---- basePath detection ----
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$checks[] = ['label' => 'URL base detectada', 'ok' => true, 'msg' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath . '/'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LabelPrint — Verificación del Sistema</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; padding: 24px; }
.wrap { max-width: 720px; margin: 0 auto; }
h1 { color: #1e293b; font-size: 1.5rem; margin-bottom: 4px; }
p.sub { color: #64748b; font-size: .9rem; margin-bottom: 24px; }
.card { background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 16px; }
.result { display: flex; align-items: flex-start; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
.result:last-child { border-bottom: none; }
.icon { font-size: 1.2rem; line-height: 1; flex-shrink: 0; }
.label { font-weight: 600; font-size: .88rem; color: #334155; }
.msg { font-size: .83rem; color: #64748b; margin-top: 2px; }
.ok .icon::before { content: '✓'; color: #059669; }
.fail .icon::before { content: '✗'; color: #dc2626; }
.banner { padding: 16px 20px; border-radius: 8px; font-weight: 700; font-size: 1rem; margin-bottom: 20px; }
.banner.success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
.banner.danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
.actions { margin-top: 16px; }
.btn { display: inline-block; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: .9rem; }
.btn-primary { background: #1a56db; color: #fff; }
.btn-secondary { background: #e2e8f0; color: #334155; margin-left: 8px; }
</style>
</head>
<body>
<div class="wrap">
    <h1>🖨️ LabelPrint — Verificación del Sistema</h1>
    <p class="sub">Comprueba que el entorno XAMPP esté configurado correctamente antes de usar el sistema.</p>

    <div class="banner <?= $ok ? 'success' : 'danger' ?>">
        <?= $ok ? '✓ El sistema está listo para usarse.' : '⚠ Hay problemas que deben corregirse antes de usar el sistema.' ?>
    </div>

    <div class="card">
        <?php foreach ($checks as $c): ?>
        <div class="result <?= $c['ok'] ? 'ok' : 'fail' ?>">
            <div class="icon"></div>
            <div>
                <div class="label"><?= htmlspecialchars($c['label']) ?></div>
                <div class="msg"><?= htmlspecialchars($c['msg']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="actions">
        <?php if ($ok): ?>
        <a href="<?= htmlspecialchars($basePath) ?>/" class="btn btn-primary">Ir al sistema →</a>
        <?php endif; ?>
        <a href="check.php" class="btn btn-secondary">↺ Volver a verificar</a>
    </div>

    <p style="margin-top:16px;font-size:.8rem;color:#94a3b8;">
        ⚠ Elimine o proteja este archivo (check.php) antes de pasar a producción.
    </p>
</div>
</body>
</html>
