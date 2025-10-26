<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$deporte_filter = isset($_GET['deporte']) ? (int)$_GET['deporte'] : 0;
$tipo_filter = isset($_GET['tipo']) ? $_GET['tipo'] : '';

$sql_where = " WHERE 1=1";
$params = [];
$types = '';

if ($deporte_filter > 0) {
    $sql_where .= " AND jd.deporte_id = ?";
    $params[] = $deporte_filter;
    $types .= "i";
}

if (!empty($tipo_filter)) {
    $sql_where .= " AND jd.tipo_destacado = ?";
    $params[] = $tipo_filter;
    $types .= "s";
}

$sql = "SELECT
            jd.id, jd.tipo_destacado, jd.descripcion, jd.fecha_destacado, jd.esta_activo, jd.orden,
            m.nombre_jugador, m.posicion, m.url_foto,
            d.nombre_mostrado AS nombre_deporte,
            t.nombre AS nombre_torneo,
            temp.nombre AS nombre_temporada
        FROM jugadores_destacados jd
        JOIN miembros_plantel m ON jd.miembro_plantel_id = m.id
        JOIN deportes d ON jd.deporte_id = d.id
        LEFT JOIN torneos t ON jd.torneo_id = t.id
        LEFT JOIN temporadas temp ON jd.temporada_id = temp.id
        $sql_where
        ORDER BY jd.orden ASC, jd.fecha_destacado DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$destacados = $stmt->get_result();

$deportes_sql = "SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado";
$deportes = $conn->query($deportes_sql);

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Gestionar Jugadores Destacados</h1>
        <div>
            <a href="crear_destacado.php" class="btn btn-primary">
                <i class="fas fa-star"></i> Agregar Destacado
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Volver a Inicio
            </a>
        </div>
    </div>

    <div class="search-bar">
        <form action="gestionar_destacados.php" method="GET" class="filter-form">
            <div class="form-row">
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
                <div class="form-group">
                    <label for="tipo">Filtrar por Tipo:</label>
                    <select name="tipo" id="tipo">
                        <option value="">Todos los tipos</option>
                        <option value="torneo" <?php echo $tipo_filter == 'torneo' ? 'selected' : ''; ?>>Torneo</option>
                        <option value="seleccion" <?php echo $tipo_filter == 'seleccion' ? 'selected' : ''; ?>>Selección</option>
                        <option value="general" <?php echo $tipo_filter == 'general' ? 'selected' : ''; ?>>General</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="gestionar_destacados.php" class="btn btn-secondary">Limpiar</a>
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
                    <th>Foto</th>
                    <th>Jugador</th>
                    <th>Deporte</th>
                    <th>Tipo</th>
                    <th>Torneo/Temporada</th>
                    <th>Descripción</th>
                    <th>Orden</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($destacados->num_rows > 0) {
                    while($row = $destacados->fetch_assoc()) {
                        $foto_url = !empty($row['url_foto']) ? htmlspecialchars($row['url_foto']) : '../../img/jugadores/default.png';
                        $estado_badge = $row['esta_activo'] ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>';
                        $torneo_temp = $row['nombre_torneo'] ?? $row['nombre_temporada'] ?? 'N/A';
                ?>
                    <tr>
                        <td><img src="<?php echo $foto_url; ?>" alt="Foto" class="table-avatar"></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['nombre_jugador']); ?></strong><br>
                            <small><?php echo htmlspecialchars($row['posicion']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['nombre_deporte']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($row['tipo_destacado'])); ?></td>
                        <td><?php echo htmlspecialchars($torneo_temp); ?></td>
                        <td><?php echo htmlspecialchars(substr($row['descripcion'], 0, 50)) . '...'; ?></td>
                        <td><?php echo htmlspecialchars($row['orden']); ?></td>
                        <td><?php echo $estado_badge; ?></td>
                        <td class="action-buttons">
                            <a href="crear_destacado.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="destacado_process.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Seguro que quieres eliminar este destacado?');">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='9'>No se encontraron jugadores destacados.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<?php
$stmt->close();
require_once 'admin_footer.php';
?>
