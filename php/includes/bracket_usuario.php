<?php





$stmt_torneo_bracket = $conn->prepare("SELECT t.fase_actual, t.nombre
                                      FROM torneos t
                                      WHERE t.id = ?");
$stmt_torneo_bracket->bind_param("i", $torneo_id);
$stmt_torneo_bracket->execute();
$torneo_bracket = $stmt_torneo_bracket->get_result()->fetch_assoc();
$stmt_torneo_bracket->close();

if (!$torneo_bracket) {
    echo "<p>Error al cargar datos del bracket.</p>";
    return;
}


$fase_inicial = $torneo_bracket['fase_actual'] ?? 'cuartos';
$mostrar_cuartos = in_array($fase_inicial, ['liga', 'cuartos']);
$mostrar_semis = in_array($fase_inicial, ['liga', 'cuartos', 'semis']);
$mostrar_final = true;


$stmt_partidos = $conn->prepare("SELECT p.*, f.tipo_fase_id, f.nombre AS nombre_fase,
                                  pl.id AS id_local, pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                                  pv.id AS id_visitante, pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                                  e.nombre_mostrado AS estado_partido
                                  FROM partidos p
                                  JOIN fases f ON p.fase_id = f.id
                                  LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                                  LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                                  LEFT JOIN estados_partido e ON p.estado_id = e.id
                                  WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4)
                                  ORDER BY f.tipo_fase_id ASC, p.id ASC");
$stmt_partidos->bind_param("i", $torneo_id);
$stmt_partidos->execute();
$partidos_res = $stmt_partidos->get_result();

$partidos_por_fase = [
    'cuartos' => [], 
    'semis' => [],   
    'final' => []    
];

while($partido = $partidos_res->fetch_assoc()) {
    if ($partido['tipo_fase_id'] == 2) $partidos_por_fase['cuartos'][] = $partido;
    elseif ($partido['tipo_fase_id'] == 3) $partidos_por_fase['semis'][] = $partido;
    elseif ($partido['tipo_fase_id'] == 4) $partidos_por_fase['final'][] = $partido;
}
$stmt_partidos->close();


$stmt_ganador = $conn->prepare("SELECT p.marcador_local, p.marcador_visitante,
                                 pl.id AS id_local, pl.nombre_mostrado AS nombre_local, pl.nombre_corto AS corto_local, pl.url_logo AS logo_local,
                                 pv.id AS id_visitante, pv.nombre_mostrado AS nombre_visitante, pv.nombre_corto AS corto_visitante, pv.url_logo AS logo_visitante
                                 FROM partidos p
                                 JOIN fases f ON p.fase_id = f.id
                                 LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                                 LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                                 WHERE p.torneo_id = ? AND f.tipo_fase_id = 4 AND p.estado_id = 5
                                 LIMIT 1");
$stmt_ganador->bind_param("i", $torneo_id);
$stmt_ganador->execute();
$ganador_res = $stmt_ganador->get_result();
$ganador = $ganador_res->fetch_assoc();
$stmt_ganador->close();

$campeon = null;
if ($ganador) {
    if ($ganador['marcador_local'] > $ganador['marcador_visitante']) $campeon = ['nombre' => $ganador['nombre_local'], 'corto' => $ganador['corto_local'], 'logo' => $ganador['logo_local']];
    elseif ($ganador['marcador_visitante'] > $ganador['marcador_local']) $campeon = ['nombre' => $ganador['nombre_visitante'], 'corto' => $ganador['corto_visitante'], 'logo' => $ganador['logo_visitante']];
}


function mostrarEquipoBracket($partido, $tipo, $placeholder) {
    $id = $partido ? $partido['id_' . $tipo] : null;
    $logo = $partido ? ($partido[$tipo . '_logo'] ?? null) : null;
    $corto = $partido ? ($partido[$tipo . '_corto'] ?? 'TBD') : 'TBD';
    $nombre = $partido ? ($partido['equipo_' . $tipo] ?? $placeholder) : $placeholder;
    $marcador = $partido ? ($partido['marcador_' . $tipo] ?? '-') : '-';
    $es_finalizado = $partido && $partido['estado_id'] == 5;
    $es_ganador = false;

    if ($es_finalizado) {
        $marcador_local = (int)$partido['marcador_local'];
        $marcador_visitante = (int)$partido['marcador_visitante'];
        if ($tipo == 'local' && $marcador_local > $marcador_visitante) $es_ganador = true;
        if ($tipo == 'visitante' && $marcador_visitante > $marcador_local) $es_ganador = true;
    }

    $logo_path = $logo ? htmlspecialchars($logo) : '../../img/logos/default.png'; 
    
    echo '<div class="matchup-team-user ' . ($id ? 'has-team' : 'is-placeholder') . ($es_ganador ? ' is-winner' : ($es_finalizado ? ' is-loser' : '')) . '">';
    echo '<img src="' . $logo_path . '" alt="Logo" class="team-logo-user">';
    echo '<span class="team-name-user">' . htmlspecialchars($nombre) . '</span>';
    if ($es_finalizado) {
         echo '<span class="team-score-user">' . $marcador . '</span>';
    }
    echo '</div>';
}
?>

<div class="bracket-wrapper-user">
    <?php if (empty($partidos_por_fase['cuartos']) && empty($partidos_por_fase['semis']) && empty($partidos_por_fase['final'])): ?>
        <div class="info-box text-center" style="padding: 2rem;">
            <p class="info-text" style="margin: 0;"><i class="fas fa-sitemap"></i> El bracket de playoffs aún no se ha generado.</p>
        </div>
    <?php else: ?>
        <div class="bracket-visual-user">
            <?php if ($mostrar_cuartos): ?>
            <div class="bracket-round-user">
                <h3 class="bracket-round-title">Cuartos de Final</h3>
                <?php for($i = 0; $i < 4; $i++): $partido = $partidos_por_fase['cuartos'][$i] ?? null; ?>
                <div class="matchup-user">
                    <?php mostrarEquipoBracket($partido, 'local', 'Equipo A'); ?>
                    <?php mostrarEquipoBracket($partido, 'visitante', 'Equipo B'); ?>
                </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php if ($mostrar_semis): ?>
            <div class="bracket-round-user">
                <h3 class="bracket-round-title">Semifinales</h3>
                <?php for($i = 0; $i < 2; $i++): $partido = $partidos_por_fase['semis'][$i] ?? null; ?>
                <div class="matchup-user">
                    <?php mostrarEquipoBracket($partido, 'local', $mostrar_cuartos ? 'Ganador QF ' . ($i*2+1) : 'Equipo A'); ?>
                    <?php mostrarEquipoBracket($partido, 'visitante', $mostrar_cuartos ? 'Ganador QF ' . ($i*2+2) : 'Equipo B'); ?>
                </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <div class="bracket-round-user">
                <h3 class="bracket-round-title">Final</h3>
                <?php $partido_f = $partidos_por_fase['final'][0] ?? null; ?>
                <div class="matchup-user matchup-final">
                    <?php mostrarEquipoBracket($partido_f, 'local', $mostrar_semis ? 'Ganador SF 1' : 'Equipo A'); ?>
                    <?php mostrarEquipoBracket($partido_f, 'visitante', $mostrar_semis ? 'Ganador SF 2' : 'Equipo B'); ?>
                </div>
            </div>

            <div class="bracket-round-user">
                <h3 class="bracket-round-title">Campeón</h3>
                <div class="champion-container-user">
                    <?php if ($campeon): ?>
                        <div class="champion-display-user">
                            <i class="fas fa-trophy"></i>
                            <img src="<?php echo htmlspecialchars($campeon['logo'] ?: '../../img/logos/default.png'); ?>" alt="Campeón">
                            <span><?php echo htmlspecialchars($campeon['nombre']); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="champion-placeholder-user">
                            <i class="fas fa-trophy"></i>
                            <span>Por definir</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>