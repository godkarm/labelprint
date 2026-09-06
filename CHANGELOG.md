# CHANGELOG

## [1.0.0] - 2026-08-30

### Added
- Sistema completo de impresión de etiquetas TSC TE200
- Autenticación con bcrypt (login/logout)
- Dashboard con estadísticas del día y mes
- Módulo Empresa: nombre + logo (PNG/JPG/JPEG/WEBP)
- CRUD Productos con código, nombre, toggle activo/inactivo
- CRUD Subproductos (descripción textual, NO color visual)
- Asociación Producto → Subproductos (N:M)
- Pantalla de impresión con vista previa en tiempo real
- Validación de formulario frontend (JS) y backend (PHP)
- Generación de comandos TSPL2 para TSC TE200 (80×40 mm, 203 DPI)
- Envío de impresión por TCP/IP (fsockopen, puerto 9100)
- Envío de impresión por USB (/dev/usb/lp0 Linux, copy /b Windows)
- Soporte de impresora compartida (lpr / copy /b)
- Procesamiento de logo: redimensionado + conversión a bitmap monocromo TSPL2
- Historial de impresiones con filtros (producto, estado, fecha) y paginación
- Detalle de impresión con vista previa de etiqueta
- Reimpresión desde historial (conserva datos originales)
- Configuración de impresora (DPI, velocidad, densidad, conexión, IP, puerto)
- Prueba de impresión (etiqueta de prueba con líneas de referencia)
- Etiqueta de calibración de dimensiones
- API AJAX: /api/productos, /api/subproductos, /api/preview, /api/imprimir, /api/impresora/estado
- Creación rápida de subproducto desde pantalla de impresión
- Protección CSRF en todos los formularios
- Prepared statements en todas las queries SQL
- Protección XSS con htmlspecialchars() en outputs
- Validación de uploads (MIME, extensión, tamaño, nombre seguro)
- Bloqueo de ejecución de scripts en directorio uploads
- Logs de errores PHP en storage/logs/
- Documentación completa: CLAUDE.md, PROJECT.md, ANALISIS.md, README.md
- Base de datos relacional con InnoDB, UTF8MB4, claves foráneas

### Arquitectura
- Patrón MVC con PHP 8.x
- Router simple con soporte de rutas paramétricas
- Autoloader PSR-4 sin Composer
- Base de datos vía PDO singleton con prepared statements
- Separación: Controllers / Models(DB) / Services / Views / Core

### Notas de implementación
- "Color" en requerimientos originales = Subproducto (VARCHAR 500, texto descriptivo)
- El historial guarda snapshots del subproducto para auditoría exacta
- El logo se convierte a bitmap 1bpp para compatibilidad con TSPL2
- La impresión desde web requiere PHP en el servidor, no desde el navegador

## [1.1.0] - 2026-08-31

### Added
- Compatibilidad completa con entorno local XAMPP (Windows + Apache + PHP + MySQL).
- Assets Bootstrap 5 y Bootstrap Icons descargados localmente (`public/assets/`) — el sistema funciona sin Internet.
- Archivo `.env.example` con configuración de ejemplo para XAMPP.
- Carga automática de `.env` desde `config/app.php` (opcional).
- Página de verificación del sistema: `public/check.php` (sin login) y `/check` (con login).
- `CheckController` y vista `check/index.php` integrados en el menú lateral.
- Ruta `/check` añadida en `routes/web.php`.
- `config/database.php`: host por defecto cambiado a `127.0.0.1` (XAMPP estándar).

### Changed
- `BaseController::requireAuth()`: usa `basePath()` en lugar de ruta absoluta `/login` — corrige `ERR_TOO_MANY_REDIRECTS` en XAMPP subcarpeta.
- `BaseController::redirect()`: centralizado en método con `basePath()`.
- `AuthController`: todos los redirects usan `$this->redirect()` (vía basePath).
- `AuthController::logout()`: usa `$this->redirect('/login')` consistentemente.
- `public/.htaccess`: añadida condición para pasar archivos y directorios reales antes del rewrite — previene bucles.
- `app/Views/layouts/main.php`: Bootstrap y Bootstrap Icons cargados desde assets locales.
- `app/Views/layouts/minimal.php`: ídem.
- `config/app.php`: `logo_dir` usa `DIRECTORY_SEPARATOR` para compatibilidad Windows/Linux.
- `app/Services/PrinterService.php`: `sendUsb()` corregido para Windows — usa `copy /b` con nombre de impresora instalada en Windows; fallback usa `ROOT_PATH . DIRECTORY_SEPARATOR`.

