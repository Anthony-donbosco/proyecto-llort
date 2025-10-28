<?php




$fase_inicial = $torneo['fase_actual'] ?? 'cuartos';
$mostrar_cuartos = in_array($fase_inicial, ['liga', 'cuartos']);
$mostrar_semis = in_array($fase_inicial, ['liga', 'cuartos', 'semis']);
$mostrar_final = true; 


$stmt_partidos_bracket = $conn->prepare("SELECT p.*, f.tipo_fase_id,
                                  pl.id AS id_local, pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                                  pv.id AS id_visitante, pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                                  f.nombre AS nombre_fase
                                  FROM partidos p
                                  JOIN fases f ON p.fase_id = f.id
                                  LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                                  LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                                  WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4)
                                  ORDER BY f.tipo_fase_id ASC, p.id ASC");
$stmt_partidos_bracket->bind_param("i", $torneo_id);
$stmt_partidos_bracket->execute();
$partidos_bracket = $stmt_partidos_bracket->get_result();

$partidos_por_fase_bracket = [
    'cuartos' => [],
    'semis' => [],
    'final' => []
];

while($partido = $partidos_bracket->fetch_assoc()) {
    
    if ($partido['tipo_fase_id'] == 2) {
        $partidos_por_fase_bracket['cuartos'][] = $partido;
    } elseif ($partido['tipo_fase_id'] == 3) {
        $partidos_por_fase_bracket['semis'][] = $partido;
    } elseif ($partido['tipo_fase_id'] == 4) {
        $partidos_por_fase_bracket['final'][] = $partido;
    }
}

$hay_partidos_bracket = (count($partidos_por_fase_bracket['cuartos']) + count($partidos_por_fase_bracket['semis']) + count($partidos_por_fase_bracket['final'])) > 0;
$stmt_partidos_bracket->close();


