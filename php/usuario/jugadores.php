<?php

require_once '../auth_user.php'; 
require_once '../includes/header.php'; 


$deporte_filtro = isset($_GET['deporte_id']) && $_GET['deporte_id'] != '' ? (int)$_GET['deporte_id'] : null;

$stmt_deportes = $conn->query("SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado");


$sql = "SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo, d.nombre_mostrado AS deporte, p.tipo_participante_id
        FROM participantes p
        JOIN deportes d ON p.deporte_id = d.id";

$params = [];
$types = '';

if ($deporte_filtro !== null) {
    $sql .= " WHERE p.deporte_id = ?";
    $params[] = $deporte_filtro;
    $types .= "i";
}

$sql .= " ORDER BY p.tipo_participante_id, p.nombre_mostrado";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$participantes = $stmt->get_result();

echo "<script>document.title = 'Equipos y Jugadores - Portal Deportivo';</script>";
?>

<div class="container page-container">
    <div class="page-header-user">
        <h1><i class="fas fa-users"></i> Equipos y Jugadores</h1>
        <p>Explora todos los equipos y participantes individuales de nuestros torneos.</p>
    </div>

    <form method="GET" action="jugadores.php" class="filtros-card-user">
        <div class="filtros-grid" style="grid-template-columns: 1fr auto;">
            <div class="filtro-item">
                <label for="deporte_id">Filtrar por Deporte:</label>
                <select id="deporte_id" name="deporte_id" onchange="this.form.submit()">
                    <option value="">Todos los deportes</option>
                    <?php while($d = $stmt_deportes->fetch_assoc()): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($deporte_filtro == $d['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['nombre_mostrado']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filtro-item-actions">
                <a href="jugadores.php" class="btn btn-secondary" style="color: white !important;">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="jugadores-container-user">
        <?php if ($participantes->num_rows == 0): ?>
            <div class="info-box text-center" style="padding: 2rem;">
                <p class="info-text" style="margin: 0;"><i class="fas fa-info-circle"></i> No se encontraron equipos o jugadores con los filtros seleccionados.</p>
            </div>
        <?php else: ?>
            <div class="torneo-grid"> <?php while($p = $participantes->fetch_assoc()): 
                    $es_equipo = $p['tipo_participante_id'] == 1;
                    $logo_url = $p['url_logo'] ? htmlspecialchars($p['url_logo']) : '../../img/logos/default.png';
                ?>
                <div class="torneo-card card"> <div class="card-content" style="text-align: center;">
                        <img src="<?php echo $logo_url; ?>" alt="Logo" class="equipo-logo-grande">
                        <h3 class="card-title" style="margin-top: 1rem;"><?php echo htmlspecialchars($p['nombre_mostrado']); ?></h3>
                        <p class="torneo-info-preview" style="justify-content: center;">
                            <i class="fas <?php echo $es_equipo ? 'fa-shield-alt' : 'fa-user'; ?>"></i> <?php echo $es_equipo ? 'Equipo' : 'Jugador Individual'; ?>
                            <span style="margin: 0 0.5rem;">|</span>
                            <i class="fas fa-futbol"></i> <?php echo htmlspecialchars($p['deporte']); ?>
                        </p>
                        
                        <?php ?>
                        <?php if ($es_equipo): ?>
                            <a href="plantel.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm" style="width: 100%; margin-top: 1rem;">
                                Ver Plantel <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                             <span class="btn btn-secondary btn-sm" style="width: 100%; margin-top: 1rem; opacity: 0.6; cursor: default;">
                                Participante Individual
                            </span>
                        <?php endif; ?>

                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt_deportes->close();
$stmt->close();
$conn->close();
require_once '../includes/footer.php';
?>