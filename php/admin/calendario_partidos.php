<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';


$mes_actual = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$anio_actual = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$torneo_filtro = isset($_GET['torneo']) ? (int)$_GET['torneo'] : null;
$deporte_filtro = isset($_GET['deporte']) ? (int)$_GET['deporte'] : null;
$estado_filtro = isset($_GET['estado']) ? (int)$_GET['estado'] : null;


if ($mes_actual < 1 || $mes_actual > 12) $mes_actual = date('n');
if ($anio_actual < 2000 || $anio_actual > 2100) $anio_actual = date('Y');


$primer_dia = date('Y-m-d', mktime(0, 0, 0, $mes_actual, 1, $anio_actual));
$ultimo_dia = date('Y-m-t', mktime(0, 0, 0, $mes_actual, 1, $anio_actual));


$stmt_torneos = $conn->query("SELECT id, nombre FROM torneos ORDER BY fecha_inicio DESC");


$stmt_deportes = $conn->query("SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado");


$stmt_estados = $conn->query("SELECT id, nombre_mostrado FROM estados_partido ORDER BY orden");


$sql_partidos = "SELECT p.*,
                 pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                 pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                 t.nombre AS nombre_torneo, t.id AS torneo_id,
                 d.nombre_mostrado AS deporte, d.id AS deporte_id,
                 ep.nombre_mostrado AS estado, ep.codigo AS estado_codigo,
                 j.numero_jornada, j.id AS jornada_id
                 FROM partidos p
                 JOIN participantes pl ON p.participante_local_id = pl.id
                 JOIN participantes pv ON p.participante_visitante_id = pv.id
                 JOIN torneos t ON p.torneo_id = t.id
                 JOIN deportes d ON t.deporte_id = d.id
                 JOIN estados_partido ep ON p.estado_id = ep.id
                 LEFT JOIN jornadas j ON p.jornada_id = j.id
                 WHERE DATE(p.inicio_partido) BETWEEN ? AND ?";

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

if ($estado_filtro) {
    $sql_partidos .= " AND p.estado_id = ?";
    $params[] = $estado_filtro;
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
    if (!isset($partidos_por_fecha[$fecha])) {
        $partidos_por_fecha[$fecha] = [];
    }
    $partidos_por_fecha[$fecha][] = $partido;
}


$mes_anterior = $mes_actual - 1;
$anio_anterior = $anio_actual;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $anio_anterior--;
}

$mes_siguiente = $mes_actual + 1;
$anio_siguiente = $anio_actual;
if ($mes_siguiente > 12) {
    $mes_siguiente = 1;
    $anio_siguiente++;
}


$nombres_meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$nombre_mes = $nombres_meses[$mes_actual];

?>

