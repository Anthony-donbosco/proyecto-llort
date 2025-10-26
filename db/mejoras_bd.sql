-- Mejoras para el sistema deportivo
-- Agrega estadísticas a jugadores y crea tablas para destacados y galería

-- 1. Agregar campos de estadísticas a miembros_plantel
ALTER TABLE `miembros_plantel`
ADD COLUMN `goles` INT DEFAULT 0 COMMENT 'Goles anotados' AFTER `numero_camiseta`,
ADD COLUMN `asistencias` INT DEFAULT 0 COMMENT 'Asistencias realizadas' AFTER `goles`,
ADD COLUMN `porterias_cero` INT DEFAULT 0 COMMENT 'Porterías a cero (solo para porteros)' AFTER `asistencias`;

-- 2. Crear tabla de jugadores destacados por deporte/torneo
CREATE TABLE IF NOT EXISTS `jugadores_destacados` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `deporte_id` TINYINT(4) NOT NULL COMMENT 'Deporte al que pertenece',
  `torneo_id` INT(11) DEFAULT NULL COMMENT 'Torneo específico o NULL para general',
  `miembro_plantel_id` INT(11) NOT NULL COMMENT 'Referencia al jugador',
  `temporada_id` INT(11) DEFAULT NULL COMMENT 'Temporada a la que pertenece',
  `tipo_destacado` ENUM('torneo', 'seleccion', 'general') DEFAULT 'general' COMMENT 'Tipo de destacado',
  `descripcion` TEXT DEFAULT NULL COMMENT 'Descripción del logro',
  `fecha_destacado` DATE DEFAULT NULL,
  `orden` TINYINT(4) DEFAULT 0 COMMENT 'Orden de visualización',
  `esta_activo` TINYINT(1) DEFAULT 1,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_destacados_deporte` (`deporte_id`),
  KEY `fk_destacados_torneo` (`torneo_id`),
  KEY `fk_destacados_miembro` (`miembro_plantel_id`),
  KEY `fk_destacados_temporada` (`temporada_id`),
  CONSTRAINT `fk_destacados_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`),
  CONSTRAINT `fk_destacados_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_destacados_miembro` FOREIGN KEY (`miembro_plantel_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_destacados_temporada` FOREIGN KEY (`temporada_id`) REFERENCES `temporadas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Crear tabla de galería por temporadas
CREATE TABLE IF NOT EXISTS `galeria_temporadas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `temporada_id` INT(11) NOT NULL COMMENT 'Temporada a la que pertenece la foto',
  `deporte_id` TINYINT(4) DEFAULT NULL COMMENT 'Deporte relacionado (NULL si es general)',
  `titulo` VARCHAR(120) DEFAULT NULL COMMENT 'Título de la foto',
  `descripcion` TEXT DEFAULT NULL COMMENT 'Descripción de la foto',
  `url_foto` VARCHAR(255) NOT NULL COMMENT 'Ruta de la foto',
  `es_foto_grupo` TINYINT(1) DEFAULT 1 COMMENT '1 si es foto grupal de selección',
  `orden` SMALLINT(6) DEFAULT 0 COMMENT 'Orden de visualización',
  `fecha_captura` DATE DEFAULT NULL COMMENT 'Fecha en que se tomó la foto',
  `esta_activa` TINYINT(1) DEFAULT 1,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_galeria_temporada` (`temporada_id`),
  KEY `fk_galeria_deporte` (`deporte_id`),
  KEY `idx_galeria_activa` (`esta_activa`, `orden`),
  CONSTRAINT `fk_galeria_temporada` FOREIGN KEY (`temporada_id`) REFERENCES `temporadas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_galeria_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Crear tabla de configuración de galería (para controlar qué temporada mostrar)
CREATE TABLE IF NOT EXISTS `configuracion_galeria` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(64) NOT NULL COMMENT 'Clave de configuración',
  `valor` VARCHAR(255) DEFAULT NULL COMMENT 'Valor de la configuración',
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto
INSERT INTO `configuracion_galeria` (`clave`, `valor`, `descripcion`) VALUES
('temporada_galeria_activa', NULL, 'ID de la temporada que se muestra actualmente en la galería'),
('mostrar_todas_temporadas', '0', '1 para mostrar todas las temporadas, 0 para mostrar solo la activa');
