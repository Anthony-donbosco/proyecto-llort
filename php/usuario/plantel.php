<?php

require_once '../auth_user.php'; 
require_once '../includes/header.php'; 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: jugadores.php");
    exit;
}

$equipo_id = (int)$_GET['id'];


$stmt_equipo = $conn->prepare("SELECT p.nombre_mostrado, p.url_logo, d.nombre_mostrado as deporte, pl.id as plantel_id
                                FROM participantes p
                                JOIN deportes d ON p.deporte_id = d.id
                                LEFT JOIN planteles_equipo pl ON p.id = pl.participante_id AND pl.esta_activo = 1
                                WHERE p.id = ? AND p.tipo_participante_id = 1");
$stmt_equipo->bind_param("i", $equipo_id);
$stmt_equipo->execute();
$equipo_res = $stmt_equipo->get_result();

if ($equipo_res->num_rows == 0) {
    header("Location: jugadores.php?error=Equipo no encontrado o no es válido.");
    exit;
}
$equipo = $equipo_res->fetch_assoc();
$plantel_id = $equipo['plantel_id'];
$logo_url = $equipo['url_logo'] ? htmlspecialchars($equipo['url_logo']) : '../../img/logos/default.png';


$jugadores = [];
if ($plantel_id) {
    $stmt_jugadores = $conn->prepare("SELECT * FROM miembros_plantel WHERE plantel_id = ? ORDER BY numero_camiseta, nombre_jugador");
    $stmt_jugadores->bind_param("i", $plantel_id);
    $stmt_jugadores->execute();
    $jugadores_res = $stmt_jugadores->get_result();
    while($j = $jugadores_res->fetch_assoc()) {
        $jugadores[] = $j;
    }
    $stmt_jugadores->close();
}


$goleador = null;
$stmt_goleador = $conn->prepare("SELECT mp.*, COUNT(ep.id) as total_goles
                                 FROM miembros_plantel mp
                                 LEFT JOIN eventos_partido ep ON mp.id = ep.miembro_plantel_id AND ep.tipo_evento IN ('gol', 'penal_anotado')
                                 JOIN planteles_equipo pe ON mp.plantel_id = pe.id
                                 WHERE pe.participante_id = ?
                                 GROUP BY mp.id
                                 ORDER BY total_goles DESC
                                 LIMIT 1");
$stmt_goleador->bind_param("i", $equipo_id);
$stmt_goleador->execute();
$goleador_res = $stmt_goleador->get_result();
if($goleador_res->num_rows > 0) {
    $goleador = $goleador_res->fetch_assoc();
    if($goleador['total_goles'] == 0) $goleador = null; 
}
$stmt_goleador->close();

$mvp = null;
$stmt_mvp = $conn->prepare("SELECT mp.*, mp.mvps as veces_mvp
                            FROM miembros_plantel mp
                            JOIN planteles_equipo pe ON mp.plantel_id = pe.id
                            WHERE pe.participante_id = ?
                            ORDER BY mp.mvps DESC
                            LIMIT 1");
$stmt_mvp->bind_param("i", $equipo_id);
$stmt_mvp->execute();
$mvp_res = $stmt_mvp->get_result();
if($mvp_res->num_rows > 0) {
    $mvp = $mvp_res->fetch_assoc();
    if($mvp['veces_mvp'] == 0) $mvp = null; 
}
$stmt_mvp->close();


echo "<script>document.title = 'Plantel: " . htmlspecialchars($equipo['nombre_mostrado']) . "';</script>";
?>

<div class="container page-container">
    <div class="plantel-header-user">
        <img src="<?php echo $logo_url; ?>" alt="Logo <?php echo htmlspecialchars($equipo['nombre_mostrado']); ?>" class="equipo-logo-grande">
        <h1><?php echo htmlspecialchars($equipo['nombre_mostrado']); ?></h1>
        <p><?php echo htmlspecialchars($equipo['deporte']); ?></p>
        <a href="jugadores.php" class="btn btn-secondary btn-sm" style="margin-top: 1rem; color: white !important;"><i class="fas fa-arrow-left"></i> Volver a Equipos</a>
    </div>

    <div class="plantel-layout-user">
        
        <div class="tabla-jugadores-user">
             <h2 class="section-title-sub" style="text-align: left;">Jugadores</h2>
            <div class="table-container-user">
                <table class="tabla-posiciones-user">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Foto</th>
                            <th style="width: 40px;">N°</th>
                            <th>Nombre Jugador</th>
                            <th>Posición</th>
                            <th class="stat-col">Edad</th>
                            <th class="stat-col">Grado</th>
                            <th class="stat-col" title="Goles"><i class="fas fa-futbol"></i></th>
                            <th class="stat-col" title="Asistencias"><i class="fas fa-hands-helping"></i></th>
                            <th class="stat-col" title="Porterías a Cero"><i class="fas fa-hand-paper"></i></th>
                            <th class="stat-col" title="Veces MVP"><i class="fas fa-star"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jugadores)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 2rem;">Este equipo aún no tiene jugadores registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($jugadores as $row):
                                $foto_url = !empty($row['url_foto']) ? htmlspecialchars($row['url_foto']) : '../../img/jugadores/default.png';
                            ?>
                                <tr>
                                    <td><img src="<?php echo $foto_url; ?>" alt="Foto" class="table-avatar-user"></td>
                                    <td style="font-weight: 700;"><?php echo htmlspecialchars($row['numero_camiseta']); ?></td>
                                    <td style="text-align: left; font-weight: 500;"><?php echo htmlspecialchars($row['nombre_jugador']); ?></td>
                                    <td style="text-align: left;"><?php echo htmlspecialchars($row['posicion']); ?></td>
                                    <td class="stat-col"><?php echo htmlspecialchars($row['edad']); ?></td>
                                    <td class="stat-col"><?php echo htmlspecialchars($row['grado']); ?></td>
                                    <td class="stat-col" style="font-weight: 700;"><?php echo htmlspecialchars($row['goles'] ?? 0); ?></td>
                                    <td class="stat-col" style="font-weight: 700;"><?php echo htmlspecialchars($row['asistencias'] ?? 0); ?></td>
                                    <td class="stat-col" style="font-weight: 700;"><?php echo htmlspecialchars($row['porterias_cero'] ?? 0); ?></td>
                                    <td class="stat-col" style="font-weight: 700;"><?php echo htmlspecialchars($row['mvps'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="estadisticas-equipo-user">
            <div class="stat-card goleador-card">
                <?php if ($goleador): 
                    $foto_url = !empty($goleador['url_foto']) ? htmlspecialchars($goleador['url_foto']) : '../../img/jugadores/default.png';
                ?>
                    <div class="stat-content">
                        <div class="jugador-foto-container">
                            <div class="jugador-foto-stat"><img src="<?php echo $foto_url; ?>" alt="Foto"></div>
                            <div class="jugador-numero-stat"><?php echo htmlspecialchars($goleador['numero_camiseta']); ?></div>
                        </div>
                        <div class="jugador-info-stat">
                            <h4><?php echo htmlspecialchars($goleador['nombre_jugador']); ?></h4>
                            <div class="badge-title"><i class="fas fa-futbol"></i><span>Máximo Goleador</span></div>
                            <div class="stat-details">
                                <div class="stat-item"><span class="stat-label">Posición</span><span class="stat-value"><?php echo htmlspecialchars($goleador['posicion']); ?></span></div>
                                <div class="stat-item"><span class="stat-label">Grado</span><span class="stat-value"><?php echo htmlspecialchars($goleador['grado']); ?></span></div>
                                <div class="stat-item destacado">
                                    <span class="stat-label">Total Goles</span>
                                    <span class="stat-value goles-count"><?php echo $goleador['total_goles']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-empty"><div class="empty-badge"><i class="fas fa-futbol"></i><span>Máximo Goleador</span></div><p>Aún no hay goles registrados</p></div>
                <?php endif; ?>
            </div>

            <div class="stat-card mvp-card">
                <?php if ($mvp): 
                    $foto_url = !empty($mvp['url_foto']) ? htmlspecialchars($mvp['url_foto']) : '../../img/jugadores/default.png';
                ?>
                    <div class="stat-content">
                        <div class="jugador-foto-container">
                            <div class="jugador-foto-stat"><img src="<?php echo $foto_url; ?>" alt="Foto"></div>
                            <div class="jugador-numero-stat"><?php echo htmlspecialchars($mvp['numero_camiseta']); ?></div>
                        </div>
                        <div class="jugador-info-stat">
                            <h4><?php echo htmlspecialchars($mvp['nombre_jugador']); ?></h4>
                            <div class="badge-title mvp-badge"><i class="fas fa-star"></i><span>MVP del Equipo</span></div>
                            <div class="stat-details">
                                <div class="stat-item"><span class="stat-label">Posición</span><span class="stat-value"><?php echo htmlspecialchars($mvp['posicion']); ?></span></div>
                                <div class="stat-item"><span class="stat-label">Grado</span><span class="stat-value"><?php echo htmlspecialchars($mvp['grado']); ?></span></div>
                                <div class="stat-item destacado">
                                    <span class="stat-label">Veces MVP</span>
                                    <span class="stat-value mvp-count"><?php echo $mvp['veces_mvp']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-empty"><div class="empty-badge mvp-empty-badge"><i class="fas fa-star"></i><span>MVP del Equipo</span></div><p>Aún no hay MVPs seleccionados</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$stmt_equipo->close();
$conn->close();
require_once '../includes/footer.php'; 
?>