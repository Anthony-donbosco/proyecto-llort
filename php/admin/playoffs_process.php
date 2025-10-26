<?php
require_once 'auth_admin.php';

if (!isset($_POST['action']) || !isset($_POST['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=Parámetros inválidos.");
    exit;
}

$action = $_POST['action'];
$torneo_id = (int)$_POST['torneo_id'];

if ($action == 'generar') {
    try {
        
        if (!isset($_POST['emparejamientos']) || !isset($_POST['fechas']) || !isset($_POST['nombre_fase'])) {
            header("Location: generar_playoffs.php?torneo_id=$torneo_id&error=Faltan datos requeridos.");
            exit;
        }

        $emparejamientos = $_POST['emparejamientos'];
        $fechas = $_POST['fechas'];
        $nombre_fase = trim($_POST['nombre_fase']);

        
        if (count($emparejamientos) != 4 || count($fechas) != 4) {
            header("Location: generar_playoffs.php?torneo_id=$torneo_id&error=Número incorrecto de emparejamientos o fechas.");
            exit;
        }

        
        $conn->begin_transaction();

        
        $stmt_fase = $conn->prepare("INSERT INTO fases (torneo_id, tipo_fase_id, orden_fase, nombre, fecha_inicio, fecha_fin)
                                      VALUES (?, 5, 2, ?, ?, ?)");

        
        $fechas_ordenadas = $fechas;
        sort($fechas_ordenadas);
        $fecha_inicio = date('Y-m-d', strtotime($fechas_ordenadas[0]));
        $fecha_fin = date('Y-m-d', strtotime($fechas_ordenadas[3]));

        $stmt_fase->bind_param("isss", $torneo_id, $nombre_fase, $fecha_inicio, $fecha_fin);
        $stmt_fase->execute();
        $fase_id = $conn->insert_id;
        $stmt_fase->close();

        
        $stmt_partido = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                         VALUES (?, ?, ?, ?, ?, 2)");

        for ($i = 0; $i < 4; $i++) {
            $local_id = (int)$emparejamientos[$i]['local_id'];
            $visitante_id = (int)$emparejamientos[$i]['visitante_id'];
            $fecha_partido = $fechas[$i];

            
            if (empty($fecha_partido)) {
                throw new Exception("La fecha del partido " . ($i + 1) . " no puede estar vacía.");
            }

            
            if ($local_id == $visitante_id) {
                throw new Exception("Un equipo no puede jugar contra sí mismo.");
            }

            
            $stmt_partido->bind_param("iiiss", $torneo_id, $fase_id, $local_id, $visitante_id, $fecha_partido);
            $stmt_partido->execute();
        }

        $stmt_partido->close();

        
        $conn->commit();

        header("Location: gestionar_jornadas.php?torneo_id=$torneo_id&success=Cuartos de final generados exitosamente.");

    } catch (Exception $e) {
        
        $conn->rollback();
        header("Location: generar_playoffs.php?torneo_id=$torneo_id&error=Error al generar playoffs: " . $e->getMessage());
    }

} else {
    header("Location: gestionar_torneos.php?error=Acción no válida.");
}

$conn->close();
exit;
?>
