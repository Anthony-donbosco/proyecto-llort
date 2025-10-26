-- Agregar jugadores al plantel existente (Selecta del llort)
-- El plantel_id = 1 corresponde al equipo existente

INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`, `goles`, `asistencias`, `porterias_cero`) VALUES
(1, 'Edgardo Rojas', 'Defensa', 16, '1°A', 2, 0, 0, 0),
(1, 'Isaac Escamilla', 'Delantero', 16, '1°A', 9, 0, 0, 0),
(1, 'Alexis Mendoza', 'Defensa', 17, '1°B', 3, 0, 0, 0),
(1, 'Jefferson Mejía', 'Defensa', 16, '1°B', 4, 0, 0, 0),
(1, 'Byron Segovia', 'Delantero', 17, '1°A', 7, 0, 0, 0),
(1, 'Oscar Vázquez', 'Mediocampista', 20, '1°A', 8, 0, 0, 0),
(1, 'Javier Barrera', 'Portero', 17, '2°B', 1, 0, 0, 0),
(1, 'Kevin Lopez', 'Delantero', 17, '2°A', 11, 0, 0, 0),
(1, 'Jasson López', 'Defensa', 17, '2°A', 5, 0, 0, 0),
(1, 'Aldo Flores', 'Mediocampista', 17, '2°A', 6, 0, 0, 0);

-- Crear equipos adicionales para el torneo de fútbol (11 equipos más para completar 12)
-- Tipo participante: 1 = Equipo, Deporte: 1 = Fútbol

INSERT INTO `participantes` (`deporte_id`, `tipo_participante_id`, `nombre_mostrado`, `nombre_corto`, `url_logo`) VALUES
(1, 1, 'Águilas FC', 'AGUI', NULL),
(1, 1, 'Titanes del Norte', 'TITA', NULL),
(1, 1, 'Leones Dorados', 'LEON', NULL),
(1, 1, 'Dragones Unidos', 'DRAG', NULL),
(1, 1, 'Halcones Rojos', 'HALC', NULL),
(1, 1, 'Tigres del Sur', 'TIGR', NULL),
(1, 1, 'Cóndores FC', 'COND', NULL),
(1, 1, 'Pumas Salvajes', 'PUMA', NULL),
(1, 1, 'Lobos Grises', 'LOBO', NULL),
(1, 1, 'Zorros Azules', 'ZORR', NULL),
(1, 1, 'Osos Pardos', 'OSOS', NULL);

-- Crear planteles para los nuevos equipos
-- Los IDs de participantes serán 2-12 (asumiendo que el ID 1 ya existe)

INSERT INTO `planteles_equipo` (`participante_id`, `nombre_plantel`, `esta_activo`) VALUES
(2, 'Plantel Principal', 1),
(3, 'Plantel Principal', 1),
(4, 'Plantel Principal', 1),
(5, 'Plantel Principal', 1),
(6, 'Plantel Principal', 1),
(7, 'Plantel Principal', 1),
(8, 'Plantel Principal', 1),
(9, 'Plantel Principal', 1),
(10, 'Plantel Principal', 1),
(11, 'Plantel Principal', 1),
(12, 'Plantel Principal', 1);

-- Agregar algunos jugadores de ejemplo a los nuevos equipos (3 por equipo para empezar)
-- Esto es solo de ejemplo, puedes agregar más después desde el panel admin

-- Águilas FC (participante_id=2, plantel_id=2)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(2, 'Carlos Ramírez', 'Portero', 18, '2°A', 1),
(2, 'Miguel Torres', 'Defensa', 17, '2°B', 4),
(2, 'Luis Hernández', 'Delantero', 16, '1°A', 9);

-- Titanes del Norte (participante_id=3, plantel_id=3)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(3, 'Roberto Gómez', 'Portero', 17, '2°A', 1),
(3, 'Pedro Martínez', 'Mediocampista', 18, '2°B', 8),
(3, 'Juan Pérez', 'Delantero', 17, '2°A', 10);

-- Leones Dorados (participante_id=4, plantel_id=4)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(4, 'Antonio Silva', 'Portero', 16, '1°B', 1),
(4, 'Fernando Castro', 'Defensa', 17, '2°A', 3),
(4, 'Diego Morales', 'Delantero', 18, '2°B', 11);

-- Dragones Unidos (participante_id=5, plantel_id=5)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(5, 'Sergio Ruiz', 'Portero', 17, '2°A', 1),
(5, 'Mario Vargas', 'Mediocampista', 16, '1°A', 6),
(5, 'Ricardo Delgado', 'Delantero', 17, '2°B', 9);

-- Halcones Rojos (participante_id=6, plantel_id=6)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(6, 'Andrés Ortiz', 'Portero', 18, '2°B', 1),
(6, 'Gabriel Medina', 'Defensa', 17, '2°A', 5),
(6, 'Daniel Ramos', 'Delantero', 16, '1°B', 7);

-- Tigres del Sur (participante_id=7, plantel_id=7)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(7, 'Javier Soto', 'Portero', 17, '2°A', 1),
(7, 'Alberto Jiménez', 'Mediocampista', 18, '2°B', 10),
(7, 'Raúl Cordero', 'Delantero', 17, '2°A', 9);

-- Cóndores FC (participante_id=8, plantel_id=8)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(8, 'Cristian Vega', 'Portero', 16, '1°A', 1),
(8, 'Manuel Navarro', 'Defensa', 17, '2°B', 2),
(8, 'Pablo Guzmán', 'Delantero', 18, '2°A', 11);

-- Pumas Salvajes (participante_id=9, plantel_id=9)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(9, 'Esteban Reyes', 'Portero', 17, '2°A', 1),
(9, 'Alejandro Cruz', 'Mediocampista', 16, '1°B', 8),
(9, 'Francisco Díaz', 'Delantero', 17, '2°B', 9);

-- Lobos Grises (participante_id=10, plantel_id=10)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(10, 'Hugo Paredes', 'Portero', 18, '2°B', 1),
(10, 'Gustavo Luna', 'Defensa', 17, '2°A', 4),
(10, 'Marcos Salazar', 'Delantero', 16, '1°A', 10);

-- Zorros Azules (participante_id=11, plantel_id=11)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(11, 'Rodrigo Campos', 'Portero', 17, '2°A', 1),
(11, 'Iván Fuentes', 'Mediocampista', 18, '2°B', 6),
(11, 'Óscar Peña', 'Delantero', 17, '2°A', 11);

-- Osos Pardos (participante_id=12, plantel_id=12)
INSERT INTO `miembros_plantel` (`plantel_id`, `nombre_jugador`, `posicion`, `edad`, `grado`, `numero_camiseta`) VALUES
(12, 'Víctor Acosta', 'Portero', 16, '1°B', 1),
(12, 'César Molina', 'Defensa', 17, '2°A', 3),
(12, 'Ernesto Ponce', 'Delantero', 18, '2°B', 9);
