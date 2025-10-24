<?php
require_once 'auth_admin.php';

// --- Lógica de CREATE y UPDATE (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // --- 1. Recolección de Datos ---
    $nombre = trim($_POST['nombre_mostrado']);
    $nombre_corto = trim($_POST['nombre_corto']);
    $deporte_id = (int)$_POST['deporte_id'];
    $tipo_id = (int)$_POST['tipo_participante_id']; // Siempre será '1' (Equipo)
    
    // Ruta del logo: por defecto es la actual (en edición) o vacía (en creación)
    $logo_path = $_POST['current_logo_path'] ?? '';

    // --- 2. Lógica de Subida de Archivo (Logo) ---
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $upload_dir = '../../img/logos/';
        
        // Crear el directorio si no existe
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = uniqid() . '-' . basename($_FILES['logo']['name']);
        $target_file = $upload_dir . $file_name;
        
        // Validar tipo de imagen
        $imageType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if ($imageType != "jpg" && $imageType != "png" && $imageType != "jpeg" && $imageType != "webp") {
            header("Location: gestionar_equipos.php?error=Solo se permiten archivos JPG, JPEG, PNG y WEBP.");
            exit;
        }

        // Mover el archivo
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
            $logo_path = 'img/logos/' . $file_name; // Ruta relativa para la BD
        } else {
            header("Location: gestionar_equipos.php?error=Error al subir el logo.");
            exit;
        }
    }

    // --- 3. Ejecución de SQL ---
    try {
        if ($_POST['action'] == 'create') {
            // --- Acción CREAR ---
            $conn->begin_transaction();

            $sql = "INSERT INTO participantes (nombre_mostrado, nombre_corto, deporte_id, tipo_participante_id, url_logo) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiis", $nombre, $nombre_corto, $deporte_id, $tipo_id, $logo_path);
            $stmt->execute();
            
            // ¡IMPORTANTE! Creamos automáticamente su "Plantel"
            $participante_id = $conn->insert_id;
            $plantel_nombre = "Plantel Principal";
            $sql_plantel = "INSERT INTO planteles_equipo (participante_id, nombre_plantel, esta_activo) VALUES (?, ?, 1)";
            $stmt_plantel = $conn->prepare($sql_plantel);
            $stmt_plantel->bind_param("is", $participante_id, $plantel_nombre);
            $stmt_plantel->execute();

            $conn->commit();
            header("Location: gestionar_equipos.php?success=Equipo y su plantel creados exitosamente.");
        
        } elseif ($_POST['action'] == 'update' && isset($_POST['equipo_id'])) {
            // --- Acción ACTUALIZAR ---
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

// --- Lógica de DELETE (GET) ---
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
        // Error de Foreign Key (Código 1451)
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