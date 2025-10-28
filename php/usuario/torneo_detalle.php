<?php

require_once '../auth_user.php'; 
require_once '../includes/header.php'; 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: torneos.php");
    exit;
}

$torneo_id = (int)$_GET['id'];


$stmt_torneo = $conn->prepare("SELECT t.*, d.nombre_mostrado AS deporte, e.nombre_mostrado AS estado, e.codigo AS estado_codigo
                              FROM torneos t
                              JOIN deportes d ON t.deporte_id = d.id
                              JOIN estados_torneo e ON t.estado_id = e.id
                              WHERE t.id = ?");
$stmt_torneo->bind_param("i", $torneo_id);
$stmt_torneo->execute();
$torneo_res = $stmt_torneo->get_result();

if ($torneo_res->num_rows == 0) {
    header("Location: torneos.php?error=Torneo no encontrado");
    exit;
}
$torneo = $torneo_res->fetch_assoc();
$stmt_torneo->close();

$es_liga = ($torneo['tipo_torneo'] == 'liga');
$es_bracket = ($torneo['tipo_torneo'] == 'bracket');


$tabla = [];
if ($es_liga) {
    $stmt_equipos = $conn->prepare("SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo FROM torneo_participantes tp
                                    JOIN participantes p ON tp.participante_id = p.id WHERE tp.torneo_id = ?");
    $stmt_equipos->bind_param("i", $torneo_id);
    $stmt_equipos->execute();
    $equipos_result = $stmt_equipos->get_result();

    while ($equipo = $equipos_result->fetch_assoc()) {
        $tabla[$equipo['id']] = [
            'id' => $equipo['id'], 'nombre' => $equipo['nombre_mostrado'], 'nombre_corto' => $equipo['nombre_corto'],
            'logo' => $equipo['url_logo'], 'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
            'gf' => 0, 'gc' => 0, 'dg' => 0, 'pts' => 0
        ];
    }
    $stmt_equipos->close();

    $sql_partidos = "SELECT p.participante_local_id, p.participante_visitante_id, p.marcador_local, p.marcador_visitante
                     FROM partidos p
                     JOIN fases f ON p.fase_id = f.id
                     WHERE p.torneo_id = ? AND p.estado_id = 5 AND f.tipo_fase_id = 1";
    $stmt_partidos = $conn->prepare($sql_partidos);
    $stmt_partidos->bind_param("i", $torneo_id);
    $stmt_partidos->execute();
    $partidos = $stmt_partidos->get_result();

    while ($partido = $partidos->fetch_assoc()) {
        $local_id = $partido['participante_local_id'];
        $visitante_id = $partido['participante_visitante_id'];
        if (!isset($tabla[$local_id]) || !isset($tabla[$visitante_id])) continue;

        $tabla[$local_id]['pj']++; $tabla[$visitante_id]['pj']++;
        $tabla[$local_id]['gf'] += $partido['marcador_local']; $tabla[$local_id]['gc'] += $partido['marcador_visitante'];
        $tabla[$visitante_id]['gf'] += $partido['marcador_visitante']; $tabla[$visitante_id]['gc'] += $partido['marcador_local'];

        if ($partido['marcador_local'] > $partido['marcador_visitante']) {
            $tabla[$local_id]['pg']++; $tabla[$local_id]['pts'] += 3; $tabla[$visitante_id]['pp']++;
        } elseif ($partido['marcador_local'] < $partido['marcador_visitante']) {
            $tabla[$visitante_id]['pg']++; $tabla[$visitante_id]['pts'] += 3; $tabla[$local_id]['pp']++;
        } else {
            $tabla[$local_id]['pe']++; $tabla[$local_id]['pts'] += 1; $tabla[$visitante_id]['pe']++;
        }
    }
    foreach ($tabla as $id => &$equipo) $equipo['dg'] = $equipo['gf'] - $equipo['gc'];
    usort($tabla, function($a, $b) {
        if ($b['pts'] != $a['pts']) return $b['pts'] - $a['pts'];
        if ($b['dg'] != $a['dg']) return $b['dg'] - $a['dg'];
        return $b['gf'] - $a['gf'];
    });
    $stmt_partidos->close();
}


$partidos_por_jornada = [];
$partidos_playoff = []; 

if ($es_liga) {
    $sql_jornadas = "SELECT j.id, j.numero_jornada, j.nombre,
                       p.id as partido_id, p.inicio_partido, p.estado_id, e.nombre_mostrado as estado_partido,
                       p.marcador_local, p.marcador_visitante,
                       pl.nombre_corto as local_corto, pl.url_logo as local_logo,
                       pv.nombre_corto as visitante_corto, pv.url_logo as visitante_logo
                     FROM jornadas j
                     JOIN fases f ON j.fase_id = f.id
                     LEFT JOIN partidos p ON p.jornada_id = j.id
                     LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                     LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                     LEFT JOIN estados_partido e ON p.estado_id = e.id
                     WHERE f.torneo_id = ? AND f.tipo_fase_id = 1
                     ORDER BY j.numero_jornada ASC, p.inicio_partido ASC";
    $stmt_jornadas = $conn->prepare($sql_jornadas);
    $stmt_jornadas->bind_param("i", $torneo_id);
    $stmt_jornadas->execute();
    $jornadas_res = $stmt_jornadas->get_result();
    while($row = $jornadas_res->fetch_assoc()) {
        $partidos_por_jornada[$row['numero_jornada']]['nombre'] = $row['nombre'];
        $partidos_por_jornada[$row['numero_jornada']]['partidos'][] = $row;
    }
    $stmt_jornadas->close();
}


$sql_playoffs = "SELECT 1 FROM partidos p JOIN fases f ON p.fase_id = f.id
                 WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4) LIMIT 1";
$stmt_playoffs = $conn->prepare($sql_playoffs);
$stmt_playoffs->bind_param("i", $torneo_id);
$stmt_playoffs->execute();
$partidos_playoff_res = $stmt_playoffs->get_result();
$partidos_playoff = ($partidos_playoff_res->num_rows > 0); 
$stmt_playoffs->close();


$sql_todos_partidos = "SELECT p.id, p.inicio_partido, p.estado_id, p.marcador_local, p.marcador_visitante,
                        pl.nombre_corto as local_corto, pl.url_logo as local_logo,
                        pv.nombre_corto as visitante_corto, pv.url_logo as visitante_logo,
                        e.nombre_mostrado as estado_partido,
                        f.nombre as fase_nombre, f.tipo_fase_id,
                        j.nombre as jornada_nombre
                        FROM partidos p
                        JOIN fases f ON p.fase_id = f.id
                        LEFT JOIN jornadas j ON p.jornada_id = j.id
                        LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                        LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                        LEFT JOIN estados_partido e ON p.estado_id = e.id
                        WHERE p.torneo_id = ?
                        ORDER BY p.inicio_partido ASC, f.tipo_fase_id ASC";
$stmt_todos = $conn->prepare($sql_todos_partidos);
$stmt_todos->bind_param("i", $torneo_id);
$stmt_todos->execute();
$todos_partidos_res = $stmt_todos->get_result();
$todos_partidos = $todos_partidos_res->fetch_all(MYSQLI_ASSOC);
$stmt_todos->close();


echo "<script>document.title = '" . htmlspecialchars($torneo['nombre']) . " - Portal Deportivo';</script>";
?>

<div class="container page-container">
    <div class="torneo-detalle-header" style="background-image: url('../../img/jugadores/68feb14d34109-Screenshot 2025-10-26 161932.png');">
        <div class="torneo-header-overlay">
            <span class="torneo-estado-badge <?php echo htmlspecialchars($torneo['estado_codigo']); ?>">
                <?php echo htmlspecialchars($torneo['estado']); ?>
            </span>
            <h1><?php echo htmlspecialchars($torneo['nombre']); ?></h1>
            <div class="torneo-header-info">
                <span><i class="fas fa-futbol"></i> <?php echo htmlspecialchars($torneo['deporte']); ?></span>
                <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($torneo['fecha_inicio'])); ?></span>
                <span><i class="fas fa-sitemap"></i> <?php echo $es_liga ? 'Liga + Playoffs' : 'Bracket Directo'; ?></span>
            </div>
        </div>
    </div>

    <div class="tab-navigation">
        <button class="tab-link active" data-tab="info"><i class="fas fa-info-circle"></i> Información</button>
        <?php if (!empty($todos_partidos)): ?>
            <button class="tab-link" data-tab="todos-partidos"><i class="fas fa-futbol"></i> Todos los Partidos</button>
        <?php endif; ?>
        <?php if ($es_liga && !empty($tabla)): ?>
            <button class="tab-link <?php echo ($torneo['fase_actual'] == 'liga') ? 'tab-destacado' : ''; ?>" data-tab="posiciones"><i class="fas fa-list-ol"></i> Tabla de Posiciones</button>
        <?php endif; ?>
        <?php if ($es_liga && !empty($partidos_por_jornada)): ?>
            <button class="tab-link" data-tab="partidos"><i class="fas fa-calendar-day"></i> Jornadas</button>
        <?php endif; ?>
        <?php if ($es_bracket || $partidos_playoff): ?>
             <button class="tab-link" data-tab="bracket"><i class="fas fa-sitemap"></i> Playoffs / Bracket</button>
        <?php endif; ?>
    </div>

    <div class="tab-content">
        
        <div class="tab-pane active" id="tab-info">
             <div class="info-box" style="padding: 2rem;">
                <h3 class="section-title-sub" style="text-align: left;">Descripción del Torneo</h3>
                <p><?php echo nl2br(htmlspecialchars($torneo['descripcion'] ?: 'No hay descripción disponible para este torneo.')); ?></p>
            </div>
        </div>

        <?php if (!empty($todos_partidos)): ?>
        <div class="tab-pane" id="tab-todos-partidos">
            <h2 class="section-title-sub">Todos los Partidos del Torneo</h2>
            <div class="partidos-list-user">
                <?php
                $fecha_anterior = null;
                foreach($todos_partidos as $partido):
                    $fecha_partido = date('Y-m-d', strtotime($partido['inicio_partido']));

                    
                    if ($fecha_anterior !== $fecha_partido):
                        $fecha_anterior = $fecha_partido;
                ?>
                    <div class="fecha-separador">
                        <h3><i class="fas fa-calendar"></i> <?php echo date('d \d\e F, Y', strtotime($partido['inicio_partido'])); ?></h3>
                    </div>
                <?php endif; ?>

                <div class="partido-card-user partido-completo">
                    <div class="partido-header-info">
                        <?php
                        $tipo_fase_labels = [1 => 'Liga', 2 => 'Cuartos', 3 => 'Semifinal', 4 => 'Final'];
                        $tipo_fase = $tipo_fase_labels[$partido['tipo_fase_id']] ?? 'Partido';
                        $info_fase = $partido['jornada_nombre'] ?: $tipo_fase;
                        ?>
                        <span class="fase-badge fase-tipo-<?php echo $partido['tipo_fase_id']; ?>">
                            <?php echo htmlspecialchars($info_fase); ?>
                        </span>
                        <span class="hora-partido"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($partido['inicio_partido'])); ?></span>
                    </div>

                    <div class="partido-equipos-row">
                        <div class="partido-equipo local">
                            <img src="<?php echo htmlspecialchars($partido['local_logo'] ?: '../../img/logos/default.png'); ?>" alt="Logo">
                            <span class="equipo-nombre-cal"><?php echo htmlspecialchars($partido['local_corto'] ?: 'TBD'); ?></span>
                        </div>

                        <div class="partido-marcador-user">
                            <?php if ($partido['estado_id'] == 5): ?>
                                <span class="marcador-num"><?php echo $partido['marcador_local']; ?></span>
                                <span class="marcador-sep">-</span>
                                <span class="marcador-num"><?php echo $partido['marcador_visitante']; ?></span>
                            <?php else: ?>
                                <span class="marcador-estado"><?php echo htmlspecialchars($partido['estado_partido']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="partido-equipo visitante">
                            <span class="equipo-nombre-cal"><?php echo htmlspecialchars($partido['visitante_corto'] ?: 'TBD'); ?></span>
                            <img src="<?php echo htmlspecialchars($partido['visitante_logo'] ?: '../../img/logos/default.png'); ?>" alt="Logo">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($es_liga && !empty($partidos_por_jornada)): ?>
        <div class="tab-pane" id="tab-partidos">
             <h2 class="section-title-sub">Partidos de Jornadas</h2>
             <div class="jornadas-list-user">
                <?php foreach($partidos_por_jornada as $numero => $jornada): ?>
                    <div class="jornada-grupo-user">
                        <h3><?php echo htmlspecialchars($jornada['nombre']); ?></h3>
                        <div class="partidos-list-user">
                            <?php if (empty($jornada['partidos'][0]['partido_id'])): ?>
                                <p>Partidos aún no definidos.</p>
                            <?php else: ?>
                                <?php foreach($jornada['partidos'] as $partido_j): ?>
                                <div class="partido-card-user">
                                    <div class="partido-equipo local">
                                        <span class="equipo-nombre-cal"><?php echo htmlspecialchars($partido_j['local_corto'] ?: 'TBD'); ?></span>
                                        <img src="<?php echo htmlspecialchars($partido_j['local_logo'] ?: '../../img/logos/default.png'); ?>" alt="Logo">
                                    </div>
                                    <div class="partido-marcador-user">
                                        <?php if ($partido_j['estado_id'] == 5): ?>
                                            <span class="marcador-num"><?php echo $partido_j['marcador_local']; ?></span>
                                            <span class="marcador-sep">-</span>
                                            <span class="marcador-num"><?php echo $partido_j['marcador_visitante']; ?></span>
                                        <?php else: ?>
                                            <span class="marcador-fecha"><?php echo date('d/m H:i', strtotime($partido_j['inicio_partido'])); ?></span>
                                            <span class="marcador-estado"><?php echo htmlspecialchars($partido_j['estado_partido']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="partido-equipo visitante">
                                        <img src="<?php echo htmlspecialchars($partido_j['visitante_logo'] ?: '../../img/logos/default.png'); ?>" alt="Logo">
                                        <span class="equipo-nombre-cal"><?php echo htmlspecialchars($partido_j['visitante_corto'] ?: 'TBD'); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
             </div>
        </div>
        <?php endif; ?>

        <?php if ($es_liga && !empty($tabla)): ?>
        <div class="tab-pane" id="tab-posiciones">
            <h2 class="section-title-sub">Tabla de Posiciones</h2>
            <div class="tabla-container-user">
                <table class="tabla-posiciones-user">
                    <thead>
                        <tr>
                            <th class="pos-col">#</th>
                            <th class="equipo-col">Equipo</th>
                            <th class="stat-col" title="Jugados">PJ</th>
                            <th class="stat-col" title="Ganados">PG</th>
                            <th class="stat-col" title="Empatados">PE</th>
                            <th class="stat-col" title="Perdidos">PP</th>
                            <th class="stat-col" title="Goles a Favor">GF</th>
                            <th class="stat-col" title="Goles en Contra">GC</th>
                            <th class="stat-col" title="Diferencia">DG</th>
                            <th class="pts-col" title="Puntos">Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($tabla as $index => $equipo): 
                            $posicion = $index + 1;
                            $clase_pos = '';
                            if ($posicion == 1) $clase_pos = 'lider';
                            else if ($posicion <= 8) $clase_pos = 'playoff';
                        ?>
                        <tr class="<?php echo $clase_pos; ?>">
                            <td class="pos-col"><span class="posicion-num"><?php echo $posicion; ?></span></td>
                            <td class="equipo-col">
                                <img src="<?php echo htmlspecialchars($equipo['logo'] ?: '../../img/logos/default.png'); ?>" alt="Logo">
                                <a href="plantel.php?id=<?php echo $equipo['id']; ?>" class="team-link"><?php echo htmlspecialchars($equipo['nombre']); ?></a>
                            </td>
                            <td class="stat-col"><?php echo $equipo['pj']; ?></td>
                            <td class="stat-col"><?php echo $equipo['pg']; ?></td>
                            <td class="stat-col"><?php echo $equipo['pe']; ?></td>
                            <td class="stat-col"><?php echo $equipo['pp']; ?></td>
                            <td class="stat-col"><?php echo $equipo['gf']; ?></td>
                            <td class="stat-col"><?php echo $equipo['gc']; ?></td>
                            <td class="stat-col <?php echo $equipo['dg'] > 0 ? 'dg-pos' : ($equipo['dg'] < 0 ? 'dg-neg' : ''); ?>">
                                <?php echo $equipo['dg']; ?>
                            </td>
                            <td class="pts-col"><?php echo $equipo['pts']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
             <div class="leyenda">
                <span class="leyenda-item"><span class="color-box lider"></span> Líder</span>
                <span class="leyenda-item"><span class="color-box playoff"></span> Zona de Playoffs (Top 8)</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($es_bracket || $partidos_playoff): ?>
        <div class="tab-pane" id="tab-bracket">
            <h2 class="section-title-sub">Fase de Eliminatorias</h2>
            <?php
            
            
            require_once 'includes/bracket_usuario.php';
            ?>
        </div>
        <?php endif; ?>

    </div> </div>

<script>
// Script simple para pestañas
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.dataset.tab;
            tabLinks.forEach(l => l.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const activePane = document.getElementById('tab-' + tabId);
            if (activePane) {
                activePane.classList.add('active');
            }
        });
    });
});
</script>

<?php
$conn->close();
require_once '../includes/footer.php'; 
?>