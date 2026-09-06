# CLAUDE.md — REGLAS PERMANENTES DEL PROYECTO

## LEER ESTE ARCHIVO ANTES DE CUALQUIER MODIFICACIÓN

---

## 1. OBJETIVO DEL SISTEMA

Sistema web para gestionar, preparar e imprimir etiquetas térmicas de 40×80 mm para la impresora TSC TE200.

## 2. ARQUITECTURA

- **Backend:** PHP 8.x, patrón MVC
- **Base de datos:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5, AJAX/fetch
- **Entorno:** Apache + PHP + MySQL (local/LAN)

## 3. TECNOLOGÍAS

| Tecnología | Versión | Uso |
|---|---|---|
| PHP | 8.x | Backend, lógica, servicios |
| MySQL/MariaDB | 8.x/10.x | Persistencia |
| Bootstrap | 5.x | UI |
| JavaScript | ES6+ | Frontend interactivo |
| TSPL/TSPL2 | — | Lenguaje de impresión TSC |

## 4. REGLAS DE DESARROLLO

1. Leer CLAUDE.md antes de modificar cualquier archivo.
2. Leer PROJECT.md para el mapa de archivos.
3. Revisar ANALISIS.md y CHANGELOG.md.
4. No reemplazar archivos sin revisar su contenido primero.
5. No inventar tablas, rutas, variables ni comandos TSPL.
6. Mantener separación: lógica/interfaz/impresión/config/storage.
7. Consultas SQL únicamente con prepared statements.
8. Validar siempre frontend Y backend.
9. Registrar todo cambio en CHANGELOG.md.

## 5. REGLAS DE IMPRESIÓN — TSC TE200

- **DPI:** 203 dpi
- **Lenguaje:** TSPL2
- **Tamaño físico:** 80 mm × 40 mm (horizontal)
- **LABEL_WIDTH_MM = 80, LABEL_HEIGHT_MM = 40**
- **Velocidad:** 4 (configurable)
- **Densidad:** 8 (configurable)
- **Conversión a píxeles @ 203 dpi:** 1 mm = 8.0315 dots
  - 80 mm = 643 dots
  - 40 mm = 322 dots
- **El navegador NO puede enviar RAW a USB directamente.**
- **Arquitectura de impresión:** Navegador → PHP server → socket/USB → TSC TE200
- **Logo:** Convertir a BMP monocromo antes de enviar.
- No inventar comandos TSPL. Documentar cada comando usado.

## 6. REGLAS DEL CAMPO SUBPRODUCTO

⚠️ **"Color" = SUBPRODUCTO (texto descriptivo), NO color visual.**

- Tipo: VARCHAR(500) en la BD
- NO usar RGB, HEX, ni selector de color
- Almacenar exactamente como fue registrado
- Ejemplo: `501B - NATURAL CCX1103000`
- Imprimir completo, sin truncar

## 7. REGLAS DE BASE DE DATOS

- Usar prepared statements siempre
- Nunca concatenar SQL con input de usuario
- Tablas: empresa, productos, subproductos, producto_subproducto, configuracion_impresora, impresiones, usuarios
- Documentar cambios de esquema en CHANGELOG.md y PROJECT.md
- Usar InnoDB, UTF8MB4

## 8. REGLAS DE SEGURIDAD

- SQL: prepared statements obligatorio
- XSS: htmlspecialchars() en outputs
- CSRF: token en formularios
- Uploads: validar extensión + MIME + tamaño + nombre seguro
- Contraseñas: password_hash() + PASSWORD_BCRYPT
- No mostrar errores PHP en producción
- Logs en storage/logs/

## 9. REGLAS DE DOCUMENTACIÓN

- CLAUDE.md: reglas permanentes (este archivo)
- PROJECT.md: documentación técnica + mapa de archivos
- ANALISIS.md: análisis técnico/funcional, estados de requerimientos
- CHANGELOG.md: registro de cambios con versionado semántico
- README.md: instrucciones de instalación

## 10. PROCEDIMIENTO ANTES DE MODIFICAR CÓDIGO

1. Leer CLAUDE.md
2. Identificar archivos en PROJECT.md (mapa)
3. Revisar el archivo actual antes de editar
4. Analizar dependencias
5. Implementar cambio mínimo necesario
6. Probar
7. Actualizar documentación
8. Registrar en CHANGELOG.md

## 11. ESTRUCTURA DEL PROYECTO

```
/
├── CLAUDE.md, PROJECT.md, ANALISIS.md, CHANGELOG.md, README.md
├── app/
│   ├── Controllers/    → Controladores MVC
│   ├── Models/         → Modelos de datos
│   ├── Services/       → PrinterService, LabelService, LogoProcessor
│   ├── Views/          → Plantillas HTML
│   └── Core/           → Router, DB, Request, Response, Session
├── config/             → Configuración de la aplicación
├── database/           → Migraciones y seeders SQL
├── public/             → Punto de entrada (index.php), assets
├── routes/             → Definición de rutas
├── storage/            → Logs, temporales
└── tests/              → Pruebas
```

