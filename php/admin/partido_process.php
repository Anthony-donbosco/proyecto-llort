<?php
require_once 'auth_admin.php';

if (!isset($_POST['action']) || !isset($_POST['partido_id'])) {
    header("Location: gestionar_torneos.php?error=Parámetros inválidos.");
    exit;
}

$action = $_POST['action'];
$partido_id = (int)$_POST['partido_id'];
$jornada_id = isset($_POST['jornada_id']) ? (int)$_POST['jornada_id'] : null;
$torneo_id = isset($_POST['torneo_id']) ? (int)$_POST['torneo_id'] : null;
$es_bracket = isset($_POST['es_bracket']) && $_POST['es_bracket'] == '1';

$redirect_url = "editar_partido.php?partido_id=$partido_id";


$redirect_partidos = $es_bracket
    ? "gestionar_partidos.php?torneo_id=$torneo_id"
    : "gestionar_partidos.php?jornada_id=$jornada_id";

if ($action == 'editar') {
    try {
        
        if (!isset($_POST['marcador_local']) || !isset($_POST['marcador_visitante']) ||
            !isset($_POST['estado_id']) || !isset($_POST['inicio_partido'])) {
            header("Location: $redirect_url&error=Todos los campos obligatorios deben ser completados.");
            exit;
        }

        $marcador_local = (int)$_POST['marcador_local'];
        $marcador_visitante = (int)$_POST['marcador_visitante'];
        $estado_id = (int)$_POST['estado_id'];
        $inicio_partido = $_POST['inicio_partido'];
        $notas = isset($_POST['notas']) ? trim($_POST['notas']) : null;
        $jugador_local_id = !empty($_POST['jugador_local_id']) ? (int)$_POST['jugador_local_id'] : NULL;
        $jugador_visitante_id = !empty($_POST['jugador_visitante_id']) ? (int)$_POST['jugador_visitante_id'] : NULL;
        
        $ganador_individual_id = (isset($_POST['ganador_individual_id']) && $_POST['ganador_individual_id'] !== '') ? (int)$_POST['ganador_individual_id'] : NULL;



        if ($marcador_local < 0 || $marcador_visitante < 0) {
            header("Location: $redirect_url&error=Los marcadores no pueden ser negativos.");
            exit;
        }

        
        $sql = "UPDATE partidos SET
            marcador_local = ?,
            marcador_visitante = ?,
            estado_id = ?,
            inicio_partido = ?,
            notas = ?,
            jugador_local_id = ?,
            jugador_visitante_id = ?,
            ganador_individual_id = ?";

        $params = [
            $marcador_local,
            $marcador_visitante,
            $estado_id,
            $inicio_partido,
            $notas,
            $jugador_local_id,
            $jugador_visitante_id,
            $ganador_individual_id
        ];
        $types = "iiissiii";

        
        if (isset($_POST['marcador_local_sets']) && isset($_POST['marcador_visitante_sets'])) {
            $marcador_local_sets = (int)$_POST['marcador_local_sets'];
            $marcador_visitante_sets = (int)$_POST['marcador_visitante_sets'];

            if ($marcador_local_sets < 0 || $marcador_visitante_sets < 0) {
                header("Location: $redirect_url&error=Los sets no pueden ser negativos.");
                exit;
            }

            $sql .= ", marcador_local_sets = ?, marcador_visitante_sets = ?";
            $params[] = $marcador_local_sets;
            $params[] = $marcador_visitante_sets;
            $types .= "ii";
        }

        $sql .= " WHERE id = ?";
        $params[] = $partido_id;
        $types .= "i";

        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            header("Location: $redirect_url&error=Error al preparar consulta: " . $conn->error);
            exit;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            $stmt->close();

            
            if ($estado_id == 5) {
                require_once 'avanzar_ganador.php';
                $resultado_avance = avanzarGanadorSiguienteFase($conn, $partido_id);

                if ($resultado_avance['success']) {
                    header("Location: $redirect_partidos&success=Partido actualizado exitosamente. " . $resultado_avance['message']);
                } else {
                    header("Location: $redirect_partidos&success=Partido actualizado, pero: " . $resultado_avance['message']);
                }
            } else {
                header("Location: $redirect_partidos&success=Partido actualizado exitosamente.");
            }
        } else {
            $stmt->close();
            header("Location: $redirect_url&error=No se realizaron cambios.");
        }

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error al actualizar: " . $e->getMessage());
    }

} elseif ($action == 'seleccionar_mvp') {
    try {

        if (!isset($_POST['mvp_jugador_id']) || empty($_POST['mvp_jugador_id'])) {
            header("Location: $redirect_url&error=Debe seleccionar un jugador para MVP.");
            exit;
        }

        $mvp_jugador_id = (int)$_POST['mvp_jugador_id'];

        
        $stmt_anterior = $conn->prepare("SELECT mvp_miembro_plantel_id FROM partidos WHERE id = ?");
        $stmt_anterior->bind_param("i", $partido_id);
        $stmt_anterior->execute();
        $resultado = $stmt_anterior->get_result()->fetch_assoc();
        $mvp_anterior_id = $resultado['mvp_miembro_plantel_id'];
        $stmt_anterior->close();

        
        if ($mvp_anterior_id && $mvp_anterior_id != $mvp_jugador_id) {
            $stmt_restar = $conn->prepare("UPDATE miembros_plantel SET mvps = GREATEST(mvps - 1, 0) WHERE id = ?");
            $stmt_restar->bind_param("i", $mvp_anterior_id);
            $stmt_restar->execute();
            $stmt_restar->close();
        }

        
        $stmt = $conn->prepare("UPDATE partidos SET mvp_miembro_plantel_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $mvp_jugador_id, $partido_id);
        $stmt->execute();
        $stmt->close();

        
        if ($mvp_jugador_id != $mvp_anterior_id) {
            $stmt_sumar = $conn->prepare("UPDATE miembros_plantel SET mvps = mvps + 1 WHERE id = ?");
            $stmt_sumar->bind_param("i", $mvp_jugador_id);
            $stmt_sumar->execute();
            $stmt_sumar->close();
        }

        header("Location: $redirect_url&success=MVP seleccionado exitosamente. Contadores actualizados.");

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error al seleccionar MVP: " . $e->getMessage());
    }

} else {
    header("Location: gestionar_torneos.php?error=Acción no válida.");
}

$conn->close();
exit;
?>
