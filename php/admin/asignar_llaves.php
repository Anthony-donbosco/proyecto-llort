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


$stmt_partidos = $conn->prepare("SELECT p.*,
                                  pl.nombre_mostrado AS equipo_local, pl.nombre_corto AS local_corto, pl.url_logo AS logo_local,
                                  pv.nombre_mostrado AS equipo_visitante, pv.nombre_corto AS visitante_corto, pv.url_logo AS logo_visitante,
                                  f.nombre AS nombre_fase
                                  FROM partidos p
                                  LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                                  LEFT JOIN participantes pv ON p.participante_visitante_id = pv.id
                                  LEFT JOIN fases f ON p.fase_id = f.id
                                  WHERE p.torneo_id = ? AND f.tipo_fase_id IN (2, 3, 4)
                                  ORDER BY f.orden, p.id");
$stmt_partidos->bind_param("i", $torneo_id);
$stmt_partidos->execute();
$partidos = $stmt_partidos->get_result();

$partidos_playoff = [];
while($partido = $partidos->fetch_assoc()) {
    $partidos_playoff[] = $partido;
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
        <h2><i class="fas fa-users"></i> Equipos Disponibles</h2>
        <p style="color: #666; margin-bottom: 1rem;">Arrastra los equipos a las llaves para asignarlos</p>
        <div class="equipos-grid-drag">
            <?php
            $equipos->data_seek(0);
            while($equipo = $equipos->fetch_assoc()):
            ?>
                <div class="equipo-draggable" draggable="true" data-equipo-id="<?php echo $equipo['id']; ?>">
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

        <div class="bracket-visual">
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

            <div class="bracket-round">
                <h3>Semifinales</h3>
                <div class="matchups">
                    <?php for($i = 1; $i <= 2; $i++): ?>
                        <div class="matchup" data-fase="semis" data-posicion="<?php echo $i; ?>">
                            <div class="matchup-title">Semifinal <?php echo $i; ?></div>
                            <div class="matchup-team">
                                <div class="team-placeholder">
                                    <i class="fas fa-trophy"></i>
                                    <span>Ganador Cuarto <?php echo $i == 1 ? '1' : '3'; ?></span>
                                </div>
                            </div>
                            <div class="vs-text">VS</div>
                            <div class="matchup-team">
                                <div class="team-placeholder">
                                    <i class="fas fa-trophy"></i>
                                    <span>Ganador Cuarto <?php echo $i == 1 ? '2' : '4'; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="bracket-round">
                <h3>Final</h3>
                <div class="matchups">
                    <div class="matchup matchup-final" data-fase="final" data-posicion="1">
                        <div class="matchup-title"><i class="fas fa-crown"></i> Gran Final</div>
                        <div class="matchup-team">
                            <div class="team-placeholder">
                                <i class="fas fa-trophy"></i>
                                <span>Ganador Semi 1</span>
                            </div>
                        </div>
                        <div class="vs-text">VS</div>
                        <div class="matchup-team">
                            <div class="team-placeholder">
                                <i class="fas fa-trophy"></i>
                                <span>Ganador Semi 2</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bracket-actions">
            <button type="button" class="btn btn-primary btn-lg" onclick="guardarBracket()">
                <i class="fas fa-save"></i> Guardar y Generar Partidos de Cuartos
            </button>
            <button type="button" class="btn btn-secondary" onclick="limpiarBracket()">
                <i class="fas fa-eraser"></i> Limpiar Todo
            </button>
        </div>
    </div>
</main>

<style>
.equipos-disponibles {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.equipos-disponibles h2 {
    margin: 0 0 1rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.equipos-grid-drag {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.equipo-draggable {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: grab;
    transition: all 0.2s;
}

.equipo-draggable:hover {
    background: #e9ecef;
    border-color: #1a237e;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.equipo-draggable.dragging {
    opacity: 0.5;
    cursor: grabbing;
}

.equipo-logo-small {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 6px;
    flex-shrink: 0;
}

.equipo-logo-small img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.equipo-logo-small i {
    font-size: 1.5rem;
    color: #999;
}

.equipo-nombre-small {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.equipo-nombre-small strong {
    font-size: 0.95rem;
    color: #333;
}

.equipo-nombre-small small {
    font-size: 0.8rem;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bracket-container {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.bracket-container h2 {
    margin: 0 0 2rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.bracket-visual {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.bracket-round h3 {
    text-align: center;
    margin: 0 0 1.5rem 0;
    color: #1a237e;
    font-size: 1.1rem;
}

.matchups {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    justify-content: space-around;
}

.matchup {
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
}

.matchup-final {
    background: linear-gradient(135deg, #fff9e6 0%, #fff 100%);
    border-color: #ffc107;
}

.matchup-title {
    text-align: center;
    font-weight: bold;
    color: #1a237e;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e0e0e0;
}

.matchup-team {
    min-height: 60px;
    border-radius: 6px;
    transition: all 0.2s;
}

.matchup-team.droppable {
    border: 2px dashed #ccc;
    background: white;
}

.matchup-team.droppable.drag-over {
    border-color: #1a237e;
    background: #e3f2fd;
}

.team-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    color: #999;
    font-size: 0.9rem;
}

.team-placeholder i {
    font-size: 1.25rem;
}

.vs-text {
    text-align: center;
    font-weight: bold;
    color: #666;
    padding: 0.5rem 0;
}

.bracket-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    padding-top: 2rem;
    border-top: 2px solid #e0e0e0;
}

@media (max-width: 1024px) {
    .bracket-visual {
        grid-template-columns: 1fr;
    }

    .equipos-grid-drag {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}
</style>

<script>
let bracketData = {
    cuartos: {},
    semis: {},
    final: {}
};


const draggables = document.querySelectorAll('.equipo-draggable');
const droppables = document.querySelectorAll('.droppable');

draggables.forEach(draggable => {
    draggable.addEventListener('dragstart', () => {
        draggable.classList.add('dragging');
    });

    draggable.addEventListener('dragend', () => {
        draggable.classList.remove('dragging');
    });
});

droppables.forEach(droppable => {
    droppable.addEventListener('dragover', (e) => {
        e.preventDefault();
        droppable.classList.add('drag-over');
    });

    droppable.addEventListener('dragleave', () => {
        droppable.classList.remove('drag-over');
    });

    droppable.addEventListener('drop', (e) => {
        e.preventDefault();
        droppable.classList.remove('drag-over');

        const dragging = document.querySelector('.dragging');
        const equipoId = dragging.dataset.equipoId;
        const equipoHTML = dragging.innerHTML;

        const matchup = droppable.closest('.matchup');
        const fase = matchup.dataset.fase;
        const posicion = matchup.dataset.posicion;
        const tipo = droppable.dataset.tipo;

        
        droppable.innerHTML = `
            <div class="equipo-asignado">
                ${equipoHTML}
                <button type="button" class="btn-remove-team" onclick="removerEquipo(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        droppable.classList.remove('droppable');
        droppable.classList.add('has-team');

        
        if (!bracketData[fase][posicion]) {
            bracketData[fase][posicion] = {};
        }
        bracketData[fase][posicion][tipo] = equipoId;

        console.log('Bracket actualizado:', bracketData);
    });
});

function removerEquipo(btn) {
    const droppable = btn.closest('.matchup-team');
    droppable.innerHTML = `
        <div class="team-placeholder">
            <i class="fas fa-plus-circle"></i>
            <span>${droppable.dataset.tipo === 'local' ? 'Equipo Local' : 'Equipo Visitante'}</span>
        </div>
    `;
    droppable.classList.remove('has-team');
    droppable.classList.add('droppable');

    
    const matchup = droppable.closest('.matchup');
    const fase = matchup.dataset.fase;
    const posicion = matchup.dataset.posicion;
    const tipo = droppable.dataset.tipo;

    if (bracketData[fase][posicion]) {
        delete bracketData[fase][posicion][tipo];
    }

    
    setupDroppable(droppable);
}

function setupDroppable(element) {
    element.addEventListener('dragover', (e) => {
        e.preventDefault();
        element.classList.add('drag-over');
    });

    element.addEventListener('dragleave', () => {
        element.classList.remove('drag-over');
    });

    element.addEventListener('drop', (e) => {
        e.preventDefault();
        element.classList.remove('drag-over');

        const dragging = document.querySelector('.dragging');
        const equipoId = dragging.dataset.equipoId;
        const equipoHTML = dragging.innerHTML;

        const matchup = element.closest('.matchup');
        const fase = matchup.dataset.fase;
        const posicion = matchup.dataset.posicion;
        const tipo = element.dataset.tipo;

        element.innerHTML = `
            <div class="equipo-asignado">
                ${equipoHTML}
                <button type="button" class="btn-remove-team" onclick="removerEquipo(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        element.classList.remove('droppable');
        element.classList.add('has-team');

        if (!bracketData[fase][posicion]) {
            bracketData[fase][posicion] = {};
        }
        bracketData[fase][posicion][tipo] = equipoId;
    });
}

function guardarBracket() {
    
    if (Object.keys(bracketData.cuartos).length < 4) {
        alert('Debes asignar los 8 equipos en los cuartos de final (4 partidos)');
        return;
    }

    for (let i = 1; i <= 4; i++) {
        if (!bracketData.cuartos[i] || !bracketData.cuartos[i].local || !bracketData.cuartos[i].visitante) {
            alert(`Falta asignar equipos en el partido ${i} de cuartos de final`);
            return;
        }
    }

    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'playoffs_process.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'bracket_data';
    input.value = JSON.stringify(bracketData);
    form.appendChild(input);

    const inputTorneo = document.createElement('input');
    inputTorneo.type = 'hidden';
    inputTorneo.name = 'torneo_id';
    inputTorneo.value = '<?php echo $torneo_id; ?>';
    form.appendChild(inputTorneo);

    const inputAction = document.createElement('input');
    inputAction.type = 'hidden';
    inputAction.name = 'action';
    inputAction.value = 'crear_bracket';
    form.appendChild(inputAction);

    document.body.appendChild(form);
    form.submit();
}

function limpiarBracket() {
    if (confirm('¿Seguro que deseas limpiar todo el bracket?')) {
        location.reload();
    }
}
</script>

<?php
$stmt_torneo->close();
$stmt_equipos->close();
$stmt_bracket->close();
$stmt_partidos->close();
require_once 'admin_footer.php';
?>
