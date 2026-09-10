-- ============================================================
-- LABELPRINT — Schema de instalación
-- Compatible con XAMPP (MySQL 5.7+ / MariaDB 10.x)
-- Versión: 1.8.0
-- ============================================================
-- INSTRUCCIONES phpMyAdmin:
--   1. Abrir phpMyAdmin → http://localhost/phpmyadmin
--   2. Crear BD "labelprint" con charset utf8mb4_unicode_ci
--   3. Seleccionar la BD "labelprint"
--   4. Importar ESTE archivo
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = '';

-- ============================================================
-- EMPRESA
-- ============================================================
DROP TABLE IF EXISTS `empresa`;
CREATE TABLE `empresa` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(255)     NOT NULL DEFAULT '',
  `logo`           VARCHAR(500)     NULL DEFAULT NULL,
  `creado_en`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `empresa` (`nombre`) VALUES ('Mi Empresa');

-- ============================================================
-- USUARIOS
-- ============================================================
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(255)     NOT NULL,
  `email`          VARCHAR(255)     NOT NULL,
  `password`       VARCHAR(255)     NOT NULL,
  `rol`            VARCHAR(20)      NOT NULL DEFAULT 'operador',
  `activo`         TINYINT(1)       NOT NULL DEFAULT 1,
  `creado_en`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contraseña: admin123
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`, `activo`) VALUES
('Administrador', 'admin@labelprint.local',
 '$2y$10$jA8eLk5JiYGIMMxaHfKr.e7.jF/SG27/O.F/GKMA/hBxVM7jB33TO',
 'admin', 1);

-- ============================================================
-- PRODUCTOS
-- ============================================================
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `codigo`         VARCHAR(100)     NOT NULL,
  `nombre`         VARCHAR(255)     NOT NULL,
  `activo`         TINYINT(1)       NOT NULL DEFAULT 1,
  `creado_en`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo` (`codigo`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SUBPRODUCTOS (campo COLOR = descripción de subproducto, NO color visual)
-- ============================================================
DROP TABLE IF EXISTS `subproductos`;
CREATE TABLE `subproductos` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `descripcion`    VARCHAR(500)     NOT NULL,
  `activo`         TINYINT(1)       NOT NULL DEFAULT 1,
  `creado_en`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- RELACIÓN PRODUCTO → SUBPRODUCTO
-- ============================================================
DROP TABLE IF EXISTS `producto_subproducto`;
CREATE TABLE `producto_subproducto` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `producto_id`    INT UNSIGNED     NOT NULL,
  `subproducto_id` INT UNSIGNED     NOT NULL,
  `creado_en`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prod_sub` (`producto_id`, `subproducto_id`),
  KEY `idx_producto`    (`producto_id`),
  KEY `idx_subproducto` (`subproducto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CONFIGURACIÓN DE IMPRESORA
-- ============================================================
DROP TABLE IF EXISTS `configuracion_impresora`;
CREATE TABLE `configuracion_impresora` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre`           VARCHAR(255)     NOT NULL DEFAULT 'TSC TE200',
  `modelo`           VARCHAR(100)     NOT NULL DEFAULT 'TE200',
  `dpi`              INT              NOT NULL DEFAULT 203,
  `ancho_mm`         DECIMAL(8,2)     NOT NULL DEFAULT 200.00,
  `alto_mm`          DECIMAL(8,2)     NOT NULL DEFAULT 100.00,
  `velocidad`        INT              NOT NULL DEFAULT 4,
  `densidad`         INT              NOT NULL DEFAULT 8,
  `orientacion`      VARCHAR(20)      NOT NULL DEFAULT 'horizontal',
  `tipo_conexion`    VARCHAR(20)      NOT NULL DEFAULT 'usb',
  `ip`               VARCHAR(45)      NULL DEFAULT NULL,
  `puerto`           INT              NULL DEFAULT 9100,
  `nombre_compartido`VARCHAR(255)     NULL DEFAULT NULL,
  `activo`           TINYINT(1)       NOT NULL DEFAULT 1,
  `creado_en`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración por defecto: 200×100mm horizontal (material ETIQUETAS 4X8)
INSERT INTO `configuracion_impresora`
  (`nombre`, `modelo`, `dpi`, `ancho_mm`, `alto_mm`, `velocidad`, `densidad`, `orientacion`, `tipo_conexion`)
VALUES
  ('TSC TE200', 'TE200', 203, 200.00, 100.00, 4, 8, 'horizontal', 'usb');

-- ============================================================
-- IMPRESIONES (historial)
-- ============================================================
DROP TABLE IF EXISTS `impresiones`;
CREATE TABLE `impresiones` (
  `id`                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nombre_empresa`          VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Snapshot al imprimir',
  `producto_id`             INT UNSIGNED  NULL DEFAULT NULL,
  `producto_nombre`         VARCHAR(255)  NOT NULL DEFAULT '',
  `subproducto_id`          INT UNSIGNED  NULL DEFAULT NULL,
  `subproducto_descripcion` VARCHAR(500)  NOT NULL DEFAULT '',
  `cantidad`                INT UNSIGNED  NOT NULL DEFAULT 0,
  `turno`                   TINYINT(1)   NOT NULL DEFAULT 1,
  `fecha_etiqueta`          DATE          NOT NULL,
  `copias`                  INT UNSIGNED  NOT NULL DEFAULT 1,
  `usuario_id`              INT UNSIGNED  NULL DEFAULT NULL,
  `impresora_id`            INT UNSIGNED  NULL DEFAULT NULL,
  `estado`                  VARCHAR(20)   NOT NULL DEFAULT 'PENDIENTE',
  `error_detalle`           TEXT          NULL DEFAULT NULL,
  `es_reimpresion`          TINYINT(1)   NOT NULL DEFAULT 0,
  `impresion_original_id`   INT UNSIGNED  NULL DEFAULT NULL,
  `creado_en`               DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_estado`   (`estado`),
  KEY `idx_fecha`    (`fecha_etiqueta`),
  KEY `idx_creado`   (`creado_en`),
  KEY `idx_producto` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE EJEMPLO (opcionales — borrar si no se necesitan)
-- ============================================================
INSERT INTO `productos` (`codigo`, `nombre`) VALUES
('BOLSA-UVA-2KG', 'BOLSA UVA 2 KG'),
('CAJA-PALTA-4KG', 'CAJA PALTA 4 KG');

INSERT INTO `subproductos` (`descripcion`) VALUES
('501-uhb-CC100510'),
('501B - NATURAL CCX1103000'),
('502A - VERDE CCX1104000');

INSERT INTO `producto_subproducto` (`producto_id`, `subproducto_id`) VALUES
(1, 1), (1, 2), (2, 3);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VERIFICACIÓN FINAL
-- ============================================================
SELECT 'INSTALACIÓN COMPLETADA' AS resultado;
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;
