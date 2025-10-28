<?php
require_once 'auth_admin.php';

if (!isset($_POST['action']) || !isset($_POST['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=Parámetros inválidos.");
    exit;
}

$action = $_POST['action'];
$torneo_id = (int)$_POST['torneo_id'];

if ($action == 'generar') {
    try {
        
        if (!isset($_POST['emparejamientos']) || !isset($_POST['fechas']) || !isset($_POST['nombre_fase'])) {
            header("Location: generar_playoffs.php?torneo_id=$torneo_id&error=Faltan datos requeridos.");
            exit;
        }

        $emparejamientos = $_POST['emparejamientos'];
        $fechas = $_POST['fechas'];
        $nombre_fase = trim($_POST['nombre_fase']);

        
        if (count($emparejamientos) != 4 || count($fechas) != 4) {
            header("Location: generar_playoffs.php?torneo_id=$torneo_id&error=Número incorrecto de emparejamientos o fechas.");
            exit;
        }

        
        $conn->begin_transaction();

        
        $stmt_fase = $conn->prepare("INSERT INTO fases (torneo_id, tipo_fase_id, orden_fase, nombre, fecha_inicio, fecha_fin)
                                      VALUES (?, 5, 2, ?, ?, ?)");

        
        $fechas_ordenadas = $fechas;
        sort($fechas_ordenadas);
        $fecha_inicio = date('Y-m-d', strtotime($fechas_ordenadas[0]));
        $fecha_fin = date('Y-m-d', strtotime($fechas_ordenadas[3]));

        $stmt_fase->bind_param("isss", $torneo_id, $nombre_fase, $fecha_inicio, $fecha_fin);
        $stmt_fase->execute();
        $fase_id = $conn->insert_id;
        $stmt_fase->close();

        
        $stmt_partido = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                         VALUES (?, ?, ?, ?, ?, 2)");

        for ($i = 0; $i < 4; $i++) {
            $local_id = (int)$emparejamientos[$i]['local_id'];
            $visitante_id = (int)$emparejamientos[$i]['visitante_id'];
            $fecha_partido = $fechas[$i];

            
            if (empty($fecha_partido)) {
                throw new Exception("La fecha del partido " . ($i + 1) . " no puede estar vacía.");
            }

            
            if ($local_id == $visitante_id) {
                throw new Exception("Un equipo no puede jugar contra sí mismo.");
            }

            
            $stmt_partido->bind_param("iiiss", $torneo_id, $fase_id, $local_id, $visitante_id, $fecha_partido);
            $stmt_partido->execute();
        }

        $stmt_partido->close();

        
        $conn->commit();

        header("Location: gestionar_jornadas.php?torneo_id=$torneo_id&success=Cuartos de final generados exitosamente.");

    } catch (Exception $e) {
        
        $conn->rollback();
        header("Location: generar_playoffs.php?torneo_id=$torneo_id&error=Error al generar playoffs: " . $e->getMessage());
    }

} elseif ($action == 'crear_bracket') {
    try {
        
        if (!isset($_POST['bracket_data']) || !isset($_POST['fase_inicial'])) {
            header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=Datos del bracket no recibidos.");
            exit;
        }

        $bracket_data = json_decode($_POST['bracket_data'], true);
        $fase_inicial = $_POST['fase_inicial'];

        if (!$bracket_data) {
            header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=Datos del bracket inválidos.");
            exit;
        }

        
        if ($fase_inicial == 'cuartos' && !isset($bracket_data['cuartos'])) {
            header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=Datos de cuartos de final inválidos.");
            exit;
        } elseif ($fase_inicial == 'semis' && !isset($bracket_data['semis'])) {
            header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=Datos de semifinales inválidos.");
            exit;
        } elseif ($fase_inicial == 'final' && !isset($bracket_data['final'])) {
            header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=Datos de final inválidos.");
            exit;
        }

        
        $stmt_validar = $conn->prepare("SELECT COUNT(*) as partidos_pendientes
                                        FROM partidos p
                                        JOIN fases f ON p.fase_id = f.id
                                        WHERE p.torneo_id = ?
                                        AND f.tipo_fase_id = 1
                                        AND p.estado_id NOT IN (5, 7, 9)");
        $stmt_validar->bind_param("i", $torneo_id);
        $stmt_validar->execute();
        $validacion = $stmt_validar->get_result()->fetch_assoc();
        $stmt_validar->close();

        if ($validacion['partidos_pendientes'] > 0) {
            header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=No se pueden asignar los playoffs. Aún hay {$validacion['partidos_pendientes']} partido(s) de fase regular sin finalizar.");
            exit;
        }

        
        $conn->begin_transaction();

        
        $stmt_delete = $conn->prepare("DELETE FROM bracket_torneos WHERE torneo_id = ?");
        $stmt_delete->bind_param("i", $torneo_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        
        $stmt_insert = $conn->prepare("INSERT INTO bracket_torneos (torneo_id, fase, posicion_bracket, participante_id)
                                       VALUES (?, ?, ?, ?)");

        
        $fase_data_key = $fase_inicial; 

        if (isset($bracket_data[$fase_data_key])) {
            foreach ($bracket_data[$fase_data_key] as $posicion => $partido) {
                if (isset($partido['local'])) {
                    $tipo_bracket = (int)$posicion * 2 - 1; 
                    $participante_id = (int)$partido['local'];
                    $stmt_insert->bind_param("isii", $torneo_id, $fase_inicial, $tipo_bracket, $participante_id);
                    $stmt_insert->execute();
                }

                if (isset($partido['visitante'])) {
                    $tipo_bracket = (int)$posicion * 2; 
                    $participante_id = (int)$partido['visitante'];
                    $stmt_insert->bind_param("isii", $torneo_id, $fase_inicial, $tipo_bracket, $participante_id);
                    $stmt_insert->execute();
                }
            }
        }

        $stmt_insert->close();

        
        $tipo_fase_map = [
            'cuartos' => ['id' => 2, 'nombre' => 'Cuartos de Final', 'orden' => 2],
            'semis' => ['id' => 3, 'nombre' => 'Semifinales', 'orden' => 3],
            'final' => ['id' => 4, 'nombre' => 'Final', 'orden' => 4]
        ];

        
        $fases_ids = [];

        
        $fases_a_crear = [];
        if ($fase_inicial == 'cuartos') {
            $fases_a_crear = ['cuartos', 'semis', 'final'];
        } elseif ($fase_inicial == 'semis') {
            $fases_a_crear = ['semis', 'final'];
        } else {
            $fases_a_crear = ['final'];
        }

        foreach ($fases_a_crear as $fase_nombre) {
            $fase_config = $tipo_fase_map[$fase_nombre];

            
            $stmt_fase = $conn->prepare("SELECT id FROM fases WHERE torneo_id = ? AND tipo_fase_id = ? LIMIT 1");
            $stmt_fase->bind_param("ii", $torneo_id, $fase_config['id']);
            $stmt_fase->execute();
            $result_fase = $stmt_fase->get_result();

            if ($result_fase->num_rows > 0) {
                $fase = $result_fase->fetch_assoc();
                $fases_ids[$fase_nombre] = $fase['id'];
            } else {
                
                $dias_inicio = ($fase_nombre == 'cuartos') ? 0 : (($fase_nombre == 'semis') ? 7 : 14);
                $stmt_crear_fase = $conn->prepare("INSERT INTO fases (torneo_id, tipo_fase_id, orden_fase, nombre, fecha_inicio, fecha_fin)
                                                   VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY))");
                $dias_fin = $dias_inicio + 7;
                $stmt_crear_fase->bind_param("iiisii", $torneo_id, $fase_config['id'], $fase_config['orden'], $fase_config['nombre'], $dias_inicio, $dias_fin);
                $stmt_crear_fase->execute();
                $fases_ids[$fase_nombre] = $conn->insert_id;
                $stmt_crear_fase->close();
            }
            $stmt_fase->close();
        }

        
        $stmt_partido = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), 2)");

        $contador = 0;
        $equipos_con_bye = [];

        if (isset($bracket_data[$fase_inicial])) {
            foreach ($bracket_data[$fase_inicial] as $posicion => $partido) {
                if (isset($partido['local'])) {
                    $local_id = (int)$partido['local'];
                    $visitante_id = isset($partido['visitante']) ? (int)$partido['visitante'] : null;
                    $dias_adelante = $contador + 1;

                    $stmt_partido->bind_param("iiiii", $torneo_id, $fases_ids[$fase_inicial], $local_id, $visitante_id, $dias_adelante);
                    $stmt_partido->execute();
                    $contador++;

                    if (!$visitante_id) {
                        $equipos_con_bye[] = $local_id;
                    }
                }
            }
        }
        $stmt_partido->close();

        
        if ($fase_inicial == 'cuartos' && isset($fases_ids['semis'])) {
            $stmt_semis = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                          VALUES (?, ?, NULL, NULL, DATE_ADD(NOW(), INTERVAL 7 DAY), 2)");

            
            for ($i = 0; $i < 2; $i++) {
                $stmt_semis->bind_param("ii", $torneo_id, $fases_ids['semis']);
                $stmt_semis->execute();
            }
            $stmt_semis->close();
        }

        
        if ($fase_inicial != 'final' && isset($fases_ids['final'])) {
            $stmt_final = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                          VALUES (?, ?, NULL, NULL, DATE_ADD(NOW(), INTERVAL 14 DAY), 2)");
            $stmt_final->bind_param("ii", $torneo_id, $fases_ids['final']);
            $stmt_final->execute();
            $stmt_final->close();
        }

        
        $mensaje_bye = '';
        if (count($equipos_con_bye) > 0) {
            $mensaje_bye = ' (' . count($equipos_con_bye) . ' equipo(s) pasaron directo por BYE)';
        }

        
        $conn->commit();

        header("Location: asignar_llaves.php?torneo_id=$torneo_id&success=Bracket de playoffs creado exitosamente. Se generaron " . $contador . " partido(s) de " . $fase_config['nombre'] . $mensaje_bye . ".");

    } catch (Exception $e) {
        
        $conn->rollback();
        header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=Error al crear bracket: " . $e->getMessage());
    }

} elseif ($action == 'eliminar_partidos_bracket') {
    try {
        
        $conn->begin_transaction();

        
        $stmt_delete_eventos = $conn->prepare("DELETE ep FROM eventos_partido ep
                                                JOIN partidos p ON ep.partido_id = p.id
                                                JOIN fases f ON p.fase_id = f.id
                                                WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4)");
        $stmt_delete_eventos->bind_param("i", $torneo_id);
        $stmt_delete_eventos->execute();
        $eventos_eliminados = $stmt_delete_eventos->affected_rows;
        $stmt_delete_eventos->close();

        
        $stmt_delete_cronometro = $conn->prepare("DELETE cp FROM cronometro_partido cp
                                                   JOIN partidos p ON cp.partido_id = p.id
                                                   JOIN fases f ON p.fase_id = f.id
                                                   WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4)");
        $stmt_delete_cronometro->bind_param("i", $torneo_id);
        $stmt_delete_cronometro->execute();
        $stmt_delete_cronometro->close();

        
        $stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM partidos p
                                       JOIN fases f ON p.fase_id = f.id
                                       WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4)");
        $stmt_count->bind_param("i", $torneo_id);
        $stmt_count->execute();
        $count_result = $stmt_count->get_result()->fetch_assoc();
        $partidos_eliminados = $count_result['total'];
        $stmt_count->close();

        
        $stmt_delete_partidos = $conn->prepare("DELETE p FROM partidos p
                                                 JOIN fases f ON p.fase_id = f.id
                                                 WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4)");
        $stmt_delete_partidos->bind_param("i", $torneo_id);
        $stmt_delete_partidos->execute();
        $stmt_delete_partidos->close();

        
        $stmt_delete_fases = $conn->prepare("DELETE FROM fases WHERE torneo_id = ? AND tipo_fase_id IN (2, 3, 4)");
        $stmt_delete_fases->bind_param("i", $torneo_id);
        $stmt_delete_fases->execute();
        $stmt_delete_fases->close();

        
        

        
        $conn->commit();

        $mensaje_detalle = "Partidos eliminados: $partidos_eliminados. Eventos eliminados: $eventos_eliminados.";
        header("Location: asignar_llaves.php?torneo_id=$torneo_id&success=Partidos del bracket eliminados exitosamente. $mensaje_detalle");

    } catch (Exception $e) {
        
        $conn->rollback();
        header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=Error al eliminar partidos: " . $e->getMessage());
    }

} else {
    header("Location: gestionar_torneos.php?error=Acción no válida.");
}

$conn->close();
exit;
?>
