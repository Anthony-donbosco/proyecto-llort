-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2025 at 12:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistema_deportivo`
--

-- --------------------------------------------------------

--
-- Table structure for table `bracket_torneos`
--

CREATE TABLE `bracket_torneos` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `fase` enum('cuartos','semis','final') NOT NULL,
  `posicion_bracket` tinyint(4) NOT NULL COMMENT 'Posición en el bracket (1-8 para cuartos, 1-4 para semis, etc)',
  `participante_id` int(11) DEFAULT NULL,
  `ganador_de_partido_id` int(11) DEFAULT NULL COMMENT 'Se llena automáticamente cuando gana',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bracket_torneos`
--

INSERT INTO `bracket_torneos` (`id`, `torneo_id`, `fase`, `posicion_bracket`, `participante_id`, `ganador_de_partido_id`, `creado_en`) VALUES
(53, 13, 'semis', 1, 42, NULL, '2025-10-29 23:12:39'),
(54, 13, 'semis', 2, 40, NULL, '2025-10-29 23:12:39'),
(55, 13, 'semis', 3, 39, NULL, '2025-10-29 23:12:39'),
(56, 13, 'semis', 4, 41, NULL, '2025-10-29 23:12:39');

-- --------------------------------------------------------

--
-- Table structure for table `configuracion_galeria`
--

