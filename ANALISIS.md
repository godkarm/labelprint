# ANALISIS.md — LabelPrint

Última actualización: 2026-08-30

---

## 1. Problema a Resolver

Operadores de planta necesitan imprimir etiquetas térmicas de 40×80 mm con datos de producto, subproducto, cantidad, turno y fecha en la impresora TSC TE200. El proceso debe ser rápido, confiable y registrar cada operación.

---

## 2. Requerimientos Funcionales

| ID | Requerimiento | Estado |
|---|---|---|
| RF01 | Configurar nombre e logo de empresa | IMPLEMENTADO |
| RF02 | CRUD de productos con código y nombre | IMPLEMENTADO |
| RF03 | CRUD de subproductos como texto descriptivo | IMPLEMENTADO |
| RF04 | Asociar subproductos a productos | IMPLEMENTADO |
| RF05 | Pantalla de creación de etiqueta | IMPLEMENTADO |
| RF06 | Campos: producto, subproducto, cantidad, turno, fecha, copias | IMPLEMENTADO |
| RF07 | Vista previa en tiempo real | IMPLEMENTADO |
| RF08 | Generación de comandos TSPL2 | IMPLEMENTADO |
| RF09 | Envío a TSC TE200 vía TCP/IP | IMPLEMENTADO |
| RF10 | Envío a TSC TE200 vía USB | IMPLEMENTADO |
| RF11 | Historial de impresiones | IMPLEMENTADO |
| RF12 | Reimpresión desde historial | IMPLEMENTADO |
| RF13 | Prueba de impresión | IMPLEMENTADO |
| RF14 | Configuración de impresora | IMPLEMENTADO |
| RF15 | Procesamiento de logo para impresión | IMPLEMENTADO |
| RF16 | Etiqueta de calibración | IMPLEMENTADO |

---

## 3. Requerimientos No Funcionales

| ID | Requerimiento | Estado |
|---|---|---|
| RNF01 | PHP 8.x + MySQL + Apache | IMPLEMENTADO |
| RNF02 | Prepared statements (SQL Injection) | IMPLEMENTADO |
| RNF03 | XSS protection | IMPLEMENTADO |
| RNF04 | CSRF tokens en formularios | IMPLEMENTADO |
| RNF05 | Validación de uploads (MIME, ext, tamaño) | IMPLEMENTADO |
| RNF06 | Contraseñas con bcrypt | IMPLEMENTADO |
| RNF07 | Logs de errores PHP | IMPLEMENTADO |
| RNF08 | Interfaz responsive | IMPLEMENTADO |
| RNF09 | Documentación técnica | IMPLEMENTADO |

---

## 4. Flujo de Impresión

```
[Operador]
    ↓ selecciona producto
[API /api/subproductos?producto_id=X]
    ↓ carga subproductos del producto
[Operador]
    ↓ completa: subproducto, cantidad, turno, fecha, copias
[Vista previa actualizada en JS]
    ↓ confirma
[POST /api/imprimir]
    ↓ LabelService::validate()
    ↓ LabelService::print()
    ↓   LogoProcessor::toTsplBitmap()
    ↓   PrinterService::generateTspl()
    ↓   PrinterService::send()
    ↓     TCP/IP: fsockopen → fwrite
    ↓     USB: /dev/usb/lp0 ó copy /b Windows
    ↓   Database::INSERT impresiones (estado=IMPRESO/ERROR)
[Resultado al operador]
```

---

## 5. Decisiones Técnicas

### 5.1 Impresión desde web

**Problema:** Un navegador no puede enviar RAW a USB directamente.

**Solución:** PHP en el servidor genera el TSPL2 y lo envía directamente:
- TCP/IP: mediante `fsockopen` al puerto 9100 de la impresora.
- USB: escribiendo en `/dev/usb/lp0` (Linux) o mediante `copy /b` (Windows).

**Documentado:** PrinterService.php, secciones sendTcp() y sendUsb().

### 5.2 Logo → TSPL2

**Problema:** TSPL2 no acepta PNG/JPG directamente via comando simple.

**Solución:** LogoProcessor convierte la imagen a bitmap monocromo 1bpp y genera el comando `BITMAP` con los datos en hexadecimal.

**Alternativa no implementada:** Comando `PUTPCX` de TSPL2 (requeriría convertir a formato PCX). Se eligió BITMAP por mayor compatibilidad.

### 5.3 Subproducto

El campo originalmente llamado "Color" es en realidad una **descripción textual del subproducto**. Se implementó como `VARCHAR(500)`, nunca como color RGB/HEX. No tiene selector cromático.

### 5.4 Snapshots en historial

