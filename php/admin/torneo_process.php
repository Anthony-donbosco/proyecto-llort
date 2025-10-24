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
    $creado_por = (int)$_SESSION['user_id']; 

    try {
        if ($_POST['action'] == 'create') {
            $sql = "INSERT INTO torneos (nombre, deporte_id, temporada_id, descripcion, fecha_inicio, fecha_fin, max_participantes, estado_id, creado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siisssiii", $nombre, $deporte_id, $temporada_id, $descripcion, $fecha_inicio, $fecha_fin, $max_participantes, $estado_id, $creado_por);
            $stmt->execute();
            header("Location: gestionar_torneos.php?success=Torneo creado exitosamente.");
        
        } elseif ($_POST['action'] == 'update' && isset($_POST['torneo_id'])) {
            $torneo_id = (int)$_POST['torneo_id'];
            $sql = "UPDATE torneos SET nombre = ?, deporte_id = ?, temporada_id = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ?, max_participantes = ?, estado_id = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siisssiii", $nombre, $deporte_id, $temporada_id, $descripcion, $fecha_inicio, $fecha_fin, $max_participantes, $estado_id, $torneo_id);
            $stmt->execute();
            header("Location: gestionar_torneos.php?success=Torneo actualizado exitosamente.");
        }
        
        $stmt->close();
        
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