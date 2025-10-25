<?php
// php/admin/gestionar_bracket.php
session_start();
require_once '../db_connect.php';


$torneo_id = isset($_GET['torneo_id']) ? (int)$_GET['torneo_id'] : 0;

if ($torneo_id === 0) {
    header("Location: listar_torneos.php");
    exit();
}

// Obtener información del torneo
$query_torneo = "SELECT t.*, d.nombre_mostrado as deporte, d.codigo as codigo_deporte, d.es_por_equipos
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

// Para ajedrez, iniciar directamente como bracket
if ($torneo['codigo_deporte'] === 'chess' && $torneo['tipo_torneo'] !== 'bracket') {
    $update_query = "UPDATE torneos SET tipo_torneo = 'bracket', fase_actual = 'cuartos' WHERE id = ?";
    $stmt_update = $conn->prepare($update_query);
    $stmt_update->bind_param("i", $torneo_id);
    $stmt_update->execute();
    $torneo['tipo_torneo'] = 'bracket';
    $torneo['fase_actual'] = 'cuartos';
}

// Obtener participantes del torneo
$query_participantes = "SELECT p.*, tp.semilla
                       FROM participantes p 
                       JOIN torneo_participantes tp ON p.id = tp.participante_id 
                       WHERE tp.torneo_id = ? 
                       ORDER BY tp.semilla ASC, p.nombre_mostrado ASC";
$stmt_part = $conn->prepare($query_participantes);
$stmt_part->bind_param("i", $torneo_id);
$stmt_part->execute();
$participantes = $stmt_part->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener bracket actual
$query_bracket = "SELECT bt.*, p.nombre_mostrado as participante_nombre, p.nombre_corto
                 FROM bracket_torneos bt
                 LEFT JOIN participantes p ON bt.participante_id = p.id
                 WHERE bt.torneo_id = ?
                 ORDER BY bt.fase, bt.posicion_bracket";
$stmt_bracket = $conn->prepare($query_bracket);
$stmt_bracket->bind_param("i", $torneo_id);
$stmt_bracket->execute();
$bracket_data = $stmt_bracket->get_result()->fetch_all(MYSQLI_ASSOC);

// Organizar bracket por fases
$bracket = [
    'cuartos' => [],
    'semis' => [],
    'final' => []
];

