<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['equipo_id'])) {
    header("Location: gestionar_equipos.php?error=No se especificó un equipo.");
    exit;
}

$equipo_id = (int)$_GET['equipo_id'];

$stmt_equipo = $conn->prepare("SELECT nombre_mostrado FROM participantes WHERE id = ?");
$stmt_equipo->bind_param("i", $equipo_id);
$stmt_equipo->execute();
$equipo_result = $stmt_equipo->get_result();
if ($equipo_result->num_rows == 0) {
    header("Location: gestionar_equipos.php?error=Equipo no encontrado.");
    exit;
}
$equipo = $equipo_result->fetch_assoc();
$nombre_equipo = $equipo['nombre_mostrado'];

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

<style>
.plantel-layout {
    display: grid;
    
    grid-template-columns: 2fr 1fr;
    gap: 2.5rem;
    margin-top: 1rem;
    align-items: start;
}

.tabla-jugadores {
    min-width: 0;
}

.estadisticas-equipo {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    position: sticky;
    top: 80px;
    align-content: start;
    grid-auto-rows: 1fr;
}

.stat-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12), 0 4px 10px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.18), 0 8px 15px rgba(0,0,0,0.12);
}

.stat-content {
    padding: 2rem 1.25rem 1.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-evenly;
    gap: 1.25rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
    flex: 1;
    min-height: 500px;
}

.jugador-foto-container {
    position: relative;
    flex-shrink: 0;
}

.jugador-foto-stat {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    overflow: hidden;
    border: 6px solid #fff;
    box-shadow: 0 10px 35px rgba(0,0,0,0.25), 0 0 0 10px rgba(26, 35, 126, 0.08);
    transition: all 0.3s ease;
    position: relative;
    z-index: 2;
}

.stat-card:hover .jugador-foto-stat {
    transform: scale(1.05);
    box-shadow: 0 15px 45px rgba(0,0,0,0.3), 0 0 0 10px rgba(26, 35, 126, 0.12);
}

.mvp-card .jugador-foto-stat {
    box-shadow: 0 10px 35px rgba(255, 215, 0, 0.35), 0 0 0 10px rgba(255, 215, 0, 0.08);
}

.mvp-card:hover .jugador-foto-stat {
    box-shadow: 0 15px 45px rgba(255, 215, 0, 0.45), 0 0 0 10px rgba(255, 215, 0, 0.12);
}

.jugador-foto-stat img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.jugador-numero-stat {
    width: 65px;
    height: 65px;
    background: linear-gradient(135deg, #1a237e 0%, #3f51b5 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.85rem;
    font-weight: 900;
    box-shadow: 0 8px 25px rgba(26, 35, 126, 0.5);
    position: absolute;
    bottom: -5px;
    right: -5px;
    border: 5px solid white;
    z-index: 3;
}

.goleador-card .jugador-numero-stat {
    background: linear-gradient(135deg, #00c853 0%, #64dd17 100%);
    box-shadow: 0 8px 25px rgba(0, 200, 83, 0.5);
}

.mvp-card .jugador-numero-stat {
    background: linear-gradient(135deg, #ffd700 0%, #ffa000 100%);
    color: #333;
    box-shadow: 0 8px 25px rgba(255, 215, 0, 0.6);
}

.jugador-info-stat {
    width: 100%;
    text-align: center;
    padding: 0 0.5rem;
}

.jugador-info-stat h4 {
    margin: 0 0 0.4rem 0;
    font-size: 1.4rem;
    font-weight: 700;
    color: #1a237e;
    letter-spacing: -0.5px;
    line-height: 1.2;
}

.badge-title {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.9rem;
    background: linear-gradient(135deg, rgba(0, 200, 83, 0.1) 0%, rgba(100, 221, 23, 0.1) 100%);
    border: 2px solid rgba(0, 200, 83, 0.3);
    border-radius: 20px;
    margin-bottom: 1.25rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #00c853;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.badge-title i {
    font-size: 0.95rem;
}

.stat-card:hover .badge-title {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 200, 83, 0.2);
}

.mvp-badge {
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 160, 0, 0.15) 100%);
    border-color: rgba(255, 215, 0, 0.4);
    color: #f57c00;
}

