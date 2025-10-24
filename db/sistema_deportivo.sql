-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 03:18 AM
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
-- Table structure for table `deportes`
--

CREATE TABLE `deportes` (
  `id` tinyint(4) NOT NULL,
  `codigo` varchar(32) NOT NULL,
  `nombre_mostrado` varchar(64) NOT NULL,
  `es_por_equipos` tinyint(1) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deportes`
--

INSERT INTO `deportes` (`id`, `codigo`, `nombre_mostrado`, `es_por_equipos`, `creado_en`) VALUES
(1, 'football', 'Fútbol', 1, '2025-10-24 01:17:52'),
(2, 'volleyball', 'Voleibol', 1, '2025-10-24 01:17:52'),
(3, 'basketball', 'Baloncesto', 1, '2025-10-24 01:17:52'),
(4, 'table_tennis', 'Tenis de Mesa', 0, '2025-10-24 01:17:52'),
(5, 'chess', 'Ajedrez', 0, '2025-10-24 01:17:52');

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

-- --------------------------------------------------------

--
-- Table structure for table `partidos`
--

CREATE TABLE `partidos` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `fase_id` int(11) NOT NULL,
  `jornada_id` int(11) DEFAULT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `participante_local_id` int(11) NOT NULL,
  `participante_visitante_id` int(11) NOT NULL,
  `inicio_partido` datetime NOT NULL,
  `fecha_partido` date GENERATED ALWAYS AS (cast(`inicio_partido` as date)) STORED,
  `hora_partido` time GENERATED ALWAYS AS (cast(`inicio_partido` as time)) STORED,
  `estado_id` tinyint(4) NOT NULL DEFAULT 1,
  `marcador_local` smallint(6) DEFAULT 0,
  `marcador_visitante` smallint(6) DEFAULT 0,
  `marcador_local_sets` tinyint(4) DEFAULT NULL COMMENT 'Para voleibol',
  `marcador_visitante_sets` tinyint(4) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

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
) ;

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
  `estado_id` tinyint(4) NOT NULL DEFAULT 1,
  `max_participantes` smallint(6) NOT NULL,
  `creado_por` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'Administrador del Sistema', 'admin@gmail.com', '12345', 1, '2025-10-24 01:17:52', '2025-10-24 01:17:52', NULL);

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
(1, 1, '2025-10-24 01:17:52', NULL);

--
-- Indexes for dumped tables
--

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
-- Indexes for table `fases`
--
ALTER TABLE `fases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fases_grupo` (`grupo_id`),
  ADD KEY `idx_fases_torneo` (`torneo_id`),
  ADD KEY `idx_fases_tipo` (`tipo_fase_id`),
  ADD KEY `idx_fases_orden` (`torneo_id`,`orden_fase`);

--
-- Indexes for table `jornadas`
--
ALTER TABLE `jornadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jornadas_fase` (`fase_id`),
  ADD KEY `idx_jornadas_fecha` (`fecha_jornada`),
  ADD KEY `idx_jornadas_numero` (`fase_id`,`numero_jornada`);

--
-- Indexes for table `miembros_grupo`
--
ALTER TABLE `miembros_grupo`
  ADD PRIMARY KEY (`grupo_id`,`participante_id`),
  ADD KEY `idx_miembros_grupo_participante` (`participante_id`);

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
  ADD KEY `idx_partidos_torneo_estado` (`torneo_id`,`estado_id`,`fecha_partido`);

--
-- Indexes for table `partidos_seleccion`
--
ALTER TABLE `partidos_seleccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seleccion_deporte` (`deporte_id`),
  ADD KEY `fk_seleccion_sede` (`sede_id`),
  ADD KEY `fk_seleccion_estado` (`estado_id`);

--
-- Indexes for table `posiciones_bracket`
--
ALTER TABLE `posiciones_bracket`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

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
  ADD KEY `idx_torneos_fechas` (`fecha_inicio`,`fecha_fin`);

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
-- AUTO_INCREMENT for table `deportes`
--
ALTER TABLE `deportes`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `enlaces_bracket`
--
ALTER TABLE `enlaces_bracket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fases`
--
ALTER TABLE `fases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jornadas`
--
ALTER TABLE `jornadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `participantes`
--
ALTER TABLE `participantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partidos`
--
ALTER TABLE `partidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partidos_seleccion`
--
ALTER TABLE `partidos_seleccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `torneos`
--
ALTER TABLE `torneos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `torneo_grupos`
--
ALTER TABLE `torneo_grupos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `enlaces_bracket`
--
ALTER TABLE `enlaces_bracket`
  ADD CONSTRAINT `fk_bracket_condicion` FOREIGN KEY (`tipo_condicion_id`) REFERENCES `tipos_condicion_bracket` (`id`),
  ADD CONSTRAINT `fk_bracket_destino` FOREIGN KEY (`partido_destino_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bracket_origen` FOREIGN KEY (`partido_origen_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bracket_posicion` FOREIGN KEY (`posicion_destino_id`) REFERENCES `posiciones_bracket` (`id`);

--
-- Constraints for table `fases`
--
ALTER TABLE `fases`
  ADD CONSTRAINT `fk_fases_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `torneo_grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fases_tipo` FOREIGN KEY (`tipo_fase_id`) REFERENCES `tipos_fase` (`id`),
  ADD CONSTRAINT `fk_fases_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jornadas`
--
ALTER TABLE `jornadas`
  ADD CONSTRAINT `fk_jornadas_fase` FOREIGN KEY (`fase_id`) REFERENCES `fases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `miembros_grupo`
--
ALTER TABLE `miembros_grupo`
  ADD CONSTRAINT `fk_miembros_grupo_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `torneo_grupos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_miembros_grupo_participante` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_partidos_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados_partido` (`id`),
  ADD CONSTRAINT `fk_partidos_fase` FOREIGN KEY (`fase_id`) REFERENCES `fases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_partidos_jornada` FOREIGN KEY (`jornada_id`) REFERENCES `jornadas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_partidos_local` FOREIGN KEY (`participante_local_id`) REFERENCES `participantes` (`id`),
  ADD CONSTRAINT `fk_partidos_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_partidos_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_partidos_visitante` FOREIGN KEY (`participante_visitante_id`) REFERENCES `participantes` (`id`);

--
-- Constraints for table `partidos_seleccion`
--
ALTER TABLE `partidos_seleccion`
  ADD CONSTRAINT `fk_seleccion_deporte` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`),
  ADD CONSTRAINT `fk_seleccion_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados_partido` (`id`),
  ADD CONSTRAINT `fk_seleccion_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE SET NULL;

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
