-- ============================================================
-- LABEL PRINT SYSTEM — SCHEMA INICIAL
-- Versión: 1.0.0
-- Charset: UTF8MB4 / InnoDB
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `labelprint`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `labelprint`;

-- ============================================================
-- EMPRESA
-- ============================================================
CREATE TABLE IF NOT EXISTS `empresa` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(255) NOT NULL DEFAULT '',
  `logo`        VARCHAR(500) NULL DEFAULT NULL COMMENT 'Ruta relativa al logo',
  `creado_en`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro único de empresa
INSERT INTO `empresa` (`nombre`) VALUES ('Mi Empresa') ON DUPLICATE KEY UPDATE `id`=`id`;

-- ============================================================
-- USUARIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(255) NOT NULL,
  `email`       VARCHAR(255) NOT NULL,
  `password`    VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
  `rol`         ENUM('admin','operador') NOT NULL DEFAULT 'operador',
  `activo`      TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador por defecto (password: admin123)
-- Contraseña: admin123
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`) VALUES
('Administrador', 'admin@labelprint.local', '$2y$10$jA8eLk5JiYGIMMxaHfKr.e7.jF/SG27/O.F/GKMA/hBxVM7jB33TO', 'admin');

-- ============================================================
-- PRODUCTOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `productos` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`      VARCHAR(100) NOT NULL,
  `nombre`      VARCHAR(255) NOT NULL,
  `activo`      TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codigo` (`codigo`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SUBPRODUCTOS
-- (Campo "Color" original = descripción del subproducto, NO color visual)
-- ============================================================
CREATE TABLE IF NOT EXISTS `subproductos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `descripcion`   VARCHAR(500) NOT NULL COMMENT 'Ej: 501B - NATURAL CCX1103000',
  `activo`        TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- RELACIÓN PRODUCTO → SUBPRODUCTO
-- ============================================================
CREATE TABLE IF NOT EXISTS `producto_subproducto` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `producto_id`     INT UNSIGNED NOT NULL,
  `subproducto_id`  INT UNSIGNED NOT NULL,
  `creado_en`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prod_sub` (`producto_id`, `subproducto_id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_subproducto` (`subproducto_id`),
  CONSTRAINT `fk_ps_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_subproducto` FOREIGN KEY (`subproducto_id`) REFERENCES `subproductos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CONFIGURACIÓN DE IMPRESORA
-- ============================================================
CREATE TABLE IF NOT EXISTS `configuracion_impresora` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(255) NOT NULL DEFAULT 'TSC TE200',
  `modelo`          VARCHAR(100) NOT NULL DEFAULT 'TE200',
  `dpi`             INT NOT NULL DEFAULT 203,
  `ancho_mm`        DECIMAL(8,2) NOT NULL DEFAULT 80.00,
  `alto_mm`         DECIMAL(8,2) NOT NULL DEFAULT 40.00,
  `velocidad`       INT NOT NULL DEFAULT 4 COMMENT '1-14 pulgadas/segundo',
  `densidad`        INT NOT NULL DEFAULT 8 COMMENT '0-15',
  `orientacion`     ENUM('horizontal','vertical') NOT NULL DEFAULT 'horizontal',
  `tipo_conexion`   ENUM('usb','tcp','shared') NOT NULL DEFAULT 'usb',
  `ip`              VARCHAR(45) NULL DEFAULT NULL,
  `puerto`          INT NULL DEFAULT 9100,
  `nombre_compartido` VARCHAR(255) NULL DEFAULT NULL,
  `activo`          TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración por defecto
INSERT INTO `configuracion_impresora`
  (`nombre`, `modelo`, `dpi`, `ancho_mm`, `alto_mm`, `velocidad`, `densidad`, `orientacion`, `tipo_conexion`)
VALUES
  ('TSC TE200', 'TE200', 203, 80.00, 40.00, 4, 8, 'horizontal', 'usb');

-- ============================================================
-- IMPRESIONES (HISTORIAL)
-- ============================================================
CREATE TABLE IF NOT EXISTS `impresiones` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre_empresa`        VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Snapshot del nombre de empresa al momento de imprimir',
  `producto_id`           INT UNSIGNED NULL DEFAULT NULL,
  `producto_nombre`       VARCHAR(255) NOT NULL COMMENT 'Snapshot al momento de imprimir',
  `subproducto_id`        INT UNSIGNED NULL DEFAULT NULL,
  `subproducto_descripcion` VARCHAR(500) NOT NULL COMMENT 'Snapshot completo del subproducto',
  `cantidad`              INT UNSIGNED NOT NULL,
  `turno`                 TINYINT(1) NOT NULL COMMENT '1=Mañana, 2=Tarde, 3=Noche',
  `fecha_etiqueta`        DATE NOT NULL,
  `copias`                INT UNSIGNED NOT NULL DEFAULT 1,
  `usuario_id`            INT UNSIGNED NULL DEFAULT NULL,
  `impresora_id`          INT UNSIGNED NULL DEFAULT NULL,
  `estado`                ENUM('PENDIENTE','IMPRESO','ERROR','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
  `error_detalle`         TEXT NULL DEFAULT NULL,
  `es_reimpresion`        TINYINT(1) NOT NULL DEFAULT 0,
  `impresion_original_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Si es reimpresión, ID original',
  `creado_en`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha` (`fecha_etiqueta`),
  KEY `idx_creado` (`creado_en`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `fk_imp_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_imp_subproducto` FOREIGN KEY (`subproducto_id`) REFERENCES `subproductos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_imp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_imp_impresora` FOREIGN KEY (`impresora_id`) REFERENCES `configuracion_impresora` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================
INSERT INTO `productos` (`codigo`, `nombre`) VALUES
('CAJA-PALTA-4KG', 'CAJA PALTA 4 KG'),
('BOLSA-UVA-2KG', 'BOLSA UVA 2 KG');

INSERT INTO `subproductos` (`descripcion`) VALUES
('501B - NATURAL CCX1103000'),
('502A - VERDE CCX1104000'),
('503C - PREMIUM CCX1205000');

INSERT INTO `producto_subproducto` (`producto_id`, `subproducto_id`) VALUES
(1, 1), (1, 2), (2, 3);
