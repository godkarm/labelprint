-- ============================================================
-- LABELPRINT — Migración 002
-- Agrega nombre_empresa a la tabla impresiones
--
-- Propósito: guardar snapshot del nombre de empresa
-- en cada registro de impresión, de forma que los datos
-- históricos no cambien aunque la empresa se renombre.
--
-- Ejecutar en phpMyAdmin o cliente MySQL:
--   USE labelprint;
--   SOURCE database/migrations/002_add_empresa_to_impresiones.sql
-- ============================================================

USE `labelprint`;

-- 1. Agregar columna nombre_empresa (snapshot histórico)
ALTER TABLE `impresiones`
    ADD COLUMN `nombre_empresa` VARCHAR(255) NOT NULL DEFAULT ''
    AFTER `id`;

-- 2. Rellenar registros existentes con el nombre actual de empresa
--    (única opción razonable para datos históricos sin snapshot)
UPDATE `impresiones`
SET `nombre_empresa` = (SELECT `nombre` FROM `empresa` LIMIT 1)
WHERE `nombre_empresa` = '';

-- Verificar resultado
SELECT
    id,
    nombre_empresa,
    producto_nombre,
    subproducto_descripcion,
    estado
FROM `impresiones`
ORDER BY id DESC
LIMIT 10;
