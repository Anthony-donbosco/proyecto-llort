<?php

require_once '../auth_user.php'; 
require_once '../includes/header.php'; 


$mes_actual = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$anio_actual = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$torneo_filtro = isset($_GET['torneo']) ? (int)$_GET['torneo'] : null;
$deporte_filtro = isset($_GET['deporte']) ? (int)$_GET['deporte'] : null;

if ($mes_actual < 1 || $mes_actual > 12) $mes_actual = date('n');
if ($anio_actual < 2000 || $anio_actual > 2100) $anio_actual = date('Y');

$primer_dia = date('Y-m-d', mktime(0, 0, 0, $mes_actual, 1, $anio_actual));
$ultimo_dia = date('Y-m-t', mktime(0, 0, 0, $mes_actual, 1, $anio_actual));

$stmt_torneos = $conn->query("SELECT id, nombre FROM torneos WHERE estado_id IN (2, 3) ORDER BY fecha_inicio DESC"); 
$stmt_deportes = $conn->query("SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado");


$sql_partidos = "SELECT p.*,
                 pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                 pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                 t.nombre AS nombre_torneo, t.id AS torneo_id,
                 d.nombre_mostrado AS deporte, d.id AS deporte_id,
                 ep.nombre_mostrado AS estado, ep.codigo AS estado_codigo, ep.id AS estado_id_num,
                 j.numero_jornada, j.id AS jornada_id,
                 f.nombre AS fase_nombre
                 FROM partidos p
                 LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                 LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                 JOIN torneos t ON p.torneo_id = t.id
                 JOIN deportes d ON t.deporte_id = d.id
                 JOIN estados_partido ep ON p.estado_id = ep.id
                 JOIN fases f ON p.fase_id = f.id
                 LEFT JOIN jornadas j ON p.jornada_id = j.id
                 WHERE DATE(p.inicio_partido) BETWEEN ? AND ?
                 AND (pl.id IS NOT NULL AND pv.id IS NOT NULL)"; 

$params = [$primer_dia, $ultimo_dia];
$types = "ss";

if ($torneo_filtro) {
    $sql_partidos .= " AND t.id = ?";
    $params[] = $torneo_filtro;
    $types .= "i";
}
if ($deporte_filtro) {
    $sql_partidos .= " AND d.id = ?";
    $params[] = $deporte_filtro;
    $types .= "i";
}

$sql_partidos .= " ORDER BY p.inicio_partido ASC";

$stmt_partidos = $conn->prepare($sql_partidos);
$stmt_partidos->bind_param($types, ...$params);
$stmt_partidos->execute();
$partidos = $stmt_partidos->get_result();

$partidos_por_fecha = [];
while ($partido = $partidos->fetch_assoc()) {
    $fecha = date('Y-m-d', strtotime($partido['inicio_partido']));
    $partidos_por_fecha[$fecha][] = $partido;
}
$stmt_partidos->close();


$mes_anterior = $mes_actual - 1; $anio_anterior = $anio_actual;
if ($mes_anterior < 1) { $mes_anterior = 12; $anio_anterior--; }
$mes_siguiente = $mes_actual + 1; $anio_siguiente = $anio_actual;
if ($mes_siguiente > 12) { $mes_siguiente = 1; $anio_siguiente++; }

$nombres_meses = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
$nombre_mes = $nombres_meses[$mes_actual];
$nombre_mes_anterior = $nombres_meses[$mes_anterior];
$nombre_mes_siguiente = $nombres_meses[$mes_siguiente]; 

$query_string = "";
if ($torneo_filtro) $query_string .= "&torneo=$torneo_filtro";
if ($deporte_filtro) $query_string .= "&deporte=$deporte_filtro";

echo "<script>document.title = 'Calendario de Partidos - Portal Deportivo';</script>";
?>

