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

    <!-- Sección de MVP y Goleadores del Torneo -->
    <div class="mvp-torneo-section">
        <h2><i class="fas fa-trophy"></i> MVP y Goleadores de Torneos</h2>

        <?php
        $sql_torneos_mvp = "SELECT t.id, t.nombre, d.nombre_mostrado AS deporte, d.tipo_puntuacion,
                            mvp.id AS mvp_id, mvp.nombre_jugador AS mvp_nombre, mvp.numero_camiseta AS mvp_numero, mvp.url_foto AS mvp_foto,
                            p_mvp.nombre_mostrado AS mvp_equipo,
                            gol.id AS gol_id, gol.nombre_jugador AS gol_nombre, gol.numero_camiseta AS gol_numero, gol.url_foto AS gol_foto,
                            p_gol.nombre_mostrado AS gol_equipo,
                            t.goles_goleador
                            FROM torneos t
                            JOIN deportes d ON t.deporte_id = d.id
                            LEFT JOIN miembros_plantel mvp ON t.mvp_torneo_miembro_id = mvp.id
                            LEFT JOIN planteles_equipo pe_mvp ON mvp.plantel_id = pe_mvp.id
                            LEFT JOIN participantes p_mvp ON pe_mvp.participante_id = p_mvp.id
                            LEFT JOIN miembros_plantel gol ON t.goleador_torneo_miembro_id = gol.id
                            LEFT JOIN planteles_equipo pe_gol ON gol.plantel_id = pe_gol.id
                            LEFT JOIN participantes p_gol ON pe_gol.participante_id = p_gol.id
                            WHERE t.mvp_torneo_miembro_id IS NOT NULL OR t.goleador_torneo_miembro_id IS NOT NULL
                            ORDER BY t.fecha_inicio DESC";
        $torneos_mvp = $conn->query($sql_torneos_mvp);

        if ($torneos_mvp->num_rows > 0):
        ?>
            <div class="torneos-mvp-grid">
                <?php while($torneo = $torneos_mvp->fetch_assoc()):
                    $nombre_goleador = ($torneo['tipo_puntuacion'] == 'goles') ? 'Goleador' : 'Máximo Anotador';
                ?>
                    <div class="torneo-mvp-card">
                        <div class="torneo-mvp-header">
                            <h3><?php echo htmlspecialchars($torneo['nombre']); ?></h3>
                            <p><?php echo htmlspecialchars($torneo['deporte']); ?></p>
                        </div>

                        <div class="torneo-mvp-content">
                            <?php if ($torneo['mvp_id']): ?>
                                <div class="mvp-item-small">
                                    <div class="mvp-badge-small">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="jugador-foto-mini">
                                        <?php if ($torneo['mvp_foto']): ?>
                                            <img src="<?php echo htmlspecialchars($torneo['mvp_foto']); ?>" alt="MVP">
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="jugador-info-mini">
                                        <span class="badge-label">MVP</span>
                                        <strong>#<?php echo $torneo['mvp_numero']; ?> <?php echo htmlspecialchars($torneo['mvp_nombre']); ?></strong>
                                        <small><?php echo htmlspecialchars($torneo['mvp_equipo']); ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($torneo['gol_id']): ?>
                                <div class="goleador-item-small">
                                    <div class="goleador-badge-small">
                                        <i class="fas fa-futbol"></i>
                                    </div>
                                    <div class="jugador-foto-mini">
                                        <?php if ($torneo['gol_foto']): ?>
                                            <img src="<?php echo htmlspecialchars($torneo['gol_foto']); ?>" alt="Goleador">
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="jugador-info-mini">
                                        <span class="badge-label"><?php echo $nombre_goleador; ?></span>
                                        <strong>#<?php echo $torneo['gol_numero']; ?> <?php echo htmlspecialchars($torneo['gol_nombre']); ?></strong>
                                        <small><?php echo htmlspecialchars($torneo['gol_equipo']); ?> - <?php echo $torneo['goles_goleador']; ?> goles</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-results">No hay MVP o goleadores asignados a torneos aún.</p>
        <?php endif; ?>
    </div>

    <hr style="margin: 2rem 0; border: 1px solid #e0e0e0;">

    <h2><i class="fas fa-star"></i> Jugadores Destacados Individuales</h2>

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

<style>
.mvp-torneo-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.mvp-torneo-section h2 {
    margin: 0 0 1.5rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.torneos-mvp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.torneo-mvp-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.2s;
}

.torneo-mvp-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
}

.torneo-mvp-header {
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
    color: white;
    padding: 1rem 1.5rem;
    text-align: center;
}

.torneo-mvp-header h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
}

.torneo-mvp-header p {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.9;
}

.torneo-mvp-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.mvp-item-small, .goleador-item-small {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.mvp-badge-small {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(255, 215, 0, 0.4);
}

.goleador-badge-small {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(211, 47, 47, 0.4);
}

.jugador-foto-mini {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.jugador-foto-mini img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.jugador-foto-mini i {
    font-size: 1.5rem;
    color: #ccc;
}

.jugador-info-mini {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
}

.badge-label {
    background: #1a237e;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    align-self: flex-start;
    letter-spacing: 0.5px;
}

.jugador-info-mini strong {
    font-size: 0.95rem;
    color: #333;
}

.jugador-info-mini small {
    font-size: 0.85rem;
    color: #666;
}

.no-results {
    text-align: center;
    padding: 2rem;
    color: #999;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .torneos-mvp-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$stmt->close();
require_once 'admin_footer.php';
?>
