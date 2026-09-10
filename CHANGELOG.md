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

## [1.5.0] - 2026-09-01

### Fixed
- Corregida codificación de caracteres en TSPL2: tildes (á,é,í,ó,ú) y Ñ/ñ ahora se
  transliteran a ASCII antes de enviar a la impresora (TE200 usa CP850, no UTF-8).
  Esto corregía impresión corrupta o rechazo silencioso del trabajo.
- Corregido envío USB en Windows: cascada de 3 métodos (copy /b por nombre,
  copy /b por puerto USB001/COM3, PowerShell Out-Printer).
- Corregido: el archivo .prn se guarda siempre en storage/temp/ para diagnóstico.

### Added
- Campo "Puerto USB de Windows" en Configuración → Impresora (USB001, USB002, COM3...).
- Botón "Descargar archivo .prn" en pantalla de prueba para impresión manual.
- Visor de TSPL2 generado en pantalla de prueba de impresión.
- Endpoint GET /storage/temp/{file} para descarga segura de archivos .prn.
- Guía de conexión USB en Windows visible en pantalla de Configuración.
- Mensajes de error detallados que explican qué verificar cuando falla la impresión.

### Changed
- PrinterService::send() ahora retorna siempre 'tspl' y 'prn_file' en la respuesta.
- PrinterService::tspl() (antes sanitizeTspl): transliteración de caracteres especiales.
- Texto de turno en TSPL: "1-MANANA" en lugar de "1-MAÑANA" para evitar corrupción.
- ConfiguracionController::prueba(): devuelve 'tspl' y 'prn_file' al frontend.

## [1.5.1] - 2026-09-01

### Fixed (crítico)
- **BUG PRINCIPAL DE IMPRESIÓN:** `ApiController::imprimir()` leía `php://input`
  dos veces (stream no rebobinable). La primera lectura obtenía el CSRF del JSON,
  la segunda devolvía cadena vacía → `$data = null` → validación fallaba con
  "Seleccione un producto" aunque el usuario lo hubiera seleccionado.
  **Corregido:** se lee el stream UNA sola vez, se cachea en `$rawInput`, se
  extrae CSRF y datos del mismo JSON decodificado.
- `LabelService::print()` y `reprint()` ahora propagan `prn_file` y `tspl` al
  resultado devuelto al frontend.
- `etiquetas.js`: cuando la impresión falla pero existe archivo .prn,
  se muestra botón de descarga directa para impresión manual.

## [1.5.2] - 2026-09-01

### Added
- Página de diagnóstico de impresión paso a paso: `/configuracion/diagnostico`
  Muestra el resultado de cada capa: BD → TSPL → .prn → envío → respuesta.
  Incluye detección del usuario bajo el que corre Apache (causa más probable del fallo USB).
- Script `storage/print_raw.ps1`: PowerShell con RawPrinterHelper (.NET Win32 API)
  para envío RAW al spooler de Windows — funciona incluso desde SYSTEM si la
  impresora está instalada para todos los usuarios.
- Script `storage/print_helper.vbs`: alternativa VBScript para envío via WScript.
- Nuevo método `sendUsbWindows()`: cascada de 5 métodos (PS RawPrinterHelper externo,
  PS inline encoded, copy/b nombre, copy/b puerto, VBScript).
- Enlace "Diagnóstico impresión" en el menú lateral.
- Botón "Diagnóstico paso a paso" en la pantalla de Configuración.

### Root cause análisis
El fallo de impresión USB en XAMPP tiene 3 causas posibles en orden de probabilidad:
1. `php://input` leído dos veces en `ApiController::imprimir()` → ya corregido en v1.5.1.
2. Apache corre como `NT AUTHORITY\SYSTEM` → sin acceso a impresoras del usuario → 
   se añade cascada de 5 métodos y guía para cambiar la cuenta de Apache.
3. Nombre de impresora en configuración no coincide con Windows → diagnóstico
   muestra el `whoami` del proceso Apache para confirmar.

## [1.6.0] - 2026-09-01

### Fixed (crítico)
- **Dimensiones de etiqueta corregidas:** el material real es 100×200mm (vertical),
  no 80×40mm (horizontal). Se ajusta todo el sistema con los parámetros del
  material "ETIQUETAS 4X8" (Ancho: 100.0mm, Alto: 200.0mm, vertical).

### Changed
- `PrinterService::generateTspl()`: layout vertical 100×200mm, área útil 95%
  (márgenes x=20dots/2.5mm, y=40dots/5mm). Marco BOX 20,40,783,1566.
  Distribución de campos: EMPRESA, PRODUCTO, COLOR, CANTIDAD, TURNO, FECHA, COPIAS
  con separadores BAR entre cada sección y fuentes escaladas (tamaños 2-5 TSPL).
- `PrinterService::generateTestTspl()`: etiqueta de calibración 100×200mm con
  líneas de referencia cada 20mm en ambos ejes.
- `PrinterService`: constantes `LABEL_W_MM=100`, `LABEL_H_MM=200`,
  `LABEL_W_DOTS=803`, `LABEL_H_DOTS=1606`, `MX=20`, `MY=40`.
