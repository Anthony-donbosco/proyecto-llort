<?php
require_once 'auth_admin.php';

$redirect_url = "gestionar_destacados.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $deporte_id = (int)$_POST['deporte_id'];
    $tipo_destacado = $_POST['tipo_destacado'];
    $miembro_plantel_id = (int)$_POST['miembro_plantel_id'];
    $torneo_id = !empty($_POST['torneo_id']) ? (int)$_POST['torneo_id'] : NULL;
    $temporada_id = !empty($_POST['temporada_id']) ? (int)$_POST['temporada_id'] : NULL;
    $descripcion = trim($_POST['descripcion']);
    $fecha_destacado = !empty($_POST['fecha_destacado']) ? $_POST['fecha_destacado'] : NULL;
    $orden = !empty($_POST['orden']) ? (int)$_POST['orden'] : 0;
    $esta_activo = isset($_POST['esta_activo']) ? 1 : 0;

    try {
        if ($_POST['action'] == 'create') {
            $sql = "INSERT INTO jugadores_destacados (deporte_id, tipo_destacado, miembro_plantel_id, torneo_id, temporada_id, descripcion, fecha_destacado, orden, esta_activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isiiiisii", $deporte_id, $tipo_destacado, $miembro_plantel_id, $torneo_id, $temporada_id, $descripcion, $fecha_destacado, $orden, $esta_activo);
            $stmt->execute();
            header("Location: $redirect_url&success=Jugador destacado agregado exitosamente.");

        } elseif ($_POST['action'] == 'update' && isset($_POST['destacado_id'])) {
            $destacado_id = (int)$_POST['destacado_id'];
            $sql = "UPDATE jugadores_destacados
                    SET deporte_id = ?, tipo_destacado = ?, miembro_plantel_id = ?, torneo_id = ?, temporada_id = ?, descripcion = ?, fecha_destacado = ?, orden = ?, esta_activo = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isiiiisiii", $deporte_id, $tipo_destacado, $miembro_plantel_id, $torneo_id, $temporada_id, $descripcion, $fecha_destacado, $orden, $esta_activo, $destacado_id);
            $stmt->execute();
            header("Location: $redirect_url&success=Jugador destacado actualizado exitosamente.");
        }

        $stmt->close();

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error de BD: " . $e->getMessage());
    }

    $conn->close();
    exit;
}

if (isset($_GET['delete_id'])) {
    $destacado_id = (int)$_GET['delete_id'];

    try {
        $sql = "DELETE FROM jugadores_destacados WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $destacado_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            header("Location: $redirect_url&success=Jugador destacado eliminado exitosamente.");
        } else {
            header("Location: $redirect_url&error=No se pudo eliminar (ID no encontrado).");
        }

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error de BD: " . $e->getMessage());
    }

    $stmt->close();
    $conn->close();
    exit;
}

header("Location: $redirect_url&error=Acción no válida.");
exit;
?>
