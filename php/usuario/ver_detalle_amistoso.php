<?php
 require_once '../auth_user.php'; 
 require_once '../includes/header.php';

$redirect_url = 'amistosos.php'; 

if (!isset($_GET['partido_id'])) {
    header("Location: $redirect_url?error=ID de partido no especificado.");
    exit;
}

$partido_id = (int)$_GET['partido_id'];

$sql_partido = "SELECT p.*,
                pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                
                NULL AS nombre_torneo,
                NULL AS tipo_torneo,
                NULL AS jornada_id,
                NULL AS numero_jornada,
                NULL AS nombre_fase,
                
                d.nombre_mostrado AS deporte, 
                d.codigo AS codigo_deporte, 
                d.tipo_puntuacion, 
                d.eventos_disponibles, 
                d.usa_cronometro, 
                d.es_por_equipos,
                d.id AS deporte_id,
                
                ep.nombre_mostrado AS estado_actual,
                mvp.nombre_jugador AS mvp_nombre, 
                mvp.numero_camiseta AS mvp_numero
                
                FROM partidos p
                LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                LEFT JOIN deportes d ON pl.deporte_id = d.id
                LEFT JOIN estados_partido ep ON p.estado_id = ep.id
                LEFT JOIN miembros_plantel mvp ON p.mvp_miembro_plantel_id = mvp.id
                
                WHERE p.id = ? AND p.torneo_id IS NULL";

$stmt_partido = $conn->prepare($sql_partido);
if (!$stmt_partido) {
     header("Location: $redirect_url?error=Error preparando consulta: " . $conn->error);
     exit;
}
$stmt_partido->bind_param("i", $partido_id);
$stmt_partido->execute();
$partido = $stmt_partido->get_result()->fetch_assoc();

if (!$partido) {
    header("Location: $redirect_url?error=Partido amistoso no encontrado.");
    exit();
}

$url_volver = "amistosos.php"; 

$eventos_disponibles = json_decode($partido['eventos_disponibles'], true) ?? [];
$tipo_puntuacion = $partido['tipo_puntuacion'];
$usa_cronometro = (int)$partido['usa_cronometro'];
$es_deporte_individual = ((int)$partido['es_por_equipos'] === 0);
$es_ajedrez = ($partido['codigo_deporte'] === 'chess');
$es_pingpong = ($partido['codigo_deporte'] === 'table_tennis');


$jugador_local_seleccionado = null;
$jugador_visitante_seleccionado = null;
if ($es_deporte_individual) {
    if ($partido['jugador_local_id']) {
        $stmt_jl = $conn->prepare("SELECT * FROM miembros_plantel WHERE id = ?");
        $stmt_jl->bind_param("i", $partido['jugador_local_id']);
        $stmt_jl->execute();
        $jugador_local_seleccionado = $stmt_jl->get_result()->fetch_assoc();
        $stmt_jl->close();
    }
    if ($partido['jugador_visitante_id']) {
        $stmt_jv = $conn->prepare("SELECT * FROM miembros_plantel WHERE id = ?");
        $stmt_jv->bind_param("i", $partido['jugador_visitante_id']);
        $stmt_jv->execute();
        $jugador_visitante_seleccionado = $stmt_jv->get_result()->fetch_assoc();
        $stmt_jv->close();
    }
}

$puntos_sets = [];
if ($es_pingpong) {
    $stmt_sets = $conn->prepare("SELECT * FROM puntos_set WHERE partido_id = ? ORDER BY set_numero");
    $stmt_sets->bind_param("i", $partido_id);
    $stmt_sets->execute();
    $result_sets = $stmt_sets->get_result();
    while ($set = $result_sets->fetch_assoc()) {
        $puntos_sets[$set['set_numero']] = $set;
    }
    $stmt_sets->close();
}

$nombre_evento_singular = 'Evento';
$nombre_evento_plural = 'Eventos';
$icono_evento = 'fa-futbol';
switch($tipo_puntuacion) {
    case 'goles':
        $nombre_evento_singular = 'Gol'; $nombre_evento_plural = 'Goleadores'; $icono_evento = 'fa-futbol';
        break;
    case 'puntos':
        $nombre_evento_singular = 'Anotación'; $nombre_evento_plural = 'Anotaciones'; $icono_evento = 'fa-basketball-ball';
        break;
    case 'sets':
        $nombre_evento_singular = 'Set'; $nombre_evento_plural = 'Sets'; $icono_evento = 'fa-table-tennis';
        break;
    case 'ganador_directo':
        $nombre_evento_singular = 'Resultado'; $nombre_evento_plural = 'Resultado'; $icono_evento = 'fa-chess';
        break;
}

$sql_eventos = "SELECT ep.*,
                mp.nombre_jugador, mp.numero_camiseta, mp.posicion, pe.participante_id,
                mp_asist.nombre_jugador AS asistencia_nombre, mp_asist.numero_camiseta AS asistencia_numero
                FROM eventos_partido ep
                JOIN miembros_plantel mp ON ep.miembro_plantel_id = mp.id
                JOIN planteles_equipo pe ON mp.plantel_id = pe.id
                LEFT JOIN miembros_plantel mp_asist ON ep.asistencia_miembro_plantel_id = mp_asist.id
                WHERE ep.partido_id = ?
                ORDER BY ep.minuto ASC, ep.id ASC";
