-- ============================================================
-- LABELPRINT — Migración 003
-- Corregir dimensiones de etiqueta: 100×200mm (vertical)
-- Etiquetas 4×8cm reales del material ETIQUETAS 4X8
--
-- Ejecutar en phpMyAdmin si ya tiene la BD importada.
-- ============================================================

USE `labelprint`;

UPDATE `configuracion_impresora`
SET
    `ancho_mm`   = 100.00,
    `alto_mm`    = 200.00,
    `orientacion`= 'vertical'
WHERE `activo` = 1;

-- Verificar
SELECT id, nombre, ancho_mm, alto_mm, orientacion, tipo_conexion
FROM configuracion_impresora;