$stmt_ganador_bracket = $conn->prepare("SELECT p.id, p.marcador_local, p.marcador_visitante,
                                 pl.id AS id_local, pl.nombre_mostrado AS nombre_local, pl.nombre_corto AS corto_local, pl.url_logo AS logo_local,
                                 pv.id AS id_visitante, pv.nombre_mostrado AS nombre_visitante, pv.nombre_corto AS corto_visitante, pv.url_logo AS logo_visitante
                                 FROM partidos p
                                 JOIN fases f ON p.fase_id = f.id
                                 LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                                 LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                                 WHERE p.torneo_id = ? AND f.tipo_fase_id = 4 AND p.estado_id = 5
                                 LIMIT 1");
$stmt_ganador_bracket->bind_param("i", $torneo_id);
$stmt_ganador_bracket->execute();
$resultado_ganador_bracket = $stmt_ganador_bracket->get_result();
$ganador_bracket = $resultado_ganador_bracket->fetch_assoc();
$stmt_ganador_bracket->close();

$campeon_bracket = null;
if ($ganador_bracket) {
    
    if ($ganador_bracket['marcador_local'] > $ganador_bracket['marcador_visitante']) {
        $campeon_bracket = [
            'id' => $ganador_bracket['id_local'],
            'nombre' => $ganador_bracket['nombre_local'],
            'corto' => $ganador_bracket['corto_local'],
            'logo' => $ganador_bracket['logo_local']
        ];
    } elseif ($ganador_bracket['marcador_visitante'] > $ganador_bracket['marcador_local']) {
        $campeon_bracket = [
            'id' => $ganador_bracket['id_visitante'],
            'nombre' => $ganador_bracket['nombre_visitante'],
            'corto' => $ganador_bracket['corto_visitante'],
            'logo' => $ganador_bracket['logo_visitante']
        ];
    }
}
?>

<div class="bracket-container">
    <?php if (!$hay_partidos_bracket): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Los partidos de eliminatorias aún no han sido generados por el administrador.
        </div>
    <?php else: ?>

        <?php if ($fase_inicial == 'final'): ?>
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle"></i> Este es un torneo tipo <strong>Final Directa</strong>.
            </div>
        <?php elseif ($fase_inicial == 'semis'): ?>
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle"></i> Este torneo inicia en <strong>Semifinales</strong>.
            </div>
        <?php else: ?>
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle"></i> Este torneo incluye <strong>Cuartos de Final</strong>.
            </div>
        <?php endif; ?>

        <div class="bracket-visual">
            <?php if ($mostrar_cuartos && !empty($partidos_por_fase_bracket['cuartos'])): ?>
            <div class="bracket-round">
                <h3>Cuartos de Final</h3>
                <div class="matchups">
                    <?php
                    $cuartos_mostrados = 0;
                    foreach ($partidos_por_fase_bracket['cuartos'] as $partido_cuarto):
                        $cuartos_mostrados++;
                    ?>
                        <div class="matchup">
                            <div class="matchup-title">Cuarto de Final <?php echo $cuartos_mostrados; ?></div>

                            
                            <div class="matchup-team <?php echo $partido_cuarto['id_local'] ? 'has-team-avanzado' : ''; ?>">
                                <?php if ($partido_cuarto['id_local']): ?>
                                    <div class="equipo-logo-small">
                                        <?php if ($partido_cuarto['logo_local']): ?>
                                            <img src="<?php echo htmlspecialchars($partido_cuarto['logo_local']); ?>" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-shield-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="equipo-nombre-small">
                                        <strong><?php echo htmlspecialchars($partido_cuarto['local_corto']); ?></strong>
                                        <small><?php echo htmlspecialchars($partido_cuarto['equipo_local']); ?></small>
                                    </div>
                                    <?php if ($partido_cuarto['estado_id'] == 5): ?>
                                        <div style="margin-left: auto; font-weight: bold; font-size: 1.1rem; color: #333;">
                                            <?php echo $partido_cuarto['marcador_local']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="team-placeholder">
                                        <i class="fas fa-trophy"></i>
                                        <span>TBD</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="vs-text">VS</div>

                            
                            <div class="matchup-team <?php echo $partido_cuarto['id_visitante'] ? 'has-team-avanzado' : ''; ?>">
                                <?php if ($partido_cuarto['id_visitante']): ?>
                                    <div class="equipo-logo-small">
                                        <?php if ($partido_cuarto['logo_visitante']): ?>
                                            <img src="<?php echo htmlspecialchars($partido_cuarto['logo_visitante']); ?>" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-shield-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="equipo-nombre-small">
                                        <strong><?php echo htmlspecialchars($partido_cuarto['visitante_corto']); ?></strong>
                                        <small><?php echo htmlspecialchars($partido_cuarto['equipo_visitante']); ?></small>
                                    </div>
                                    <?php if ($partido_cuarto['estado_id'] == 5): ?>
                                        <div style="margin-left: auto; font-weight: bold; font-size: 1.1rem; color: #333;">
                                            <?php echo $partido_cuarto['marcador_visitante']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="team-placeholder">
                                        <i class="fas fa-trophy"></i>
                                        <span>TBD</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($mostrar_semis && !empty($partidos_por_fase_bracket['semis'])): ?>
            <div class="bracket-round">
                <h3>Semifinales</h3>
                <div class="matchups">
                    <?php
                    $semis_mostradas = 0;
                    foreach ($partidos_por_fase_bracket['semis'] as $partido_semi):
                        $semis_mostradas++;
                        $posicion_semi = $semis_mostradas;
                    ?>
                        <div class="matchup">
                            <div class="matchup-title">Semifinal <?php echo $posicion_semi; ?></div>

                            
                            <div class="matchup-team <?php echo $partido_semi['id_local'] ? 'has-team-avanzado' : ''; ?>">
                                <?php if ($partido_semi['id_local']): ?>
                                    <div class="equipo-logo-small">
                                        <?php if ($partido_semi['logo_local']): ?>
                                            <img src="<?php echo htmlspecialchars($partido_semi['logo_local']); ?>" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-shield-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="equipo-nombre-small">
                                        <strong><?php echo htmlspecialchars($partido_semi['local_corto']); ?></strong>
                                        <small><?php echo htmlspecialchars($partido_semi['equipo_local']); ?></small>
                                    </div>
                                    <?php if ($partido_semi['estado_id'] == 5): ?>
                                        <div style="margin-left: auto; font-weight: bold; font-size: 1.1rem; color: #333;">
                                            <?php echo $partido_semi['marcador_local']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="team-placeholder">
                                        <i class="fas fa-trophy"></i>
                                        <span>Ganador Cuarto <?php echo $posicion_semi == 1 ? '1' : '3'; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="vs-text">VS</div>

                            
                            <div class="matchup-team <?php echo $partido_semi['id_visitante'] ? 'has-team-avanzado' : ''; ?>">
                                <?php if ($partido_semi['id_visitante']): ?>
                                    <div class="equipo-logo-small">
                                        <?php if ($partido_semi['logo_visitante']): ?>
                                            <img src="<?php echo htmlspecialchars($partido_semi['logo_visitante']); ?>" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-shield-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="equipo-nombre-small">
                                        <strong><?php echo htmlspecialchars($partido_semi['visitante_corto']); ?></strong>
                                        <small><?php echo htmlspecialchars($partido_semi['equipo_visitante']); ?></small>
                                    </div>
                                    <?php if ($partido_semi['estado_id'] == 5): ?>
                                        <div style="margin-left: auto; font-weight: bold; font-size: 1.1rem; color: #333;">
                                            <?php echo $partido_semi['marcador_visitante']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="team-placeholder">
                                        <i class="fas fa-trophy"></i>
                                        <span>Ganador Cuarto <?php echo $posicion_semi == 1 ? '2' : '4'; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($mostrar_final && !empty($partidos_por_fase_bracket['final'])): ?>
            <div class="bracket-round">
                <h3>Final</h3>
                <div class="matchups">
                    <?php
                    $partido_final = $partidos_por_fase_bracket['final'][0];
                    ?>
                    <div class="matchup matchup-final">
                        <div class="matchup-title"><i class="fas fa-crown"></i> Gran Final</div>

                        
                        <div class="matchup-team <?php echo ($partido_final && $partido_final['id_local']) ? 'has-team-avanzado' : ''; ?>">
                            <?php if ($partido_final && $partido_final['id_local']): ?>
                                <div class="equipo-logo-small">
                                    <?php if ($partido_final['logo_local']): ?>
                                        <img src="<?php echo htmlspecialchars($partido_final['logo_local']); ?>" alt="Logo">
                                    <?php else: ?>
                                        <i class="fas fa-shield-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="equipo-nombre-small">
                                    <strong><?php echo htmlspecialchars($partido_final['local_corto']); ?></strong>
                                    <small><?php echo htmlspecialchars($partido_final['equipo_local']); ?></small>
                                </div>
                                <?php if ($partido_final['estado_id'] == 5): ?>
                                    <div style="margin-left: auto; font-weight: bold; font-size: 1.1rem; color: #333;">
                                        <?php echo $partido_final['marcador_local']; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="team-placeholder">
                                    <i class="fas fa-trophy"></i>
                                    <span><?php echo $mostrar_semis ? 'Ganador Semi 1' : 'Equipo Local'; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="vs-text">VS</div>

                        
                        <div class="matchup-team <?php echo ($partido_final && $partido_final['id_visitante']) ? 'has-team-avanzado' : ''; ?>">
                            <?php if ($partido_final && $partido_final['id_visitante']): ?>
                                <div class="equipo-logo-small">
                                    <?php if ($partido_final['logo_visitante']): ?>
                                        <img src="<?php echo htmlspecialchars($partido_final['logo_visitante']); ?>" alt="Logo">
                                    <?php else: ?>
                                        <i class="fas fa-shield-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="equipo-nombre-small">
                                    <strong><?php echo htmlspecialchars($partido_final['visitante_corto']); ?></strong>
                                    <small><?php echo htmlspecialchars($partido_final['equipo_visitante']); ?></small>
                                </div>
                                <?php if ($partido_final['estado_id'] == 5): ?>
                                    <div style="margin-left: auto; font-weight: bold; font-size: 1.1rem; color: #333;">
                                        <?php echo $partido_final['marcador_visitante']; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="team-placeholder">
                                    <i class="fas fa-trophy"></i>
                                    <span><?php echo $mostrar_semis ? 'Ganador Semi 2' : 'Equipo Visitante'; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="bracket-round bracket-champion">
                <h3><i class="fas fa-trophy"></i> Campeón</h3>
                <div class="matchups">
                    <div class="champion-container">
                        <?php if ($campeon_bracket): ?>
                            <div class="champion-display">
                                <div class="champion-badge">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div class="champion-logo">
                                    <?php if ($campeon_bracket['logo']): ?>
                                        <img src="<?php echo htmlspecialchars($campeon_bracket['logo']); ?>" alt="Logo">
                                    <?php else: ?>
                                        <i class="fas fa-shield-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="champion-info">
                                    <h4><?php echo htmlspecialchars($campeon_bracket['nombre']); ?></h4>
                                    <p><?php echo htmlspecialchars($campeon_bracket['corto']); ?></p>
                                </div>
                                <div class="champion-confetti">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="champion-placeholder">
                                <i class="fas fa-trophy"></i>
                                <p>El campeón se determinará al finalizar la Gran Final</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="../../css/asignar_llaves.css">