Al registrar una impresión se guardan `producto_nombre` y `subproducto_descripcion` como snapshots. Esto garantiza que el historial refleje exactamente qué se imprimió, incluso si el catálogo cambia después.

---

## 6. Riesgos Técnicos

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Impresora USB no accesible desde PHP | Alto | Usar TCP/IP; documentar permisos Linux |
| Logo muy grande o complejo → bitmap lento | Medio | Redimensionar a 60×45 dots máximo |
| Subproducto muy largo → overflow en TSPL | Medio | Reducir fuente automáticamente en PrinterService |
| Concurrencia (múltiples operadores) | Bajo | Cada impresión es independiente |

---

## 7. Estado de Criterios de Aceptación

- [x] Sistema inicia correctamente
- [x] Base de datos funciona
- [x] Configurar nombre de empresa
- [x] Subir logo
- [x] Registrar producto
- [x] Registrar subproducto
- [x] Asociar subproducto a producto
- [x] Subproducto tratado como texto/descripción NO color visual
- [x] Ingresar cantidad, turno, fecha, copias
- [x] Vista previa
- [x] Etiqueta 4×8 cm
- [x] Generación TSPL2
- [x] Preparado para TSC TE200
- [x] Prueba de impresión
- [x] Historial
- [x] Reimpresión
- [x] Logs de errores
- [x] Validación de archivos
- [x] Seguridad básica implementada
- [x] CLAUDE.md, PROJECT.md, ANALISIS.md, CHANGELOG.md, README.md creados

---

## 8. Compatibilidad XAMPP (v1.1.0)

### Análisis realizado

| Componente | Estado | Resultado |
|---|---|---|
| PHP 8.x en XAMPP | VALIDADO | Compatible. Requiere pdo, pdo_mysql, mbstring, fileinfo, json |
| MySQL/MariaDB XAMPP | VALIDADO | Compatible. Host: 127.0.0.1, puerto 3306, user root |
| Apache mod_rewrite | VALIDADO | `.htaccess` corregido — pasar archivos reales antes del rewrite |
| Rutas en subcarpeta | IMPLEMENTADO | `basePath()` en `BaseController` detecta automáticamente |
| Sesiones en localhost | VALIDADO | `Session::start()` usa SameSite=Lax, no fuerza HTTPS |
| Uploads en Windows | IMPLEMENTADO | `DIRECTORY_SEPARATOR` en `logo_dir`, uploads funcional |
| Logs en Windows | IMPLEMENTADO | `ROOT_PATH . DS . 'storage/logs'` — compatible Windows/Linux |
| Assets sin Internet | IMPLEMENTADO | Bootstrap + BI descargados en `public/assets/` |
| ERR_TOO_MANY_REDIRECTS | IMPLEMENTADO | `requireAuth()` usa `basePath() . '/login'` |
| HTTPS forzado | VALIDADO | NO se fuerza — funciona en HTTP local |
| Impresión USB Windows | IMPLEMENTADO | `copy /b` con nombre de impresora; fallback a storage/temp |
| Impresión TCP/IP | VALIDADO | `fsockopen()` disponible en XAMPP PHP — puerto 9100 |
| Verificación entorno | IMPLEMENTADO | `check.php` y `/check` muestran estado de todos los componentes |

### Problemas encontrados y corregidos

1. **ERR_TOO_MANY_REDIRECTS** — `requireAuth()` usaba `/login` absoluto. Corregido con `basePath() . '/login'`.
2. **Assets sin Internet** — CDN jsdelivr bloqueada en red local. Corregido descargando localmente.
3. **USB Windows mal escapado** — Comando `copy /b` con doble escape incorrecto. Corregido.
4. **Ruta storage/temp hardcodeada** — Usaba `__DIR__` Unix-style. Corregido con `ROOT_PATH . DS`.

### Impresión en Windows/XAMPP

**TCP/IP (recomendado):** La TSC TE200 soporta TCP/IP. Configurar IP estática en la impresora y usar el modo TCP en Configuración → Impresora. `fsockopen()` funciona normalmente desde PHP en XAMPP.

**USB en Windows:** PHP no puede acceder directamente al dispositivo USB. Solución implementada:
- La impresora debe estar instalada en Windows como impresora local.
- El sistema usa `copy /b archivo.prn "Nombre Impresora"` para enviar RAW.
- Si falla, guarda el `.prn` en `storage/temp/` para impresión manual (arrastrar al icono de impresora en Windows).

### Configuración Apache XAMPP requerida

En `C:\xampp\apache\conf\httpd.conf` verificar:
```
LoadModule rewrite_module modules/mod_rewrite.so
```
Y en el bloque `<Directory "C:/xampp/htdocs">`:
```
AllowOverride All
```

