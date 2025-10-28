<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=ID de torneo no especificado.");
    exit;
}

$torneo_id = (int)$_GET['torneo_id'];


$stmt_torneo = $conn->prepare("SELECT t.*, d.nombre_mostrado AS deporte, d.es_por_equipos
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

$es_torneo_equipos = ($torneo['es_por_equipos'] == 1);
$tipo_participante_buscado = $es_torneo_equipos ? 1 : 2;
$label = $es_torneo_equipos ? 'Equipo' : 'Participante';


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
                     WHERE p.deporte_id = ? AND p.tipo_participante_id = ?"; 

$params = [$torneo['deporte_id'], $tipo_participante_buscado];
$types = 'ii'; 

if (count($ids_inscritos) > 0) {
    $placeholders = implode(',', array_fill(0, count($ids_inscritos), '?'));
    $sql_disponibles .= " AND p.id NOT IN ($placeholders)";
    
    $params = array_merge($params, $ids_inscritos);
    $types .= str_repeat('i', count($ids_inscritos));
}

$sql_disponibles .= " ORDER BY p.nombre_mostrado";

$stmt_disponibles = $conn->prepare($sql_disponibles);
$stmt_disponibles->bind_param($types, ...$params);

$stmt_disponibles->execute();
$disponibles = $stmt_disponibles->get_result();

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Inscribir Equipos - <?php echo htmlspecialchars($torneo['nombre']); ?></h1>
        <div>
            <?php if ($torneo['tipo_torneo'] == 'bracket'): ?>
                <a href="asignar_llaves.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-warning">
                    <i class="fas fa-sitemap"></i> Ir a Asignar Llaves
                </a>
            <?php else: ?>
                <a href="gestionar_jornadas.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Jornadas
                </a>
            <?php endif; ?>
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
                    <span class="label">Tipo:</span>
                    <span class="value" style="color: <?php echo $torneo['tipo_torneo'] == 'bracket' ? '#1565c0' : '#6a1b9a'; ?>;">
                        <?php echo $torneo['tipo_torneo'] == 'bracket' ? 'Bracket (Eliminatoria)' : 'Liga'; ?>
                    </span>
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

    <?php if ($torneo['tipo_torneo'] == 'bracket'): ?>
        <div class="alert alert-info" style="margin-bottom: 1.5rem;">
            <i class="fas fa-info-circle"></i>
            <strong>Torneo Bracket:</strong> Una vez inscribas los equipos, debes ir a "Asignar Llaves" para configurar el bracket eliminatorio.
            No se generarán jornadas para este torneo.
        </div>
    <?php endif; ?>

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

<link rel="stylesheet" href="../../css/inscribir_equipos.css">

<?php
$stmt_torneo->close();
$stmt_inscritos->close();
$stmt_disponibles->close();
require_once 'admin_footer.php';
?>
