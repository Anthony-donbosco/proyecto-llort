<?php
require_once 'auth_admin.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$partido_id = (int)($_POST['partido_id'] ?? $_GET['partido_id'] ?? 0);

if (!$partido_id) {
    echo json_encode(['success' => false, 'error' => 'ID de partido no especificado']);
    exit;
}


function obtenerCronometro($conn, $partido_id) {
    $stmt = $conn->prepare("SELECT * FROM cronometro_partido WHERE partido_id = ?");
    $stmt->bind_param("i", $partido_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cronometro = $result->fetch_assoc();
    $stmt->close();

    
    if (!$cronometro) {
        $stmt = $conn->prepare("INSERT INTO cronometro_partido (partido_id, estado_cronometro, tiempo_transcurrido, periodo_actual)
                                VALUES (?, 'detenido', 0, '1er Tiempo')");
        $stmt->bind_param("i", $partido_id);
        $stmt->execute();
        $stmt->close();

        return obtenerCronometro($conn, $partido_id);
    }

    return $cronometro;
}


if ($action == 'obtener_estado') {
    $cronometro = obtenerCronometro($conn, $partido_id);

    
    if ($cronometro['estado_cronometro'] == 'corriendo' && $cronometro['tiempo_inicio']) {
        
        $stmt = $conn->prepare("SELECT UNIX_TIMESTAMP(tiempo_inicio) as inicio_unix FROM cronometro_partido WHERE partido_id = ?");
        $stmt->bind_param("i", $partido_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tiempo_data = $result->fetch_assoc();
        $stmt->close();

        $inicio_unix = (int)$tiempo_data['inicio_unix'];
        $ahora = time();
        $segundos_corriendo = $ahora - $inicio_unix;

        
        $tiempo_base = (int)$cronometro['tiempo_transcurrido'];
        $cronometro['tiempo_transcurrido'] = $tiempo_base + $segundos_corriendo;
    } else {
        
        $cronometro['tiempo_transcurrido'] = (int)$cronometro['tiempo_transcurrido'];
    }

    echo json_encode([
        'success' => true,
        'cronometro' => $cronometro
    ]);
    exit;
}


if ($action == 'iniciar') {
    $cronometro = obtenerCronometro($conn, $partido_id);

    
    if ($cronometro['estado_cronometro'] == 'pausado') {
        $stmt = $conn->prepare("UPDATE cronometro_partido
                                SET estado_cronometro = 'corriendo',
                                    tiempo_inicio = NOW(),
                                    tiempo_pausa = NULL
                                WHERE partido_id = ?");
        $stmt->bind_param("i", $partido_id);
    } else {
        
        $stmt = $conn->prepare("UPDATE cronometro_partido
                                SET estado_cronometro = 'corriendo',
                                    tiempo_inicio = NOW(),
                                    tiempo_transcurrido = 0,
                                    tiempo_pausa = NULL
                                WHERE partido_id = ?");
        $stmt->bind_param("i", $partido_id);
    }

    $stmt->execute();
    $stmt->close();

    
    $stmt_partido = $conn->prepare("UPDATE partidos SET estado_id = 3 WHERE id = ?");
    $stmt_partido->bind_param("i", $partido_id);
    $stmt_partido->execute();
    $stmt_partido->close();

    echo json_encode(['success' => true, 'message' => 'Cronómetro iniciado']);
    exit;
}


if ($action == 'pausar') {
    $cronometro = obtenerCronometro($conn, $partido_id);

    if ($cronometro['estado_cronometro'] == 'corriendo') {
        
        $stmt = $conn->prepare("SELECT UNIX_TIMESTAMP(tiempo_inicio) as inicio_unix FROM cronometro_partido WHERE partido_id = ?");
        $stmt->bind_param("i", $partido_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tiempo_data = $result->fetch_assoc();
        $stmt->close();

        $inicio_unix = (int)$tiempo_data['inicio_unix'];
        $ahora = time();
        $segundos_corriendo = $ahora - $inicio_unix;
        $tiempo_total = $cronometro['tiempo_transcurrido'] + $segundos_corriendo;

        $stmt = $conn->prepare("UPDATE cronometro_partido
                                SET estado_cronometro = 'pausado',
                                    tiempo_transcurrido = ?,
                                    tiempo_pausa = NOW()
                                WHERE partido_id = ?");
        $stmt->bind_param("ii", $tiempo_total, $partido_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Cronómetro pausado', 'tiempo' => $tiempo_total]);
    } else {
        echo json_encode(['success' => false, 'error' => 'El cronómetro no está corriendo']);
    }
    exit;
}


if ($action == 'cambiar_periodo') {
    $periodo = $_POST['periodo'] ?? '';

    if (empty($periodo)) {
        echo json_encode(['success' => false, 'error' => 'Periodo no especificado']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE cronometro_partido SET periodo_actual = ? WHERE partido_id = ?");
    $stmt->bind_param("si", $periodo, $partido_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Periodo actualizado']);
    exit;
}


if ($action == 'agregar_tiempo') {
    $minutos = (int)($_POST['minutos'] ?? 0);

    $stmt = $conn->prepare("UPDATE cronometro_partido SET tiempo_agregado = ? WHERE partido_id = ?");
    $stmt->bind_param("ii", $minutos, $partido_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Tiempo agregado actualizado']);
    exit;
}


if ($action == 'reiniciar') {
    $stmt = $conn->prepare("UPDATE cronometro_partido
                            SET estado_cronometro = 'detenido',
                                tiempo_transcurrido = 0,
                                tiempo_inicio = NULL,
                                tiempo_pausa = NULL,
                                periodo_actual = '1er Tiempo',
                                tiempo_agregado = 0
                            WHERE partido_id = ?");
    $stmt->bind_param("i", $partido_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Cronómetro reiniciado']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no válida']);
$conn->close();
?>
