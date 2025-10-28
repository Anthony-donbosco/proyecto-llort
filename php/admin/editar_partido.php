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
                t.nombre AS nombre_torneo, t.deporte_id, t.tipo_torneo,
                d.nombre_mostrado AS deporte, d.codigo AS codigo_deporte, d.tipo_puntuacion, d.eventos_disponibles, d.usa_cronometro, d.es_por_equipos,
                j.id AS jornada_id, j.numero_jornada,
                f.nombre AS nombre_fase,
                ep.nombre_mostrado AS estado_actual,
                mvp.nombre_jugador AS mvp_nombre, mvp.numero_camiseta AS mvp_numero
                FROM partidos p
                JOIN participantes pl ON p.participante_local_id = pl.id
                JOIN participantes pv ON p.participante_visitante_id = pv.id
                JOIN torneos t ON p.torneo_id = t.id
                JOIN deportes d ON t.deporte_id = d.id
                LEFT JOIN jornadas j ON p.jornada_id = j.id
                LEFT JOIN fases f ON p.fase_id = f.id
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

// Decodificar eventos disponibles del deporte
$eventos_disponibles = json_decode($partido['eventos_disponibles'], true) ?? [];
$tipo_puntuacion = $partido['tipo_puntuacion'];
$usa_cronometro = $partido['usa_cronometro'];
// Comparación explícita para evitar problemas de tipos
$es_deporte_individual = ((int)$partido['es_por_equipos'] === 0);
$es_ajedrez = ($partido['codigo_deporte'] === 'chess');
$es_pingpong = ($partido['codigo_deporte'] === 'table_tennis');

// Para deportes individuales, verificar si el "equipo" tiene solo 1 jugador
// Si es así, auto-seleccionar ese jugador
if ($es_deporte_individual && !$partido['jugador_local_id']) {
    $participante_id_local = $partido['participante_local_id'];

    // 1. Buscar el plantel_id para este participante
    $stmt_plantel = $conn->prepare("SELECT id FROM planteles_equipo WHERE participante_id = ? LIMIT 1");
    $stmt_plantel->bind_param("i", $participante_id_local);
    $stmt_plantel->execute();
    $plantel_result = $stmt_plantel->get_result();
    
    if ($plantel_result->num_rows == 0) {
        // No tiene plantel, CREARLO (esto debería hacerlo equipo_process.php)
        $stmt_crear_plantel = $conn->prepare("INSERT INTO planteles_equipo (participante_id) VALUES (?)");
        $stmt_crear_plantel->bind_param("i", $participante_id_local);
        $stmt_crear_plantel->execute();
        $plantel_id = $stmt_crear_plantel->insert_id;
        $stmt_crear_plantel->close();
    } else {
        $plantel_row = $plantel_result->fetch_assoc();
        $plantel_id = $plantel_row['id'];
    }
    $stmt_plantel->close();

    // 2. Buscar el miembro_plantel (el jugador)
    $stmt_miembro = $conn->prepare("SELECT id FROM miembros_plantel WHERE plantel_id = ? LIMIT 1");
    $stmt_miembro->bind_param("i", $plantel_id);
    $stmt_miembro->execute();
    $miembro_result = $stmt_miembro->get_result();
    
    $jugador_unico_id = null;

    if ($miembro_result->num_rows == 0) {
        // No existe el jugador, CREARLO
        // Usamos el nombre del "participante" (equipo) como nombre de jugador
        $nombre_jugador = $partido['equipo_local'];
        $numero_camiseta = 1; // Número por defecto
        $posicion = $es_ajedrez ? 'Jugador' : 'Jugador'; // Posición genérica

        $stmt_crear_miembro = $conn->prepare("INSERT INTO miembros_plantel (plantel_id, nombre_jugador, numero_camiseta, posicion) VALUES (?, ?, ?, ?)");
        $stmt_crear_miembro->bind_param("isss", $plantel_id, $nombre_jugador, $numero_camiseta, $posicion);
        $stmt_crear_miembro->execute();
        $jugador_unico_id = $stmt_crear_miembro->insert_id;
        $stmt_crear_miembro->close();
    } else {
        $miembro_row = $miembro_result->fetch_assoc();
        $jugador_unico_id = $miembro_row['id'];
    }
    $stmt_miembro->close();

    // 3. Auto-seleccionar este jugador en el partido
    if ($jugador_unico_id) {
        $stmt_update = $conn->prepare("UPDATE partidos SET jugador_local_id = ? WHERE id = ?");
        $stmt_update->bind_param("ii", $jugador_unico_id, $partido_id);
        $stmt_update->execute();
        $stmt_update->close();
        $partido['jugador_local_id'] = $jugador_unico_id;
    }
}

