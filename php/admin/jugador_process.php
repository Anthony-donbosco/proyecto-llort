<?php
require_once 'auth_admin.php';

if (!isset($_REQUEST['equipo_id'])) {
    header("Location: gestionar_equipos.php?error=ID de equipo no especificado para la redirección.");
    exit;
}
$equipo_id = (int)$_REQUEST['equipo_id'];
$redirect_url = "ver_plantel.php?equipo_id=$equipo_id";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    $plantel_id = (int)$_POST['plantel_id'];
    $nombre = trim($_POST['nombre_jugador']);
    $posicion = trim($_POST['posicion']);
    $edad = !empty($_POST['edad']) ? (int)$_POST['edad'] : NULL;
    $grado = trim($_POST['grado']);
    $numero = !empty($_POST['numero_camiseta']) ? (int)$_POST['numero_camiseta'] : NULL;

    $foto_path = $_POST['current_foto_path'] ?? '';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = '../../img/jugadores/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = uniqid() . '-' . basename($_FILES['foto']['name']);
        $target_file = $upload_dir . $file_name;
        $imageType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        if ($imageType != "jpg" && $imageType != "png" && $imageType != "jpeg" && $imageType != "webp") {
            header("Location: $redirect_url&error=Solo se permiten archivos JPG, JPEG, PNG y WEBP.");
            exit;
        }

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
            $foto_path = 'img/jugadores/' . $file_name;
        } else {
            header("Location: $redirect_url&error=Error al subir la foto.");
            exit;
        }
    }

    try {
        if ($_POST['action'] == 'create') {
            $sql = "INSERT INTO miembros_plantel (plantel_id, nombre_jugador, posicion, url_foto, edad, grado, numero_camiseta) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssisi", $plantel_id, $nombre, $posicion, $foto_path, $edad, $grado, $numero);
            $stmt->execute();
            header("Location: $redirect_url&success=Jugador agregado exitosamente.");
        
        } elseif ($_POST['action'] == 'update' && isset($_POST['jugador_id'])) {
            $jugador_id = (int)$_POST['jugador_id'];
            $sql = "UPDATE miembros_plantel SET nombre_jugador = ?, posicion = ?, url_foto = ?, edad = ?, grado = ?, numero_camiseta = ?
                    WHERE id = ? AND plantel_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssisiii", $nombre, $posicion, $foto_path, $edad, $grado, $numero, $jugador_id, $plantel_id);
            $stmt->execute();
            header("Location: $redirect_url&success=Jugador actualizado exitosamente.");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error de BD: " . $e->getMessage());
    }
    
    $conn->close();
    exit;
}

if (isset($_GET['delete_id'])) {
    $jugador_id = (int)$_GET['delete_id'];
    
    try {
        $sql = "DELETE FROM miembros_plantel WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $jugador_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            header("Location: $redirect_url&success=Jugador eliminado exitosamente.");
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