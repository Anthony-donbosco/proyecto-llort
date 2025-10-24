<?php
require_once 'auth_admin.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $nombre = trim($_POST['nombre_mostrado']);
    $nombre_corto = trim($_POST['nombre_corto']);
    $deporte_id = (int)$_POST['deporte_id'];
    $tipo_id = (int)$_POST['tipo_participante_id'];
    $logo_path = $_POST['current_logo_path'] ?? '';

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $upload_dir = '../../img/logos/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = uniqid() . '-' . basename($_FILES['logo']['name']);
        $target_file = $upload_dir . $file_name;
        
        $imageType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if ($imageType != "jpg" && $imageType != "png" && $imageType != "jpeg" && $imageType != "webp") {
            header("Location: gestionar_equipos.php?error=Solo se permiten archivos JPG, JPEG, PNG y WEBP.");
            exit;
        }

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
            $logo_path = '../../img/logos/' . $file_name;
        } else {
            header("Location: gestionar_equipos.php?error=Error al subir el logo.");
            exit;
        }
    }

    try {
        if ($_POST['action'] == 'create') {
            $conn->begin_transaction();

            $sql = "INSERT INTO participantes (nombre_mostrado, nombre_corto, deporte_id, tipo_participante_id, url_logo) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiis", $nombre, $nombre_corto, $deporte_id, $tipo_id, $logo_path);
            $stmt->execute();
            
            $participante_id = $conn->insert_id;
            $plantel_nombre = "Plantel Principal";
            $sql_plantel = "INSERT INTO planteles_equipo (participante_id, nombre_plantel, esta_activo) VALUES (?, ?, 1)";
            $stmt_plantel = $conn->prepare($sql_plantel);
            $stmt_plantel->bind_param("is", $participante_id, $plantel_nombre);
            $stmt_plantel->execute();

            $conn->commit();
            header("Location: gestionar_equipos.php?success=Equipo y su plantel creados exitosamente.");
        
        } elseif ($_POST['action'] == 'update' && isset($_POST['equipo_id'])) {
            $equipo_id = (int)$_POST['equipo_id'];
            $sql = "UPDATE participantes SET nombre_mostrado = ?, nombre_corto = ?, deporte_id = ?, tipo_participante_id = ?, url_logo = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiisi", $nombre, $nombre_corto, $deporte_id, $tipo_id, $logo_path, $equipo_id);
            $stmt->execute();
            header("Location: gestionar_equipos.php?success=Equipo actualizado exitosamente.");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: gestionar_equipos.php?error=Error de BD: " . $e->getMessage());
    }
    
    $conn->close();
    exit;
}

if (isset($_GET['delete_id'])) {
    $equipo_id = (int)$_GET['delete_id'];
    
    try {
        $sql = "DELETE FROM participantes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $equipo_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            header("Location: gestionar_equipos.php?success=Equipo eliminado exitosamente.");
        } else {
            header("Location: gestionar_equipos.php?error=No se pudo eliminar (ID no encontrado).");
        }
        
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            header("Location: gestionar_equipos.php?error=No se puede eliminar. El equipo está inscrito en un torneo o tiene jugadores.");
        } else {
            header("Location: gestionar_equipos.php?error=Error de BD: " . $e->getMessage());
        }
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

header("Location: gestionar_equipos.php");
exit;
?>