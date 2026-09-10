<?php

// ============================================================
// LABELPRINT — Configuración Principal
// Compatible con XAMPP (Windows/Linux/Mac)
// ============================================================

// Cargar .env si existe (opcional, para desarrollo local)
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        if (!empty($key) && !isset($_ENV[$key])) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

return [
    'name'    => 'LabelPrint — TSC TE200',
    'version' => '1.1.0',
    'debug'   => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),  // true=mostrar errores en XAMPP local
    'url'     => getenv('APP_URL') ?: 'http://localhost/labelprint/public',
    'timezone'=> getenv('APP_TIMEZONE') ?: 'America/Lima',
    'env'     => getenv('APP_ENV') ?: 'local',

    'upload' => [
        'max_size'    => 5 * 1024 * 1024,   // 5 MB
        'allowed_ext' => ['png', 'jpg', 'jpeg', 'webp'],
        'allowed_mime'=> ['image/png', 'image/jpeg', 'image/webp'],
        // Ruta usando DIRECTORY_SEPARATOR para compatibilidad Windows/Linux
        'logo_dir'    => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'logos' . DIRECTORY_SEPARATOR,
    ],

    'label' => [
        'width_mm'   => 100,
        'height_mm'  => 200,
        'dpi'        => 203,
        'orientation'=> 'vertical',
    ],

    'print' => [
        'max_copies' => 999,
        'speed'      => 4,
        'density'    => 8,
    ],

    'turnos' => [
        1 => '1 - Mañana',
        2 => '2 - Tarde',
        3 => '3 - Noche',
    ],
];
