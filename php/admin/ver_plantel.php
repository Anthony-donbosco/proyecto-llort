<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['equipo_id'])) {
    header("Location: gestionar_equipos.php?error=No se especificó un equipo.");
    exit;
}

$equipo_id = (int)$_GET['equipo_id'];

$stmt_equipo = $conn->prepare("SELECT p.nombre_mostrado,
                                       (SELECT tp.torneo_id FROM torneo_participantes tp WHERE tp.participante_id = p.id LIMIT 1) as torneo_id
                                FROM participantes p WHERE p.id = ?");
$stmt_equipo->bind_param("i", $equipo_id);
$stmt_equipo->execute();
$equipo_result = $stmt_equipo->get_result();
if ($equipo_result->num_rows == 0) {
    header("Location: gestionar_equipos.php?error=Equipo no encontrado.");
    exit;
}
$equipo = $equipo_result->fetch_assoc();
$nombre_equipo = $equipo['nombre_mostrado'];
$torneo_id = $equipo['torneo_id'];

$stmt_plantel = $conn->prepare("SELECT id FROM planteles_equipo WHERE participante_id = ? AND esta_activo = 1 LIMIT 1");
$stmt_plantel->bind_param("i", $equipo_id);
$stmt_plantel->execute();
$plantel_result = $stmt_plantel->get_result();
if ($plantel_result->num_rows == 0) {
    echo "Error: No se encontró un plantel activo para este equipo.";
    require_once 'admin_footer.php';
    exit;
}
$plantel_id = $plantel_result->fetch_assoc()['id'];

$stmt_jugadores = $conn->prepare("SELECT * FROM miembros_plantel WHERE plantel_id = ? ORDER BY numero_camiseta, nombre_jugador");
$stmt_jugadores->bind_param("i", $plantel_id);
$stmt_jugadores->execute();
$jugadores = $stmt_jugadores->get_result();


$stmt_goleador = $conn->prepare("SELECT mp.*, COUNT(ep.id) as total_goles
                                 FROM miembros_plantel mp
                                 LEFT JOIN eventos_partido ep ON mp.id = ep.miembro_plantel_id
                                    AND ep.tipo_evento IN ('gol', 'penal_anotado')
                                 JOIN planteles_equipo pe ON mp.plantel_id = pe.id
                                 WHERE pe.participante_id = ?
                                 GROUP BY mp.id
                                 ORDER BY total_goles DESC
                                 LIMIT 1");
$stmt_goleador->bind_param("i", $equipo_id);
$stmt_goleador->execute();
$goleador_result = $stmt_goleador->get_result();
$goleador = $goleador_result->fetch_assoc();


$stmt_mvp = $conn->prepare("SELECT mp.*, COUNT(p.id) as veces_mvp,
                            MAX(p.inicio_partido) as ultimo_partido
                            FROM miembros_plantel mp
                            JOIN partidos p ON mp.id = p.mvp_miembro_plantel_id
                            JOIN planteles_equipo pe ON mp.plantel_id = pe.id
                            WHERE pe.participante_id = ?
                            GROUP BY mp.id
                            ORDER BY veces_mvp DESC, ultimo_partido DESC
                            LIMIT 1");
$stmt_mvp->bind_param("i", $equipo_id);
$stmt_mvp->execute();
$mvp_result = $stmt_mvp->get_result();
$mvp = $mvp_result->fetch_assoc();
?>

<main class="admin-page">
    <div class="page-header">
        <h1>Plantel de "<?php echo htmlspecialchars($nombre_equipo); ?>"</h1>
        <div>
            <a href="crear_jugador.php?plantel_id=<?php echo $plantel_id; ?>&equipo_id=<?php echo $equipo_id; ?>" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Agregar Jugador
            </a>
            <?php if ($torneo_id): ?>
                <a href="ver-plantel-fifa.php?torneo_id=<?php echo $torneo_id; ?>&participante_id=<?php echo $equipo_id; ?>" class="btn btn-success">
                    <i class="fas fa-users"></i> Ver Plantilla FIFA
                </a>
            <?php endif; ?>
            <a href="gestionar_equipos.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Equipos
            </a>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="plantel-layout">
        
        <div class="tabla-jugadores">
            <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>N°</th>
                    <th>Nombre Jugador</th>
                    <th>Posición</th>
                    <th>Edad</th>
                    <th>Grado</th>
                    <th>Goles</th>
                    <th>Asist.</th>
                    <th>P. Cero</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($jugadores->num_rows > 0) {
                    while($row = $jugadores->fetch_assoc()) {
                        $foto_url = !empty($row['url_foto']) ? htmlspecialchars($row['url_foto']) : '../../img/jugadores/default.png';
                ?>
                    <tr>
                        <td><img src="<?php echo $foto_url; ?>" alt="Foto" class="table-avatar"></td>
                        <td><?php echo htmlspecialchars($row['numero_camiseta']); ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_jugador']); ?></td>
                        <td><?php echo htmlspecialchars($row['posicion']); ?></td>
                        <td><?php echo htmlspecialchars($row['edad']); ?></td>
                        <td><?php echo htmlspecialchars($row['grado']); ?></td>
                        <td><?php echo isset($row['goles']) ? htmlspecialchars($row['goles']) : '0'; ?></td>
                        <td><?php echo isset($row['asistencias']) ? htmlspecialchars($row['asistencias']) : '0'; ?></td>
                        <td><?php echo isset($row['porterias_cero']) ? htmlspecialchars($row['porterias_cero']) : '0'; ?></td>
                        <td class="action-buttons">
                            <a href="crear_jugador.php?edit_id=<?php echo $row['id']; ?>&equipo_id=<?php echo $equipo_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="jugador_process.php?delete_id=<?php echo $row['id']; ?>&equipo_id=<?php echo $equipo_id; ?>" class="btn btn-danger" onclick="return confirm('¿Seguro que quieres eliminar a este jugador?');">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='10'>Este equipo aún no tiene jugadores en su plantel.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
        </div>

        
        <div class="estadisticas-equipo">
            
            <div class="stat-card goleador-card">
                <?php if ($goleador && $goleador['total_goles'] > 0): ?>
                    <div class="stat-content">
                        <div class="jugador-foto-container">
                            <div class="jugador-foto-stat">
                                <?php
                                $foto_url = !empty($goleador['url_foto']) ? htmlspecialchars($goleador['url_foto']) : '../../img/jugadores/default.png';
                                ?>
                                <img src="<?php echo $foto_url; ?>" alt="Foto">
                            </div>
                            <div class="jugador-numero-stat"><?php echo htmlspecialchars($goleador['numero_camiseta']); ?></div>
                        </div>
                        <div class="jugador-info-stat">
                            <h4><?php echo htmlspecialchars($goleador['nombre_jugador']); ?></h4>
                            <div class="badge-title">
                                <i class="fas fa-futbol"></i>
                                <span>Máximo Goleador</span>
                            </div>
                            <div class="stat-details">
                                <div class="stat-item">
                                    <span class="stat-label">Posición</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($goleador['posicion']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Grado</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($goleador['grado']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Edad</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($goleador['edad']); ?> años</span>
                                </div>
                                <div class="stat-item destacado">
                                    <span class="stat-label">Total Goles</span>
                                    <span class="stat-value goles-count"><?php echo $goleador['total_goles']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-empty">
                        <div class="empty-badge">
                            <i class="fas fa-futbol"></i>
                            <span>Máximo Goleador</span>
                        </div>
                        <p>Aún no hay goles registrados en este equipo</p>
                    </div>
                <?php endif; ?>
            </div>

            
            <div class="stat-card mvp-card">
                <?php if ($mvp): ?>
                    <div class="stat-content">
                        <div class="jugador-foto-container">
                            <div class="jugador-foto-stat">
                                <?php
                                $foto_url = !empty($mvp['url_foto']) ? htmlspecialchars($mvp['url_foto']) : '../../img/jugadores/default.png';
                                ?>
                                <img src="<?php echo $foto_url; ?>" alt="Foto">
                            </div>
                            <div class="jugador-numero-stat"><?php echo htmlspecialchars($mvp['numero_camiseta']); ?></div>
                        </div>
                        <div class="jugador-info-stat">
                            <h4><?php echo htmlspecialchars($mvp['nombre_jugador']); ?></h4>
                            <div class="badge-title mvp-badge">
                                <i class="fas fa-trophy"></i>
                                <span>MVP del Equipo</span>
                            </div>
                            <div class="stat-details">
                                <div class="stat-item">
                                    <span class="stat-label">Posición</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($mvp['posicion']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Grado</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($mvp['grado']); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Edad</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($mvp['edad']); ?> años</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Goles</span>
                                    <span class="stat-value"><?php echo htmlspecialchars($mvp['goles'] ?? 0); ?></span>
                                </div>
                                <div class="stat-item destacado">
                                    <span class="stat-label">Veces MVP</span>
                                    <span class="stat-value mvp-count"><?php echo $mvp['veces_mvp']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-empty">
                        <div class="empty-badge mvp-empty-badge">
                            <i class="fas fa-trophy"></i>
                            <span>MVP del Equipo</span>
                        </div>
                        <p>Aún no hay MVP seleccionado en este equipo</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<link rel="stylesheet" href="../../css/ver_plantel.css">

<?php
$stmt_equipo->close();
$stmt_plantel->close();
$stmt_jugadores->close();
$stmt_goleador->close();
$stmt_mvp->close();
require_once 'admin_footer.php';
?>