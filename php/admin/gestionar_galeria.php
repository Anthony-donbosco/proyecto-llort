<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$temporada_filter = isset($_GET['temporada']) ? (int)$_GET['temporada'] : 0;
$deporte_filter = isset($_GET['deporte']) ? (int)$_GET['deporte'] : 0;

$sql_where = " WHERE 1=1";
$params = [];
$types = '';

if ($temporada_filter > 0) {
    $sql_where .= " AND g.temporada_id = ?";
    $params[] = $temporada_filter;
    $types .= "i";
}

if ($deporte_filter > 0) {
    $sql_where .= " AND g.deporte_id = ?";
    $params[] = $deporte_filter;
    $types .= "i";
}

$sql = "SELECT
            g.id, g.titulo, g.descripcion, g.url_foto, g.es_foto_grupo, g.orden, g.fecha_captura, g.esta_activa,
            t.nombre AS nombre_temporada,
            d.nombre_mostrado AS nombre_deporte
        FROM galeria_temporadas g
        JOIN temporadas t ON g.temporada_id = t.id
        LEFT JOIN deportes d ON g.deporte_id = d.id
        $sql_where
        ORDER BY t.es_actual DESC, g.orden ASC, g.fecha_captura DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$fotos = $stmt->get_result();

$temporadas_sql = "SELECT id, nombre FROM temporadas ORDER BY es_actual DESC, ano DESC";
$temporadas = $conn->query($temporadas_sql);

$deportes_sql = "SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado";
$deportes = $conn->query($deportes_sql);


$config_sql = "SELECT * FROM configuracion_galeria";
$config_result = $conn->query($config_sql);
$config = [];
while($row = $config_result->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Gestionar Galería de Fotos</h1>
        <div>
            <a href="subir_foto_galeria.php" class="btn btn-primary">
                <i class="fas fa-image"></i> Subir Foto
            </a>
            <a href="configurar_galeria.php" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Configuración
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Volver a Inicio
            </a>
        </div>
    </div>

    <div class="search-bar">
        <form action="gestionar_galeria.php" method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="temporada">Filtrar por Temporada:</label>
                    <select name="temporada" id="temporada">
                        <option value="0">Todas las temporadas</option>
                        <?php
                        $temporadas->data_seek(0);
                        while($temporada = $temporadas->fetch_assoc()):
                        ?>
                            <option value="<?php echo $temporada['id']; ?>" <?php echo $temporada_filter == $temporada['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($temporada['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="deporte">Filtrar por Deporte:</label>
                    <select name="deporte" id="deporte">
                        <option value="0">Todos los deportes</option>
                        <?php while($deporte = $deportes->fetch_assoc()): ?>
                            <option value="<?php echo $deporte['id']; ?>" <?php echo $deporte_filter == $deporte['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deporte['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="gestionar_galeria.php" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="galeria-grid">
        <?php
        if ($fotos->num_rows > 0) {
            while($row = $fotos->fetch_assoc()) {
                $foto_url = htmlspecialchars($row['url_foto']);
                $estado_badge = $row['esta_activa'] ? '<span class="badge badge-success">Activa</span>' : '<span class="badge badge-danger">Inactiva</span>';
                $tipo_badge = $row['es_foto_grupo'] ? '<span class="badge badge-primary">Foto Grupal</span>' : '<span class="badge badge-secondary">Individual</span>';
        ?>
            <div class="galeria-item">
                <div class="galeria-imagen">
                    <img src="<?php echo $foto_url; ?>" alt="<?php echo htmlspecialchars($row['titulo']); ?>">
                </div>
                <div class="galeria-info">
                    <h3><?php echo htmlspecialchars($row['titulo']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($row['descripcion'], 0, 100)); ?></p>
                    <div class="galeria-meta">
                        <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($row['nombre_temporada']); ?></span>
                        <?php if ($row['nombre_deporte']): ?>
                            <span><i class="fas fa-basketball-ball"></i> <?php echo htmlspecialchars($row['nombre_deporte']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="galeria-badges">
                        <?php echo $estado_badge; ?>
                        <?php echo $tipo_badge; ?>
                        <span class="badge badge-info">Orden: <?php echo $row['orden']; ?></span>
                    </div>
                    <div class="galeria-actions">
                        <a href="subir_foto_galeria.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="galeria_process.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que quieres eliminar esta foto?');">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    </div>
                </div>
            </div>
        <?php
            }
        } else {
            echo "<p class='no-results'>No se encontraron fotos en la galería.</p>";
        }
        ?>
    </div>
</main>

<style>
.galeria-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.galeria-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.galeria-imagen {
    width: 100%;
    height: 200px;
    overflow: hidden;
}

.galeria-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.galeria-info {
    padding: 15px;
}

.galeria-info h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
    color: #333;
}

.galeria-info p {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.galeria-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 10px;
    font-size: 12px;
    color: #888;
}

.galeria-badges {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.badge-success {
    background: #28a745;
    color: white;
}

.badge-danger {
    background: #dc3545;
    color: white;
}

.badge-primary {
    background: #007bff;
    color: white;
}

.badge-secondary {
    background: #6c757d;
    color: white;
}

.badge-info {
    background: #17a2b8;
    color: white;
}

.galeria-actions {
    display: flex;
    gap: 10px;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.no-results {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 16px;
}
</style>

<?php
$stmt->close();
require_once 'admin_footer.php';
?>
