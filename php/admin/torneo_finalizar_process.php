<?php
require_once 'auth_admin.php';

if (!isset($_POST['action']) || !isset($_POST['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=Parámetros inválidos.");
    exit;
}

$action = $_POST['action'];
$torneo_id = (int)$_POST['torneo_id'];

if ($action == 'terminar') {
    try {
        
        $stmt_check = $conn->prepare("SELECT id, nombre FROM torneos WHERE id = ?");
        $stmt_check->bind_param("i", $torneo_id);
        $stmt_check->execute();
        $torneo = $stmt_check->get_result()->fetch_assoc();

        if (!$torneo) {
            header("Location: gestionar_torneos.php?error=Torneo no encontrado.");
            exit;
        }
        $stmt_check->close();

        
        $stmt_update = $conn->prepare("UPDATE torneos SET estado_id = 5 WHERE id = ?");
        $stmt_update->bind_param("i", $torneo_id);
        $stmt_update->execute();
        $stmt_update->close();

        
        

        header("Location: gestionar_torneos.php?success=Torneo finalizado exitosamente.");

    } catch (Exception $e) {
        header("Location: finalizar_torneo.php?torneo_id=$torneo_id&error=Error al finalizar: " . $e->getMessage());
    }

} else {
    header("Location: gestionar_torneos.php?error=Acción no válida.");
}

$conn->close();
exit;
?>
