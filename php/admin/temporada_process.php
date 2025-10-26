<?php
require_once 'auth_admin.php';

$redirect_url = "gestionar_temporadas.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $nombre = trim($_POST['nombre']);
    $ano = (int)$_POST['ano'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $es_actual = isset($_POST['es_actual']) ? 1 : 0;

    try {
        
        if ($es_actual == 1) {
            $conn->query("UPDATE temporadas SET es_actual = 0");
        }

        if ($_POST['action'] == 'create') {
            $sql = "INSERT INTO temporadas (nombre, ano, fecha_inicio, fecha_fin, es_actual)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissi", $nombre, $ano, $fecha_inicio, $fecha_fin, $es_actual);
            $stmt->execute();
            header("Location: $redirect_url&success=Temporada agregada exitosamente.");

        } elseif ($_POST['action'] == 'update' && isset($_POST['temporada_id'])) {
            $temporada_id = (int)$_POST['temporada_id'];
            $sql = "UPDATE temporadas
                    SET nombre = ?, ano = ?, fecha_inicio = ?, fecha_fin = ?, es_actual = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sissii", $nombre, $ano, $fecha_inicio, $fecha_fin, $es_actual, $temporada_id);
            $stmt->execute();
            header("Location: $redirect_url&success=Temporada actualizada exitosamente.");
        }

        $stmt->close();

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error de BD: " . $e->getMessage());
    }

    $conn->close();
    exit;
}

if (isset($_GET['set_actual'])) {
    $temporada_id = (int)$_GET['set_actual'];

    try {
        
        $conn->query("UPDATE temporadas SET es_actual = 0");

        
        $sql = "UPDATE temporadas SET es_actual = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $temporada_id);
        $stmt->execute();

        header("Location: $redirect_url&success=Temporada marcada como actual exitosamente.");

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error de BD: " . $e->getMessage());
    }

    $stmt->close();
    $conn->close();
    exit;
}

if (isset($_GET['delete_id'])) {
    $temporada_id = (int)$_GET['delete_id'];

    try {
        $sql = "DELETE FROM temporadas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $temporada_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            header("Location: $redirect_url&success=Temporada eliminada exitosamente.");
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
