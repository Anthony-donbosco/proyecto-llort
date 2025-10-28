<?php
require_once 'auth_admin.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $nombre = trim($_POST['nombre']);
    $deporte_id = (int)$_POST['deporte_id'];
    $temporada_id = !empty($_POST['temporada_id']) ? (int)$_POST['temporada_id'] : NULL;
    $descripcion = trim($_POST['descripcion']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : NULL;
    $max_participantes = (int)$_POST['max_participantes'];
    $estado_id = (int)$_POST['estado_id'];
    $tipo_torneo = trim($_POST['tipo_torneo']);
    $fase_actual = trim($_POST['fase_actual']);
    $ida_y_vuelta = isset($_POST['ida_y_vuelta']) ? 1 : 0;
    $creado_por = (int)$_SESSION['user_id']; 

    try {
        if ($_POST['action'] == 'create') {
            $sql = "INSERT INTO torneos (nombre, deporte_id, temporada_id, descripcion, fecha_inicio, fecha_fin, max_participantes, estado_id, creado_por, tipo_torneo, fase_actual, ida_y_vuelta)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siisssiiissi", $nombre, $deporte_id, $temporada_id, $descripcion, $fecha_inicio, $fecha_fin, $max_participantes, $estado_id, $creado_por, $tipo_torneo, $fase_actual, $ida_y_vuelta);
            $stmt->execute();
            header("Location: gestionar_torneos.php?success=Torneo creado exitosamente.");

        } elseif ($_POST['action'] == 'update' && isset($_POST['torneo_id'])) {
            $torneo_id = (int)$_POST['torneo_id'];

            
            $stmt_check = $conn->prepare("SELECT tipo_torneo FROM torneos WHERE id = ?");
            $stmt_check->bind_param("i", $torneo_id);
            $stmt_check->execute();
            $tipo_anterior = $stmt_check->get_result()->fetch_assoc()['tipo_torneo'];
            $stmt_check->close();

            
            if ($tipo_anterior == 'liga' && $tipo_torneo == 'bracket') {
                
                $conn->begin_transaction();

                try {
                    
                    $stmt_del_partidos = $conn->prepare("DELETE p FROM partidos p
                                                         JOIN fases f ON p.fase_id = f.id
                                                         WHERE f.torneo_id = ? AND f.tipo_fase_id = 1");
                    $stmt_del_partidos->bind_param("i", $torneo_id);
                    $stmt_del_partidos->execute();
                    $partidos_eliminados = $stmt_del_partidos->affected_rows;
                    $stmt_del_partidos->close();

                    
                    $stmt_del_jornadas = $conn->prepare("DELETE j FROM jornadas j
                                                         JOIN fases f ON j.fase_id = f.id
                                                         WHERE f.torneo_id = ? AND f.tipo_fase_id = 1");
                    $stmt_del_jornadas->bind_param("i", $torneo_id);
                    $stmt_del_jornadas->execute();
                    $jornadas_eliminadas = $stmt_del_jornadas->affected_rows;
                    $stmt_del_jornadas->close();

                    
                    $stmt_del_fases = $conn->prepare("DELETE FROM fases WHERE torneo_id = ? AND tipo_fase_id = 1");
                    $stmt_del_fases->bind_param("i", $torneo_id);
                    $stmt_del_fases->execute();
                    $fases_eliminadas = $stmt_del_fases->affected_rows;
                    $stmt_del_fases->close();

                    
                    $sql = "UPDATE torneos SET nombre = ?, deporte_id = ?, temporada_id = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?, max_participantes = ?, estado_id = ?, tipo_torneo = ?, fase_actual = ?, ida_y_vuelta = ?
                            WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("siisssiissii", $nombre, $deporte_id, $temporada_id, $descripcion, $fecha_inicio, $fecha_fin, $max_participantes, $estado_id, $tipo_torneo, $fase_actual, $ida_y_vuelta, $torneo_id);
                    $stmt->execute();
                    $stmt->close();

                    
                    $conn->commit();

                    $mensaje = "Torneo actualizado a tipo Bracket. Se eliminaron $partidos_eliminados partido(s), $jornadas_eliminadas jornada(s) y $fases_eliminadas fase(s) de liga.";
                    header("Location: gestionar_torneos.php?success=" . urlencode($mensaje));
                } catch (Exception $e) {
                    $conn->rollback();
                    header("Location: gestionar_torneos.php?error=Error al cambiar a Bracket: " . $e->getMessage());
                }
            } else {
                
                $sql = "UPDATE torneos SET nombre = ?, deporte_id = ?, temporada_id = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?, max_participantes = ?, estado_id = ?, tipo_torneo = ?, fase_actual = ?, ida_y_vuelta = ?
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siisssiissii", $nombre, $deporte_id, $temporada_id, $descripcion, $fecha_inicio, $fecha_fin, $max_participantes, $estado_id, $tipo_torneo, $fase_actual, $ida_y_vuelta, $torneo_id);
                $stmt->execute();
                $stmt->close();
                header("Location: gestionar_torneos.php?success=Torneo actualizado exitosamente.");
            }
        }

    } catch (Exception $e) {
        header("Location: gestionar_torneos.php?error=Error al procesar la solicitud: " . $e->getMessage());
    }
    
    $conn->close();
    exit;
}

if (isset($_GET['delete_id'])) {
    $torneo_id = (int)$_GET['delete_id'];
    
    try {
        $sql = "DELETE FROM torneos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $torneo_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            header("Location: gestionar_torneos.php?success=Torneo eliminado exitosamente.");
        } else {
            header("Location: gestionar_torneos.php?error=No se pudo eliminar el torneo (quizás ya fue eliminado).");
        }
        $stmt->close();
        
    } catch (Exception $e) {
        header("Location: gestionar_torneos.php?error=No se pudo eliminar. Es posible que tenga partidos u otros datos asociados.");
    }

    $conn->close();
    exit;
}

header("Location: gestionar_torneos.php");
exit;
?>