<main class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-calendar-alt"></i> Calendario de Partidos</h1>
        <div>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>

    
    <form method="GET" class="filtros-card">
        <div class="filtros-header">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
            <button type="button" class="btn btn-sm btn-secondary" onclick="window.location.href='calendario_partidos.php'">
                <i class="fas fa-redo"></i> Limpiar
            </button>
        </div>

        <div class="filtros-grid">
            <div class="form-group">
                <label class="form-label">Torneo</label>
                <select name="torneo" class="form-input">
                    <option value="">Todos los torneos</option>
                    <?php while($torneo = $stmt_torneos->fetch_assoc()): ?>
                        <option value="<?php echo $torneo['id']; ?>"
                                <?php echo $torneo_filtro == $torneo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($torneo['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Deporte</label>
                <select name="deporte" class="form-input">
                    <option value="">Todos los deportes</option>
                    <?php while($deporte = $stmt_deportes->fetch_assoc()): ?>
                        <option value="<?php echo $deporte['id']; ?>"
                                <?php echo $deporte_filtro == $deporte['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($deporte['nombre_mostrado']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-input">
                    <option value="">Todos los estados</option>
                    <?php while($estado = $stmt_estados->fetch_assoc()): ?>
                        <option value="<?php echo $estado['id']; ?>"
                                <?php echo $estado_filtro == $estado['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($estado['nombre_mostrado']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Mes</label>
                <select name="mes" class="form-input">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $mes_actual == $m ? 'selected' : ''; ?>>
                            <?php echo $nombres_meses[$m]; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Año</label>
                <select name="anio" class="form-input">
                    <?php for ($a = date('Y') - 1; $a <= date('Y') + 2; $a++): ?>
                        <option value="<?php echo $a; ?>" <?php echo $anio_actual == $a ? 'selected' : ''; ?>>
                            <?php echo $a; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-search"></i> Filtrar
                </button>
            </div>
        </div>
    </form>

    
    <div class="mes-navegacion">
        <a href="?mes=<?php echo $mes_anterior; ?>&anio=<?php echo $anio_anterior; ?><?php echo $torneo_filtro ? '&torneo='.$torneo_filtro : ''; ?><?php echo $deporte_filtro ? '&deporte='.$deporte_filtro : ''; ?><?php echo $estado_filtro ? '&estado='.$estado_filtro : ''; ?>"
           class="btn btn-secondary">
            <i class="fas fa-chevron-left"></i> <?php echo $nombres_meses[$mes_anterior]; ?>
        </a>

        <h2><?php echo $nombre_mes; ?> <?php echo $anio_actual; ?></h2>

        <a href="?mes=<?php echo $mes_siguiente; ?>&anio=<?php echo $anio_siguiente; ?><?php echo $torneo_filtro ? '&torneo='.$torneo_filtro : ''; ?><?php echo $deporte_filtro ? '&deporte='.$deporte_filtro : ''; ?><?php echo $estado_filtro ? '&estado='.$estado_filtro : ''; ?>"
           class="btn btn-secondary">
            <?php echo $nombres_meses[$mes_siguiente]; ?> <i class="fas fa-chevron-right"></i>
        </a>
    </div>

    
    <div class="resumen-card">
        <div class="resumen-item">
            <i class="fas fa-futbol"></i>
            <div>
                <strong><?php echo count($partidos_por_fecha); ?></strong>
                <small>Días con partidos</small>
            </div>
        </div>
        <div class="resumen-item">
            <i class="fas fa-list"></i>
            <div>
                <strong><?php echo array_sum(array_map('count', $partidos_por_fecha)); ?></strong>
                <small>Total de partidos</small>
            </div>
        </div>
    </div>

    
    <div class="calendario-container">
        <?php if (empty($partidos_por_fecha)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times" style="font-size: 3rem; color: #ccc;"></i>
                <h3>No hay partidos programados</h3>
                <p>No se encontraron partidos para <?php echo $nombre_mes; ?> <?php echo $anio_actual; ?> con los filtros seleccionados.</p>
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
                <div class="fecha-section <?php echo $es_hoy ? 'fecha-hoy' : ''; ?>">
                    <div class="fecha-header">
                        <div class="fecha-info">
                            <div class="fecha-dia-numero"><?php echo $dia_numero; ?></div>
                            <div class="fecha-detalles">
                                <strong><?php echo $dia_semana; ?></strong>
                                <small><?php echo date('d/m/Y', $timestamp); ?></small>
                            </div>
                        </div>
                        <div class="fecha-contador">
                            <span class="badge badge-primary"><?php echo count($partidos_del_dia); ?> partido<?php echo count($partidos_del_dia) > 1 ? 's' : ''; ?></span>
                            <?php if ($es_hoy): ?>
                                <span class="badge badge-success">Hoy</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="partidos-list">
                        <?php foreach ($partidos_del_dia as $partido): ?>
                            <div class="partido-calendario-card">
                                <div class="partido-hora">
                                    <i class="fas fa-clock"></i>
                                    <strong><?php echo date('H:i', strtotime($partido['inicio_partido'])); ?></strong>
                                </div>

                                <div class="partido-contenido">
                                    <div class="partido-torneo-info">
                                        <span class="torneo-nombre"><?php echo htmlspecialchars($partido['nombre_torneo']); ?></span>
                                        <?php if ($partido['numero_jornada']): ?>
                                            <span class="jornada-num">Jornada <?php echo $partido['numero_jornada']; ?></span>
                                        <?php endif; ?>
                                        <span class="deporte-badge"><?php echo htmlspecialchars($partido['deporte']); ?></span>
                                    </div>

                                    <div class="partido-enfrentamiento">
                                        <div class="equipo-calendario">
                                            <div class="equipo-logo-cal">
                                                <?php if ($partido['logo_local']): ?>
                                                    <img src="<?php echo htmlspecialchars($partido['logo_local']); ?>" alt="Logo">
                                                <?php else: ?>
                                                    <i class="fas fa-shield-alt"></i>
                                                <?php endif; ?>
                                            </div>
                                            <span class="equipo-nombre"><?php echo htmlspecialchars($partido['equipo_local']); ?></span>
                                            <span class="equipo-badge local-badge">Local</span>
                                        </div>

                                        <div class="partido-marcador">
                                            <?php if ($partido['estado_id'] == 5): ?>
                                                <div class="marcador-final">
                                                    <span class="marcador-num"><?php echo $partido['marcador_local']; ?></span>
                                                    <span class="marcador-sep">-</span>
                                                    <span class="marcador-num"><?php echo $partido['marcador_visitante']; ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="vs-text">VS</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="equipo-calendario">
                                            <div class="equipo-logo-cal">
                                                <?php if ($partido['logo_visitante']): ?>
                                                    <img src="<?php echo htmlspecialchars($partido['logo_visitante']); ?>" alt="Logo">
                                                <?php else: ?>
                                                    <i class="fas fa-shield-alt"></i>
                                                <?php endif; ?>
                                            </div>
                                            <span class="equipo-nombre"><?php echo htmlspecialchars($partido['equipo_visitante']); ?></span>
                                            <span class="equipo-badge visitante-badge">Visitante</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="partido-acciones">
                                    <span class="badge <?php echo getBadgeClass($partido['estado_id']); ?>">
                                        <?php echo htmlspecialchars($partido['estado']); ?>
                                    </span>
                                    <a href="editar_partido.php?partido_id=<?php echo $partido['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<style>
.filtros-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.filtros-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e0e0e0;
}

.filtros-header h3 {
    margin: 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filtros-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.mes-navegacion {
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
    box-shadow: 0 4px 8px rgba(26, 35, 126, 0.3);
}

.mes-navegacion h2 {
    margin: 0;
    font-size: 1.75rem;
    color: white;
}

.mes-navegacion .btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}

.mes-navegacion .btn:hover {
    background: rgba(255,255,255,0.3);
}

.resumen-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.resumen-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.resumen-item i {
    font-size: 2.5rem;
    color: #1a237e;
}

.resumen-item strong {
    display: block;
    font-size: 2rem;
    color: #1a237e;
}

.resumen-item small {
    display: block;
    font-size: 0.85rem;
    color: #666;
}

.calendario-container {
    display: grid;
    gap: 2rem;
}

.fecha-section {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.fecha-section.fecha-hoy {
    border: 2px solid #4caf50;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
}

.fecha-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #dee2e6;
}

.fecha-hoy .fecha-header {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

.fecha-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.fecha-dia-numero {
    width: 50px;
    height: 50px;
    background: #1a237e;
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}

.fecha-hoy .fecha-dia-numero {
    background: #4caf50;
}

.fecha-detalles strong {
    display: block;
    font-size: 1.1rem;
    color: #333;
}

.fecha-detalles small {
    display: block;
    font-size: 0.85rem;
    color: #666;
}

.fecha-contador {
    display: flex;
    gap: 0.5rem;
}

.partidos-list {
    padding: 1.5rem;
    display: grid;
    gap: 1rem;
}

.partido-calendario-card {
    display: grid;
    grid-template-columns: 80px 1fr auto;
    gap: 1.5rem;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #1a237e;
    transition: all 0.2s;
}

.partido-calendario-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateX(4px);
}

.partido-hora {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: #1a237e;
}

.partido-hora i {
    font-size: 1.5rem;
}

.partido-hora strong {
    font-size: 1.1rem;
}

.partido-contenido {
    flex: 1;
}

.partido-torneo-info {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.torneo-nombre {
    font-weight: 600;
    color: #1a237e;
    font-size: 0.95rem;
}

.jornada-num {
    font-size: 0.85rem;
    color: #666;
    padding: 0.25rem 0.5rem;
    background: #e0e0e0;
    border-radius: 4px;
}

.deporte-badge {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    background: #1a237e;
    color: white;
    border-radius: 4px;
}

.partido-enfrentamiento {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 1.5rem;
    align-items: center;
}

.equipo-calendario {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.equipo-logo-cal {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 6px;
    flex-shrink: 0;
}

.equipo-logo-cal img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.equipo-logo-cal i {
    font-size: 1.5rem;
    color: #999;
}

.equipo-nombre {
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
}

.equipo-badge {
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.local-badge {
    background: #e3f2fd;
    color: #1565c0;
}

.visitante-badge {
    background: #fff3e0;
    color: #e65100;
}

.partido-marcador {
    display: flex;
    align-items: center;
    justify-content: center;
}

.marcador-final {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: bold;
    font-size: 1.5rem;
    color: #1a237e;
}

.marcador-num {
    min-width: 30px;
    text-align: center;
}

.marcador-sep {
    color: #999;
}

.vs-text {
    font-weight: bold;
    color: #999;
    font-size: 1.25rem;
}

.partido-acciones {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-end;
}

@media (max-width: 1200px) {
    .partido-calendario-card {
        grid-template-columns: 70px 1fr auto;
        gap: 1rem;
    }

    .partido-enfrentamiento {
        grid-template-columns: 1fr auto 1fr;
        gap: 1rem;
    }
}

@media (max-width: 968px) {
    .filtros-grid {
        grid-template-columns: 1fr 1fr;
    }

    .partido-calendario-card {
        grid-template-columns: 1fr;
    }

    .partido-hora {
        flex-direction: row;
        justify-content: flex-start;
    }

    .partido-enfrentamiento {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .partido-acciones {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

@media (max-width: 640px) {
    .filtros-grid {
        grid-template-columns: 1fr;
    }

    .mes-navegacion {
        flex-direction: column;
        gap: 1rem;
    }

    .mes-navegacion h2 {
        font-size: 1.5rem;
    }
}
</style>

<?php
function getBadgeClass($estado_id) {
    switch($estado_id) {
        case 1: 
        case 2: 
            return 'badge-secondary';
        case 3: 
            return 'badge-success';
        case 5: 
            return 'badge-primary';
        case 6: 
        case 8: 
            return 'badge-warning';
        case 7: 
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

$stmt_torneos->close();
$stmt_deportes->close();
$stmt_estados->close();
$stmt_partidos->close();
require_once 'admin_footer.php';
?>