CREATE TABLE `configuracion_galeria` (
  `id` int(11) NOT NULL,
  `clave` varchar(64) NOT NULL COMMENT 'Clave de configuración',
  `valor` varchar(255) DEFAULT NULL COMMENT 'Valor de la configuración',
  `descripcion` varchar(255) DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `configuracion_galeria`
--

INSERT INTO `configuracion_galeria` (`id`, `clave`, `valor`, `descripcion`, `actualizado_en`) VALUES
(1, 'temporada_galeria_activa', NULL, 'ID de la temporada que se muestra actualmente en la galería', '2025-10-26 15:07:28'),
(2, 'mostrar_todas_temporadas', '0', '1 para mostrar todas las temporadas, 0 para mostrar solo la activa', '2025-10-26 15:07:28');

-- --------------------------------------------------------

--
-- Table structure for table `configuracion_noticias`
--

CREATE TABLE `configuracion_noticias` (
  `id` int(11) NOT NULL,
  `clave` varchar(64) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `configuracion_noticias`
--

INSERT INTO `configuracion_noticias` (`id`, `clave`, `valor`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'noticias_por_pagina', '9', 'Cantidad de noticias por página en el listado', '2025-10-27 21:19:26', '2025-10-27 21:19:26'),
(2, 'mostrar_autor', '1', 'Mostrar nombre del autor en las noticias', '2025-10-27 21:19:26', '2025-10-27 21:19:26'),
(3, 'mostrar_fecha', '1', 'Mostrar fecha de publicación', '2025-10-27 21:19:26', '2025-10-27 21:19:26'),
(4, 'permitir_comentarios', '0', 'Permitir comentarios en noticias (futuro)', '2025-10-27 21:19:26', '2025-10-27 21:19:26'),
(5, 'max_noticias_destacadas', '3', 'Máximo de noticias destacadas en slider', '2025-10-27 21:19:26', '2025-10-27 21:19:26');

-- --------------------------------------------------------

--
-- Table structure for table `cronometro_partido`
--

CREATE TABLE `cronometro_partido` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL COMMENT 'ID del partido',
  `estado_cronometro` enum('detenido','corriendo','pausado','finalizado') DEFAULT 'detenido',
  `tiempo_transcurrido` int(11) DEFAULT 0 COMMENT 'Segundos transcurridos',
  `tiempo_inicio` timestamp NULL DEFAULT NULL COMMENT 'Cuando se inici├│ el cron├│metro',
  `tiempo_pausa` timestamp NULL DEFAULT NULL COMMENT 'Cuando se paus├│',
  `periodo_actual` varchar(20) DEFAULT '1er Tiempo' COMMENT 'Periodo del juego',
  `tiempo_agregado` int(11) DEFAULT 0 COMMENT 'Minutos de tiempo agregado',
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cronometro_partido`
--

INSERT INTO `cronometro_partido` (`id`, `partido_id`, `estado_cronometro`, `tiempo_transcurrido`, `tiempo_inicio`, `tiempo_pausa`, `periodo_actual`, `tiempo_agregado`, `actualizado_en`) VALUES
(43, 137, 'pausado', 4846, '2025-10-29 22:20:50', '2025-10-29 22:21:48', '1er Tiempo', 0, '2025-10-29 22:21:48'),
(50, 140, 'pausado', 356, '2025-10-29 23:01:28', '2025-10-29 23:07:24', '1er Tiempo', 0, '2025-10-29 23:07:24'),
(51, 206, 'pausado', 7, '2025-10-29 23:12:52', '2025-10-29 23:12:59', '1er Tiempo', 0, '2025-10-29 23:12:59'),
(52, 207, 'pausado', 6, '2025-10-29 23:13:04', '2025-10-29 23:13:10', '1er Tiempo', 0, '2025-10-29 23:13:10'),
(53, 208, 'pausado', 5, '2025-10-29 23:13:36', '2025-10-29 23:13:41', '1er Tiempo', 0, '2025-10-29 23:13:41'),
(54, 146, 'detenido', 0, NULL, NULL, '1er Tiempo', 0, '2025-10-29 23:14:34');

-- --------------------------------------------------------

--
-- Table structure for table `deportes`
--

CREATE TABLE `deportes` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `es_por_equipos` tinyint(1) NOT NULL,
  `tipo_puntuacion` enum('goles','puntos','sets','ganador_directo') DEFAULT 'goles' COMMENT 'Tipo de sistema de puntuaci├│n del deporte',
  `eventos_disponibles` text DEFAULT NULL COMMENT 'JSON con eventos disponibles para este deporte',
  `usa_cronometro` tinyint(1) DEFAULT 1 COMMENT 'Si el deporte usa cron├│metro (1) o solo registro de eventos (0)',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deportes`
--

INSERT INTO `deportes` (`id`, `codigo`, `nombre_mostrado`, `es_por_equipos`, `tipo_puntuacion`, `eventos_disponibles`, `usa_cronometro`, `creado_en`) VALUES
(1, 'football', 'Fútbol', 1, 'goles', '[{\"tipo\": \"gol\", \"nombre\": \"Gol\", \"puntos\": 1}, {\"tipo\": \"autogol\", \"nombre\": \"Autogol\", \"puntos\": 1}, {\"tipo\": \"penal_anotado\", \"nombre\": \"Penal Anotado\", \"puntos\": 1}, {\"tipo\": \"penal_fallado\", \"nombre\": \"Penal Fallado\", \"puntos\": 0}, {\"tipo\": \"tarjeta_amarilla\", \"nombre\": \"Tarjeta Amarilla\", \"puntos\": 0}, {\"tipo\": \"tarjeta_roja\", \"nombre\": \"Tarjeta Roja\", \"puntos\": 0}, {\"tipo\": \"porteria_cero\", \"nombre\": \"Portería en Cero\", \"puntos\": 0}]', 1, '2025-10-24 01:17:52'),
(2, 'volleyball', 'Voleibol', 1, 'puntos', '[{\"tipo\": \"punto\", \"nombre\": \"Punto\", \"puntos\": 1}, {\"tipo\": \"ace\", \"nombre\": \"Ace (Saque Directo)\", \"puntos\": 1}, {\"tipo\": \"bloqueo\", \"nombre\": \"Punto por Bloqueo\", \"puntos\": 1}]', 0, '2025-10-24 01:17:52'),
(3, 'basketball', 'Baloncesto', 1, 'puntos', '[{\"tipo\": \"canasta_1pt\", \"nombre\": \"Tiro Libre (1pt)\", \"puntos\": 1}, {\"tipo\": \"canasta_2pt\", \"nombre\": \"Canasta 2 Puntos\", \"puntos\": 2}, {\"tipo\": \"canasta_3pt\", \"nombre\": \"Triple (3pts)\", \"puntos\": 3}, {\"tipo\": \"falta\", \"nombre\": \"Falta Personal\", \"puntos\": 0}, {\"tipo\": \"falta_tecnica\", \"nombre\": \"Falta Técnica\", \"puntos\": 0}]', 1, '2025-10-24 01:17:52'),
(4, 'table_tennis', 'Ping Pong', 0, 'sets', '[{\"tipo\": \"set_ganado_local\", \"nombre\": \"Set Ganado Local\", \"puntos\": 1}, {\"tipo\": \"set_ganado_visitante\", \"nombre\": \"Set Ganado Visitante\", \"puntos\": 1}, {\"tipo\": \"punto_pingpong\", \"nombre\": \"Punto\", \"puntos\": 0}]', 0, '2025-10-24 01:17:52'),
(5, 'chess', 'Ajedrez', 0, 'ganador_directo', '[{\"tipo\": \"victoria_local\", \"nombre\": \"Victoria Local\", \"puntos\": 0}, {\"tipo\": \"victoria_visitante\", \"nombre\": \"Victoria Visitante\", \"puntos\": 0}, {\"tipo\": \"empate\", \"nombre\": \"Empate (Tablas)\", \"puntos\": 0}, {\"tipo\": \"jaque_mate\", \"nombre\": \"Jaque Mate\", \"puntos\": 0}]', 0, '2025-10-24 01:17:52'),
(6, 'futsal_3', 'Fútbol Sala 3v3', 1, 'goles', '[{\"tipo\": \"gol\", \"nombre\": \"Gol\", \"puntos\": 1}, {\"tipo\": \"autogol\", \"nombre\": \"Autogol\", \"puntos\": 1}, {\"tipo\": \"penal_anotado\", \"nombre\": \"Penal Anotado\", \"puntos\": 1}, {\"tipo\": \"tarjeta_amarilla\", \"nombre\": \"Tarjeta Amarilla\", \"puntos\": 0}, {\"tipo\": \"tarjeta_roja\", \"nombre\": \"Tarjeta Roja\", \"puntos\": 0}]', 1, '2025-10-27 01:03:54'),
(7, 'futsal_4', 'Fútbol Sala 4v4', 1, 'goles', '[{\"tipo\": \"gol\", \"nombre\": \"Gol\", \"puntos\": 1}, {\"tipo\": \"autogol\", \"nombre\": \"Autogol\", \"puntos\": 1}, {\"tipo\": \"penal_anotado\", \"nombre\": \"Penal Anotado\", \"puntos\": 1}, {\"tipo\": \"tarjeta_amarilla\", \"nombre\": \"Tarjeta Amarilla\", \"puntos\": 0}, {\"tipo\": \"tarjeta_roja\", \"nombre\": \"Tarjeta Roja\", \"puntos\": 0}]', 1, '2025-10-27 01:03:54'),
(8, 'futsal_5', 'Fútbol Sala 5v5', 1, 'goles', '[{\"tipo\": \"gol\", \"nombre\": \"Gol\", \"puntos\": 1}, {\"tipo\": \"autogol\", \"nombre\": \"Autogol\", \"puntos\": 1}, {\"tipo\": \"penal_anotado\", \"nombre\": \"Penal Anotado\", \"puntos\": 1}, {\"tipo\": \"tarjeta_amarilla\", \"nombre\": \"Tarjeta Amarilla\", \"puntos\": 0}, {\"tipo\": \"tarjeta_roja\", \"nombre\": \"Tarjeta Roja\", \"puntos\": 0}]', 1, '2025-10-27 01:03:54'),
(9, 'basketball_3', 'Basketball 3v3', 1, 'puntos', '[{\"tipo\": \"canasta_1pt\", \"nombre\": \"Tiro Libre (1pt)\", \"puntos\": 1}, {\"tipo\": \"canasta_2pt\", \"nombre\": \"Canasta 2 Puntos\", \"puntos\": 2}, {\"tipo\": \"canasta_3pt\", \"nombre\": \"Triple (3pts)\", \"puntos\": 3}, {\"tipo\": \"falta\", \"nombre\": \"Falta Personal\", \"puntos\": 0}, {\"tipo\": \"falta_tecnica\", \"nombre\": \"Falta Técnica\", \"puntos\": 0}]', 1, '2025-10-27 01:03:54'),
(10, 'basketball_4', 'Basketball 4v4', 1, 'puntos', '[{\"tipo\": \"canasta_1pt\", \"nombre\": \"Tiro Libre (1pt)\", \"puntos\": 1}, {\"tipo\": \"canasta_2pt\", \"nombre\": \"Canasta 2 Puntos\", \"puntos\": 2}, {\"tipo\": \"canasta_3pt\", \"nombre\": \"Triple (3pts)\", \"puntos\": 3}, {\"tipo\": \"falta\", \"nombre\": \"Falta Personal\", \"puntos\": 0}, {\"tipo\": \"falta_tecnica\", \"nombre\": \"Falta Técnica\", \"puntos\": 0}]', 1, '2025-10-27 01:03:54'),
(11, 'basketball_5', 'Basketball 5v5', 1, 'puntos', '[{\"tipo\": \"canasta_1pt\", \"nombre\": \"Tiro Libre (1pt)\", \"puntos\": 1}, {\"tipo\": \"canasta_2pt\", \"nombre\": \"Canasta 2 Puntos\", \"puntos\": 2}, {\"tipo\": \"canasta_3pt\", \"nombre\": \"Triple (3pts)\", \"puntos\": 3}, {\"tipo\": \"falta\", \"nombre\": \"Falta Personal\", \"puntos\": 0}, {\"tipo\": \"falta_tecnica\", \"nombre\": \"Falta Técnica\", \"puntos\": 0}]', 1, '2025-10-27 01:03:54');

-- --------------------------------------------------------

--
-- Table structure for table `enlaces_bracket`
--

CREATE TABLE `enlaces_bracket` (
  `id` int(11) NOT NULL,
  `partido_origen_id` int(11) NOT NULL COMMENT 'Partido origen',
  `partido_destino_id` int(11) NOT NULL COMMENT 'Partido destino',
  `posicion_destino_id` tinyint(4) NOT NULL COMMENT '1=local, 2=visitante',
  `tipo_condicion_id` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'ganador/perdedor/específico',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `estados_partido`
--

CREATE TABLE `estados_partido` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `es_estado_final` tinyint(1) DEFAULT 0,
  `orden` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `estados_partido`
--

INSERT INTO `estados_partido` (`id`, `codigo`, `nombre_mostrado`, `descripcion`, `es_estado_final`, `orden`) VALUES
(1, 'not_started', 'No Iniciado', 'Partido pendiente de iniciar', 0, 1),
(2, 'scheduled', 'Programado', 'Partido programado con fecha confirmada', 0, 2),
(3, 'live', 'En Vivo', 'Partido en curso', 0, 3),
(4, 'halftime', 'Medio Tiempo', 'Partido en descanso', 0, 4),
(5, 'finished', 'Finalizado', 'Partido terminado', 1, 5),
(6, 'postponed', 'Pospuesto', 'Partido pospuesto para otra fecha', 0, 6),
(7, 'cancelled', 'Cancelado', 'Partido cancelado', 1, 7),
(8, 'suspended', 'Suspendido', 'Partido suspendido (puede reanudarse)', 0, 8),
(9, 'awarded', 'WO/Adjudicado', 'Resultado por walkover o decisión administrativa', 1, 9);

-- --------------------------------------------------------

--
-- Table structure for table `estados_torneo`
--

CREATE TABLE `estados_torneo` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `orden` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `estados_torneo`
--

INSERT INTO `estados_torneo` (`id`, `codigo`, `nombre_mostrado`, `descripcion`, `orden`) VALUES
(1, 'draft', 'Borrador', 'Torneo en preparación', 1),
(2, 'registration', 'Inscripción', 'Periodo de inscripción abierto', 2),
(3, 'active', 'Activo', 'Torneo en curso', 3),
(4, 'paused', 'Pausado', 'Torneo pausado temporalmente', 4),
(5, 'closed', 'Finalizado', 'Torneo finalizado', 5),
(6, 'cancelled', 'Cancelado', 'Torneo cancelado', 6);

-- --------------------------------------------------------

--
-- Table structure for table `estados_usuario`
--

CREATE TABLE `estados_usuario` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `estados_usuario`
--

INSERT INTO `estados_usuario` (`id`, `codigo`, `nombre_mostrado`, `descripcion`) VALUES
(1, 'active', 'Activo', 'Usuario activo en el sistema'),
(2, 'blocked', 'Bloqueado', 'Usuario bloqueado temporalmente'),
(3, 'suspended', 'Suspendido', 'Usuario suspendido por violación de normas'),
(4, 'inactive', 'Inactivo', 'Usuario inactivo (sin uso prolongado)');

-- --------------------------------------------------------

--
-- Table structure for table `eventos_partido`
--

CREATE TABLE `eventos_partido` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL COMMENT 'ID del partido',
  `miembro_plantel_id` int(11) NOT NULL COMMENT 'Jugador que realiz├│ el evento',
  `asistencia_miembro_plantel_id` int(11) DEFAULT NULL,
  `tipo_evento` enum('gol','autogol','penal_anotado','penal_fallado','porteria_cero','tarjeta_amarilla','tarjeta_roja','canasta_1pt','canasta_2pt','canasta_3pt','falta','falta_tecnica','punto','ace','bloqueo','set_ganado_local','set_ganado_visitante','punto_pingpong','victoria_local','victoria_visitante','empate','jaque_mate','asistencia','otro') DEFAULT 'gol',
  `valor_puntos` tinyint(4) DEFAULT 1 COMMENT 'Valor en puntos del evento (1, 2 o 3 para basketball)',
  `minuto` varchar(10) DEFAULT NULL COMMENT 'Minuto del evento (ej: 45, 90+2)',
  `periodo` varchar(20) DEFAULT NULL COMMENT 'Periodo del juego (1er tiempo, 2do tiempo, etc)',
  `notas` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `eventos_partido`
--

INSERT INTO `eventos_partido` (`id`, `partido_id`, `miembro_plantel_id`, `asistencia_miembro_plantel_id`, `tipo_evento`, `valor_puntos`, `minuto`, `periodo`, `notas`, `creado_en`) VALUES
(82, 137, 28, 27, 'gol', 1, '1', NULL, NULL, '2025-10-29 20:55:21'),
(83, 137, 29, 30, 'gol', 1, '09', NULL, NULL, '2025-10-29 22:21:04'),
(84, 140, 6, 3, 'gol', 1, '2', NULL, NULL, '2025-10-29 23:01:59');

--
-- Triggers `eventos_partido`
--
DELIMITER $$
CREATE TRIGGER `trg_evento_delete` BEFORE DELETE ON `eventos_partido` FOR EACH ROW BEGIN
    
    IF OLD.tipo_evento IN ('gol', 'penal_anotado') THEN
        UPDATE miembros_plantel 
        SET goles = GREATEST(goles - 1, 0) 
        WHERE id = OLD.miembro_plantel_id;
    END IF;
    
    
    IF OLD.asistencia_miembro_plantel_id IS NOT NULL THEN
        UPDATE miembros_plantel 
        SET asistencias = GREATEST(asistencias - 1, 0) 
        WHERE id = OLD.asistencia_miembro_plantel_id;
    END IF;
    
    
    IF OLD.tipo_evento = 'porteria_cero' THEN
        UPDATE miembros_plantel 
        SET porterias_cero = GREATEST(porterias_cero - 1, 0) 
        WHERE id = OLD.miembro_plantel_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_evento_insert` AFTER INSERT ON `eventos_partido` FOR EACH ROW BEGIN
    
    IF NEW.tipo_evento IN ('gol', 'penal_anotado') THEN
        UPDATE miembros_plantel 
        SET goles = goles + 1 
        WHERE id = NEW.miembro_plantel_id;
    END IF;
    
    
    IF NEW.asistencia_miembro_plantel_id IS NOT NULL THEN
        UPDATE miembros_plantel 
        SET asistencias = asistencias + 1 
        WHERE id = NEW.asistencia_miembro_plantel_id;
    END IF;
    
    
    IF NEW.tipo_evento = 'porteria_cero' THEN
        UPDATE miembros_plantel 
        SET porterias_cero = porterias_cero + 1 
        WHERE id = NEW.miembro_plantel_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_evento_update` BEFORE UPDATE ON `eventos_partido` FOR EACH ROW BEGIN
    
    IF OLD.miembro_plantel_id <> NEW.miembro_plantel_id THEN
        
        IF OLD.tipo_evento IN ('gol', 'penal_anotado') THEN
            UPDATE miembros_plantel 
            SET goles = GREATEST(goles - 1, 0) 
            WHERE id = OLD.miembro_plantel_id;
        END IF;
        
        
        IF NEW.tipo_evento IN ('gol', 'penal_anotado') THEN
            UPDATE miembros_plantel 
            SET goles = goles + 1 
            WHERE id = NEW.miembro_plantel_id;
        END IF;
    END IF;
    
    
    IF (OLD.asistencia_miembro_plantel_id IS NULL AND NEW.asistencia_miembro_plantel_id IS NOT NULL) OR
       (OLD.asistencia_miembro_plantel_id IS NOT NULL AND NEW.asistencia_miembro_plantel_id IS NULL) OR
       (OLD.asistencia_miembro_plantel_id <> NEW.asistencia_miembro_plantel_id) THEN
        
        
        IF OLD.asistencia_miembro_plantel_id IS NOT NULL THEN
            UPDATE miembros_plantel 
            SET asistencias = GREATEST(asistencias - 1, 0) 
            WHERE id = OLD.asistencia_miembro_plantel_id;
        END IF;
        
        
        IF NEW.asistencia_miembro_plantel_id IS NOT NULL THEN
            UPDATE miembros_plantel 
            SET asistencias = asistencias + 1 
            WHERE id = NEW.asistencia_miembro_plantel_id;
        END IF;
    END IF;
    
    
    IF OLD.tipo_evento <> NEW.tipo_evento THEN
        
        IF OLD.tipo_evento IN ('gol', 'penal_anotado') THEN
            UPDATE miembros_plantel 
            SET goles = GREATEST(goles - 1, 0) 
            WHERE id = OLD.miembro_plantel_id;
        ELSEIF OLD.tipo_evento = 'porteria_cero' THEN
            UPDATE miembros_plantel 
            SET porterias_cero = GREATEST(porterias_cero - 1, 0) 
            WHERE id = OLD.miembro_plantel_id;
        END IF;
        
        
        IF NEW.tipo_evento IN ('gol', 'penal_anotado') THEN
            UPDATE miembros_plantel 
            SET goles = goles + 1 
            WHERE id = NEW.miembro_plantel_id;
        ELSEIF NEW.tipo_evento = 'porteria_cero' THEN
            UPDATE miembros_plantel 
            SET porterias_cero = porterias_cero + 1 
            WHERE id = NEW.miembro_plantel_id;
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `fases`
--

CREATE TABLE `fases` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `tipo_fase_id` tinyint(4) NOT NULL,
  `grupo_id` int(11) DEFAULT NULL COMMENT 'Si es fase de grupos, referencia al grupo',
  `orden_fase` tinyint(4) NOT NULL,
  `nombre` varchar(64) DEFAULT NULL COMMENT 'Nombre personalizado de la fase',
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fases`
--

INSERT INTO `fases` (`id`, `torneo_id`, `tipo_fase_id`, `grupo_id`, `orden_fase`, `nombre`, `fecha_inicio`, `fecha_fin`, `creado_en`) VALUES
(27, 12, 1, NULL, 1, 'Fase de Liga', '2025-10-29', NULL, '2025-10-29 23:01:21'),
(28, 13, 3, NULL, 3, 'Semifinales', '2025-11-05', '2025-11-12', '2025-10-29 23:12:39'),
(29, 13, 4, NULL, 4, 'Final', '2025-11-12', '2025-11-19', '2025-10-29 23:12:39');

-- --------------------------------------------------------

--
-- Table structure for table `galeria_temporadas`
--

CREATE TABLE `galeria_temporadas` (
  `id` int(11) NOT NULL,
  `temporada_id` int(11) NOT NULL COMMENT 'Temporada a la que pertenece la foto',
  `deporte_id` tinyint(4) DEFAULT NULL COMMENT 'Deporte relacionado (NULL si es general)',
  `titulo` varchar(120) DEFAULT NULL COMMENT 'Título de la foto',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción de la foto',
  `url_foto` varchar(255) NOT NULL COMMENT 'Ruta de la foto',
  `es_foto_grupo` tinyint(1) DEFAULT 1 COMMENT '1 si es foto grupal de selección',
  `orden` smallint(6) DEFAULT 0 COMMENT 'Orden de visualización',
  `fecha_captura` date DEFAULT NULL COMMENT 'Fecha en que se tomó la foto',
  `esta_activa` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `galeria_temporadas`
--

INSERT INTO `galeria_temporadas` (`id`, `temporada_id`, `deporte_id`, `titulo`, `descripcion`, `url_foto`, `es_foto_grupo`, `orden`, `fecha_captura`, `esta_activa`, `creado_en`) VALUES
(1, 2, NULL, 'LA SELECTA 2025 (1)', '', '../../img/galeria/69003b8f30dc3-IMG-20251024-WA0056.jpg', 1, 0, '2025-10-28', 1, '2025-10-28 03:42:07'),
(2, 2, NULL, 'LA SELECTA 2025 (2)', '', '../../img/galeria/69003b8f3188f-IMG-20251024-WA0057.jpg', 1, 1, '2025-10-28', 1, '2025-10-28 03:42:07'),
(3, 2, NULL, 'LA SELECTA 2025 (3)', '', '../../img/galeria/69003b8f31de4-IMG-20251024-WA0058.jpg', 1, 2, '2025-10-28', 1, '2025-10-28 03:42:07'),
(4, 2, NULL, 'LA SELECTA 2025 (4)', '', '../../img/galeria/69003b8f32949-IMG-20251024-WA0059.jpg', 1, 3, '2025-10-28', 1, '2025-10-28 03:42:07'),
(5, 2, NULL, 'LA SELECTA 2025 (5)', '', '../../img/galeria/69003b8f32d70-IMG-20251024-WA0048.jpg', 1, 4, '2025-10-28', 1, '2025-10-28 03:42:07'),
(6, 2, NULL, 'LA SELECTA 2025 (6)', '', '../../img/galeria/69003b8f33181-IMG-20251024-WA0049.jpg', 1, 5, '2025-10-28', 1, '2025-10-28 03:42:07'),
(7, 2, NULL, 'LA SELECTA 2025 (7)', '', '../../img/galeria/69003b8f33601-IMG-20251024-WA0050.jpg', 1, 6, '2025-10-28', 1, '2025-10-28 03:42:07'),
(8, 2, NULL, 'LA SELECTA 2025 (8)', '', '../../img/galeria/69003b8f33bd4-IMG-20251024-WA0051.jpg', 1, 7, '2025-10-28', 1, '2025-10-28 03:42:07'),
(9, 2, NULL, 'LA SELECTA 2025 (9)', '', '../../img/galeria/69003b8f3402d-IMG-20251024-WA0052.jpg', 1, 8, '2025-10-28', 1, '2025-10-28 03:42:07'),
(10, 2, NULL, 'LA SELECTA 2025 (10)', '', '../../img/galeria/69003b8f343f8-IMG-20251024-WA0042.jpg', 1, 9, '2025-10-28', 1, '2025-10-28 03:42:07'),
(11, 2, NULL, 'LA SELECTA 2025 (11)', '', '../../img/galeria/69003b8f34766-IMG-20251024-WA0043.jpg', 1, 10, '2025-10-28', 1, '2025-10-28 03:42:07'),
(12, 2, NULL, 'LA SELECTA 2025 (12)', '', '../../img/galeria/69003b8f34ae7-IMG-20251024-WA0044.jpg', 1, 11, '2025-10-28', 1, '2025-10-28 03:42:07'),
(13, 2, NULL, 'LA SELECTA 2025 (13)', '', '../../img/galeria/69003b8f350cd-IMG-20251024-WA0045.jpg', 1, 12, '2025-10-28', 1, '2025-10-28 03:42:07'),
(14, 2, NULL, 'LA SELECTA 2025 (14)', '', '../../img/galeria/69003b8f3552e-IMG-20251024-WA0046.jpg', 1, 13, '2025-10-28', 1, '2025-10-28 03:42:07'),
(15, 2, NULL, 'LA SELECTA 2025 (15)', '', '../../img/galeria/69003b8f358d4-IMG-20251024-WA0047.jpg', 1, 14, '2025-10-28', 1, '2025-10-28 03:42:07'),
(16, 2, NULL, 'LA SELECTA 2025 (16)', '', '../../img/galeria/69003b8f35ce2-IMG-20251024-WA0039.jpg', 1, 15, '2025-10-28', 1, '2025-10-28 03:42:07'),
(17, 2, NULL, 'LA SELECTA 2025 (17)', '', '../../img/galeria/69003b8f3605b-IMG-20251024-WA0040.jpg', 1, 16, '2025-10-28', 1, '2025-10-28 03:42:07'),
(18, 2, NULL, 'LA SELECTA 2025 (18)', '', '../../img/galeria/69003b8f3641a-IMG-20251024-WA0041.jpg', 1, 17, '2025-10-28', 1, '2025-10-28 03:42:07'),
(19, 2, NULL, 'LA SELECTA 2025 (19)', '', '../../img/galeria/69003b8f36c40-IMG-20251024-WA0038.jpg', 1, 18, '2025-10-28', 1, '2025-10-28 03:42:07'),
(20, 2, NULL, 'LA SELECTA 2025 (20)', '', '../../img/galeria/69003b8f370a6-IMG-20251024-WA0037.jpg', 1, 19, '2025-10-28', 1, '2025-10-28 03:42:07'),
(21, 2, NULL, '', '', '../../img/galeria/69003beab6ac1-IMG-20251024-WA0109.jpg', 1, 0, '2025-10-28', 1, '2025-10-28 03:43:38'),
(22, 2, NULL, '', '', '../../img/galeria/69003c6dd6921-IMG-20251024-WA0277.jpg', 1, 0, '2025-10-28', 1, '2025-10-28 03:45:49'),
(23, 2, NULL, 'PRUEBEAA', '', '../../img/galeria/690070cfcec8f-IMG-20251024-WA0372.jpg', 1, 0, '2025-10-28', 1, '2025-10-28 07:29:19'),
(24, 2, NULL, 'ALFIN (1)', 'Ojala que si', '../../img/galeria/69015db757c27-IMG-20251024-WA0281.jpg', 1, 0, '2025-10-29', 1, '2025-10-29 00:20:07'),
(25, 2, NULL, 'ALFIN (2)', 'Ojala que si', '../../img/galeria/69015db75862f-IMG-20251024-WA0282.jpg', 1, 1, '2025-10-29', 1, '2025-10-29 00:20:07'),
(26, 2, NULL, 'ALFIN (3)', 'Ojala que si', '../../img/galeria/69015db758e54-IMG-20251024-WA0277.jpg', 1, 2, '2025-10-29', 1, '2025-10-29 00:20:07'),
(27, 2, NULL, 'ALFIN (4)', 'Ojala que si', '../../img/galeria/69015db759475-IMG-20251024-WA0278.jpg', 1, 3, '2025-10-29', 1, '2025-10-29 00:20:07'),
(28, 2, NULL, 'ALFIN (5)', 'Ojala que si', '../../img/galeria/69015db75997a-IMG-20251024-WA0279.jpg', 1, 4, '2025-10-29', 1, '2025-10-29 00:20:07'),
(29, 2, NULL, 'ALFIN (6)', 'Ojala que si', '../../img/galeria/69015db75a38f-IMG-20251024-WA0280.jpg', 1, 5, '2025-10-29', 1, '2025-10-29 00:20:07'),
(30, 2, NULL, 'ALFIN (7)', 'Ojala que si', '../../img/galeria/69015db75a99b-IMG-20251024-WA0274.jpg', 1, 6, '2025-10-29', 1, '2025-10-29 00:20:07'),
(31, 2, NULL, 'ALFIN (8)', 'Ojala que si', '../../img/galeria/69015db75afba-IMG-20251024-WA0275.jpg', 1, 7, '2025-10-29', 1, '2025-10-29 00:20:07'),
(32, 2, NULL, 'ALFIN (9)', 'Ojala que si', '../../img/galeria/69015db75b661-IMG-20251024-WA0276.jpg', 1, 8, '2025-10-29', 1, '2025-10-29 00:20:07'),
(33, 2, NULL, 'ALFIN (10)', 'Ojala que si', '../../img/galeria/69015db75bd0e-IMG-20251024-WA0271.jpg', 1, 9, '2025-10-29', 1, '2025-10-29 00:20:07'),
(34, 2, NULL, 'ALFIN (11)', 'Ojala que si', '../../img/galeria/69015db75c33a-IMG-20251024-WA0272.jpg', 1, 10, '2025-10-29', 1, '2025-10-29 00:20:07'),
(35, 2, NULL, 'ALFIN (12)', 'Ojala que si', '../../img/galeria/69015db75c85d-IMG-20251024-WA0273.jpg', 1, 11, '2025-10-29', 1, '2025-10-29 00:20:07'),
(36, 2, NULL, 'ALFIN (13)', 'Ojala que si', '../../img/galeria/69015db75cd53-IMG-20251024-WA0267.jpg', 1, 12, '2025-10-29', 1, '2025-10-29 00:20:07'),
(37, 2, NULL, 'ALFIN (14)', 'Ojala que si', '../../img/galeria/69015db75d21c-IMG-20251024-WA0268.jpg', 1, 13, '2025-10-29', 1, '2025-10-29 00:20:07'),
(38, 2, NULL, 'ALFIN (15)', 'Ojala que si', '../../img/galeria/69015db75d6c8-IMG-20251024-WA0269.jpg', 1, 14, '2025-10-29', 1, '2025-10-29 00:20:07'),
(39, 2, NULL, 'ALFIN (16)', 'Ojala que si', '../../img/galeria/69015db75dbe6-IMG-20251024-WA0270.jpg', 1, 15, '2025-10-29', 1, '2025-10-29 00:20:07'),
(40, 2, NULL, 'ALFIN (17)', 'Ojala que si', '../../img/galeria/69015db75e05d-IMG-20251024-WA0263.jpg', 1, 16, '2025-10-29', 1, '2025-10-29 00:20:07'),
(41, 2, NULL, 'ALFIN (18)', 'Ojala que si', '../../img/galeria/69015db75e4df-IMG-20251024-WA0264.jpg', 1, 17, '2025-10-29', 1, '2025-10-29 00:20:07'),
(42, 2, NULL, 'ALFIN (19)', 'Ojala que si', '../../img/galeria/69015db75ebb8-IMG-20251024-WA0265.jpg', 1, 18, '2025-10-29', 1, '2025-10-29 00:20:07'),
(43, 2, NULL, 'ALFIN (20)', 'Ojala que si', '../../img/galeria/69015db75f244-IMG-20251024-WA0266.jpg', 1, 19, '2025-10-29', 1, '2025-10-29 00:20:07');

-- --------------------------------------------------------

--
-- Table structure for table `jornadas`
--

CREATE TABLE `jornadas` (
  `id` int(11) NOT NULL,
  `fase_id` int(11) NOT NULL,
  `numero_jornada` smallint(6) NOT NULL,
  `fecha_jornada` date NOT NULL,
  `nombre` varchar(64) DEFAULT NULL COMMENT 'Jornada 1, Fecha 2, etc',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jornadas`
--

INSERT INTO `jornadas` (`id`, `fase_id`, `numero_jornada`, `fecha_jornada`, `nombre`, `creado_en`) VALUES
(33, 27, 1, '2025-10-29', 'Jornada 1', '2025-10-29 23:01:21'),
(34, 27, 2, '2025-11-05', 'Jornada 2', '2025-10-29 23:01:21'),
(35, 27, 3, '2025-11-12', 'Jornada 3', '2025-10-29 23:01:21'),
(36, 27, 4, '2025-11-19', 'Jornada 4', '2025-10-29 23:01:21'),
(37, 27, 5, '2025-11-26', 'Jornada 5', '2025-10-29 23:01:21'),
(38, 27, 6, '2025-12-03', 'Jornada 6', '2025-10-29 23:01:21'),
(39, 27, 7, '2025-12-10', 'Jornada 7', '2025-10-29 23:01:21'),
(40, 27, 8, '2025-12-17', 'Jornada 8', '2025-10-29 23:01:21'),
(41, 27, 9, '2025-12-24', 'Jornada 9', '2025-10-29 23:01:21'),
(42, 27, 10, '2025-12-31', 'Jornada 10', '2025-10-29 23:01:21'),
(43, 27, 11, '2026-01-07', 'Jornada 11', '2025-10-29 23:01:21');

-- --------------------------------------------------------

--
-- Table structure for table `jugadores`
--

CREATE TABLE `jugadores` (
  `id` int(11) NOT NULL,
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
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jugadores_destacados`
--

CREATE TABLE `jugadores_destacados` (
  `id` int(11) NOT NULL,
  `deporte_id` tinyint(4) NOT NULL COMMENT 'Deporte al que pertenece',
  `torneo_id` int(11) DEFAULT NULL COMMENT 'Torneo específico o NULL para general',
  `miembro_plantel_id` int(11) NOT NULL COMMENT 'Referencia al jugador',
  `temporada_id` int(11) DEFAULT NULL COMMENT 'Temporada a la que pertenece',
  `tipo_destacado` enum('torneo','seleccion','general') DEFAULT 'general' COMMENT 'Tipo de destacado',
  `descripcion` text DEFAULT NULL COMMENT 'Descripción del logro',
  `fecha_destacado` date DEFAULT NULL,
  `orden` tinyint(4) DEFAULT 0 COMMENT 'Orden de visualización',
  `esta_activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `miembros_grupo`
--

CREATE TABLE `miembros_grupo` (
  `grupo_id` int(11) NOT NULL,
  `participante_id` int(11) NOT NULL,
  `semilla_en_grupo` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `miembros_plantel`
--

CREATE TABLE `miembros_plantel` (
  `id` int(11) NOT NULL,
  `plantel_id` int(11) NOT NULL,
  `nombre_jugador` varchar(120) NOT NULL,
  `posicion` varchar(32) DEFAULT NULL COMMENT 'Ej: Delantero, Defensa',
  `url_foto` varchar(255) DEFAULT NULL COMMENT 'Ruta a la foto del jugador',
  `edad` tinyint(4) DEFAULT NULL,
  `grado` varchar(16) DEFAULT NULL COMMENT 'Ej: 2°A, 9°B',
  `numero_camiseta` tinyint(4) DEFAULT NULL,
  `goles` int(11) DEFAULT 0 COMMENT 'Goles anotados',
  `asistencias` int(11) DEFAULT 0 COMMENT 'Asistencias realizadas',
  `mvps` int(11) DEFAULT 0,
  `porterias_cero` int(11) DEFAULT 0 COMMENT 'Porterías a cero (solo para porteros)',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `miembros_plantel`
--

INSERT INTO `miembros_plantel` (`id`, `plantel_id`, `nombre_jugador`, `posicion`, `url_foto`, `edad`, `grado`, `numero_camiseta`, `goles`, `asistencias`, `mvps`, `porterias_cero`, `creado_en`) VALUES
(1, 1, 'Steven Lopez', 'Mediocampista', '../../img/jugadores/68fbca6494b3f-jugadorSteven.jpg', 18, '2° B', 10, 0, 0, 0, 0, '2025-10-24 18:50:12'),
(3, 1, 'Edgardo Rojas', 'Defensa', '../../img/jugadores/68feb15bb092e-jugadorEdgardo.jpg', 16, '1°A', 2, 0, 1, 0, 0, '2025-10-26 15:07:36'),
(4, 1, 'Isaac Escamilla', 'Delantero', '../../img/jugadores/68fe45bc34d4a-jugadorIsaac.jpg', 16, '1°A', 9, 0, 0, 0, 0, '2025-10-26 15:07:36'),
(5, 1, 'Alexis Mendoza', 'Defensa', '../../img/jugadores/68fe457a77207-jugadorAlexis.jpg', 17, '1°B', 3, 0, 0, 0, 0, '2025-10-26 15:07:36'),
(6, 1, 'Jefferson Mejía', 'Defensa', '../../img/jugadores/68fe4582411e8-jugadorJeffMejia.jpg', 16, '1°B', 4, 1, 0, 0, 0, '2025-10-26 15:07:36'),
(7, 1, 'Byron Segovia', 'Delantero', '../../img/jugadores/68fe45a869c6f-jugadorByron.jpg', 17, '1°A', 7, 0, 0, 0, 0, '2025-10-26 15:07:36'),
(8, 1, 'Oscar Vázquez', 'Mediocampista', '../../img/jugadores/68fe45b1dedda-judadorOscar.jpg', 20, '1°A', 8, 0, 0, 0, 0, '2025-10-26 15:07:36'),
(9, 1, 'Javier Barrera', 'Portero', '../../img/jugadores/68fe4348b9d18-jugadorJavier.jpg', 17, '2°B', 1, 0, 0, 0, 0, '2025-10-26 15:07:36'),
(10, 1, 'Kevin Lopez', 'Delantero', '../../img/jugadores/68fe45c912aeb-jugadorKevin.jpg', 17, '2°A', 11, 0, 0, 0, 0, '2025-10-26 15:07:36'),
(11, 1, 'Jasson López', 'Defensa', '../../img/jugadores/68fe459441f71-jugadorJasson.jpg', 17, '2°A', 5, 0, 0, 2, 0, '2025-10-26 15:07:36'),
(12, 1, 'Aldo Flores', 'Mediocampista', '../../img/jugadores/68fe459d4643e-jugadorAldo.jpg', 17, '2°A', 6, 0, 0, 0, 0, '2025-10-26 15:07:36'),
(26, 14, 'Carlos Ramírez', 'Portero', '../../img/jugadores/690070f46bd6b-IMG-20251024-WA0374.jpg', 18, '2° A', 1, 0, 0, 2, 0, '2025-10-26 15:22:57'),
(27, 14, 'Miguel Torres', 'Defensa', NULL, 17, '2° B', 4, 0, 1, 0, 0, '2025-10-26 15:22:57'),
(28, 14, 'Luis Hernández', 'Delantero', '', 16, '1° A', 9, 1, 0, 1, 0, '2025-10-26 15:22:57'),
(29, 15, 'Roberto Gómez', 'Portero', '', 17, '2° A', 1, 1, 0, 0, 0, '2025-10-26 15:22:57'),
(30, 15, 'Pedro Martínez', 'Mediocampista', '', 18, '2° B', 8, 0, 1, 0, 0, '2025-10-26 15:22:57'),
(31, 15, 'Juan Pérez', 'Delantero', '', 17, '2° A', 10, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(32, 16, 'Antonio Silva', 'Portero', NULL, 16, '1° B', 1, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(33, 16, 'Fernando Castro', 'Defensa', NULL, 17, '2° A', 3, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(34, 16, 'Diego Morales', 'Delantero', NULL, 18, '2° B', 11, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(35, 17, 'Sergio Ruiz', 'Portero', NULL, 17, '2° A', 1, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(36, 17, 'Mario Vargas', 'Mediocampista', NULL, 16, '1° A', 6, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(37, 17, 'Ricardo Delgado', 'Delantero', NULL, 17, '2° B', 9, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(38, 18, 'Andrés Ortiz', 'Portero', '', 18, '2° B', 1, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(39, 18, 'Gabriel Medina', 'Defensa', NULL, 17, '2° A', 5, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(40, 18, 'Daniel Ramos', 'Delantero', NULL, 16, '1° B', 7, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(41, 19, 'Javier Soto', 'Portero', NULL, 17, '2° A', 1, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(42, 19, 'Alberto Jimenez', 'Mediocampista', '', 18, '2° B', 10, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(43, 19, 'Rafael Cordero', 'Delantero', '', 17, '2° A', 9, 0, 0, 1, 0, '2025-10-26 15:22:57'),
(44, 20, 'Cristian Vega', 'Portero', NULL, 16, '1° A', 1, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(45, 20, 'Manuel Navarro', 'Defensa', NULL, 17, '2° B', 2, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(46, 20, 'Pablo Guzmán', 'Delantero', '', 18, '2° A', 11, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(47, 21, 'Esteban Reyes', 'Portero', NULL, 17, '2° A', 1, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(48, 21, 'Alejandro Cruz', 'Mediocampista', NULL, 16, '1° B', 8, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(49, 21, 'Francisco Díaz', 'Delantero', '', 17, '2° B', 9, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(50, 22, 'Hugo Paredes', 'Portero', NULL, 18, '2° B', 1, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(51, 22, 'Gustavo Luna', 'Defensa', NULL, 17, '2° A', 4, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(52, 22, 'Marcos Salazar', 'Delantero', NULL, 16, '1° A', 10, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(53, 23, 'Rodrigo Campos', 'Portero', NULL, 17, '2° A', 1, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(54, 23, 'Iván Fuentes', 'Mediocampista', '', 18, '2° B', 6, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(55, 23, 'Oscar Peña', 'Delantero', '', 17, '2° A', 11, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(56, 24, 'Victor Acosta', 'Portero', '', 16, '1° B', 1, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(57, 24, 'César Molina', 'Defensa', '', 17, '2° A', 3, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(58, 24, 'Ernesto Ponce', 'Delantero', NULL, 18, '2° B', 9, 0, 0, 0, 0, '2025-10-26 15:22:57'),
(59, 25, 'Carlos Veloz', 'Portero', NULL, 17, '2°B', 1, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(60, 25, 'Miguel Rámirez', 'Ala', NULL, 16, '1°A', 7, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(61, 25, 'Jorge Flash', 'Pivote', NULL, 17, '2°B', 10, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(62, 25, 'Luis Trueno', 'Ala', NULL, 16, '1°A', 9, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(63, 26, 'Pedro Azul', 'Portero', NULL, 16, '1°A', 1, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(64, 26, 'Roberto Cielo', 'Ala', NULL, 17, '2°B', 8, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(65, 26, 'Antonio Mar', 'Pivote', NULL, 16, '1°A', 11, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(66, 26, 'Diego Ocano', 'Ala', NULL, 17, '2°B', 7, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(71, 28, 'Pablo Brillante', 'Portero', NULL, 16, '1°A', 1, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(72, 28, 'Andrés Luz', 'Ala', NULL, 17, '2°B', 7, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(73, 28, 'Gabriel Destello', 'Pivote', NULL, 16, '1°A', 11, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(74, 28, 'Sergio Resplandor', 'Ala', NULL, 17, '2°B', 10, 0, 0, 0, 0, '2025-10-27 01:07:12'),
(75, 29, 'Michael Jordan Jr.', 'Alero', NULL, 17, '2°B', 23, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(76, 29, 'Kevin Durant III', 'Ala-Pívot', NULL, 16, '1°A', 35, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(77, 29, 'Stephen Curry II', 'Base', NULL, 17, '2°B', 30, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(78, 29, 'LeBron James Jr', 'Suplente Mediocampista', '', 16, '1°A', 6, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(79, 30, 'Kobe Bryant II', 'Escolta', NULL, 17, '2°B', 24, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(80, 30, 'Tim Duncan Jr.', 'Ala-Pívot', NULL, 16, '1°A', 21, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(81, 30, 'Chris Paul II', 'Base', NULL, 17, '2°B', 3, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(82, 30, 'James Harden Jr.', 'Escolta', NULL, 16, '1°A', 13, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(83, 31, 'Ray Allen Jr.', 'Escolta', NULL, 16, '1°A', 34, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(84, 31, 'Dirk Nowitzki II', 'Ala-Pívot', NULL, 17, '2°B', 41, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(85, 31, 'Kyrie Irving Jr.', 'Base', NULL, 16, '1°A', 11, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(86, 31, 'Damian Lillard Jr.', 'Base', NULL, 17, '2°B', 0, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(87, 32, 'Russell Westbrook Jr.', 'Base', NULL, 17, '2°B', 0, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(88, 32, 'Anthony Davis Jr.', 'Ala-Pívot', NULL, 16, '1°A', 3, 0, 0, 1, 0, '2025-10-27 01:07:17'),
(89, 32, 'Kawhi Leonard Jr.', 'Alero', NULL, 17, '2°B', 2, 0, 0, 1, 0, '2025-10-27 01:07:17'),
(90, 32, 'Giannis Antetokounmpo Jr.', 'Ala-Pívot', NULL, 16, '1°A', 34, 0, 0, 0, 0, '2025-10-27 01:07:17'),
(97, 34, 'Sofía Ruiz', 'Colocadora', NULL, 17, '1°B', 3, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(98, 34, 'Lucía Moreno', 'Opuesta', NULL, 16, '1°A', 15, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(99, 34, 'Paula JimÚnez', 'Central', NULL, 17, '2°B', 13, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(100, 34, 'Andrea Álvarez', 'Receptora', NULL, 16, '1°A', 6, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(101, 34, 'Cristina Romero', 'Líbero', NULL, 17, '2°B', 2, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(102, 34, 'Marta Navarro', 'Central', NULL, 16, '1°A', 14, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(103, 35, 'Natalia Castro', 'Colocadora', NULL, 16, '1°A', 5, 0, 0, 1, 0, '2025-10-27 01:07:38'),
(104, 35, 'Verónica Ortiz', 'Opuesta', NULL, 17, '2°B', 18, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(105, 35, 'Julia Rubio', 'Central', NULL, 16, '1°A', 17, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(106, 35, 'Alicia Sanz', 'Receptora', NULL, 17, '2°B', 9, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(107, 35, 'Rosa Iglesias', 'Líbero', NULL, 16, '1°A', 1, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(108, 35, 'Pilar Medrano', 'Central', NULL, 17, '2°B', 12, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(109, 36, 'Clara Santos', 'Colocadora', NULL, 17, '2°B', 4, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(110, 36, 'Irene Vargas', 'Opuesta', NULL, 16, '1°A', 20, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(111, 36, 'Eva Herrera', 'Central', NULL, 17, '2°B', 15, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(112, 36, 'Diana Medina', 'Receptora', NULL, 16, '1°A', 8, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(113, 36, 'Rocío Vega', 'Líbero', NULL, 17, '2°B', 2, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(114, 36, 'Lorena CortÚs', 'Central', NULL, 16, '1°A', 13, 0, 0, 0, 0, '2025-10-27 01:07:38'),
(115, 38, 'ELPINGPONG2', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 1, 0, '2025-10-27 23:42:58'),
(116, 37, 'ELPINGPONG', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-27 23:42:58'),
(117, 41, 'Bobby Fischer Jr.', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 00:29:33'),
(118, 42, 'Garry King', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 00:29:33'),
(119, 39, 'ELPINGPONG3', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 00:30:30'),
(120, 43, 'Daniel Loop', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 1, 0, '2025-10-28 00:30:30'),
(121, 44, 'Alejandro Spin', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 02:35:30'),
(122, 48, 'Roberto Smash', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 02:35:30'),
(123, 46, 'Francisco Drive', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 02:35:30'),
(124, 47, 'Magnus Chess', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 02:35:30'),
(125, 45, 'Anatoly Knight', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 02:35:30'),
(126, 40, 'ELPINGPONG4', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 02:35:30'),
(128, 54, 'AJEDREZ4', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 03:02:08'),
(129, 53, 'AJEDREZ3', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 03:02:08'),
(130, 52, 'AJEDREZ2', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 03:19:29'),
(131, 51, 'AJEDREZ1', 'Jugador', NULL, NULL, NULL, 1, 0, 0, 0, 0, '2025-10-28 03:19:29');

-- --------------------------------------------------------

--
-- Table structure for table `noticias`
--

CREATE TABLE `noticias` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `subtitulo` varchar(255) DEFAULT NULL COMMENT 'Subtítulo o bajada',
  `contenido` text NOT NULL COMMENT 'Contenido de la noticia con formato básico',
  `imagen_portada` varchar(255) DEFAULT NULL COMMENT 'URL de la imagen principal',
  `autor` varchar(100) DEFAULT 'Redacción' COMMENT 'Nombre del autor',
  `deporte_id` tinyint(4) DEFAULT NULL COMMENT 'Deporte relacionado (opcional)',
  `temporada_id` int(11) DEFAULT NULL COMMENT 'Temporada relacionada (opcional)',
  `etiquetas` varchar(255) DEFAULT NULL COMMENT 'Etiquetas separadas por comas',
  `destacada` tinyint(1) DEFAULT 0 COMMENT 'Si es noticia destacada (slider)',
  `publicada` tinyint(1) DEFAULT 1 COMMENT 'Si está publicada o en borrador',
  `fecha_publicacion` datetime DEFAULT current_timestamp() COMMENT 'Fecha de publicación',
  `fecha_modificacion` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `visitas` int(11) DEFAULT 0 COMMENT 'Contador de visitas',
  `orden` int(11) DEFAULT 0 COMMENT 'Orden de visualización (menor primero)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `noticias`
--

INSERT INTO `noticias` (`id`, `titulo`, `subtitulo`, `contenido`, `imagen_portada`, `autor`, `deporte_id`, `temporada_id`, `etiquetas`, `destacada`, `publicada`, `fecha_publicacion`, `fecha_modificacion`, `visitas`, `orden`, `created_at`, `updated_at`) VALUES
(1, 'Inicio de la Temporada 2025', 'Los equipos se preparan para un nuevo año de competencia', 'Comienza una nueva temporada deportiva con gran entusiasmo. Los equipos de fútbol, baloncesto y voleibol han comenzado sus entrenamientos preparándose para los desafíos que vienen.\n\nEste año promete ser muy competitivo con la incorporación de nuevos talentos y la renovación de varias categorías.', NULL, 'Redacción', NULL, NULL, NULL, 1, 1, '2025-10-27 15:19:26', '2025-10-28 21:36:58', 2, 0, '2025-10-27 21:19:26', '2025-10-29 03:36:58'),
(2, 'Inauguración del Nuevo Gimnasio', 'Instalaciones renovadas para mejorar la experiencia deportiva', 'Se inauguró el gimnasio renovado con nuevas facilidades para los equipos de baloncesto y voleibol. Las mejoras incluyen pisos nuevos, marcadores electrónicos y gradas ampliadas.\n\nLa comunidad estudiantil podrá disfrutar de estas instalaciones durante toda la temporada.', NULL, 'Redacción', NULL, NULL, NULL, 0, 1, '2025-10-24 15:19:26', NULL, 0, 0, '2025-10-27 21:19:26', '2025-10-27 21:19:26'),
(3, 'LE HICIERON FALTA AL JASSOOOON', 'FALTA GRAVE', 'GGS', '../../img/noticias/69003e4935816-la falta del siglo.png', 'Redacción', NULL, NULL, '', 1, 1, '0000-00-00 00:00:00', '2025-10-28 21:37:08', 4, 0, '2025-10-28 03:53:45', '2025-10-29 03:37:08'),
(4, 'LE HICIERON FALTA AL JASSOOOON', 'FALTA GRAVISISISISISISISIMA', 'Se le va la carrera a nuestra estrella?', '../../img/noticias/69007152a8431-la falta del siglo.png', 'Redacción', NULL, NULL, '', 0, 1, '0000-00-00 00:00:00', '2025-10-28 21:37:12', 6, 2, '2025-10-28 07:31:30', '2025-10-29 03:37:12');

-- --------------------------------------------------------

--
-- Table structure for table `operaciones_auditoria`
--

CREATE TABLE `operaciones_auditoria` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(16) NOT NULL,
  `nombre_mostrado` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `operaciones_auditoria`
--

INSERT INTO `operaciones_auditoria` (`id`, `codigo`, `nombre_mostrado`) VALUES
(1, 'INSERT', 'Inserción'),
(2, 'UPDATE', 'Actualización'),
(3, 'DELETE', 'Eliminación'),
(4, 'RESTORE', 'Restauración');

-- --------------------------------------------------------

--
-- Table structure for table `participantes`
--

CREATE TABLE `participantes` (
  `id` int(11) NOT NULL,
  `deporte_id` tinyint(4) NOT NULL,
  `tipo_participante_id` tinyint(4) NOT NULL,
  `nombre_mostrado` varchar(120) NOT NULL,
  `nombre_corto` varchar(32) DEFAULT NULL,
  `url_logo` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `participantes`
--

INSERT INTO `participantes` (`id`, `deporte_id`, `tipo_participante_id`, `nombre_mostrado`, `nombre_corto`, `url_logo`, `creado_en`) VALUES
(1, 1, 1, 'Selecta del llort asi los meros meros', 'LLORT', '../../img/logos/68fbbeaf21cdb-logolllory.jpg', '2025-10-24 18:00:15'),
(3, 1, 1, 'Águilas FC', 'AGUI', NULL, '2025-10-26 15:07:36'),
(4, 1, 1, 'Titanes del Norte', 'TITA', NULL, '2025-10-26 15:07:36'),
(5, 1, 1, 'Leones Dorados', 'LEON', NULL, '2025-10-26 15:07:36'),
(6, 1, 1, 'Dragones Unidos', 'DRAG', NULL, '2025-10-26 15:07:36'),
(7, 1, 1, 'Halcones Rojos', 'HALC', NULL, '2025-10-26 15:07:36'),
(8, 1, 1, 'Tigres del Sur', 'TIGR', NULL, '2025-10-26 15:07:36'),
(9, 1, 1, 'Cóndores FC', 'COND', NULL, '2025-10-26 15:07:36'),
(10, 1, 1, 'Pumas Salvajes', 'PUMA', NULL, '2025-10-26 15:07:36'),
(11, 1, 1, 'Lobos Grises', 'LOBO', NULL, '2025-10-26 15:07:36'),
(12, 1, 1, 'Zorros Azules', 'ZORR', NULL, '2025-10-26 15:07:36'),
(13, 1, 1, 'Osos Pardos', 'OSOS', NULL, '2025-10-26 15:07:36'),
(15, 6, 1, 'Relámpagos FS', 'REL', NULL, '2025-10-27 01:05:46'),
(16, 6, 1, 'Rayos Azules FS', 'RAZ', NULL, '2025-10-27 01:05:46'),
(18, 6, 1, 'Cometas FS', 'COM', NULL, '2025-10-27 01:05:46'),
(19, 9, 1, 'Aces 3x3', 'ACE', NULL, '2025-10-27 01:05:46'),
(20, 9, 1, 'Dunkers 3x3', 'DUN', NULL, '2025-10-27 01:05:46'),
(21, 9, 1, 'Shooters 3x3', 'SHO', NULL, '2025-10-27 01:05:46'),
(22, 9, 1, 'Ballers 3x3', 'BAL', NULL, '2025-10-27 01:05:46'),
(24, 2, 1, 'Bloqueadores Pro', 'BLP', NULL, '2025-10-27 01:05:46'),
(25, 2, 1, 'Rematadores FC', 'REM', NULL, '2025-10-27 01:05:46'),
(26, 2, 1, 'Defensores VB', 'DEF', NULL, '2025-10-27 01:05:46'),
(27, 4, 2, 'Alejandro Spin', 'A.SPIN', NULL, '2025-10-27 01:07:41'),
(28, 4, 2, 'Roberto Smash', 'R.SMASH', NULL, '2025-10-27 01:07:41'),
(29, 4, 2, 'Daniel Loop', 'D.LOOP', NULL, '2025-10-27 01:07:41'),
(30, 4, 2, 'Francisco Drive', 'F.DRIVE', NULL, '2025-10-27 01:07:41'),
(31, 5, 2, 'Magnus Chess', 'M.CHESS', NULL, '2025-10-27 01:07:41'),
(32, 5, 2, 'Garry King', 'G.KING', NULL, '2025-10-27 01:07:41'),
(33, 5, 2, 'Bobby Fischer Jr.', 'B.FISCHER', NULL, '2025-10-27 01:07:41'),
(34, 5, 2, 'Anatoly Knight', 'A.KNIGHT', NULL, '2025-10-27 01:07:41'),
(35, 4, 2, 'ELPINGPONG', '', '', '2025-10-27 23:28:43'),
(36, 4, 2, 'ELPINGPONG2', '', '', '2025-10-27 23:28:55'),
(37, 4, 2, 'ELPINGPONG3', '', '', '2025-10-27 23:29:04'),
(38, 4, 2, 'ELPINGPONG4', '', '', '2025-10-27 23:29:12'),
(39, 5, 2, 'AJEDREZ1', '', '', '2025-10-28 03:01:04'),
(40, 5, 2, 'AJEDREZ2', '', '', '2025-10-28 03:01:14'),
(41, 5, 2, 'AJEDREZ3', '', '', '2025-10-28 03:01:22'),
(42, 5, 2, 'AJEDREZ4', '', '', '2025-10-28 03:01:34'),
(43, 5, 2, 'pruebaultima', '', '', '2025-10-29 00:05:18');

-- --------------------------------------------------------

--
-- Table structure for table `partidos`
--

CREATE TABLE `partidos` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) DEFAULT NULL COMMENT 'NULL para partidos amistosos',
  `fase_id` int(11) DEFAULT NULL COMMENT 'NULL para partidos amistosos',
  `jornada_id` int(11) DEFAULT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `participante_local_id` int(11) DEFAULT NULL,
  `participante_visitante_id` int(11) DEFAULT NULL,
  `inicio_partido` datetime NOT NULL,
  `fecha_partido` date GENERATED ALWAYS AS (cast(`inicio_partido` as date)) STORED,
  `hora_partido` time GENERATED ALWAYS AS (cast(`inicio_partido` as time)) STORED,
  `estado_id` tinyint(4) NOT NULL DEFAULT 1,
  `marcador_local` smallint(6) DEFAULT 0,
  `marcador_visitante` smallint(6) DEFAULT 0,
  `marcador_local_sets` tinyint(4) DEFAULT NULL COMMENT 'Para voleibol',
  `marcador_visitante_sets` tinyint(4) DEFAULT NULL,
  `mvp_miembro_plantel_id` int(11) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `jugador_local_id` int(11) DEFAULT NULL COMMENT 'Jugador local para deportes individuales',
  `jugador_visitante_id` int(11) DEFAULT NULL COMMENT 'Jugador visitante para deportes individuales',
  `sets_ganados_local` tinyint(4) DEFAULT 0 COMMENT 'Sets ganados por local (ping pong)',
  `sets_ganados_visitante` tinyint(4) DEFAULT 0 COMMENT 'Sets ganados por visitante (ping pong)',
  `ganador_individual_id` int(11) DEFAULT NULL COMMENT 'Ganador para deportes individuales (ajedrez, ping pong)',
  `set_actual` tinyint(4) DEFAULT 1 COMMENT 'Set actual en juego (para ping pong)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partidos`
--

INSERT INTO `partidos` (`id`, `torneo_id`, `fase_id`, `jornada_id`, `sede_id`, `participante_local_id`, `participante_visitante_id`, `inicio_partido`, `estado_id`, `marcador_local`, `marcador_visitante`, `marcador_local_sets`, `marcador_visitante_sets`, `mvp_miembro_plantel_id`, `notas`, `creado_en`, `actualizado_en`, `jugador_local_id`, `jugador_visitante_id`, `sets_ganados_local`, `sets_ganados_visitante`, `ganador_individual_id`, `set_actual`) VALUES
(137, NULL, NULL, NULL, NULL, 3, 4, '2025-10-10 16:21:00', 3, 1, 1, NULL, NULL, 26, '', '2025-10-29 15:20:04', '2025-10-29 22:21:41', NULL, NULL, 0, 0, NULL, 1),
(140, 12, 27, 33, NULL, 1, 13, '2025-10-29 15:00:00', 5, 1, 0, NULL, NULL, NULL, '', '2025-10-29 23:01:21', '2025-10-29 23:02:15', NULL, NULL, 0, 0, NULL, 1),
(141, 12, 27, 33, NULL, 3, 12, '2025-10-29 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(142, 12, 27, 33, NULL, 4, 11, '2025-10-29 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(143, 12, 27, 33, NULL, 5, 10, '2025-10-29 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(144, 12, 27, 33, NULL, 6, 9, '2025-10-29 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(145, 12, 27, 33, NULL, 7, 8, '2025-10-29 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(146, 12, 27, 34, NULL, 1, 12, '2025-11-18 06:00:00', 1, 0, 0, NULL, NULL, NULL, '', '2025-10-29 23:01:21', '2025-10-29 23:14:47', NULL, NULL, 0, 0, NULL, 1),
(147, 12, 27, 34, NULL, 13, 11, '2025-11-05 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(148, 12, 27, 34, NULL, 3, 10, '2025-11-05 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(149, 12, 27, 34, NULL, 4, 9, '2025-11-05 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(150, 12, 27, 34, NULL, 5, 8, '2025-11-05 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(151, 12, 27, 34, NULL, 6, 7, '2025-11-05 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(152, 12, 27, 35, NULL, 1, 11, '2025-11-12 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(153, 12, 27, 35, NULL, 12, 10, '2025-11-12 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(154, 12, 27, 35, NULL, 13, 9, '2025-11-12 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(155, 12, 27, 35, NULL, 3, 8, '2025-11-12 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(156, 12, 27, 35, NULL, 4, 7, '2025-11-12 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(157, 12, 27, 35, NULL, 5, 6, '2025-11-12 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(158, 12, 27, 36, NULL, 1, 10, '2025-11-19 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(159, 12, 27, 36, NULL, 11, 9, '2025-11-19 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(160, 12, 27, 36, NULL, 12, 8, '2025-11-19 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(161, 12, 27, 36, NULL, 13, 7, '2025-11-19 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(162, 12, 27, 36, NULL, 3, 6, '2025-11-19 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(163, 12, 27, 36, NULL, 4, 5, '2025-11-19 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(164, 12, 27, 37, NULL, 1, 9, '2025-11-26 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(165, 12, 27, 37, NULL, 10, 8, '2025-11-26 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(166, 12, 27, 37, NULL, 11, 7, '2025-11-26 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(167, 12, 27, 37, NULL, 12, 6, '2025-11-26 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(168, 12, 27, 37, NULL, 13, 5, '2025-11-26 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(169, 12, 27, 37, NULL, 3, 4, '2025-11-26 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(170, 12, 27, 38, NULL, 1, 8, '2025-12-03 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(171, 12, 27, 38, NULL, 9, 7, '2025-12-03 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(172, 12, 27, 38, NULL, 10, 6, '2025-12-03 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(173, 12, 27, 38, NULL, 11, 5, '2025-12-03 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(174, 12, 27, 38, NULL, 12, 4, '2025-12-03 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(175, 12, 27, 38, NULL, 13, 3, '2025-12-03 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(176, 12, 27, 39, NULL, 1, 7, '2025-12-10 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(177, 12, 27, 39, NULL, 8, 6, '2025-12-10 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(178, 12, 27, 39, NULL, 9, 5, '2025-12-10 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(179, 12, 27, 39, NULL, 10, 4, '2025-12-10 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(180, 12, 27, 39, NULL, 11, 3, '2025-12-10 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(181, 12, 27, 39, NULL, 12, 13, '2025-12-10 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(182, 12, 27, 40, NULL, 1, 6, '2025-12-17 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(183, 12, 27, 40, NULL, 7, 5, '2025-12-17 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(184, 12, 27, 40, NULL, 8, 4, '2025-12-17 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(185, 12, 27, 40, NULL, 9, 3, '2025-12-17 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(186, 12, 27, 40, NULL, 10, 13, '2025-12-17 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(187, 12, 27, 40, NULL, 11, 12, '2025-12-17 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(188, 12, 27, 41, NULL, 1, 5, '2025-12-24 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(189, 12, 27, 41, NULL, 6, 4, '2025-12-24 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(190, 12, 27, 41, NULL, 7, 3, '2025-12-24 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(191, 12, 27, 41, NULL, 8, 13, '2025-12-24 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(192, 12, 27, 41, NULL, 9, 12, '2025-12-24 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(193, 12, 27, 41, NULL, 10, 11, '2025-12-24 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(194, 12, 27, 42, NULL, 1, 4, '2025-12-31 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(195, 12, 27, 42, NULL, 5, 3, '2025-12-31 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(196, 12, 27, 42, NULL, 6, 13, '2025-12-31 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(197, 12, 27, 42, NULL, 7, 12, '2025-12-31 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(198, 12, 27, 42, NULL, 8, 11, '2025-12-31 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(199, 12, 27, 42, NULL, 9, 10, '2025-12-31 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(200, 12, 27, 43, NULL, 1, 3, '2026-01-07 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(201, 12, 27, 43, NULL, 4, 13, '2026-01-07 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(202, 12, 27, 43, NULL, 5, 12, '2026-01-07 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(203, 12, 27, 43, NULL, 6, 11, '2026-01-07 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(204, 12, 27, 43, NULL, 7, 10, '2026-01-07 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(205, 12, 27, 43, NULL, 8, 9, '2026-01-07 15:00:00', 1, 0, 0, NULL, NULL, NULL, NULL, '2025-10-29 23:01:21', '2025-10-29 23:01:21', NULL, NULL, 0, 0, NULL, 1),
(206, 13, 28, NULL, NULL, 42, 40, '2025-10-30 17:12:00', 5, 1, 0, NULL, NULL, NULL, '', '2025-10-29 23:12:39', '2025-10-29 23:13:00', NULL, NULL, 0, 0, NULL, 1),
(207, 13, 28, NULL, NULL, 39, 41, '2025-10-31 17:12:00', 5, 0, 1, NULL, NULL, NULL, '', '2025-10-29 23:12:39', '2025-10-29 23:13:12', NULL, NULL, 0, 0, NULL, 1),
(208, 13, 29, NULL, NULL, 42, 41, '2025-11-12 17:12:00', 5, 1, 0, NULL, NULL, NULL, '', '2025-10-29 23:12:39', '2025-10-29 23:13:43', NULL, NULL, 0, 0, NULL, 1);

--
-- Triggers `partidos`
--
DELIMITER $$
CREATE TRIGGER `trg_finalizar_torneo_al_completar_final` AFTER UPDATE ON `partidos` FOR EACH ROW BEGIN
    
    IF (NEW.estado_id IN (5, 7)) AND (OLD.estado_id NOT IN (5, 7)) THEN
        
        IF EXISTS (
            SELECT 1 FROM fases
            WHERE id = NEW.fase_id
            AND tipo_fase_id = 4
        ) THEN
            
            UPDATE torneos
            SET estado_id = 5
            WHERE id = NEW.torneo_id;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_partido_delete_mvp` BEFORE DELETE ON `partidos` FOR EACH ROW BEGIN
    
    IF OLD.mvp_miembro_plantel_id IS NOT NULL THEN
        UPDATE miembros_plantel 
        SET mvps = GREATEST(mvps - 1, 0) 
        WHERE id = OLD.mvp_miembro_plantel_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_partido_update_mvp` BEFORE UPDATE ON `partidos` FOR EACH ROW BEGIN
    
    IF OLD.mvp_miembro_plantel_id <> NEW.mvp_miembro_plantel_id OR 
       (OLD.mvp_miembro_plantel_id IS NULL AND NEW.mvp_miembro_plantel_id IS NOT NULL) OR
       (OLD.mvp_miembro_plantel_id IS NOT NULL AND NEW.mvp_miembro_plantel_id IS NULL) THEN
        
        
        IF OLD.mvp_miembro_plantel_id IS NOT NULL THEN
            UPDATE miembros_plantel 
            SET mvps = GREATEST(mvps - 1, 0) 
            WHERE id = OLD.mvp_miembro_plantel_id;
        END IF;
        
        
        IF NEW.mvp_miembro_plantel_id IS NOT NULL THEN
            UPDATE miembros_plantel 
            SET mvps = mvps + 1 
            WHERE id = NEW.mvp_miembro_plantel_id;
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `partidos_seleccion`
--

CREATE TABLE `partidos_seleccion` (
  `id` int(11) NOT NULL,
  `deporte_id` tinyint(4) NOT NULL,
  `oponente` varchar(120) NOT NULL,
  `fecha_partido` date NOT NULL,
  `hora_partido` time NOT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `marcador_nuestro` smallint(6) DEFAULT NULL COMMENT 'NULL si no se ha jugado',
  `marcador_oponente` smallint(6) DEFAULT NULL COMMENT 'NULL si no se ha jugado',
  `estado_id` tinyint(4) NOT NULL DEFAULT 1,
  `nombre_competicion` varchar(120) DEFAULT NULL COMMENT 'Copa Oro, Clasificatorias, etc',
  `es_local` tinyint(1) DEFAULT 1,
  `notas` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `planteles_equipo`
--

CREATE TABLE `planteles_equipo` (
  `id` int(11) NOT NULL,
  `participante_id` int(11) NOT NULL COMMENT 'ID del equipo en la tabla participantes',
  `nombre_plantel` varchar(64) NOT NULL COMMENT 'Ej: Plantel 2025, Equipo A',
  `esta_activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `planteles_equipo`
--

INSERT INTO `planteles_equipo` (`id`, `participante_id`, `nombre_plantel`, `esta_activo`, `creado_en`) VALUES
(1, 1, 'Plantel Principal', 1, '2025-10-24 18:00:15'),
(14, 3, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(15, 4, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(16, 5, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(17, 6, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(18, 7, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(19, 8, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(20, 9, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(21, 10, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(22, 11, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(23, 12, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(24, 13, 'Plantel Principal', 1, '2025-10-26 15:21:39'),
(25, 15, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(26, 16, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(28, 18, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(29, 19, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(30, 20, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(31, 21, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(32, 22, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(34, 24, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(35, 25, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(36, 26, 'Plantel 2025', 1, '2025-10-27 01:06:42'),
(37, 35, 'Plantel Principal', 1, '2025-10-27 23:28:43'),
(38, 36, 'Plantel Principal', 1, '2025-10-27 23:28:55'),
(39, 37, 'Plantel Principal', 1, '2025-10-27 23:29:04'),
(40, 38, 'Plantel Principal', 1, '2025-10-27 23:29:12'),
(41, 33, '', 1, '2025-10-28 00:29:33'),
(42, 32, '', 1, '2025-10-28 00:29:33'),
(43, 29, '', 1, '2025-10-28 00:30:30'),
(44, 27, 'Plantel Alejandro Spin', 1, '2025-10-28 02:35:07'),
(45, 34, 'Plantel Anatoly Knight', 1, '2025-10-28 02:35:07'),
(46, 30, 'Plantel Francisco Drive', 1, '2025-10-28 02:35:07'),
(47, 31, 'Plantel Magnus Chess', 1, '2025-10-28 02:35:07'),
(48, 28, 'Plantel Roberto Smash', 1, '2025-10-28 02:35:07'),
(51, 39, 'Plantel Principal', 1, '2025-10-28 03:01:04'),
(52, 40, 'Plantel Principal', 1, '2025-10-28 03:01:14'),
(53, 41, 'Plantel Principal', 1, '2025-10-28 03:01:22'),
(54, 42, 'Plantel Principal', 1, '2025-10-28 03:01:34'),
(55, 43, 'Plantel Principal', 1, '2025-10-29 00:05:18');

-- --------------------------------------------------------

--
-- Table structure for table `posiciones_bracket`
--

CREATE TABLE `posiciones_bracket` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(16) NOT NULL,
  `nombre_mostrado` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posiciones_bracket`
--

INSERT INTO `posiciones_bracket` (`id`, `codigo`, `nombre_mostrado`) VALUES
(1, 'home', 'Local'),
(2, 'away', 'Visitante');

-- --------------------------------------------------------

--
-- Table structure for table `posiciones_deporte`
--

CREATE TABLE `posiciones_deporte` (
  `id` int(11) NOT NULL,
  `deporte_id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `coordenada_x` decimal(5,2) DEFAULT NULL COMMENT 'Posición X en campo (porcentaje 0-100)',
  `coordenada_y` decimal(5,2) DEFAULT NULL COMMENT 'Posición Y en campo (porcentaje 0-100)',
  `orden_visualizacion` tinyint(4) DEFAULT 0,
  `es_titular` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posiciones_deporte`
--

INSERT INTO `posiciones_deporte` (`id`, `deporte_id`, `codigo`, `nombre_mostrado`, `descripcion`, `coordenada_x`, `coordenada_y`, `orden_visualizacion`, `es_titular`, `creado_en`) VALUES
(1, 1, 'GK', 'Portero', 'Guardameta', 10.00, 50.00, 1, 1, '2025-10-25 02:56:51'),
(2, 1, 'CB', 'Defensa Central', 'Defensa central', 25.00, 50.00, 2, 1, '2025-10-25 02:56:51'),
(3, 1, 'LB', 'Lateral Izquierdo', 'Defensa lateral izquierdo', 25.00, 20.00, 3, 1, '2025-10-25 02:56:51'),
(4, 1, 'RB', 'Lateral Derecho', 'Defensa lateral derecho', 25.00, 80.00, 4, 1, '2025-10-25 02:56:51'),
(5, 1, 'CDM', 'Mediocampista Defensivo', 'Mediocampista defensivo', 45.00, 50.00, 5, 1, '2025-10-25 02:56:51'),
(6, 1, 'CM', 'Mediocampista Central', 'Mediocampista central', 60.00, 50.00, 6, 1, '2025-10-25 02:56:51'),
(7, 1, 'LM', 'Mediocampista Izquierdo', 'Mediocampista por izquierda', 60.00, 25.00, 7, 1, '2025-10-25 02:56:51'),
(8, 1, 'RM', 'Mediocampista Derecho', 'Mediocampista por derecha', 60.00, 75.00, 8, 1, '2025-10-25 02:56:51'),
(9, 1, 'CAM', 'Mediocampista Ofensivo', 'Mediocampista ofensivo', 75.00, 50.00, 9, 1, '2025-10-25 02:56:51'),
(10, 1, 'LW', 'Extremo Izquierdo', 'Delantero por izquierda', 85.00, 25.00, 10, 1, '2025-10-25 02:56:51'),
(11, 1, 'RW', 'Extremo Derecho', 'Delantero por derecha', 85.00, 75.00, 11, 1, '2025-10-25 02:56:51'),
(12, 1, 'ST', 'Delantero Centro', 'Delantero centro', 90.00, 50.00, 12, 1, '2025-10-25 02:56:51'),
(13, 1, 'SUB', 'Suplente', 'Jugador suplente', NULL, NULL, 13, 0, '2025-10-25 02:56:51'),
(14, 3, 'PG', 'Base', 'Point Guard - Base', 30.00, 50.00, 1, 1, '2025-10-25 02:56:51'),
(15, 3, 'SG', 'Escolta', 'Shooting Guard - Escolta', 50.00, 30.00, 2, 1, '2025-10-25 02:56:51'),
(16, 3, 'SF', 'Alero', 'Small Forward - Alero', 70.00, 50.00, 3, 1, '2025-10-25 02:56:51'),
(17, 3, 'PF', 'Ala-Pivot', 'Power Forward - Ala-Pivot', 50.00, 70.00, 4, 1, '2025-10-25 02:56:51'),
(18, 3, 'C', 'Pivot', 'Center - Pivot', 80.00, 50.00, 5, 1, '2025-10-25 02:56:51'),
(19, 3, 'SUB', 'Suplente', 'Jugador suplente', NULL, NULL, 6, 0, '2025-10-25 02:56:51'),
(20, 2, 'S', 'Colocador', 'Setter - Colocador', 40.00, 50.00, 1, 1, '2025-10-25 02:56:51'),
(21, 2, 'OH1', 'Opuesto', 'Outside Hitter - Atacante opuesto', 60.00, 20.00, 2, 1, '2025-10-25 02:56:51'),
(22, 2, 'OH2', 'Atacante Exterior', 'Outside Hitter - Atacante exterior', 60.00, 80.00, 3, 1, '2025-10-25 02:56:51'),
(23, 2, 'MB1', 'Bloqueador Central 1', 'Middle Blocker - Bloqueador central', 70.00, 40.00, 4, 1, '2025-10-25 02:56:51'),
(24, 2, 'MB2', 'Bloqueador Central 2', 'Middle Blocker - Bloqueador central', 70.00, 60.00, 5, 1, '2025-10-25 02:56:51'),
(25, 2, 'L', 'Líbero', 'Libero - Líbero', 30.00, 50.00, 6, 1, '2025-10-25 02:56:51'),
(26, 2, 'SUB', 'Suplente', 'Jugador suplente', NULL, NULL, 7, 0, '2025-10-25 02:56:51');

-- --------------------------------------------------------

--
-- Table structure for table `puntos_set`
--

CREATE TABLE `puntos_set` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL COMMENT 'ID del partido',
  `set_numero` tinyint(4) NOT NULL COMMENT 'Número del set (1-5)',
  `jugador_local_id` int(11) DEFAULT NULL COMMENT 'Jugador local (miembro plantel)',
  `jugador_visitante_id` int(11) DEFAULT NULL COMMENT 'Jugador visitante (miembro plantel)',
  `puntos_local` tinyint(4) DEFAULT 0 COMMENT 'Puntos del jugador local',
  `puntos_visitante` tinyint(4) DEFAULT 0 COMMENT 'Puntos del jugador visitante',
  `ganador_id` int(11) DEFAULT NULL COMMENT 'Ganador del set',
  `finalizado` tinyint(1) DEFAULT 0 COMMENT 'Si el set finalizó',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reglas_puntuacion_deporte`
--

CREATE TABLE `reglas_puntuacion_deporte` (
  `id` int(11) NOT NULL,
  `deporte_id` tinyint(4) NOT NULL,
  `puntos_victoria` tinyint(4) NOT NULL DEFAULT 3,
  `puntos_empate` tinyint(4) NOT NULL DEFAULT 1,
  `puntos_derrota` tinyint(4) NOT NULL DEFAULT 0,
  `usa_goles` tinyint(1) DEFAULT 0,
  `usa_sets` tinyint(1) DEFAULT 0,
  `usa_puntos` tinyint(1) DEFAULT 0,
  `prioridad_desempate` varchar(255) DEFAULT NULL COMMENT 'JSON: orden de desempate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reglas_puntuacion_deporte`
--

INSERT INTO `reglas_puntuacion_deporte` (`id`, `deporte_id`, `puntos_victoria`, `puntos_empate`, `puntos_derrota`, `usa_goles`, `usa_sets`, `usa_puntos`, `prioridad_desempate`) VALUES
(1, 1, 3, 1, 0, 1, 0, 0, '[\"points\",\"goal_difference\",\"goals_for\",\"head_to_head\"]'),
(2, 2, 3, 0, 0, 0, 1, 1, '[\"points\",\"set_difference\",\"sets_for\",\"head_to_head\"]'),
(3, 3, 2, 0, 0, 0, 0, 1, '[\"points\",\"point_difference\",\"points_for\",\"head_to_head\"]'),
(4, 4, 1, 0, 0, 0, 1, 1, '[\"wins\",\"set_difference\",\"head_to_head\"]'),
(5, 5, 1, 0, 0, 0, 0, 0, '[\"wins\",\"head_to_head\",\"performance_rating\"]');

-- --------------------------------------------------------

--
-- Table structure for table `resultados_periodo_partido`
--

CREATE TABLE `resultados_periodo_partido` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `numero_periodo` tinyint(4) NOT NULL,
  `puntos_local` tinyint(4) NOT NULL DEFAULT 0,
  `puntos_visitante` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resultados_set_partido`
--

CREATE TABLE `resultados_set_partido` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `numero_set` tinyint(4) NOT NULL,
  `puntos_local` tinyint(4) NOT NULL DEFAULT 0,
  `puntos_visitante` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `codigo`, `nombre_mostrado`, `descripcion`, `creado_en`) VALUES
(1, 'admin', 'Administrador', 'Acceso completo al sistema', '2025-10-24 01:17:52'),
(2, 'user', 'Usuario', 'Acceso de solo lectura', '2025-10-24 01:17:52'),
(3, 'moderator', 'Moderador', 'Puede gestionar contenido pero no usuarios', '2025-10-24 01:17:52'),
(4, 'coordinator', 'Coordinador Deportivo', 'Gestiona torneos y partidos de su deporte', '2025-10-24 01:17:52');

-- --------------------------------------------------------

--
-- Table structure for table `sedes`
--

CREATE TABLE `sedes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `ubicacion` varchar(160) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `capacidad` smallint(6) DEFAULT NULL,
  `zona_horaria` varchar(40) DEFAULT NULL COMMENT 'Ej: America/El_Salvador para conversión horaria',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabla_posiciones`
--

CREATE TABLE `tabla_posiciones` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `participante_id` int(11) NOT NULL,
  `fase_id` int(11) DEFAULT NULL COMMENT 'Posiciones por fase si aplica',
  `grupo_id` int(11) DEFAULT NULL COMMENT 'Posiciones por grupo si aplica',
  `clave_fase` int(11) GENERATED ALWAYS AS (coalesce(`fase_id`,0)) STORED,
  `clave_grupo` int(11) GENERATED ALWAYS AS (coalesce(`grupo_id`,0)) STORED,
  `jugados` smallint(6) DEFAULT 0,
  `ganados` smallint(6) DEFAULT 0,
  `empatados` smallint(6) DEFAULT 0,
  `perdidos` smallint(6) DEFAULT 0,
  `goles_favor` smallint(6) DEFAULT NULL COMMENT 'Para fútbol',
  `goles_contra` smallint(6) DEFAULT NULL,
  `diferencia_goles` smallint(6) DEFAULT NULL,
  `sets_favor` smallint(6) DEFAULT NULL COMMENT 'Para voleibol',
  `sets_contra` smallint(6) DEFAULT NULL,
  `diferencia_sets` smallint(6) DEFAULT NULL,
  `puntos_favor` smallint(6) DEFAULT NULL COMMENT 'Para baloncesto',
  `puntos_contra` smallint(6) DEFAULT NULL,
  `diferencia_puntos` smallint(6) DEFAULT NULL,
  `puntos` smallint(6) DEFAULT 0 COMMENT 'Puntos de tabla',
  `posicion` tinyint(4) DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temporadas`
--

CREATE TABLE `temporadas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(64) NOT NULL,
  `ano` year(4) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `es_actual` tinyint(1) DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `temporadas`
--

INSERT INTO `temporadas` (`id`, `nombre`, `ano`, `fecha_inicio`, `fecha_fin`, `es_actual`, `creado_en`) VALUES
(1, 'Temporada 2024', '2024', '2024-01-01', '2024-12-31', 0, '2025-10-27 04:45:20'),
(2, 'Temporada 2025', '2025', '2025-01-01', '2025-12-31', 1, '2025-10-27 04:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `tipos_condicion_bracket`
--

CREATE TABLE `tipos_condicion_bracket` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(16) NOT NULL,
  `nombre_mostrado` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tipos_condicion_bracket`
--

INSERT INTO `tipos_condicion_bracket` (`id`, `codigo`, `nombre_mostrado`) VALUES
(1, 'winner', 'Ganador'),
(2, 'loser', 'Perdedor'),
(3, 'specific', 'Específico');

-- --------------------------------------------------------

--
-- Table structure for table `tipos_evento_calendario`
--

CREATE TABLE `tipos_evento_calendario` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(48) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `icono` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tipos_evento_calendario`
--

INSERT INTO `tipos_evento_calendario` (`id`, `codigo`, `nombre_mostrado`, `descripcion`, `icono`) VALUES
(1, 'tournament_start', 'Inicio de Torneo', 'Fecha de inicio de un torneo', 'trophy'),
(2, 'tournament_round', 'Jornada de Torneo', 'Fecha de una jornada/ronda', 'calendar'),
(3, 'tournament_match', 'Partido de Torneo', 'Partido individual de torneo', 'whistle'),
(4, 'selection_match', 'Partido de Selección', 'Partido de la selección nacional', 'flag'),
(5, 'tournament_final', 'Final de Torneo', 'Partido final de un torneo', 'award'),
(6, 'tournament_end', 'Cierre de Torneo', 'Fecha de finalización de torneo', 'check-circle');

-- --------------------------------------------------------

--
-- Table structure for table `tipos_fase`
--

CREATE TABLE `tipos_fase` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `permite_empates` tinyint(1) DEFAULT 0,
  `orden` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tipos_fase`
--

INSERT INTO `tipos_fase` (`id`, `codigo`, `nombre_mostrado`, `descripcion`, `permite_empates`, `orden`) VALUES
(1, 'league', 'Liga/Jornadas', 'Fase de liga con sistema round-robin', 1, 1),
(2, 'group', 'Fase de Grupos', 'Fase de grupos previa a eliminación', 1, 2),
(3, 'round_32', 'Dieciseisavos', 'Ronda de 32 equipos', 0, 3),
(4, 'round_16', 'Octavos de Final', 'Ronda de 16 equipos', 0, 4),
(5, 'quarterfinal', 'Cuartos de Final', 'Fase de cuartos de final', 0, 5),
(6, 'semifinal', 'Semifinal', 'Fase semifinal', 0, 6),
(7, 'third_place', 'Tercer Lugar', 'Partido por el tercer lugar', 0, 7),
(8, 'final', 'Final', 'Partido final del torneo', 0, 8);

-- --------------------------------------------------------

--
-- Table structure for table `tipos_participante`
--

CREATE TABLE `tipos_participante` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tipos_participante`
--

INSERT INTO `tipos_participante` (`id`, `codigo`, `nombre_mostrado`, `descripcion`) VALUES
(1, 'team', 'Equipo', 'Participante tipo equipo'),
(2, 'individual', 'Individual', 'Participante tipo jugador individual');

-- --------------------------------------------------------

--
-- Table structure for table `torneos`
--

CREATE TABLE `torneos` (
  `id` int(11) NOT NULL,
  `deporte_id` tinyint(4) NOT NULL,
  `temporada_id` int(11) DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `ida_y_vuelta` tinyint(1) DEFAULT 0 COMMENT 'Si tiene ida y vuelta',
  `tipo_torneo` enum('liga','bracket') DEFAULT 'liga' COMMENT 'Tipo de torneo: liga (round-robin) o bracket (eliminatoria)',
  `fase_actual` enum('liga','cuartos','semis','final') DEFAULT 'liga' COMMENT 'Fase actual del torneo',
  `estado_id` tinyint(4) NOT NULL DEFAULT 1,
  `max_participantes` smallint(6) NOT NULL,
  `creado_por` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `mvp_torneo_miembro_id` int(11) DEFAULT NULL COMMENT 'MVP del torneo completo',
  `goleador_torneo_miembro_id` int(11) DEFAULT NULL COMMENT 'Goleador del torneo completo',
  `goles_goleador` int(11) DEFAULT 0 COMMENT 'Total de goles del goleador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `torneos`
--

INSERT INTO `torneos` (`id`, `deporte_id`, `temporada_id`, `nombre`, `descripcion`, `fecha_inicio`, `fecha_fin`, `ida_y_vuelta`, `tipo_torneo`, `fase_actual`, `estado_id`, `max_participantes`, `creado_por`, `creado_en`, `actualizado_en`, `mvp_torneo_miembro_id`, `goleador_torneo_miembro_id`, `goles_goleador`) VALUES
(12, 1, 2, 'Copa Salesianda', 'EL TORNEO PRINCIPAL', '2025-10-29', '2025-12-31', 0, 'liga', 'liga', 3, 12, 1, '2025-10-29 23:00:57', '2025-10-29 23:02:44', NULL, NULL, 0),
(13, 5, 2, 'Prueba de torneo ya finalizado', 'ojala', '2025-10-29', '2025-10-29', 0, 'bracket', 'semis', 5, 4, 1, '2025-10-29 23:12:14', '2025-10-29 23:14:03', 130, 128, 0);

-- --------------------------------------------------------

--
-- Table structure for table `torneo_grupos`
--

CREATE TABLE `torneo_grupos` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `nombre` varchar(16) NOT NULL COMMENT 'Grupo A, B, C, etc',
  `orden` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `torneo_participantes`
--

CREATE TABLE `torneo_participantes` (
  `torneo_id` int(11) NOT NULL,
  `participante_id` int(11) NOT NULL,
  `semilla` smallint(6) DEFAULT NULL COMMENT 'Orden de clasificación/sorteo',
  `inscrito_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `torneo_participantes`
--

INSERT INTO `torneo_participantes` (`torneo_id`, `participante_id`, `semilla`, `inscrito_en`) VALUES
(12, 1, NULL, '2025-10-29 23:01:02'),
(12, 3, NULL, '2025-10-29 23:01:00'),
(12, 4, NULL, '2025-10-29 23:01:14'),
(12, 5, NULL, '2025-10-29 23:01:07'),
(12, 6, NULL, '2025-10-29 23:01:05'),
(12, 7, NULL, '2025-10-29 23:01:06'),
(12, 8, NULL, '2025-10-29 23:01:12'),
(12, 9, NULL, '2025-10-29 23:01:03'),
(12, 10, NULL, '2025-10-29 23:01:11'),
(12, 11, NULL, '2025-10-29 23:01:08'),
(12, 12, NULL, '2025-10-29 23:01:16'),
(12, 13, NULL, '2025-10-29 23:01:10'),
(13, 39, NULL, '2025-10-29 23:12:24'),
(13, 40, NULL, '2025-10-29 23:12:26'),
(13, 41, NULL, '2025-10-29 23:12:27'),
(13, 42, NULL, '2025-10-29 23:12:29');

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `hash_contrasena` varchar(255) NOT NULL,
  `estado_id` tinyint(4) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ultimo_inicio_sesion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `hash_contrasena`, `estado_id`, `creado_en`, `actualizado_en`, `ultimo_inicio_sesion`) VALUES
(1, 'Administrador del Sistema', 'admin@gmail.com', '$2y$10$O1yKxQGw.jbAjehH67CTJeB7HzJrktQ77R7A9o18r3vhUCTJ2Q4am', 1, '2025-10-24 01:17:52', '2025-10-24 17:28:49', NULL),
(2, 'Anthony', 'anthony@gmail.com', '$2y$10$BU1Jzv5hAqXCzO4egg061eeVOJw6NXKVUb7ra2YHAwijoRSVyUeBa', 1, '2025-10-28 04:12:04', '2025-10-28 04:12:04', NULL),
(6, 'otro nombre', 'extra@gmail.com', '$2y$10$GI45.AXdjTltY6xC5qqUcuoreIr6oc1pL3Kf6rMv/Wr5m9hwRpisy', 1, '2025-10-28 04:21:35', '2025-10-28 04:21:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `usuario_roles`
--

CREATE TABLE `usuario_roles` (
  `usuario_id` int(11) NOT NULL,
  `rol_id` tinyint(4) NOT NULL,
  `asignado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `asignado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usuario_roles`
--

INSERT INTO `usuario_roles` (`usuario_id`, `rol_id`, `asignado_en`, `asignado_por`) VALUES
(1, 1, '2025-10-24 01:17:52', NULL),
(2, 2, '2025-10-28 04:12:04', NULL),
(6, 2, '2025-10-28 04:21:35', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bracket_torneos`
--
ALTER TABLE `bracket_torneos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_bracket_posicion` (`torneo_id`,`fase`,`posicion_bracket`),
  ADD KEY `fk_bracket_torneo` (`torneo_id`),
  ADD KEY `fk_bracket_participante` (`participante_id`),
  ADD KEY `fk_bracket_partido` (`ganador_de_partido_id`);

--
-- Indexes for table `configuracion_galeria`
--
ALTER TABLE `configuracion_galeria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_config_clave` (`clave`);

--
-- Indexes for table `configuracion_noticias`
--
ALTER TABLE `configuracion_noticias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indexes for table `cronometro_partido`
--
ALTER TABLE `cronometro_partido`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_partido_cronometro` (`partido_id`);

--
-- Indexes for table `deportes`
--
ALTER TABLE `deportes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `enlaces_bracket`
--
ALTER TABLE `enlaces_bracket`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_bracket_posicion` (`partido_destino_id`,`posicion_destino_id`),
  ADD KEY `fk_bracket_posicion` (`posicion_destino_id`),
  ADD KEY `fk_bracket_condicion` (`tipo_condicion_id`),
  ADD KEY `idx_bracket_origen` (`partido_origen_id`),
  ADD KEY `idx_bracket_destino` (`partido_destino_id`);

--
-- Indexes for table `estados_partido`
--
ALTER TABLE `estados_partido`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `estados_torneo`
--
ALTER TABLE `estados_torneo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `estados_usuario`
--
ALTER TABLE `estados_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `eventos_partido`
--
ALTER TABLE `eventos_partido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_evento_partido` (`partido_id`),
  ADD KEY `fk_evento_jugador` (`miembro_plantel_id`),
  ADD KEY `asistencia_miembro_plantel_id` (`asistencia_miembro_plantel_id`);

--
-- Indexes for table `fases`
--
ALTER TABLE `fases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fases_grupo` (`grupo_id`),
  ADD KEY `idx_fases_torneo` (`torneo_id`),
  ADD KEY `idx_fases_tipo` (`tipo_fase_id`),
  ADD KEY `idx_fases_orden` (`torneo_id`,`orden_fase`);

--
-- Indexes for table `galeria_temporadas`
--
ALTER TABLE `galeria_temporadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_galeria_temporada` (`temporada_id`),
  ADD KEY `fk_galeria_deporte` (`deporte_id`),
  ADD KEY `idx_galeria_activa` (`esta_activa`,`orden`);

--
-- Indexes for table `jornadas`
--
ALTER TABLE `jornadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jornadas_fase` (`fase_id`),
  ADD KEY `idx_jornadas_fecha` (`fecha_jornada`),
  ADD KEY `idx_jornadas_numero` (`fase_id`,`numero_jornada`);

--
-- Indexes for table `jugadores`
--
ALTER TABLE `jugadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_jugadores_participante` (`participante_id`),
  ADD KEY `fk_jugadores_torneo` (`torneo_id`),
  ADD KEY `fk_jugadores_deporte` (`deporte_id`),
  ADD KEY `fk_jugadores_posicion` (`posicion_id`),
  ADD KEY `idx_jugadores_equipo_numero` (`participante_id`,`numero_camiseta`);

--
-- Indexes for table `jugadores_destacados`
--
ALTER TABLE `jugadores_destacados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_destacados_deporte` (`deporte_id`),
  ADD KEY `fk_destacados_torneo` (`torneo_id`),
  ADD KEY `fk_destacados_miembro` (`miembro_plantel_id`),
  ADD KEY `fk_destacados_temporada` (`temporada_id`);

--
-- Indexes for table `miembros_grupo`
--
ALTER TABLE `miembros_grupo`
  ADD PRIMARY KEY (`grupo_id`,`participante_id`),
  ADD KEY `idx_miembros_grupo_participante` (`participante_id`);

--
-- Indexes for table `miembros_plantel`
--
ALTER TABLE `miembros_plantel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_miembros_plantel_plantel` (`plantel_id`);

--
-- Indexes for table `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `temporada_id` (`temporada_id`),
  ADD KEY `idx_publicada` (`publicada`),
  ADD KEY `idx_destacada` (`destacada`),
  ADD KEY `idx_fecha` (`fecha_publicacion`),
  ADD KEY `idx_deporte` (`deporte_id`);

--
-- Indexes for table `operaciones_auditoria`
--
ALTER TABLE `operaciones_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `participantes`
--
ALTER TABLE `participantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_participante_deporte_nombre` (`deporte_id`,`nombre_mostrado`),
  ADD KEY `idx_participantes_deporte` (`deporte_id`),
  ADD KEY `idx_participantes_tipo` (`tipo_participante_id`),
  ADD KEY `idx_participantes_nombre` (`nombre_mostrado`);

--
-- Indexes for table `partidos`
--
ALTER TABLE `partidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_partidos_jornada` (`fase_id`,`jornada_id`,`participante_local_id`,`participante_visitante_id`),
  ADD UNIQUE KEY `uk_partidos_tiempo` (`torneo_id`,`fecha_partido`,`participante_local_id`,`participante_visitante_id`),
  ADD KEY `idx_partidos_torneo` (`torneo_id`),
  ADD KEY `idx_partidos_fase` (`fase_id`),
  ADD KEY `idx_partidos_jornada` (`jornada_id`),
  ADD KEY `idx_partidos_sede` (`sede_id`),
  ADD KEY `idx_partidos_local` (`participante_local_id`),
  ADD KEY `idx_partidos_visitante` (`participante_visitante_id`),
  ADD KEY `idx_partidos_estado` (`estado_id`),
  ADD KEY `idx_partidos_inicio` (`inicio_partido`),
  ADD KEY `idx_partidos_fecha` (`fecha_partido`),
  ADD KEY `idx_partidos_fecha_estado` (`fecha_partido`,`estado_id`),
  ADD KEY `idx_partidos_torneo_estado` (`torneo_id`,`estado_id`,`fecha_partido`),
  ADD KEY `mvp_miembro_plantel_id` (`mvp_miembro_plantel_id`),
  ADD KEY `fk_jugador_local` (`jugador_local_id`),
  ADD KEY `fk_jugador_visitante` (`jugador_visitante_id`),
  ADD KEY `fk_ganador_individual` (`ganador_individual_id`);

--
-- Indexes for table `partidos_seleccion`
--
ALTER TABLE `partidos_seleccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seleccion_deporte` (`deporte_id`),
  ADD KEY `fk_seleccion_sede` (`sede_id`),
  ADD KEY `fk_seleccion_estado` (`estado_id`);

--
-- Indexes for table `planteles_equipo`
--
ALTER TABLE `planteles_equipo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_plantel_participante` (`participante_id`);

--
-- Indexes for table `posiciones_bracket`
--
ALTER TABLE `posiciones_bracket`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `posiciones_deporte`
--
ALTER TABLE `posiciones_deporte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_posicion_deporte_codigo` (`deporte_id`,`codigo`),
  ADD KEY `fk_posiciones_deporte` (`deporte_id`);

--
-- Indexes for table `puntos_set`
--
ALTER TABLE `puntos_set`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_partido_set` (`partido_id`,`set_numero`),
  ADD KEY `jugador_local_id` (`jugador_local_id`),
  ADD KEY `jugador_visitante_id` (`jugador_visitante_id`),
  ADD KEY `ganador_id` (`ganador_id`),
  ADD KEY `idx_partido_set` (`partido_id`,`set_numero`);

--
-- Indexes for table `reglas_puntuacion_deporte`
--
ALTER TABLE `reglas_puntuacion_deporte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_puntuacion_deporte` (`deporte_id`);

--
-- Indexes for table `resultados_periodo_partido`
--
ALTER TABLE `resultados_periodo_partido`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_partido_periodo` (`partido_id`,`numero_periodo`);

--
-- Indexes for table `resultados_set_partido`
--
ALTER TABLE `resultados_set_partido`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_partido_set` (`partido_id`,`numero_set`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sedes_nombre` (`nombre`);

--
-- Indexes for table `tabla_posiciones`
--
ALTER TABLE `tabla_posiciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_posicion_norm` (`torneo_id`,`participante_id`,`clave_fase`,`clave_grupo`),
  ADD KEY `idx_posiciones_torneo` (`torneo_id`),
  ADD KEY `idx_posiciones_participante` (`participante_id`),
  ADD KEY `idx_posiciones_fase` (`fase_id`),
  ADD KEY `idx_posiciones_grupo` (`grupo_id`),
  ADD KEY `idx_posiciones_posicion` (`torneo_id`,`fase_id`,`grupo_id`,`posicion`);

--
-- Indexes for table `temporadas`
--
ALTER TABLE `temporadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_temporadas_ano` (`ano`),
  ADD KEY `idx_temporadas_actual` (`es_actual`);

--
-- Indexes for table `tipos_condicion_bracket`
--
ALTER TABLE `tipos_condicion_bracket`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `tipos_evento_calendario`
--
ALTER TABLE `tipos_evento_calendario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `tipos_fase`
--
ALTER TABLE `tipos_fase`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `tipos_participante`
--
ALTER TABLE `tipos_participante`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indexes for table `torneos`
--
ALTER TABLE `torneos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_torneos_creador` (`creado_por`),
  ADD KEY `idx_torneos_deporte` (`deporte_id`),
  ADD KEY `idx_torneos_temporada` (`temporada_id`),
  ADD KEY `idx_torneos_estado` (`estado_id`),
  ADD KEY `idx_torneos_fechas` (`fecha_inicio`,`fecha_fin`),
  ADD KEY `fk_mvp_torneo` (`mvp_torneo_miembro_id`),
  ADD KEY `fk_goleador_torneo` (`goleador_torneo_miembro_id`);

--
-- Indexes for table `torneo_grupos`
--
ALTER TABLE `torneo_grupos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_torneo_grupo` (`torneo_id`,`nombre`);

--
-- Indexes for table `torneo_participantes`
--
ALTER TABLE `torneo_participantes`
  ADD PRIMARY KEY (`torneo_id`,`participante_id`),
  ADD KEY `idx_torneo_part_participante` (`participante_id`),
  ADD KEY `idx_torneo_part_semilla` (`torneo_id`,`semilla`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_estado` (`estado_id`);

--
-- Indexes for table `usuario_roles`
--
ALTER TABLE `usuario_roles`
  ADD PRIMARY KEY (`usuario_id`,`rol_id`),
  ADD KEY `fk_usuario_roles_asignador` (`asignado_por`),
  ADD KEY `idx_usuario_roles_rol` (`rol_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bracket_torneos`
--
ALTER TABLE `bracket_torneos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `configuracion_galeria`
--
ALTER TABLE `configuracion_galeria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `configuracion_noticias`
--
ALTER TABLE `configuracion_noticias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cronometro_partido`
--
ALTER TABLE `cronometro_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `deportes`
--
ALTER TABLE `deportes`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `enlaces_bracket`
--
ALTER TABLE `enlaces_bracket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eventos_partido`
--
ALTER TABLE `eventos_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `fases`
--
ALTER TABLE `fases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `galeria_temporadas`
--
ALTER TABLE `galeria_temporadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `jornadas`
--
ALTER TABLE `jornadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `jugadores`
--
ALTER TABLE `jugadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jugadores_destacados`
--
ALTER TABLE `jugadores_destacados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `miembros_plantel`
--
ALTER TABLE `miembros_plantel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `participantes`
--
ALTER TABLE `participantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `partidos`
--
ALTER TABLE `partidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=209;

--
-- AUTO_INCREMENT for table `partidos_seleccion`
--
ALTER TABLE `partidos_seleccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `planteles_equipo`
--
ALTER TABLE `planteles_equipo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `posiciones_deporte`
--
ALTER TABLE `posiciones_deporte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `puntos_set`
--
ALTER TABLE `puntos_set`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `reglas_puntuacion_deporte`
--
ALTER TABLE `reglas_puntuacion_deporte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `resultados_periodo_partido`
--
ALTER TABLE `resultados_periodo_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resultados_set_partido`
--
ALTER TABLE `resultados_set_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tabla_posiciones`
--
ALTER TABLE `tabla_posiciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temporadas`
--
ALTER TABLE `temporadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `torneos`
--
ALTER TABLE `torneos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `torneo_grupos`
--
ALTER TABLE `torneo_grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bracket_torneos`
--
ALTER TABLE `bracket_torneos`
  ADD CONSTRAINT `fk_bracket_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bracket_partido` FOREIGN KEY (`ganador_de_partido_id`) REFERENCES `partidos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_bracket_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cronometro_partido`
--
ALTER TABLE `cronometro_partido`
  ADD CONSTRAINT `fk_cronometro_partido` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enlaces_bracket`
--
ALTER TABLE `enlaces_bracket`
  ADD CONSTRAINT `fk_bracket_condicion` FOREIGN KEY (`tipo_condicion_id`) REFERENCES `tipos_condicion_bracket` (`id`),
  ADD CONSTRAINT `fk_bracket_destino` FOREIGN KEY (`partido_destino_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bracket_origen` FOREIGN KEY (`partido_origen_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bracket_posicion` FOREIGN KEY (`posicion_destino_id`) REFERENCES `posiciones_bracket` (`id`);

--
-- Constraints for table `eventos_partido`
--
ALTER TABLE `eventos_partido`
  ADD CONSTRAINT `eventos_partido_ibfk_1` FOREIGN KEY (`asistencia_miembro_plantel_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_evento_jugador` FOREIGN KEY (`miembro_plantel_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_evento_partido` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fases`
--
ALTER TABLE `fases`
  ADD CONSTRAINT `fk_fases_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `torneo_grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fases_tipo` FOREIGN KEY (`tipo_fase_id`) REFERENCES `tipos_fase` (`id`),
  ADD CONSTRAINT `fk_fases_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `galeria_temporadas`
--
ALTER TABLE `galeria_temporadas`
  ADD CONSTRAINT `fk_galeria_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_galeria_temporada` FOREIGN KEY (`temporada_id`) REFERENCES `temporadas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jornadas`
--
ALTER TABLE `jornadas`
  ADD CONSTRAINT `fk_jornadas_fase` FOREIGN KEY (`fase_id`) REFERENCES `fases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jugadores`
--
ALTER TABLE `jugadores`
  ADD CONSTRAINT `fk_jugadores_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`),
  ADD CONSTRAINT `fk_jugadores_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jugadores_posicion` FOREIGN KEY (`posicion_id`) REFERENCES `posiciones_deporte` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_jugadores_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jugadores_destacados`
--
ALTER TABLE `jugadores_destacados`
  ADD CONSTRAINT `fk_destacados_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`),
  ADD CONSTRAINT `fk_destacados_miembro` FOREIGN KEY (`miembro_plantel_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_destacados_temporada` FOREIGN KEY (`temporada_id`) REFERENCES `temporadas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_destacados_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `miembros_grupo`
--
ALTER TABLE `miembros_grupo`
  ADD CONSTRAINT `fk_miembros_grupo_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `torneo_grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_miembros_grupo_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `miembros_plantel`
--
ALTER TABLE `miembros_plantel`
  ADD CONSTRAINT `fk_miembros_plantel_plantel` FOREIGN KEY (`plantel_id`) REFERENCES `planteles_equipo` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `noticias`
--
ALTER TABLE `noticias`
  ADD CONSTRAINT `noticias_ibfk_1` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `noticias_ibfk_2` FOREIGN KEY (`temporada_id`) REFERENCES `temporadas` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `participantes`
--
ALTER TABLE `participantes`
  ADD CONSTRAINT `fk_participantes_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`),
  ADD CONSTRAINT `fk_participantes_tipo` FOREIGN KEY (`tipo_participante_id`) REFERENCES `tipos_participante` (`id`);

--
-- Constraints for table `partidos`
--
ALTER TABLE `partidos`
  ADD CONSTRAINT `fk_ganador_individual` FOREIGN KEY (`ganador_individual_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_jugador_local` FOREIGN KEY (`jugador_local_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_jugador_visitante` FOREIGN KEY (`jugador_visitante_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_partidos_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados_partido` (`id`),
  ADD CONSTRAINT `fk_partidos_fase` FOREIGN KEY (`fase_id`) REFERENCES `fases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_partidos_jornada` FOREIGN KEY (`jornada_id`) REFERENCES `jornadas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_partidos_local` FOREIGN KEY (`participante_local_id`) REFERENCES `participantes` (`id`),
  ADD CONSTRAINT `fk_partidos_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_partidos_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_partidos_visitante` FOREIGN KEY (`participante_visitante_id`) REFERENCES `participantes` (`id`),
  ADD CONSTRAINT `partidos_ibfk_1` FOREIGN KEY (`mvp_miembro_plantel_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `partidos_seleccion`
--
ALTER TABLE `partidos_seleccion`
  ADD CONSTRAINT `fk_seleccion_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`),
  ADD CONSTRAINT `fk_seleccion_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados_partido` (`id`),
  ADD CONSTRAINT `fk_seleccion_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `planteles_equipo`
--
ALTER TABLE `planteles_equipo`
  ADD CONSTRAINT `fk_plantel_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posiciones_deporte`
--
ALTER TABLE `posiciones_deporte`
  ADD CONSTRAINT `fk_posiciones_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `puntos_set`
--
ALTER TABLE `puntos_set`
  ADD CONSTRAINT `puntos_set_ibfk_1` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `puntos_set_ibfk_2` FOREIGN KEY (`jugador_local_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `puntos_set_ibfk_3` FOREIGN KEY (`jugador_visitante_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `puntos_set_ibfk_4` FOREIGN KEY (`ganador_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reglas_puntuacion_deporte`
--
ALTER TABLE `reglas_puntuacion_deporte`
  ADD CONSTRAINT `fk_puntuacion_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resultados_periodo_partido`
--
ALTER TABLE `resultados_periodo_partido`
  ADD CONSTRAINT `fk_resultados_periodo_partido` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resultados_set_partido`
--
ALTER TABLE `resultados_set_partido`
  ADD CONSTRAINT `fk_resultados_set_partido` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tabla_posiciones`
--
ALTER TABLE `tabla_posiciones`
  ADD CONSTRAINT `fk_posiciones_fase` FOREIGN KEY (`fase_id`) REFERENCES `fases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_posiciones_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `torneo_grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_posiciones_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_posiciones_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `torneos`
--
ALTER TABLE `torneos`
  ADD CONSTRAINT `fk_goleador_torneo` FOREIGN KEY (`goleador_torneo_miembro_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mvp_torneo` FOREIGN KEY (`mvp_torneo_miembro_id`) REFERENCES `miembros_plantel` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_torneos_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_torneos_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`),
  ADD CONSTRAINT `fk_torneos_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados_torneo` (`id`),
  ADD CONSTRAINT `fk_torneos_temporada` FOREIGN KEY (`temporada_id`) REFERENCES `temporadas` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `torneo_grupos`
--
ALTER TABLE `torneo_grupos`
  ADD CONSTRAINT `fk_torneo_grupos_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `torneo_participantes`
--
ALTER TABLE `torneo_participantes`
  ADD CONSTRAINT `fk_torneo_part_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`),
  ADD CONSTRAINT `fk_torneo_part_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados_usuario` (`id`);

--
-- Constraints for table `usuario_roles`
--
ALTER TABLE `usuario_roles`
  ADD CONSTRAINT `fk_usuario_roles_asignador` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_usuario_roles_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_usuario_roles_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