- `config/app.php`: `width_mm=100`, `height_mm=200`, `orientation=vertical`.
- `database/migrations/001_initial_schema.sql`: config impresora por defecto 100×200mm.
- `public/assets/css/app.css`: vista previa 200×400px (proporción 1:2 vertical).
  `.lp-label-bottom` cambia a flex-column para layout vertical.
- `app/Views/etiquetas/index.php`: badge "100×200mm Vertical", campo COPIAS en preview.
- `app/Views/historial/show.php`: campo COPIAS en vista previa del detalle.
- `app/Views/empresa/index.php`: campo COPIAS en vista previa de empresa.
- `CLAUDE.md`: dimensiones actualizadas.

### Added
- `database/migrations/003_fix_label_dimensions.sql`: migración para instalaciones
  existentes que actualiza `configuracion_impresora` a 100×200mm vertical.

## [1.7.0] - 2026-09-08

### Fixed (crítico)
- **Dimensiones corregidas:** 200×100mm horizontal (ancho=200mm largo de avance,
  alto=100mm ancho de papel) según configuración confirmada por el usuario.
- **Constantes hardcodeadas eliminadas:** `generateTspl()` ahora lee SIEMPRE
  ancho_mm/alto_mm/velocidad/densidad desde `$this->config` (BD). Las constantes
  eran la causa por la que cambiar la configuración no tenía efecto en la impresión.
- **Campo COPIAS eliminado de la etiqueta:** la etiqueta ya no muestra "COPIAS / 1".
  El sistema siempre imprime 1 copia (campo hidden en el formulario).
- **Campo copias eliminado del formulario de impresión** — solo existe la cantidad del producto.
- **Logo (LogoProcessor):** posición corregida para 200×100mm (x=50, y=25, maxW=100, maxH=60 dots).
  Se agrega logging para diagnosticar si GD no está disponible.
- **CSS vista previa:** 360×180px (proporción 2:1 horizontal, igual que 200×100mm).
- **Opciones de orientación** en configuración: eliminado texto hardcodeado "80×40".

### Changed
- `PrinterService::generateTspl()`: lee dimensiones de `$this->config`, calcula
  dots dinámicamente. Layout: empresa+logo (header), PRODUCTO, COLOR, CANTIDAD|TURNO|FECHA (3 col).
- `PrinterService::generateTestTspl()`: lee dimensiones de `$this->config`.
- `LogoProcessor::toTsplBitmap()`: x=50, y=25, maxW=100, maxH=60 dots (200×100mm).
- `database/migrations/001_initial_schema.sql`: ancho=200, alto=100, horizontal.
- `database/migrations/003_fix_label_dimensions.sql`: actualizado a 200×100 horizontal.
- `config/app.php`: width_mm=200, height_mm=100, orientation=horizontal.

## [1.7.1] - 2026-09-08

### Fixed (crítico)
- **Error 500 en login:** `use App\Core\Session` y `use App\Core\Router` estaban
  ubicados en la línea 40 de `public/index.php`, DESPUÉS de código PHP ejecutable
  (defines, spl_autoload_register, require, etc.). En PHP 8 esto genera
  `Fatal error: Cannot use statement after executable code`.
  **Corrección:** eliminados los `use` statements; las clases ahora se referencian
  con FQCN completo (`\App\Core\Session::start()`, `new \App\Core\Router()`).
- `config/app.php`: `debug=true` por defecto para entorno XAMPP local, permitiendo
  ver errores PHP directamente en el navegador durante el desarrollo.

## [1.8.0] - 2026-09-08

### Fixed (crítico)
- **"Table usuarios doesn't exist in engine":** El SQL original usaba ENUM y
  claves foráneas que causaban que InnoDB rechazara silenciosamente las tablas
  en algunas versiones de XAMPP/MariaDB. El nuevo schema usa VARCHAR en lugar
  de ENUM, elimina las FK constraints, agrega SET FOREIGN_KEY_CHECKS=0 al inicio
  y DROP TABLE IF EXISTS antes de cada CREATE.
- **Error 500 en login (use después de código ejecutable):** `public/index.php`
  tenía `use App\Core\Session` en línea 40, después de código ejecutable PHP.
  PHP 8 lanza Fatal error en ese caso. Corregido usando FQCN completo.

### Added
- `public/install.php`: instalador PHP alternativo para cuando phpMyAdmin
  no puede importar el SQL. Acceder a http://localhost/labelprint/public/install.php
  Crear las tablas directamente via PDO, sin depender de phpMyAdmin.
  ⚠️ Eliminar después de instalar.

### Changed
- `database/migrations/001_initial_schema.sql`: reescrito completamente.
  Sin FK constraints, sin ENUM (usa VARCHAR), con DROP IF EXISTS antes de cada
  tabla, SET FOREIGN_KEY_CHECKS=0/1 envolviendo todo el script.
  Compatible con MySQL 5.7+ y MariaDB 10.x de XAMPP.
