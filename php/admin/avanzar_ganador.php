<?php





require_once 'auth_admin.php';

function avanzarGanadorSiguienteFase($conn, $partido_id) {
    try {
        
        $stmt = $conn->prepare("SELECT p.*, f.tipo_fase_id, f.nombre AS nombre_fase,
                                 t.id AS torneo_id, t.nombre AS nombre_torneo
                                 FROM partidos p
                                 JOIN fases f ON p.fase_id = f.id
                                 JOIN torneos t ON p.torneo_id = t.id
                                 WHERE p.id = ?");
        $stmt->bind_param("i", $partido_id);
        $stmt->execute();
        $partido = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$partido) {
            return ['success' => false, 'message' => 'Partido no encontrado'];
        }

        
        if (!in_array($partido['tipo_fase_id'], [2, 3, 4])) {
            return ['success' => true, 'message' => 'No es un partido de playoff'];
        }

        
        $ganador_id = null;
        if ($partido['marcador_local'] > $partido['marcador_visitante']) {
            $ganador_id = $partido['participante_local_id'];
        } elseif ($partido['marcador_visitante'] > $partido['marcador_local']) {
            $ganador_id = $partido['participante_visitante_id'];
        } else {
            
            return ['success' => true, 'message' => 'Partido empatado, no se puede avanzar'];
        }

        
        $siguiente_fase_id = null;
        $siguiente_tipo_fase = null;

        if ($partido['tipo_fase_id'] == 2) { 
            $siguiente_tipo_fase = 3;
        } elseif ($partido['tipo_fase_id'] == 3) { 
            $siguiente_tipo_fase = 4;
        } elseif ($partido['tipo_fase_id'] == 4) { 
            
            $stmt_finalizar = $conn->prepare("UPDATE torneos SET estado_id = 5 WHERE id = ?");
            $stmt_finalizar->bind_param("i", $partido['torneo_id']);
            $stmt_finalizar->execute();
            $stmt_finalizar->close();

            return ['success' => true, 'message' => 'El partido es la final, se ha determinado el campeón. Torneo finalizado automáticamente.'];
        }

        
        $stmt_fase = $conn->prepare("SELECT id FROM fases WHERE torneo_id = ? AND tipo_fase_id = ? LIMIT 1");
        $stmt_fase->bind_param("ii", $partido['torneo_id'], $siguiente_tipo_fase);
        $stmt_fase->execute();
        $result_fase = $stmt_fase->get_result();

        if ($result_fase->num_rows > 0) {
            $fase = $result_fase->fetch_assoc();
            $siguiente_fase_id = $fase['id'];
        } else {
            
            $nombres_fase = [3 => 'Semifinales', 4 => 'Final'];
            $orden_fase = [3 => 3, 4 => 4];

            $stmt_crear = $conn->prepare("INSERT INTO fases (torneo_id, tipo_fase_id, orden_fase, nombre, fecha_inicio, fecha_fin)
                                          VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY))");
            $stmt_crear->bind_param("iiis", $partido['torneo_id'], $siguiente_tipo_fase, $orden_fase[$siguiente_tipo_fase], $nombres_fase[$siguiente_tipo_fase]);
            $stmt_crear->execute();
            $siguiente_fase_id = $conn->insert_id;
            $stmt_crear->close();
        }
        $stmt_fase->close();

        
        
        
        $posicion_siguiente = determinarPosicionSiguiente($partido['id'], $partido['torneo_id'], $partido['tipo_fase_id']);

        
        
        $stmt_buscar = $conn->prepare("SELECT id, participante_local_id, participante_visitante_id
                                        FROM partidos
                                        WHERE torneo_id = ? AND fase_id = ?
                                        ORDER BY id
                                        LIMIT 1 OFFSET ?");
        $offset = $posicion_siguiente - 1;
        $stmt_buscar->bind_param("iii", $partido['torneo_id'], $siguiente_fase_id, $offset);
        $stmt_buscar->execute();
        $partido_siguiente = $stmt_buscar->get_result()->fetch_assoc();
        $stmt_buscar->close();

        if (!$partido_siguiente) {
            
            $stmt_crear = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                          VALUES (?, ?, NULL, NULL, DATE_ADD(NOW(), INTERVAL 7 DAY), 2)");
            $stmt_crear->bind_param("ii", $partido['torneo_id'], $siguiente_fase_id);
            $stmt_crear->execute();
            $partido_siguiente_id = $conn->insert_id;
            $stmt_crear->close();

            
            $stmt_buscar2 = $conn->prepare("SELECT id, participante_local_id, participante_visitante_id FROM partidos WHERE id = ?");
            $stmt_buscar2->bind_param("i", $partido_siguiente_id);
            $stmt_buscar2->execute();
            $partido_siguiente = $stmt_buscar2->get_result()->fetch_assoc();
            $stmt_buscar2->close();
        }

        
        $es_local_siguiente = esLocalEnSiguienteFase($partido['id'], $partido['torneo_id'], $partido['tipo_fase_id']);

        
        if ($es_local_siguiente) {
            $stmt_update = $conn->prepare("UPDATE partidos SET participante_local_id = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $ganador_id, $partido_siguiente['id']);
        } else {
            $stmt_update = $conn->prepare("UPDATE partidos SET participante_visitante_id = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $ganador_id, $partido_siguiente['id']);
        }
        $stmt_update->execute();
        $stmt_update->close();

        
        $stmt_verificar = $conn->prepare("SELECT participante_local_id, participante_visitante_id FROM partidos WHERE id = ?");
        $stmt_verificar->bind_param("i", $partido_siguiente['id']);
        $stmt_verificar->execute();
        $partido_actualizado = $stmt_verificar->get_result()->fetch_assoc();
        $stmt_verificar->close();

        
        $ganador_asignado = false;
        if ($es_local_siguiente && $partido_actualizado['participante_local_id'] == $ganador_id) {
            $ganador_asignado = true;
        } elseif (!$es_local_siguiente && $partido_actualizado['participante_visitante_id'] == $ganador_id) {
            $ganador_asignado = true;
        }

        if ($ganador_asignado) {
            return ['success' => true, 'message' => 'Ganador avanzado a la siguiente fase exitosamente'];
        } else {
            return ['success' => false, 'message' => "Error: El ganador (ID: $ganador_id) no se asignó correctamente al partido siguiente (ID: {$partido_siguiente['id']}) como " . ($es_local_siguiente ? 'local' : 'visitante')];
        }

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al avanzar ganador: ' . $e->getMessage()];
    }
}

function determinarPosicionSiguiente($partido_id, $torneo_id, $tipo_fase_actual) {
    global $conn;

    
    $stmt = $conn->prepare("SELECT p1.id
                            FROM partidos p1
                            JOIN fases f ON p1.fase_id = f.id
                            WHERE p1.torneo_id = ? AND f.tipo_fase_id = ?
                            ORDER BY p1.id ASC");
    $stmt->bind_param("ii", $torneo_id, $tipo_fase_actual);
    $stmt->execute();
    $result = $stmt->get_result();

    $posicion_actual = 0;
    $contador = 1;
    while ($row = $result->fetch_assoc()) {
        if ($row['id'] == $partido_id) {
            $posicion_actual = $contador;
            break;
        }
        $contador++;
    }
    $stmt->close();

    
    if ($tipo_fase_actual == 2) { 
        return ($posicion_actual <= 2) ? 1 : 2;
    } else { 
        return 1; 
    }
}

function esLocalEnSiguienteFase($partido_id, $torneo_id, $tipo_fase_actual) {
    global $conn;

    
    $stmt = $conn->prepare("SELECT p1.id
                            FROM partidos p1
                            JOIN fases f ON p1.fase_id = f.id
                            WHERE p1.torneo_id = ? AND f.tipo_fase_id = ?
                            ORDER BY p1.id ASC");
    $stmt->bind_param("ii", $torneo_id, $tipo_fase_actual);
    $stmt->execute();
    $result = $stmt->get_result();

    $posicion = 0;
    $contador = 1;
    while ($row = $result->fetch_assoc()) {
        if ($row['id'] == $partido_id) {
            $posicion = $contador;
            break;
        }
        $contador++;
    }
    $stmt->close();

    
    
    if ($tipo_fase_actual == 2) { 
        return ($posicion == 1 || $posicion == 3);
    } else { 
        return ($posicion == 1);
    }
}
?>
