# PROJECT.md — LabelPrint

## Información General

- **Nombre:** LabelPrint — Sistema de Impresión de Etiquetas
- **Versión:** 1.0.0
- **Objetivo:** Gestionar y enviar etiquetas de 40×80 mm a la impresora TSC TE200
- **Arquitectura:** PHP 8.x MVC, MySQL, Bootstrap 5, TSPL2

---

## Mapa de Archivos → Función

```
public/index.php
→ Front controller. Autoloader, sesión, routing.

routes/web.php
→ Todas las rutas web y API del sistema.

config/app.php
→ Configuración general: timezone, uploads, label, print, turnos.

config/database.php
→ Credenciales de conexión MySQL.

app/Core/Database.php
→ Singleton PDO. Métodos: query, fetchAll, fetchOne, lastInsertId, transacciones.

app/Core/Router.php
→ Router simple. Soporta rutas GET/POST con parámetros {id}.

app/Core/View.php
→ Renderizado de vistas con layout. json(), redirect(), e() para XSS.

app/Core/Session.php
→ Gestión de sesiones. Login, flash messages, token CSRF.

app/Core/ViewConfig.php
→ Almacena basePath global para vistas.

app/Controllers/BaseController.php
→ requireAuth(), verifyCsrf(), jsonSuccess(), jsonError(), redirect(), flashAndRedirect().

app/Controllers/AuthController.php
→ Login y logout. Verificación bcrypt.

app/Controllers/DashboardController.php
→ Estadísticas del día/mes. Últimas 10 impresiones.

app/Controllers/EmpresaController.php
→ Nombre empresa. Upload/delete de logo usando LogoProcessor.

app/Controllers/ProductoController.php
→ CRUD productos. Toggle activo/inactivo.

app/Controllers/SubproductoController.php
→ CRUD subproductos (texto descriptivo). Asociación con productos.

app/Controllers/EtiquetaController.php
→ Pantalla principal de impresión. Validación + LabelService::print().

app/Controllers/HistorialController.php
→ Listado con filtros/paginación. Detalle. Reimpresión vía LabelService::reprint().

app/Controllers/ConfiguracionController.php
→ Configuración impresora. Prueba de impresión. Calibración.

app/Controllers/ApiController.php
→ Endpoints AJAX: /api/productos, /api/subproductos, /api/preview, /api/imprimir, /api/impresora/estado.

app/Services/PrinterService.php
→ Generación TSPL2. Envío a impresora (TCP socket / USB device / shared printer).

app/Services/LabelService.php
→ Orquestador de impresión. Valida datos, prepara etiqueta, llama PrinterService, registra historial.

app/Services/LogoProcessor.php
→ Valida uploads (MIME, extensión, tamaño). Redimensiona. Convierte a bitmap TSPL2 monocromo.

database/migrations/001_initial_schema.sql
→ Schema completo: empresa, usuarios, productos, subproductos, producto_subproducto,
  configuracion_impresora, impresiones. Datos semilla incluidos.
```

---

## Funcionalidades Implementadas

| Módulo | Estado |
|---|---|
| Autenticación (login/logout) | ✅ |
| Dashboard con estadísticas | ✅ |
| Configuración de empresa | ✅ |
| Upload/delete de logo | ✅ |
| CRUD Productos | ✅ |
| CRUD Subproductos (texto) | ✅ |
| Asociación Producto→Subproducto | ✅ |
| Pantalla de impresión | ✅ |
| Vista previa en tiempo real | ✅ |
| Validación frontend + backend | ✅ |
| Generación TSPL2 | ✅ |
| Envío TCP/IP socket | ✅ |
| Envío USB (Linux /dev/usb/lp0) | ✅ |
| Envío impresora compartida | ✅ |
| Historial de impresiones | ✅ |
| Filtros y paginación en historial | ✅ |
| Reimpresión desde historial | ✅ |
| Configuración de impresora | ✅ |
| Prueba de impresión (TSPL2) | ✅ |
| Procesamiento logo → bitmap TSPL2 | ✅ |
| CSRF en todos los formularios | ✅ |
| Prepared statements en todas las queries | ✅ |
| XSS protection (htmlspecialchars) | ✅ |
| Logs de errores PHP | ✅ |

---

## Base de Datos

### empresa
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | — |
| nombre | VARCHAR(255) | Nombre empresa |
| logo | VARCHAR(500) NULL | Ruta relativa al logo |

### usuarios
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | — |
| nombre | VARCHAR(255) | — |
| email | VARCHAR(255) UNIQUE | — |
| password | VARCHAR(255) | bcrypt hash |
| rol | ENUM(admin,operador) | — |
| activo | TINYINT(1) | — |

### productos
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | — |
| codigo | VARCHAR(100) UNIQUE | Código del producto |
| nombre | VARCHAR(255) | Nombre del producto |
| activo | TINYINT(1) | — |

### subproductos
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | — |
| descripcion | VARCHAR(500) | Descripción textual (ej: 501B - NATURAL CCX1103000) |
| activo | TINYINT(1) | — |

### producto_subproducto
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | — |
| producto_id | FK productos | — |
| subproducto_id | FK subproductos | — |
| UNIQUE(producto_id, subproducto_id) | — | No duplicados |

