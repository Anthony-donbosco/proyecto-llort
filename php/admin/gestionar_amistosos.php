<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$sql_amistosos = "SELECT p.*,
                  pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                  pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                  ep.nombre_mostrado AS estado,
                  d.nombre_mostrado AS deporte
                  FROM partidos p
                  LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                  LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                  LEFT JOIN estados_partido ep ON p.estado_id = ep.id
                  LEFT JOIN deportes d ON (
                      SELECT deporte_id FROM participantes WHERE id = p.participante_local_id
                  ) = d.id
                  WHERE p.torneo_id IS NULL
                  ORDER BY p.inicio_partido DESC";

$result_amistosos = $conn->query($sql_amistosos);
?>

<main class="admin-page">
    <div class="page-header">
        <h1>Gestionar Partidos Amistosos</h1>
        <a href="crear_amistoso.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Crear Partido Amistoso
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <?php if ($result_amistosos && $result_amistosos->num_rows > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha y Hora</th>
                        <th>Local</th>
                        <th>Visitante</th>
                        <th>Marcador</th>
                        <th>Estado</th>
                        <th>Deporte</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($partido = $result_amistosos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $partido['id']; ?></td>
                            <td>
                                <?php
                                $fecha = new DateTime($partido['inicio_partido']);
                                echo $fecha->format('d/m/Y H:i');
                                ?>
                            </td>
                            <td>
                                <div class="team-info">
                                    <?php if ($partido['logo_local']): ?>
                                        <img src="../../<?php echo htmlspecialchars($partido['logo_local']); ?>"
                                             alt="<?php echo htmlspecialchars($partido['equipo_local']); ?>"
                                             class="team-logo-small">
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($partido['equipo_local'] ?? 'Sin asignar'); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="team-info">
                                    <?php if ($partido['logo_visitante']): ?>
                                        <img src="../../<?php echo htmlspecialchars($partido['logo_visitante']); ?>"
                                             alt="<?php echo htmlspecialchars($partido['equipo_visitante']); ?>"
                                             class="team-logo-small">
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($partido['equipo_visitante'] ?? 'Sin asignar'); ?></span>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo $partido['marcador_local']; ?> - <?php echo $partido['marcador_visitante']; ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $partido['estado'])); ?>">
                                    <?php echo htmlspecialchars($partido['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($partido['deporte'] ?? 'N/A'); ?></td>
                            <td class="actions">
                                <a href="editar_amistoso.php?partido_id=<?php echo $partido['id']; ?>&es_amistoso=1"
                                   class="btn btn-small btn-info" title="Editar Partido">
                                    <i class="fas fa-futbol"></i>
                                </a>
                                <a href="crear_amistoso.php?edit_id=<?php echo $partido['id']; ?>"
                                   class="btn btn-small btn-warning" title="Editar Info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="amistoso_process.php?delete_id=<?php echo $partido['id']; ?>"
                                   class="btn btn-small btn-danger"
                                   onclick="return confirm('¿Estás seguro de que quieres eliminar este partido amistoso?');"
                                   title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-handshake fa-3x"></i>
                <p>No hay partidos amistosos registrados.</p>
                <a href="crear_amistoso.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Primer Partido Amistoso
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
.team-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.team-logo-small {
    width: 30px;
    height: 30px;
    object-fit: contain;
    border-radius: 4px;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 500;
}

.badge-programado { background-color: #2196F3; color: white; }
.badge-en-juego { background-color: #4CAF50; color: white; }
.badge-finalizado { background-color: #9E9E9E; color: white; }
.badge-suspendido { background-color: #FF9800; color: white; }
.badge-cancelado { background-color: #F44336; color: white; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state i {
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state p {
    font-size: 1.1em;
    margin-bottom: 20px;
}
</style>

<?php
require_once 'admin_footer.php';
?>
