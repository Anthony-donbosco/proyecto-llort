<?php
require_once 'auth_admin.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $participante_local_id = (int)$_POST['participante_local_id'];
    $participante_visitante_id = (int)$_POST['participante_visitante_id'];
    $inicio_partido = $_POST['inicio_partido'];
    $estado_id = (int)$_POST['estado_id'];
    $notas = trim($_POST['notas'] ?? '');

    if ($participante_local_id == $participante_visitante_id) {
        header("Location: crear_amistoso.php?error=No puedes seleccionar el mismo equipo como local y visitante.");
        exit;
    }

    try {
        if ($_POST['action'] == 'create') {

            $sql = "INSERT INTO partidos (torneo_id, fase_id, participante_local_id, participante_visitante_id,
                    inicio_partido, estado_id, notas)
                    VALUES (NULL, NULL, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisis", $participante_local_id, $participante_visitante_id,
                              $inicio_partido, $estado_id, $notas);
            $stmt->execute();
            $stmt->close();

            header("Location: gestionar_amistosos.php?success=Partido amistoso creado exitosamente.");

        } elseif ($_POST['action'] == 'update' && isset($_POST['partido_id'])) {
            $partido_id = (int)$_POST['partido_id'];

            $sql = "UPDATE partidos
                    SET participante_local_id = ?,
                        participante_visitante_id = ?,
                        inicio_partido = ?,
                        estado_id = ?,
                        notas = ?
                    WHERE id = ? AND torneo_id IS NULL";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisisi", $participante_local_id, $participante_visitante_id,
                              $inicio_partido, $estado_id, $notas, $partido_id);
            $stmt->execute();
            $stmt->close();

            header("Location: gestionar_amistosos.php");
        }

    } catch (Exception $e) {
        $action = $_POST['action'] == 'create' ? 'crear' : 'actualizar';
        header("Location: crear_amistoso.php?error=Error al $action el partido: " . $e->getMessage());
    }

    $conn->close();
    exit;
}

if (isset($_GET['delete_id'])) {
    $partido_id = (int)$_GET['delete_id'];

    try {
        $conn->begin_transaction();

        $stmt_eventos = $conn->prepare("DELETE FROM eventos_partido WHERE partido_id = ?");
        $stmt_eventos->bind_param("i", $partido_id);
        $stmt_eventos->execute();
        $stmt_eventos->close();

        $stmt_crono = $conn->prepare("DELETE FROM cronometro_partido WHERE partido_id = ?");
        $stmt_crono->bind_param("i", $partido_id);
        $stmt_crono->execute();
        $stmt_crono->close();

        $stmt_sets = $conn->prepare("DELETE FROM puntos_set WHERE partido_id = ?");
        $stmt_sets->bind_param("i", $partido_id);
        $stmt_sets->execute();
        $stmt_sets->close();

        $stmt_periodos = $conn->prepare("DELETE FROM resultados_periodo_partido WHERE partido_id = ?");
        $stmt_periodos->bind_param("i", $partido_id);
        $stmt_periodos->execute();
        $stmt_periodos->close();

        $stmt_rsets = $conn->prepare("DELETE FROM resultados_set_partido WHERE partido_id = ?");
        $stmt_rsets->bind_param("i", $partido_id);
        $stmt_rsets->execute();
        $stmt_rsets->close();

        $stmt_partido = $conn->prepare("DELETE FROM partidos WHERE id = ? AND torneo_id IS NULL");
        $stmt_partido->bind_param("i", $partido_id);
        $stmt_partido->execute();
        $affected = $stmt_partido->affected_rows;
        $stmt_partido->close();

        $conn->commit();

        if ($affected > 0) {
            header("Location: gestionar_amistosos.php?success=Partido amistoso eliminado exitosamente.");
        } else {
            header("Location: gestionar_amistosos.php?error=No se pudo eliminar el partido (quizás no es un partido amistoso).");
        }

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: gestionar_amistosos.php?error=Error al eliminar el partido: " . $e->getMessage());
    }

    $conn->close();
    exit;
}

header("Location: gestionar_amistosos.php");
exit;
?>
