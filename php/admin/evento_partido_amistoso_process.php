<?php
require_once 'auth_admin.php'; 

header('Content-Type: application/json');

$partido_id = isset($_POST['partido_id']) ? (int)$_POST['partido_id'] : (isset($_GET['partido_id']) ? (int)$_GET['partido_id'] : 0);

if (!$partido_id) {
    echo json_encode(['success' => false, 'error' => 'ID de partido no especificado']);
    exit;
}

function recalcularMarcadores_Amistoso($conn, $partido_id) {
    
    $sql_partido = "SELECT p.participante_local_id, p.participante_visitante_id,
                    d.tipo_puntuacion
                    FROM partidos p
                    JOIN participantes pl ON p.participante_local_id = pl.id
                    JOIN deportes d ON pl.deporte_id = d.id
                    WHERE p.id = ? AND p.torneo_id IS NULL";
    
    $stmt_partido = $conn->prepare($sql_partido);
    if (!$stmt_partido) {
         error_log("Error al preparar consulta de partido: " . $conn->error);
         return ['local' => 0, 'visitante' => 0];
    }
    $stmt_partido->bind_param("i", $partido_id);
    $stmt_partido->execute();
    $partido = $stmt_partido->get_result()->fetch_assoc();
    $stmt_partido->close();

    if (!$partido) {
        error_log("Partido amistoso no encontrado para recalcular: ID $partido_id");
        return ['local' => 0, 'visitante' => 0];
    }

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
        $participante_evento_id = $evento['participante_id'];
        $es_local = ($participante_evento_id == $partido['participante_local_id']);

        if ($tipo_puntuacion == 'goles') {
            $es_gol_valido = in_array($tipo, ['gol', 'penal_anotado']);
            $es_autogol = ($tipo == 'autogol');

            if ($es_local) {
                if ($es_gol_valido) $marcador_local++;
                elseif ($es_autogol) $marcador_visitante++; 
            } else {
                if ($es_gol_valido) $marcador_visitante++;
                elseif ($es_autogol) $marcador_local++;
            }
        } 
        elseif ($tipo_puntuacion == 'puntos') {
            $eventos_puntos = ['canasta_1pt', 'canasta_2pt', 'canasta_3pt', 'punto', 'ace', 'bloqueo'];
            if (in_array($tipo, $eventos_puntos)) {
                if ($es_local) {
                    $marcador_local += $puntos;
                } else {
                    $marcador_visitante += $puntos;
                }
            }
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
    
    $jugador_id = isset($_POST['jugador_id']) ? (int)$_POST['jugador_id'] : 0;
    $asistencia_id = !empty($_POST['asistencia_id']) ? (int)$_POST['asistencia_id'] : NULL;
    $minuto = !empty($_POST['minuto']) ? trim($_POST['minuto']) : NULL;
    $tipo_evento = isset($_POST['tipo_evento']) ? $_POST['tipo_evento'] : '';
    $valor_puntos = isset($_POST['valor_puntos']) ? (int)$_POST['valor_puntos'] : 1;

    if (!$jugador_id || !$tipo_evento) {
        echo json_encode(['success' => false, 'error' => 'Faltan datos (Jugador o Tipo de evento)']);
        exit;
    }

    if ($asistencia_id !== NULL && $asistencia_id == $jugador_id) {
        echo json_encode(['success' => false, 'error' => 'El jugador que asiste no puede ser el mismo que anota.']);
        exit;
    }

    try {
        $sql = "INSERT INTO eventos_partido (partido_id, miembro_plantel_id, asistencia_miembro_plantel_id, tipo_evento, valor_puntos, minuto)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisis", $partido_id, $jugador_id, $asistencia_id, $tipo_evento, $valor_puntos, $minuto);
        $stmt->execute();
        $stmt->close();

        $marcadores = recalcularMarcadores_Amistoso($conn, $partido_id);

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
    
    $evento_id = isset($_POST['evento_id']) ? (int)$_POST['evento_id'] : 0;

    if (!$evento_id) {
        echo json_encode(['success' => false, 'error' => 'ID de evento no especificado']);
        exit;
    }

    try {
        $sql_delete = "DELETE FROM eventos_partido WHERE id = ? AND partido_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $evento_id, $partido_id);
        $stmt_delete->execute();
        
        if ($stmt_delete->affected_rows > 0) {
            $stmt_delete->close();
            $marcadores = recalcularMarcadores_Amistoso($conn, $partido_id);
            echo json_encode([
                'success' => true,
                'mensaje' => 'Evento eliminado exitosamente',
                'nuevoMarcadorLocal' => $marcadores['local'],
                'nuevoMarcadorVisitante' => $marcadores['visitante']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Evento no encontrado o no pertenece a este partido']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar evento: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no válida']);
$conn->close();
?>