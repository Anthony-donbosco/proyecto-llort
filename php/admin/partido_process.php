<?php
require_once 'auth_admin.php';

if (!isset($_POST['action']) || !isset($_POST['partido_id'])) {
    header("Location: gestionar_torneos.php?error=Parámetros inválidos.");
    exit;
}

$action = $_POST['action'];
$partido_id = (int)$_POST['partido_id'];
$jornada_id = (int)$_POST['jornada_id'];

$redirect_url = "editar_partido.php?partido_id=$partido_id";

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

        
        if ($marcador_local < 0 || $marcador_visitante < 0) {
            header("Location: $redirect_url&error=Los marcadores no pueden ser negativos.");
            exit;
        }

        
        $sql = "UPDATE partidos SET
                marcador_local = ?,
                marcador_visitante = ?,
                estado_id = ?,
                inicio_partido = ?,
                notas = ?";

        $params = [$marcador_local, $marcador_visitante, $estado_id, $inicio_partido, $notas];
        $types = "iiiss";

        
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
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            $stmt->close();
            header("Location: gestionar_partidos.php?jornada_id=$jornada_id&success=Partido actualizado exitosamente.");
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

        
        $stmt = $conn->prepare("UPDATE partidos SET mvp_miembro_plantel_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $mvp_jugador_id, $partido_id);
        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            $stmt->close();
            header("Location: $redirect_url&success=MVP seleccionado exitosamente.");
        } else {
            $stmt->close();
            header("Location: $redirect_url&error=No se pudo seleccionar el MVP.");
        }

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error al seleccionar MVP: " . $e->getMessage());
    }

} else {
    header("Location: gestionar_torneos.php?error=Acción no válida.");
}

$conn->close();
exit;
?>