$stmt_eventos = $conn->prepare($sql_eventos);
$stmt_eventos->bind_param("i", $partido_id);
$stmt_eventos->execute();
$eventos = $stmt_eventos->get_result();

$goles_local = [];
$goles_visitante = [];
$contador_goles_local = 0;
$contador_goles_visitante = 0;

while($evento = $eventos->fetch_assoc()) {
    $participante_evento_id = $evento['participante_id'];
    $es_equipo_local = ($participante_evento_id == $partido['participante_local_id']);
    
    if ($tipo_puntuacion == 'goles') {
        $es_gol_valido = in_array($evento['tipo_evento'], ['gol', 'penal_anotado']);
        $es_autogol = $evento['tipo_evento'] == 'autogol';
        if ($es_equipo_local) {
            $goles_local[] = $evento;
            if ($es_gol_valido) $contador_goles_local++;
            elseif ($es_autogol) $contador_goles_visitante++;
        } else {
            $goles_visitante[] = $evento;
            if ($es_gol_valido) $contador_goles_visitante++;
            elseif ($es_autogol) $contador_goles_local++;
        }
    } 
    elseif ($tipo_puntuacion == 'puntos') {
        $puntos = (int)($evento['valor_puntos'] ?? 0); 
        $eventos_puntos = ['canasta_1pt', 'canasta_2pt', 'canasta_3pt', 'punto', 'ace', 'bloqueo'];
        if (in_array($evento['tipo_evento'], $eventos_puntos)) {
            if ($es_equipo_local) {
                $goles_local[] = $evento;
                $contador_goles_local += $puntos; 
            } else {
                $goles_visitante[] = $evento;
                $contador_goles_visitante += $puntos; 
            }
        } else {
             if ($es_equipo_local) $goles_local[] = $evento;
             else $goles_visitante[] = $evento;
        }
    } 
    else {
         if ($es_equipo_local) $goles_local[] = $evento;
         else $goles_visitante[] = $evento;
    }
}


if ($es_pingpong) {
    $marcador_local_calculado = $partido['sets_ganados_local'] ?? 0;
    $marcador_visitante_calculado = $partido['sets_ganados_visitante'] ?? 0;
} else {
    $marcador_local_calculado = $contador_goles_local;
    $marcador_visitante_calculado = $contador_goles_visitante;
}
?>

<style>
.page-container {
    max-width: 1200px; 
    margin: 2rem auto; 
    padding: 0 1.5rem; 
}
.page-header-user {
    text-align: center;
    margin-bottom: 2rem;
}
.page-header-user h1 {
    font-size: 2.5rem;
    color: #1a237e;
    margin: 0 0 0.5rem 0;
}
.page-header-user p {
    font-size: 1.1rem;
    color: #666;
    margin: 0;
}
</style>
<script>
const PARTIDO_ID = <?php echo $partido_id; ?>;
let intervaloCronometro = null;

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('cronometroTiempo')) {
        obtenerEstadoCronometro();
        intervaloCronometro = setInterval(obtenerEstadoCronometro, 1000);
    }
});

