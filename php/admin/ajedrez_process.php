<?php
require_once 'auth_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
$partido_id = (int)($_POST['partido_id'] ?? 0);

if (!$partido_id) {
    echo json_encode(['success' => false, 'error' => 'ID de partido no especificado']);
    exit;
}

try {
    if ($action == 'definir_ganador') {
        $tipo_resultado = $_POST['tipo_resultado'] ?? ''; 

        
        $stmt = $conn->prepare("SELECT jugador_local_id, jugador_visitante_id FROM partidos WHERE id = ?");
        $stmt->bind_param("i", $partido_id);
        $stmt->execute();
        $partido = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$partido) {
            echo json_encode(['success' => false, 'error' => 'Partido no encontrado']);
            exit;
        }

        
        $ganador_individual_id = null;
        $marcador_local = 0;
        $marcador_visitante = 0;

        switch($tipo_resultado) {
            case 'local':
                $ganador_individual_id = $partido['jugador_local_id'];
                $marcador_local = 1;
                $marcador_visitante = 0;
                break;
            case 'visitante':
                $ganador_individual_id = $partido['jugador_visitante_id'];
                $marcador_local = 0;
                $marcador_visitante = 1;
                break;
            case 'empate':
                $ganador_individual_id = 0; 
                $marcador_local = 0;
                $marcador_visitante = 0;
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Tipo de resultado inválido']);
                exit;
        }

        
        $stmt_update = $conn->prepare("UPDATE partidos SET
            ganador_individual_id = ?,
            marcador_local = ?,
            marcador_visitante = ?,
            estado_id = 5
            WHERE id = ?");
        $stmt_update->bind_param("iiii", $ganador_individual_id, $marcador_local, $marcador_visitante, $partido_id);

        if ($stmt_update->execute()) {
            $stmt_update->close();

            echo json_encode([
                'success' => true,
                'mensaje' => 'Resultado guardado exitosamente',
                'marcador_local' => $marcador_local,
                'marcador_visitante' => $marcador_visitante,
                'ganador' => $tipo_resultado
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar el partido']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