<div class="container page-container">
    <div class="page-header-user">
        <h1><i class="fas fa-calendar-alt"></i> Calendario de Partidos</h1>
        <p>Consulta las fechas de los próximos encuentros y torneos.</p>
    </div>

    <form method="GET" class="filtros-card-user">
        <div class="filtros-grid">
            <div class="filtro-item">
                <label for="torneo">Torneo</label>
                <select name="torneo" id="torneo">
                    <option value="">Todos los torneos</option>
                    <?php while($torneo_f = $stmt_torneos->fetch_assoc()): ?>
                        <option value="<?php echo $torneo_f['id']; ?>" <?php echo $torneo_filtro == $torneo_f['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($torneo_f['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
             <div class="filtro-item">
                <label for="deporte">Deporte</label>
                <select name="deporte" id="deporte">
                    <option value="">Todos los deportes</option>
                    <?php while($deporte_f = $stmt_deportes->fetch_assoc()): ?>
                        <option value="<?php echo $deporte_f['id']; ?>" <?php echo $deporte_filtro == $deporte_f['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($deporte_f['nombre_mostrado']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <input type="hidden" name="mes" value="<?php echo $mes_actual; ?>">
            <input type="hidden" name="anio" value="<?php echo $anio_actual; ?>">
            
            <div class="filtro-item-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="calendario.php" class="btn btn-secondary" style="color: white !important;">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="mes-navegacion">
        <a href="calendario.php?mes=<?php echo $mes_anterior; ?>&anio=<?php echo $anio_anterior; ?><?php echo $query_string; ?>" class="btn btn-primary">
            <i class="fas fa-chevron-left"></i> <?php echo $nombre_mes_anterior; ?>
        </a>
        <h2><?php echo $nombre_mes; ?> <?php echo $anio_actual; ?></h2>
        <a href="calendario.php?mes=<?php echo $mes_siguiente; ?>&anio=<?php echo $anio_siguiente; ?><?php echo $query_string; ?>" class="btn btn-primary">
            <?php echo $nombres_meses[$mes_siguiente]; ?> <i class="fas fa-chevron-right"></i>
        </a>
    </div>

    <div class="calendario-container-user">
        <?php if (empty($partidos_por_fecha)): ?>
            <div class="info-box text-center" style="padding: 2rem;">
                <p class="info-text" style="margin: 0;"><i class="fas fa-calendar-times"></i> No se encontraron partidos para <?php echo $nombre_mes; ?> <?php echo $anio_actual; ?> con los filtros seleccionados.</p>
            </div>
        <?php else: ?>
            <?php
            $hoy = date('Y-m-d');
            foreach ($partidos_por_fecha as $fecha => $partidos_del_dia):
                $timestamp = strtotime($fecha);
                $dia_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][date('w', $timestamp)];
                $dia_numero = date('d', $timestamp);
                $es_hoy = ($fecha == $hoy);
            ?>
                <div class="fecha-section-user <?php echo $es_hoy ? 'fecha-hoy' : ''; ?>">
                    <div class="fecha-header-user">
                        <div class="fecha-info-user">
                            <div class="fecha-dia-numero"><?php echo $dia_numero; ?></div>
                            <div class="fecha-detalles">
                                <strong><?php echo $dia_semana; ?></strong>
                                <small><?php echo date('d M Y', $timestamp); ?></small>
                            </div>
                        </div>
                        <div class="fecha-contador">
                            <span class="badge-user badge-primary"><?php echo count($partidos_del_dia); ?> partido<?php echo count($partidos_del_dia) > 1 ? 's' : ''; ?></span>
                            <?php if ($es_hoy): ?>
                                <span class="badge-user badge-success">Hoy</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="partidos-list-user-cal">
                        <?php foreach ($partidos_del_dia as $partido): ?>
                            <div class="partido-calendario-card-user">
                                <div class="partido-hora">
                                    <i class="fas fa-clock"></i>
                                    <strong><?php echo date('H:i', strtotime($partido['inicio_partido'])); ?></strong>
                                </div>
                                <div class="partido-contenido-user">
                                    <div class="partido-torneo-info-user">
                                        <a href="torneo_detalle.php?id=<?php echo $partido['torneo_id']; ?>" class="torneo-nombre-link"><?php echo htmlspecialchars($partido['nombre_torneo']); ?></a>
                                        <?php if ($partido['numero_jornada']): ?>
                                            <span class="jornada-num">Jornada <?php echo $partido['numero_jornada']; ?></span>
                                        <?php elseif ($partido['fase_nombre']): ?>
                                            <span class="jornada-num fase-playoff"><?php echo htmlspecialchars($partido['fase_nombre']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="partido-enfrentamiento-user">
                                        <div class="equipo-cal local">
                                            <span class="equipo-nombre-cal"><?php echo htmlspecialchars($partido['equipo_local']); ?></span>
                                            <img src="<?php echo htmlspecialchars($partido['logo_local'] ?: '../../img/logos/default.png'); ?>" alt="Logo">
                                        </div>
                                        <div class="partido-marcador-cal">
                                            <?php if ($partido['estado_id_num'] == 5): ?>
                                                <span class="marcador-num-cal <?php if($partido['marcador_local'] > $partido['marcador_visitante']) echo 'ganador'; ?>"><?php echo $partido['marcador_local']; ?></span>
                                                <span class="marcador-sep-cal">-</span>
                                                <span class="marcador-num-cal <?php if($partido['marcador_visitante'] > $partido['marcador_local']) echo 'ganador'; ?>"><?php echo $partido['marcador_visitante']; ?></span>
                                            <?php else: ?>
                                                <span class="vs-text-cal">VS</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="equipo-cal visitante">
                                            <img src="<?php echo htmlspecialchars($partido['logo_visitante'] ?: '../../img/logos/default.png'); ?>" alt="Logo">
                                            <span class="equipo-nombre-cal"><?php echo htmlspecialchars($partido['equipo_visitante']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="partido-estado-cal">
                                    <span class="badge-user badge-status-<?php echo $partido['estado_id_num']; ?>">
                                        <?php echo htmlspecialchars($partido['estado']); ?>
                                    </span>
                                    <?php
                                    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2): ?>
                                        <a href="ver_partido.php?partido_id=<?php echo $partido['id']; ?>&es_amistoso=1"
                                            class="btn btn-small btn-info" title="Editar Partido">
                                            <i class="fas fa-futbol"></i> Ver más
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt_torneos->close();
$stmt_deportes->close();
$conn->close();
require_once '../includes/footer.php'; 
?>