if ($es_deporte_individual && !$partido['jugador_visitante_id']) {
    $participante_id_visitante = $partido['participante_visitante_id'];

    // 1. Buscar el plantel_id
    $stmt_plantel = $conn->prepare("SELECT id FROM planteles_equipo WHERE participante_id = ? LIMIT 1");
    $stmt_plantel->bind_param("i", $participante_id_visitante);
    $stmt_plantel->execute();
    $plantel_result = $stmt_plantel->get_result();
    
    if ($plantel_result->num_rows == 0) {
        // No tiene plantel, CREARLO
        $stmt_crear_plantel = $conn->prepare("INSERT INTO planteles_equipo (participante_id) VALUES (?)");
        $stmt_crear_plantel->bind_param("i", $participante_id_visitante);
        $stmt_crear_plantel->execute();
        $plantel_id = $stmt_crear_plantel->insert_id;
        $stmt_crear_plantel->close();
    } else {
        $plantel_row = $plantel_result->fetch_assoc();
        $plantel_id = $plantel_row['id'];
    }
    $stmt_plantel->close();

    // 2. Buscar el miembro_plantel
    $stmt_miembro = $conn->prepare("SELECT id FROM miembros_plantel WHERE plantel_id = ? LIMIT 1");
    $stmt_miembro->bind_param("i", $plantel_id);
    $stmt_miembro->execute();
    $miembro_result = $stmt_miembro->get_result();
    
    $jugador_unico_id = null;

    if ($miembro_result->num_rows == 0) {
        // No existe el jugador, CREARLO
        $nombre_jugador = $partido['equipo_visitante'];
        $numero_camiseta = 1;
        $posicion = $es_ajedrez ? 'Jugador' : 'Jugador';

        $stmt_crear_miembro = $conn->prepare("INSERT INTO miembros_plantel (plantel_id, nombre_jugador, numero_camiseta, posicion) VALUES (?, ?, ?, ?)");
        $stmt_crear_miembro->bind_param("isss", $plantel_id, $nombre_jugador, $numero_camiseta, $posicion);
        $stmt_crear_miembro->execute();
        $jugador_unico_id = $stmt_crear_miembro->insert_id;
        $stmt_crear_miembro->close();
    } else {
        $miembro_row = $miembro_result->fetch_assoc();
        $jugador_unico_id = $miembro_row['id'];
    }
    $stmt_miembro->close();

    // 3. Auto-seleccionar este jugador en el partido
    if ($jugador_unico_id) {
        $stmt_update = $conn->prepare("UPDATE partidos SET jugador_visitante_id = ? WHERE id = ?");
        $stmt_update->bind_param("ii", $jugador_unico_id, $partido_id);
        $stmt_update->execute();
        $stmt_update->close();
        $partido['jugador_visitante_id'] = $jugador_unico_id;
    }
}

// Obtener jugadores seleccionados si existen
$jugador_local_seleccionado = null;
$jugador_visitante_seleccionado = null;

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

// Para ping pong: obtener puntos por set
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


// Determinar nombres según tipo de puntuación
$nombre_evento_singular = 'Evento';
$nombre_evento_plural = 'Eventos';
$icono_evento = 'fa-futbol';

