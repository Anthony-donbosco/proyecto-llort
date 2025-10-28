<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=ID de torneo no especificado.");
    exit;
}

$torneo_id = (int)$_GET['torneo_id'];


$stmt_torneo = $conn->prepare("SELECT t.*, d.nombre_mostrado AS deporte, e.nombre_mostrado AS estado
                                FROM torneos t
                                JOIN deportes d ON t.deporte_id = d.id
                                JOIN estados_torneo e ON t.estado_id = e.id
                                WHERE t.id = ?");
$stmt_torneo->bind_param("i", $torneo_id);
$stmt_torneo->execute();
$torneo = $stmt_torneo->get_result()->fetch_assoc();

if (!$torneo) {
    header("Location: gestionar_torneos.php?error=Torneo no encontrado.");
    exit;
}

// Prevenir acceso a jornadas si es torneo tipo bracket
if ($torneo['tipo_torneo'] == 'bracket') {
    header("Location: asignar_llaves.php?torneo_id=$torneo_id&error=Los torneos tipo Bracket no tienen jornadas. Ve directamente a Asignar Llaves.");
    exit;
}


$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM torneo_participantes WHERE torneo_id = ?");
$stmt_count->bind_param("i", $torneo_id);
$stmt_count->execute();
$total_equipos = $stmt_count->get_result()->fetch_assoc()['total'];


$jornadas_ida = $total_equipos > 0 ? $total_equipos - 1 : 0;
$jornadas_totales = $torneo['ida_y_vuelta'] ? $jornadas_ida * 2 : $jornadas_ida;


$sql_jornadas = "SELECT j.*, COUNT(p.id) as total_partidos
                 FROM jornadas j
                 JOIN fases f ON j.fase_id = f.id
                 LEFT JOIN partidos p ON j.id = p.jornada_id
                 WHERE f.torneo_id = ?
                 GROUP BY j.id
                 ORDER BY j.numero_jornada ASC";
$stmt_jornadas = $conn->prepare($sql_jornadas);
$stmt_jornadas->bind_param("i", $torneo_id);
$stmt_jornadas->execute();
$jornadas = $stmt_jornadas->get_result();


$todas_jornadas_completas = true;
$jornadas_array = [];
while($jornada = $jornadas->fetch_assoc()) {
    $jornadas_array[] = $jornada;

    
    $stmt_check = $conn->prepare("SELECT COUNT(*) as total,
                                   SUM(CASE WHEN estado_id = 5 THEN 1 ELSE 0 END) as finalizados
                                   FROM partidos
                                   WHERE jornada_id = ?");
    $stmt_check->bind_param("i", $jornada['id']);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result()->fetch_assoc();

    if ($check_result['total'] > 0 && $check_result['total'] != $check_result['finalizados']) {
        $todas_jornadas_completas = false;
    }
    $stmt_check->close();
}

$jornadas_creadas = count($jornadas_array);

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Jornadas - <?php echo htmlspecialchars($torneo['nombre']); ?></h1>
        <div>
            <?php if ($jornadas_creadas == 0 && $total_equipos >= 2): ?>
                <a href="jornadas_process.php?action=generar&torneo_id=<?php echo $torneo_id; ?>" class="btn btn-primary" onclick="return confirm('¿Generar todas las jornadas automáticamente?');">
                    <i class="fas fa-magic"></i> Generar Jornadas
                </a>
            <?php endif; ?>

            <?php if ($total_equipos >= 2): ?>
                <a href="tabla_posiciones.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-success">
                    <i class="fas fa-table"></i> Tabla de Posiciones
                </a>
            <?php endif; ?>

            <?php if ($todas_jornadas_completas && $jornadas_creadas == $jornadas_totales): ?>
                <a href="finalizar_torneo.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-primary">
                    <i class="fas fa-flag-checkered"></i> Opciones de Finalización
                </a>
            <?php endif; ?>

            <a href="gestionar_torneos.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Torneos
            </a>
        </div>
    </div>

    <div class="torneo-info-card">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Deporte:</span>
                <span class="info-value"><?php echo htmlspecialchars($torneo['deporte']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Estado:</span>
                <span class="info-value"><?php echo htmlspecialchars($torneo['estado']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Equipos Inscritos:</span>
                <span class="info-value"><?php echo $total_equipos; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Jornadas Necesarias:</span>
                <span class="info-value"><?php echo $jornadas_totales; ?> (<?php echo $torneo['ida_y_vuelta'] ? 'Ida y Vuelta' : 'Solo Ida'; ?>)</span>
            </div>
            <div class="info-item">
                <span class="info-label">Jornadas Creadas:</span>
                <span class="info-value"><?php echo $jornadas_creadas; ?> / <?php echo $jornadas_totales; ?></span>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <?php if ($total_equipos < 2): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Este torneo necesita al menos 2 equipos inscritos para generar jornadas.
            <a href="inscribir_equipos.php?torneo_id=<?php echo $torneo_id; ?>">Inscribir equipos</a>
        </div>
    <?php endif; ?>

    <div class="jornadas-container">
        <?php if (count($jornadas_array) > 0): ?>
            <?php foreach($jornadas_array as $jornada): ?>
                <div class="jornada-card">
                    <div class="jornada-header">
                        <h3>Jornada <?php echo $jornada['numero_jornada']; ?></h3>
                        <span class="jornada-fecha"><?php echo date('d/m/Y', strtotime($jornada['fecha_jornada'])); ?></span>
                    </div>
                    <div class="jornada-body">
                        <p><i class="fas fa-futbol"></i> <?php echo $jornada['total_partidos']; ?> partidos</p>
                        <a href="gestionar_partidos.php?jornada_id=<?php echo $jornada['id']; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-list"></i> Ver Partidos
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times" style="font-size: 3rem; color: #ccc;"></i>
                <h3>No hay jornadas creadas</h3>
                <p>Genera las jornadas automáticamente para comenzar el torneo.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.torneo-info-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
}

.info-value {
    font-size: 1.1rem;
    color: #1a237e;
    font-weight: bold;
}

.jornadas-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.jornada-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.jornada-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.jornada-header {
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.jornada-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.jornada-fecha {
    font-size: 0.9rem;
    opacity: 0.9;
}

.jornada-body {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 3rem;
    color: #999;
}

.empty-state h3 {
    margin: 1rem 0 0.5rem;
    color: #666;
}
</style>

<?php
$stmt_torneo->close();
$stmt_count->close();
$stmt_jornadas->close();
require_once 'admin_footer.php';
?>
