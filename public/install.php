<?php
/**
 * LABELPRINT — Instalador de Base de Datos
 *
 * Usar SOLO si phpMyAdmin no puede importar el SQL.
 * Acceder via: http://localhost/labelprint/public/install.php
 *
 * ELIMINAR ESTE ARCHIVO después de instalar.
 */

// Seguridad básica: solo funciona desde localhost
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteIp, ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    die('Acceso denegado.');
}

define('ROOT_PATH', dirname(__DIR__));

// Cargar config de BD
$dbCfg = require ROOT_PATH . '/config/database.php';

$host = $dbCfg['host'];
$port = $dbCfg['port'];
$user = $dbCfg['user'];
$pass = $dbCfg['password'];
$db   = $dbCfg['dbname'];

$action = $_POST['action'] ?? '';
$result = [];

if ($action === 'install') {
    try {
        // Conectar sin seleccionar BD
        $pdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Crear BD si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db}`");

        // Hash correcto para admin123
        $hash = '$2y$10$jA8eLk5JiYGIMMxaHfKr.e7.jF/SG27/O.F/GKMA/hBxVM7jB33TO';

        $sqls = [
            "SET FOREIGN_KEY_CHECKS = 0",

            "DROP TABLE IF EXISTS `impresiones`",
            "DROP TABLE IF EXISTS `producto_subproducto`",
            "DROP TABLE IF EXISTS `subproductos`",
            "DROP TABLE IF EXISTS `productos`",
            "DROP TABLE IF EXISTS `configuracion_impresora`",
            "DROP TABLE IF EXISTS `usuarios`",
            "DROP TABLE IF EXISTS `empresa`",

            "CREATE TABLE `empresa` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `nombre` VARCHAR(255) NOT NULL DEFAULT '',
              `logo` VARCHAR(500) NULL DEFAULT NULL,
              `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT INTO `empresa` (`nombre`) VALUES ('Mi Empresa')",

            "CREATE TABLE `usuarios` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `nombre` VARCHAR(255) NOT NULL,
              `email` VARCHAR(255) NOT NULL,
              `password` VARCHAR(255) NOT NULL,
              `rol` VARCHAR(20) NOT NULL DEFAULT 'operador',
              `activo` TINYINT(1) NOT NULL DEFAULT 1,
              `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`, `activo`) VALUES
             ('Administrador', 'admin@labelprint.local', '{$hash}', 'admin', 1)",

            "CREATE TABLE `productos` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `codigo` VARCHAR(100) NOT NULL,
              `nombre` VARCHAR(255) NOT NULL,
              `activo` TINYINT(1) NOT NULL DEFAULT 1,
              `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_codigo` (`codigo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE `subproductos` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `descripcion` VARCHAR(500) NOT NULL,
              `activo` TINYINT(1) NOT NULL DEFAULT 1,
              `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE `producto_subproducto` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `producto_id` INT UNSIGNED NOT NULL,
              `subproducto_id` INT UNSIGNED NOT NULL,
              `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_prod_sub` (`producto_id`, `subproducto_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE `configuracion_impresora` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `nombre` VARCHAR(255) NOT NULL DEFAULT 'TSC TE200',
              `modelo` VARCHAR(100) NOT NULL DEFAULT 'TE200',
              `dpi` INT NOT NULL DEFAULT 203,
              `ancho_mm` DECIMAL(8,2) NOT NULL DEFAULT 200.00,
              `alto_mm` DECIMAL(8,2) NOT NULL DEFAULT 100.00,
              `velocidad` INT NOT NULL DEFAULT 4,
              `densidad` INT NOT NULL DEFAULT 8,
              `orientacion` VARCHAR(20) NOT NULL DEFAULT 'horizontal',
              `tipo_conexion` VARCHAR(20) NOT NULL DEFAULT 'usb',
              `ip` VARCHAR(45) NULL DEFAULT NULL,
              `puerto` INT NULL DEFAULT 9100,
              `nombre_compartido` VARCHAR(255) NULL DEFAULT NULL,
              `activo` TINYINT(1) NOT NULL DEFAULT 1,
              `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT INTO `configuracion_impresora`
             (`nombre`,`modelo`,`dpi`,`ancho_mm`,`alto_mm`,`velocidad`,`densidad`,`orientacion`,`tipo_conexion`)
             VALUES ('TSC TE200','TE200',203,200.00,100.00,4,8,'horizontal','usb')",

            "CREATE TABLE `impresiones` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `nombre_empresa` VARCHAR(255) NOT NULL DEFAULT '',
              `producto_id` INT UNSIGNED NULL DEFAULT NULL,
              `producto_nombre` VARCHAR(255) NOT NULL DEFAULT '',
              `subproducto_id` INT UNSIGNED NULL DEFAULT NULL,
              `subproducto_descripcion` VARCHAR(500) NOT NULL DEFAULT '',
              `cantidad` INT UNSIGNED NOT NULL DEFAULT 0,
              `turno` TINYINT(1) NOT NULL DEFAULT 1,
              `fecha_etiqueta` DATE NOT NULL,
              `copias` INT UNSIGNED NOT NULL DEFAULT 1,
              `usuario_id` INT UNSIGNED NULL DEFAULT NULL,
              `impresora_id` INT UNSIGNED NULL DEFAULT NULL,
              `estado` VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
              `error_detalle` TEXT NULL DEFAULT NULL,
              `es_reimpresion` TINYINT(1) NOT NULL DEFAULT 0,
              `impresion_original_id` INT UNSIGNED NULL DEFAULT NULL,
              `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_creado` (`creado_en`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "INSERT INTO `productos` (`codigo`,`nombre`) VALUES
             ('BOLSA-UVA-2KG','BOLSA UVA 2 KG'),
             ('CAJA-PALTA-4KG','CAJA PALTA 4 KG')",

            "INSERT INTO `subproductos` (`descripcion`) VALUES
             ('501-uhb-CC100510'),
             ('501B - NATURAL CCX1103000')",

            "INSERT INTO `producto_subproducto` (`producto_id`,`subproducto_id`) VALUES (1,1),(2,2)",

            "SET FOREIGN_KEY_CHECKS = 1",
        ];

        foreach ($sqls as $sql) {
            $pdo->exec($sql);
            $result[] = ['ok' => true, 'sql' => substr(trim($sql), 0, 60) . '…'];
        }

        $result[] = ['ok' => true, 'sql' => '✓ INSTALACIÓN COMPLETADA'];

    } catch (Exception $e) {
        $result[] = ['ok' => false, 'sql' => 'ERROR: ' . $e->getMessage()];
    }
}

