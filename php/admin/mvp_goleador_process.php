<?php
require_once 'auth_admin.php';

$redirect_url = "gestionar_torneos.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['torneo_id'])) {
    $torneo_id = (int)$_POST['torneo_id'];
    $redirect_url = "asignar_mvp_goleador.php?torneo_id=$torneo_id";

    try {
        if ($_POST['action'] == 'asignar_mvp') {
            $jugador_id = (int)$_POST['jugador_id'];

            $sql = "UPDATE torneos SET mvp_torneo_miembro_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $jugador_id, $torneo_id);
            $stmt->execute();
            $stmt->close();

            header("Location: $redirect_url&success=MVP del torneo asignado exitosamente.");

        } elseif ($_POST['action'] == 'asignar_goleador') {
            $jugador_id = (int)$_POST['jugador_id'];
            $total_goles = (int)$_POST['total_goles'];

            $sql = "UPDATE torneos SET goleador_torneo_miembro_id = ?, goles_goleador = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $jugador_id, $total_goles, $torneo_id);
            $stmt->execute();
            $stmt->close();

            header("Location: $redirect_url&success=Goleador del torneo asignado exitosamente.");
        }

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error: " . $e->getMessage());
    }

    $conn->close();
    exit;
}

header("Location: $redirect_url&error=Acción no válida.");
exit;
?>
