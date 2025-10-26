<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['partido_id'])) {
    header("Location: gestionar_torneos.php?error=ID de partido no especificado.");
    exit;
}

$partido_id = (int)$_GET['partido_id'];


$sql_partido = "SELECT p.*,
                pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                t.nombre AS nombre_torneo, t.deporte_id,
                d.nombre_mostrado AS deporte, d.codigo AS codigo_deporte,
                j.id AS jornada_id, j.numero_jornada,
                ep.nombre_mostrado AS estado_actual,
                mvp.nombre_jugador AS mvp_nombre, mvp.numero_camiseta AS mvp_numero
                FROM partidos p
                JOIN participantes pl ON p.participante_local_id = pl.id
                JOIN participantes pv ON p.participante_visitante_id = pv.id
                JOIN torneos t ON p.torneo_id = t.id
                JOIN deportes d ON t.deporte_id = d.id
                JOIN jornadas j ON p.jornada_id = j.id
                JOIN estados_partido ep ON p.estado_id = ep.id
                LEFT JOIN miembros_plantel mvp ON p.mvp_miembro_plantel_id = mvp.id
                WHERE p.id = ?";

$stmt_partido = $conn->prepare($sql_partido);
$stmt_partido->bind_param("i", $partido_id);
$stmt_partido->execute();
$partido = $stmt_partido->get_result()->fetch_assoc();

if (!$partido) {
    header("Location: gestionar_torneos.php?error=Partido no encontrado.");
    exit;
}


$stmt_estados = $conn->query("SELECT * FROM estados_partido ORDER BY orden");


$es_voleibol = ($partido['codigo_deporte'] == 'volleyball');


$sql_jugadores_local = "SELECT mp.*, pe.participante_id
                        FROM miembros_plantel mp
                        JOIN planteles_equipo pe ON mp.plantel_id = pe.id
                        WHERE pe.participante_id = ?
                        ORDER BY mp.numero_camiseta, mp.nombre_jugador";
$stmt_local = $conn->prepare($sql_jugadores_local);
$stmt_local->bind_param("i", $partido['participante_local_id']);
$stmt_local->execute();
$jugadores_local = $stmt_local->get_result();


$stmt_visitante = $conn->prepare($sql_jugadores_local);
$stmt_visitante->bind_param("i", $partido['participante_visitante_id']);
$stmt_visitante->execute();
$jugadores_visitante = $stmt_visitante->get_result();


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
    
    $es_gol_valido = in_array($evento['tipo_evento'], ['gol', 'penal_anotado']);
    $es_autogol = $evento['tipo_evento'] == 'autogol';

    if ($evento['participante_id'] == $partido['participante_local_id']) {
        $goles_local[] = $evento;
        if ($es_gol_valido) {
            $contador_goles_local++;
        } elseif ($es_autogol) {
            
            $contador_goles_visitante++;
        }
    } else {
        $goles_visitante[] = $evento;
        if ($es_gol_valido) {
            $contador_goles_visitante++;
        } elseif ($es_autogol) {
            
            $contador_goles_local++;
        }
    }
}


