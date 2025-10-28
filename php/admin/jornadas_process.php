<?php
require_once 'auth_admin.php';

if (!isset($_GET['action']) || !isset($_GET['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=Parámetros inválidos.");
    exit;
}

$action = $_GET['action'];
$torneo_id = (int)$_GET['torneo_id'];

if ($action == 'generar') {
    
    $stmt_torneo = $conn->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmt_torneo->bind_param("i", $torneo_id);
    $stmt_torneo->execute();
    $torneo = $stmt_torneo->get_result()->fetch_assoc();

    if (!$torneo) {
        header("Location: gestionar_torneos.php?error=Torneo no encontrado.");
        exit;
    }

    // Prevenir generación de jornadas en torneos tipo bracket
    if ($torneo['tipo_torneo'] == 'bracket') {
        header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=No se pueden generar jornadas en torneos tipo Bracket.");
        exit;
    }

    
    $stmt_equipos = $conn->prepare("SELECT participante_id FROM torneo_participantes WHERE torneo_id = ? ORDER BY semilla, participante_id");
    $stmt_equipos->bind_param("i", $torneo_id);
    $stmt_equipos->execute();
    $result_equipos = $stmt_equipos->get_result();

    $equipos = [];
    while ($row = $result_equipos->fetch_assoc()) {
        $equipos[] = $row['participante_id'];
    }

    if (count($equipos) < 2) {
        header("Location: gestionar_jornadas.php?torneo_id=$torneo_id&error=Se necesitan al menos 2 equipos.");
        exit;
    }

    
    $stmt_fase = $conn->prepare("SELECT id FROM fases WHERE torneo_id = ? AND tipo_fase_id = 1 LIMIT 1");
    $stmt_fase->bind_param("i", $torneo_id);
    $stmt_fase->execute();
    $result_fase = $stmt_fase->get_result();

    if ($result_fase->num_rows > 0) {
        $fase_id = $result_fase->fetch_assoc()['id'];
    } else {
        
        $stmt_create_fase = $conn->prepare("INSERT INTO fases (torneo_id, tipo_fase_id, orden_fase, nombre, fecha_inicio) VALUES (?, 1, 1, 'Fase de Liga', ?)");
        $stmt_create_fase->bind_param("is", $torneo_id, $torneo['fecha_inicio']);
        $stmt_create_fase->execute();
        $fase_id = $conn->insert_id;
        $stmt_create_fase->close();
    }

    
    $num_equipos = count($equipos);
    $jornadas_ida = $num_equipos - 1;
    $jornadas_totales = $torneo['ida_y_vuelta'] ? $jornadas_ida * 2 : $jornadas_ida;

    
    if ($num_equipos % 2 != 0) {
        $equipos[] = null; 
        $num_equipos++;
    }

    $partidos_por_jornada = $num_equipos / 2;
    $fecha_actual = new DateTime($torneo['fecha_inicio']);

    
    for ($jornada_num = 1; $jornada_num <= $jornadas_ida; $jornada_num++) {
        
        $fecha_jornada = $fecha_actual->format('Y-m-d');
        $nombre_jornada = "Jornada " . $jornada_num;

        $stmt_jornada = $conn->prepare("INSERT INTO jornadas (fase_id, numero_jornada, fecha_jornada, nombre) VALUES (?, ?, ?, ?)");
        $stmt_jornada->bind_param("iiss", $fase_id, $jornada_num, $fecha_jornada, $nombre_jornada);
        $stmt_jornada->execute();
        $jornada_id = $conn->insert_id;
        $stmt_jornada->close();

        
        for ($i = 0; $i < $partidos_por_jornada; $i++) {
            $local = $equipos[$i];
            $visitante = $equipos[$num_equipos - 1 - $i];

            
            if ($local === null || $visitante === null) continue;

            
            $inicio_partido = $fecha_jornada . ' 15:00:00'; 

            $stmt_partido = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, jornada_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                            VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt_partido->bind_param("iiiiss", $torneo_id, $fase_id, $jornada_id, $local, $visitante, $inicio_partido);
            $stmt_partido->execute();
            $stmt_partido->close();
        }

        
        $primer_equipo = $equipos[0];
        array_shift($equipos);
        $ultimo_equipo = array_pop($equipos);
        array_unshift($equipos, $ultimo_equipo);
        array_unshift($equipos, $primer_equipo);

        
        $fecha_actual->modify('+7 days');
    }

    
    if ($torneo['ida_y_vuelta']) {
        
        $stmt_equipos->execute();
        $result_equipos = $stmt_equipos->get_result();
        $equipos = [];
        while ($row = $result_equipos->fetch_assoc()) {
            $equipos[] = $row['participante_id'];
        }

        if (count($equipos) % 2 != 0) {
            $equipos[] = null;
            $num_equipos = count($equipos);
        }

        for ($jornada_num = $jornadas_ida + 1; $jornada_num <= $jornadas_totales; $jornada_num++) {
            $fecha_jornada = $fecha_actual->format('Y-m-d');
            $nombre_jornada = "Jornada " . $jornada_num;

            $stmt_jornada = $conn->prepare("INSERT INTO jornadas (fase_id, numero_jornada, fecha_jornada, nombre) VALUES (?, ?, ?, ?)");
            $stmt_jornada->bind_param("iiss", $fase_id, $jornada_num, $fecha_jornada, $nombre_jornada);
            $stmt_jornada->execute();
            $jornada_id = $conn->insert_id;
            $stmt_jornada->close();

            for ($i = 0; $i < $partidos_por_jornada; $i++) {
                
                $visitante = $equipos[$i];
                $local = $equipos[$num_equipos - 1 - $i];

                if ($local === null || $visitante === null) continue;

                $inicio_partido = $fecha_jornada . ' 15:00:00';

                $stmt_partido = $conn->prepare("INSERT INTO partidos (torneo_id, fase_id, jornada_id, participante_local_id, participante_visitante_id, inicio_partido, estado_id)
                                                VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt_partido->bind_param("iiiiss", $torneo_id, $fase_id, $jornada_id, $local, $visitante, $inicio_partido);
                $stmt_partido->execute();
                $stmt_partido->close();
            }

            $primer_equipo = $equipos[0];
            array_shift($equipos);
            $ultimo_equipo = array_pop($equipos);
            array_unshift($equipos, $ultimo_equipo);
            array_unshift($equipos, $primer_equipo);

            $fecha_actual->modify('+7 days');
        }
    }

    header("Location: gestionar_jornadas.php?torneo_id=$torneo_id&success=Jornadas generadas exitosamente.");
    exit;
}

header("Location: gestionar_torneos.php?error=Acción no válida.");
exit;
?>
