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


$stmt_inscritos = $conn->prepare("SELECT tp.participante_id, p.nombre_mostrado, p.nombre_corto, p.url_logo
                                   FROM torneo_participantes tp
                                   JOIN participantes p ON tp.participante_id = p.id
                                   WHERE tp.torneo_id = ?
                                   ORDER BY tp.inscrito_en");
$stmt_inscritos->bind_param("i", $torneo_id);
$stmt_inscritos->execute();
$inscritos = $stmt_inscritos->get_result();

$equipos_inscritos = [];
$ids_inscritos = [];
while ($row = $inscritos->fetch_assoc()) {
    $equipos_inscritos[] = $row;
    $ids_inscritos[] = $row['participante_id'];
}

$total_inscritos = count($equipos_inscritos);
$cupos_disponibles = $torneo['max_participantes'] - $total_inscritos;


$sql_disponibles = "SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo
                    FROM participantes p
                    WHERE p.deporte_id = ? AND p.tipo_participante_id = 1";

if (count($ids_inscritos) > 0) {
    $placeholders = implode(',', array_fill(0, count($ids_inscritos), '?'));
    $sql_disponibles .= " AND p.id NOT IN ($placeholders)";
}

$sql_disponibles .= " ORDER BY p.nombre_mostrado";

$stmt_disponibles = $conn->prepare($sql_disponibles);

if (count($ids_inscritos) > 0) {
    $types = str_repeat('i', count($ids_inscritos) + 1);
    $params = array_merge([$torneo['deporte_id']], $ids_inscritos);
    $stmt_disponibles->bind_param($types, ...$params);
} else {
    $stmt_disponibles->bind_param("i", $torneo['deporte_id']);
}

$stmt_disponibles->execute();
$disponibles = $stmt_disponibles->get_result();

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Inscribir Equipos - <?php echo htmlspecialchars($torneo['nombre']); ?></h1>
        <div>
            <a href="gestionar_jornadas.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Jornadas
            </a>
            <a href="gestionar_torneos.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Ver Torneos
            </a>
        </div>
    </div>

    <div class="inscripcion-info">
        <div class="info-card">
            <h3>Información del Torneo</h3>
            <div class="info-grid">
                <div>
                    <span class="label">Deporte:</span>
                    <span class="value"><?php echo htmlspecialchars($torneo['deporte']); ?></span>
                </div>
                <div>
                    <span class="label">Equipos Inscritos:</span>
                    <span class="value"><?php echo $total_inscritos; ?> / <?php echo $torneo['max_participantes']; ?></span>
                </div>
                <div>
                    <span class="label">Cupos Disponibles:</span>
                    <span class="value <?php echo $cupos_disponibles > 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $cupos_disponibles; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="inscripcion-container">
        <div class="equipos-section">
            <h2><i class="fas fa-list"></i> Equipos Disponibles</h2>

            <?php if ($cupos_disponibles <= 0): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No hay cupos disponibles. El torneo está completo.
                </div>
            <?php elseif ($disponibles->num_rows == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p>No hay más equipos disponibles para inscribir.</p>
                </div>
            <?php else: ?>
                <div class="equipos-grid">
                    <?php while($equipo = $disponibles->fetch_assoc()): ?>
                        <div class="equipo-card">
                            <div class="equipo-logo">
                                <?php if ($equipo['url_logo']): ?>
                                    <img src="<?php echo htmlspecialchars($equipo['url_logo']); ?>" alt="Logo">
                                <?php else: ?>
                                    <i class="fas fa-shield-alt"></i>
                                <?php endif; ?>
                            </div>
                            <div class="equipo-info">
                                <h4><?php echo htmlspecialchars($equipo['nombre_mostrado']); ?></h4>
                                <span class="equipo-corto"><?php echo htmlspecialchars($equipo['nombre_corto']); ?></span>
                            </div>
                            <div class="equipo-actions">
                                <a href="inscripcion_process.php?action=inscribir&torneo_id=<?php echo $torneo_id; ?>&equipo_id=<?php echo $equipo['id']; ?>"
                                   class="btn btn-primary btn-sm"
                                   onclick="return confirm('¿Inscribir este equipo al torneo?');">
                                    <i class="fas fa-plus"></i> Inscribir
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="equipos-section">
            <h2><i class="fas fa-check-circle"></i> Equipos Inscritos (<?php echo $total_inscritos; ?>)</h2>

            <?php if (count($equipos_inscritos) == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash" style="font-size: 3rem; color: #ccc;"></i>
                    <p>Aún no hay equipos inscritos en este torneo.</p>
                </div>
            <?php else: ?>
                <div class="equipos-list">
                    <?php foreach($equipos_inscritos as $equipo): ?>
                        <div class="equipo-inscrito">
                            <div class="equipo-logo-small">
                                <?php if ($equipo['url_logo']): ?>
                                    <img src="<?php echo htmlspecialchars($equipo['url_logo']); ?>" alt="Logo">
                                <?php else: ?>
                                    <i class="fas fa-shield-alt"></i>
                                <?php endif; ?>
                            </div>
                            <div class="equipo-nombre">
                                <strong><?php echo htmlspecialchars($equipo['nombre_mostrado']); ?></strong>
                                <small><?php echo htmlspecialchars($equipo['nombre_corto']); ?></small>
                            </div>
                            <div class="equipo-acciones">
                                <a href="inscripcion_process.php?action=desinscribir&torneo_id=<?php echo $torneo_id; ?>&equipo_id=<?php echo $equipo['participante_id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('¿Eliminar este equipo del torneo? Esto eliminará también sus partidos.');">
                                    <i class="fas fa-times"></i> Eliminar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
.inscripcion-info {
    margin-bottom: 2rem;
}

.info-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.info-card h3 {
    margin: 0 0 1rem 0;
    color: #1a237e;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-grid > div {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-grid .label {
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
}

.info-grid .value {
    font-size: 1.25rem;
    font-weight: bold;
    color: #1a237e;
}

.text-success {
    color: #28a745 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.inscripcion-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.equipos-section {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.equipos-section h2 {
    margin: 0 0 1.5rem 0;
    color: #1a237e;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.equipos-grid {
    display: grid;
    gap: 1rem;
}

.equipo-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.2s;
}

.equipo-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.equipo-logo {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 8px;
    flex-shrink: 0;
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

.equipo-info {
    flex: 1;
}

.equipo-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    color: #333;
}

.equipo-corto {
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
}

.equipo-actions {
    flex-shrink: 0;
}

.equipos-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.equipo-inscrito {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    background: #f9f9f9;
}

.equipo-logo-small {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 6px;
    flex-shrink: 0;
}

.equipo-logo-small img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.equipo-logo-small i {
    font-size: 1.5rem;
    color: #999;
}

.equipo-nombre {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.equipo-nombre strong {
    font-size: 0.95rem;
    color: #333;
}

.equipo-nombre small {
    font-size: 0.8rem;
    color: #666;
}

.equipo-acciones {
    flex-shrink: 0;
}

@media (max-width: 968px) {
    .inscripcion-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$stmt_torneo->close();
$stmt_inscritos->close();
$stmt_disponibles->close();
require_once 'admin_footer.php';
?>
