<?php
 require_once '../auth_user.php'; 
 require_once '../includes/header.php'; 

$sql_amistosos = "SELECT p.*,
                  pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                  pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                  ep.nombre_mostrado AS estado, ep.id AS estado_id_num,
                  d.nombre_mostrado AS deporte
                  FROM partidos p
                  LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                  LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                  LEFT JOIN estados_partido ep ON p.estado_id = ep.id
                  LEFT JOIN deportes d ON (pl.deporte_id = d.id) 
                  WHERE p.torneo_id IS NULL 
                  AND pl.id IS NOT NULL AND pv.id IS NOT NULL
                  ORDER BY p.inicio_partido DESC";

$result_amistosos = $conn->query($sql_amistosos);

$amistosos_por_fecha = [];
if ($result_amistosos) {
    while ($partido = $result_amistosos->fetch_assoc()) {
        $fecha = date('Y-m-d', strtotime($partido['inicio_partido']));
        if (!isset($amistosos_por_fecha[$fecha])) {
            $amistosos_por_fecha[$fecha] = [];
        }
        $amistosos_por_fecha[$fecha][] = $partido;
    }
}

echo "<script>document.title = 'Partidos Amistosos - Portal Deportivo';</script>";
?>

<div class="container page-container">
    <div class="page-header-user">
        <h1><i class="fas fa-handshake"></i> Partidos Amistosos</h1>
        <p>Consulta los próximos y pasados encuentros amistosos.</p>
    </div>

    <div class="calendario-container-user">
        <?php if (empty($amistosos_por_fecha)): ?>
            <div class="info-box text-center" style="padding: 2rem;">
                <p class="info-text" style="margin: 0;"><i class="fas fa-calendar-times"></i> No se encontraron partidos amistosos programados.</p>
                
                <?php 
                if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
                    <a href="../admin/crear_amistoso.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Crear Partido Amistoso
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php
            $hoy = date('Y-m-d');
            foreach ($amistosos_por_fecha as $fecha => $partidos_del_dia):
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
                                        <span class="torneo-nombre-link">Partido Amistoso</span>
                                        <span class="jornada-num fase-amistoso"><?php echo htmlspecialchars($partido['deporte']); ?></span>
                                    </div>
                                    <div class="partido-enfrentamiento-user">
                                        <div class="equipo-cal local">
                                            <span class="equipo-nombre-cal"><?php echo htmlspecialchars($partido['equipo_local']); ?></span>
                                            <img src="<?php echo htmlspecialchars($partido['logo_local'] ?: '../../img/logos/default.png'); ?>" alt="Logo">
                                        </div>
                                        
                                        <div class="partido-marcador-cal">
                                            <?php if ($partido['estado_id_num'] == 5 || $partido['estado_id_num'] == 3): ?>
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
                                
                                <div class="partido-estado-cal" style="display: flex; flex-direction: column; gap: 0.5rem; align-items: center;">
                                    <span class="badge-user badge-status-<?php echo $partido['estado_id_num']; ?>">
                                        <?php echo htmlspecialchars($partido['estado']); ?>
                                    </span>
                                    
                                    <?php
                                    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2): ?>
                                        <a href="ver_detalle_amistoso.php?partido_id=<?php echo $partido['id']; ?>&es_amistoso=1"
                                            class="btn btn-small btn-info" title="Editar Partido">
                                            <i class="fas fa-futbol"></i>
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