function obtenerEstadoCronometro() {
    fetch(`../usuario/api/obtener_cronometro.php?action=obtener_estado&partido_id=${PARTIDO_ID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.cronometro) {
                actualizarDisplayCronometro(data.cronometro);
            }
        })
        .catch(error => console.error('Error:', error));
}

function actualizarDisplayCronometro(cronometro) {
    const tiempoDisplay = document.getElementById('cronometroTiempo');
    const periodoDisplay = document.getElementById('cronometroPeriodo');
    const estadoDisplay = document.getElementById('cronometroEstado');
    
    if (!tiempoDisplay || !periodoDisplay || !estadoDisplay) return;

    const tiempo = formatearTiempoPartido(cronometro.tiempo_transcurrido, cronometro.tiempo_agregado);
    tiempoDisplay.textContent = tiempo;
    periodoDisplay.textContent = cronometro.periodo_actual;

    const estado = cronometro.estado_cronometro;
    let badgeHTML = '';

    if (estado === 'corriendo') {
        badgeHTML = '<span class="estado-badge estado-corriendo"><i class="fas fa-play-circle"></i> En Curso</span>';
    } else if (estado === 'pausado') {
        badgeHTML = '<span class="estado-badge estado-pausado"><i class="fas fa-pause-circle"></i> Pausado</span>';
    } else {
        badgeHTML = '<span class="estado-badge estado-detenido"><i class="fas fa-stop-circle"></i> Detenido</span>';
    }
    estadoDisplay.innerHTML = badgeHTML;
}

function formatearTiempoPartido(segundos, tiempoAgregado) {
    segundos = parseInt(segundos) || 0;
    tiempoAgregado = parseInt(tiempoAgregado) || 0;

    const minutos = Math.floor(segundos / 60);
    const segs = segundos % 60;
    const codigoDeporte = '<?php echo $partido['codigo_deporte']; ?>';

    if (codigoDeporte === 'basketball') {
        return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
    }
    if (codigoDeporte === 'table_tennis') {
        return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
    }
    if (codigoDeporte === 'football' || codigoDeporte.startsWith('futsal')) {
        let minutosBase = 45;
        if (codigoDeporte.startsWith('futsal')) minutosBase = 20;
        
        if (minutos < minutosBase) {
            return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        } else if (minutos >= minutosBase && minutos < (minutosBase + tiempoAgregado)) {
            const minAgregados = minutos - minutosBase;
            return `${minutosBase}' +${minAgregados.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        } else if (minutos < (minutosBase * 2)) {
            return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        } else if (minutos >= (minutosBase * 2)) {
            const minAgregados = minutos - (minutosBase * 2);
             return `${(minutosBase * 2)}' +${minAgregados.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }
    }
    return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
}
</script>

<div class="container page-container">
    <main class="admin-page"> <div class="page-header">
            <h1>Detalle de Partido Amistoso</h1>
            <div>
                <a href="<?php echo $url_volver; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Amistosos
                </a>
            </div>
        </div>

        <div class="partido-info-card">
            <div class="torneo-info">
                <h3>Partido Amistoso</h3>
                <p>
                    <i class="fas fa-handshake"></i> Deporte: <?php echo htmlspecialchars($partido['deporte']); ?>
                </p>
            </div>
            <div class="equipos-display">
                <?php if ($es_deporte_individual): ?>
                    <div class="jugador-individual-display">
                        <div class="jugador-info">
                            <?php if ($jugador_local_seleccionado): ?>
                                <div class="jugador-foto-individual">
                                    <?php if ($jugador_local_seleccionado['url_foto']): ?>
                                        <img src="<?php echo htmlspecialchars($jugador_local_seleccionado['url_foto']); ?>" alt="Jugador">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="jugador-datos">
                                    <strong><?php echo htmlspecialchars($jugador_local_seleccionado['nombre_jugador']); ?></strong>
                                    <small><?php echo htmlspecialchars($partido['equipo_local']); ?></small>
                                </div>
                            <?php else: ?>
                                <div class="jugador-sin-asignar"><i class="fas fa-user-plus"></i><span>Jugador local no asignado</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="vs-display">VS</div>
                        <div class="jugador-info">
                            <?php if ($jugador_visitante_seleccionado): ?>
                                <div class="jugador-foto-individual">
                                    <?php if ($jugador_visitante_seleccionado['url_foto']): ?>
                                        <img src="<?php echo htmlspecialchars($jugador_visitante_seleccionado['url_foto']); ?>" alt="Jugador">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="jugador-datos">
                                    <strong><?php echo htmlspecialchars($jugador_visitante_seleccionado['nombre_jugador']); ?></strong>
                                    <small><?php echo htmlspecialchars($partido['equipo_visitante']); ?></small>
                                </div>
                            <?php else: ?>
                                <div class="jugador-sin-asignar"><i class="fas fa-user-plus"></i><span>Jugador visitante no asignado</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="equipo-display">
                        <div class="equipo-logo-display">
                            <?php if ($partido['logo_local']): ?>
                                <img src="<?php echo htmlspecialchars($partido['logo_local']); ?>" alt="Logo">
                            <?php else: ?>
                                <i class="fas fa-shield-alt"></i>
                            <?php endif; ?>
                        </div>
                        <div class="equipo-nombre-display">
                            <strong><?php echo htmlspecialchars($partido['equipo_local']); ?></strong>
                            <small>Local</small>
                        </div>
                    </div>
                    <div class="vs-display">VS</div>
                    <div class="equipo-display">
                        <div class="equipo-logo-display">
                            <?php if ($partido['logo_visitante']): ?>
                                <img src="<?php echo htmlspecialchars($partido['logo_visitante']); ?>" alt="Logo">
                            <?php else: ?>
                                <i class="fas fa-shield-alt"></i>
                            <?php endif; ?>
                        </div>
                        <div class="equipo-nombre-display">
                            <strong><?php echo htmlspecialchars($partido['equipo_visitante']); ?></strong>
                            <small>Visitante</small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($usa_cronometro): ?>
        <div class="cronometro-section">
            <h2><i class="fas fa-clock"></i> Cronómetro del Partido</h2>
            <div class="cronometro-container" style="grid-template-columns: 1fr;"> <div class="cronometro-display">
                    <div class="cronometro-tiempo" id="cronometroTiempo">00:00</div>
                    <div class="cronometro-periodo" id="cronometroPeriodo">Detenido</div>
                    <div class="cronometro-estado" id="cronometroEstado">
                        <span class="estado-badge estado-detenido">
                            <i class="fas fa-stop-circle"></i> Detenido
                        </span>
                    </div>
                </div>
                </div>
        </div>
        <?php endif; ?>
        <?php if ($es_pingpong): ?>
            <div class="pingpong-section">
                <h2><i class="fas fa-table-tennis"></i> Control de Sets - Ping Pong</h2>
                <div class="pingpong-sets-container">
                    <div class="set-tabs">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="set-tab <?php echo $i == ($partido['set_actual'] ?? 1) ? 'active' : ''; ?>" disabled>
                                Set <?php echo $i; ?>
                                <?php if (isset($puntos_sets[$i]) && $puntos_sets[$i]['finalizado']): ?>
                                    <i class="fas fa-check-circle" style="color: #4caf50;"></i>
                                <?php endif; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <?php for ($set_num = 1; $set_num <= 5; $set_num++):
                        $set_data = $puntos_sets[$set_num] ?? null;
                        $puntos_local = $set_data['puntos_local'] ?? 0;
                        $puntos_visitante = $set_data['puntos_visitante'] ?? 0;
                        $finalizado = $set_data['finalizado'] ?? 0;
                        $ganador_set_id = $set_data['ganador_id'] ?? null;
                    ?>
                        <div class="set-content" id="set-<?php echo $set_num; ?>" style="display: <?php echo $set_num == ($partido['set_actual'] ?? 1) ? 'block' : 'none'; ?>">
                            <div class="set-marcador">
                                <div class="jugador-puntos jugador-local-puntos">
                                    <div class="jugador-nombre"><?php echo $jugador_local_seleccionado ? htmlspecialchars($jugador_local_seleccionado['nombre_jugador']) : 'Local'; ?></div>
                                    <div class="puntos-display" id="puntos-local-set-<?php echo $set_num; ?>"><?php echo $puntos_local; ?></div>
                                </div>
                                <div class="marcador-separador">-</div>
                                <div class="jugador-puntos jugador-visitante-puntos">
                                    <div class="jugador-nombre"><?php echo $jugador_visitante_seleccionado ? htmlspecialchars($jugador_visitante_seleccionado['nombre_jugador']) : 'Visitante'; ?></div>
                                    <div class="puntos-display" id="puntos-visitante-set-<?php echo $set_num; ?>"><?php echo $puntos_visitante; ?></div>
                                </div>
                            </div>
                            <?php if ($finalizado): ?>
                                <div class="set-finalizado-badge">
                                    <i class="fas fa-flag-checkered"></i>
                                    Set finalizado - Ganador: <?php
                                        if ($ganador_set_id == $partido['jugador_local_id']) echo $jugador_local_seleccionado ? htmlspecialchars($jugador_local_seleccionado['nombre_jugador']) : 'Local';
                                        else echo $jugador_visitante_seleccionado ? htmlspecialchars($jugador_visitante_seleccionado['nombre_jugador']) : 'Visitante';
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                    <div class="sets-resumen">
                        <div class="sets-ganados">
                            <strong>Sets Ganados:</strong>
                            Local: <span id="sets-local"><?php echo $partido['sets_ganados_local'] ?? 0; ?></span> -
                            Visitante: <span id="sets-visitante"><?php echo $partido['sets_ganados_visitante'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="goleadores-section">
            <?php if (!$es_pingpong && !$es_ajedrez): ?>
                <h2><i class="fas <?php echo $icono_evento; ?>"></i> <?php echo $nombre_evento_plural; ?> del Partido</h2>
                <div class="goleadores-container">
                    <div class="goleadores-equipo">
                        <div class="goleadores-header">
                            <h3><?php echo htmlspecialchars($partido['equipo_local']); ?></h3>
                            </div>
                        <div class="goles-list">
                            <?php if (empty($goles_local)): ?>
                                <div class="empty-goles"><i class="fas <?php echo $icono_evento; ?>"></i><p>Sin <?php echo strtolower($nombre_evento_plural); ?> registrados</p></div>
                            <?php else: ?>
                                <?php foreach($goles_local as $gol): ?>
                                <div class="gol-item">
                                    <div class="gol-info">
                                        <div class="jugador-numero"><?php echo htmlspecialchars($gol['numero_camiseta'] ?? '?'); ?></div>
                                        <div class="jugador-datos">
                                            <strong><?php echo htmlspecialchars($gol['nombre_jugador'] ?? 'N/A'); ?></strong>
                                            <small><?php echo htmlspecialchars($gol['posicion'] ?? ''); ?></small>
                                            <?php if (!empty($gol['asistencia_nombre'])): ?>
                                                <small class="asistencia-info"><i class="fas fa-hands-helping"></i> Asist: <?php echo htmlspecialchars($gol['asistencia_nombre']); ?> (#<?php echo htmlspecialchars($gol['asistencia_numero'] ?? '?'); ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($gol['minuto'])): ?>
                                            <span class="gol-minuto"><?php echo htmlspecialchars($gol['minuto']); ?>'</span>
                                        <?php endif; ?>
                                    </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="goleadores-equipo">
                        <div class="goleadores-header">
                            <h3><?php echo htmlspecialchars($partido['equipo_visitante']); ?></h3>
                            </div>
                        <div class="goles-list">
                            <?php if (empty($goles_visitante)): ?>
                                <div class="empty-goles"><i class="fas <?php echo $icono_evento; ?>"></i><p>Sin <?php echo strtolower($nombre_evento_plural); ?> registrados</p></div>
                            <?php else: ?>
                                <?php foreach($goles_visitante as $gol): ?>
                                <div class="gol-item">
                                    <div class="gol-info">
                                        <div class="jugador-numero"><?php echo htmlspecialchars($gol['numero_camiseta'] ?? '?'); ?></div>
                                        <div class="jugador-datos">
                                            <strong><?php echo htmlspecialchars($gol['nombre_jugador'] ?? 'N/A'); ?></strong>
                                            <small><?php echo htmlspecialchars($gol['posicion'] ?? ''); ?></small>
                                            <?php if (!empty($gol['asistencia_nombre'])): ?>
                                                <small class="asistencia-info"><i class="fas fa-hands-helping"></i> Asist: <?php echo htmlspecialchars($gol['asistencia_nombre']); ?> (#<?php echo htmlspecialchars($gol['asistencia_numero'] ?? '?'); ?>)</small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($gol['minuto'])): ?>
                                            <span class="gol-minuto"><?php echo htmlspecialchars($gol['minuto']); ?>'</span>
                                        <?php endif; ?>
                                    </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$es_ajedrez): ?>
        <div class="mvp-section">
            <h2><i class="fas fa-trophy"></i> MVP del Partido</h2>
            <div class="mvp-container">
                <?php if ($partido['mvp_miembro_plantel_id']): ?>
                    <div class="mvp-actual">
                        <div class="mvp-badge"><i class="fas fa-star"></i></div>
                        <div class="mvp-info">
                            <div class="mvp-numero"><?php echo htmlspecialchars($partido['mvp_numero'] ?? '?'); ?></div>
                            <div class="mvp-datos">
                                <strong><?php echo htmlspecialchars($partido['mvp_nombre'] ?? 'N/A'); ?></strong>
                                <small>MVP Actual</small>
                            </div>
                        </div>
                        </div>
                <?php else: ?>
                    <div class="mvp-vacio">
                        <i class="fas fa-trophy"></i>
                        <p>No se ha seleccionado MVP para este partido</p>
                        </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="form-section">
            <h2><i class="fas fa-scoreboard"></i> Resultado del Partido</h2>
            
            <?php if ($es_ajedrez): ?>
                <div class="form-section">
                    <h2><i class="fas fa-trophy"></i> Ganador</h2>
                    <?php if ($partido['ganador_individual_id'] !== null): ?>
                        <div class="resultado-ajedrez-definido">
                            <i class="fas fa-check-circle"></i>
                            <strong>Resultado Definido:</strong>
                            <?php
                            if ($partido['ganador_individual_id'] == 0) echo '<span class="badge-empate">Empate / Tablas</span>';
                            elseif ($partido['ganador_individual_id'] == $partido['jugador_local_id']) echo '<span class="badge-ganador">Ganador: ' . htmlspecialchars($jugador_local_seleccionado['nombre_jugador'] ?? 'Local') . '</span>';
                            elseif ($partido['ganador_individual_id'] == $partido['jugador_visitante_id']) echo '<span class="badge-ganador">Ganador: ' . htmlspecialchars($jugador_visitante_seleccionado['nombre_jugador'] ?? 'Visitante') . '</span>';
                            ?>
                        </div>
                    <?php else: ?>
                         <div class="info-badge"><i class="fas fa-info-circle"></i> El resultado aún no ha sido definido.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="marcadores-grid">
                <div class="marcador-group">
                    <label class="form-label">Marcador Local <small>(<?php echo htmlspecialchars($partido['local_corto']); ?>)</small></label>
                    <div class="marcador-display">
                        <div class="marcador-calculado"><?php echo $marcador_local_calculado; ?></div>
                    </div>
                </div>
                <div class="marcador-separator"><span>-</span></div>
                <div class="marcador-group">
                    <label class="form-label">Marcador Visitante <small>(<?php echo htmlspecialchars($partido['visitante_corto']); ?>)</small></label>
                    <div class="marcador-display">
                        <div class="marcador-calculado"><?php echo $marcador_visitante_calculado; ?></div>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div> <style>
    .partido-info-card { background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    .torneo-info { text-align: center; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 2px solid #e0e0e0; }
    .torneo-info h3 { margin: 0 0 0.5rem 0; color: #1a237e; }
    .torneo-info p { margin: 0; color: #666; font-size: 0.95rem; }
    .equipos-display { display: grid; grid-template-columns: 1fr auto 1fr; gap: 2rem; align-items: center; }
    .equipo-display { display: flex; flex-direction: column; align-items: center; gap: 1rem; }
    .equipo-logo-display { width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; background: #f5f5f5; border-radius: 12px; padding: 0.5rem; }
    .equipo-logo-display img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .equipo-logo-display i { font-size: 3rem; color: #999; }
    .equipo-nombre-display { text-align: center; }
    .equipo-nombre-display strong { display: block; font-size: 1.1rem; color: #333; margin-bottom: 0.25rem; }
    .equipo-nombre-display small { color: #666; font-size: 0.85rem; }
    .vs-display { font-size: 1.5rem; font-weight: bold; color: #1a237e; }
    .marcadores-grid { display: grid; grid-template-columns: 1fr auto 1fr; gap: 2rem; align-items: end; margin-bottom: 1.5rem; }
    .marcador-group { display: flex; flex-direction: column; align-items: center; }
    .marcador-group .form-label { text-align: center; margin-bottom: 0.5rem; }
    .marcador-group .form-label small { display: block; font-weight: normal; color: #666; font-size: 0.85rem; }
    .marcador-input { width: 120px !important; text-align: center; font-size: 2rem !important; font-weight: bold; padding: 0.75rem !important; color: #1a237e; }
    .marcadores-calculados { margin-bottom: 1.5rem; }
    .info-badge { padding: 1rem 1.5rem; border-radius: 8px; display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; }
    .info-badge-primary { background: rgba(26, 35, 126, 0.1); color: #1a237e; border: 1px solid rgba(26, 35, 126, 0.3); }
    .info-badge i { font-size: 1.25rem; }
    .marcador-display { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
    .marcador-calculado { width: 120px; height: 120px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%); color: white; font-size: 3rem; font-weight: bold; border-radius: 12px; box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3); }
    .marcador-display small { color: #666; font-size: 0.85rem; font-weight: normal; }
    .marcador-separator { font-size: 2rem; font-weight: bold; color: #999; padding-bottom: 0.5rem; }
    .sets-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; margin-top: 1rem; }
    .cronometro-section { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    .cronometro-section h2 { margin: 0 0 1.5rem 0; color: #1a237e; display: flex; align-items: center; gap: 0.5rem; }
    .cronometro-container { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start; }
    .cronometro-display { background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%); color: white; padding: 2rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3); }
    .cronometro-tiempo { font-size: 4rem; font-weight: bold; letter-spacing: 0.1em; margin-bottom: 0.5rem; font-family: 'Courier New', monospace; }
    .cronometro-periodo { font-size: 1.25rem; margin-bottom: 1rem; opacity: 0.9; }
    .cronometro-estado { margin-top: 1rem; }
    .estado-badge { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: 500; }
    .estado-detenido { background: rgba(255,255,255,0.2); }
    .estado-corriendo { background: rgba(40,167,69,0.9); animation: pulse 2s ease-in-out infinite; }
    .estado-pausado { background: rgba(255,193,7,0.9); }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
    .cronometro-controles { display: flex; flex-direction: column; gap: 1rem; }
    .btn-cronometro { padding: 0.75rem 1.5rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; justify-content: center; transition: all 0.2s; }
    .btn-cronometro:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
    .btn-iniciar { background: #28a745; color: white; font-size: 1.1rem; padding: 1rem 2rem; }
    .btn-iniciar:hover { background: #218838; }
    .btn-pausar { background: #ffc107; color: #333; font-size: 1.1rem; padding: 1rem 2rem; }
    .btn-pausar:hover { background: #e0a800; }
    .btn-reanudar { background: #17a2b8; color: white; font-size: 1.1rem; padding: 1rem 2rem; }
    .btn-reanudar:hover { background: #138496; }
    .cronometro-opciones { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .btn-periodo { background: #6c757d; color: white; flex: 1; min-width: 120px; }
    .btn-periodo-small { min-width: 90px; font-size: 0.85rem; padding: 0.6rem 0.75rem; }
    .btn-periodo:hover { background: #5a6268; }
    .btn-reiniciar { background: #dc3545; color: white; flex: 1; min-width: 120px; }
    .btn-reiniciar:hover { background: #c82333; }
    .btn-sm { padding: 0.5rem 1rem; font-size: 0.9rem; }
    .tiempo-agregado-control { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f8f9fa; border-radius: 6px; }
    .tiempo-agregado-control label { font-size: 0.9rem; color: #666; margin: 0; }
    .tiempo-agregado-control .form-input { margin: 0; }
    .goleadores-section { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    .goleadores-section h2 { margin: 0 0 1.5rem 0; color: #1a237e; display: flex; align-items: center; gap: 0.5rem; }
    .goleadores-container { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
    .goleadores-equipo { border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; }
    .goleadores-header { background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%); color: white; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
    .goleadores-header h3 { margin: 0; font-size: 1.1rem; }
    .goles-list { padding: 1rem; min-height: 150px; }
    .empty-goles { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; color: #999; }
    .empty-goles i { font-size: 3rem; margin-bottom: 1rem; }
    .empty-goles p { margin: 0; font-size: 0.95rem; }
    .gol-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8f9fa; border-radius: 6px; margin-bottom: 0.5rem; transition: all 0.2s; }
    .gol-item:hover { background: #e9ecef; transform: translateX(5px); }
    .gol-info { display: flex; align-items: center; gap: 1rem; flex: 1; }
    .jugador-numero { width: 36px; height: 36px; background: #1a237e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; }
    .jugador-datos { display: flex; flex-direction: column; gap: 0.15rem; flex: 1; }
    .jugador-datos strong { font-size: 0.95rem; color: #333; }
    .jugador-datos small { font-size: 0.8rem; color: #666; }
    .asistencia-info { display: flex; align-items: center; gap: 0.35rem; color: #1a237e !important; font-weight: 500; margin-top: 0.25rem; }
    .asistencia-info i { font-size: 0.75rem; }
    .gol-minuto { background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
    .btn-delete-gol { background: #dc3545; color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; flex-shrink: 0; }
    .btn-delete-gol:hover { background: #c82333; transform: scale(1.1); }
    .mvp-section { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    .mvp-section h2 { margin: 0 0 1.5rem 0; color: #1a237e; display: flex; align-items: center; gap: 0.5rem; }
    .mvp-container { display: flex; justify-content: center; align-items: center; min-height: 150px; }
    .mvp-actual { display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); border-radius: 12px; box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3); }
    .mvp-badge { width: 60px; height: 60px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #ffd700; flex-shrink: 0; }
    .mvp-info { display: flex; align-items: center; gap: 1rem; flex: 1; }
    .mvp-numero { width: 50px; height: 50px; background: #1a237e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.25rem; flex-shrink: 0; }
    .mvp-datos { display: flex; flex-direction: column; gap: 0.25rem; }
    .mvp-datos strong { font-size: 1.1rem; color: #333; }
    .mvp-datos small { font-size: 0.85rem; color: #666; }
    .mvp-vacio { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; color: #999; text-align: center; }
    .mvp-vacio i { font-size: 3rem; margin-bottom: 1rem; color: #ffd700; }
    .mvp-vacio p { margin: 0 0 1.5rem 0; font-size: 0.95rem; }
    .equipo-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
    .tab-btn { flex: 1; padding: 0.75rem 1rem; border: 2px solid #e0e0e0; background: white; border-radius: 6px; font-size: 0.95rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
    .tab-btn:hover { border-color: #1a237e; color: #1a237e; }
    .tab-btn.active { background: #1a237e; color: white; border-color: #1a237e; }
    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 9999; }
    .modal-content { background: white; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
    .modal-header { background: #1a237e; color: white; padding: 1.5rem; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
    .modal-header h3 { margin: 0; font-size: 1.25rem; }
    .modal-close { background: transparent; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .modal-close:hover { transform: scale(1.2); }
    .modal-content form { padding: 1.5rem; }
    .modal-actions { display: flex; gap: 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e0e0e0; }
    .modal-actions .btn { flex: 1; }
    @media (max-width: 768px) {
        .equipos-display { grid-template-columns: 1fr; gap: 1rem; }
        .vs-display { order: 2; }
        .marcadores-grid { grid-template-columns: 1fr; }
        .marcador-separator { display: none; }
        .sets-grid { grid-template-columns: 1fr; }
        .cronometro-container { grid-template-columns: 1fr; }
        .cronometro-tiempo { font-size: 3rem; }
        .cronometro-opciones { flex-direction: column; }
        .btn-periodo, .btn-reiniciar { width: 100%; }
        .tiempo-agregado-control { flex-wrap: wrap; }
        .goleadores-container { grid-template-columns: 1fr; gap: 1rem; }
        .goleadores-header { flex-direction: column; gap: 0.75rem; text-align: center; }
        .goleadores-header .btn { width: 100%; }
        .gol-info { gap: 0.5rem; }
        .jugador-numero { width: 32px; height: 32px; font-size: 0.85rem; }
    }
    .jugador-individual-display { display: flex; align-items: center; justify-content: space-around; padding: 2rem; background: #f8f9fa; border-radius: 12px; }
    .jugador-info { display: flex; flex-direction: column; align-items: center; gap: 1rem; flex: 1; }
    .jugador-foto-individual { width: 120px; height: 120px; border-radius: 50%; overflow: hidden; background: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .jugador-foto-individual img { width: 100%; height: 100%; object-fit: cover; }
    .jugador-foto-individual i { font-size: 4rem; color: #ccc; }
    .jugador-datos { text-align: center; }
    .jugador-datos strong { font-size: 1.25rem; color: #1a237e; }
    .jugador-datos small { color: #666; font-size: 0.9rem; }
    .jugador-sin-asignar { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; color: #999; padding: 2rem; }
    .jugador-sin-asignar i { font-size: 3rem; }
    .pingpong-section { background: white; padding: 2rem; border-radius: 12px; margin: 2rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .pingpong-section h2 { color: #1a237e; margin-bottom: 1.5rem; }
    .set-tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid #e0e0e0; }
    .set-tab { padding: 1rem 1.5rem; background: transparent; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-weight: 600; color: #666; transition: all 0.2s; }
    .set-tab:hover { color: #1a237e; background: #f5f5f5; }
    .set-tab.active { color: #1a237e; border-bottom-color: #1a237e; background: #f5f5f5; }
    .set-marcador { display: flex; align-items: center; justify-content: center; gap: 3rem; padding: 2rem; background: #f8f9fa; border-radius: 12px; }
    .jugador-puntos { display: flex; flex-direction: column; align-items: center; gap: 1rem; }
    .jugador-nombre { font-weight: 600; color: #333; font-size: 1.1rem; }
    .puntos-display { font-size: 4rem; font-weight: bold; color: #1a237e; font-family: 'Courier New', monospace; min-width: 100px; text-align: center; }
    .puntos-botones { display: flex; gap: 1rem; }
    .btn-punto { width: 50px; height: 50px; border-radius: 50%; border: none; cursor: pointer; font-size: 1.5rem; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
    .btn-sumar { background: #4caf50; color: white; }
    .btn-sumar:hover:not(:disabled) { background: #45a049; transform: scale(1.1); }
    .btn-restar { background: #f44336; color: white; }
    .btn-restar:hover:not(:disabled) { background: #da190b; transform: scale(1.1); }
    .btn-punto:disabled { opacity: 0.5; cursor: not-allowed; }
    .marcador-separador { font-size: 3rem; font-weight: bold; color: #666; }
    .set-finalizado-badge { text-align: center; padding: 1rem; background: #4caf50; color: white; border-radius: 8px; margin-top: 1rem; font-weight: 600; }
    .set-acciones { margin-top: 1.5rem; text-align: center; padding: 1rem; background: #fff3e0; border-radius: 8px; border: 2px dashed #ff9800; }
    .set-acciones small { display: block; margin-top: 0.75rem; font-size: 0.85rem; line-height: 1.4; }
    .btn-finalizar-set { padding: 0.75rem 2rem; background: #ff9800; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
    .btn-finalizar-set:hover { background: #f57c00; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3); }
    .btn-finalizar-set:active { transform: translateY(0); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
    .btn-finalizar-set i { margin-right: 0.5rem; }
    .sets-resumen { margin-top: 2rem; padding: 1rem; background: #e3f2fd; border-radius: 8px; text-align: center; }
    .sets-ganados { font-size: 1.1rem; color: #1565c0; }
    .sets-ganados span { font-weight: bold; font-size: 1.3rem; }
    .ajedrez-botones-ganador { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0; }
    .btn-ajedrez { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem; border: 3px solid transparent; border-radius: 12px; cursor: pointer; font-size: 1.1rem; font-weight: 600; transition: all 0.3s; min-height: 140px; background: white; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
    .btn-ajedrez i { font-size: 2.5rem; margin-bottom: 0.75rem; }
    .btn-ajedrez .nombre-jugador { font-size: 1.2rem; margin-bottom: 0.5rem; font-weight: 700; }
    .btn-ajedrez .texto-accion { font-size: 0.95rem; font-weight: 500; opacity: 0.8; }
    .btn-ganador-local { border-color: #4caf50; color: #2e7d32; }
    .btn-ganador-local:hover { background: #4caf50; color: white; transform: translateY(-4px); box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4); }
    .btn-empate { border-color: #9e9e9e; color: #616161; }
    .btn-empate:hover { background: #9e9e9e; color: white; transform: translateY(-4px); box-shadow: 0 6px 20px rgba(158, 158, 158, 0.4); }
    .btn-ganador-visitante { border-color: #2196f3; color: #1565c0; }
    .btn-ganador-visitante:hover { background: #2196f3; color: white; transform: translateY(-4px); box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4); }
    .btn-ajedrez:active { transform: translateY(-2px); }
    .resultado-ajedrez-definido { background: #e8f5e9; border: 2px solid #4caf50; border-radius: 12px; padding: 2rem; text-align: center; }
    .resultado-ajedrez-definido i { font-size: 3rem; color: #4caf50; margin-bottom: 1rem; display: block; }
    .resultado-ajedrez-definido strong { font-size: 1.2rem; display: block; margin-bottom: 1rem; color: #2e7d32; }
    .badge-ganador { display: inline-block; background: #4caf50; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 1.3rem; font-weight: 700; margin: 0.5rem 0; }
    .badge-empate { display: inline-block; background: #9e9e9e; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 1.3rem; font-weight: 700; margin: 0.5rem 0; }
    .texto-advertencia { margin-top: 1rem; color: #616161; font-size: 0.95rem; }
    @media (max-width: 768px) {
        .ajedrez-botones-ganador { grid-template-columns: 1fr; gap: 1rem; }
        .btn-ajedrez { min-height: 100px; padding: 1.5rem 1rem; }
    }
    .alert-fixed { position: fixed; top: 80px; right: 20px; z-index: 10000; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

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
    .badge-user { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .badge-status-1, .badge-status-2 { background-color: #2196F3; color: white; } 
    .badge-status-3, .badge-status-4 { background-color: #4CAF50; color: white; } 
    .badge-status-5 { background-color: #757575; color: white; }
    .badge-status-6, .badge-status-8 { background-color: #FF9800; color: white; }
    .badge-status-7 { background-color: #F44336; color: white; }
    .badge-status-9 { background-color: #607D8B; color: white; } 
    .info-box { background: #f8f9fa; border-radius: 8px; padding: 1.5rem; }
    .info-text { font-size: 1rem; color: #666; }
    .text-center { text-align: center; }
</style>


<?php
$stmt_partido->close();
$stmt_eventos->close();
require_once '../includes/footer.php';
?>