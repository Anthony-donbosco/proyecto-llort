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
        // Validar datos requeridos
        if (!isset($_POST['emparejamientos']) || !isset($_POST['fechas']) || !isset($_POST['nombre_fase'])) {
            header("Location: generar_playoffs.php?torneo_id=$torneo_id&error=Faltan datos requeridos.");
            exit;
        }

        $emparejamientos = $_POST['emparejamientos'];
        $fechas = $_POST['fechas'];
        $nombre_fase = trim($_POST['nombre_fase']);

        // Validar que haya 4 emparejamientos y 4 fechas
        if (count($emparejamientos) != 4 || count($fechas) != 4) {
            header("Location: generar_playoffs.php?torneo_id=$torneo_id&error=Número incorrecto de emparejamientos o fechas.");
            exit;
        }

        // Iniciar transacción
        $conn->begin_transaction();

        // Crear la fase de Cuartos de Final (tipo_fase_id = 5)
        $stmt_fase = $conn->prepare("INSERT INTO fases (torneo_id, tipo_fase_id, orden_fase, nombre, fecha_inicio, fecha_fin)
                                      VALUES (?, 5, 2, ?, ?, ?)");

        // Calcular fecha de inicio y fin basado en las fechas de los partidos
        $fechas_ordenadas = $fechas;
        sort($fechas_ordenadas);
        $fecha_inicio = date('Y-m-d', strtotime($fechas_ordenadas[0]));
        $fecha_fin = date('Y-m-d', strtotime($fechas_ordenadas[3]));

        $stmt_fase->bind_param("isss", $torneo_id, $nombre_fase, $fecha_inicio, $fecha_fin);
        $stmt_fase->execute();
        $fase_id = $conn->insert_id;
        $stmt_fase->close();

        // Crear los 4 partidos
        $stmt_partido = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                         VALUES (?, ?, ?, ?, ?, 2)");

        for ($i = 0; $i < 4; $i++) {
            $local_id = (int)$emparejamientos[$i]['local_id'];
            $visitante_id = (int)$emparejamientos[$i]['visitante_id'];
            $fecha_partido = $fechas[$i];

            // Validar que la fecha no esté vacía
            if (empty($fecha_partido)) {
                throw new Exception("La fecha del partido " . ($i + 1) . " no puede estar vacía.");
            }

            // Validar que los equipos sean diferentes
            if ($local_id == $visitante_id) {
                throw new Exception("Un equipo no puede jugar contra sí mismo.");
            }

            // Insertar partido - estado_id = 2 (Programado)
            $stmt_partido->bind_param("iiiss", $torneo_id, $fase_id, $local_id, $visitante_id, $fecha_partido);
            $stmt_partido->execute();
        }

        $stmt_partido->close();

        // Confirmar transacción
        $conn->commit();

        header("Location: gestionar_jornadas.php?torneo_id=$torneo_id&success=Cuartos de final generados exitosamente.");

    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $conn->rollback();
        header("Location: generar_playoffs.php?torneo_id=$torneo_id&error=Error al generar playoffs: " . $e->getMessage());
    }

} else {
    header("Location: gestionar_torneos.php?error=Acción no válida.");
}

$conn->close();
exit;
?>