.stat-card:hover .mvp-badge {
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.stat-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.7rem;
    width: 100%;
    flex-shrink: 0;
}

.stat-item {
    background: white;
    padding: 0.75rem 0.6rem;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    text-align: center;
    transition: all 0.2s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.stat-item.destacado {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #1a237e 0%, #3f51b5 100%);
    padding: 1.25rem 1rem;
    box-shadow: 0 6px 20px rgba(26, 35, 126, 0.3);
}

.goleador-card .stat-item.destacado {
    background: linear-gradient(135deg, #00c853 0%, #64dd17 100%);
    box-shadow: 0 6px 20px rgba(0, 200, 83, 0.3);
}

.mvp-card .stat-item.destacado {
    background: linear-gradient(135deg, #ffd700 0%, #ffa000 100%);
    box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
}

.stat-label {
    font-weight: 600;
    color: #666;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-item.destacado .stat-label {
    color: rgba(255,255,255,0.9);
    font-size: 0.75rem;
}

.mvp-card .stat-item.destacado .stat-label {
    color: rgba(0,0,0,0.7);
}

.stat-value {
    font-weight: 700;
    color: #1a237e;
    font-size: 1.05rem;
    line-height: 1.1;
}

.stat-item.destacado .stat-value {
    color: white;
    font-size: 2.25rem;
}

.mvp-card .stat-item.destacado .stat-value {
    color: #333;
}

.goles-count, .mvp-count {
    font-size: 2.5rem;
    font-weight: 900;
    display: block;
    line-height: 1;
}

.stat-empty {
    padding: 3rem 2rem;
    text-align: center;
    color: #999;
    background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    flex: 1;
    min-height: 500px;
}

.empty-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.2rem;
    background: rgba(0, 200, 83, 0.08);
    border: 2px dashed rgba(0, 200, 83, 0.3);
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #00c853;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.empty-badge i {
    font-size: 1.2rem;
    opacity: 0.6;
}

.mvp-empty-badge {
    background: rgba(255, 215, 0, 0.1);
    border-color: rgba(255, 215, 0, 0.3);
    color: #f57c00;
}

.stat-empty p {
    margin: 0.5rem 0 0 0;
    font-size: 0.95rem;
    font-weight: 500;
    color: #999;
}


@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card {
    animation: fadeInUp 0.5s ease-out;
}

.goleador-card {
    animation-delay: 0.1s;
}

.mvp-card {
    animation-delay: 0.2s;
}


@media (max-width: 1200px) {
    .plantel-layout {
        grid-template-columns: 1fr;
    }

    .estadisticas-equipo {
        position: static;
        grid-template-columns: 1fr 1fr;
        display: grid;
    }
}

@media (max-width: 768px) {
    .estadisticas-equipo {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        grid-auto-rows: auto;
    }

    .stat-content {
        padding: 1.75rem 1.25rem 1.5rem;
        min-height: 450px;
    }

    .stat-empty {
        min-height: 450px;
    }

    .badge-title {
        font-size: 0.75rem;
        padding: 0.3rem 0.8rem;
    }

    .jugador-foto-stat {
        width: 150px;
        height: 150px;
    }

    .jugador-numero-stat {
        width: 55px;
        height: 55px;
        font-size: 1.6rem;
    }

    .jugador-info-stat h4 {
        font-size: 1.3rem;
    }

    .stat-details {
        gap: 0.6rem;
    }

    .stat-item {
        padding: 0.65rem 0.5rem;
    }

    .stat-item.destacado {
        padding: 1.1rem 0.85rem;
    }

    .stat-value {
        font-size: 1rem;
    }

    .stat-item.destacado .stat-value {
        font-size: 2rem;
    }
}
</style>

<?php
$stmt_equipo->close();
$stmt_plantel->close();
$stmt_jugadores->close();
$stmt_goleador->close();
$stmt_mvp->close();
require_once 'admin_footer.php';
?>