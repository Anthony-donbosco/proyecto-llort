<?php

require_once 'auth_admin.php'; 
require_once 'admin_header.php'; 



$sql = "SELECT t.id, t.nombre, d.nombre_mostrado AS deporte, t.fecha_inicio, t.fecha_fin, e.nombre_mostrado AS estado
        FROM torneos t
        JOIN deportes d ON t.deporte_id = d.id
        JOIN estados_torneo e ON t.estado_id = e.id
        ORDER BY t.fecha_inicio DESC";
$result = $conn->query($sql);
?>

<main class="admin-page">
    <div class="page-header">
        <h1>Gestionar Torneos</h1>
        <a href="crear_torneo.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Nuevo Torneo
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
                    <th>ID</th>
                    <th>Nombre del Torneo</th>
                    <th>Deporte</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($row['deporte']); ?></td>
                        <td><?php echo date("d/m/Y", strtotime($row['fecha_inicio'])); ?></td>
                        <td><?php echo $row['fecha_fin'] ? date("d/m/Y", strtotime($row['fecha_fin'])) : 'N/A'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars(strtolower($row['estado'])); ?>">
                                <?php echo htmlspecialchars($row['estado']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <?php
                            $estado_lower = strtolower($row['estado']);
                            if ($estado_lower == 'inscripción' || $estado_lower == 'inscripcion'):
                            ?>
                                <a href="inscribir_equipos.php?torneo_id=<?php echo $row['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-user-plus"></i> Inscribir Equipos
                                </a>
                            <?php endif; ?>

                            <?php if ($estado_lower == 'activo'): ?>
                                <a href="gestionar_jornadas.php?torneo_id=<?php echo $row['id']; ?>" class="btn btn-info">
                                    <i class="fas fa-calendar-alt"></i> Jornadas
                                </a>
                                <a href="gestionar_partidos.php?torneo_id=<?php echo $row['id']; ?>" class="btn btn-info">
                                    <i class="fas fa-futbol"></i> Partidos
                                </a>
                                <a href="asignar_llaves.php?torneo_id=<?php echo $row['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-sitemap"></i> Asignar Llaves
                                </a>
                            <?php endif; ?>

                            <a href="crear_torneo.php?edit_id=<?php echo $row['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="torneo_process.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este torneo? Esta acción no se puede deshacer.');">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='7'>No se encontraron torneos.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<?php

require_once 'admin_footer.php'; 
?>