<?php
require_once 'auth_admin.php';

$redirect_url = "gestionar_galeria.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $temporada_id = (int)$_POST['temporada_id'];
    $deporte_id = !empty($_POST['deporte_id']) ? (int)$_POST['deporte_id'] : NULL;
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $es_foto_grupo = isset($_POST['es_foto_grupo']) ? 1 : 0;
    $orden = !empty($_POST['orden']) ? (int)$_POST['orden'] : 0;
    $fecha_captura = !empty($_POST['fecha_captura']) ? $_POST['fecha_captura'] : NULL;
    $esta_activa = isset($_POST['esta_activa']) ? 1 : 0;

    $foto_path = $_POST['current_foto_path'] ?? '';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $upload_dir = '../../img/galeria/';
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
            $foto_path = '../../img/galeria/' . $file_name;
        } else {
            header("Location: $redirect_url&error=Error al subir la foto.");
            exit;
        }
    }

    try {
        if ($_POST['action'] == 'create') {
            $sql = "INSERT INTO galeria_temporadas (temporada_id, deporte_id, titulo, descripcion, url_foto, es_foto_grupo, orden, fecha_captura, esta_activa)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisssissi", $temporada_id, $deporte_id, $titulo, $descripcion, $foto_path, $es_foto_grupo, $orden, $fecha_captura, $esta_activa);
            $stmt->execute();
            header("Location: $redirect_url&success=Foto agregada exitosamente a la galería.");

        } elseif ($_POST['action'] == 'update' && isset($_POST['foto_id'])) {
            $foto_id = (int)$_POST['foto_id'];
            $sql = "UPDATE galeria_temporadas
                    SET temporada_id = ?, deporte_id = ?, titulo = ?, descripcion = ?, url_foto = ?, es_foto_grupo = ?, orden = ?, fecha_captura = ?, esta_activa = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisssissii", $temporada_id, $deporte_id, $titulo, $descripcion, $foto_path, $es_foto_grupo, $orden, $fecha_captura, $esta_activa, $foto_id);
            $stmt->execute();
            header("Location: $redirect_url&success=Foto actualizada exitosamente.");
        }

        $stmt->close();

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error de BD: " . $e->getMessage());
    }

    $conn->close();
    exit;
}

if (isset($_GET['delete_id'])) {
    $foto_id = (int)$_GET['delete_id'];

    try {
        $sql = "DELETE FROM galeria_temporadas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $foto_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            header("Location: $redirect_url&success=Foto eliminada exitosamente.");
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
