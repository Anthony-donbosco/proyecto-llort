<?php
require_once 'auth_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
$partido_id = (int)($_POST['partido_id'] ?? 0);
// Solo necesitamos set_numero si la acción lo requiere
$set_numero = ($action === 'sumar_punto' || $action === 'restar_punto' || $action === 'finalizar_set_manual')
    ? (int)($_POST['set_numero'] ?? 0)
    : null;


// Validar parámetros base
if (!$partido_id) {
     echo json_encode(['success' => false, 'error' => 'ID de partido inválido']);
     exit;
}
// Validar set_numero solo si es necesario para la acción
if (($action === 'sumar_punto' || $action === 'restar_punto' || $action === 'finalizar_set_manual') && !$set_numero) {
    echo json_encode(['success' => false, 'error' => 'Número de set inválido']);
    exit;
}


function recalcularYGuardarSetsGanados($conn, $partido_id) {
    // Consultar todos los sets finalizados para este partido
    $stmt_sets = $conn->prepare("SELECT ganador_id FROM puntos_set WHERE partido_id = ? AND finalizado = 1");
    $stmt_sets->bind_param("i", $partido_id);
    $stmt_sets->execute();
    $result_sets = $stmt_sets->get_result();

    // Obtener los IDs de los jugadores del partido (asegurarse que existen)
    $stmt_partido = $conn->prepare("SELECT jugador_local_id, jugador_visitante_id FROM partidos WHERE id = ?");
    $stmt_partido->bind_param("i", $partido_id);
    $stmt_partido->execute();
    $partido_jugadores = $stmt_partido->get_result()->fetch_assoc();
    $stmt_partido->close();

    // Si no se encuentran los jugadores (error raro), devolver 0
    if (!$partido_jugadores || !$partido_jugadores['jugador_local_id'] || !$partido_jugadores['jugador_visitante_id']) {
         error_log("Error: No se encontraron jugadores para el partido ID $partido_id al recalcular sets.");
         // Intentar guardar 0 para evitar fallos, pero registrar el error
         $stmt_update_error = $conn->prepare("UPDATE partidos SET sets_ganados_local = 0, sets_ganados_visitante = 0 WHERE id = ?");
         $stmt_update_error->bind_param("i", $partido_id);
         $stmt_update_error->execute();
         $stmt_update_error->close();
         return ['sets_ganados_local' => 0, 'sets_ganados_visitante' => 0];
    }

    $sets_local = 0;
    $sets_visitante = 0;

    while ($set = $result_sets->fetch_assoc()) {
        if ($set['ganador_id'] == $partido_jugadores['jugador_local_id']) {
            $sets_local++;
        } elseif ($set['ganador_id'] == $partido_jugadores['jugador_visitante_id']) {
            $sets_visitante++;
        }
    }
    $stmt_sets->close();

    // Guardar en la tabla partidos
    $stmt_update = $conn->prepare("UPDATE partidos SET sets_ganados_local = ?, sets_ganados_visitante = ? WHERE id = ?");
    $stmt_update->bind_param("iii", $sets_local, $sets_visitante, $partido_id);
    $stmt_update->execute();
    $stmt_update->close();

    // Devolver los valores actualizados
    return ['sets_ganados_local' => $sets_local, 'sets_ganados_visitante' => $sets_visitante];
}