## 12. COMANDOS TSPL2 DOCUMENTADOS (TSC TE200)

```
SIZE 80 mm, 40 mm          → Definir tamaño de etiqueta
GAP 2 mm, 0 mm             → Espacio entre etiquetas
DIRECTION 0                → Orientación
REFERENCE 0,0              → Punto de referencia
SPEED 4                    → Velocidad de impresión
DENSITY 8                  → Densidad/oscuridad
CLS                        → Limpiar buffer
TEXT x,y,"font",r,sx,sy,"text" → Imprimir texto
BITMAP x,y,w,h,bpp,data   → Imprimir imagen bitmap
PRINT copies,sets          → Imprimir
```

## 13. ESTADOS DE REQUERIMIENTOS (ANALISIS.md)

```
PENDIENTE | EN_ANALISIS | EN_DESARROLLO | IMPLEMENTADO | VALIDADO | BLOQUEADO
```

## 14. ESTADOS DE IMPRESIÓN

```
PENDIENTE | IMPRESO | ERROR | CANCELADO
```

---

## 15. COMPATIBILIDAD XAMPP (añadido v1.1.0)

### Entorno soportado

```
Windows 10/11 + XAMPP (Apache + PHP 8.x + MySQL/MariaDB)
Linux + XAMPP o LAMP
```

### Instalación local

El proyecto debe funcionar desde:
```
C:\xampp\htdocs\labelprint\
```
URL de acceso:
```
http://localhost/labelprint/public/
```

### Reglas específicas para XAMPP

1. **NO** usar rutas absolutas Linux (`/var/log/`, `/dev/usb/lp0`) sin verificar SO.
2. **NO** forzar HTTPS en entorno local (causa `ERR_TOO_MANY_REDIRECTS`).
3. Usar `DIRECTORY_SEPARATOR` o `ROOT_PATH` para rutas de archivos.
4. `requireAuth()` usa `basePath()` para redirigir correctamente en subcarpeta.
5. Assets (Bootstrap, BI) disponibles localmente en `public/assets/` — no requiere Internet.
6. `.env` opcional para configurar variables sin editar PHP.

### Base de datos XAMPP

```
Host:     127.0.0.1
Puerto:   3306
Usuario:  root
Password: (vacío por defecto en XAMPP)
BD:       labelprint
```
NO guardar contraseñas reales en CLAUDE.md.

### Impresión en Windows/XAMPP

- **TCP/IP** (recomendado): configurar IP de la impresora en Configuración.
- **USB en Windows**: el sistema usa `copy /b` al nombre de la impresora instalada en Windows. La impresora debe estar instalada previamente en el sistema operativo.
- **Fallback**: si no puede enviar, guarda el `.prn` en `storage/temp/` para impresión manual.

### Verificación del entorno

Acceder a:
```
http://localhost/labelprint/public/check.php
```
o dentro del sistema:
```
Menú → Verificar sistema
```

---

## 16. SNAPSHOT DE NOMBRE DE EMPRESA EN IMPRESIONES (añadido v1.2.0)

> **Regla:** El Nombre de Empresa utilizado en una impresión DEBE almacenarse como snapshot histórico dentro del registro de impresión (`impresiones.nombre_empresa`), para garantizar que los registros y reimpresiones mantengan el nombre utilizado originalmente aunque posteriormente se modifique la configuración de empresa.

- El campo `impresiones.nombre_empresa VARCHAR(255)` guarda el nombre exacto al momento de imprimir.
- La reimpresión usa `nombre_empresa` del registro original, NO el nombre actual de empresa.
- Si `nombre_empresa` está vacío en un registro antiguo, se usa el nombre actual como fallback.
- El logo siempre se toma del estado actual (es un archivo físico, no un snapshot).
- La validación de impresión verifica que exista nombre de empresa configurado antes de proceder.

---

## 17. Historial de Impresiones — Reglas (añadido v1.3.0)

- Cada impresión es un registro histórico independiente.
- `nombre_empresa` se almacena como snapshot al momento de imprimir.
- La Vista previa de una impresión histórica usa `nombre_empresa` del registro, no el nombre actual de empresa.
- La Reimpresión usa el `nombre_empresa` almacenado originalmente.
- Cambiar la empresa actual NO modifica impresiones históricas.
- El botón Eliminar elimina únicamente el registro de impresión (`DELETE FROM impresiones WHERE id=?`).
- No se eliminan productos, colores, empresa ni usuarios al eliminar una impresión.
- La eliminación requiere confirmación y token CSRF.
