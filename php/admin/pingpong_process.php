<?php
require_once 'auth_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
$partido_id = (int)($_POST['partido_id'] ?? 0);

$set_numero = ($action === 'sumar_punto' || $action === 'restar_punto' || $action === 'finalizar_set_manual')
    ? (int)($_POST['set_numero'] ?? 0)
    : null;



if (!$partido_id) {
     echo json_encode(['success' => false, 'error' => 'ID de partido inválido']);
     exit;
}

if (($action === 'sumar_punto' || $action === 'restar_punto' || $action === 'finalizar_set_manual') && !$set_numero) {
    echo json_encode(['success' => false, 'error' => 'Número de set inválido']);
    exit;
}


function recalcularYGuardarSetsGanados($conn, $partido_id) {
    
    $stmt_sets = $conn->prepare("SELECT ganador_id FROM puntos_set WHERE partido_id = ? AND finalizado = 1");
    $stmt_sets->bind_param("i", $partido_id);
    $stmt_sets->execute();
    $result_sets = $stmt_sets->get_result();

    
    $stmt_partido = $conn->prepare("SELECT jugador_local_id, jugador_visitante_id FROM partidos WHERE id = ?");
    $stmt_partido->bind_param("i", $partido_id);
    $stmt_partido->execute();
    $partido_jugadores = $stmt_partido->get_result()->fetch_assoc();
    $stmt_partido->close();

    
    if (!$partido_jugadores || !$partido_jugadores['jugador_local_id'] || !$partido_jugadores['jugador_visitante_id']) {
         error_log("Error: No se encontraron jugadores para el partido ID $partido_id al recalcular sets.");
         
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

    
    $stmt_update = $conn->prepare("UPDATE partidos SET sets_ganados_local = ?, sets_ganados_visitante = ? WHERE id = ?");
    $stmt_update->bind_param("iii", $sets_local, $sets_visitante, $partido_id);
    $stmt_update->execute();
    $stmt_update->close();

    
    return ['sets_ganados_local' => $sets_local, 'sets_ganados_visitante' => $sets_visitante];
}

try {
    if ($action == 'sumar_punto') {
        $tipo = $_POST['tipo']; 
        if ($tipo !== 'local' && $tipo !== 'visitante') {
             throw new Exception("Tipo inválido.");
        }
        $campo = ($tipo == 'local') ? 'puntos_local' : 'puntos_visitante';

        
        $stmt_partido = $conn->prepare("SELECT jugador_local_id, jugador_visitante_id FROM partidos WHERE id = ?");
        $stmt_partido->bind_param("i", $partido_id);
        $stmt_partido->execute();
        $partido = $stmt_partido->get_result()->fetch_assoc();
        $stmt_partido->close();
        if (!$partido || !$partido['jugador_local_id'] || !$partido['jugador_visitante_id']) {
             throw new Exception("No se pudieron obtener los jugadores del partido.");
        }


        
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
            
            $nuevo_valor = $set_existente[$campo] + 1;
            $stmt_update = $conn->prepare("UPDATE puntos_set SET $campo = ? WHERE partido_id = ? AND set_numero = ?");
            $stmt_update->bind_param("iii", $nuevo_valor, $partido_id, $set_numero);
            $stmt_update->execute();
            $stmt_update->close();

            $puntos_local = ($tipo == 'local') ? $nuevo_valor : $set_existente['puntos_local'];
            $puntos_visitante = ($tipo == 'visitante') ? $nuevo_valor : $set_existente['puntos_visitante'];
        } else {
            
            $puntos_local = ($tipo == 'local') ? 1 : 0;
            $puntos_visitante = ($tipo == 'visitante') ? 1 : 0;

            $stmt_insert = $conn->prepare("INSERT INTO puntos_set (partido_id, set_numero, jugador_local_id, jugador_visitante_id, puntos_local, puntos_visitante) VALUES (?, ?, ?, ?, ?, ?)");
            
            $stmt_insert->bind_param("iiiiii", $partido_id, $set_numero, $partido['jugador_local_id'], $partido['jugador_visitante_id'], $puntos_local, $puntos_visitante);
            $stmt_insert->execute();
            $stmt_insert->close();
        }

        
        $set_finalizado = false;
        $ganador_id = null;
        $sets_totales = null; 

        if (($puntos_local >= 11 || $puntos_visitante >= 11) && abs($puntos_local - $puntos_visitante) >= 2) {
            $set_finalizado = true;
            $ganador_id = ($puntos_local > $puntos_visitante) ? $partido['jugador_local_id'] : $partido['jugador_visitante_id'];

            
            $stmt_finalizar = $conn->prepare("UPDATE puntos_set SET finalizado = 1, ganador_id = ? WHERE partido_id = ? AND set_numero = ?");
            $stmt_finalizar->bind_param("iii", $ganador_id, $partido_id, $set_numero);
            $stmt_finalizar->execute();
            $stmt_finalizar->close();

            
            
            $sets_totales = recalcularYGuardarSetsGanados($conn, $partido_id);

            
            







             
             $stmt_next_set = $conn->prepare("UPDATE partidos SET set_actual = GREATEST(set_actual, ? + 1) WHERE id = ?");
             $stmt_next_set->bind_param("ii", $set_numero, $partido_id);
             $stmt_next_set->execute();
             $stmt_next_set->close();


            
             
            if ($sets_totales && ($sets_totales['sets_ganados_local'] >= 3 || $sets_totales['sets_ganados_visitante'] >= 3)) {
                $ganador_partido_id = ($sets_totales['sets_ganados_local'] >= 3) ? $partido['jugador_local_id'] : $partido['jugador_visitante_id'];
                
                $stmt_ganador = $conn->prepare("UPDATE partidos SET ganador_individual_id = ?, estado_id = 5 WHERE id = ?");
                $stmt_ganador->bind_param("ii", $ganador_partido_id, $partido_id);
                $stmt_ganador->execute();
                $stmt_ganador->close();
            }
        }

        
        $response_data = [
            'success' => true,
            'puntos_local' => $puntos_local,
            'puntos_visitante' => $puntos_visitante,
            'set_finalizado' => $set_finalizado,
            'ganador_id' => $ganador_id
        ];
        
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
                
            ]);
        } else {
             
             echo json_encode([
                 'success' => true, 
                 'puntos_local' => $set_existente['puntos_local'] ?? 0,
                 'puntos_visitante' => $set_existente['puntos_visitante'] ?? 0,
                 'error' => 'No se puede restar, puntos en 0 o set no iniciado'
             ]);
        }

    } elseif ($action == 'finalizar_set_manual') {
        
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

        
        $stmt_partido = $conn->prepare("SELECT jugador_local_id, jugador_visitante_id FROM partidos WHERE id = ?");
        $stmt_partido->bind_param("i", $partido_id);
        $stmt_partido->execute();
        $partido = $stmt_partido->get_result()->fetch_assoc();
        $stmt_partido->close();
         if (!$partido || !$partido['jugador_local_id'] || !$partido['jugador_visitante_id']) {
             throw new Exception("No se pudieron obtener los jugadores del partido.");
         }

        $ganador_id = ($set_existente['puntos_local'] > $set_existente['puntos_visitante']) ? $partido['jugador_local_id'] : $partido['jugador_visitante_id'];

        
        $stmt_finalizar = $conn->prepare("UPDATE puntos_set SET finalizado = 1, ganador_id = ? WHERE partido_id = ? AND set_numero = ?");
        $stmt_finalizar->bind_param("iii", $ganador_id, $partido_id, $set_numero);
        $stmt_finalizar->execute();
        $stmt_finalizar->close();

        
        
        $sets_totales = recalcularYGuardarSetsGanados($conn, $partido_id);

        
        







         
         $stmt_next_set = $conn->prepare("UPDATE partidos SET set_actual = GREATEST(set_actual, ? + 1) WHERE id = ?");
         $stmt_next_set->bind_param("ii", $set_numero, $partido_id);
         $stmt_next_set->execute();
         $stmt_next_set->close();


        
         
        if ($sets_totales && ($sets_totales['sets_ganados_local'] >= 3 || $sets_totales['sets_ganados_visitante'] >= 3)) {
            $ganador_partido_id = ($sets_totales['sets_ganados_local'] >= 3) ? $partido['jugador_local_id'] : $partido['jugador_visitante_id'];
            $stmt_ganador = $conn->prepare("UPDATE partidos SET ganador_individual_id = ?, estado_id = 5 WHERE id = ?"); 
            $stmt_ganador->bind_param("ii", $ganador_partido_id, $partido_id);
            $stmt_ganador->execute();
            $stmt_ganador->close();
        }

        echo json_encode([
            'success' => true,
            'mensaje' => 'Set finalizado manualmente',
            'sets_ganados' => $sets_totales 
        ]);
    } else {
         echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }

} catch (Exception $e) {
    
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}








$conn->close();
?>