foreach ($bracket_data as $item) {
    $bracket[$item['fase']][$item['posicion_bracket']] = $item;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'cambiar_fase_bracket') {
        // Cambiar torneo a modo bracket
        $update_query = "UPDATE torneos SET tipo_torneo = 'bracket', fase_actual = 'cuartos' WHERE id = ?";
        $stmt_update = $conn->prepare($update_query);
        $stmt_update->bind_param("i", $torneo_id);
        
        if ($stmt_update->execute()) {
            // Crear estructura inicial del bracket con los mejores equipos
            $num_participantes = count($participantes);
            $cuartos_participantes = array_slice($participantes, 0, min(8, $num_participantes));
            
            // Limpiar bracket existente
            $delete_query = "DELETE FROM bracket_torneos WHERE torneo_id = ?";
            $stmt_delete = $conn->prepare($delete_query);
            $stmt_delete->bind_param("i", $torneo_id);
            $stmt_delete->execute();
            
            // Insertar participantes en cuartos
            $insert_query = "INSERT INTO bracket_torneos (torneo_id, fase, posicion_bracket, participante_id) VALUES (?, 'cuartos', ?, ?)";
            $stmt_insert = $conn->prepare($insert_query);
            
            for ($i = 0; $i < count($cuartos_participantes); $i++) {
                $posicion = $i + 1;
                $participante_id = $cuartos_participantes[$i]['id'];
                $stmt_insert->bind_param("iii", $torneo_id, $posicion, $participante_id);
                $stmt_insert->execute();
            }
            
            $success_message = "Torneo cambiado a modo bracket. Se han seleccionado los " . count($cuartos_participantes) . " mejores equipos para cuartos de final.";
            
            // Recargar datos del bracket
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $error_message = "Error al cambiar a modo bracket";
        }
    }
    
    if ($action === 'actualizar_bracket') {
        $fase = $_POST['fase'];
        $participantes_seleccionados = $_POST['participantes'] ?? [];
        
        // Validar número de participantes por fase
        $max_participantes = [
            'cuartos' => 8,
            'semis' => 4,
            'final' => 2
        ];
        
        if (count($participantes_seleccionados) > $max_participantes[$fase]) {
            $error_message = "Demasiados participantes para la fase de " . $fase;
        } else {
            // Limpiar fase actual
            $delete_query = "DELETE FROM bracket_torneos WHERE torneo_id = ? AND fase = ?";
            $stmt_delete = $conn->prepare($delete_query);
            $stmt_delete->bind_param("is", $torneo_id, $fase);
            $stmt_delete->execute();
            
            // Insertar nuevos participantes
            $insert_query = "INSERT INTO bracket_torneos (torneo_id, fase, posicion_bracket, participante_id) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_query);
            
            $posicion = 1;
            foreach ($participantes_seleccionados as $participante_id) {
                if (!empty($participante_id)) {
                    $stmt_insert->bind_param("isii", $torneo_id, $fase, $posicion, $participante_id);
                    $stmt_insert->execute();
                    $posicion++;
                }
            }
            
            // Actualizar fase actual del torneo
            $update_torneo = "UPDATE torneos SET fase_actual = ? WHERE id = ?";
            $stmt_update_torneo = $conn->prepare($update_torneo);
            $stmt_update_torneo->bind_param("si", $fase, $torneo_id);
            $stmt_update_torneo->execute();
            
            $success_message = "Bracket de " . $fase . " actualizado correctamente";
            
            // Recargar página
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Bracket - <?php echo htmlspecialchars($torneo['nombre']); ?></title>
    <link href="../../css/admin_style.css" rel="stylesheet">
    <style>
        .bracket-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .torneo-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .fase-selector {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        
        .bracket-visual {
            display: flex;
            justify-content: space-around;
            align-items: stretch;
            gap: 20px;
            margin: 30px 0;
            min-height: 400px;
        }
        
        .fase-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 20px;
        }
        
        .fase-title {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
            padding: 10px;
            background: #007bff;
            color: white;
            border-radius: 8px;
        }
        
        .participante-bracket {
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .participante-bracket:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .participante-bracket.vacio {
            border-style: dashed;
            color: #999;
            background: #f8f9fa;
        }
        
        .participante-bracket.ganador {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .conectores {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 30px;
        }
        
        .conector {
            width: 30px;
            height: 2px;
            background: #ddd;
            position: relative;
        }
        
        .conector::before,
        .conector::after {
            content: '';
            position: absolute;
            right: 0;
            width: 0;
            height: 0;
            border-left: 8px solid #ddd;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
        }
        
        .participantes-disponibles {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .participantes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .participante-item {
            background: white;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .participante-item:hover {
            border-color: #007bff;
            background: #e3f2fd;
        }
        
        .participante-item.selected {
            background: #007bff;
            color: white;
            border-color: #0056b3;
        }
        
        .form-bracket {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
        
        .fase-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .fase-tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .fase-tab.active {
            background: #007bff;
            color: white;
            border-color: #0056b3;
        }
        
        .posicion-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .posicion-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="bracket-container">
        <div class="torneo-info">
            <div>
                <h1><?php echo htmlspecialchars($torneo['nombre']); ?></h1>
                <p><?php echo htmlspecialchars($torneo['deporte']); ?> | Participantes: <?php echo count($participantes); ?></p>
            </div>
            <div>
                <strong>Tipo:</strong> <?php echo $torneo['tipo_torneo']; ?><br>
                <strong>Fase Actual:</strong> <?php echo $torneo['fase_actual'] ?: 'Liga'; ?>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($torneo['tipo_torneo'] !== 'bracket'): ?>
            <!-- Cambiar a modo bracket -->
            <div class="fase-selector">
                <h3>Cambiar a Modo Eliminatoria (Bracket)</h3>
                <p>El torneo está actualmente en modo liga. Puedes cambiarlo a modo eliminatoria para gestionar cuartos de final, semifinales y final.</p>
                
                <?php if ($torneo['codigo_deporte'] === 'chess'): ?>
                    <div class="alert alert-info">
                        <strong>Torneo de Ajedrez:</strong> Los torneos de ajedrez se manejan automáticamente como brackets desde el inicio.
                    </div>
                <?php endif; ?>
                
                <form method="POST" onsubmit="return confirm('¿Estás seguro de cambiar este torneo a modo bracket? Esta acción seleccionará automáticamente los mejores equipos para la fase eliminatoria.');">
                    <input type="hidden" name="action" value="cambiar_fase_bracket">
                    <button type="submit" class="btn btn-primary">Cambiar a Modo Bracket</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Gestionar bracket -->
            <div class="bracket-visual">
                <!-- Cuartos de Final -->
                <div class="fase-column">
                    <div class="fase-title">Cuartos de Final</div>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <?php $participante = $bracket['cuartos'][$i] ?? null; ?>
                        <div class="participante-bracket <?php echo $participante ? '' : 'vacio'; ?>">
                            <?php if ($participante): ?>
                                <?php echo htmlspecialchars($participante['participante_nombre'] ?: $participante['nombre_corto'] ?: 'TBD'); ?>
                            <?php else: ?>
                                Posición <?php echo $i; ?>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <div class="conectores">
                    <div class="conector"></div>
                </div>
                
                <!-- Semifinales -->
                <div class="fase-column">
                    <div class="fase-title">Semifinales</div>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <?php $participante = $bracket['semis'][$i] ?? null; ?>
                        <div class="participante-bracket <?php echo $participante ? '' : 'vacio'; ?>" style="margin: 40px 0;">
                            <?php if ($participante): ?>
                                <?php echo htmlspecialchars($participante['participante_nombre'] ?: $participante['nombre_corto'] ?: 'TBD'); ?>
                            <?php else: ?>
                                Semi <?php echo $i; ?>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <div class="conectores">
                    <div class="conector"></div>
                </div>
                
                <!-- Final -->
                <div class="fase-column">
                    <div class="fase-title">Final</div>
                    <div style="display: flex; flex-direction: column; justify-content: center; height: 100%; gap: 60px;">
                        <?php for ($i = 1; $i <= 2; $i++): ?>
                            <?php $participante = $bracket['final'][$i] ?? null; ?>
                            <div class="participante-bracket <?php echo $participante ? '' : 'vacio'; ?>">
                                <?php if ($participante): ?>
                                    <?php echo htmlspecialchars($participante['participante_nombre'] ?: $participante['nombre_corto'] ?: 'TBD'); ?>
                                <?php else: ?>
                                    Finalista <?php echo $i; ?>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- Formulario para gestionar bracket -->
            <div class="form-bracket">
                <h3>Gestionar Bracket</h3>
                
                <div class="fase-tabs">
                    <div class="fase-tab active" onclick="showFase('cuartos')">Cuartos de Final</div>
                    <div class="fase-tab" onclick="showFase('semis')">Semifinales</div>
                    <div class="fase-tab" onclick="showFase('final')">Final</div>
                </div>
                
                <form method="POST" id="bracket-form">
                    <input type="hidden" name="action" value="actualizar_bracket">
                    <input type="hidden" name="fase" id="fase-actual" value="cuartos">
                    
                    <div class="participantes-disponibles">
                        <h4>Participantes Disponibles</h4>
                        <div class="participantes-grid">
                            <?php foreach ($participantes as $participante): ?>
                                <div class="participante-item" onclick="selectParticipante(this, <?php echo $participante['id']; ?>)">
                                    <?php echo htmlspecialchars($participante['nombre_mostrado']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="posicion-selector" id="posiciones-cuartos">
                        <h4>Asignar Posiciones para Cuartos de Final (máximo 8)</h4>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <div class="posicion-item">
                                <label>Posición <?php echo $i; ?>:</label>
                                <select name="participantes[]" class="form-control">
                                    <option value="">-- Vacío --</option>
                                    <?php foreach ($participantes as $p): ?>
                                        <?php 
                                        $selected = '';
                                        if (isset($bracket['cuartos'][$i]) && $bracket['cuartos'][$i]['participante_id'] == $p['id']) {
                                            $selected = 'selected';
                                        }
                                        ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($p['nombre_mostrado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="posicion-selector" id="posiciones-semis" style="display: none;">
                        <h4>Asignar Posiciones para Semifinales (máximo 4)</h4>
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="posicion-item">
                                <label>Posición <?php echo $i; ?>:</label>
                                <select name="participantes[]" class="form-control">
                                    <option value="">-- Vacío --</option>
                                    <?php foreach ($participantes as $p): ?>
                                        <?php 
                                        $selected = '';
                                        if (isset($bracket['semis'][$i]) && $bracket['semis'][$i]['participante_id'] == $p['id']) {
                                            $selected = 'selected';
                                        }
                                        ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($p['nombre_mostrado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="posicion-selector" id="posiciones-final" style="display: none;">
                        <h4>Asignar Posiciones para Final (máximo 2)</h4>
                        <?php for ($i = 1; $i <= 2; $i++): ?>
                            <div class="posicion-item">
                                <label>Finalista <?php echo $i; ?>:</label>
                                <select name="participantes[]" class="form-control">
                                    <option value="">-- Vacío --</option>
                                    <?php foreach ($participantes as $p): ?>
                                        <?php 
                                        $selected = '';
                                        if (isset($bracket['final'][$i]) && $bracket['final'][$i]['participante_id'] == $p['id']) {
                                            $selected = 'selected';
                                        }
                                        ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($p['nombre_mostrado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Actualizar Bracket</button>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="actions" style="text-align: center; margin-top: 30px;">
            <a href="gestionar_torneos.php" class="btn btn-secondary">Volver a Torneos</a>
            <a href="generar_partidos_bracket.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-success">Generar Partidos</a>
        </div>
    </div>

    <script>
        function showFase(fase) {
            // Actualizar tabs
            document.querySelectorAll('.fase-tab').forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Actualizar campo oculto
            document.getElementById('fase-actual').value = fase;
            
            // Mostrar/ocultar secciones
            document.querySelectorAll('.posicion-selector').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById('posiciones-' + fase).style.display = 'grid';
        }
        
        function selectParticipante(element, participanteId) {
            // Toggle selección visual
            element.classList.toggle('selected');
        }
    </script>
</body>
</html>