<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=ID de torneo no especificado.");
    exit;
}

$torneo_id = (int)$_GET['torneo_id'];


$stmt_torneo = $conn->prepare("SELECT t.*, d.nombre_mostrado AS deporte
                                FROM torneos t
                                JOIN deportes d ON t.deporte_id = d.id
                                WHERE t.id = ?");
$stmt_torneo->bind_param("i", $torneo_id);
$stmt_torneo->execute();
$torneo = $stmt_torneo->get_result()->fetch_assoc();

if (!$torneo) {
    header("Location: gestionar_torneos.php?error=Torneo no encontrado.");
    exit;
}

// Determinar qué fases mostrar según la configuración del torneo
$fase_inicial = $torneo['fase_actual'] ?? 'cuartos';
$mostrar_cuartos = in_array($fase_inicial, ['liga', 'cuartos']);
$mostrar_semis = in_array($fase_inicial, ['liga', 'cuartos', 'semis']);
$mostrar_final = true; // Siempre mostrar final

// Calcular cantidad de equipos necesarios según la fase inicial
$equipos_necesarios = 2; // Por defecto final
if ($fase_inicial == 'semis') {
    $equipos_necesarios = 4;
} elseif ($fase_inicial == 'cuartos') {
    $equipos_necesarios = 8;
}


$stmt_equipos = $conn->prepare("SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo
                                 FROM torneo_participantes tp
                                 JOIN participantes p ON tp.participante_id = p.id
                                 WHERE tp.torneo_id = ?
                                 ORDER BY p.nombre_mostrado");
$stmt_equipos->bind_param("i", $torneo_id);
$stmt_equipos->execute();
$equipos = $stmt_equipos->get_result();


$stmt_bracket = $conn->prepare("SELECT * FROM bracket_torneos WHERE torneo_id = ? ORDER BY fase, posicion_bracket");
$stmt_bracket->bind_param("i", $torneo_id);
$stmt_bracket->execute();
$bracket_result = $stmt_bracket->get_result();

$bracket = [];
while($b = $bracket_result->fetch_assoc()) {
    $bracket[$b['fase']][$b['posicion_bracket']] = $b;
}


$stmt_partidos = $conn->prepare("SELECT p.*, f.tipo_fase_id,
                                  pl.id AS id_local, pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                                  pv.id AS id_visitante, pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                                  f.nombre AS nombre_fase
                                  FROM partidos p
                                  JOIN fases f ON p.fase_id = f.id
                                  LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                                  LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                                  WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4)
                                  ORDER BY f.tipo_fase_id ASC, p.id ASC");
$stmt_partidos->bind_param("i", $torneo_id);
$stmt_partidos->execute();
$partidos = $stmt_partidos->get_result();

$partidos_playoff = [];
$partidos_por_fase = [
    'cuartos' => [],
    'semis' => [],
    'final' => []
];

while($partido = $partidos->fetch_assoc()) {
    $partidos_playoff[] = $partido;

    // Organizar por fase
    if ($partido['tipo_fase_id'] == 2) {
        $partidos_por_fase['cuartos'][] = $partido;
    } elseif ($partido['tipo_fase_id'] == 3) {
        $partidos_por_fase['semis'][] = $partido;
    } elseif ($partido['tipo_fase_id'] == 4) {
        $partidos_por_fase['final'][] = $partido;
    }
}

$hay_partidos_generados = count($partidos_playoff) > 0;

// Obtener el ganador del torneo si existe (partido de final finalizado)
$stmt_ganador = $conn->prepare("SELECT p.id, p.marcador_local, p.marcador_visitante,
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
$resultado_ganador = $stmt_ganador->get_result();
$ganador = $resultado_ganador->fetch_assoc();
$stmt_ganador->close();

$campeon = null;
if ($ganador) {
    // Determinar quién ganó basado en el marcador
    if ($ganador['marcador_local'] > $ganador['marcador_visitante']) {
        $campeon = [
            'id' => $ganador['id_local'],
            'nombre' => $ganador['nombre_local'],
            'corto' => $ganador['corto_local'],
            'logo' => $ganador['logo_local']
        ];
    } elseif ($ganador['marcador_visitante'] > $ganador['marcador_local']) {
        $campeon = [
            'id' => $ganador['id_visitante'],
            'nombre' => $ganador['nombre_visitante'],
            'corto' => $ganador['corto_visitante'],
            'logo' => $ganador['logo_visitante']
        ];
    }
}
?>

<main class="admin-page">
    <div class="page-header">
        <h1>Asignar Llaves - <?php echo htmlspecialchars($torneo['nombre']); ?></h1>
        <a href="gestionar_torneos.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="equipos-disponibles">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div>
                <h2 style="margin: 0;"><i class="fas fa-users"></i> Equipos Disponibles</h2>
                <p style="color: #666; margin: 0.5rem 0 0 0;">
                    Arrastra los equipos a las llaves para asignarlos.
                    <?php if (count($bracket) > 0): ?>
                        <span style="color: #28a745; font-weight: 600;">
                            <i class="fas fa-check-circle"></i> Equipos asignados guardados
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <?php if (count($bracket) == 0): ?>
                <button type="button" class="btn btn-success" onclick="generarAutomatico()" style="white-space: nowrap;">
                    <i class="fas fa-magic"></i> Generar Automático
                </button>
            <?php endif; ?>
        </div>
        <div class="equipos-grid-drag" id="equipos-disponibles-grid">
            <?php
            $equipos->data_seek(0);
            $equipos_array = [];
            while($equipo = $equipos->fetch_assoc()):
                $equipos_array[] = $equipo;
            ?>
                <div class="equipo-draggable" draggable="true" data-equipo-id="<?php echo $equipo['id']; ?>" id="equipo-<?php echo $equipo['id']; ?>">
                    <div class="equipo-logo-small">
                        <?php if ($equipo['url_logo']): ?>
                            <img src="<?php echo htmlspecialchars($equipo['url_logo']); ?>" alt="Logo">
                        <?php else: ?>
                            <i class="fas fa-shield-alt"></i>
                        <?php endif; ?>
                    </div>
                    <div class="equipo-nombre-small">
                        <strong><?php echo htmlspecialchars($equipo['nombre_corto']); ?></strong>
                        <small><?php echo htmlspecialchars($equipo['nombre_mostrado']); ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="bracket-container">
        <h2><i class="fas fa-sitemap"></i> Bracket de Playoffs</h2>

        <?php if ($fase_inicial == 'final'): ?>
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle"></i> Este es un torneo tipo <strong>Final Directa</strong>. Solo necesitas asignar 2 equipos.
                <?php if (count($bracket) > 0): ?>
                    <br><small style="font-size: 0.9em;"><i class="fas fa-save"></i> Los equipos asignados permanecen guardados después de generar los partidos.</small>
                <?php endif; ?>
            </div>
        <?php elseif ($fase_inicial == 'semis'): ?>
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle"></i> Este torneo inicia en <strong>Semifinales</strong>. Necesitas asignar 4 equipos (2 semifinales).
                <?php if (count($bracket) > 0): ?>
                    <br><small style="font-size: 0.9em;"><i class="fas fa-save"></i> Los equipos asignados permanecen guardados después de generar los partidos.</small>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <i class="fas fa-info-circle"></i> Este torneo incluye <strong>Cuartos de Final</strong>. Necesitas asignar 8 equipos.
                <?php if (count($bracket) > 0): ?>
                    <br><small style="font-size: 0.9em;"><i class="fas fa-save"></i> Los equipos asignados permanecen guardados después de generar los partidos.</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="bracket-visual">
            <?php if ($mostrar_cuartos): ?>
            <div class="bracket-round">
                <h3>Cuartos de Final</h3>
                <div class="matchups">
                    <?php for($i = 1; $i <= 4; $i++): ?>
                        <div class="matchup" data-fase="cuartos" data-posicion="<?php echo $i; ?>">
                            <div class="matchup-title">Partido <?php echo $i; ?></div>
                            <div class="matchup-team droppable" data-tipo="local">
                                <div class="team-placeholder">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Equipo Local</span>
                                </div>
                            </div>
                            <div class="vs-text">VS</div>
                            <div class="matchup-team droppable" data-tipo="visitante">
                                <div class="team-placeholder">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Equipo Visitante</span>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($mostrar_semis): ?>
            <div class="bracket-round">
                <h3>Semifinales</h3>
                <div class="matchups">
                    <?php
                    // Si el torneo comienza desde semis y no hay partidos, mostrar áreas droppables
                    $hay_semis = !empty($partidos_por_fase['semis']);
                    $semis_es_fase_inicial = ($fase_inicial == 'semis');

                    if ($hay_semis) {
                        // Hay partidos de semifinales creados - mostrarlos
                        $semis_mostradas = 0;
                        foreach ($partidos_por_fase['semis'] as $idx => $partido_semi):
                            $semis_mostradas++;
                            $posicion_semi = $semis_mostradas;
                        ?>
                            <div class="matchup" data-fase="semis" data-posicion="<?php echo $posicion_semi; ?>">
                                <div class="matchup-title">Semifinal <?php echo $posicion_semi; ?></div>

                                <!-- Equipo Local -->
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
                                    <?php else: ?>
                                        <div class="team-placeholder">
                                            <i class="fas fa-trophy"></i>
                                            <span>Ganador Cuarto <?php echo $posicion_semi == 1 ? '1' : '3'; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="vs-text">VS</div>

                                <!-- Equipo Visitante -->
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
                                    <?php else: ?>
                                        <div class="team-placeholder">
                                            <i class="fas fa-trophy"></i>
                                            <span>Ganador Cuarto <?php echo $posicion_semi == 1 ? '2' : '4'; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach;
                    } elseif ($semis_es_fase_inicial) {
                        // El torneo comienza desde semis pero aún no hay partidos - mostrar droppables
                        for($i = 1; $i <= 2; $i++): ?>
                            <div class="matchup" data-fase="semis" data-posicion="<?php echo $i; ?>">
                                <div class="matchup-title">Semifinal <?php echo $i; ?></div>
                                <div class="matchup-team droppable" data-tipo="local">
                                    <div class="team-placeholder">
                                        <i class="fas fa-plus-circle"></i>
                                        <span>Equipo Local</span>
                                    </div>
                                </div>
                                <div class="vs-text">VS</div>
                                <div class="matchup-team droppable" data-tipo="visitante">
                                    <div class="team-placeholder">
                                        <i class="fas fa-plus-circle"></i>
                                        <span>Equipo Visitante</span>
                                    </div>
                                </div>
                            </div>
                        <?php endfor;
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($mostrar_final): ?>
            <div class="bracket-round">
                <h3>Final</h3>
                <div class="matchups">
                    <?php
                    $partido_final = !empty($partidos_por_fase['final']) ? $partidos_por_fase['final'][0] : null;
                    $final_es_fase_inicial = ($fase_inicial == 'final');

                    // Si el torneo es solo final directa y no hay partido creado, mostrar droppables
                    if (!$partido_final && $final_es_fase_inicial): ?>
                        <div class="matchup matchup-final" data-fase="final" data-posicion="1">
                            <div class="matchup-title"><i class="fas fa-crown"></i> Gran Final</div>
                            <div class="matchup-team droppable" data-tipo="local">
                                <div class="team-placeholder">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Equipo Local</span>
                                </div>
                            </div>
                            <div class="vs-text">VS</div>
                            <div class="matchup-team droppable" data-tipo="visitante">
                                <div class="team-placeholder">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Equipo Visitante</span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Final con equipos avanzados o ya asignados -->
                        <div class="matchup matchup-final" data-fase="final" data-posicion="1">
                            <div class="matchup-title"><i class="fas fa-crown"></i> Gran Final</div>

                            <!-- Equipo Local -->
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
                                <?php else: ?>
                                    <div class="team-placeholder">
                                        <i class="fas fa-trophy"></i>
                                        <span><?php echo $mostrar_semis ? 'Ganador Semi 1' : 'Equipo Local'; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="vs-text">VS</div>

                            <!-- Equipo Visitante -->
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
                                <?php else: ?>
                                    <div class="team-placeholder">
                                        <i class="fas fa-trophy"></i>
                                        <span><?php echo $mostrar_semis ? 'Ganador Semi 2' : 'Equipo Visitante'; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Sección del Campeón -->
            <div class="bracket-round bracket-champion">
                <h3><i class="fas fa-trophy"></i> Campeón</h3>
                <div class="matchups">
                    <div class="champion-container">
                        <?php if ($campeon): ?>
                            <div class="champion-display">
                                <div class="champion-badge">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <div class="champion-logo">
                                    <?php if ($campeon['logo']): ?>
                                        <img src="<?php echo htmlspecialchars($campeon['logo']); ?>" alt="Logo">
                                    <?php else: ?>
                                        <i class="fas fa-shield-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="champion-info">
                                    <h4><?php echo htmlspecialchars($campeon['nombre']); ?></h4>
                                    <p><?php echo htmlspecialchars($campeon['corto']); ?></p>
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

        <div class="bracket-actions">

            <?php if ($hay_partidos_generados): ?>

                <button type="button" class="btn btn-danger btn-lg" onclick="eliminarPartidosBracket()">
                    <i class="fas fa-trash-alt"></i> Eliminar Partidos Generados
                </button>
                <div class="alert alert-warning" style="margin-top: 1rem; text-align: left;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Partidos ya generados.</strong> Para re-generar las llaves, primero debes eliminar los partidos existentes.
                    <br><small>Esto reiniciará todos los marcadores y avances de este bracket.</small>
                </div>

            <?php else: ?>

                <button type="button" class="btn btn-primary btn-lg" onclick="guardarBracket()">
                    <i class="fas fa-save"></i> Guardar y Generar Partidos
                    <?php if ($fase_inicial == 'cuartos'): ?>
                        de Cuartos
                    <?php elseif ($fase_inicial == 'semis'): ?>
                        de Semifinales
                    <?php elseif ($fase_inicial == 'final'): ?>
                        de Final
                    <?php endif; ?>
                </button>

            <?php endif; ?>

        </div>
    </div>
</main>

<link rel="stylesheet" href="../../css/asignar_llaves.css">
<script src="../../js/asignar_llaves.js"></script>
<script>
// Cargar bracket existente desde PHP
const bracketExistente = <?php echo json_encode($bracket); ?>;
const torneoId = <?php echo $torneo_id; ?>;
const faseInicial = '<?php echo $fase_inicial; ?>';
const equiposNecesarios = <?php echo $equipos_necesarios; ?>;
const equiposDisponibles = <?php echo json_encode($equipos_array); ?>;

// Inicializar el bracket cuando el DOM esté listo
window.addEventListener('DOMContentLoaded', function() {
    inicializarBracket(bracketExistente, torneoId, faseInicial, equiposNecesarios);
});
</script>

<?php
$stmt_torneo->close();
$stmt_equipos->close();
$stmt_bracket->close();
$stmt_partidos->close();
require_once 'admin_footer.php';
?>
