-- ============================================================
-- LABELPRINT — Corrección de contraseña de administrador
-- Ejecutar en phpMyAdmin si ya tiene la BD importada
-- Contraseña resultante: admin123
-- ============================================================

USE `labelprint`;

UPDATE `usuarios`
SET `password` = '$2y$10$jA8eLk5JiYGIMMxaHfKr.e7.jF/SG27/O.F/GKMA/hBxVM7jB33TO'
WHERE `email` = 'admin@labelprint.local';

-- Verificar:
SELECT id, nombre, email, rol, activo FROM `usuarios`;