---

## 9. Corrección: Nombre de Empresa en Impresiones (v1.2.0)

**Problema detectado:**
El Nombre de Empresa no se almacenaba en los registros de impresión.

**Causa real:**
El método `registrarImpresion()` en `LabelService` hacía un INSERT en `impresiones` sin incluir el campo `nombre_empresa`. La tabla tampoco tenía esa columna. El nombre de empresa se usaba solo para generar el TSPL2 pero nunca se persistía. En la reimpresión, se consultaba `empresa.nombre` en tiempo real (valor actual), perdiendo el nombre histórico.

**Solución implementada:**

1. **BD:** añadida columna `impresiones.nombre_empresa VARCHAR(255) NOT NULL DEFAULT ''`.
2. **Migración:** `002_add_empresa_to_impresiones.sql` rellena registros existentes con el nombre actual de empresa.
3. **`LabelService::registrarImpresion()`:** INSERT ahora incluye `nombre_empresa` desde `$labelData['empresa_nombre']`.
4. **`LabelService::registrarReimpresion()`:** INSERT propaga `nombre_empresa` del registro original.
5. **`LabelService::reprint()`:** usa `$original['nombre_empresa']` como snapshot, no consulta empresa actual. Fallback a empresa actual solo si el campo está vacío (registros antiguos).
6. **`LabelService::validate()`:** verifica que exista nombre de empresa configurado antes de permitir la impresión.
7. **Vistas:** historial, detalle y dashboard muestran columna "Empresa".

**Archivos modificados:**
- `app/Services/LabelService.php`
- `app/Views/historial/index.php`
- `app/Views/historial/show.php`
- `app/Views/dashboard/index.php`
- `database/migrations/001_initial_schema.sql`
- `database/migrations/002_add_empresa_to_impresiones.sql` (nuevo)

**Estado:** IMPLEMENTADO

---

## 10. Diagnóstico: La etiqueta no imprime (v1.5.0)

**Problema:** La TSC TE200 no recibía o no procesaba la etiqueta.

**Puntos de falla identificados:**

1. **Codificación de caracteres (CRÍTICO):** El TSPL2 contenía "MAÑANA" con bytes UTF-8.
   La TE200 usa CP850 por defecto. Los bytes multi-byte de Ñ/tildes corrompían el stream
   TSPL2, haciendo que la impresora los interpretara como comandos inválidos o descartara
   el trabajo silenciosamente. **Solución:** transliteración a ASCII en `tspl()`.

2. **Envío USB Windows — método insuficiente:** El `copy /b` al nombre de impresora
   falla si el nombre no coincide exactamente o si Windows no lo acepta en modo RAW.
   **Solución:** cascada de 3 métodos + campo de puerto USB (USB001, COM3...).

3. **Sin diagnóstico:** El sistema no dejaba trazas del TSPL generado ni del .prn,
   imposibilitando el diagnóstico. **Solución:** guardar .prn siempre, visor TSPL,
   descarga del .prn para impresión manual.

**Método de impresión:** TCP/IP (recomendado) o USB Windows via copy /b.
**Impresora:** TSC TE200 — TSPL2 — 203 DPI — 80×40 mm.
**Estado:** IMPLEMENTADO

---

## 11. Análisis: fallo USB en XAMPP/Windows (v1.5.2)

**CAPA CON FALLA:** Capa de comunicación PHP → Windows spooler.

**ARCHIVO:** `app/Services/PrinterService.php` → `sendUsbWindows()`

**CAUSA RAÍZ (más probable):**
Apache/XAMPP en Windows se instala y corre como servicio de sistema bajo
`NT AUTHORITY\SYSTEM`. Esa cuenta de sistema NO tiene acceso a las impresoras
instaladas para el usuario interactivo. Por tanto:
- `copy /b archivo.prn "TSC TE200"` → rc=1, falla silenciosamente.
- El sistema reporta éxito o falla dependiendo de la configuración.

**SOLUCIONES IMPLEMENTADAS:**
1. Cascada de 5 métodos en `sendUsbWindows()`.
2. PowerShell RawPrinterHelper (API Win32 del spooler) como método primario.
3. Página de diagnóstico paso a paso con detección del usuario de Apache.
4. Guía para cambiar la cuenta de servicio de Apache.

**SOLUCIÓN DEFINITIVA PARA EL USUARIO:**
- Usar TCP/IP (más limpio y confiable).
- O cambiar la cuenta de Apache en Servicios → Inicio de sesión → "Esta cuenta".

**Estado:** IMPLEMENTADO — pendiente prueba física del usuario.
