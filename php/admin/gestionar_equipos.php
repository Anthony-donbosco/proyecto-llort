<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

// Obtener lista de deportes para el filtro
$deportes_query = $conn->query("SELECT id, nombre_mostrado FROM deportes WHERE es_por_equipos = 1 ORDER BY nombre_mostrado");

// Filtro por deporte
$deporte_filtro = isset($_GET['deporte_id']) && $_GET['deporte_id'] != '' ? (int)$_GET['deporte_id'] : null;

// Construir consulta SQL con filtro
$sql = "SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo, d.nombre_mostrado AS deporte, d.id AS deporte_id
        FROM participantes p
        JOIN deportes d ON p.deporte_id = d.id
        WHERE p.tipo_participante_id = 1";

if ($deporte_filtro !== null) {
    $sql .= " AND p.deporte_id = " . $deporte_filtro;
}

$sql .= " ORDER BY p.nombre_mostrado";
$result = $conn->query($sql);
?>

<main class="admin-page">
    <div class="page-header">
        <h1>Gestionar Equipos</h1>
        <a href="crear_equipo.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Nuevo Equipo
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <!-- Filtro por deporte -->
    <div class="search-bar">
        <form method="GET" action="gestionar_equipos.php" class="filter-form">
            <div class="form-group">
                <label for="deporte_id">Filtrar por Deporte:</label>
                <select id="deporte_id" name="deporte_id" onchange="this.form.submit()">
                    <option value="">Todos los deportes</option>
                    <?php
                    $deportes_query->data_seek(0); // Reset pointer
                    while($d = $deportes_query->fetch_assoc()):
                    ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($deporte_filtro == $d['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['nombre_mostrado']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <?php if ($deporte_filtro !== null): ?>
                <a href="gestionar_equipos.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar Filtro
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Logo</th>
                    <th>Nombre de Equipo</th>
                    <th>Nombre Corto</th>
                    <th>Deporte</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $logo_url = !empty($row['url_logo']) ? htmlspecialchars($row['url_logo']) : '../../img/logos/default.png';
                ?>
                    <tr>
                        <td><img src="<?php echo $logo_url; ?>" alt="Logo" class="table-logo"></td>
                        <td><?php echo htmlspecialchars($row['nombre_mostrado']); ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_corto']); ?></td>
                        <td><?php echo htmlspecialchars($row['deporte']); ?></td>
                        <td class="action-buttons">
                            <a href="ver_plantel.php?equipo_id=<?php echo $row['id']; ?>" class="btn btn-info">
                                <i class="fas fa-users"></i> Plantel
                            </a>
                            <a href="crear_equipo.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="equipo_process.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Seguro? Si el equipo está en un torneo, NO se podrá eliminar.');">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='5'>No se encontraron equipos.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<?php
require_once 'admin_footer.php';
?>