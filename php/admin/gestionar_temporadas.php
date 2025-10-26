<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$sql = "SELECT * FROM temporadas ORDER BY es_actual DESC, ano DESC, fecha_inicio DESC";
$temporadas = $conn->query($sql);

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Gestionar Temporadas</h1>
        <div>
            <a href="crear_temporada.php" class="btn btn-primary">
                <i class="fas fa-calendar-plus"></i> Agregar Temporada
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Volver a Inicio
            </a>
        </div>
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
                    <th>Nombre</th>
                    <th>Año</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($temporadas->num_rows > 0) {
                    while($row = $temporadas->fetch_assoc()) {
                        $estado_badge = $row['es_actual'] ? '<span class="badge badge-success">Actual</span>' : '<span class="badge badge-secondary">Pasada</span>';
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['ano']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['fecha_inicio'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['fecha_fin'])); ?></td>
                        <td><?php echo $estado_badge; ?></td>
                        <td class="action-buttons">
                            <a href="crear_temporada.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <?php if (!$row['es_actual']): ?>
                                <a href="temporada_process.php?set_actual=<?php echo $row['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Marcar Actual
                                </a>
                            <?php endif; ?>
                            <a href="temporada_process.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Seguro que quieres eliminar esta temporada? Se eliminarán también las fotos asociadas.');">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='6'>No se encontraron temporadas.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<?php
require_once 'admin_footer.php';
?>
