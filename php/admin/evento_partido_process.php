<?php
require_once 'auth_admin.php';

$partido_id = isset($_POST['partido_id']) ? (int)$_POST['partido_id'] : (isset($_GET['partido_id']) ? (int)$_GET['partido_id'] : 0);

if (!$partido_id) {
    header("Location: gestionar_torneos.php?error=ID de partido no especificado.");
    exit;
}


function recalcularMarcadores($conn, $partido_id) {
    
    $sql_partido = "SELECT p.participante_local_id, p.participante_visitante_id,
                    t.deporte_id, d.tipo_puntuacion
                    FROM partidos p
                    JOIN torneos t ON p.torneo_id = t.id
                    JOIN deportes d ON t.deporte_id = d.id
                    WHERE p.id = ?";
    $stmt_partido = $conn->prepare($sql_partido);
    $stmt_partido->bind_param("i", $partido_id);
    $stmt_partido->execute();
    $partido = $stmt_partido->get_result()->fetch_assoc();
    $stmt_partido->close();

    if (!$partido) return;

    $tipo_puntuacion = $partido['tipo_puntuacion'];

    
    $sql_eventos = "SELECT ep.tipo_evento, ep.valor_puntos, pe.participante_id
                    FROM eventos_partido ep
                    JOIN miembros_plantel mp ON ep.miembro_plantel_id = mp.id
                    JOIN planteles_equipo pe ON mp.plantel_id = pe.id
                    WHERE ep.partido_id = ?";
    $stmt_eventos = $conn->prepare($sql_eventos);
    $stmt_eventos->bind_param("i", $partido_id);
    $stmt_eventos->execute();
    $eventos = $stmt_eventos->get_result();

    $marcador_local = 0;
    $marcador_visitante = 0;

    
    while($evento = $eventos->fetch_assoc()) {
        $tipo = $evento['tipo_evento'];
        $puntos = $evento['valor_puntos'] ?? 1;
        $participante = $evento['participante_id'];

        switch($tipo_puntuacion) {
            case 'goles':
                
                $es_gol_valido = in_array($tipo, ['gol', 'penal_anotado']);
                $es_autogol = ($tipo == 'autogol');

                if ($participante == $partido['participante_local_id']) {
                    if ($es_gol_valido) $marcador_local++;
                    elseif ($es_autogol) $marcador_visitante++;
                } else {
                    if ($es_gol_valido) $marcador_visitante++;
                    elseif ($es_autogol) $marcador_local++;
                }
                break;

            case 'puntos':
                
                $eventos_puntos = ['canasta_1pt', 'canasta_2pt', 'canasta_3pt', 'punto', 'ace', 'bloqueo'];

                if (in_array($tipo, $eventos_puntos)) {
                    if ($participante == $partido['participante_local_id']) {
                        $marcador_local += $puntos;
                    } else {
                        $marcador_visitante += $puntos;
                    }
                }
                break;

            case 'sets':
                
                if ($tipo == 'set_ganado_local') {
                    $marcador_local++;
                } elseif ($tipo == 'set_ganado_visitante') {
                    $marcador_visitante++;
                }
                break;

            case 'ganador_directo':
                
                if ($tipo == 'victoria_local' || $tipo == 'jaque_mate') {
                    if ($participante == $partido['participante_local_id']) {
                        $marcador_local = 1;
                        $marcador_visitante = 0;
                    } else {
                        $marcador_local = 0;
                        $marcador_visitante = 1;
                    }
                } elseif ($tipo == 'victoria_visitante') {
                    if ($participante == $partido['participante_visitante_id']) {
                        $marcador_visitante = 1;
                        $marcador_local = 0;
                    } else {
                        $marcador_visitante = 0;
                        $marcador_local = 1;
                    }
                } elseif ($tipo == 'empate') {
                    $marcador_local = 0;
                    $marcador_visitante = 0;
                }
                break;
        }
    }
    $stmt_eventos->close();

    
    $sql_update = "UPDATE partidos SET marcador_local = ?, marcador_visitante = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("iii", $marcador_local, $marcador_visitante, $partido_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    
    return [
        'local' => $marcador_local,
        'visitante' => $marcador_visitante
    ];
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'agregar_gol') {
    
    header('Content-Type: application/json');

    $jugador_id = isset($_POST['jugador_id']) ? (int)$_POST['jugador_id'] : 0;
    $asistencia_id = !empty($_POST['asistencia_id']) ? (int)$_POST['asistencia_id'] : NULL;
    $minuto = !empty($_POST['minuto']) ? trim($_POST['minuto']) : NULL;
    $tipo_evento = isset($_POST['tipo_evento']) ? $_POST['tipo_evento'] : '';
    $valor_puntos = isset($_POST['valor_puntos']) ? (int)$_POST['valor_puntos'] : 1;

    
    if (!$jugador_id) {
        echo json_encode(['success' => false, 'error' => 'Jugador no especificado']);
        exit;
    }

    if (!$tipo_evento) {
        echo json_encode(['success' => false, 'error' => 'Tipo de evento no especificado']);
        exit;
    }

    
    if ($asistencia_id !== NULL && $asistencia_id == $jugador_id) {
        echo json_encode(['success' => false, 'error' => 'El jugador que asiste no puede ser el mismo que anota.']);
        exit;
    }

    
    if ($minuto !== NULL && !preg_match('/^([0-9]{1,3}|[0-9]{1,3}\+[0-9]{1,2})$/', $minuto)) {
        echo json_encode(['success' => false, 'error' => 'Formato de minuto inválido. Use: 45 o 90+2']);
        exit;
    }

    try {
        $sql = "INSERT INTO eventos_partido (partido_id, miembro_plantel_id, asistencia_miembro_plantel_id, tipo_evento, valor_puntos, minuto)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Error al preparar consulta: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param("iiisis", $partido_id, $jugador_id, $asistencia_id, $tipo_evento, $valor_puntos, $minuto);

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Error al ejecutar INSERT: ' . $stmt->error]);
            exit;
        }

        $stmt->close();

        
        

        
        $marcadores = recalcularMarcadores($conn, $partido_id);

        echo json_encode([
            'success' => true, 
            'mensaje' => 'Evento agregado exitosamente',
            'nuevoMarcadorLocal' => $marcadores['local'],
            'nuevoMarcadorVisitante' => $marcadores['visitante']
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error al agregar evento: ' . $e->getMessage()]);
        exit;
    }
}



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'eliminar_gol') {
    header('Content-Type: application/json');

    $evento_id = isset($_POST['evento_id']) ? (int)$_POST['evento_id'] : 0;

    if (!$evento_id) {
        echo json_encode(['success' => false, 'error' => 'ID de evento no especificado']);
        exit;
    }

    try {
        
        $sql_get = "SELECT id FROM eventos_partido WHERE id = ?";
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->bind_param("i", $evento_id);
        $stmt_get->execute();
        $evento = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if ($evento) {
            
            $sql_delete = "DELETE FROM eventos_partido WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $evento_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            
            $marcadores = recalcularMarcadores($conn, $partido_id);

            echo json_encode([
                'success' => true,
                'mensaje' => 'Evento eliminado exitosamente',
                'nuevoMarcadorLocal' => $marcadores['local'],
                'nuevoMarcadorVisitante' => $marcadores['visitante']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Evento no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar evento: ' . $e->getMessage()]);
    }
    exit;
}


if (isset($_GET['action']) && $_GET['action'] == 'eliminar_gol' && isset($_GET['evento_id'])) {
    $evento_id = (int)$_GET['evento_id'];

    try {
        
        $sql_get = "SELECT id FROM eventos_partido WHERE id = ?";
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->bind_param("i", $evento_id);
        $stmt_get->execute();
        $evento = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if ($evento) {
            
            
            $sql_delete = "DELETE FROM eventos_partido WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $evento_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            
            recalcularMarcadores($conn, $partido_id);

            header("Location: editar_partido.php?partido_id=$partido_id&success=Evento eliminado exitosamente. Contadores actualizados.");
        } else {
            header("Location: editar_partido.php?partido_id=$partido_id&error=Evento no encontrado.");
        }
    } catch (Exception $e) {
        header("Location: editar_partido.php?partido_id=$partido_id&error=Error al eliminar evento: " . $e->getMessage());
    }
}

$conn->close();
exit;
?>
