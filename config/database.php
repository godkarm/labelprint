<?php

// ============================================================
// LABELPRINT — Configuración de Base de Datos
// Compatible con XAMPP (Windows/Linux)
//
// Modificar los valores según su entorno local:
//   XAMPP Windows: host=127.0.0.1, user=root, password=''
//   XAMPP Linux:   host=127.0.0.1, user=root, password=''
//
// Se puede usar variables de entorno o editar directamente aquí.
// NO subir contraseñas reales a repositorios públicos.
// ============================================================

return [
    'host'     => getenv('DB_HOST')   ?: '127.0.0.1',
    'port'     => (int)(getenv('DB_PORT') ?: 3306),
    'dbname'   => getenv('DB_NAME')   ?: 'labelprint',
    'user'     => getenv('DB_USER')   ?: 'root',
    'password' => getenv('DB_PASS')   ?: '',
];
