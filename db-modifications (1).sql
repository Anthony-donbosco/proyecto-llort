-- Modificaciones a la base de datos sistema_deportivo.sql
-- Nuevas tablas y alteraciones para manejo de posiciones y jugadores en equipos

-- 1. Crear tabla de posiciones para los diferentes deportes
CREATE TABLE `posiciones_deporte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deporte_id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `coordenada_x` decimal(5,2) DEFAULT NULL COMMENT 'Posición X en campo (porcentaje 0-100)',
  `coordenada_y` decimal(5,2) DEFAULT NULL COMMENT 'Posición Y en campo (porcentaje 0-100)',
  `orden_visualizacion` tinyint(4) DEFAULT 0,
  `es_titular` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_posicion_deporte_codigo` (`deporte_id`, `codigo`),
  KEY `fk_posiciones_deporte` (`deporte_id`),
  CONSTRAINT `fk_posiciones_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insertar posiciones para fútbol
INSERT INTO `posiciones_deporte` (`deporte_id`, `codigo`, `nombre_mostrado`, `descripcion`, `coordenada_x`, `coordenada_y`, `orden_visualizacion`, `es_titular`) VALUES
-- Portero
(1, 'GK', 'Portero', 'Guardameta', 10.00, 50.00, 1, 1),
-- Defensas
(1, 'CB', 'Defensa Central', 'Defensa central', 25.00, 50.00, 2, 1),
(1, 'LB', 'Lateral Izquierdo', 'Defensa lateral izquierdo', 25.00, 20.00, 3, 1),
(1, 'RB', 'Lateral Derecho', 'Defensa lateral derecho', 25.00, 80.00, 4, 1),
-- Mediocampistas
(1, 'CDM', 'Mediocampista Defensivo', 'Mediocampista defensivo', 45.00, 50.00, 5, 1),
(1, 'CM', 'Mediocampista Central', 'Mediocampista central', 60.00, 50.00, 6, 1),
(1, 'LM', 'Mediocampista Izquierdo', 'Mediocampista por izquierda', 60.00, 25.00, 7, 1),
(1, 'RM', 'Mediocampista Derecho', 'Mediocampista por derecha', 60.00, 75.00, 8, 1),
(1, 'CAM', 'Mediocampista Ofensivo', 'Mediocampista ofensivo', 75.00, 50.00, 9, 1),
-- Delanteros
(1, 'LW', 'Extremo Izquierdo', 'Delantero por izquierda', 85.00, 25.00, 10, 1),
(1, 'RW', 'Extremo Derecho', 'Delantero por derecha', 85.00, 75.00, 11, 1),
(1, 'ST', 'Delantero Centro', 'Delantero centro', 90.00, 50.00, 12, 1),
-- Suplentes
(1, 'SUB', 'Suplente', 'Jugador suplente', NULL, NULL, 13, 0);

-- 3. Insertar posiciones para baloncesto
INSERT INTO `posiciones_deporte` (`deporte_id`, `codigo`, `nombre_mostrado`, `descripcion`, `coordenada_x`, `coordenada_y`, `orden_visualizacion`, `es_titular`) VALUES
(3, 'PG', 'Base', 'Point Guard - Base', 30.00, 50.00, 1, 1),
(3, 'SG', 'Escolta', 'Shooting Guard - Escolta', 50.00, 30.00, 2, 1),
(3, 'SF', 'Alero', 'Small Forward - Alero', 70.00, 50.00, 3, 1),
(3, 'PF', 'Ala-Pivot', 'Power Forward - Ala-Pivot', 50.00, 70.00, 4, 1),
(3, 'C', 'Pivot', 'Center - Pivot', 80.00, 50.00, 5, 1),
(3, 'SUB', 'Suplente', 'Jugador suplente', NULL, NULL, 6, 0);

-- 4. Insertar posiciones para voleibol
INSERT INTO `posiciones_deporte` (`deporte_id`, `codigo`, `nombre_mostrado`, `descripcion`, `coordenada_x`, `coordenada_y`, `orden_visualizacion`, `es_titular`) VALUES
(2, 'S', 'Colocador', 'Setter - Colocador', 40.00, 50.00, 1, 1),
(2, 'OH1', 'Opuesto', 'Outside Hitter - Atacante opuesto', 60.00, 20.00, 2, 1),
(2, 'OH2', 'Atacante Exterior', 'Outside Hitter - Atacante exterior', 60.00, 80.00, 3, 1),
(2, 'MB1', 'Bloqueador Central 1', 'Middle Blocker - Bloqueador central', 70.00, 40.00, 4, 1),
(2, 'MB2', 'Bloqueador Central 2', 'Middle Blocker - Bloqueador central', 70.00, 60.00, 5, 1),
(2, 'L', 'Líbero', 'Libero - Líbero', 30.00, 50.00, 6, 1),
(2, 'SUB', 'Suplente', 'Jugador suplente', NULL, NULL, 7, 0);

-- 5. Crear tabla de jugadores (para participantes individuales y miembros de equipos)
CREATE TABLE `jugadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participante_id` int(11) DEFAULT NULL COMMENT 'Si pertenece a un equipo, NULL si es individual',
  `torneo_id` int(11) NOT NULL COMMENT 'Torneo al que pertenece',
  `deporte_id` tinyint(4) NOT NULL,
  `posicion_id` int(11) DEFAULT NULL COMMENT 'Posición si pertenece a un equipo, NULL si es individual',
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `numero_camiseta` tinyint(4) DEFAULT NULL COMMENT 'Número de camiseta si es de equipo',
  `url_foto` varchar(255) DEFAULT NULL,
  `es_capitan` tinyint(1) DEFAULT 0,
  `es_titular` tinyint(1) DEFAULT 1,
  `fecha_nacimiento` date DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `altura` decimal(4,2) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_jugadores_participante` (`participante_id`),
  KEY `fk_jugadores_torneo` (`torneo_id`),
  KEY `fk_jugadores_deporte` (`deporte_id`),
  KEY `fk_jugadores_posicion` (`posicion_id`),
  KEY `idx_jugadores_equipo_numero` (`participante_id`, `numero_camiseta`),
  CONSTRAINT `fk_jugadores_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jugadores_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jugadores_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`),
  CONSTRAINT `fk_jugadores_posicion` FOREIGN KEY (`posicion_id`) REFERENCES `posiciones_deporte` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Modificar tabla torneos para incluir el tipo de torneo (liga o bracket)