$marcador_local_calculado = $contador_goles_local;
$marcador_visitante_calculado = $contador_goles_visitante;

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Editar Partido</h1>
        <div>
            <a href="gestionar_partidos.php?jornada_id=<?php echo $partido['jornada_id']; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Partidos
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="partido-info-card">
        <div class="torneo-info">
            <h3><?php echo htmlspecialchars($partido['nombre_torneo']); ?></h3>
            <p>Jornada <?php echo $partido['numero_jornada']; ?> - <?php echo htmlspecialchars($partido['deporte']); ?></p>
        </div>

        <div class="equipos-display">
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
        </div>
    </div>

    
    <div class="cronometro-section">
        <h2><i class="fas fa-clock"></i> Cronómetro del Partido</h2>

        <div class="cronometro-container">
            <div class="cronometro-display">
                <div class="cronometro-tiempo" id="cronometroTiempo">00:00</div>
                <div class="cronometro-periodo" id="cronometroPeriodo">Detenido</div>
                <div class="cronometro-estado" id="cronometroEstado">
                    <span class="estado-badge estado-detenido">
                        <i class="fas fa-stop-circle"></i> Detenido
                    </span>
                </div>
            </div>

            <div class="cronometro-controles">
                <button type="button" id="btnIniciar" class="btn-cronometro btn-iniciar" onclick="iniciarCronometro()">
                    <i class="fas fa-play"></i> Iniciar Partido
                </button>
                <button type="button" id="btnPausar" class="btn-cronometro btn-pausar" onclick="pausarCronometro()" style="display:none;">
                    <i class="fas fa-pause"></i> Pausar
                </button>
                <button type="button" id="btnReanudar" class="btn-cronometro btn-reanudar" onclick="reanudarCronometro()" style="display:none;">
                    <i class="fas fa-play"></i> Reanudar
                </button>

                <div class="cronometro-opciones">
                    <button type="button" class="btn-cronometro btn-periodo" onclick="cambiarPeriodo('1er Tiempo')">
                        <i class="fas fa-step-backward"></i> 1er Tiempo
                    </button>
                    <button type="button" class="btn-cronometro btn-periodo" onclick="cambiarPeriodo('2do Tiempo')">
                        <i class="fas fa-step-forward"></i> 2do Tiempo
                    </button>
                    <button type="button" class="btn-cronometro btn-reiniciar" onclick="reiniciarCronometro()">
                        <i class="fas fa-redo"></i> Reiniciar
                    </button>
                </div>

                <div class="tiempo-agregado-control">
                    <label for="tiempoAgregado">Tiempo Agregado (min):</label>
                    <input type="number" id="tiempoAgregado" min="0" max="15" value="0" class="form-input" style="width: 80px;">
                    <button type="button" class="btn-cronometro btn-sm" onclick="agregarTiempo()">
                        <i class="fas fa-plus"></i> Aplicar
                    </button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="goleadores-section">
        <h2><i class="fas fa-futbol"></i> Goleadores del Partido</h2>

        <div class="goleadores-container">
            
            <div class="goleadores-equipo">
                <div class="goleadores-header">
                    <h3><?php echo htmlspecialchars($partido['equipo_local']); ?></h3>
                    <button type="button" class="btn btn-sm btn-success" onclick="abrirModalGol('local')">
                        <i class="fas fa-plus"></i> Agregar Gol
                    </button>
                </div>

                <div class="goles-list">
                    <?php if (count($goles_local) == 0): ?>
                        <div class="empty-goles">
                            <i class="fas fa-futbol"></i>
                            <p>Sin goles registrados</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($goles_local as $gol): ?>
                            <div class="gol-item">
                                <div class="gol-info">
                                    <div class="jugador-numero"><?php echo $gol['numero_camiseta']; ?></div>
                                    <div class="jugador-datos">
                                        <strong><?php echo htmlspecialchars($gol['nombre_jugador']); ?></strong>
                                        <small><?php echo htmlspecialchars($gol['posicion']); ?></small>
                                        <?php if (!empty($gol['asistencia_nombre'])): ?>
                                            <small class="asistencia-info">
                                                <i class="fas fa-hands-helping"></i>
                                                Asist: <?php echo htmlspecialchars($gol['asistencia_nombre']); ?> (#<?php echo $gol['asistencia_numero']; ?>)
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($gol['minuto']): ?>
                                        <span class="gol-minuto"><?php echo $gol['minuto']; ?>'</span>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn-delete-gol" onclick="eliminarGol(<?php echo $gol['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="goleadores-equipo">
                <div class="goleadores-header">
                    <h3><?php echo htmlspecialchars($partido['equipo_visitante']); ?></h3>
                    <button type="button" class="btn btn-sm btn-success" onclick="abrirModalGol('visitante')">
                        <i class="fas fa-plus"></i> Agregar Gol
                    </button>
                </div>

                <div class="goles-list">
                    <?php if (count($goles_visitante) == 0): ?>
                        <div class="empty-goles">
                            <i class="fas fa-futbol"></i>
                            <p>Sin goles registrados</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($goles_visitante as $gol): ?>
                            <div class="gol-item">
                                <div class="gol-info">
                                    <div class="jugador-numero"><?php echo $gol['numero_camiseta']; ?></div>
                                    <div class="jugador-datos">
                                        <strong><?php echo htmlspecialchars($gol['nombre_jugador']); ?></strong>
                                        <small><?php echo htmlspecialchars($gol['posicion']); ?></small>
                                        <?php if (!empty($gol['asistencia_nombre'])): ?>
                                            <small class="asistencia-info">
                                                <i class="fas fa-hands-helping"></i>
                                                Asist: <?php echo htmlspecialchars($gol['asistencia_nombre']); ?> (#<?php echo $gol['asistencia_numero']; ?>)
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($gol['minuto']): ?>
                                        <span class="gol-minuto"><?php echo $gol['minuto']; ?>'</span>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn-delete-gol" onclick="eliminarGol(<?php echo $gol['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <div class="mvp-section">
        <h2><i class="fas fa-trophy"></i> MVP del Partido</h2>

        <div class="mvp-container">
            <?php if ($partido['mvp_miembro_plantel_id']): ?>
                <div class="mvp-actual">
                    <div class="mvp-badge">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="mvp-info">
                        <div class="mvp-numero"><?php echo $partido['mvp_numero']; ?></div>
                        <div class="mvp-datos">
                            <strong><?php echo htmlspecialchars($partido['mvp_nombre']); ?></strong>
                            <small>MVP Actual</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-warning" onclick="abrirModalMVP()">
                        <i class="fas fa-edit"></i> Cambiar MVP
                    </button>
                </div>
            <?php else: ?>
                <div class="mvp-vacio">
                    <i class="fas fa-trophy"></i>
                    <p>No se ha seleccionado MVP para este partido</p>
                    <button type="button" class="btn btn-primary" onclick="abrirModalMVP()">
                        <i class="fas fa-plus"></i> Seleccionar MVP
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div id="modalMVP" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Seleccionar MVP del Partido</h3>
                <button type="button" class="modal-close" onclick="cerrarModalMVP()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formSeleccionarMVP" action="partido_process.php" method="POST">
                <input type="hidden" name="action" value="seleccionar_mvp">
                <input type="hidden" name="partido_id" value="<?php echo $partido_id; ?>">

                <div class="form-group">
                    <label>Seleccione el equipo del jugador</label>
                    <div class="equipo-tabs">
                        <button type="button" class="tab-btn active" onclick="cambiarEquipoMVP('local')">
                            <?php echo htmlspecialchars($partido['equipo_local']); ?>
                        </button>
                        <button type="button" class="tab-btn" onclick="cambiarEquipoMVP('visitante')">
                            <?php echo htmlspecialchars($partido['equipo_visitante']); ?>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mvp_jugador_id">Jugador MVP *</label>
                    <select id="mvp_jugador_id" name="mvp_jugador_id" class="form-input" required>
                        <option value="">Seleccione un jugador</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-trophy"></i> Seleccionar como MVP
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalMVP()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="modalGol" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Agregar Gol</h3>
                <button type="button" class="modal-close" onclick="cerrarModalGol()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="formAgregarGol" action="evento_partido_process.php" method="POST">
                <input type="hidden" name="action" value="agregar_gol">
                <input type="hidden" name="partido_id" value="<?php echo $partido_id; ?>">
                <input type="hidden" id="equipoTipo" name="equipo_tipo" value="">

                <div class="form-group">
                    <label for="jugador_id">Jugador (Anotador) *</label>
                    <select id="jugador_id" name="jugador_id" class="form-input" required>
                        <option value="">Seleccione un jugador</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="asistencia_id">Asistencia (Opcional)</label>
                    <select id="asistencia_id" name="asistencia_id" class="form-input">
                        <option value="">Sin asistencia</option>
                    </select>
                    <small class="form-hint">Jugador que dio el pase para el gol</small>
                </div>

                <div class="form-group">
                    <label for="minuto">Minuto</label>
                    <input type="text" id="minuto" name="minuto" class="form-input"
                           placeholder="Ej: 45, 90+2, 105+1"
                           pattern="^([0-9]{1,3}|[0-9]{1,3}\+[0-9]{1,2})$"
                           title="Formato: 45 o 90+2">
                    <small class="form-hint">
                        Formato liga: 1-90 o 90+tiempo agregado (ej: 90+2)<br>
                        Formato playoff: 1-120 o 120+tiempo agregado (ej: 105+1)
                    </small>
                </div>

                <div class="form-group">
                    <label for="tipo_evento">Tipo de Gol</label>
                    <select id="tipo_evento" name="tipo_evento" class="form-input">
                        <option value="gol">Gol Normal</option>
                        <option value="penal_anotado">Penal Anotado</option>
                        <option value="autogol">Autogol</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalGol()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form action="partido_process.php" method="POST" class="admin-form">
        <input type="hidden" name="partido_id" value="<?php echo $partido_id; ?>">
        <input type="hidden" name="jornada_id" value="<?php echo $partido['jornada_id']; ?>">
        <input type="hidden" name="action" value="editar">

        <div class="form-section">
            <h2><i class="fas fa-scoreboard"></i> Resultado del Partido</h2>

            
            <div class="marcadores-calculados">
                <div class="info-badge info-badge-primary">
                    <i class="fas fa-info-circle"></i>
                    Los marcadores se calculan automáticamente según los goles registrados arriba.
                </div>
            </div>

            <div class="marcadores-grid">
                <div class="marcador-group">
                    <label class="form-label">
                        Marcador Local
                        <small>(<?php echo htmlspecialchars($partido['local_corto']); ?>)</small>
                    </label>
                    <div class="marcador-display">
                        <div class="marcador-calculado"><?php echo $marcador_local_calculado; ?></div>
                    </div>
                    <input type="hidden" name="marcador_local" value="<?php echo $marcador_local_calculado; ?>">
                </div>

                <div class="marcador-separator">
                    <span>-</span>
                </div>

                <div class="marcador-group">
                    <label class="form-label">
                        Marcador Visitante
                        <small>(<?php echo htmlspecialchars($partido['visitante_corto']); ?>)</small>
                    </label>
                    <div class="marcador-display">
                        <div class="marcador-calculado"><?php echo $marcador_visitante_calculado; ?></div>
                    </div>
                    <input type="hidden" name="marcador_visitante" value="<?php echo $marcador_visitante_calculado; ?>">
                </div>
            </div>

            <?php if (count($goles_local) == 0 && count($goles_visitante) == 0): ?>
                <div class="marcadores-manual-option">
                    <p style="color: #666; font-size: 0.9rem; text-align: center; margin-top: 1rem;">
                        <i class="fas fa-lightbulb"></i>
                        Tip: Agrega goles individuales arriba para calcular el marcador automáticamente
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($es_voleibol): ?>
                <div class="sets-grid">
                    <div class="form-group">
                        <label class="form-label">Sets Ganados - Local</label>
                        <input type="number" name="marcador_local_sets" min="0" max="5"
                               value="<?php echo $partido['marcador_local_sets'] ?? 0; ?>"
                               class="form-input">
                        <small class="form-hint">Cantidad de sets ganados por el equipo local</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sets Ganados - Visitante</label>
                        <input type="number" name="marcador_visitante_sets" min="0" max="5"
                               value="<?php echo $partido['marcador_visitante_sets'] ?? 0; ?>"
                               class="form-input">
                        <small class="form-hint">Cantidad de sets ganados por el equipo visitante</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-section">
            <h2><i class="fas fa-info-circle"></i> Información del Partido</h2>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="estado_id">Estado del Partido *</label>
                    <select name="estado_id" id="estado_id" class="form-input" required>
                        <?php while($estado = $stmt_estados->fetch_assoc()): ?>
                            <option value="<?php echo $estado['id']; ?>"
                                    <?php echo $partido['estado_id'] == $estado['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($estado['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="form-hint">Selecciona "Finalizado" para completar el partido</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inicio_partido">Fecha y Hora del Partido *</label>
                    <input type="datetime-local" name="inicio_partido" id="inicio_partido"
                           value="<?php echo date('Y-m-d\TH:i', strtotime($partido['inicio_partido'])); ?>"
                           class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="notas">Notas / Observaciones</label>
                <textarea name="notas" id="notas" rows="4" class="form-input"><?php echo htmlspecialchars($partido['notas'] ?? ''); ?></textarea>
                <small class="form-hint">Información adicional sobre el partido (incidencias, destacados, etc.)</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="gestionar_partidos.php?jornada_id=<?php echo $partido['jornada_id']; ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</main>

<style>
.partido-info-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.torneo-info {
    text-align: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e0e0e0;
}

.torneo-info h3 {
    margin: 0 0 0.5rem 0;
    color: #1a237e;
}

.torneo-info p {
    margin: 0;
    color: #666;
    font-size: 0.95rem;
}

.equipos-display {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 2rem;
    align-items: center;
}

.equipo-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.equipo-logo-display {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 12px;
    padding: 0.5rem;
}

.equipo-logo-display img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.equipo-logo-display i {
    font-size: 3rem;
    color: #999;
}

.equipo-nombre-display {
    text-align: center;
}

.equipo-nombre-display strong {
    display: block;
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 0.25rem;
}

.equipo-nombre-display small {
    color: #666;
    font-size: 0.85rem;
}

.vs-display {
    font-size: 1.5rem;
    font-weight: bold;
    color: #1a237e;
}

.marcadores-grid {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 2rem;
    align-items: end;
    margin-bottom: 1.5rem;
}

.marcador-group {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.marcador-group .form-label {
    text-align: center;
    margin-bottom: 0.5rem;
}

.marcador-group .form-label small {
    display: block;
    font-weight: normal;
    color: #666;
    font-size: 0.85rem;
}

.marcador-input {
    width: 120px !important;
    text-align: center;
    font-size: 2rem !important;
    font-weight: bold;
    padding: 0.75rem !important;
    color: #1a237e;
}

.marcadores-calculados {
    margin-bottom: 1.5rem;
}

.info-badge {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.95rem;
}

.info-badge-primary {
    background: rgba(26, 35, 126, 0.1);
    color: #1a237e;
    border: 1px solid rgba(26, 35, 126, 0.3);
}

.info-badge i {
    font-size: 1.25rem;
}

.marcador-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.marcador-calculado {
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
    color: white;
    font-size: 3rem;
    font-weight: bold;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
}

.marcador-display small {
    color: #666;
    font-size: 0.85rem;
    font-weight: normal;
}

.marcador-separator {
    font-size: 2rem;
    font-weight: bold;
    color: #999;
    padding-bottom: 0.5rem;
}

.sets-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 1rem;
}


.cronometro-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.cronometro-section h2 {
    margin: 0 0 1.5rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cronometro-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    align-items: start;
}

.cronometro-display {
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
}

.cronometro-tiempo {
    font-size: 4rem;
    font-weight: bold;
    letter-spacing: 0.1em;
    margin-bottom: 0.5rem;
    font-family: 'Courier New', monospace;
}

.cronometro-periodo {
    font-size: 1.25rem;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.cronometro-estado {
    margin-top: 1rem;
}

.estado-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.estado-detenido {
    background: rgba(255,255,255,0.2);
}

.estado-corriendo {
    background: rgba(40,167,69,0.9);
    animation: pulse 2s ease-in-out infinite;
}

.estado-pausado {
    background: rgba(255,193,7,0.9);
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.cronometro-controles {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.btn-cronometro {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
    transition: all 0.2s;
}

.btn-cronometro:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-iniciar {
    background: #28a745;
    color: white;
    font-size: 1.1rem;
    padding: 1rem 2rem;
}

.btn-iniciar:hover {
    background: #218838;
}

.btn-pausar {
    background: #ffc107;
    color: #333;
    font-size: 1.1rem;
    padding: 1rem 2rem;
}

.btn-pausar:hover {
    background: #e0a800;
}

.btn-reanudar {
    background: #17a2b8;
    color: white;
    font-size: 1.1rem;
    padding: 1rem 2rem;
}

.btn-reanudar:hover {
    background: #138496;
}

.cronometro-opciones {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-periodo {
    background: #6c757d;
    color: white;
    flex: 1;
    min-width: 120px;
}

.btn-periodo:hover {
    background: #5a6268;
}

.btn-reiniciar {
    background: #dc3545;
    color: white;
    flex: 1;
    min-width: 120px;
}

.btn-reiniciar:hover {
    background: #c82333;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.tiempo-agregado-control {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.tiempo-agregado-control label {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
}

.tiempo-agregado-control .form-input {
    margin: 0;
}


.goleadores-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.goleadores-section h2 {
    margin: 0 0 1.5rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.goleadores-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.goleadores-equipo {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
}

.goleadores-header {
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.goleadores-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.goles-list {
    padding: 1rem;
    min-height: 150px;
}

.empty-goles {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #999;
}

.empty-goles i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.empty-goles p {
    margin: 0;
    font-size: 0.95rem;
}

.gol-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 0.5rem;
    transition: all 0.2s;
}

.gol-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.gol-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.jugador-numero {
    width: 36px;
    height: 36px;
    background: #1a237e;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.jugador-datos {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    flex: 1;
}

.jugador-datos strong {
    font-size: 0.95rem;
    color: #333;
}

.jugador-datos small {
    font-size: 0.8rem;
    color: #666;
}

.asistencia-info {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    color: #1a237e !important;
    font-weight: 500;
    margin-top: 0.25rem;
}

.asistencia-info i {
    font-size: 0.75rem;
}

.gol-minuto {
    background: #28a745;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: bold;
}

.btn-delete-gol {
    background: #dc3545;
    color: white;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.btn-delete-gol:hover {
    background: #c82333;
    transform: scale(1.1);
}


.mvp-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.mvp-section h2 {
    margin: 0 0 1.5rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mvp-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 150px;
}

.mvp-actual {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.mvp-badge {
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #ffd700;
    flex-shrink: 0;
}

.mvp-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.mvp-numero {
    width: 50px;
    height: 50px;
    background: #1a237e;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.mvp-datos {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.mvp-datos strong {
    font-size: 1.1rem;
    color: #333;
}

.mvp-datos small {
    font-size: 0.85rem;
    color: #666;
}

.mvp-vacio {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #999;
    text-align: center;
}

.mvp-vacio i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ffd700;
}

.mvp-vacio p {
    margin: 0 0 1.5rem 0;
    font-size: 0.95rem;
}

.equipo-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.tab-btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 2px solid #e0e0e0;
    background: white;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.tab-btn:hover {
    border-color: #1a237e;
    color: #1a237e;
}

.tab-btn.active {
    background: #1a237e;
    color: white;
    border-color: #1a237e;
}


.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.modal-header {
    background: #1a237e;
    color: white;
    padding: 1.5rem;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.modal-close {
    background: transparent;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    transform: scale(1.2);
}

.modal-content form {
    padding: 1.5rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e0e0e0;
}

.modal-actions .btn {
    flex: 1;
}

@media (max-width: 768px) {
    .equipos-display {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .vs-display {
        order: 2;
    }

    .marcadores-grid {
        grid-template-columns: 1fr;
    }

    .marcador-separator {
        display: none;
    }

    .sets-grid {
        grid-template-columns: 1fr;
    }

    .cronometro-container {
        grid-template-columns: 1fr;
    }

    .cronometro-tiempo {
        font-size: 3rem;
    }

    .cronometro-opciones {
        flex-direction: column;
    }

    .btn-periodo,
    .btn-reiniciar {
        width: 100%;
    }

    .tiempo-agregado-control {
        flex-wrap: wrap;
    }

    .goleadores-container {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .goleadores-header {
        flex-direction: column;
        gap: 0.75rem;
        text-align: center;
    }

    .goleadores-header .btn {
        width: 100%;
    }

    .gol-info {
        gap: 0.5rem;
    }

    .jugador-numero {
        width: 32px;
        height: 32px;
        font-size: 0.85rem;
    }
}
</style>

<script>

const jugadoresLocal = <?php echo json_encode($jugadores_local->fetch_all(MYSQLI_ASSOC)); ?>;
const jugadoresVisitante = <?php echo json_encode($jugadores_visitante->fetch_all(MYSQLI_ASSOC)); ?>;

function abrirModalGol(tipo) {
    const modal = document.getElementById('modalGol');
    const select = document.getElementById('jugador_id');
    const selectAsistencia = document.getElementById('asistencia_id');
    const equipoTipo = document.getElementById('equipoTipo');
    const modalTitle = document.getElementById('modalTitle');

    
    select.innerHTML = '<option value="">Seleccione un jugador</option>';
    selectAsistencia.innerHTML = '<option value="">Sin asistencia</option>';

    
    const jugadores = tipo === 'local' ? jugadoresLocal : jugadoresVisitante;
    const nombreEquipo = tipo === 'local' ? '<?php echo addslashes($partido['equipo_local']); ?>' : '<?php echo addslashes($partido['equipo_visitante']); ?>';

    
    jugadores.forEach(jugador => {
        const option = document.createElement('option');
        option.value = jugador.id;
        option.textContent = `#${jugador.numero_camiseta || '0'} - ${jugador.nombre_jugador} (${jugador.posicion || 'Sin posición'})`;
        select.appendChild(option);
    });

    
    jugadores.forEach(jugador => {
        const option = document.createElement('option');
        option.value = jugador.id;
        option.textContent = `#${jugador.numero_camiseta || '0'} - ${jugador.nombre_jugador} (${jugador.posicion || 'Sin posición'})`;
        selectAsistencia.appendChild(option);
    });

    equipoTipo.value = tipo;
    modalTitle.textContent = `Agregar Gol - ${nombreEquipo}`;
    modal.style.display = 'flex';
}

function cerrarModalGol() {
    const modal = document.getElementById('modalGol');
    modal.style.display = 'none';
    document.getElementById('formAgregarGol').reset();
}

function eliminarGol(eventoId) {
    if (confirm('¿Eliminar este gol?')) {
        window.location.href = `evento_partido_process.php?action=eliminar_gol&evento_id=${eventoId}&partido_id=<?php echo $partido_id; ?>`;
    }
}


document.getElementById('modalGol')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalGol();
    }
});


let equipoMVPActual = 'local';

function abrirModalMVP() {
    const modal = document.getElementById('modalMVP');
    equipoMVPActual = 'local';
    cargarJugadoresMVP('local');
    modal.style.display = 'flex';
}

function cerrarModalMVP() {
    const modal = document.getElementById('modalMVP');
    modal.style.display = 'none';
    document.getElementById('formSeleccionarMVP').reset();
}

function cambiarEquipoMVP(tipo) {
    equipoMVPActual = tipo;

    
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');

    
    cargarJugadoresMVP(tipo);
}

function cargarJugadoresMVP(tipo) {
    const select = document.getElementById('mvp_jugador_id');
    select.innerHTML = '<option value="">Seleccione un jugador</option>';

    const jugadores = tipo === 'local' ? jugadoresLocal : jugadoresVisitante;

    jugadores.forEach(jugador => {
        const option = document.createElement('option');
        option.value = jugador.id;
        option.textContent = `#${jugador.numero_camiseta || '0'} - ${jugador.nombre_jugador} (${jugador.posicion || 'Sin posición'})`;
        select.appendChild(option);
    });
}


document.getElementById('modalMVP')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalMVP();
    }
});


const PARTIDO_ID = <?php echo $partido_id; ?>;
let intervaloCronometro = null;


document.addEventListener('DOMContentLoaded', function() {
    obtenerEstadoCronometro();
    
    intervaloCronometro = setInterval(obtenerEstadoCronometro, 1000);
});

function obtenerEstadoCronometro() {
    fetch(`cronometro_process.php?action=obtener_estado&partido_id=${PARTIDO_ID}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                actualizarDisplayCronometro(data.cronometro);
            }
        })
        .catch(error => console.error('Error:', error));
}

function actualizarDisplayCronometro(cronometro) {
    const tiempoDisplay = document.getElementById('cronometroTiempo');
    const periodoDisplay = document.getElementById('cronometroPeriodo');
    const estadoDisplay = document.getElementById('cronometroEstado');
    const btnIniciar = document.getElementById('btnIniciar');
    const btnPausar = document.getElementById('btnPausar');
    const btnReanudar = document.getElementById('btnReanudar');

    
    const tiempo = formatearTiempoPartido(cronometro.tiempo_transcurrido, cronometro.tiempo_agregado);
    tiempoDisplay.textContent = tiempo;
    periodoDisplay.textContent = cronometro.periodo_actual;

    
    const estado = cronometro.estado_cronometro;
    let badgeHTML = '';

    if (estado === 'corriendo') {
        badgeHTML = '<span class="estado-badge estado-corriendo"><i class="fas fa-play-circle"></i> En Curso</span>';
        btnIniciar.style.display = 'none';
        btnPausar.style.display = 'block';
        btnReanudar.style.display = 'none';
    } else if (estado === 'pausado') {
        badgeHTML = '<span class="estado-badge estado-pausado"><i class="fas fa-pause-circle"></i> Pausado</span>';
        btnIniciar.style.display = 'none';
        btnPausar.style.display = 'none';
        btnReanudar.style.display = 'block';
    } else {
        badgeHTML = '<span class="estado-badge estado-detenido"><i class="fas fa-stop-circle"></i> Detenido</span>';
        btnIniciar.style.display = 'block';
        btnPausar.style.display = 'none';
        btnReanudar.style.display = 'none';
    }

    estadoDisplay.innerHTML = badgeHTML;
}

function formatearTiempoPartido(segundos, tiempoAgregado) {
    const minutos = Math.floor(segundos / 60);
    const segs = segundos % 60;

    
    
    if (minutos < 45) {
        
        return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
    } else if (minutos >= 45 && minutos < 90) {
        
        return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
    } else {
        
        
        const minutosExtra = minutos - 90;
        if (minutosExtra > 0) {
            return `90+${minutosExtra}'`;
        }
        return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
    }
}

function iniciarCronometro() {
    if (confirm('¿Iniciar el partido? Esto cambiará el estado del partido a "En Curso".')) {
        enviarAccionCronometro('iniciar');
    }
}

function pausarCronometro() {
    enviarAccionCronometro('pausar');
}

function reanudarCronometro() {
    enviarAccionCronometro('iniciar');
}

function cambiarPeriodo(periodo) {
    const formData = new FormData();
    formData.append('action', 'cambiar_periodo');
    formData.append('partido_id', PARTIDO_ID);
    formData.append('periodo', periodo);

    fetch('cronometro_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            obtenerEstadoCronometro();
            mostrarAlerta('Periodo cambiado a: ' + periodo, 'success');
        } else {
            mostrarAlerta('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error al cambiar periodo', 'error');
    });
}

function agregarTiempo() {
    const minutos = document.getElementById('tiempoAgregado').value;

    const formData = new FormData();
    formData.append('action', 'agregar_tiempo');
    formData.append('partido_id', PARTIDO_ID);
    formData.append('minutos', minutos);

    fetch('cronometro_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            obtenerEstadoCronometro();
            mostrarAlerta('Tiempo agregado: ' + minutos + ' minutos', 'success');
        } else {
            mostrarAlerta('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error al agregar tiempo', 'error');
    });
}

function reiniciarCronometro() {
    if (confirm('¿Reiniciar el cronómetro? Esto pondrá el tiempo en 00:00.')) {
        enviarAccionCronometro('reiniciar');
    }
}

function enviarAccionCronometro(accion) {
    const formData = new FormData();
    formData.append('action', accion);
    formData.append('partido_id', PARTIDO_ID);

    fetch('cronometro_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.text(); 
    })
    .then(text => {
        console.log('Respuesta del servidor:', text); 
        try {
            const data = JSON.parse(text);
            if (data.success) {
                obtenerEstadoCronometro();
                if (accion === 'iniciar') {
                    mostrarAlerta('Cronómetro iniciado', 'success');
                }
            } else {
                mostrarAlerta('Error: ' + (data.error || 'Error desconocido'), 'error');
            }
        } catch (e) {
            console.error('Error parsing JSON:', e);
            console.error('Texto recibido:', text);
            mostrarAlerta('Error: Respuesta inválida del servidor', 'error');
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        mostrarAlerta('Error al ejecutar acción: ' + error.message, 'error');
    });
}

function mostrarAlerta(mensaje, tipo) {
    
    const alerta = document.createElement('div');
    alerta.className = 'alert alert-' + tipo;
    alerta.textContent = mensaje;
    alerta.style.position = 'fixed';
    alerta.style.top = '80px';
    alerta.style.right = '20px';
    alerta.style.zIndex = '10000';
    alerta.style.minWidth = '300px';

    document.body.appendChild(alerta);

    setTimeout(() => {
        alerta.remove();
    }, 3000);
}
</script>

<?php
$stmt_partido->close();
$stmt_estados->close();
$stmt_local->close();
$stmt_visitante->close();
$stmt_eventos->close();
require_once 'admin_footer.php';
?>
