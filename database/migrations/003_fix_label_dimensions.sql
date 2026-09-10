-- ============================================================
-- LABELPRINT — Migración 003
-- Corregir dimensiones: 200mm × 100mm, orientación horizontal
-- Material real: "ETIQUETAS 4X8"
--   Ancho (largo de avance): 200 mm
--   Alto (ancho de papel):   100 mm
--   Orientación: horizontal
-- Ejecutar en phpMyAdmin si ya tiene la BD importada.
-- ============================================================

USE `labelprint`;

UPDATE `configuracion_impresora`
SET
    `ancho_mm`    = 200.00,
    `alto_mm`     = 100.00,
    `orientacion` = 'horizontal'
WHERE `activo` = 1;

-- Verificar:
SELECT id, nombre, ancho_mm, alto_mm, orientacion, tipo_conexion
FROM configuracion_impresora;
