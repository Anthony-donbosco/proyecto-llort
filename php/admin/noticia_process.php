<?php
require_once 'auth_admin.php';

$redirect_url = "gestionar_noticias.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    $titulo = trim($_POST['titulo']);
    $subtitulo = trim($_POST['subtitulo']);
    $contenido = trim($_POST['contenido']);
    $autor = trim($_POST['autor']) ?: 'Redacción';
    $deporte_id = !empty($_POST['deporte_id']) ? (int)$_POST['deporte_id'] : NULL;
    $temporada_id = !empty($_POST['temporada_id']) ? (int)$_POST['temporada_id'] : NULL;
    $etiquetas = trim($_POST['etiquetas']);
    $destacada = isset($_POST['destacada']) ? 1 : 0;
    $publicada = isset($_POST['publicada']) ? 1 : 0;
    $fecha_publicacion = !empty($_POST['fecha_publicacion']) ? $_POST['fecha_publicacion'] : date('Y-m-d H:i:s');
    $orden = !empty($_POST['orden']) ? (int)$_POST['orden'] : 0;

    $imagen_path = $_POST['current_imagen_path'] ?? '';

    if (isset($_FILES['imagen_portada']) && $_FILES['imagen_portada']['error'] == 0) {
        $upload_dir = '../../img/noticias/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = uniqid() . '-' . basename($_FILES['imagen_portada']['name']);
        $target_file = $upload_dir . $file_name;
        $imageType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if ($imageType != "jpg" && $imageType != "png" && $imageType != "jpeg" && $imageType != "webp") {
            header("Location: $redirect_url&error=Solo se permiten archivos JPG, JPEG, PNG y WEBP.");
            exit;
        }

        if (move_uploaded_file($_FILES['imagen_portada']['tmp_name'], $target_file)) {
            $imagen_path = '../img/noticias/' . $file_name;
        } else {
            header("Location: $redirect_url&error=Error al subir la imagen.");
            exit;
        }
    }

    try {
        if ($_POST['action'] == 'create') {
            $sql = "INSERT INTO noticias (titulo, subtitulo, contenido, imagen_portada, autor, deporte_id, temporada_id, etiquetas, destacada, publicada, fecha_publicacion, orden)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssiisiiis", $titulo, $subtitulo, $contenido, $imagen_path, $autor, $deporte_id, $temporada_id, $etiquetas, $destacada, $publicada, $fecha_publicacion, $orden);
            $stmt->execute();
            header("Location: $redirect_url?success=Noticia creada exitosamente.");

        } elseif ($_POST['action'] == 'update' && isset($_POST['noticia_id'])) {
            $noticia_id = (int)$_POST['noticia_id'];
            $sql = "UPDATE noticias
                    SET titulo = ?, subtitulo = ?, contenido = ?, imagen_portada = ?, autor = ?, deporte_id = ?, temporada_id = ?, etiquetas = ?, destacada = ?, publicada = ?, fecha_publicacion = ?, orden = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssiisiiisi", $titulo, $subtitulo, $contenido, $imagen_path, $autor, $deporte_id, $temporada_id, $etiquetas, $destacada, $publicada, $fecha_publicacion, $orden, $noticia_id);
            header("Location: $redirect_url?success=Noticia actualizada exitosamente.");
        }

        $stmt->close();

    } catch (Exception $e) {
        header("Location: $redirect_url?error=Error de BD: " . $e->getMessage());
    }

    $conn->close();
    exit;
}

if (isset($_GET['delete_id'])) {
    $noticia_id = (int)$_GET['delete_id'];

    try {
        $sql = "DELETE FROM noticias WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $noticia_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            header("Location: $redirect_url?success=Noticia eliminada exitosamente.");
        } else {
            header("Location: $redirect_url?error=No se pudo eliminar (ID no encontrado).");
        }

    } catch (Exception $e) {
        header("Location: $redirect_url?error=Error de BD: " . $e->getMessage());
    }

    $stmt->close();
    $conn->close();
    exit;
}

header("Location: $redirect_url?error=Acción no válida.");
exit;
?>
