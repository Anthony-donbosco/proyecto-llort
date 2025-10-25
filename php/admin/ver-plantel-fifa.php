<?php
// php/admin/ver_plantel.php
session_start();
require_once '../db_connect.php';

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$torneo_id = isset($_GET['torneo_id']) ? (int)$_GET['torneo_id'] : 0;
$participante_id = isset($_GET['participante_id']) ? (int)$_GET['participante_id'] : 0;

if ($torneo_id === 0) {
    header("Location: listar_torneos.php");
    exit();
}

// Obtener información del torneo y equipo
$query_info = "SELECT t.*, d.nombre_mostrado as deporte, d.codigo as codigo_deporte, d.es_por_equipos,
               p.nombre_mostrado as equipo, p.nombre_corto as equipo_corto, p.url_logo
               FROM torneos t 
               JOIN deportes d ON t.deporte_id = d.id 
               LEFT JOIN participantes p ON p.id = ?
               WHERE t.id = ?";
$stmt_info = $conn->prepare($query_info);
$stmt_info->bind_param("ii", $participante_id, $torneo_id);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();

if (!$info) {
    header("Location: listar_torneos.php");
    exit();
}

// Obtener jugadores con posiciones
if ($info['es_por_equipos'] == 1 && $participante_id > 0) {
    // Para equipos
    $query_jugadores = "SELECT j.*, pos.codigo as posicion_codigo, pos.nombre_mostrado as posicion_nombre,
                       pos.coordenada_x, pos.coordenada_y, pos.es_titular as posicion_titular
                       FROM jugadores j 
                       LEFT JOIN posiciones_deporte pos ON j.posicion_id = pos.id
                       WHERE j.torneo_id = ? AND j.participante_id = ?
                       ORDER BY j.es_titular DESC, pos.orden_visualizacion ASC, j.numero_camiseta ASC";
    $stmt_jug = $conn->prepare($query_jugadores);
    $stmt_jug->bind_param("ii", $torneo_id, $participante_id);
} else {
    // Para deportes individuales
    $query_jugadores = "SELECT j.*, NULL as posicion_codigo, NULL as posicion_nombre,
                       NULL as coordenada_x, NULL as coordenada_y, 1 as posicion_titular
                       FROM jugadores j 
                       WHERE j.torneo_id = ? AND j.participante_id IS NULL
                       ORDER BY j.nombre ASC";
    $stmt_jug = $conn->prepare($query_jugadores);
    $stmt_jug->bind_param("i", $torneo_id);
}

$stmt_jug->execute();
$jugadores = $stmt_jug->get_result()->fetch_all(MYSQLI_ASSOC);

// Separar titulares y suplentes
$titulares = [];
$suplentes = [];

