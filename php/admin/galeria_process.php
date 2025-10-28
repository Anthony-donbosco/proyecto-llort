<?php
require_once 'auth_admin.php';

$redirect_url = "gestionar_galeria.php";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    
    $temporada_id = (int)$_POST['temporada_id'];
    $deporte_id = !empty($_POST['deporte_id']) ? (int)$_POST['deporte_id'] : NULL;
    $titulo_base = trim($_POST['titulo']); 
    $descripcion = trim($_POST['descripcion']);
    $es_foto_grupo = isset($_POST['es_foto_grupo']) ? 1 : 0;
    $orden_base = !empty($_POST['orden']) ? (int)$_POST['orden'] : 0; 
    $fecha_captura = !empty($_POST['fecha_captura']) ? $_POST['fecha_captura'] : NULL;
    $esta_activa = isset($_POST['esta_activa']) ? 1 : 0;

    
    $upload_dir = '../../img/galeria/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ["jpg", "png", "jpeg", "webp"];

    try {
        
        
        
        if ($_POST['action'] == 'create') {
            
            
            if (!isset($_FILES['fotos']) || !is_array($_FILES['fotos']['name'])) {
                
                header("Location: $redirect_url?error=No se seleccionaron fotos para subir.");
                exit;
            }

            $file_count = count($_FILES['fotos']['name']);
            $limit = 50; 
            $uploaded_count = 0;
            $errors = [];

            
            $sql = "INSERT INTO galeria_temporadas (temporada_id, deporte_id, titulo, descripcion, url_foto, es_foto_grupo, orden, fecha_captura, esta_activa)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                 
                 header("Location: $redirect_url?error=Error al preparar la consulta.");
                 exit;
            }

            
            for ($i = 0; $i < $file_count && $uploaded_count < $limit; $i++) {
                
                
                if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
                    
                    $tmp_name = $_FILES['fotos']['tmp_name'][$i];
                    $original_name = basename($_FILES['fotos']['name'][$i]);
                    $file_name = uniqid() . '-' . $original_name;
                    $target_file = $upload_dir . $file_name;
                    $imageType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                    
                    if (!in_array($imageType, $allowed_types)) {
                        $errors[] = "$original_name: Tipo no permitido.";
                        continue; 
                    }

                    
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        
                        
                        $foto_path = $target_file; 
                        $titulo_individual = $file_count > 1 ? $titulo_base . ' (' . ($i + 1) . ')' : $titulo_base;
                        $orden_individual = $orden_base + $i; 

                        
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
            } 

            $stmt->close();

            
            $success_msg = "Se subieron $uploaded_count fotos exitosamente.";
            $error_msg = !empty($errors) ? " Errores: " . implode(", ", $errors) : "";
            
            header("Location: $redirect_url?success=" . urlencode($success_msg . $error_msg));
            exit;

        
        
        
        } elseif ($_POST['action'] == 'update' && isset($_POST['foto_id'])) {
            
            $foto_id = (int)$_POST['foto_id'];
            
            $foto_path = $_POST['current_foto_path'] ?? ''; 

            
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                
                $file_name = uniqid() . '-' . basename($_FILES['foto']['name']);
                $target_file = $upload_dir . $file_name;
                $imageType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                
                if (!in_array($imageType, $allowed_types)) {
                    
                    header("Location: $redirect_url?error=Solo se permiten archivos JPG, JPEG, PNG y WEBP.");
                    exit;
                }

                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                    $foto_path = $target_file; 
                    
                } else {
                    
                    header("Location: $redirect_url?error=Error al subir la nueva foto.");
                    exit;
                }
            }
            
            
            
            $sql = "UPDATE galeria_temporadas
                    SET temporada_id = ?, deporte_id = ?, titulo = ?, descripcion = ?, url_foto = ?, es_foto_grupo = ?, orden = ?, fecha_captura = ?, esta_activa = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "iisssissii",
                $temporada_id,
                $deporte_id,
                $titulo_base, 
                $descripcion,
                $foto_path, 
                $es_foto_grupo,
                $orden_base, 
                $fecha_captura,
                $esta_activa,
                $foto_id
            );
            $stmt->execute();
            $stmt->close();
            
            header("Location: $redirect_url?success=Foto actualizada exitosamente.");
            exit;
        }

    } catch (Exception $e) {
        
        
        header("Location: $redirect_url?error=Error de BD: " . urlencode($e->getMessage()));
        exit;
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
            
            header("Location: $redirect_url?success=Foto eliminada exitosamente.");
        } else {
            
            header("Location: $redirect_url?error=No se pudo eliminar (ID no encontrado).");
        }
        $stmt->close();

    } catch (Exception $e) {
        
        header("Location: $redirect_url?error=Error de BD: " . urlencode($e->getMessage()));
    }

    $conn->close();
    exit;
}



header("Location: $redirect_url?error=Acción no válida.");
exit;
?>