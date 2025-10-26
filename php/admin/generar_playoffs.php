<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=ID de torneo no especificado.");
    exit;
}

$torneo_id = (int)$_GET['torneo_id'];


$stmt_torneo = $conn->prepare("SELECT t.*, d.nombre_mostrado AS deporte
                                FROM torneos t
                                JOIN deportes d ON t.deporte_id = d.id
                                WHERE t.id = ?");
$stmt_torneo->bind_param("i", $torneo_id);
$stmt_torneo->execute();
$torneo = $stmt_torneo->get_result()->fetch_assoc();

if (!$torneo) {
    header("Location: gestionar_torneos.php?error=Torneo no encontrado.");
    exit;
}


$stmt_equipos = $conn->prepare("SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo
                                 FROM torneo_participantes tp
                                 JOIN participantes p ON tp.participante_id = p.id
                                 WHERE tp.torneo_id = ?");
$stmt_equipos->bind_param("i", $torneo_id);
$stmt_equipos->execute();
$equipos_result = $stmt_equipos->get_result();


$tabla = [];
while ($equipo = $equipos_result->fetch_assoc()) {
    $tabla[$equipo['id']] = [
        'id' => $equipo['id'],
        'nombre' => $equipo['nombre_mostrado'],
        'nombre_corto' => $equipo['nombre_corto'],
        'logo' => $equipo['url_logo'],
        'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
        'gf' => 0, 'gc' => 0, 'dg' => 0, 'pts' => 0
    ];
}


$sql_partidos = "SELECT p.participante_local_id, p.participante_visitante_id,
                 p.marcador_local, p.marcador_visitante
                 FROM partidos p
                 WHERE p.torneo_id = ? AND p.estado_id = 5";

$stmt_partidos = $conn->prepare($sql_partidos);
$stmt_partidos->bind_param("i", $torneo_id);
$stmt_partidos->execute();
$partidos = $stmt_partidos->get_result();

while ($partido = $partidos->fetch_assoc()) {
    $local_id = $partido['participante_local_id'];
    $visitante_id = $partido['participante_visitante_id'];
    $goles_local = $partido['marcador_local'];
    $goles_visitante = $partido['marcador_visitante'];

    if (!isset($tabla[$local_id]) || !isset($tabla[$visitante_id])) {
        continue;
    }

    $tabla[$local_id]['pj']++;
    $tabla[$visitante_id]['pj']++;
    $tabla[$local_id]['gf'] += $goles_local;
    $tabla[$local_id]['gc'] += $goles_visitante;
    $tabla[$visitante_id]['gf'] += $goles_visitante;
    $tabla[$visitante_id]['gc'] += $goles_local;

    if ($goles_local > $goles_visitante) {
        $tabla[$local_id]['pg']++;
        $tabla[$local_id]['pts'] += 3;
        $tabla[$visitante_id]['pp']++;
    } elseif ($goles_local < $goles_visitante) {
        $tabla[$visitante_id]['pg']++;
        $tabla[$visitante_id]['pts'] += 3;
        $tabla[$local_id]['pp']++;
    } else {
        $tabla[$local_id]['pe']++;
        $tabla[$local_id]['pts'] += 1;
        $tabla[$visitante_id]['pe']++;
        $tabla[$visitante_id]['pts'] += 1;
    }
}


foreach ($tabla as &$equipo) {
    $equipo['dg'] = $equipo['gf'] - $equipo['gc'];
}


usort($tabla, function($a, $b) {
    if ($b['pts'] != $a['pts']) return $b['pts'] - $a['pts'];
    if ($b['dg'] != $a['dg']) return $b['dg'] - $a['dg'];
    return $b['gf'] - $a['gf'];
});


if (count($tabla) < 8) {
    header("Location: finalizar_torneo.php?torneo_id=$torneo_id&error=Se necesitan al menos 8 equipos para generar playoffs.");
    exit;
}


$clasificados = array_slice($tabla, 0, 8);



$emparejamientos = [
    ['local' => $clasificados[0], 'visitante' => $clasificados[7], 'numero' => 1],
    ['local' => $clasificados[3], 'visitante' => $clasificados[4], 'numero' => 2],
    ['local' => $clasificados[2], 'visitante' => $clasificados[5], 'numero' => 3],
    ['local' => $clasificados[1], 'visitante' => $clasificados[6], 'numero' => 4],
];

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Generar Playoffs - <?php echo htmlspecialchars($torneo['nombre']); ?></h1>
        <div>
            <a href="finalizar_torneo.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="playoffs-container">
        
        <div class="info-card">
            <h2><i class="fas fa-info-circle"></i> Generación de Cuartos de Final</h2>
            <p>Se generará una nueva fase de "Cuartos de Final" con 4 partidos. Los emparejamientos se realizan según las posiciones de la tabla:</p>
            <ul>
                <li>1° vs 8° clasificado</li>
                <li>4° vs 5° clasificado</li>
                <li>3° vs 6° clasificado</li>
                <li>2° vs 7° clasificado</li>
            </ul>
        </div>

        
        <div class="clasificados-card">
            <h3><i class="fas fa-trophy"></i> Equipos Clasificados a Playoffs</h3>
            <div class="clasificados-grid">
                <?php foreach ($clasificados as $index => $equipo): ?>
                    <div class="clasificado-item">
                        <div class="clasificado-posicion"><?php echo ($index + 1); ?>°</div>
                        <div class="clasificado-logo">
                            <?php if ($equipo['logo']): ?>
                                <img src="<?php echo htmlspecialchars($equipo['logo']); ?>" alt="Logo">
                            <?php else: ?>
                                <i class="fas fa-shield-alt"></i>
                            <?php endif; ?>
                        </div>
                        <div class="clasificado-info">
                            <strong><?php echo htmlspecialchars($equipo['nombre']); ?></strong>
                            <small><?php echo $equipo['pts']; ?> pts</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        
        <form action="playoffs_process.php" method="POST" class="playoffs-form">
            <input type="hidden" name="torneo_id" value="<?php echo $torneo_id; ?>">
            <input type="hidden" name="action" value="generar">

            <?php foreach ($emparejamientos as $index => $enfrentamiento): ?>
                <input type="hidden" name="emparejamientos[<?php echo $index; ?>][local_id]" value="<?php echo $enfrentamiento['local']['id']; ?>">
                <input type="hidden" name="emparejamientos[<?php echo $index; ?>][visitante_id]" value="<?php echo $enfrentamiento['visitante']['id']; ?>">
            <?php endforeach; ?>

            <div class="emparejamientos-card">
                <h3><i class="fas fa-sitemap"></i> Emparejamientos de Cuartos de Final</h3>

                <div class="emparejamientos-list">
                    <?php foreach ($emparejamientos as $index => $enfrentamiento): ?>
                        <div class="emparejamiento-item">
                            <div class="emparejamiento-header">
                                <h4>Partido <?php echo $enfrentamiento['numero']; ?></h4>
                            </div>
                            <div class="emparejamiento-teams">
                                <div class="team-box local">
                                    <div class="team-logo">
                                        <?php if ($enfrentamiento['local']['logo']): ?>
                                            <img src="<?php echo htmlspecialchars($enfrentamiento['local']['logo']); ?>" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-shield-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="team-info">
                                        <strong><?php echo htmlspecialchars($enfrentamiento['local']['nombre']); ?></strong>
                                        <small><?php echo $enfrentamiento['local']['pts']; ?> pts - Posición 1</small>
                                    </div>
                                    <div class="team-badge local-badge">Local</div>
                                </div>

                                <div class="vs-bracket">VS</div>

                                <div class="team-box visitante">
                                    <div class="team-logo">
                                        <?php if ($enfrentamiento['visitante']['logo']): ?>
                                            <img src="<?php echo htmlspecialchars($enfrentamiento['visitante']['logo']); ?>" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-shield-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="team-info">
                                        <strong><?php echo htmlspecialchars($enfrentamiento['visitante']['nombre']); ?></strong>
                                        <small><?php echo $enfrentamiento['visitante']['pts']; ?> pts - Posición <?php echo (8 - $index); ?></small>
                                    </div>
                                    <div class="team-badge visitante-badge">Visitante</div>
                                </div>
                            </div>

                            <div class="emparejamiento-fecha">
                                <label class="form-label">Fecha y Hora del Partido</label>
                                <input type="datetime-local" name="fechas[]" class="form-input" required>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-cog"></i> Configuración de la Fase</h3>
                <div class="form-group">
                    <label class="form-label">Nombre de la Fase</label>
                    <input type="text" name="nombre_fase" class="form-input" value="Cuartos de Final" required>
                </div>
            </div>

            <div class="form-actions-center">
                <button type="submit" class="btn btn-primary btn-large" onclick="return confirm('¿Generar los cuartos de final con estos emparejamientos?');">
                    <i class="fas fa-check"></i> Generar Cuartos de Final
                </button>
                <a href="finalizar_torneo.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-secondary btn-large">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<style>
.playoffs-container {
    display: grid;
    gap: 2rem;
}

.info-card {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #1976d2;
}

.info-card h2 {
    margin: 0 0 1rem 0;
    color: #1565c0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
}

.info-card p {
    margin: 0 0 0.75rem 0;
    color: #424242;
}

.info-card ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #424242;
}

.clasificados-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.clasificados-card h3 {
    margin: 0 0 1.5rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.clasificados-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.clasificado-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
}

.clasificado-posicion {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
}

.clasificado-logo {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 8px;
    padding: 0.5rem;
}

.clasificado-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.clasificado-logo i {
    font-size: 2rem;
    color: #999;
}

.clasificado-info {
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.clasificado-info strong {
    font-size: 0.9rem;
    color: #333;
}

.clasificado-info small {
    font-size: 0.8rem;
    color: #666;
}

.emparejamientos-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.emparejamientos-card h3 {
    margin: 0 0 1.5rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.emparejamientos-list {
    display: grid;
    gap: 1.5rem;
}

.emparejamiento-item {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
}

.emparejamiento-header {
    text-align: center;
    margin-bottom: 1rem;
}

.emparejamiento-header h4 {
    margin: 0;
    color: #1a237e;
    font-size: 1.1rem;
}

.emparejamiento-teams {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 1.5rem;
    align-items: center;
    margin-bottom: 1.5rem;
}

.team-box {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    position: relative;
}

.team-logo {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 8px;
    padding: 0.5rem;
}

.team-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.team-logo i {
    font-size: 2.5rem;
    color: #999;
}

.team-info {
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.team-info strong {
    font-size: 1rem;
    color: #333;
}

.team-info small {
    font-size: 0.85rem;
    color: #666;
}

.team-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.local-badge {
    background: #e3f2fd;
    color: #1565c0;
}

.visitante-badge {
    background: #fff3e0;
    color: #e65100;
}

.vs-bracket {
    font-size: 1.5rem;
    font-weight: bold;
    color: #1a237e;
}

.emparejamiento-fecha {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.emparejamiento-fecha .form-label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #666;
    margin: 0;
}

.emparejamiento-fecha .form-input {
    width: 100%;
}

.form-actions-center {
    display: flex;
    gap: 1rem;
    justify-content: center;
    padding: 2rem 0 0 0;
}

.btn-large {
    padding: 0.75rem 2rem;
    font-size: 1rem;
}

@media (max-width: 968px) {
    .clasificados-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .emparejamiento-teams {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .vs-bracket {
        text-align: center;
    }
}

@media (max-width: 640px) {
    .clasificados-grid {
        grid-template-columns: 1fr;
    }

    .form-actions-center {
        flex-direction: column;
    }

    .btn-large {
        width: 100%;
    }
}
</style>

<?php
$stmt_torneo->close();
$stmt_equipos->close();
$stmt_partidos->close();
require_once 'admin_footer.php';
?>