### configuracion_impresora
| Campo | Tipo | Descripción |
|---|---|---|
| dpi | INT | 203 ó 300 |
| ancho_mm | DECIMAL(8,2) | 80.00 por defecto |
| alto_mm | DECIMAL(8,2) | 40.00 por defecto |
| velocidad | INT | 1-14 |
| densidad | INT | 0-15 |
| tipo_conexion | ENUM(usb,tcp,shared) | — |
| ip | VARCHAR(45) NULL | Para TCP/IP |
| puerto | INT NULL | 9100 por defecto |

### impresiones
| Campo | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED PK | — |
| producto_id | FK NULL | Referencia al producto |
| producto_nombre | VARCHAR(255) | Snapshot al momento de imprimir |
| subproducto_id | FK NULL | Referencia al subproducto |
| subproducto_descripcion | VARCHAR(500) | Snapshot completo |
| cantidad | INT UNSIGNED | — |
| turno | TINYINT(1) | 1/2/3 |
| fecha_etiqueta | DATE | Fecha en la etiqueta |
| copias | INT UNSIGNED | Número de copias físicas |
| estado | ENUM(PENDIENTE,IMPRESO,ERROR,CANCELADO) | — |
| es_reimpresion | TINYINT(1) | — |
| impresion_original_id | INT NULL | Si es reimpresión |

---

## Impresión — TSC TE200

| Parámetro | Valor |
|---|---|
| Lenguaje | TSPL2 |
| DPI | 203 |
| Ancho | 80 mm = 643 dots |
| Alto | 40 mm = 322 dots |
| Fórmula | 1 mm = 8.0315 dots @ 203 DPI |
| Velocidad | 4 (configurable 1-14) |
| Densidad | 8 (configurable 0-15) |
| Gap | 2 mm, 0 mm |
| Dirección | 0 (normal) |
| Puerto TCP | 9100 |

### Comandos TSPL2 usados

```
SIZE 80 mm, 40 mm
GAP 2 mm, 0 mm
DIRECTION 0
SPEED 4
DENSITY 8
SET TEAR ON
CLS
TEXT x,y,"font",rotation,xmul,ymul,"texto"
BAR x,y,width,height
BOX x1,y1,x2,y2,thickness
BITMAP x,y,bytes_per_row,height,mode,hex_data
PRINT copies,sets
```

### Logo

Se convierte a bitmap monocromo 1bpp antes de enviarlo.
Comando: `BITMAP x,y,width_bytes,height,1,HEXDATA`
Posición: x=10, y=5, máximo 60×45 dots.

---

## Entorno local XAMPP

### Requisitos

| Componente | Versión | Notas |
|---|---|---|
| PHP | 8.1+ | Extensiones: pdo, pdo_mysql, mbstring, fileinfo, json, gd |
| MySQL/MariaDB | 8.0 / 10.6 | Puerto 3306, usuario root |
| Apache | 2.4+ | mod_rewrite habilitado, AllowOverride All |
| XAMPP | 8.x | Windows 10/11 |

### Ubicación del proyecto

```
C:\xampp\htdocs\labelprint\
```

### URL de acceso

```
http://localhost/labelprint/public/
```

### Verificación rápida

```
http://localhost/labelprint/public/check.php
```

### Configuración Apache requerida en httpd.conf

```apache
LoadModule rewrite_module modules/mod_rewrite.so

<Directory "C:/xampp/htdocs">
    AllowOverride All
    Require all granted
</Directory>
```

### Configuración BD para XAMPP

- Host: `127.0.0.1`
- Puerto: `3306`
- Usuario: `root`
- Password: `` (vacío por defecto)
- BD: `labelprint`

### Importar BD

1. Abrir http://localhost/phpmyadmin
2. Crear BD `labelprint` con charset `utf8mb4_unicode_ci`
3. Importar `database/migrations/001_initial_schema.sql`

### Assets locales (sin Internet)

| Archivo | Descripción |
|---|---|
| public/assets/css/bootstrap.min.css | Bootstrap 5.3.3 |
| public/assets/css/bootstrap-icons.min.css | Bootstrap Icons 1.11.3 |
| public/assets/css/fonts/ | Fuentes de íconos (woff, woff2) |
| public/assets/js/bootstrap.bundle.min.js | Bootstrap JS + Popper |
| public/assets/js/app.js | Funciones globales |
| public/assets/js/etiquetas.js | Lógica pantalla de impresión |

### Impresión USB en Windows/XAMPP

La TSC TE200 conectada por USB debe estar **instalada en Windows** como impresora.
El sistema usa `copy /b archivo.prn "Nombre Impresora"` para enviar RAW.
Si falla, el `.prn` se guarda en `storage/temp/` para impresión manual.
**TCP/IP es el método recomendado** para XAMPP.

### Archivos clave para XAMPP

```
check.php              → Verificación sin login
app/Controllers/CheckController.php → Verificación con login
.env.example           → Plantilla de variables de entorno
config/database.php    → Credenciales BD (host: 127.0.0.1)
config/app.php         → Carga .env automáticamente si existe
public/.htaccess       → Rewrite sin bucle para subcarpeta
app/Controllers/BaseController.php → basePath() para redirects
```
