<?php
// php/admin/gestionar_jugadores.php
session_start();
require_once '../db_connect.php';

$torneo_id = isset($_GET['torneo_id']) ? (int)$_GET['torneo_id'] : 0;
$participante_id = isset($_GET['participante_id']) ? (int)$_GET['participante_id'] : 0;

if ($torneo_id === 0) {
    header("Location: listar_torneos.php");
    exit();
}

// Obtener información del torneo
$query_torneo = "SELECT t.*, d.nombre_mostrado as deporte, d.es_por_equipos, d.codigo as codigo_deporte 
                 FROM torneos t 
                 JOIN deportes d ON t.deporte_id = d.id 
                 WHERE t.id = ?";
$stmt_torneo = $conn->prepare($query_torneo);
$stmt_torneo->bind_param("i", $torneo_id);
$stmt_torneo->execute();
$torneo = $stmt_torneo->get_result()->fetch_assoc();

if (!$torneo) {
    header("Location: listar_torneos.php");
    exit();
}

// Obtener posiciones del deporte si es por equipos
$posiciones = [];
if ($torneo['es_por_equipos'] == 1) {
    $query_posiciones = "SELECT * FROM posiciones_deporte WHERE deporte_id = ? ORDER BY orden_visualizacion";
    $stmt_pos = $conn->prepare($query_posiciones);
    $stmt_pos->bind_param("i", $torneo['deporte_id']);
    $stmt_pos->execute();
    $posiciones = $stmt_pos->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Obtener equipos/participantes del torneo
$query_participantes = "SELECT p.* FROM participantes p 
                       JOIN torneo_participantes tp ON p.id = tp.participante_id 
                       WHERE tp.torneo_id = ? ORDER BY p.nombre_mostrado";
$stmt_part = $conn->prepare($query_participantes);
$stmt_part->bind_param("i", $torneo_id);
$stmt_part->execute();
$participantes = $stmt_part->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener jugadores existentes
$jugadores = [];
if ($participante_id > 0) {
    $query_jugadores = "SELECT j.*, pos.nombre_mostrado as posicion_nombre, pos.codigo as posicion_codigo
                       FROM jugadores j 
                       LEFT JOIN posiciones_deporte pos ON j.posicion_id = pos.id
                       WHERE j.torneo_id = ? AND j.participante_id = ?
                       ORDER BY j.es_titular DESC, j.numero_camiseta ASC";
    $stmt_jug = $conn->prepare($query_jugadores);
    $stmt_jug->bind_param("ii", $torneo_id, $participante_id);
    $stmt_jug->execute();
    $jugadores = $stmt_jug->get_result()->fetch_all(MYSQLI_ASSOC);
} else if ($torneo['es_por_equipos'] == 0) {
    // Para deportes individuales, obtener todos los jugadores del torneo
    $query_jugadores = "SELECT j.*, pos.nombre_mostrado as posicion_nombre, pos.codigo as posicion_codigo
                       FROM jugadores j 
                       LEFT JOIN posiciones_deporte pos ON j.posicion_id = pos.id
                       WHERE j.torneo_id = ? AND j.participante_id IS NULL
                       ORDER BY j.nombre ASC";
    $stmt_jug = $conn->prepare($query_jugadores);
    $stmt_jug->bind_param("i", $torneo_id);
    $stmt_jug->execute();
    $jugadores = $stmt_jug->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'agregar_jugador') {
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $numero_camiseta = !empty($_POST['numero_camiseta']) ? (int)$_POST['numero_camiseta'] : null;
        $posicion_id = !empty($_POST['posicion_id']) ? (int)$_POST['posicion_id'] : null;
        $es_capitan = isset($_POST['es_capitan']) ? 1 : 0;
        $es_titular = isset($_POST['es_titular']) ? 1 : 0;
        $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
        $peso = !empty($_POST['peso']) ? (float)$_POST['peso'] : null;
        $altura = !empty($_POST['altura']) ? (float)$_POST['altura'] : null;
        
        // Para deportes individuales, participante_id es NULL
        $participante_insert = ($torneo['es_por_equipos'] == 1) ? $participante_id : null;
        
        $query_insert = "INSERT INTO jugadores (participante_id, torneo_id, deporte_id, posicion_id, nombre, apellido, numero_camiseta, es_capitan, es_titular, fecha_nacimiento, peso, altura) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($query_insert);
        $stmt_insert->bind_param("iiiissiissdd", $participante_insert, $torneo_id, $torneo['deporte_id'], $posicion_id, $nombre, $apellido, $numero_camiseta, $es_capitan, $es_titular, $fecha_nacimiento, $peso, $altura);
        
        if ($stmt_insert->execute()) {
            $success_message = "Jugador agregado correctamente";
            // Recargar jugadores
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $error_message = "Error al agregar jugador: " . $conn->error;
        }
    }
    
    if ($action === 'eliminar_jugador') {
        $jugador_id = (int)$_POST['jugador_id'];
        $query_delete = "DELETE FROM jugadores WHERE id = ? AND torneo_id = ?";
        $stmt_delete = $conn->prepare($query_delete);
        $stmt_delete->bind_param("ii", $jugador_id, $torneo_id);
        
        if ($stmt_delete->execute()) {
            $success_message = "Jugador eliminado correctamente";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $error_message = "Error al eliminar jugador";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Jugadores - <?php echo htmlspecialchars($torneo['nombre']); ?></title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <style>
        .jugador-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .jugador-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .jugador-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .capitan-badge {
            background: #f39c12;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .titular-badge {
            background: #27ae60;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .suplente-badge {
            background: #95a5a6;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .participante-selector {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestionar Jugadores</h1>
        <h2><?php echo htmlspecialchars($torneo['nombre']); ?> - <?php echo htmlspecialchars($torneo['deporte']); ?></h2>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($torneo['es_por_equipos'] == 1): ?>
            <!-- Selector de equipo para deportes por equipos -->
            <div class="participante-selector">
                <h3>Seleccionar Equipo</h3>
                <form method="GET">
                    <input type="hidden" name="torneo_id" value="<?php echo $torneo_id; ?>">
                    <select name="participante_id" onchange="this.form.submit()" class="form-control">
                        <option value="0">-- Seleccionar Equipo --</option>
                        <?php foreach ($participantes as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $participante_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nombre_mostrado']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if (($torneo['es_por_equipos'] == 1 && $participante_id > 0) || $torneo['es_por_equipos'] == 0): ?>
            <!-- Formulario para agregar jugador -->
            <div class="form-container">
                <h3>Agregar <?php echo ($torneo['es_por_equipos'] == 1) ? 'Jugador al Equipo' : 'Participante'; ?></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="agregar_jugador">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Apellido *</label>
                            <input type="text" name="apellido" class="form-control" required>
                        </div>
                        
                        <?php if ($torneo['es_por_equipos'] == 1): ?>
                            <div class="form-group">
                                <label>Número de Camiseta</label>
                                <input type="number" name="numero_camiseta" class="form-control" min="1" max="99">
                            </div>
                            
                            <div class="form-group">
                                <label>Posición</label>
                                <select name="posicion_id" class="form-control">
                                    <option value="">-- Seleccionar Posición --</option>
                                    <?php foreach ($posiciones as $pos): ?>
                                        <option value="<?php echo $pos['id']; ?>">
                                            <?php echo htmlspecialchars($pos['nombre_mostrado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Peso (kg)</label>
                            <input type="number" name="peso" class="form-control" step="0.1" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label>Altura (m)</label>
                            <input type="number" name="altura" class="form-control" step="0.01" min="0" max="3">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <?php if ($torneo['es_por_equipos'] == 1): ?>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="es_capitan" value="1"> Capitán
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="es_titular" value="1" checked> Titular
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Agregar <?php echo ($torneo['es_por_equipos'] == 1) ? 'Jugador' : 'Participante'; ?></button>
                </form>
            </div>
            
            <!-- Lista de jugadores -->
            <div>
                <h3>
                    <?php echo ($torneo['es_por_equipos'] == 1) ? 'Jugadores del Equipo' : 'Participantes del Torneo'; ?>
                    (<?php echo count($jugadores); ?>)
                </h3>
                
                <?php if (empty($jugadores)): ?>
                    <p>No hay <?php echo ($torneo['es_por_equipos'] == 1) ? 'jugadores registrados en este equipo' : 'participantes registrados en este torneo'; ?>.</p>
                <?php else: ?>
                    <?php foreach ($jugadores as $jugador): ?>
                        <div class="jugador-card">
                            <div class="jugador-header">
                                <h4>
                                    <?php echo htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellido']); ?>
                                    <?php if ($jugador['numero_camiseta']): ?>
                                        <span style="color: #666;">#<?php echo $jugador['numero_camiseta']; ?></span>
                                    <?php endif; ?>
                                </h4>
                                
                                <div>
                                    <?php if ($jugador['es_capitan']): ?>
                                        <span class="capitan-badge">Capitán</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($torneo['es_por_equipos'] == 1): ?>
                                        <span class="<?php echo $jugador['es_titular'] ? 'titular-badge' : 'suplente-badge'; ?>">
                                            <?php echo $jugador['es_titular'] ? 'Titular' : 'Suplente'; ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este jugador?');">
                                        <input type="hidden" name="action" value="eliminar_jugador">
                                        <input type="hidden" name="jugador_id" value="<?php echo $jugador['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="jugador-info">
                                <?php if ($jugador['posicion_nombre']): ?>
                                    <div><strong>Posición:</strong> <?php echo htmlspecialchars($jugador['posicion_nombre']); ?></div>
                                <?php endif; ?>
                                
                                <?php if ($jugador['fecha_nacimiento']): ?>
                                    <div><strong>F. Nacimiento:</strong> <?php echo date('d/m/Y', strtotime($jugador['fecha_nacimiento'])); ?></div>
                                <?php endif; ?>
                                
                                <?php if ($jugador['peso']): ?>
                                    <div><strong>Peso:</strong> <?php echo $jugador['peso']; ?> kg</div>
                                <?php endif; ?>
                                
                                <?php if ($jugador['altura']): ?>
                                    <div><strong>Altura:</strong> <?php echo $jugador['altura']; ?> m</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="actions">
                <a href="ver_plantel.php?torneo_id=<?php echo $torneo_id; ?>&participante_id=<?php echo $participante_id; ?>" class="btn btn-info">Ver Plantel Estilo FIFA</a>
                <a href="gestionar_torneos.php" class="btn btn-secondary">Volver a Torneos</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>