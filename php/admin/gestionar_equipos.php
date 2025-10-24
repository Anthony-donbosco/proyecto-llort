<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$sql = "SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo, d.nombre_mostrado AS deporte
        FROM participantes p
        JOIN deportes d ON p.deporte_id = d.id
        ORDER BY p.nombre_mostrado";
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