foreach ($jugadores as $jugador) {
    if ($jugador['es_titular'] == 1) {
        $titulares[] = $jugador;
    } else {
        $suplentes[] = $jugador;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantel - <?php echo htmlspecialchars($info['equipo'] ?: 'Participantes'); ?></title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <style>
        .plantel-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .equipo-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        
        .equipo-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: bold;
            color: #333;
        }
        
        .campo-futbol {
            position: relative;
            width: 100%;
            max-width: 800px;
            height: 600px;
            margin: 0 auto;
            background: linear-gradient(90deg, #4a7c59 0%, #6fbf73 100%);
            border: 3px solid white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .campo-baloncesto {
            position: relative;
            width: 100%;
            max-width: 600px;
            height: 800px;
            margin: 0 auto;
            background: #d2691e;
            border: 3px solid white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .campo-voleibol {
            position: relative;
            width: 100%;
            max-width: 700px;
            height: 500px;
            margin: 0 auto;
            background: #8B4513;
            border: 3px solid white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .lineas-campo {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        
        /* Líneas del campo de fútbol */
        .campo-futbol .lineas-campo::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: white;
            transform: translateY(-50%);
        }
        
        .campo-futbol .lineas-campo::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120px;
            height: 120px;
            border: 2px solid white;
            border-radius: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Líneas del campo de baloncesto */
        .campo-baloncesto .lineas-campo::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: white;
        }
        
        .campo-baloncesto .lineas-campo::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 150px;
            height: 150px;
            border: 2px solid white;
            border-radius: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Red de voleibol */
        .campo-voleibol .lineas-campo::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            bottom: 0;
            width: 3px;
            background: white;
            transform: translateX(-50%);
        }
        
        .jugador {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #2c3e50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .jugador:hover {
            transform: scale(1.1);
            z-index: 10;
        }
        
        .jugador.capitan {
            border-color: #f39c12;
            box-shadow: 0 4px 8px rgba(243, 156, 18, 0.5);
        }
        
        .jugador-info {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .jugador:hover .jugador-info {
            opacity: 1;
        }
        
        .suplentes-container {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .suplentes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .suplente-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .suplente-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
        }
        
        .suplente-numero {
            width: 40px;
            height: 40px;
            background: #6c757d;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }
        
        .lista-individual {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .participante-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .participante-card:hover {
            border-color: #007bff;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .participante-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="plantel-container">
        <div class="equipo-header">
            <div class="equipo-logo">
                <?php if ($info['url_logo']): ?>
                    <img src="<?php echo htmlspecialchars($info['url_logo']); ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                <?php else: ?>
                    <?php echo strtoupper(substr($info['equipo'] ?: $info['deporte'], 0, 2)); ?>
                <?php endif; ?>
            </div>
            <h1><?php echo htmlspecialchars($info['equipo'] ?: $info['deporte']); ?></h1>
            <h2><?php echo htmlspecialchars($info['nombre']); ?></h2>
        </div>
        
        <?php if ($info['es_por_equipos'] == 1 && !empty($titulares)): ?>
            <!-- Campo para deportes de equipo -->
            <div class="campo-container">
                <h3>Alineación Titular</h3>
                <div class="campo-<?php echo $info['codigo_deporte']; ?>">
                    <div class="lineas-campo"></div>
                    
                    <?php foreach ($titulares as $jugador): ?>
                        <?php if ($jugador['coordenada_x'] !== null && $jugador['coordenada_y'] !== null): ?>
                            <div class="jugador <?php echo $jugador['es_capitan'] ? 'capitan' : ''; ?>"
                                 style="left: <?php echo $jugador['coordenada_x']; ?>%; top: <?php echo $jugador['coordenada_y']; ?>%; transform: translate(-50%, -50%);">
                                <?php echo $jugador['numero_camiseta'] ?: substr($jugador['nombre'], 0, 1) . substr($jugador['apellido'], 0, 1); ?>
                                <div class="jugador-info">
                                    <?php echo htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellido']); ?>
                                    <?php if ($jugador['posicion_nombre']): ?>
                                        <br><?php echo htmlspecialchars($jugador['posicion_nombre']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Suplentes -->
            <?php if (!empty($suplentes)): ?>
                <div class="suplentes-container">
                    <h3>Suplentes</h3>
                    <div class="suplentes-grid">
                        <?php foreach ($suplentes as $jugador): ?>
                            <div class="suplente-card">
                                <div class="suplente-numero">
                                    <?php echo $jugador['numero_camiseta'] ?: '?'; ?>
                                </div>
                                <strong><?php echo htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellido']); ?></strong>
                                <?php if ($jugador['posicion_nombre']): ?>
                                    <br><small><?php echo htmlspecialchars($jugador['posicion_nombre']); ?></small>
                                <?php endif; ?>
                                <?php if ($jugador['es_capitan']): ?>
                                    <br><span style="color: #f39c12;">★ Capitán</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Lista para deportes individuales -->
            <div>
                <h3>Participantes (<?php echo count($jugadores); ?>)</h3>
                <?php if (!empty($jugadores)): ?>
                    <div class="lista-individual">
                        <?php foreach ($jugadores as $jugador): ?>
                            <div class="participante-card">
                                <div class="participante-avatar">
                                    <?php echo strtoupper(substr($jugador['nombre'], 0, 1) . substr($jugador['apellido'], 0, 1)); ?>
                                </div>
                                <strong><?php echo htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellido']); ?></strong>
                                <?php if ($jugador['fecha_nacimiento']): ?>
                                    <br><small>Edad: <?php echo date('Y') - date('Y', strtotime($jugador['fecha_nacimiento'])); ?> años</small>
                                <?php endif; ?>
                                <?php if ($jugador['peso'] || $jugador['altura']): ?>
                                    <br><small>
                                        <?php if ($jugador['altura']): echo $jugador['altura'] . 'm'; endif; ?>
                                        <?php if ($jugador['peso'] && $jugador['altura']): echo ' | '; endif; ?>
                                        <?php if ($jugador['peso']): echo $jugador['peso'] . 'kg'; endif; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No hay participantes registrados en este torneo.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="actions" style="text-align: center; margin-top: 30px;">
            <a href="gestionar_jugadores.php?torneo_id=<?php echo $torneo_id; ?>&participante_id=<?php echo $participante_id; ?>" class="btn btn-primary">Editar Plantel</a>
            <a href="gestionar_torneos.php" class="btn btn-secondary">Volver a Torneos</a>
        </div>
    </div>
</body>
</html>