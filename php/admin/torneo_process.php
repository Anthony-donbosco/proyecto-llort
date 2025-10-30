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
        $conn->begin_transaction();

        $partidos_eliminados = 0;

        $stmt = $conn->prepare("DELETE ep FROM eventos_partido ep
                                JOIN partidos p ON ep.partido_id = p.id
                                WHERE p.torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE cp FROM cronometro_partido cp
                                JOIN partidos p ON cp.partido_id = p.id
                                WHERE p.torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE ps FROM puntos_set ps
                                JOIN partidos p ON ps.partido_id = p.id
                                WHERE p.torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE rp FROM resultados_periodo_partido rp
                                JOIN partidos p ON rp.partido_id = p.id
                                WHERE p.torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE rs FROM resultados_set_partido rs
                                JOIN partidos p ON rs.partido_id = p.id
                                WHERE p.torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE eb FROM enlaces_bracket eb
                                JOIN partidos p ON (eb.partido_origen_id = p.id OR eb.partido_destino_id = p.id)
                                WHERE p.torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM partidos WHERE torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $partidos_eliminados = $stmt->affected_rows;
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE j FROM jornadas j
                                JOIN fases f ON j.fase_id = f.id
                                WHERE f.torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM bracket_torneos WHERE torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM tabla_posiciones WHERE torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM jugadores_destacados WHERE torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE mg FROM miembros_grupo mg
                                JOIN torneo_grupos tg ON mg.grupo_id = tg.id
                                WHERE tg.torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM torneo_grupos WHERE torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM fases WHERE torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM torneo_participantes WHERE torneo_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM torneos WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $torneo_id);
            $stmt->execute();
            $torneo_eliminado = $stmt->affected_rows;
            $stmt->close();

            $conn->commit();

            if ($torneo_eliminado > 0) {
                $mensaje = "Torneo eliminado exitosamente junto con $partidos_eliminados partido(s) y todos sus datos asociados.";
                header("Location: gestionar_torneos.php?success=" . urlencode($mensaje));
            } else {
                header("Location: gestionar_torneos.php?error=No se pudo eliminar el torneo (quizás ya fue eliminado).");
            }
        } else {
            throw new Exception("Error al preparar eliminación del torneo: " . $conn->error);
        }

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: gestionar_torneos.php?error=Error al eliminar el torneo: " . $e->getMessage());
    }

    $conn->close();
    exit;
}

header("Location: gestionar_torneos.php");
exit;
?>