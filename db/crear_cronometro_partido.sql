-- Crear tabla para control de cronómetro de partidos en vivo
CREATE TABLE IF NOT EXISTS `cronometro_partido` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `partido_id` INT(11) NOT NULL COMMENT 'ID del partido',
  `estado_cronometro` ENUM('detenido', 'corriendo', 'pausado', 'finalizado') DEFAULT 'detenido',
  `tiempo_transcurrido` INT(11) DEFAULT 0 COMMENT 'Segundos transcurridos',
  `tiempo_inicio` TIMESTAMP NULL DEFAULT NULL COMMENT 'Cuando se inició el cronómetro',
  `tiempo_pausa` TIMESTAMP NULL DEFAULT NULL COMMENT 'Cuando se pausó',
  `periodo_actual` VARCHAR(20) DEFAULT '1er Tiempo' COMMENT 'Periodo del juego',
  `tiempo_agregado` INT(11) DEFAULT 0 COMMENT 'Minutos de tiempo agregado',
  `actualizado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_partido_cronometro` (`partido_id`),
  CONSTRAINT `fk_cronometro_partido` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
