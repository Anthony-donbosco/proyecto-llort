<?php

require_once '../auth_user.php'; 
require_once '../includes/header.php'; 


$config_sql = "SELECT * FROM configuracion_galeria";
$config_result = $conn->query($config_sql);
$config = [];
while($row = $config_result->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}

$temporada_activa = $config['temporada_galeria_activa'] ?? 0;
$mostrar_todas = $config['mostrar_todas_temporadas'] ?? 0;


$temporada_filter = isset($_GET['temporada']) ? (int)$_GET['temporada'] : 0;
$deporte_filter = isset($_GET['deporte']) ? (int)$_GET['deporte'] : 0;

$sql_where = " WHERE g.esta_activa = 1";
$params = [];
$types = '';


if ($mostrar_todas == 0) {
    
    $sql_where .= " AND g.temporada_id = ?";
    $params[] = $temporada_activa;
    $types .= "i";
}


if ($temporada_filter > 0) {
    
    if ($mostrar_todas == 0) {
        
        array_pop($params);
        $types = substr($types, 0, -1);
        $sql_where = " WHERE g.esta_activa = 1"; 
    }
    $sql_where .= " AND g.temporada_id = ?";
    $params[] = $temporada_filter;
    $types .= "i";
}
if ($deporte_filter > 0) {
    $sql_where .= " AND g.deporte_id = ?";
    $params[] = $deporte_filter;
    $types .= "i";
}


$sql_fotos = "SELECT g.id, g.titulo, g.descripcion, g.url_foto, d.nombre_mostrado AS deporte, t.nombre AS temporada
              FROM galeria_temporadas g
              LEFT JOIN deportes d ON g.deporte_id = d.id
              LEFT JOIN temporadas t ON g.temporada_id = t.id
              $sql_where
              ORDER BY g.orden ASC, g.fecha_captura DESC, g.id DESC";

$stmt_fotos = $conn->prepare($sql_fotos);
if (!empty($params)) {
    $stmt_fotos->bind_param($types, ...$params);
}
$stmt_fotos->execute();
$fotos = $stmt_fotos->get_result();


$stmt_temporadas = $conn->query("SELECT id, nombre FROM temporadas ORDER BY es_actual DESC, ano DESC");
$stmt_deportes = $conn->query("SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado");

echo "<script>document.title = 'Galería - Portal Deportivo CFLC';</script>";
?>

<div class="container page-container">
    <div class="page-header-user">
        <h1><i class="fas fa-images"></i> Galería de Fotos</h1>
        <p>Revive los mejores momentos de nuestros atletas y torneos.</p>
    </div>

    <form method="GET" action="galeria.php" class="filtros-card-user">
        <div class="filtros-grid">
            <div class="filtro-item">
                <label for="temporada">Temporada</label>
                <select name="temporada" id="temporada">
                    <option value="0">
                        <?php echo $mostrar_todas ? 'Todas las Temporadas' : 'Temporada Actual'; ?>
                    </option>
                    <?php while($temp = $stmt_temporadas->fetch_assoc()): ?>
                        <option value="<?php echo $temp['id']; ?>" <?php echo $temporada_filter == $temp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($temp['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
             <div class="filtro-item">
                <label for="deporte">Deporte</label>
                <select name="deporte" id="deporte">
                    <option value="0">Todos los deportes</option>
                    <?php while($dep = $stmt_deportes->fetch_assoc()): ?>
                        <option value="<?php echo $dep['id']; ?>" <?php echo $deporte_filter == $dep['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['nombre_mostrado']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filtro-item-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="galeria.php" class="btn btn-secondary" style="color: white !important;">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="galeria-container-user">
        <?php if ($fotos->num_rows > 0): ?>
            <div class="galeria-grid-user">
                <?php while($foto = $fotos->fetch_assoc()):
                    $foto_url = htmlspecialchars($foto['url_foto']);
                ?>
                <a href="<?php echo $foto_url; ?>" class="galeria-item-user" data-lightbox="galeria" data-title="<?php echo htmlspecialchars($foto['titulo']); ?> - <?php echo htmlspecialchars($foto['descripcion']); ?>">
                    <img src="<?php echo $foto_url; ?>" alt="<?php echo htmlspecialchars($foto['titulo']); ?>" loading="lazy">
                    <div class="galeria-item-overlay">
                        <i class="fas fa-search-plus"></i>
                        <p><?php echo htmlspecialchars($foto['titulo']); ?></p>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="info-box text-center" style="padding: 2rem;">
                <p class="info-text" style="margin: 0;"><i class="fas fa-camera"></i> No se encontraron fotos con los filtros seleccionados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt_fotos->close();
$conn->close();
require_once '../includes/footer.php'; 
?>