// Verificar si ya está instalado
$installed = false;
try {
    $pdo2 = new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $tables = $pdo2->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $required = ['empresa','usuarios','productos','subproductos','configuracion_impresora','impresiones'];
    $installed = count(array_intersect($required, $tables)) === count($required);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>LabelPrint — Instalador</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#f1f5f9;margin:0;padding:24px}
.wrap{max-width:640px;margin:0 auto}
h1{color:#1e293b;font-size:1.4rem}
.card{background:#fff;border-radius:10px;border:1px solid #e2e8f0;padding:24px;margin-bottom:16px}
.ok{color:#059669}.err{color:#dc2626}
.btn{display:inline-block;padding:12px 28px;background:#1a56db;color:#fff;border:none;border-radius:6px;font-size:1rem;cursor:pointer;font-weight:600}
.btn:hover{background:#1549b8}
.result{font-family:monospace;font-size:.82rem;background:#f8fafc;border-radius:6px;padding:12px;margin-top:12px;max-height:300px;overflow-y:auto}
.badge-ok{background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-size:.75rem}
.badge-err{background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:4px;font-size:.75rem}
.installed{background:#dcfce7;border:1px solid #86efac;padding:12px 16px;border-radius:8px;color:#166534;font-weight:600}
.warning{background:#fef9c3;border:1px solid #fde047;padding:12px 16px;border-radius:8px;color:#854d0e;font-size:.85rem;margin-top:12px}
</style>
</head>
<body>
<div class="wrap">
    <h1>🖨️ LabelPrint — Instalador de Base de Datos</h1>

    <?php if ($installed): ?>
    <div class="installed">
        ✓ La base de datos ya está instalada correctamente.
        <a href="/" style="display:block;margin-top:8px;color:#166534">→ Ir al sistema</a>
    </div>
    <?php else: ?>
    <div class="card">
        <p><strong>Configuración detectada:</strong></p>
        <ul style="font-size:.9rem;color:#475569">
            <li>Host: <code><?= htmlspecialchars($host) ?>:<?= (int)$port ?></code></li>
            <li>Usuario: <code><?= htmlspecialchars($user) ?></code></li>
            <li>Base de datos: <code><?= htmlspecialchars($db) ?></code></li>
        </ul>
        <form method="POST">
            <input type="hidden" name="action" value="install">
            <button type="submit" class="btn">⚙️ Instalar base de datos ahora</button>
        </form>
        <?php if (!empty($result)): ?>
        <div class="result">
            <?php foreach ($result as $r): ?>
            <div>
                <span class="<?= $r['ok'] ? 'badge-ok' : 'badge-err' ?>">
                    <?= $r['ok'] ? 'OK' : 'ERR' ?>
                </span>
                <?= htmlspecialchars($r['sql']) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        $hasError = count(array_filter($result, fn($r) => !$r['ok'])) > 0;
        if (!$hasError): ?>
        <div style="margin-top:12px;padding:12px;background:#dcfce7;border-radius:6px;color:#166534">
            ✓ Instalación completada.
            <strong>Credenciales:</strong> admin@labelprint.local / admin123<br>
            <a href="/" style="color:#166534">→ Ir al sistema</a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="warning">
        ⚠️ <strong>Seguridad:</strong> Elimine este archivo después de instalar.<br>
        Ruta: <code>C:\xampp\htdocs\labelprint\public\install.php</code>
    </div>
</div>
</body>
</html>
