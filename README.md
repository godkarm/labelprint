# LabelPrint v1.1.0 — Sistema de Impresión de Etiquetas TSC TE200

Sistema web para gestionar e imprimir etiquetas de **80×40 mm** para la impresora térmica **TSC TE200**.
Compatible con **XAMPP en Windows** y entornos LAMP en Linux.

---

## Instalación en XAMPP (Windows) — Paso a paso

### 1. Instalar XAMPP

Descargar desde https://www.apachefriends.org/ e instalar con al menos:
- ✅ Apache
- ✅ MySQL/MariaDB
- ✅ PHP 8.x

### 2. Copiar el proyecto

Extraer el ZIP dentro de:
```
C:\xampp\htdocs\labelprint\
```
La estructura debe quedar:
```
C:\xampp\htdocs\labelprint\
├── app\
├── config\
├── database\
├── public\        ← Punto de entrada
├── routes\
├── storage\
├── CLAUDE.md
└── README.md
```

### 3. Iniciar XAMPP

Abrir el Panel de Control de XAMPP y hacer clic en **Start** para:
- ✅ Apache
- ✅ MySQL

### 4. Crear la base de datos en phpMyAdmin

1. Abrir: http://localhost/phpmyadmin
2. Clic en **Nueva** (panel izquierdo)
3. Nombre: `labelprint`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Clic en **Crear**
6. Con la BD `labelprint` seleccionada, ir a la pestaña **Importar**
7. Seleccionar el archivo: `database\migrations\001_initial_schema.sql`
8. Clic en **Continuar**

### 5. Configurar la conexión a la base de datos

Editar `config\database.php`:
```php
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'labelprint',
    'user'     => 'root',
    'password' => '',           // XAMPP por defecto: sin contraseña
];
```
Si su MySQL tiene contraseña, ingrésela en `'password'`.

### 6. Habilitar mod_rewrite en Apache (si las rutas no funcionan)

Editar `C:\xampp\apache\conf\httpd.conf`:

Buscar y descomentar (quitar el `#`):
```
LoadModule rewrite_module modules/mod_rewrite.so
```

Buscar el bloque `<Directory "C:/xampp/htdocs">` y cambiar:
```
AllowOverride None
```
por:
```
AllowOverride All
```

Reiniciar Apache en el Panel de XAMPP.

### 7. Verificar el sistema

Abrir en el navegador:
```
http://localhost/labelprint/public/check.php
```
Debe mostrar todos los componentes en verde ✓.

### 8. Acceder al sistema

```
http://localhost/labelprint/public/
```

**Credenciales iniciales:**
```
Email:    admin@labelprint.local
Contraseña: admin123
```
⚠️ Cambiar la contraseña tras el primer acceso.

---

## Configuración post-instalación

### 9. Configurar la empresa

Menú → **Empresa** → Ingresar nombre y subir logo.

### 10. Registrar productos y subproductos

- Menú → **Productos** → Nuevo Producto
- Menú → **Subproductos** → Nuevo Subproducto
  - Ejemplo: `501B - NATURAL CCX1103000`
- Al editar un producto, asociar sus subproductos.

### 11. Configurar la impresora TSC TE200

Menú → **Configuración** → Impresora

**Opción A — TCP/IP (recomendado):**
1. Configurar IP estática en la TSC TE200 (consulte el manual).
2. Seleccionar Tipo: `TCP/IP`.
3. Ingresar la IP y puerto `9100`.
4. Guardar y probar con **Prueba de impresión**.

**Opción B — USB en Windows:**
1. Instalar los drivers de la TSC TE200 en Windows.
2. Verificar que aparezca en `Panel de Control → Impresoras`.
3. Seleccionar Tipo: `USB`.
4. En el campo **Nombre**, ingresar exactamente el nombre de la impresora en Windows.
5. Guardar y probar.

### 12. Imprimir la primera etiqueta

Menú → **Imprimir Etiqueta**

---

## Verificación del sistema

En cualquier momento:
```
http://localhost/labelprint/public/check.php
```
o dentro del sistema: Menú → **Verificar sistema**

---

## Requisitos del sistema

| Componente | Versión mínima |
|---|---|
| PHP | 8.1+ |
| MySQL / MariaDB | 8.0 / 10.6 |
| Apache | 2.4+ con mod_rewrite |
| Extensión GD | Recomendada (para logo en impresión) |

**Extensiones PHP requeridas:** `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `json`, `session`

---

## Estructura del proyecto

```
labelprint/
├── CLAUDE.md       → Reglas permanentes del proyecto
├── PROJECT.md      → Documentación técnica
├── ANALISIS.md     → Análisis y requerimientos
├── CHANGELOG.md    → Historial de cambios
├── README.md       → Este archivo
├── .env.example    → Plantilla de variables de entorno
│
├── app/
│   ├── Controllers/   → Auth, Dashboard, Empresa, Producto, etc.
│   ├── Core/          → Router, Database, View, Session, ViewConfig
│   ├── Services/      → PrinterService (TSPL2), LabelService, LogoProcessor
│   └── Views/         → Plantillas HTML con Bootstrap 5
│
├── config/
│   ├── app.php        → Configuración general (editar timezone, url)
│   └── database.php   → Credenciales BD (editar para XAMPP)
│
├── database/
│   └── migrations/
│       └── 001_initial_schema.sql  → Importar en phpMyAdmin
│
├── public/            → Documento raíz para Apache
│   ├── index.php      → Front controller
│   ├── check.php      → Verificación del sistema (sin login)
│   ├── .htaccess      → Rewrite rules
│   ├── assets/
│   │   ├── css/       → Bootstrap, Bootstrap Icons, app.css (LOCAL)
│   │   └── js/        → Bootstrap bundle, app.js, etiquetas.js (LOCAL)
│   └── uploads/logos/ → Logos de empresa
│
├── routes/
│   └── web.php        → Todas las rutas
│
└── storage/
    ├── logs/          → Errores PHP (php_errors.log)
    └── temp/          → Archivos .prn temporales de impresión
```

---

## Cambiar contraseña de administrador

En phpMyAdmin → BD `labelprint` → tabla `usuarios` → editar registro, o ejecutar:
```sql
UPDATE usuarios
SET password = '$2y$12$NUEVO_HASH_BCRYPT'
WHERE email = 'admin@labelprint.local';
```
Generar hash en PHP:
```php
echo password_hash('mi_nueva_clave', PASSWORD_BCRYPT);
```

---

## Funciona sin Internet

Todos los assets (Bootstrap CSS/JS, Bootstrap Icons con fuentes) están incluidos localmente en `public/assets/`. El sistema no requiere conexión a Internet para funcionar.
