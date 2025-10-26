<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['jornada_id'])) {
    header("Location: gestionar_torneos.php?error=ID de jornada no especificado.");
    exit;
}

$jornada_id = (int)$_GET['jornada_id'];

// Obtener información de la jornada y torneo
$stmt_jornada = $conn->prepare("SELECT j.*, f.torneo_id, t.nombre AS nombre_torneo
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

// Obtener partidos de esta jornada
$sql_partidos = "SELECT p.*,
                 pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                 pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                 ep.nombre_mostrado AS estado
                 FROM partidos p
                 JOIN participantes pl ON p.participante_local_id = pl.id
                 JOIN participantes pv ON p.participante_visitante_id = pv.id
                 JOIN estados_partido ep ON p.estado_id = ep.id
                 WHERE p.jornada_id = ?
                 ORDER BY p.inicio_partido";

$stmt_partidos = $conn->prepare($sql_partidos);
$stmt_partidos->bind_param("i", $jornada_id);
$stmt_partidos->execute();
$partidos = $stmt_partidos->get_result();

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
            <a href="gestionar_jornadas.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Jornadas
            </a>
        </div>
    </div>

    <div class="jornada-info-card">
        <div class="info-item">
            <i class="fas fa-calendar"></i>
            <span><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($jornada['fecha_jornada'])); ?></span>
        </div>
        <div class="info-item">
            <i class="fas fa-futbol"></i>
            <span><strong>Partidos:</strong> <?php echo $partidos->num_rows; ?></span>
        </div>
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
                        <span class="partido-fecha"><?php echo date('H:i', strtotime($partido['inicio_partido'])); ?></span>
                        <span class="badge <?php echo getBadgeClass($partido['estado_id']); ?>">
                            <?php echo htmlspecialchars($partido['estado']); ?>
                        </span>
                    </div>

                    <div class="partido-body">
                        <div class="equipo">
                            <div class="equipo-logo">
                                <?php if ($partido['logo_local']): ?>
                                    <img src="<?php echo htmlspecialchars($partido['logo_local']); ?>" alt="Logo">
                                <?php else: ?>
                                    <i class="fas fa-shield-alt"></i>
                                <?php endif; ?>
                            </div>
                            <div class="equipo-nombre">
                                <strong><?php echo htmlspecialchars($partido['equipo_local']); ?></strong>
                                <small><?php echo htmlspecialchars($partido['local_corto']); ?></small>
                            </div>
                            <div class="marcador">
                                <?php echo $partido['marcador_local'] ?? '-'; ?>
                            </div>
                        </div>

                        <div class="vs-separator">VS</div>

                        <div class="equipo">
                            <div class="equipo-logo">
                                <?php if ($partido['logo_visitante']): ?>
                                    <img src="<?php echo htmlspecialchars($partido['logo_visitante']); ?>" alt="Logo">
                                <?php else: ?>
                                    <i class="fas fa-shield-alt"></i>
                                <?php endif; ?>
                            </div>
                            <div class="equipo-nombre">
                                <strong><?php echo htmlspecialchars($partido['equipo_visitante']); ?></strong>
                                <small><?php echo htmlspecialchars($partido['visitante_corto']); ?></small>
                            </div>
                            <div class="marcador">
                                <?php echo $partido['marcador_visitante'] ?? '-'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="partido-footer">
                        <a href="editar_partido.php?partido_id=<?php echo $partido['id']; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-edit"></i> Editar Resultado
                        </a>
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

.partido-fecha {
    font-weight: 600;
    color: #495057;
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
        case 1: // No iniciado
        case 2: // Programado
            return 'badge-secondary';
        case 3: // En vivo
            return 'badge-success';
        case 5: // Finalizado
            return 'badge-primary';
        case 6: // Pospuesto
        case 8: // Suspendido
            return 'badge-warning';
        case 7: // Cancelado
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

$stmt_jornada->close();
$stmt_partidos->close();
require_once 'admin_footer.php';
?>
