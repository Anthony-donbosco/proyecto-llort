<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';


$jornada_id = isset($_GET['jornada_id']) ? (int)$_GET['jornada_id'] : null;
$torneo_id = isset($_GET['torneo_id']) ? (int)$_GET['torneo_id'] : null;

if (!$jornada_id && !$torneo_id) {
    header("Location: gestionar_torneos.php?error=ID de jornada o torneo no especificado.");
    exit;
}


if ($jornada_id) {
    $stmt_jornada = $conn->prepare("SELECT j.*, f.torneo_id, t.nombre AS nombre_torneo, t.tipo_torneo
                                     FROM jornadas j
                                     JOIN fases f ON j.fase_id = f.id
                                     JOIN torneos t ON f.torneo_id = t.id
                                     WHERE j.id = ?");
    $stmt_jornada->bind_param("i", $jornada_id);
    $stmt_jornada->execute();
    $jornada = $stmt_jornada->get_result()->fetch_assoc();

    if (!$jornada) {
        header("Location: gestionar_torneos.php?error=Jornada no encontrada.");
        exit;
    }

    $torneo_id = $jornada['torneo_id'];

    
    $sql_partidos = "SELECT p.*,
                     pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                     pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                     ep.nombre_mostrado AS estado,
                     f.nombre AS nombre_fase
                     FROM partidos p
                     JOIN participantes pl ON p.participante_local_id = pl.id
                     JOIN participantes pv ON p.participante_visitante_id = pv.id
                     JOIN estados_partido ep ON p.estado_id = ep.id
                     LEFT JOIN fases f ON p.fase_id = f.id
                     WHERE p.jornada_id = ?
                     ORDER BY p.inicio_partido";

    $stmt_partidos = $conn->prepare($sql_partidos);
    $stmt_partidos->bind_param("i", $jornada_id);
    $stmt_partidos->execute();
    $partidos = $stmt_partidos->get_result();

    $es_bracket = false;
    $stmt_jornada->close();

} else {
    
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

    
    $jornada = [
        'nombre' => 'Partidos de Playoffs - ' . $torneo['nombre'],
        'nombre_torneo' => $torneo['nombre'],
        'fecha_jornada' => date('Y-m-d')
    ];

    
    $sql_partidos = "SELECT p.*,
                     pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                     pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                     ep.nombre_mostrado AS estado,
                     f.nombre AS nombre_fase
                     FROM partidos p
                     LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                     LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                     JOIN estados_partido ep ON p.estado_id = ep.id
                     LEFT JOIN fases f ON p.fase_id = f.id
                     WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4)
                     ORDER BY f.tipo_fase_id ASC, p.inicio_partido";

    $stmt_partidos = $conn->prepare($sql_partidos);
    $stmt_partidos->bind_param("i", $torneo_id);
    $stmt_partidos->execute();
    $partidos = $stmt_partidos->get_result();

    $es_bracket = true;
    $stmt_torneo->close();
}

?>

