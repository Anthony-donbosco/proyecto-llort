<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=ID de torneo no especificado.");
    exit;
}

$torneo_id = (int)$_GET['torneo_id'];


$stmt_torneo = $conn->prepare("SELECT t.*, d.nombre_mostrado AS deporte, d.tipo_puntuacion
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

$tipo_puntuacion = $torneo['tipo_puntuacion'];
$nombre_goleador = ($tipo_puntuacion == 'goles') ? 'Goleador' : (($tipo_puntuacion == 'puntos') ? 'Máximo Anotador' : 'Mejor Jugador');


$mvp_actual = null;
if ($torneo['mvp_torneo_miembro_id']) {
    $stmt_mvp = $conn->prepare("SELECT m.*, pe.participante_id, p.nombre_mostrado AS equipo_nombre
                                FROM miembros_plantel m
                                JOIN planteles_equipo pe ON m.plantel_id = pe.id
                                JOIN participantes p ON pe.participante_id = p.id
                                WHERE m.id = ?");
    $stmt_mvp->bind_param("i", $torneo['mvp_torneo_miembro_id']);
    $stmt_mvp->execute();
    $mvp_actual = $stmt_mvp->get_result()->fetch_assoc();
    $stmt_mvp->close();
}


$goleador_actual = null;
if ($torneo['goleador_torneo_miembro_id']) {
    $stmt_gol = $conn->prepare("SELECT m.*, pe.participante_id, p.nombre_mostrado AS equipo_nombre
                                 FROM miembros_plantel m
                                 JOIN planteles_equipo pe ON m.plantel_id = pe.id
                                 JOIN participantes p ON pe.participante_id = p.id
                                 WHERE m.id = ?");
    $stmt_gol->bind_param("i", $torneo['goleador_torneo_miembro_id']);
    $stmt_gol->execute();
    $goleador_actual = $stmt_gol->get_result()->fetch_assoc();
    $stmt_gol->close();
}


$stmt_equipos = $conn->prepare("SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo
                                 FROM torneo_participantes tp
                                 JOIN participantes p ON tp.participante_id = p.id
                                 WHERE tp.torneo_id = ?
                                 ORDER BY p.nombre_mostrado");
$stmt_equipos->bind_param("i", $torneo_id);
$stmt_equipos->execute();
$equipos = $stmt_equipos->get_result();


$stmt_top_goleadores = $conn->prepare("SELECT m.id, m.nombre_jugador, m.numero_camiseta, m.posicion, m.url_foto,
                                        p.nombre_mostrado AS equipo_nombre,
                                        COUNT(ep.id) AS total_goles
                                        FROM eventos_partido ep
                                        JOIN miembros_plantel m ON ep.miembro_plantel_id = m.id
                                        JOIN planteles_equipo pe ON m.plantel_id = pe.id
                                        JOIN participantes p ON pe.participante_id = p.id
                                        JOIN partidos pa ON ep.partido_id = pa.id
                                        WHERE pa.torneo_id = ?
                                        AND ep.tipo_evento IN ('gol', 'penal_anotado', 'anotacion_1pt', 'anotacion_2pts', 'anotacion_3pts', 'triple')
                                        GROUP BY m.id
                                        ORDER BY total_goles DESC
                                        LIMIT 10");
$stmt_top_goleadores->bind_param("i", $torneo_id);
$stmt_top_goleadores->execute();
$top_goleadores = $stmt_top_goleadores->get_result();

?>

<main class="admin-page">
    <div class="page-header">
        <h1><i class="fas fa-trophy"></i> MVP y <?php echo $nombre_goleador; ?> del Torneo</h1>
        <div>
            <h3 style="margin: 0; color: #666;"><?php echo htmlspecialchars($torneo['nombre']); ?></h3>
            <a href="gestionar_torneos.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    
    <div class="mvp-section">
        <h2><i class="fas fa-star"></i> MVP del Torneo</h2>

        <div class="mvp-container">
            <?php if ($mvp_actual): ?>
                <div class="mvp-actual-display">
                    <div class="mvp-badge-large">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="mvp-foto">
                        <?php if ($mvp_actual['url_foto']): ?>
                            <img src="<?php echo htmlspecialchars($mvp_actual['url_foto']); ?>" alt="MVP">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="mvp-info-large">
                        <div class="mvp-numero">#<?php echo $mvp_actual['numero_camiseta']; ?></div>
                        <h3><?php echo htmlspecialchars($mvp_actual['nombre_jugador']); ?></h3>
                        <p class="mvp-equipo"><?php echo htmlspecialchars($mvp_actual['equipo_nombre']); ?></p>
                        <p class="mvp-posicion"><?php echo htmlspecialchars($mvp_actual['posicion']); ?></p>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="abrirModalMVP()">
                        <i class="fas fa-edit"></i> Cambiar MVP
                    </button>
                </div>
            <?php else: ?>
                <div class="mvp-vacio-display">
                    <i class="fas fa-trophy"></i>
                    <p>No se ha asignado MVP del torneo</p>
                    <button type="button" class="btn btn-primary btn-lg" onclick="abrirModalMVP()">
                        <i class="fas fa-plus"></i> Asignar MVP del Torneo
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="goleador-section">
        <h2><i class="fas fa-futbol"></i> <?php echo $nombre_goleador; ?> del Torneo</h2>

        <div class="goleador-container">
            <?php if ($goleador_actual): ?>
                <div class="goleador-actual-display">
                    <div class="goleador-badge-large">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <div class="goleador-foto">
                        <?php if ($goleador_actual['url_foto']): ?>
                            <img src="<?php echo htmlspecialchars($goleador_actual['url_foto']); ?>" alt="Goleador">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="goleador-info-large">
                        <div class="goleador-numero">#<?php echo $goleador_actual['numero_camiseta']; ?></div>
                        <h3><?php echo htmlspecialchars($goleador_actual['nombre_jugador']); ?></h3>
                        <p class="goleador-equipo"><?php echo htmlspecialchars($goleador_actual['equipo_nombre']); ?></p>
                        <p class="goleador-goles"><strong><?php echo $torneo['goles_goleador']; ?></strong> goles</p>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="abrirModalGoleador()">
                        <i class="fas fa-edit"></i> Cambiar <?php echo $nombre_goleador; ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="goleador-vacio-display">
                    <i class="fas fa-futbol"></i>
                    <p>No se ha asignado <?php echo $nombre_goleador; ?> del torneo</p>
                    <button type="button" class="btn btn-primary btn-lg" onclick="abrirModalGoleador()">
                        <i class="fas fa-plus"></i> Asignar <?php echo $nombre_goleador; ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        
        <?php if ($top_goleadores->num_rows > 0): ?>
        <div class="top-goleadores-sugerencias">
            <h3>Top Goleadores del Torneo (sugerencias)</h3>
            <div class="top-goleadores-grid">
                <?php while($gol = $top_goleadores->fetch_assoc()): ?>
                    <div class="top-goleador-item">
                        <div class="goleador-foto-small">
                            <?php if ($gol['url_foto']): ?>
                                <img src="<?php echo htmlspecialchars($gol['url_foto']); ?>" alt="Foto">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="goleador-datos">
                            <strong>#<?php echo $gol['numero_camiseta']; ?> <?php echo htmlspecialchars($gol['nombre_jugador']); ?></strong>
                            <small><?php echo htmlspecialchars($gol['equipo_nombre']); ?></small>
                            <span class="goles-badge"><?php echo $gol['total_goles']; ?> goles</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>


<div id="modalMVP" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Seleccionar MVP del Torneo</h3>
            <button type="button" class="modal-close" onclick="cerrarModalMVP()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="mvp_goleador_process.php" method="POST">
            <input type="hidden" name="action" value="asignar_mvp">
            <input type="hidden" name="torneo_id" value="<?php echo $torneo_id; ?>">

            <div class="form-group">
                <label for="equipo_mvp">Seleccione el equipo del jugador</label>
                <select id="equipo_mvp" name="equipo_id" class="form-input" onchange="cargarJugadoresMVP(this.value)" required>
                    <option value="">Seleccione un equipo</option>
                    <?php
                    $equipos->data_seek(0);
                    while($equipo = $equipos->fetch_assoc()):
                    ?>
                        <option value="<?php echo $equipo['id']; ?>">
                            <?php echo htmlspecialchars($equipo['nombre_mostrado']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="jugador_mvp_id">Jugador MVP *</label>
                <select id="jugador_mvp_id" name="jugador_id" class="form-input" required>
                    <option value="">Primero seleccione un equipo</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-trophy"></i> Asignar como MVP
                </button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModalMVP()">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>


<div id="modalGoleador" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Seleccionar <?php echo $nombre_goleador; ?> del Torneo</h3>
            <button type="button" class="modal-close" onclick="cerrarModalGoleador()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="mvp_goleador_process.php" method="POST">
            <input type="hidden" name="action" value="asignar_goleador">
            <input type="hidden" name="torneo_id" value="<?php echo $torneo_id; ?>">

            <div class="form-group">
                <label for="equipo_goleador">Seleccione el equipo del jugador</label>
                <select id="equipo_goleador" name="equipo_id" class="form-input" onchange="cargarJugadoresGoleador(this.value)" required>
                    <option value="">Seleccione un equipo</option>
                    <?php
                    $equipos->data_seek(0);
                    while($equipo = $equipos->fetch_assoc()):
                    ?>
                        <option value="<?php echo $equipo['id']; ?>">
                            <?php echo htmlspecialchars($equipo['nombre_mostrado']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="jugador_goleador_id">Jugador <?php echo $nombre_goleador; ?> *</label>
                <select id="jugador_goleador_id" name="jugador_id" class="form-input" required>
                    <option value="">Primero seleccione un equipo</option>
                </select>
            </div>

            <div class="form-group">
                <label for="total_goles">Total de Goles/Puntos *</label>
                <input type="number" id="total_goles" name="total_goles" class="form-input" min="0" required placeholder="Ej: 15">
                <small>Ingrese el total de goles o puntos anotados</small>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-futbol"></i> Asignar como <?php echo $nombre_goleador; ?>
                </button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModalGoleador()">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="../../css/mvp_goleador.css">
<script>
const torneoId = <?php echo $torneo_id; ?>;

function abrirModalMVP() {
    document.getElementById('modalMVP').style.display = 'flex';
}

function cerrarModalMVP() {
    document.getElementById('modalMVP').style.display = 'none';
    document.getElementById('equipo_mvp').value = '';
    document.getElementById('jugador_mvp_id').innerHTML = '<option value="">Primero seleccione un equipo</option>';
}

function abrirModalGoleador() {
    document.getElementById('modalGoleador').style.display = 'flex';
}

function cerrarModalGoleador() {
    document.getElementById('modalGoleador').style.display = 'none';
    document.getElementById('equipo_goleador').value = '';
    document.getElementById('jugador_goleador_id').innerHTML = '<option value="">Primero seleccione un equipo</option>';
    document.getElementById('total_goles').value = '';
}

function cargarJugadoresMVP(equipoId) {
    if (!equipoId) return;

    fetch(`../get_jugadores.php?participante_id=${equipoId}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('jugador_mvp_id');
            select.innerHTML = '<option value="">Seleccione un jugador</option>';

            data.forEach(jugador => {
                const option = document.createElement('option');
                option.value = jugador.id;
                option.textContent = `#${jugador.numero_camiseta} - ${jugador.nombre_jugador} (${jugador.posicion})`;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error:', error));
}

function cargarJugadoresGoleador(equipoId) {
    if (!equipoId) return;

    fetch(`../get_jugadores.php?participante_id=${equipoId}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('jugador_goleador_id');
            select.innerHTML = '<option value="">Seleccione un jugador</option>';

            data.forEach(jugador => {
                const option = document.createElement('option');
                option.value = jugador.id;
                option.textContent = `#${jugador.numero_camiseta} - ${jugador.nombre_jugador} (${jugador.posicion})`;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error:', error));
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalMVP')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalMVP();
});

document.getElementById('modalGoleador')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalGoleador();
});
</script>

<?php
$stmt_torneo->close();
$stmt_equipos->close();
$stmt_top_goleadores->close();
require_once 'admin_footer.php';
?>