try {
    if ($action == 'sumar_punto') {
        $tipo = $_POST['tipo']; // 'local' o 'visitante'
        if ($tipo !== 'local' && $tipo !== 'visitante') {
             throw new Exception("Tipo inválido.");
        }
        $campo = ($tipo == 'local') ? 'puntos_local' : 'puntos_visitante';

        // Obtener jugadores del partido (necesario para verificar ganador y para insertar si no existe)
        $stmt_partido = $conn->prepare("SELECT jugador_local_id, jugador_visitante_id FROM partidos WHERE id = ?");
        $stmt_partido->bind_param("i", $partido_id);
        $stmt_partido->execute();
        $partido = $stmt_partido->get_result()->fetch_assoc();
        $stmt_partido->close();
        if (!$partido || !$partido['jugador_local_id'] || !$partido['jugador_visitante_id']) {
             throw new Exception("No se pudieron obtener los jugadores del partido.");
        }


        // Verificar si el set existe
        $stmt_check = $conn->prepare("SELECT * FROM puntos_set WHERE partido_id = ? AND set_numero = ?");
        $stmt_check->bind_param("ii", $partido_id, $set_numero);
        $stmt_check->execute();
        $set_existente = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        $puntos_local = 0;
        $puntos_visitante = 0;

        if ($set_existente) {
             if ($set_existente['finalizado']) {
                 throw new Exception("El set ya está finalizado.");
             }
            // Actualizar
            $nuevo_valor = $set_existente[$campo] + 1;
            $stmt_update = $conn->prepare("UPDATE puntos_set SET $campo = ? WHERE partido_id = ? AND set_numero = ?");
            $stmt_update->bind_param("iii", $nuevo_valor, $partido_id, $set_numero);
            $stmt_update->execute();
            $stmt_update->close();

            $puntos_local = ($tipo == 'local') ? $nuevo_valor : $set_existente['puntos_local'];
            $puntos_visitante = ($tipo == 'visitante') ? $nuevo_valor : $set_existente['puntos_visitante'];
        } else {
            // Crear nuevo set
            $puntos_local = ($tipo == 'local') ? 1 : 0;
            $puntos_visitante = ($tipo == 'visitante') ? 1 : 0;

            $stmt_insert = $conn->prepare("INSERT INTO puntos_set (partido_id, set_numero, jugador_local_id, jugador_visitante_id, puntos_local, puntos_visitante) VALUES (?, ?, ?, ?, ?, ?)");
            // Usamos los IDs obtenidos al inicio
            $stmt_insert->bind_param("iiiiii", $partido_id, $set_numero, $partido['jugador_local_id'], $partido['jugador_visitante_id'], $puntos_local, $puntos_visitante);
            $stmt_insert->execute();
            $stmt_insert->close();
        }

        // Verificar si el set terminó (11 puntos con ventaja de 2)
        $set_finalizado = false;
        $ganador_id = null;
        $sets_totales = null; // Inicializar

        if (($puntos_local >= 11 || $puntos_visitante >= 11) && abs($puntos_local - $puntos_visitante) >= 2) {
            $set_finalizado = true;
            $ganador_id = ($puntos_local > $puntos_visitante) ? $partido['jugador_local_id'] : $partido['jugador_visitante_id'];

            // Marcar set como finalizado
            $stmt_finalizar = $conn->prepare("UPDATE puntos_set SET finalizado = 1, ganador_id = ? WHERE partido_id = ? AND set_numero = ?");
            $stmt_finalizar->bind_param("iii", $ganador_id, $partido_id, $set_numero);
            $stmt_finalizar->execute();
            $stmt_finalizar->close();

            // --- MOVIDO Y MODIFICADO ---
            // Recalcular y guardar sets totales AHORA que el set terminó
            $sets_totales = recalcularYGuardarSetsGanados($conn, $partido_id);

            // --- ELIMINADO --- (La función anterior ya lo hizo)
            /*
            $campo_sets = ($ganador_id == $partido['jugador_local_id']) ? 'sets_ganados_local' : 'sets_ganados_visitante';
            $stmt_sets = $conn->prepare("UPDATE partidos SET $campo_sets = $campo_sets + 1, set_actual = set_actual + 1 WHERE id = ?");
            $stmt_sets->bind_param("i", $partido_id);
            $stmt_sets->execute();
            $stmt_sets->close();
            */

             // Actualizar set_actual (siguiente set)
             $stmt_next_set = $conn->prepare("UPDATE partidos SET set_actual = GREATEST(set_actual, ? + 1) WHERE id = ?");
             $stmt_next_set->bind_param("ii", $set_numero, $partido_id);
             $stmt_next_set->execute();
             $stmt_next_set->close();


            // Verificar si alguien ganó el partido (mejor de 5: primer a 3 sets)
             // Usamos $sets_totales que viene de la función recalcularYGuardarSetsGanados
            if ($sets_totales && ($sets_totales['sets_ganados_local'] >= 3 || $sets_totales['sets_ganados_visitante'] >= 3)) {
                $ganador_partido_id = ($sets_totales['sets_ganados_local'] >= 3) ? $partido['jugador_local_id'] : $partido['jugador_visitante_id'];
                // Marcar también como finalizado estado_id = 5
                $stmt_ganador = $conn->prepare("UPDATE partidos SET ganador_individual_id = ?, estado_id = 5 WHERE id = ?");
                $stmt_ganador->bind_param("ii", $ganador_partido_id, $partido_id);
                $stmt_ganador->execute();
                $stmt_ganador->close();
            }
        }

        // Construir respuesta
        $response_data = [
            'success' => true,
            'puntos_local' => $puntos_local,
            'puntos_visitante' => $puntos_visitante,
            'set_finalizado' => $set_finalizado,
            'ganador_id' => $ganador_id
        ];
        // Añadir sets_ganados solo si se calcularon (porque el set terminó)
        if ($sets_totales !== null) {
            $response_data['sets_ganados'] = $sets_totales;
        }

        echo json_encode($response_data);

    } elseif ($action == 'restar_punto') {
        $tipo = $_POST['tipo'];
        if ($tipo !== 'local' && $tipo !== 'visitante') {
             throw new Exception("Tipo inválido.");
        }
        $campo = ($tipo == 'local') ? 'puntos_local' : 'puntos_visitante';

        $stmt_check = $conn->prepare("SELECT * FROM puntos_set WHERE partido_id = ? AND set_numero = ?");
        $stmt_check->bind_param("ii", $partido_id, $set_numero);
        $stmt_check->execute();
        $set_existente = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        // No restar si el set está finalizado
         if ($set_existente && $set_existente['finalizado']) {
             throw new Exception("No se puede restar puntos a un set finalizado.");
         }

        if ($set_existente && $set_existente[$campo] > 0) {
            $nuevo_valor = $set_existente[$campo] - 1;
            $stmt_update = $conn->prepare("UPDATE puntos_set SET $campo = ? WHERE partido_id = ? AND set_numero = ?");
            $stmt_update->bind_param("iii", $nuevo_valor, $partido_id, $set_numero);
            $stmt_update->execute();
            $stmt_update->close();

            echo json_encode([
                'success' => true,
                'puntos_local' => ($tipo == 'local') ? $nuevo_valor : $set_existente['puntos_local'],
                'puntos_visitante' => ($tipo == 'visitante') ? $nuevo_valor : $set_existente['puntos_visitante']
                // No necesitamos devolver sets_ganados aquí
            ]);
        } else {
             // Devolver los puntos actuales si no se pudo restar (ya estaban en 0 o no existía el set)
             echo json_encode([
                 'success' => true, // O false si prefieres indicar que no cambió
                 'puntos_local' => $set_existente['puntos_local'] ?? 0,
                 'puntos_visitante' => $set_existente['puntos_visitante'] ?? 0,
                 'error' => 'No se puede restar, puntos en 0 o set no iniciado'
             ]);
        }

    } elseif ($action == 'finalizar_set_manual') {
        // Finalizar set manualmente
        $stmt_check = $conn->prepare("SELECT * FROM puntos_set WHERE partido_id = ? AND set_numero = ?");
        $stmt_check->bind_param("ii", $partido_id, $set_numero);
        $stmt_check->execute();
        $set_existente = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$set_existente || ($set_existente['puntos_local'] == 0 && $set_existente['puntos_visitante'] == 0) ) {
            throw new Exception("El set no existe o no tiene puntos para finalizar.");
        }
        if ($set_existente['finalizado']) {
            throw new Exception("El set ya está finalizado.");
        }
        if ($set_existente['puntos_local'] == $set_existente['puntos_visitante']) {
             throw new Exception("No se puede finalizar con empate.");
        }

        // Obtener jugadores del partido
        $stmt_partido = $conn->prepare("SELECT jugador_local_id, jugador_visitante_id FROM partidos WHERE id = ?");
        $stmt_partido->bind_param("i", $partido_id);
        $stmt_partido->execute();
        $partido = $stmt_partido->get_result()->fetch_assoc();
        $stmt_partido->close();
         if (!$partido || !$partido['jugador_local_id'] || !$partido['jugador_visitante_id']) {
             throw new Exception("No se pudieron obtener los jugadores del partido.");
         }

        $ganador_id = ($set_existente['puntos_local'] > $set_existente['puntos_visitante']) ? $partido['jugador_local_id'] : $partido['jugador_visitante_id'];

        // Finalizar el set
        $stmt_finalizar = $conn->prepare("UPDATE puntos_set SET finalizado = 1, ganador_id = ? WHERE partido_id = ? AND set_numero = ?");
        $stmt_finalizar->bind_param("iii", $ganador_id, $partido_id, $set_numero);
        $stmt_finalizar->execute();
        $stmt_finalizar->close();

        // --- MOVIDO ---
        // Recalcular y guardar sets totales AHORA
        $sets_totales = recalcularYGuardarSetsGanados($conn, $partido_id);

        // --- ELIMINADO ---
        /*
        $campo_sets = ($ganador_id == $partido['jugador_local_id']) ? 'sets_ganados_local' : 'sets_ganados_visitante';
        $stmt_sets = $conn->prepare("UPDATE partidos SET $campo_sets = $campo_sets + 1, set_actual = set_actual + 1 WHERE id = ?");
        $stmt_sets->bind_param("i", $partido_id);
        $stmt_sets->execute();
        $stmt_sets->close();
        */

         // Actualizar set_actual (siguiente set)
         $stmt_next_set = $conn->prepare("UPDATE partidos SET set_actual = GREATEST(set_actual, ? + 1) WHERE id = ?");
         $stmt_next_set->bind_param("ii", $set_numero, $partido_id);
         $stmt_next_set->execute();
         $stmt_next_set->close();


        // Verificar si alguien ganó el partido (mejor de 5: primero a 3 sets)
         // Usamos $sets_totales
        if ($sets_totales && ($sets_totales['sets_ganados_local'] >= 3 || $sets_totales['sets_ganados_visitante'] >= 3)) {
            $ganador_partido_id = ($sets_totales['sets_ganados_local'] >= 3) ? $partido['jugador_local_id'] : $partido['jugador_visitante_id'];
            $stmt_ganador = $conn->prepare("UPDATE partidos SET ganador_individual_id = ?, estado_id = 5 WHERE id = ?"); // Marcar como finalizado
            $stmt_ganador->bind_param("ii", $ganador_partido_id, $partido_id);
            $stmt_ganador->execute();
            $stmt_ganador->close();
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Set finalizado manualmente',
            'sets_ganados' => $sets_totales // Devolver los sets actualizados
        ]);
    } else {
         echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }

} catch (Exception $e) {
    // Capturar cualquier excepción para asegurar respuesta JSON
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// --- ELIMINADO EL BLOQUE FINAL ---
/*
$sets_actualizados = recalcularYGuardarSetsGanados($conn, $partido_id);
// Añadir los sets actualizados a la respuesta JSON
$response['sets_ganados'] = $sets_actualizados;
*/

$conn->close();
?>