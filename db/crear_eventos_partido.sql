-- Crear tabla de eventos de partido (goles, tarjetas, asistencias, etc.)
CREATE TABLE IF NOT EXISTS `eventos_partido` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `partido_id` INT(11) NOT NULL COMMENT 'ID del partido',
  `miembro_plantel_id` INT(11) NOT NULL COMMENT 'Jugador que realizó el evento',
  `tipo_evento` ENUM('gol', 'asistencia', 'tarjeta_amarilla', 'tarjeta_roja', 'autogol', 'penal_anotado', 'penal_fallado', 'porteria_cero') DEFAULT 'gol',
  `minuto` TINYINT(4) DEFAULT NULL COMMENT 'Minuto del evento',
  `periodo` VARCHAR(20) DEFAULT NULL COMMENT 'Periodo del juego (1er tiempo, 2do tiempo, etc)',
  `notas` TEXT DEFAULT NULL,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_evento_partido` (`partido_id`),
  KEY `fk_evento_jugador` (`miembro_plantel_id`),
  CONSTRAINT `fk_evento_partido` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evento_jugador` FOREIGN KEY (`miembro_plantel_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
