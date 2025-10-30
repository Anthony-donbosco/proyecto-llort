<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$is_edit = false;
$partido_id = null;
$partido = [
    'participante_local_id' => '',
    'participante_visitante_id' => '',
    'inicio_partido' => date('Y-m-d\TH:i'),
    'estado_id' => 2,
    'notas' => '',
    'deporte_id' => '' 
];
$page_title = 'Crear Partido Amistoso';

if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $partido_id = (int)$_GET['edit_id'];
    $page_title = 'Editar Partido Amistoso';

    $stmt = $conn->prepare("SELECT p.*, pl.deporte_id 
                            FROM partidos p 
                            LEFT JOIN participantes pl ON p.participante_local_id = pl.id
                            WHERE p.id = ? AND p.torneo_id IS NULL");
    $stmt->bind_param("i", $partido_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $partido = $result->fetch_assoc();
        $fecha = new DateTime($partido['inicio_partido']);
        $partido['inicio_partido'] = $fecha->format('Y-m-d\TH:i');
    } else {
        header("Location: gestionar_amistosos.php?error=Partido no encontrado.");
        exit;
    }
    $stmt->close();
}
$deportes_q = $conn->query("SELECT id, nombre_mostrado, es_por_equipos FROM deportes ORDER BY nombre_mostrado");
$deportes = $deportes_q->fetch_all(MYSQLI_ASSOC);
$deportes_json = json_encode($deportes);

$participantes_q = $conn->query("SELECT id, nombre_mostrado, nombre_corto, deporte_id, tipo_participante_id FROM participantes ORDER BY nombre_mostrado");
$todos_participantes = $participantes_q->fetch_all(MYSQLI_ASSOC);
$todos_participantes_json = json_encode($todos_participantes);

$estados = $conn->query("SELECT id, nombre_mostrado FROM estados_partido ORDER BY orden");

?>