<style>
.page-container { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; }
.page-header-user { text-align: center; margin-bottom: 2rem; }
.page-header-user h1 { font-size: 2.5rem; color: #1a237e; margin: 0 0 0.5rem 0; }
.page-header-user p { font-size: 1.1rem; color: #666; margin: 0; }

.calendario-container-user { display: grid; gap: 2rem; }
.fecha-section-user { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.fecha-hoy { border: 2px solid #4caf50; }
.fecha-header-user { background: #f8f9fa; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e0e0e0; }
.fecha-info-user { display: flex; align-items: center; gap: 1rem; }
.fecha-dia-numero { width: 45px; height: 45px; background: #1a237e; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; }
.fecha-hoy .fecha-dia-numero { background: #4caf50; }
.fecha-detalles strong { display: block; font-size: 1.1rem; color: #333; }
.fecha-detalles small { display: block; font-size: 0.85rem; color: #666; }
.fecha-contador { display: flex; gap: 0.5rem; }
.badge-user { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.badge-primary { background: #1a237e; color: white; }
.badge-success { background: #4caf50; color: white; }

.partidos-list-user-cal { padding: 1rem; display: grid; gap: 1rem; }
.partido-calendario-card-user { display: grid; grid-template-columns: 100px 1fr auto; gap: 1rem; align-items: center; padding: 1rem; background: #fdfdfd; border-radius: 8px; border: 1px solid #eee; }
.partido-hora { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; color: #1a237e; border-right: 2px solid #f0f0f0; padding-right: 1rem; }
.partido-hora i { font-size: 1.5rem; }
.partido-hora strong { font-size: 1.2rem; }

.partido-contenido-user { flex: 1; }
.partido-torneo-info-user { display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; }
.torneo-nombre-link { font-weight: 600; color: #1a237e; font-size: 0.95rem; text-decoration: none; }
.jornada-num { font-size: 0.8rem; color: #666; padding: 0.25rem 0.6rem; background: #e0e0e0; border-radius: 12px; }
.fase-playoff { background: #e8eaf6; color: #3f51b5; }
.fase-amistoso { background: #e0f2f1; color: #00796b; font-weight: 600; }

.partido-enfrentamiento-user { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: center; }
.equipo-cal { display: flex; align-items: center; gap: 0.75rem; }
.equipo-cal.local { justify-content: flex-end; }
.equipo-cal.visitante { justify-content: flex-start; }
.equipo-cal img { width: 40px; height: 40px; object-fit: contain; border-radius: 50%; }
.equipo-nombre-cal { font-weight: 600; color: #333; font-size: 1rem; }
.equipo-cal.local .equipo-nombre-cal { order: 1; text-align: right; }
.equipo-cal.local img { order: 2; }

.partido-marcador-cal { display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.marcador-num-cal { font-size: 1.5rem; font-weight: bold; color: #666; }
.marcador-num-cal.ganador { color: #1a237e; }
.marcador-sep-cal { font-size: 1rem; color: #999; }
.vs-text-cal { font-weight: bold; color: #999; font-size: 1.25rem; }

.partido-estado-cal { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding-left: 1rem; border-left: 2px solid #f0f0f0; }
.badge-status-1, .badge-status-2 { background-color: #2196F3; color: white; } 
.badge-status-3, .badge-status-4 { background-color: #4CAF50; color: white; }
.badge-status-5 { background-color: #757575; color: white; }
.badge-status-6, .badge-status-8 { background-color: #FF9800; color: white; } 
.badge-status-7 { background-color: #F44336; color: white; } 
.badge-status-9 { background-color: #607D8B; color: white; } 

.info-box { background: #f8f9fa; border-radius: 8px; padding: 1.5rem; }
.info-text { font-size: 1rem; color: #666; }
.text-center { text-align: center; }

@media (max-width: 768px) {
    .partido-calendario-card-user {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    .partido-hora {
        flex-direction: row;
        border-right: none;
        border-bottom: 2px solid #f0f0f0;
        padding-right: 0;
        padding-bottom: 1rem;
        justify-content: center;
    }
    .partido-estado-cal {
        border-left: none;
        border-top: 2px solid #f0f0f0;
        padding-left: 0;
        padding-top: 1rem;
        flex-direction: row;
        justify-content: space-between;
    }
    .partido-enfrentamiento-user {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    .equipo-cal.local { justify-content: space-between; }
    .equipo-cal.visitante { justify-content: space-between; }
    .equipo-cal.local .equipo-nombre-cal { order: 2; text-align: left; }
    .equipo-cal.local img { order: 1; }
    .partido-marcador-cal {
        justify-content: center;
        padding: 0.5rem 0;
    }
}
</style>

<?php
$conn->close();
require_once '../includes/footer.php'; 
?>