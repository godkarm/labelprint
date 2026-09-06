<?php

/** @var \App\Core\Router $router */

// ============================================================
// VERIFICACIÓN DEL SISTEMA (XAMPP check)
// ============================================================
$router->get('/check', 'CheckController', 'index');

// ============================================================
// AUTENTICACIÓN
// ============================================================
$router->get('/login',  'AuthController', 'showLogin');
$router->post('/login', 'AuthController', 'login');
$router->get('/logout', 'AuthController', 'logout');

// ============================================================
// DASHBOARD
// ============================================================
$router->get('/',          'DashboardController', 'index');
$router->get('/dashboard', 'DashboardController', 'index');

// ============================================================
// EMPRESA
// ============================================================
$router->get('/empresa',          'EmpresaController', 'index');
$router->post('/empresa',         'EmpresaController', 'update');
$router->post('/empresa/logo',    'EmpresaController', 'uploadLogo');
$router->post('/empresa/logo/delete', 'EmpresaController', 'deleteLogo');

// ============================================================
// PRODUCTOS
// ============================================================
$router->get('/productos',              'ProductoController', 'index');
$router->get('/productos/crear',        'ProductoController', 'create');
$router->post('/productos/crear',       'ProductoController', 'store');
$router->get('/productos/{id}/editar',  'ProductoController', 'edit');
$router->post('/productos/{id}/editar', 'ProductoController', 'update');
$router->post('/productos/{id}/toggle', 'ProductoController', 'toggle');

// ============================================================
// SUBPRODUCTOS
// ============================================================
$router->get('/subproductos',              'SubproductoController', 'index');
$router->get('/subproductos/crear',        'SubproductoController', 'create');
$router->post('/subproductos/crear',       'SubproductoController', 'store');
$router->get('/subproductos/{id}/editar',  'SubproductoController', 'edit');
$router->post('/subproductos/{id}/editar', 'SubproductoController', 'update');
$router->post('/subproductos/{id}/toggle', 'SubproductoController', 'toggle');
$router->post('/subproductos/{id}/vincular', 'SubproductoController', 'vincular');

// ============================================================
// ETIQUETAS / IMPRESIÓN
// ============================================================
$router->get('/etiquetas',        'EtiquetaController', 'index');
$router->post('/etiquetas/imprimir', 'EtiquetaController', 'imprimir');

// ============================================================
// HISTORIAL
// ============================================================
$router->get('/historial',              'HistorialController', 'index');
$router->get('/historial/{id}',         'HistorialController', 'show');
$router->post('/historial/{id}/reimprimir', 'HistorialController', 'reimprimir');
$router->post('/historial/{id}/eliminar',   'HistorialController', 'eliminar');

// ============================================================
// CONFIGURACIÓN
// ============================================================
$router->get('/configuracion',              'ConfiguracionController', 'index');
$router->post('/configuracion/impresora',   'ConfiguracionController', 'updateImpresora');
$router->post('/configuracion/prueba',      'ConfiguracionController', 'prueba');
$router->post('/configuracion/calibracion', 'ConfiguracionController', 'calibracion');

// ============================================================
// API (AJAX)
// ============================================================
$router->get('/api/productos',              'ApiController', 'productos');
$router->get('/api/subproductos',           'ApiController', 'subproductos');
$router->post('/api/preview',               'ApiController', 'preview');
$router->post('/api/imprimir',              'ApiController', 'imprimir');
$router->get('/api/impresora/estado',       'ApiController', 'estadoImpresora');
$router->post('/api/subproductos/crear',    'ApiController', 'crearSubproducto');
