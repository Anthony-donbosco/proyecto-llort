<?php
require_once 'auth_admin.php';

$partido_id = isset($_POST['partido_id']) ? (int)$_POST['partido_id'] : (isset($_GET['partido_id']) ? (int)$_GET['partido_id'] : 0);

if (!$partido_id) {
    header("Location: gestionar_torneos.php?error=ID de partido no especificado.");
    exit;
}


function recalcularMarcadores($conn, $partido_id) {
    
    $sql_partido = "SELECT participante_local_id, participante_visitante_id FROM partidos WHERE id = ?";
    $stmt_partido = $conn->prepare($sql_partido);
    $stmt_partido->bind_param("i", $partido_id);
    $stmt_partido->execute();
    $partido = $stmt_partido->get_result()->fetch_assoc();
    $stmt_partido->close();

    if (!$partido) return;

    
    $sql_eventos = "SELECT ep.tipo_evento, pe.participante_id
                    FROM eventos_partido ep
                    JOIN miembros_plantel mp ON ep.miembro_plantel_id = mp.id
                    JOIN planteles_equipo pe ON mp.plantel_id = pe.id
                    WHERE ep.partido_id = ?";
    $stmt_eventos = $conn->prepare($sql_eventos);
    $stmt_eventos->bind_param("i", $partido_id);
    $stmt_eventos->execute();
    $eventos = $stmt_eventos->get_result();

    $goles_local = 0;
    $goles_visitante = 0;

    while($evento = $eventos->fetch_assoc()) {
        $es_gol_valido = in_array($evento['tipo_evento'], ['gol', 'penal_anotado']);
        $es_autogol = $evento['tipo_evento'] == 'autogol';

        if ($evento['participante_id'] == $partido['participante_local_id']) {
            if ($es_gol_valido) {
                $goles_local++;
            } elseif ($es_autogol) {
                $goles_visitante++;
            }
        } else {
            if ($es_gol_valido) {
                $goles_visitante++;
            } elseif ($es_autogol) {
                $goles_local++;
            }
        }
    }
    $stmt_eventos->close();

    
    $sql_update = "UPDATE partidos SET marcador_local = ?, marcador_visitante = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("iii", $goles_local, $goles_visitante, $partido_id);
    $stmt_update->execute();
    $stmt_update->close();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'agregar_gol') {
    $jugador_id = (int)$_POST['jugador_id'];
    $asistencia_id = !empty($_POST['asistencia_id']) ? (int)$_POST['asistencia_id'] : NULL;
    $minuto = !empty($_POST['minuto']) ? trim($_POST['minuto']) : NULL;
    $tipo_evento = $_POST['tipo_evento'];

    
    if ($asistencia_id !== NULL && $asistencia_id == $jugador_id) {
        header("Location: editar_partido.php?partido_id=$partido_id&error=El jugador que asiste no puede ser el mismo que anota.");
        exit;
    }

    
    if ($minuto !== NULL && !preg_match('/^([0-9]{1,3}|[0-9]{1,3}\+[0-9]{1,2})$/', $minuto)) {
        header("Location: editar_partido.php?partido_id=$partido_id&error=Formato de minuto inválido. Use: 45 o 90+2");
        exit;
    }

    try {
        $sql = "INSERT INTO eventos_partido (partido_id, miembro_plantel_id, asistencia_miembro_plantel_id, tipo_evento, minuto)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiss", $partido_id, $jugador_id, $asistencia_id, $tipo_evento, $minuto);
        $stmt->execute();
        $stmt->close();

        
        if ($tipo_evento == 'gol' || $tipo_evento == 'penal_anotado') {
            $sql_update = "UPDATE miembros_plantel SET goles = goles + 1 WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $jugador_id);
            $stmt_update->execute();
            $stmt_update->close();
        }

        
        if ($asistencia_id !== NULL) {
            $sql_asist = "UPDATE miembros_plantel SET asistencias = asistencias + 1 WHERE id = ?";
            $stmt_asist = $conn->prepare($sql_asist);
            $stmt_asist->bind_param("i", $asistencia_id);
            $stmt_asist->execute();
            $stmt_asist->close();
        }

        
        recalcularMarcadores($conn, $partido_id);

        header("Location: editar_partido.php?partido_id=$partido_id&success=Gol agregado exitosamente.");
    } catch (Exception $e) {
        header("Location: editar_partido.php?partido_id=$partido_id&error=Error al agregar gol: " . $e->getMessage());
    }
}


if (isset($_GET['action']) && $_GET['action'] == 'eliminar_gol' && isset($_GET['evento_id'])) {
    $evento_id = (int)$_GET['evento_id'];

    try {
        
        $sql_get = "SELECT miembro_plantel_id, asistencia_miembro_plantel_id, tipo_evento FROM eventos_partido WHERE id = ?";
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

            
            if ($evento['tipo_evento'] == 'gol' || $evento['tipo_evento'] == 'penal_anotado') {
                $sql_update = "UPDATE miembros_plantel SET goles = GREATEST(goles - 1, 0) WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $evento['miembro_plantel_id']);
                $stmt_update->execute();
                $stmt_update->close();
            }

            
            if ($evento['asistencia_miembro_plantel_id']) {
                $sql_asist = "UPDATE miembros_plantel SET asistencias = GREATEST(asistencias - 1, 0) WHERE id = ?";
                $stmt_asist = $conn->prepare($sql_asist);
                $stmt_asist->bind_param("i", $evento['asistencia_miembro_plantel_id']);
                $stmt_asist->execute();
                $stmt_asist->close();
            }

            
            recalcularMarcadores($conn, $partido_id);

            header("Location: editar_partido.php?partido_id=$partido_id&success=Gol eliminado exitosamente.");
        } else {
            header("Location: editar_partido.php?partido_id=$partido_id&error=Evento no encontrado.");
        }
    } catch (Exception $e) {
        header("Location: editar_partido.php?partido_id=$partido_id&error=Error al eliminar gol: " . $e->getMessage());
    }
}

$conn->close();
exit;
?>