### Fixed
- `ERR_TOO_MANY_REDIRECTS` al acceder desde XAMPP en subcarpeta.
- Assets CSS/JS no cargaban sin Internet (CDN bloqueada).
- Comando `copy /b` con escape incorrecto del nombre de impresora en Windows.
- Ruta de archivo temporal en `sendUsb()` usaba `__DIR__` hardcodeado.

### Documentation
- `CLAUDE.md`: sección 15 — Compatibilidad XAMPP añadida.
- `PROJECT.md`: sección de entorno XAMPP añadida.
- `ANALISIS.md`: análisis de compatibilidad XAMPP añadido.
- `README.md`: instrucciones XAMPP completas.

## [1.2.0] - 2026-08-31

### Fixed
- Corregido el almacenamiento del Nombre de Empresa en los registros de impresión.
- El INSERT en `impresiones` ahora incluye `nombre_empresa` (snapshot histórico).
- La reimpresión ahora usa el nombre de empresa del registro original, no el nombre actual.
- Corregida la visualización del Nombre de Empresa en el historial (columna "Empresa").
- Corregida la visualización del Nombre de Empresa en el detalle de impresión.
- Corregida la visualización en el dashboard (tabla de últimas impresiones).

### Added
- Campo `nombre_empresa VARCHAR(255)` en tabla `impresiones`.
- Migración `002_add_empresa_to_impresiones.sql` para instalaciones existentes.
- Validación en `LabelService::validate()`: verifica que exista nombre de empresa antes de imprimir.
- Columna "Empresa" en historial, detalle e impresiones del dashboard.

### Changed
- `LabelService::registrarImpresion()`: INSERT incluye `nombre_empresa`.
- `LabelService::registrarReimpresion()`: INSERT propaga `nombre_empresa` del original.
- `LabelService::reprint()`: toma nombre de empresa del snapshot del registro original.
- `001_initial_schema.sql`: columna `nombre_empresa` incluida en definición de tabla `impresiones`.

### Documentation
- `CLAUDE.md`: regla 16 — Snapshot de nombre de empresa añadida.
- `PROJECT.md`: documentado campo nombre_empresa.
- `ANALISIS.md`: causa y solución registradas.

## [1.3.0] - 2026-08-31

### Added
- Botón Eliminar individual en cada fila del Historial de Impresiones.
- Modal de confirmación antes de eliminar (muestra producto, empresa y fecha).
- Endpoint POST `/historial/{id}/eliminar` con validación CSRF, ID numérico y existencia del registro.
- La fila se elimina de la tabla sin recargar la página (fade-out animado).

### Fixed
- Vista previa en detalle de impresión ahora usa `nombre_empresa` del snapshot histórico.
- La empresa en la vista previa ya no toma el nombre actual sino el almacenado en la impresión.
- Logo en vista previa del detalle ahora carga el archivo de logo actual (el logo es un archivo físico, no snapshot).
- Búsqueda en historial incluye ahora también `nombre_empresa` en el filtro.

### Changed
- `HistorialController::show()`: pasa `$logoEmpresa` a la vista para mostrar el logo actual.
- `HistorialController::index()`: filtro `buscar` ahora incluye `nombre_empresa`.
- `historial/index.php`: filas con `data-id` para eliminación AJAX sin recarga.
- `historial/show.php`: vista previa usa `$impresion['nombre_empresa']` directamente.

## [1.4.0] - 2026-09-01

### Fixed
- Corregido el botón Reimprimir en Historial de Impresiones (no respondía).
- Corregido el botón Eliminar en Historial de Impresiones (no respondía).
- Corregido el botón Reimprimir en Detalle de Impresión (no respondía).
- Corregido el botón Eliminar en Detalle de Impresión (no respondía).

### Causa raíz
Bootstrap.js y app.js se cargaban al FINAL del body en el layout. Los scripts inline
de las vistas se ejecutaban antes — `new bootstrap.Modal()` fallaba con TypeError porque
Bootstrap no estaba disponible, silenciando todo el bloque JS sin mensaje de error visible.

### Changed
- `layouts/main.php`: Bootstrap.bundle.min.js y app.js movidos al `<head>` para garantizar
  disponibilidad antes de cualquier script inline del body.
- `layouts/minimal.php`: ídem.
- `historial/index.php`: JS reescrito como IIFE sin DOMContentLoaded (Bootstrap ya disponible).
  Funciones `reimprimir()` y `confirmarEliminar()` expuestas en `window` para onclick inline.
  Helper `fetchJSON()` detecta respuestas no-JSON (ej: redirect a login por sesión expirada).
- `historial/show.php`: ídem. ID de impresión fijo en constante `IMP_ID` (PHP). Redirección
  automática al historial tras eliminar exitosamente.
