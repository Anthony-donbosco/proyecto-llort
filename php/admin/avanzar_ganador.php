<?php
/**
 * Sistema de avance automático de ganadores en brackets
 * Este archivo maneja el avance de equipos ganadores a la siguiente fase
 */

require_once 'auth_admin.php';

function avanzarGanadorSiguienteFase($conn, $partido_id) {
    try {
        // Obtener información del partido finalizado
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

        // Solo procesar partidos de playoff (tipo_fase_id: 2=cuartos, 3=semis, 4=final)
        if (!in_array($partido['tipo_fase_id'], [2, 3, 4])) {
            return ['success' => true, 'message' => 'No es un partido de playoff'];
        }

        // Determinar el ganador
        $ganador_id = null;
        if ($partido['marcador_local'] > $partido['marcador_visitante']) {
            $ganador_id = $partido['participante_local_id'];
        } elseif ($partido['marcador_visitante'] > $partido['marcador_local']) {
            $ganador_id = $partido['participante_visitante_id'];
        } else {
            // Empate - no avanzar por ahora
            return ['success' => true, 'message' => 'Partido empatado, no se puede avanzar'];
        }

        // Determinar la siguiente fase
        $siguiente_fase_id = null;
        $siguiente_tipo_fase = null;

        if ($partido['tipo_fase_id'] == 2) { // Cuartos -> Semis
            $siguiente_tipo_fase = 3;
        } elseif ($partido['tipo_fase_id'] == 3) { // Semis -> Final
            $siguiente_tipo_fase = 4;
        } elseif ($partido['tipo_fase_id'] == 4) { // Final -> Campeón (no hay siguiente)
            // Finalizar el torneo automáticamente
            $stmt_finalizar = $conn->prepare("UPDATE torneos SET estado_id = 5 WHERE id = ?");
            $stmt_finalizar->bind_param("i", $partido['torneo_id']);
            $stmt_finalizar->execute();
            $stmt_finalizar->close();

            return ['success' => true, 'message' => 'El partido es la final, se ha determinado el campeón. Torneo finalizado automáticamente.'];
        }

        // Buscar o crear la fase siguiente
        $stmt_fase = $conn->prepare("SELECT id FROM fases WHERE torneo_id = ? AND tipo_fase_id = ? LIMIT 1");
        $stmt_fase->bind_param("ii", $partido['torneo_id'], $siguiente_tipo_fase);
        $stmt_fase->execute();
        $result_fase = $stmt_fase->get_result();

        if ($result_fase->num_rows > 0) {
            $fase = $result_fase->fetch_assoc();
            $siguiente_fase_id = $fase['id'];
        } else {
            // Crear la fase siguiente
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

        // Determinar la posición del partido siguiente
        // Los partidos de cuartos 1 y 2 van a semi 1, los partidos 3 y 4 van a semi 2
        // Las semis 1 y 2 van a la final
        $posicion_siguiente = determinarPosicionSiguiente($partido['id'], $partido['torneo_id'], $partido['tipo_fase_id']);

        // Buscar el partido de la siguiente fase
        // Los partidos ya deberían estar creados por playoffs_process.php
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
            // Si no existe, crear el partido (fallback por si acaso)
            $stmt_crear = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                          VALUES (?, ?, NULL, NULL, DATE_ADD(NOW(), INTERVAL 7 DAY), 2)");
            $stmt_crear->bind_param("ii", $partido['torneo_id'], $siguiente_fase_id);
            $stmt_crear->execute();
            $partido_siguiente_id = $conn->insert_id;
            $stmt_crear->close();

            // Volver a obtener el partido
            $stmt_buscar2 = $conn->prepare("SELECT id, participante_local_id, participante_visitante_id FROM partidos WHERE id = ?");
            $stmt_buscar2->bind_param("i", $partido_siguiente_id);
            $stmt_buscar2->execute();
            $partido_siguiente = $stmt_buscar2->get_result()->fetch_assoc();
            $stmt_buscar2->close();
        }

        // Determinar si el ganador va como local o visitante en el siguiente partido
        $es_local_siguiente = esLocalEnSiguienteFase($partido['id'], $partido['torneo_id'], $partido['tipo_fase_id']);

        // Actualizar el partido con el ganador
        if ($es_local_siguiente) {
            $stmt_update = $conn->prepare("UPDATE partidos SET participante_local_id = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $ganador_id, $partido_siguiente['id']);
        } else {
            $stmt_update = $conn->prepare("UPDATE partidos SET participante_visitante_id = ? WHERE id = ?");
            $stmt_update->bind_param("ii", $ganador_id, $partido_siguiente['id']);
        }
        $stmt_update->execute();
        $stmt_update->close();

        // Verificar que el ganador está en el partido siguiente
        $stmt_verificar = $conn->prepare("SELECT participante_local_id, participante_visitante_id FROM partidos WHERE id = ?");
        $stmt_verificar->bind_param("i", $partido_siguiente['id']);
        $stmt_verificar->execute();
        $partido_actualizado = $stmt_verificar->get_result()->fetch_assoc();
        $stmt_verificar->close();

        // Verificar que el ganador está asignado correctamente
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

    // Obtener todos los partidos de la fase actual ordenados por ID
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

    // Mapeo: Cuartos 1,2 -> Semi 1 | Cuartos 3,4 -> Semi 2 | Semi 1,2 -> Final 1
    if ($tipo_fase_actual == 2) { // Cuartos
        return ($posicion_actual <= 2) ? 1 : 2;
    } else { // Semis
        return 1; // Siempre va a la final (posición 1)
    }
}

function esLocalEnSiguienteFase($partido_id, $torneo_id, $tipo_fase_actual) {
    global $conn;

    // Obtener todos los partidos de la fase actual ordenados por ID
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

    // Cuartos 1 y 3 son locales en semis, Cuartos 2 y 4 son visitantes
    // Semi 1 es local en final, Semi 2 es visitante
    if ($tipo_fase_actual == 2) { // Cuartos
        return ($posicion == 1 || $posicion == 3);
    } else { // Semis
        return ($posicion == 1);
    }
}
?>