ALTER TABLE `torneos` 
ADD COLUMN `tipo_torneo` ENUM('liga', 'bracket') DEFAULT 'liga' COMMENT 'Tipo de torneo: liga (round-robin) o bracket (eliminatoria)' AFTER `ida_y_vuelta`,
ADD COLUMN `fase_actual` ENUM('liga', 'cuartos', 'semis', 'final') DEFAULT 'liga' COMMENT 'Fase actual del torneo' AFTER `tipo_torneo`;

-- 7. Crear tabla para gestionar brackets de eliminatorias
CREATE TABLE `bracket_torneos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `torneo_id` int(11) NOT NULL,
  `fase` ENUM('cuartos', 'semis', 'final') NOT NULL,
  `posicion_bracket` tinyint(4) NOT NULL COMMENT 'Posición en el bracket (1-8 para cuartos, 1-4 para semis, etc)',
  `participante_id` int(11) DEFAULT NULL,
  `ganador_de_partido_id` int(11) DEFAULT NULL COMMENT 'Se llena automáticamente cuando gana',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bracket_posicion` (`torneo_id`, `fase`, `posicion_bracket`),
  KEY `fk_bracket_torneo` (`torneo_id`),
  KEY `fk_bracket_participante` (`participante_id`),
  KEY `fk_bracket_partido` (`ganador_de_partido_id`),
  CONSTRAINT `fk_bracket_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bracket_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bracket_partido` FOREIGN KEY (`ganador_de_partido_id`) REFERENCES `partidos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Crear vista para obtener información completa de jugadores
CREATE VIEW `vista_jugadores_completa` AS
SELECT 
    j.id,
    j.nombre,
    j.apellido,
    CONCAT(j.nombre, ' ', j.apellido) AS nombre_completo,
    j.numero_camiseta,
    j.url_foto,
    j.es_capitan,
    j.es_titular,
    j.fecha_nacimiento,
    j.peso,
    j.altura,
    p.nombre_mostrado AS equipo,
    p.nombre_corto AS equipo_corto,
    p.url_logo AS logo_equipo,
    pos.codigo AS codigo_posicion,
    pos.nombre_mostrado AS nombre_posicion,
    pos.coordenada_x,
    pos.coordenada_y,
    pos.orden_visualizacion,
    d.nombre_mostrado AS deporte,
    d.codigo AS codigo_deporte,
    t.nombre AS torneo,
    CASE 
        WHEN j.participante_id IS NULL THEN 'individual'
        ELSE 'equipo'
    END AS tipo_participacion
FROM jugadores j
LEFT JOIN participantes p ON j.participante_id = p.id
LEFT JOIN posiciones_deporte pos ON j.posicion_id = pos.id
LEFT JOIN deportes d ON j.deporte_id = d.id
LEFT JOIN torneos t ON j.torneo_id = t.id;

-- 9. Crear índices adicionales para optimizar consultas
CREATE INDEX idx_torneos_tipo_fase ON torneos(tipo_torneo, fase_actual);
CREATE INDEX idx_jugadores_torneo_equipo ON jugadores(torneo_id, participante_id);
CREATE INDEX idx_jugadores_titular ON jugadores(torneo_id, es_titular);

-- 10. Actualizar deportes existentes para marcar cuáles permiten torneos bracket desde el inicio
UPDATE deportes SET es_por_equipos = 0 WHERE codigo IN ('chess', 'table_tennis');

COMMIT;