<main class="admin-page">
    <div class="page-header">
        <h1><?php echo $page_title; ?></h1>
        <a href="gestionar_amistosos.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a la lista
        </a>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <div class="form-container-admin">
        <form action="amistoso_process.php" method="POST" class="admin-form">
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="partido_id" value="<?php echo $partido_id; ?>">
            <?php endif; ?>

            <div class="form-section">
                <h3><i class="fas fa-basketball-ball"></i> 1. Deporte</h3>
                <div class="form-group">
                    <label for="deporte_id_filtro">Seleccione un Deporte: *</label>
                    <select id="deporte_id_filtro" name="deporte_id_filtro" required onchange="filterParticipants(this.value)">
                        <option value="">-- Primero seleccione un deporte --</option>
                        <?php foreach($deportes as $deporte): ?>
                            <option value="<?php echo $deporte['id']; ?>" <?php echo ($deporte['id'] == $partido['deporte_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deporte['nombre_mostrado']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                     <small>Esto filtrará los equipos/participantes disponibles para este deporte.</small>
                </div>
            </div>

            <div class="form-section" id="section-participantes" style="<?php echo $is_edit ? 'display:block;' : 'display:none;'; ?>">
                <h3 id="section-title-participantes"><i class="fas fa-users"></i> 2. Participantes</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="participante_local_id" id="label_local">Participante Local: *</label>
                        <select id="participante_local_id" name="participante_local_id" required>
                            <option value="">-- Seleccione --</option>
                            <?php if ($is_edit):
                                foreach($todos_participantes as $p):
                                    if ($p['deporte_id'] == $partido['deporte_id']):
                            ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $partido['participante_local_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['nombre_mostrado']); ?>
                                </option>
                            <?php 
                                    endif;
                                endforeach;
                            endif; 
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="participante_visitante_id" id="label_visitante">Participante Visitante: *</label>
                        <select id="participante_visitante_id" name="participante_visitante_id" required>
                             <option value="">-- Seleccione --</option>
                             <?php if ($is_edit):
                                foreach($todos_participantes as $p):
                                    if ($p['deporte_id'] == $partido['deporte_id']):
                            ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $partido['participante_visitante_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['nombre_mostrado']); ?>
                                </option>
                            <?php 
                                    endif;
                                endforeach;
                            endif; 
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-calendar-alt"></i> 3. Fecha y Estado</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="inicio_partido">Fecha y Hora del Partido: *</label>
                        <input type="datetime-local"
                               id="inicio_partido"
                               name="inicio_partido"
                               value="<?php echo $partido['inicio_partido']; ?>"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="estado_id">Estado:</label>
                        <select id="estado_id" name="estado_id" required>
                            <?php while($estado = $estados->fetch_assoc()): ?>
                                <option value="<?php echo $estado['id']; ?>"
                                        <?php echo ($estado['id'] == $partido['estado_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($estado['nombre_mostrado']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small>Por defecto: Programado</small>
                    </div>
                </div>
                 <div class="form-group">
                    <label for="notas">Notas:</label>
                    <textarea id="notas" name="notas" rows="3" placeholder="Notas adicionales sobre el partido..."><?php echo htmlspecialchars($partido['notas'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar Partido' : 'Crear Partido'; ?>
                </button>
                <a href="gestionar_amistosos.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<style>
.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.form-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 1.1em;
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-section h3 i {
    color: #1565c0;
}
</style>

<script>
const allParticipants = <?php echo $todos_participantes_json; ?>;
const allSports = <?php echo $deportes_json; ?>;

function filterParticipants(selectedDeporteId) {
    const localSelect = document.getElementById('participante_local_id');
    const visitanteSelect = document.getElementById('participante_visitante_id');
    const sectionParticipantes = document.getElementById('section-participantes');
    
    localSelect.innerHTML = '<option value="">-- Seleccione --</option>';
    visitanteSelect.innerHTML = '<option value="">-- Seleccione --</option>';

    if (!selectedDeporteId) {
        sectionParticipantes.style.display = 'none';
        return;
    }
    
    sectionParticipantes.style.display = 'block';
    
    const sport = allSports.find(s => s.id == selectedDeporteId);
    const targetTipoParticipante = (sport && sport.es_por_equipos == "0") ? 2 : 1; 
    const label = (targetTipoParticipante == 1) ? 'Equipo' : 'Participante';

    document.getElementById('label_local').textContent = `${label} Local: *`;
    document.getElementById('label_visitante').textContent = `${label} Visitante: *`;
    document.getElementById('section-title-participantes').innerHTML = `<i class="fas fa-users"></i> 2. ${label}s`;

    allParticipants.forEach(p => {
        if (p.deporte_id == selectedDeporteId && p.tipo_participante_id == targetTipoParticipante) {
            const optionLocal = document.createElement('option');
            optionLocal.value = p.id;
            optionLocal.textContent = p.nombre_mostrado;
            localSelect.appendChild(optionLocal);

            const optionVisitante = document.createElement('option');
            optionVisitante.value = p.id;
            optionVisitante.textContent = p.nombre_mostrado;
            visitanteSelect.appendChild(optionVisitante);
        }
    });
}

document.querySelector('form').addEventListener('submit', function(e) {
    const local = document.getElementById('participante_local_id').value;
    const visitante = document.getElementById('participante_visitante_id').value;

    if (local && visitante && local === visitante) {
        e.preventDefault();
        alert('Error: No puedes seleccionar el mismo participante como local y visitante.');
        return false;
    }
});

<?php if ($is_edit): ?>
document.addEventListener('DOMContentLoaded', function() {
    filterParticipants('<?php echo $partido['deporte_id']; ?>');
    document.getElementById('participante_local_id').value = '<?php echo $partido['participante_local_id']; ?>';
    document.getElementById('participante_visitante_id').value = '<?php echo $partido['participante_visitante_id']; ?>';
});
<?php endif; ?>
</script>

<?php
require_once 'admin_footer.php';
?>