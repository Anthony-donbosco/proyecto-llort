<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../auth_user.php';

$action = $_GET['action'] ?? '';
$partido_id = (int)($_GET['partido_id'] ?? 0);

if ($partido_id === 0) {
    echo json_encode(['success' => false, 'error' => 'ID de partido no especificado']);
    exit;
}

if ($action !== 'obtener_estado') {
    echo json_encode(['success' => false, 'error' => 'Acción no permitida para usuarios.']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM cronometro_partido WHERE partido_id = ?");
    $stmt->bind_param("i", $partido_id);
    $stmt->execute();
    $cronometro = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cronometro) {
        $cronometro_default = [
            'partido_id' => $partido_id,
            'estado_cronometro' => 'detenido',
            'tiempo_transcurrido' => 0,
            'tiempo_agregado' => 0,
            'periodo_actual' => 'Detenido'
        ];
        echo json_encode(['success' => true, 'cronometro' => $cronometro_default]);
        exit;
    }
    if ($cronometro['estado_cronometro'] == 'corriendo' && $cronometro['tiempo_inicio']) {
        
        $stmt_ts = $conn->prepare("SELECT UNIX_TIMESTAMP(tiempo_inicio) as inicio_unix FROM cronometro_partido WHERE partido_id = ?");
        $stmt_ts->bind_param("i", $partido_id);
        $stmt_ts->execute();
        $tiempo_data = $stmt_ts->get_result()->fetch_assoc();
        $stmt_ts->close();

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

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
}

$conn->close();
exit;
?>