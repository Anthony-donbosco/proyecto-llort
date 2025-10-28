<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$destacada_filter = isset($_GET['destacada']) ? (int)$_GET['destacada'] : -1;
$publicada_filter = isset($_GET['publicada']) ? (int)$_GET['publicada'] : -1;
$deporte_filter = isset($_GET['deporte']) ? (int)$_GET['deporte'] : 0;

$sql_where = " WHERE 1=1";
$params = [];
$types = '';

if ($destacada_filter >= 0) {
    $sql_where .= " AND n.destacada = ?";
    $params[] = $destacada_filter;
    $types .= "i";
}

if ($publicada_filter >= 0) {
    $sql_where .= " AND n.publicada = ?";
    $params[] = $publicada_filter;
    $types .= "i";
}

if ($deporte_filter > 0) {
    $sql_where .= " AND n.deporte_id = ?";
    $params[] = $deporte_filter;
    $types .= "i";
}

$sql = "SELECT
            n.id, n.titulo, n.subtitulo, n.autor, n.destacada, n.publicada,
            n.fecha_publicacion, n.visitas, n.orden, n.imagen_portada,
            d.nombre_mostrado AS nombre_deporte,
            temp.nombre AS nombre_temporada
        FROM noticias n
        LEFT JOIN deportes d ON n.deporte_id = d.id
        LEFT JOIN temporadas temp ON n.temporada_id = temp.id
        $sql_where
        ORDER BY n.fecha_publicacion DESC, n.orden ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$noticias = $stmt->get_result();

$deportes_sql = "SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado";
$deportes = $conn->query($deportes_sql);

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Gestionar Noticias</h1>
        <div>
            <a href="crear_noticia.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Crear Noticia
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Volver a Inicio
            </a>
        </div>
    </div>

    <div class="search-bar">
        <form action="gestionar_noticias.php" method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="destacada">Destacada:</label>
                    <select name="destacada" id="destacada">
                        <option value="-1">Todas</option>
                        <option value="1" <?php echo $destacada_filter == 1 ? 'selected' : ''; ?>>Sí</option>
                        <option value="0" <?php echo $destacada_filter === 0 ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="publicada">Publicada:</label>
                    <select name="publicada" id="publicada">
                        <option value="-1">Todas</option>
                        <option value="1" <?php echo $publicada_filter == 1 ? 'selected' : ''; ?>>Sí</option>
                        <option value="0" <?php echo $publicada_filter === 0 ? 'selected' : ''; ?>>Borrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="deporte">Deporte:</label>
                    <select name="deporte" id="deporte">
                        <option value="0">Todos</option>
                        <?php while($deporte = $deportes->fetch_assoc()): ?>
                            <option value="<?php echo $deporte['id']; ?>" <?php echo $deporte_filter == $deporte['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deporte['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="gestionar_noticias.php" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Imagen</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Deporte</th>
                    <th>Fecha</th>
                    <th>Visitas</th>
                    <th>Orden</th>
                    <th>Destacada</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($noticias->num_rows > 0) {
                    while($row = $noticias->fetch_assoc()) {
                        $imagen = !empty($row['imagen_portada']) ? htmlspecialchars($row['imagen_portada']) : '../../img/default-noticia.png';
                        $destacada_badge = $row['destacada'] ? '<span class="badge badge-warning"><i class="fas fa-star"></i> Destacada</span>' : '-';
                        $estado_badge = $row['publicada'] ? '<span class="badge badge-success">Publicada</span>' : '<span class="badge badge-secondary">Borrador</span>';
                ?>
                    <tr>
                        <td><img src="<?php echo $imagen; ?>" alt="Imagen" class="table-avatar" style="width: 60px; height: 40px; object-fit: cover;"></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['titulo']); ?></strong><br>
                            <small><?php echo htmlspecialchars(substr($row['subtitulo'], 0, 50)); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['autor']); ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_deporte'] ?? 'General'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['fecha_publicacion'])); ?></td>
                        <td><?php echo $row['visitas']; ?></td>
                        <td><?php echo $row['orden']; ?></td>
                        <td><?php echo $destacada_badge; ?></td>
                        <td><?php echo $estado_badge; ?></td>
                        <td class="action-buttons">
                            <a href="crear_noticia.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="noticia_process.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta noticia?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='10'>No se encontraron noticias.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<style>
.badge-warning {
    background: #ffc107;
    color: #333;
}
</style>

<?php
$stmt->close();
require_once 'admin_footer.php';
?>