<main class="admin-page">
    <div class="page-header">
        <h1>
            <?php echo htmlspecialchars($jornada['nombre']); ?>
            <small style="color: #666; font-size: 0.7em; font-weight: normal;">
                <?php echo htmlspecialchars($jornada['nombre_torneo']); ?>
            </small>
        </h1>
        <div>
            <?php if ($es_bracket): ?>
                <a href="gestionar_torneos.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Torneos
                </a>
            <?php else: ?>
                <a href="gestionar_jornadas.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Jornadas
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="jornada-info-card">
        <?php if (!$es_bracket): ?>
            <div class="info-item">
                <i class="fas fa-calendar"></i>
                <span><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($jornada['fecha_jornada'])); ?></span>
            </div>
        <?php endif; ?>
        <div class="info-item">
            <i class="fas fa-futbol"></i>
            <span><strong>Partidos:</strong> <?php echo $partidos->num_rows; ?></span>
        </div>
        <?php if ($es_bracket): ?>
            <div class="info-item">
                <i class="fas fa-trophy"></i>
                <span><strong>Tipo:</strong> Playoffs/Bracket</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="partidos-container">
        <?php if ($partidos->num_rows > 0): ?>
            <?php while($partido = $partidos->fetch_assoc()): ?>
                <div class="partido-card">
                    <div class="partido-header">
                        <div class="partido-header-left">
                            <?php if ($es_bracket && !empty($partido['nombre_fase'])): ?>
                                <span class="partido-fase">
                                    <i class="fas fa-trophy"></i> <?php echo htmlspecialchars($partido['nombre_fase']); ?>
                                </span>
                            <?php endif; ?>
                            <span class="partido-fecha">
                                <?php echo $partido['inicio_partido'] ? date('d/m/Y H:i', strtotime($partido['inicio_partido'])) : 'Sin fecha'; ?>
                            </span>
                        </div>
                        <span class="badge <?php echo getBadgeClass($partido['estado_id']); ?>">
                            <?php echo htmlspecialchars($partido['estado']); ?>
                        </span>
                    </div>

                    <div class="partido-body">
                        <div class="equipo">
                            <div class="equipo-logo">
                                <?php if ($partido['equipo_local'] && $partido['logo_local']): ?>
                                    <img src="<?php echo htmlspecialchars($partido['logo_local']); ?>" alt="Logo">
                                <?php else: ?>
                                    <i class="fas fa-<?php echo $partido['equipo_local'] ? 'shield-alt' : 'question-circle'; ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="equipo-nombre">
                                <?php if ($partido['equipo_local']): ?>
                                    <strong><?php echo htmlspecialchars($partido['equipo_local']); ?></strong>
                                    <small><?php echo htmlspecialchars($partido['local_corto']); ?></small>
                                <?php else: ?>
                                    <strong style="color: #999;">Por definir</strong>
                                    <small style="color: #999;">Pendiente</small>
                                <?php endif; ?>
                            </div>
                            <div class="marcador">
                                <?php echo $partido['marcador_local'] ?? '-'; ?>
                            </div>
                        </div>

                        <div class="vs-separator">VS</div>

                        <div class="equipo">
                            <div class="equipo-logo">
                                <?php if ($partido['equipo_visitante'] && $partido['logo_visitante']): ?>
                                    <img src="<?php echo htmlspecialchars($partido['logo_visitante']); ?>" alt="Logo">
                                <?php else: ?>
                                    <i class="fas fa-<?php echo $partido['equipo_visitante'] ? 'shield-alt' : 'question-circle'; ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="equipo-nombre">
                                <?php if ($partido['equipo_visitante']): ?>
                                    <strong><?php echo htmlspecialchars($partido['equipo_visitante']); ?></strong>
                                    <small><?php echo htmlspecialchars($partido['visitante_corto']); ?></small>
                                <?php else: ?>
                                    <strong style="color: #999;">Por definir</strong>
                                    <small style="color: #999;">Pendiente</small>
                                <?php endif; ?>
                            </div>
                            <div class="marcador">
                                <?php echo $partido['marcador_visitante'] ?? '-'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="partido-footer">
                        <?php if ($partido['equipo_local'] && $partido['equipo_visitante']): ?>
                            <a href="editar_partido.php?partido_id=<?php echo $partido['id']; ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-edit"></i> Editar Resultado
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">
                                <i class="fas fa-lock"></i> Equipos por definir
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times" style="font-size: 3rem; color: #ccc;"></i>
                <h3>No hay partidos en esta jornada</h3>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.jornada-info-card {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    gap: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-item i {
    color: #1a237e;
    font-size: 1.25rem;
}

.partidos-container {
    display: grid;
    gap: 1.5rem;
}

.partido-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: all 0.2s;
}

.partido-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.partido-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #dee2e6;
}

.partido-header-left {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.partido-fase {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
    color: white;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.partido-fase i {
    font-size: 0.8rem;
}

.partido-fecha {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
}

.partido-body {
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 1.5rem;
    align-items: center;
}

.equipo {
    display: grid;
    grid-template-columns: 60px 1fr 60px;
    gap: 1rem;
    align-items: center;
}

.equipo-logo {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 8px;
}

.equipo-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.equipo-logo i {
    font-size: 2rem;
    color: #999;
}

.equipo-nombre {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.equipo-nombre strong {
    font-size: 1.1rem;
    color: #333;
}

.equipo-nombre small {
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
}

.marcador {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1a237e;
    color: white;
    border-radius: 8px;
    font-size: 2rem;
    font-weight: bold;
}

.vs-separator {
    font-weight: bold;
    color: #999;
    font-size: 1.25rem;
}

.partido-footer {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: center;
}

@media (max-width: 768px) {
    .partido-body {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .vs-separator {
        text-align: center;
    }
}
</style>

<?php
function getBadgeClass($estado_id) {
    switch($estado_id) {
        case 1: 
        case 2: 
            return 'badge-secondary';
        case 3: 
            return 'badge-success';
        case 5: 
            return 'badge-primary';
        case 6: 
        case 8: 
            return 'badge-warning';
        case 7: 
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

$stmt_partidos->close();
require_once 'admin_footer.php';
?>
