<?php
require_once 'auth_admin.php';

if (!isset($_GET['action']) || !isset($_GET['torneo_id']) || !isset($_GET['equipo_id'])) {
    header("Location: gestionar_torneos.php?error=Parámetros inválidos.");
    exit;
}

$action = $_GET['action'];
$torneo_id = (int)$_GET['torneo_id'];
$equipo_id = (int)$_GET['equipo_id'];

$redirect_url = "inscribir_equipos.php?torneo_id=$torneo_id";

if ($action == 'inscribir') {
    try {
        // Verificar cupos disponibles
        $stmt_check = $conn->prepare("SELECT COUNT(*) as total, t.max_participantes
                                       FROM torneo_participantes tp
                                       JOIN torneos t ON tp.torneo_id = t.id
                                       WHERE tp.torneo_id = ?
                                       GROUP BY t.max_participantes");
        $stmt_check->bind_param("i", $torneo_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $row = $result_check->fetch_assoc();
            if ($row['total'] >= $row['max_participantes']) {
                header("Location: $redirect_url&error=No hay cupos disponibles.");
                exit;
            }
        }
        $stmt_check->close();

        // Verificar que no esté ya inscrito
        $stmt_dup = $conn->prepare("SELECT COUNT(*) as existe FROM torneo_participantes WHERE torneo_id = ? AND participante_id = ?");
        $stmt_dup->bind_param("ii", $torneo_id, $equipo_id);
        $stmt_dup->execute();
        $dup_result = $stmt_dup->get_result()->fetch_assoc();

        if ($dup_result['existe'] > 0) {
            header("Location: $redirect_url&error=Este equipo ya está inscrito.");
            exit;
        }
        $stmt_dup->close();

        // Inscribir equipo
        $stmt = $conn->prepare("INSERT INTO torneo_participantes (torneo_id, participante_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $torneo_id, $equipo_id);
        $stmt->execute();
        $stmt->close();

        header("Location: $redirect_url&success=Equipo inscrito exitosamente.");

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error: " . $e->getMessage());
    }

} elseif ($action == 'desinscribir') {
    try {
        // Verificar si ya hay jornadas creadas
        $stmt_jornadas = $conn->prepare("SELECT COUNT(*) as total FROM fases f
                                          JOIN jornadas j ON f.id = j.fase_id
                                          WHERE f.torneo_id = ?");
        $stmt_jornadas->bind_param("i", $torneo_id);
        $stmt_jornadas->execute();
        $jornadas_result = $stmt_jornadas->get_result()->fetch_assoc();

        if ($jornadas_result['total'] > 0) {
            header("Location: $redirect_url&error=No se puede desinscribir equipos cuando ya hay jornadas creadas. Elimina las jornadas primero.");
            exit;
        }
        $stmt_jornadas->close();

        // Desinscribir equipo
        $stmt = $conn->prepare("DELETE FROM torneo_participantes WHERE torneo_id = ? AND participante_id = ?");
        $stmt->bind_param("ii", $torneo_id, $equipo_id);
        $stmt->execute();
        $stmt->close();

        header("Location: $redirect_url&success=Equipo desinscrito exitosamente.");

    } catch (Exception $e) {
        header("Location: $redirect_url&error=Error: " . $e->getMessage());
    }
}

$conn->close();
exit;
?>
