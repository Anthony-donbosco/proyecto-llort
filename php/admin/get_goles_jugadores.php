<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['torneo_id'])) {
    echo json_encode([]);
    exit;
}

$torneo_id = (int)$_GET['torneo_id'];

$sql = "SELECT m.id, COUNT(ep.id) AS total_goles
        FROM eventos_partido ep
        JOIN miembros_plantel m ON ep.miembro_plantel_id = m.id
        JOIN partidos pa ON ep.partido_id = pa.id
        WHERE pa.torneo_id = ?
        AND ep.tipo_evento IN ('gol', 'penal_anotado', 'anotacion_1pt', 'anotacion_2pts', 'anotacion_3pts', 'triple')
        GROUP BY m.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $torneo_id);
$stmt->execute();
$result = $stmt->get_result();

$goles = [];
while ($row = $result->fetch_assoc()) {
    $goles[$row['id']] = (int)$row['total_goles'];
}

echo json_encode($goles);

$stmt->close();
$conn->close();
?>
