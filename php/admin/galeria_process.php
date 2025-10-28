<?php
require_once 'auth_admin.php';

$redirect_url = "gestionar_galeria.php";

// --- ACCIÓN POST: CREAR O ACTUALIZAR ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    // --- Datos comunes del formulario ---
    $temporada_id = (int)$_POST['temporada_id'];
    $deporte_id = !empty($_POST['deporte_id']) ? (int)$_POST['deporte_id'] : NULL;
    $titulo_base = trim($_POST['titulo']); // Título base para múltiples fotos
    $descripcion = trim($_POST['descripcion']);
    $es_foto_grupo = isset($_POST['es_foto_grupo']) ? 1 : 0;
    $orden_base = !empty($_POST['orden']) ? (int)$_POST['orden'] : 0; // Orden base
    $fecha_captura = !empty($_POST['fecha_captura']) ? $_POST['fecha_captura'] : NULL;
    $esta_activa = isset($_POST['esta_activa']) ? 1 : 0;

    // --- Directorio de subida ---
    $upload_dir = '../img/galeria/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ["jpg", "png", "jpeg", "webp"];

    try {
        // ==========================================================
        //  ACCIÓN: CREAR (Múltiples Fotos)
        // ==========================================================
        if ($_POST['action'] == 'create') {
            
            // Verifica el array de archivos 'fotos[]' del formulario
            if (!isset($_FILES['fotos']) || !is_array($_FILES['fotos']['name'])) {
                // CORRECCIÓN: & -> ?
                header("Location: $redirect_url?error=No se seleccionaron fotos para subir.");
                exit;
            }

            $file_count = count($_FILES['fotos']['name']);
            $limit = 50; // Límite de 50 fotos
            $uploaded_count = 0;
            $errors = [];

            // Prepara la consulta de INSERCIÓN una sola vez
            $sql = "INSERT INTO galeria_temporadas (temporada_id, deporte_id, titulo, descripcion, url_foto, es_foto_grupo, orden, fecha_captura, esta_activa)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                 // CORRECCIÓN: & -> ?
                 header("Location: $redirect_url?error=Error al preparar la consulta.");
                 exit;
            }

            // Itera sobre cada archivo subido
            for ($i = 0; $i < $file_count && $uploaded_count < $limit; $i++) {
                
                // Verifica si hay error en este archivo específico
                if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    
                    $tmp_name = $_FILES['fotos']['tmp_name'][$i];
                    $original_name = basename($_FILES['fotos']['name'][$i]);
                    $file_name = uniqid() . '-' . $original_name;
                    $target_file = $upload_dir . $file_name;
                    $imageType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                    // Valida el tipo de archivo
                    if (!in_array($imageType, $allowed_types)) {
                        $errors[] = "$original_name: Tipo no permitido.";
                        continue; // Salta este archivo y continúa con el siguiente
                    }

                    // Mueve el archivo
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        
                        // Datos individuales para esta foto
                        $foto_path = $target_file; // Ruta para la BD
                        $titulo_individual = $file_count > 1 ? $titulo_base . ' (' . ($i + 1) . ')' : $titulo_base;
                        $orden_individual = $orden_base + $i; // Incrementa el orden

                        // Ejecuta el INSERT para esta foto
                        $stmt->bind_param(
                            "iisssissi",
                            $temporada_id,
                            $deporte_id,
                            $titulo_individual,
                            $descripcion,
                            $foto_path,
                            $es_foto_grupo,
                            $orden_individual,
                            $fecha_captura,
                            $esta_activa
                        );
                        $stmt->execute();
                        $uploaded_count++;
                        
                    } else {
                        $errors[] = "$original_name: Error al mover.";
                    }
                } elseif ($_FILES['fotos']['error'][$i] != UPLOAD_ERR_NO_FILE) {
                     $errors[] = "Error en archivo $original_name: " . $_FILES['fotos']['error'][$i];
                }
            } // Fin del bucle FOR

            $stmt->close();

            // Redirige con mensaje de éxito (y errores si los hubo)
            $success_msg = "Se subieron $uploaded_count fotos exitosamente.";
            $error_msg = !empty($errors) ? " Errores: " . implode(", ", $errors) : "";
            // CORRECCIÓN: & -> ?
            header("Location: $redirect_url?success=" . urlencode($success_msg . $error_msg));
            exit;

        // ==========================================================
        //  ACCIÓN: ACTUALIZAR (Una Foto)
        // ==========================================================
        } elseif ($_POST['action'] == 'update' && isset($_POST['foto_id'])) {
            
            $foto_id = (int)$_POST['foto_id'];
            // Inicia con la ruta de la foto actual
            $foto_path = $_POST['current_foto_path'] ?? ''; 

            // Revisa si se subió una NUEVA foto (en singular: 'foto')
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                
                $file_name = uniqid() . '-' . basename($_FILES['foto']['name']);
                $target_file = $upload_dir . $file_name;
                $imageType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Valida tipo
                if (!in_array($imageType, $allowed_types)) {
                    // CORRECCIÓN: & -> ?
                    header("Location: $redirect_url?error=Solo se permiten archivos JPG, JPEG, PNG y WEBP.");
                    exit;
                }

                // Mueve la nueva foto
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                    $foto_path = $target_file; // Actualiza $foto_path con la nueva ruta
                    // (Opcional: aquí podrías borrar la foto antigua: unlink($_POST['current_foto_path']))
                } else {
                    // CORRECCIÓN: & -> ?
                    header("Location: $redirect_url?error=Error al subir la nueva foto.");
                    exit;
                }
            }
            
            // Ejecuta la actualización de la BD
            // Usa el $titulo_base y $orden_base, ya que es una sola entrada
            $sql = "UPDATE galeria_temporadas
                    SET temporada_id = ?, deporte_id = ?, titulo = ?, descripcion = ?, url_foto = ?, es_foto_grupo = ?, orden = ?, fecha_captura = ?, esta_activa = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "iisssissii",
                $temporada_id,
                $deporte_id,
                $titulo_base, // Título del formulario
                $descripcion,
                $foto_path, // Ruta (antigua o nueva)
                $es_foto_grupo,
                $orden_base, // Orden del formulario
                $fecha_captura,
                $esta_activa,
                $foto_id
            );
            $stmt->execute();
            $stmt->close();
            // CORRECCIÓN: & -> ?
            header("Location: $redirect_url?success=Foto actualizada exitosamente.");
            exit;
        }

    } catch (Exception $e) {
        // Captura errores de base de datos
        // CORRECCIÓN: & -> ?
        header("Location: $redirect_url?error=Error de BD: " . urlencode($e->getMessage()));
        exit;
    }

    $conn->close();
    exit;
}

// --- ACCIÓN GET: ELIMINAR ---
if (isset($_GET['delete_id'])) {
    $foto_id = (int)$_GET['delete_id'];

    try {
        // (Opcional: primero busca la url_foto para borrar el archivo del servidor)
        // $stmt_find = $conn->prepare("SELECT url_foto FROM galeria_temporadas WHERE id = ?");
        // ... ejecutar, obtener $ruta_a_borrar, y luego unlink($ruta_a_borrar); ...

        $sql = "DELETE FROM galeria_temporadas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $foto_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // CORRECCIÓN: & -> ?
            header("Location: $redirect_url?success=Foto eliminada exitosamente.");
        } else {
            // CORRECCIÓN: & -> ?
            header("Location: $redirect_url?error=No se pudo eliminar (ID no encontrado).");
        }
        $stmt->close();

    } catch (Exception $e) {
        // CORRECCIÓN: & -> ?
        header("Location: $redirect_url?error=Error de BD: " . urlencode($e->getMessage()));
    }

    $conn->close();
    exit;
}

// Si no es POST ni GET, redirige
// CORRECCIÓN: & -> ?
header("Location: $redirect_url?error=Acción no válida.");
exit;
?>