switch($tipo_puntuacion) {
    case 'goles':
        $nombre_evento_singular = 'Gol';
        $nombre_evento_plural = 'Goleadores';
        $icono_evento = 'fa-futbol';
        break;
    case 'puntos':
        $nombre_evento_singular = 'Anotación';
        $nombre_evento_plural = 'Anotaciones';
        $icono_evento = 'fa-basketball-ball';
        break;
    case 'sets':
        $nombre_evento_singular = 'Set';
        $nombre_evento_plural = 'Sets';
        $icono_evento = 'fa-table-tennis';
        break;
    case 'ganador_directo':
        $nombre_evento_singular = 'Resultado';
        $nombre_evento_plural = 'Resultado';
        $icono_evento = 'fa-chess';
        break;
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
$goles_local = [];
$goles_visitante = [];
$contador_goles_local = 0;
$contador_goles_visitante = 0;
$eventos = $stmt_eventos->get_result();

$goles_local = [];
$goles_visitante = [];
$contador_goles_local = 0;
$contador_goles_visitante = 0;

$goles_local = [];
$goles_visitante = [];
$contador_goles_local = 0;
$contador_goles_visitante = 0;

// Bucle WHILE corregido
while($evento = $eventos->fetch_assoc()) {
    
    // --- Lógica de Goles (Fútbol) ---
    if ($tipo_puntuacion == 'goles') {
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
    
    // --- Lógica de Puntos (Básquetbol, etc.) ---
    } elseif ($tipo_puntuacion == 'puntos') {
        // Asumimos que $evento['valor_puntos'] contiene el valor (1, 2, 3)
        // y que no hay "autopuntos" en contra (como autogoles).
        
        $puntos = (int)($evento['valor_puntos'] ?? 0); // Obtenemos el valor de la anotación

        if ($evento['participante_id'] == $partido['participante_local_id']) {
            $goles_local[] = $evento;
            $contador_goles_local += $puntos; // Sumamos el valor, no solo 1
        } else {
            $goles_visitante[] = $evento;
            $contador_goles_visitante += $puntos; // Sumamos el valor, no solo 1
        }
        
    // --- Otra lógica (Sets, Ganador Directo, etc.) ---
    } else {
        // Para otros tipos, los eventos no suman al marcador principal (ej. Voleibol)
        // pero igual queremos que aparezcan en la lista.
        if ($evento['participante_id'] == $partido['participante_local_id']) {
            $goles_local[] = $evento;
        } else {
            $goles_visitante[] = $evento;
        }
    }
}


if ($es_pingpong) {
    // Para Ping Pong, el marcador principal son los sets ganados
    $marcador_local_calculado = $partido['sets_ganados_local'] ?? 0;
    $marcador_visitante_calculado = $partido['sets_ganados_visitante'] ?? 0;
} else {
    // Para otros deportes, usar los marcadores guardados en BD (actualizados por recalcularMarcadores)
    $marcador_local_calculado = (int)($partido['marcador_local'] ?? 0);
    $marcador_visitante_calculado = (int)($partido['marcador_visitante'] ?? 0);
}

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Editar Partido</h1>
        <div>
            <?php
            $es_bracket = ($partido['tipo_torneo'] == 'bracket');
            $url_volver = $es_bracket
                ? "gestionar_partidos.php?torneo_id=" . $partido['torneo_id']
                : "gestionar_partidos.php?jornada_id=" . $partido['jornada_id'];
            ?>
            <a href="<?php echo $url_volver; ?>" class="btn btn-secondary">
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
            <p>
                <?php if ($es_bracket && !empty($partido['nombre_fase'])): ?>
                    <i class="fas fa-trophy"></i> <?php echo htmlspecialchars($partido['nombre_fase']); ?> -
                <?php elseif (!empty($partido['numero_jornada'])): ?>
                    Jornada <?php echo $partido['numero_jornada']; ?> -
                <?php endif; ?>
                <?php echo htmlspecialchars($partido['deporte']); ?>
            </p>
        </div>

        <div class="equipos-display">
            <?php if ($es_deporte_individual): ?>
                <!-- Modo Deportes Individuales -->
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
                                <strong>#<?php echo $jugador_local_seleccionado['numero_camiseta']; ?> <?php echo htmlspecialchars($jugador_local_seleccionado['nombre_jugador']); ?></strong>
                                <small><?php echo htmlspecialchars($partido['equipo_local']); ?></small>
                            </div>
                        <?php else: ?>
                            <div class="jugador-sin-asignar">
                                <i class="fas fa-user-plus"></i>
                                <span>Seleccionar jugador local</span>
                            </div>
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
                                <strong>#<?php echo $jugador_visitante_seleccionado['numero_camiseta']; ?> <?php echo htmlspecialchars($jugador_visitante_seleccionado['nombre_jugador']); ?></strong>
                                <small><?php echo htmlspecialchars($partido['equipo_visitante']); ?></small>
                            </div>
                        <?php else: ?>
                            <div class="jugador-sin-asignar">
                                <i class="fas fa-user-plus"></i>
                                <span>Seleccionar jugador visitante</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Modo Equipos Normal -->
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

                <div class="cronometro-opciones" id="botones-periodo">
                    <?php
                    // Determinar los botones de período según el deporte
                    $codigo_deporte = $partido['codigo_deporte'];
                    $periodos = [];

                    if (in_array($codigo_deporte, ['football', 'volleyball'])) {
                        $periodos = ['1er Tiempo', '2do Tiempo'];
                    } elseif ($codigo_deporte == 'basketball') {
                        $periodos = ['1er Cuarto', '2do Cuarto', '3er Cuarto', '4to Cuarto'];
                    } elseif ($codigo_deporte == 'table_tennis') {
                        $periodos = ['Set 1', 'Set 2', 'Set 3', 'Set 4', 'Set 5'];
                    } else {
                        $periodos = ['1er Tiempo', '2do Tiempo'];
                    }

                    foreach ($periodos as $periodo):
                    ?>
                        <button type="button" class="btn-cronometro btn-periodo btn-periodo-small" onclick="cambiarPeriodo('<?php echo $periodo; ?>')">
                            <?php echo $periodo; ?>
                        </button>
                    <?php endforeach; ?>

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
    <?php if ($es_pingpong): ?>
        <!-- INTERFAZ DE PING PONG -->
        <div class="pingpong-section">
            <h2><i class="fas fa-table-tennis"></i> Control de Sets - Ping Pong</h2>

            <div class="pingpong-sets-container">
                <div class="set-tabs">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="set-tab <?php echo $i == ($partido['set_actual'] ?? 1) ? 'active' : ''; ?>" onclick="cambiarSet(<?php echo $i; ?>)" data-set="<?php echo $i; ?>">
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
                                <div class="jugador-nombre">
                                    <?php echo $jugador_local_seleccionado ? htmlspecialchars($jugador_local_seleccionado['nombre_jugador']) : 'Local'; ?>
                                </div>
                                <div class="puntos-display" id="puntos-local-set-<?php echo $set_num; ?>"><?php echo $puntos_local; ?></div>
                                <div class="puntos-botones">
                                    <button type="button" class="btn-punto btn-sumar" onclick="sumarPunto(<?php echo $set_num; ?>, 'local')" <?php echo $finalizado ? 'disabled' : ''; ?>>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" class="btn-punto btn-restar" onclick="restarPunto(<?php echo $set_num; ?>, 'local')" <?php echo $finalizado ? 'disabled' : ''; ?>>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="marcador-separador">-</div>

                            <div class="jugador-puntos jugador-visitante-puntos">
                                <div class="jugador-nombre">
                                    <?php echo $jugador_visitante_seleccionado ? htmlspecialchars($jugador_visitante_seleccionado['nombre_jugador']) : 'Visitante'; ?>
                                </div>
                                <div class="puntos-display" id="puntos-visitante-set-<?php echo $set_num; ?>"><?php echo $puntos_visitante; ?></div>
                                <div class="puntos-botones">
                                    <button type="button" class="btn-punto btn-sumar" onclick="sumarPunto(<?php echo $set_num; ?>, 'visitante')" <?php echo $finalizado ? 'disabled' : ''; ?>>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" class="btn-punto btn-restar" onclick="restarPunto(<?php echo $set_num; ?>, 'visitante')" <?php echo $finalizado ? 'disabled' : ''; ?>>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="set-acciones" style="<?php echo (!$finalizado && ($puntos_local > 0 || $puntos_visitante > 0)) ? 'display: block;' : 'display: none;'; ?>">
                            <button type="button" class="btn btn-finalizar-set" onclick="finalizarSetManual(<?php echo $set_num; ?>)">
                                <i class="fas fa-flag-checkered"></i> Finalizar Set Manualmente
                            </button>
                            <small style="color: #666; display: block; margin-top: 0.5rem; text-align: center;">
                                El set se finaliza automáticamente al llegar a 11 puntos con ventaja de 2.<br>
                                Usa este botón solo si necesitas finalizar el set antes.
                            </small>
                        </div>

                        <?php if ($finalizado): ?>
                            <div class="set-finalizado-badge">
                                <i class="fas fa-flag-checkered"></i>
                                Set finalizado - Ganador: <?php
                                    if ($ganador_set_id == $partido['jugador_local_id']) {
                                        echo $jugador_local_seleccionado ? htmlspecialchars($jugador_local_seleccionado['nombre_jugador']) : 'Jugador Local';
                                    } else {
                                        echo $jugador_visitante_seleccionado ? htmlspecialchars($jugador_visitante_seleccionado['nombre_jugador']) : 'Jugador Visitante';
                                    }
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
                        <button type="button" class="btn btn-sm btn-success" onclick="abrirModalGol('local')">
                            <i class="fas fa-plus"></i> Agregar <?php echo $nombre_evento_singular; ?>
                        </button>
                    </div>

                    <div class="goles-list">
                        <?php if (count($goles_local) == 0): ?>
                            <div class="empty-goles">
                                <i class="fas <?php echo $icono_evento; ?>"></i>
                                <p>Sin <?php echo strtolower($nombre_evento_plural); ?> registrados</p>
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
                            <i class="fas fa-plus"></i> Agregar <?php echo $nombre_evento_singular; ?>
                        </button>
                    </div>

                    <div class="goles-list">
                        <?php if (count($goles_visitante) == 0): ?>
                            <div class="empty-goles">
                                <i class="fas <?php echo $icono_evento; ?>"></i>
                                <p>Sin <?php echo strtolower($nombre_evento_plural); ?> registrados</p>
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
        <?php endif; ?>



        
    </div>

    <?php if (!$es_ajedrez): ?>
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
    <?php endif; ?>
    <?php if (!$es_ajedrez): ?>
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
    <?php endif; ?>

    <?php if (!$es_pingpong && !$es_ajedrez): ?>
        <div id="modalGol" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modalTitle">Agregar <?php echo $nombre_evento_singular; ?></h3>
                    <button type="button" class="modal-close" onclick="cerrarModalGol()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="formAgregarGol" action="evento_partido_process.php" method="POST" onsubmit="enviarFormularioEvento(event)">
                    <input type="hidden" name="action" value="agregar_gol">
                    <input type="hidden" name="partido_id" value="<?php echo $partido_id; ?>">
                    <input type="hidden" id="equipoTipo" name="equipo_tipo" value="">

                    <div class="form-group">
                        <label for="jugador_id">Jugador *</label>
                        <select id="jugador_id" name="jugador_id" class="form-input" required>
                            <option value="">Seleccione un jugador</option>
                        </select>
                    </div>

                    <?php if ($tipo_puntuacion == 'goles'): ?>
                    <!-- Asistencia solo para deportes de goles -->
                    <div class="form-group">
                        <label for="asistencia_id">Asistencia (Opcional)</label>
                        <select id="asistencia_id" name="asistencia_id" class="form-input">
                            <option value="">Sin asistencia</option>
                        </select>
                        <small class="form-hint">Jugador que dio el pase para el gol</small>
                    </div>
                    <?php endif; ?>

                    <?php if ($usa_cronometro): ?>
                    <!-- Minuto solo si el deporte usa cronómetro -->
                    <div class="form-group">
                        <label for="minuto">Minuto</label>
                        <input type="text" id="minuto" name="minuto" class="form-input"
                            placeholder="Ej: 45, 90+2, 105+1"
                            pattern="^([0-9]{1,3}|[0-9]{1,3}\+[0-9]{1,2})$"
                            title="Formato: 45 o 90+2">
                        <small class="form-hint">
                            Formato: 1-90 o 90+tiempo agregado (ej: 90+2)
                        </small>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="tipo_evento">Tipo de Evento</label>
                        <select id="tipo_evento" name="tipo_evento" class="form-input" onchange="actualizarValorPuntos()">
                            <?php foreach($eventos_disponibles as $evento): ?>
                                <option value="<?php echo htmlspecialchars($evento['tipo']); ?>"
                                        data-puntos="<?php echo $evento['puntos']; ?>">
                                    <?php echo htmlspecialchars($evento['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($tipo_puntuacion == 'puntos'): ?>
                    <!-- Campo de valor de puntos (visible solo para deportes tipo puntos) -->
                    <div class="form-group">
                        <label for="valor_puntos">Valor en Puntos</label>
                        <input type="number" id="valor_puntos" name="valor_puntos" class="form-input" min="1" max="3" value="1" readonly>
                        <small class="form-hint">Valor automático según tipo de anotación</small>
                    </div>
                    <?php else: ?>
                    <input type="hidden" id="valor_puntos" name="valor_puntos" value="1">
                    <?php endif; ?>

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
    <?php endif; ?>

    <form action="partido_process.php" method="POST" class="admin-form">
        <input type="hidden" name="partido_id" value="<?php echo $partido_id; ?>">
        <?php if (!empty($partido['jornada_id'])): ?>
            <input type="hidden" name="jornada_id" value="<?php echo $partido['jornada_id']; ?>">
        <?php endif; ?>
        <input type="hidden" name="torneo_id" value="<?php echo $partido['torneo_id']; ?>">
        <input type="hidden" name="es_bracket" value="<?php echo $es_bracket ? '1' : '0'; ?>">
        <input type="hidden" name="action" value="editar">

        <div class="form-section">
            <h2><i class="fas fa-scoreboard"></i> Resultado del Partido</h2>

            <?php if ($es_deporte_individual && $es_ajedrez): ?>
                

                <?php if ($es_ajedrez): ?>
                    <!-- Botones para Definir Ganador en Ajedrez -->
                    <div class="form-section">
                        <h2><i class="fas fa-trophy"></i> Ganador</h2>

                        <?php if ($partido['ganador_individual_id'] !== null): ?>
                            <!-- Mostrar resultado ya definido -->
                            <div class="resultado-ajedrez-definido">
                                <i class="fas fa-check-circle"></i>
                                <strong>Resultado Definido:</strong>
                                <?php
                                if ($partido['ganador_individual_id'] == 0) {
                                    echo '<span class="badge-empate">Empate / Tablas</span>';
                                } elseif ($partido['ganador_individual_id'] == $partido['jugador_local_id']) {
                                    echo '<span class="badge-ganador">Ganador: ' . htmlspecialchars($jugador_local_seleccionado['nombre_jugador'] ?? 'Local') . '</span>';
                                } elseif ($partido['ganador_individual_id'] == $partido['jugador_visitante_id']) {
                                    echo '<span class="badge-ganador">Ganador: ' . htmlspecialchars($jugador_visitante_seleccionado['nombre_jugador'] ?? 'Visitante') . '</span>';
                                }
                                ?>
                                <p class="texto-advertencia">El resultado ya fue definido. Marcador: <?php echo $partido['marcador_local'] . ' - ' . $partido['marcador_visitante']; ?></p>
                            </div>
                        <?php else: ?>
                            <!-- Botones para definir ganador -->
                            <div class="ajedrez-botones-ganador">
                                <button type="button" class="btn-ajedrez btn-ganador-local" onclick="definirGanadorAjedrez('local')">
                                    <i class="fas fa-crown"></i>
                                    <span class="nombre-jugador"><?php echo htmlspecialchars($jugador_local_seleccionado['nombre_jugador'] ?? 'Local'); ?></span>
                                    <span class="texto-accion">Ganó</span>
                                </button>

                                <button type="button" class="btn-ajedrez btn-empate" onclick="definirGanadorAjedrez('empate')">
                                    <i class="fas fa-handshake"></i>
                                    <span class="texto-accion">Empate / Tablas</span>
                                </button>

                                <button type="button" class="btn-ajedrez btn-ganador-visitante" onclick="definirGanadorAjedrez('visitante')">
                                    <i class="fas fa-crown"></i>
                                    <span class="nombre-jugador"><?php echo htmlspecialchars($jugador_visitante_seleccionado['nombre_jugador'] ?? 'Visitante'); ?></span>
                                    <span class="texto-accion">Ganó</span>
                                </button>
                            </div>
                            <small class="form-hint" style="text-align: center; display: block; margin-top: 1rem;">
                                <i class="fas fa-info-circle"></i> Al definir el ganador, el marcador se actualizará automáticamente (1-0, 0-1 o 0-0) y el partido se marcará como finalizado.
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
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
            <a href="<?php echo $url_volver; ?>" class="btn btn-secondary">
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

.btn-periodo-small {
    min-width: 90px;
    font-size: 0.85rem;
    padding: 0.6rem 0.75rem;
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

.jugador-individual-display {
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.jugador-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.jugador-foto-individual {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.jugador-foto-individual img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.jugador-foto-individual i {
    font-size: 4rem;
    color: #ccc;
}

.jugador-datos {
    text-align: center;
}

.jugador-datos strong {
    font-size: 1.25rem;
    color: #1a237e;
}

.jugador-datos small {
    color: #666;
    font-size: 0.9rem;
}

.jugador-sin-asignar {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: #999;
    padding: 2rem;
}

.jugador-sin-asignar i {
    font-size: 3rem;
}

/* Ping Pong */
.pingpong-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    margin: 2rem 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.pingpong-section h2 {
    color: #1a237e;
    margin-bottom: 1.5rem;
}

.set-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e0e0e0;
}

.set-tab {
    padding: 1rem 1.5rem;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 600;
    color: #666;
    transition: all 0.2s;
}

.set-tab:hover {
    color: #1a237e;
    background: #f5f5f5;
}

.set-tab.active {
    color: #1a237e;
    border-bottom-color: #1a237e;
    background: #f5f5f5;
}

.set-marcador {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 3rem;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.jugador-puntos {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.jugador-nombre {
    font-weight: 600;
    color: #333;
    font-size: 1.1rem;
}

.puntos-display {
    font-size: 4rem;
    font-weight: bold;
    color: #1a237e;
    font-family: 'Courier New', monospace;
    min-width: 100px;
    text-align: center;
}

.puntos-botones {
    display: flex;
    gap: 1rem;
}

.btn-punto {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    font-size: 1.5rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-sumar {
    background: #4caf50;
    color: white;
}

.btn-sumar:hover:not(:disabled) {
    background: #45a049;
    transform: scale(1.1);
}

.btn-restar {
    background: #f44336;
    color: white;
}

.btn-restar:hover:not(:disabled) {
    background: #da190b;
    transform: scale(1.1);
}

.btn-punto:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.marcador-separador {
    font-size: 3rem;
    font-weight: bold;
    color: #666;
}

.set-finalizado-badge {
    text-align: center;
    padding: 1rem;
    background: #4caf50;
    color: white;
    border-radius: 8px;
    margin-top: 1rem;
    font-weight: 600;
}

.set-acciones {
    margin-top: 1.5rem;
    text-align: center;
    padding: 1rem;
    background: #fff3e0;
    border-radius: 8px;
    border: 2px dashed #ff9800;
}

.set-acciones small {
    display: block;
    margin-top: 0.75rem;
    font-size: 0.85rem;
    line-height: 1.4;
}

.btn-finalizar-set {
    padding: 0.75rem 2rem;
    background: #ff9800;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-finalizar-set:hover {
    background: #f57c00;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.btn-finalizar-set:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-finalizar-set i {
    margin-right: 0.5rem;
}

.sets-resumen {
    margin-top: 2rem;
    padding: 1rem;
    background: #e3f2fd;
    border-radius: 8px;
    text-align: center;
}

.sets-ganados {
    font-size: 1.1rem;
    color: #1565c0;
}

.sets-ganados span {
    font-weight: bold;
    font-size: 1.3rem;
}

/* Estilos para Ajedrez - Definir Ganador */
.ajedrez-botones-ganador {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.5rem;
    margin: 1.5rem 0;
}

.btn-ajedrez {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    border: 3px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    font-size: 1.1rem;
    font-weight: 600;
    transition: all 0.3s;
    min-height: 140px;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-ajedrez i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

.btn-ajedrez .nombre-jugador {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    font-weight: 700;
}

.btn-ajedrez .texto-accion {
    font-size: 0.95rem;
    font-weight: 500;
    opacity: 0.8;
}

.btn-ganador-local {
    border-color: #4caf50;
    color: #2e7d32;
}

.btn-ganador-local:hover {
    background: #4caf50;
    color: white;
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
}

.btn-empate {
    border-color: #9e9e9e;
    color: #616161;
}

.btn-empate:hover {
    background: #9e9e9e;
    color: white;
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(158, 158, 158, 0.4);
}

.btn-ganador-visitante {
    border-color: #2196f3;
    color: #1565c0;
}

.btn-ganador-visitante:hover {
    background: #2196f3;
    color: white;
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
}

.btn-ajedrez:active {
    transform: translateY(-2px);
}

.resultado-ajedrez-definido {
    background: #e8f5e9;
    border: 2px solid #4caf50;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
}

.resultado-ajedrez-definido i {
    font-size: 3rem;
    color: #4caf50;
    margin-bottom: 1rem;
    display: block;
}

.resultado-ajedrez-definido strong {
    font-size: 1.2rem;
    display: block;
    margin-bottom: 1rem;
    color: #2e7d32;
}

.badge-ganador {
    display: inline-block;
    background: #4caf50;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0.5rem 0;
}

.badge-empate {
    display: inline-block;
    background: #9e9e9e;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0.5rem 0;
}

.texto-advertencia {
    margin-top: 1rem;
    color: #616161;
    font-size: 0.95rem;
}

@media (max-width: 768px) {
    .ajedrez-botones-ganador {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .btn-ajedrez {
        min-height: 100px;
        padding: 1.5rem 1rem;
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
    if (selectAsistencia) {
        selectAsistencia.innerHTML = '<option value="">Sin asistencia</option>';
    }

    
    const jugadores = tipo === 'local' ? jugadoresLocal : jugadoresVisitante;
    const nombreEquipo = tipo === 'local' ? '<?php echo addslashes($partido['equipo_local']); ?>' : '<?php echo addslashes($partido['equipo_visitante']); ?>';

    
    jugadores.forEach(jugador => {
        const option = document.createElement('option');
        option.value = jugador.id;
        option.textContent = `#${jugador.numero_camiseta || '0'} - ${jugador.nombre_jugador} (${jugador.posicion || 'Sin posición'})`;
        select.appendChild(option);
    });

    
    if (selectAsistencia) {
        jugadores.forEach(jugador => {
            const option = document.createElement('option');
            option.value = jugador.id;
            option.textContent = `#${jugador.numero_camiseta || '0'} - ${jugador.nombre_jugador} (${jugador.posicion || 'Sin posición'})`;
            selectAsistencia.appendChild(option);
        });
    }

    equipoTipo.value = tipo;
    modalTitle.textContent = `Agregar <?php echo $nombre_evento_singular; ?> - ${nombreEquipo}`;
    modal.style.display = 'flex';

    // Actualizar valor de puntos inicial si es necesario
    <?php if ($tipo_puntuacion == 'puntos'): ?>
    actualizarValorPuntos();
    <?php endif; ?>
}

function cerrarModalGol() {
    const modal = document.getElementById('modalGol');
    modal.style.display = 'none';
    document.getElementById('formAgregarGol').reset();
}

function eliminarGol(eventoId) {
    const tipoEventoStr = '<?php echo $tipo_puntuacion == "goles" ? " gol" : ($tipo_puntuacion == "puntos" ? " evento" : " registro"); ?>';
    if (confirm(`¿Eliminar este${tipoEventoStr}?`)) {

        const formData = new FormData();
        formData.append('action', 'eliminar_gol');
        formData.append('evento_id', eventoId);
        formData.append('partido_id', <?php echo $partido_id; ?>);
        const targetUrl = 'evento_partido_process.php'; // Ruta relativa simple
        console.log("Intentando eliminar en:", targetUrl);

        fetch(targetUrl, { // <-- Usa la variable targetUrl
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Evento eliminado exitosamente', 'success');

                // Actualizar el marcador dinámicamente ANTES de recargar
                if (data.nuevoMarcadorLocal !== undefined && data.nuevoMarcadorVisitante !== undefined) {
                    actualizarMarcadorPrincipalDisplay(data.nuevoMarcadorLocal, data.nuevoMarcadorVisitante);
                }

                // Recargar la página para actualizar la lista de goles
                location.reload();

            } else {
                mostrarAlerta('Error al eliminar evento: ' + data.error, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión al eliminar evento.', 'error');
        });
    }
}

// Función para actualizar el valor de puntos según el tipo de evento
function actualizarValorPuntos() {
    const tipoEvento = document.getElementById('tipo_evento');
    const valorPuntosInput = document.getElementById('valor_puntos');

    if (tipoEvento && valorPuntosInput) {
        const selectedOption = tipoEvento.options[tipoEvento.selectedIndex];
        const puntos = selectedOption.getAttribute('data-puntos');
        valorPuntosInput.value = puntos || 1;
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
    const codigoDeporte = '<?php echo $partido['codigo_deporte']; ?>';

    // Basketball: mostrar MM:SS simple (cuartos de 10-12 min)
    if (codigoDeporte === 'basketball') {
        return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
    }

    // Table Tennis / Ping Pong: mostrar solo puntos (no tiempo)
    if (codigoDeporte === 'table_tennis') {
        return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
    }

    // Fútbol y otros deportes con tiempos agregados
    if (codigoDeporte === 'football' || codigoDeporte === 'volleyball') {
        // Primer tiempo (0-45 min)
        if (minutos < 45) {
            return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }
        // Fin del primer tiempo con tiempo agregado
        else if (minutos >= 45 && minutos < 46) {
            if (tiempoAgregado > 0 && segs > 0) {
                return `45+${Math.ceil(segs / 60)}'`;
            }
            return `45:00`;
        }
        // Segundo tiempo (45-90 min)
        else if (minutos < 90) {
            return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }
        // Fin del segundo tiempo con tiempo agregado
        else if (minutos >= 90) {
            const minutosExtra = minutos - 90;
            if (tiempoAgregado > 0 && (minutosExtra > 0 || segs > 0)) {
                const totalExtra = minutosExtra + Math.ceil(segs / 60);
                return `90+${totalExtra}'`;
            }
            return `90:00`;
        }
    }

    // Por defecto
    return `${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
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
                    // Si hay mensaje especial sobre BYE o avance automático
                    if (data.mensaje) {
                        alert('⚠️ ' + data.mensaje + '\n\n' + (data.avance || ''));
                        // Recargar la página para reflejar los cambios
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        mostrarAlerta('Cronómetro iniciado', 'success');
                    }
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

const partidoId = <?php echo $partido_id; ?>;

function cambiarSet(setNumero) {
    // Ocultar todos los sets
    document.querySelectorAll('.set-content').forEach(content => {
        content.style.display = 'none';
    });

    // Remover clase active de todos los tabs
    document.querySelectorAll('.set-tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Mostrar set seleccionado
    document.getElementById('set-' + setNumero).style.display = 'block';
    document.querySelector(`.set-tab[data-set="${setNumero}"]`).classList.add('active');
}

function sumarPunto(setNumero, tipo) {
    fetch('pingpong_process.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=sumar_punto&partido_id=${partidoId}&set_numero=${setNumero}&tipo=${tipo}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('puntos-local-set-' + setNumero).textContent = data.puntos_local;
            document.getElementById('puntos-visitante-set-' + setNumero).textContent = data.puntos_visitante;

            const puntosL = parseInt(document.getElementById('puntos-local-set-' + setNumero).textContent);
            const puntosV = parseInt(document.getElementById('puntos-visitante-set-' + setNumero).textContent);
            const accionesSetDiv = document.querySelector(`#set-${setNumero} .set-acciones`);
            const badgeFinalizado = document.querySelector(`#set-${setNumero} .set-finalizado-badge`);

            if (accionesSetDiv && !badgeFinalizado) { // Si existe el div de acciones y el set NO está finalizado
                if (puntosL > 0 || puntosV > 0) {
                    accionesSetDiv.style.display = 'block'; // Mostrar si hay puntos
                } else {
                    accionesSetDiv.style.display = 'none'; // Ocultar si vuelve a 0-0
                }
            }
            // --- FIN DE AÑADIDO ---

            if (data.set_finalizado) {
                alert('Set ' + setNumero + ' finalizado!');
                location.reload();
            }

            if (data.sets_ganados) {
                const setsLocal = data.sets_ganados.sets_ganados_local;
                const setsVisitante = data.sets_ganados.sets_ganados_visitante;

                // Actualizar spans de resumen
                document.getElementById('sets-local').textContent = setsLocal;
                document.getElementById('sets-visitante').textContent = setsVisitante;

                // --- AÑADE ESTAS LÍNEAS ---
                // Actualizar display principal (los cuadros azules)
                // Actualizar display principal (los cuadros azules)
                document.querySelector('.marcadores-grid .marcador-group:first-child .marcador-calculado').textContent = setsLocal; // O data.sets_ganados.sets_ganados_local
                document.querySelector('.marcadores-grid .marcador-group:last-child .marcador-calculado').textContent = setsVisitante; // O data.sets_ganados.sets_ganados_visitante

                // Actualizar campos ocultos del formulario principal
                document.querySelector('form.admin-form input[name="marcador_local"]').value = setsLocal; // O data.sets_ganados.sets_ganados_local
                document.querySelector('form.admin-form input[name="marcador_visitante"]').value = setsVisitante; // O data.sets_ganados.sets_ganados_visitante
                 // --- FIN DE AÑADIDO ---
            }
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

function restarPunto(setNumero, tipo) {
    if (!confirm('¿Restar 1 punto?')) return;

    fetch('pingpong_process.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=restar_punto&partido_id=${partidoId}&set_numero=${setNumero}&tipo=${tipo}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('puntos-local-set-' + setNumero).textContent = data.puntos_local;
            document.getElementById('puntos-visitante-set-' + setNumero).textContent = data.puntos_visitante;
            const puntosL = parseInt(document.getElementById('puntos-local-set-' + setNumero).textContent);
            const puntosV = parseInt(document.getElementById('puntos-visitante-set-' + setNumero).textContent);
            const accionesSetDiv = document.querySelector(`#set-${setNumero} .set-acciones`);
            const badgeFinalizado = document.querySelector(`#set-${setNumero} .set-finalizado-badge`); // Re-chequear por si acaso

            if (accionesSetDiv && !badgeFinalizado) {
                 if (puntosL > 0 || puntosV > 0) {
                    accionesSetDiv.style.display = 'block';
                } else {
                    accionesSetDiv.style.display = 'none';
                }
            }
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

function finalizarSetManual(setNumero) {
    const puntosLocal = parseInt(document.getElementById('puntos-local-set-' + setNumero).textContent);
    const puntosVisitante = parseInt(document.getElementById('puntos-visitante-set-' + setNumero).textContent);

    if (puntosLocal === puntosVisitante) {
        alert('No se puede finalizar un set empatado. Debe haber un ganador.');
        return;
    }

    const ganador = puntosLocal > puntosVisitante ? 'Local' : 'Visitante';
    const mensaje = `¿Finalizar Set ${setNumero} con el marcador actual?\n\n` +
                    `Local: ${puntosLocal} - Visitante: ${puntosVisitante}\n` +
                    `Ganador: ${ganador}`;

    if (!confirm(mensaje)) return;

    fetch('pingpong_process.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=finalizar_set_manual&partido_id=${partidoId}&set_numero=${setNumero}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Set ' + setNumero + ' finalizado correctamente!');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al finalizar el set');
    });
}
/**
 * Actualiza el display del marcador principal y los campos ocultos.
 * @param {number} nuevoMarcadorLocal - El nuevo marcador para el equipo local.
 * @param {number} nuevoMarcadorVisitante - El nuevo marcador para el equipo visitante.
 */
function actualizarMarcadorPrincipalDisplay(nuevoMarcadorLocal, nuevoMarcadorVisitante) {
    // Actualizar display principal (los cuadros azules)
    const marcadorLocalDisplay = document.querySelector('.marcadores-grid .marcador-group:first-child .marcador-calculado');
    const marcadorVisitanteDisplay = document.querySelector('.marcadores-grid .marcador-group:last-child .marcador-calculado');
    if (marcadorLocalDisplay) marcadorLocalDisplay.textContent = nuevoMarcadorLocal;
    if (marcadorVisitanteDisplay) marcadorVisitanteDisplay.textContent = nuevoMarcadorVisitante;

    // Actualizar campos ocultos del formulario principal
    const marcadorLocalInput = document.querySelector('form.admin-form input[name="marcador_local"]');
    const marcadorVisitanteInput = document.querySelector('form.admin-form input[name="marcador_visitante"]');
    if (marcadorLocalInput) marcadorLocalInput.value = nuevoMarcadorLocal;
    if (marcadorVisitanteInput) marcadorVisitanteInput.value = nuevoMarcadorVisitante;

    console.log(`Marcador actualizado a: ${nuevoMarcadorLocal} - ${nuevoMarcadorVisitante}`);
}

function enviarFormularioEvento(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const modal = document.getElementById('modalGol');

    const targetUrl = 'evento_partido_process.php'; // Ruta relativa simple
    console.log("Intentando enviar a:", targetUrl);

    // Asegúrate que form.action apunta al archivo correcto
    // Si están en el mismo directorio, esto debería ser 'evento_partido_process.php'
    console.log("Intentando enviar a:", form.action); // Añade esto para depurar

    fetch(targetUrl, { // <-- Usa la variable targetUrl
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cerrarModalGol();
            mostrarAlerta('Evento agregado exitosamente', 'success');

            // Actualizar el marcador dinámicamente ANTES de recargar
            if (data.nuevoMarcadorLocal !== undefined && data.nuevoMarcadorVisitante !== undefined) {
                actualizarMarcadorPrincipalDisplay(data.nuevoMarcadorLocal, data.nuevoMarcadorVisitante);
            }
            
            // Recargar la página para actualizar la lista de goles
            location.reload();

        } else {
            mostrarAlerta('Error al agregar evento: ' + data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión al agregar evento.', 'error');
    });
}

// Función para definir ganador en Ajedrez
function definirGanadorAjedrez(tipoResultado) {
    const partidoId = <?php echo $partido_id; ?>;

    let mensajeConfirmacion = '';
    let nombreGanador = '';

    switch(tipoResultado) {
        case 'local':
            nombreGanador = '<?php echo addslashes($jugador_local_seleccionado['nombre_jugador'] ?? 'Local'); ?>';
            mensajeConfirmacion = `¿Confirmar victoria de ${nombreGanador}?\n\nMarcador: 1 - 0\nEl partido se marcará como finalizado.`;
            break;
        case 'visitante':
            nombreGanador = '<?php echo addslashes($jugador_visitante_seleccionado['nombre_jugador'] ?? 'Visitante'); ?>';
            mensajeConfirmacion = `¿Confirmar victoria de ${nombreGanador}?\n\nMarcador: 0 - 1\nEl partido se marcará como finalizado.`;
            break;
        case 'empate':
            mensajeConfirmacion = '¿Confirmar empate / tablas?\n\nMarcador: 0 - 0\nEl partido se marcará como finalizado.';
            break;
    }

    if (!confirm(mensajeConfirmacion)) {
        return;
    }

    // Mostrar loading
    const botones = document.querySelectorAll('.btn-ajedrez');
    botones.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
    });

    // Enviar petición AJAX
    fetch('ajedrez_process.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=definir_ganador&partido_id=${partidoId}&tipo_resultado=${tipoResultado}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar marcadores en pantalla
            if (data.marcador_local !== undefined && data.marcador_visitante !== undefined) {
                actualizarMarcadorPrincipalDisplay(data.marcador_local, data.marcador_visitante);
            }

            // Mostrar mensaje de éxito
            mostrarAlerta('Resultado guardado exitosamente', 'success');

            // Recargar página después de 1 segundo
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            mostrarAlerta('Error: ' + data.error, 'error');
            // Reactivar botones
            botones.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error de conexión al guardar el resultado', 'error');
        // Reactivar botones
        botones.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    });
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
