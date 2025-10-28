<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['participante_id'])) {
    echo json_encode([]);
    exit;
}

$participante_id = (int)$_GET['participante_id'];

$sql = "SELECT m.id, m.nombre_jugador, m.numero_camiseta, m.posicion, m.url_foto
        FROM miembros_plantel m
        JOIN planteles_equipo pe ON m.plantel_id = pe.id
        WHERE pe.participante_id = ?
        ORDER BY m.numero_camiseta, m.nombre_jugador";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $participante_id);
$stmt->execute();
$result = $stmt->get_result();

$jugadores = [];
while ($row = $result->fetch_assoc()) {
    $jugadores[] = $row;
}

echo json_encode($jugadores);

$stmt->close();
$conn->close();
?>
