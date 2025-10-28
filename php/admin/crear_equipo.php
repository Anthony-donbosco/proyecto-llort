<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$is_edit = false;
$equipo = [
    'nombre_mostrado' => '',
    'nombre_corto' => '',
    'deporte_id' => '',
    'tipo_participante_id' => 1,
    'url_logo' => ''
];
$logo_preview = '../img/logos/default.png';
$is_individual_edit = false;
if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $equipo_id = (int)$_GET['edit_id'];
    
    $stmt = $conn->prepare("SELECT * FROM participantes WHERE id = ?");
    $stmt->bind_param("i", $equipo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $equipo = $result->fetch_assoc();
        if (!empty($equipo['url_logo'])) {
            $logo_preview = '../' . htmlspecialchars($equipo['url_logo']);
        }
        $is_individual_edit = ($equipo['tipo_participante_id'] != 1);
    } else {
        header("Location: gestionar_equipos.php?error=Equipo no encontrado.");
        exit;
    }
    $stmt->close();
}

$todos_deportes_q = $conn->query("SELECT id, nombre_mostrado, es_por_equipos FROM deportes ORDER BY nombre_mostrado");
$todos_deportes = $todos_deportes_q->fetch_all(MYSQLI_ASSOC);
$todos_deportes_json = json_encode($todos_deportes);

?>

<main class="admin-page">
    <div class="page-header">
        <h1><?php 
            if ($is_edit) {
                echo $is_individual_edit ? 'Editar Participante Individual' : 'Editar Equipo';
            } else {
                echo 'Crear Nuevo Participante';
            }
        ?></h1>
        <a href="gestionar_equipos.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a la lista
        </a>
    </div>

    <div class="form-container-admin">
        <form action="equipo_process.php" method="POST" class="admin-form" enctype="multipart/form-data">
            
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="equipo_id" value="<?php echo $equipo['id']; ?>">
                <input type="hidden" name="current_logo_path" value="<?php echo htmlspecialchars($equipo['url_logo']); ?>">
            <?php endif; ?>

            <fieldset class="form-fieldset">
                <legend>1. Tipo de Participante</legend>
                <div class="form-group form-group-checkbox">
                    <input 
                        type="checkbox" 
                        id="es_individual" 
                        name="es_individual"
                        <?php echo $is_individual_edit ? 'checked' : ''; ?>>
                    <label for="es_individual">
                        Es Deporte Individual (Ej: Ajedrez, Ping Pong)
                    </label>
                    <small>Marcar esto cambiará el formulario para registrar un participante individual en lugar de un equipo.</small>
                </div>
            </fieldset>

            
            <fieldset class="form-fieldset">
                <legend id="fieldset_legend_details">
                    <?php echo $is_individual_edit ? '2. Detalles del Participante' : '2. Detalles del Equipo'; ?>
                </legend>

                <input type="hidden" name="tipo_participante_id" id="tipo_participante_id" value="<?php echo htmlspecialchars($equipo['tipo_participante_id']); ?>">

                <div class="form-group">
                    <label for="nombre_mostrado" id="label_nombre_mostrado">
                        <?php echo $is_individual_edit ? 'Nombre del Participante (Jugador):' : 'Nombre Oficial del Equipo:'; ?>
                    </label>
                    <input type="text" id="nombre_mostrado" name="nombre_mostrado" value="<?php echo htmlspecialchars($equipo['nombre_mostrado']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group" id="group_nombre_corto" style="<?php echo $is_individual_edit ? 'display:none;' : ''; ?>">
                        <label for="nombre_corto">Nombre Corto (3-5 letras):</label>
                        <input type="text" id="nombre_corto" name="nombre_corto" value="<?php echo htmlspecialchars($equipo['nombre_corto']); ?>" placeholder="Ej: CFL">
                    </div>
                    
                    <div class="form-group" id="group_deporte">
                        <label for="deporte_id">Deporte:</label>
                        <select id="deporte_id" name="deporte_id" required>
                            <option value="">-- Seleccione --</option>
                            
                            <?php foreach ($todos_deportes as $d): ?>
                                <?php
                                $is_sport_individual = ($d['es_por_equipos'] == 0);
                                // Mostrar solo si el tipo de deporte coincide con el modo del formulario
                                // (Si $is_individual_edit es TRUE, solo mostrar deportes individuales)
                                // (Si $is_individual_edit es FALSE, solo mostrar deportes de equipo)
                                if (($is_individual_edit && $is_sport_individual) || (!$is_individual_edit && !$is_sport_individual)):
                                ?>
                                    <option 
                                        value="<?php echo $d['id']; ?>" 
                                        <?php echo ($d['id'] == $equipo['deporte_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($d['nombre_mostrado']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="logo" id="label_logo">
                         <?php echo $is_individual_edit ? 'Foto del Participante (Opcional):' : 'Logo del Equipo (Opcional):'; ?>
                    </label>
                    <input type="file" id="logo" name="logo" class="form-input-file" accept="image/png, image/jpeg, image/webp">
                    <div class="form-image-preview">
                        <img src="<?php echo $logo_preview; ?>" alt="Logo Preview" id="logo-preview-img">
                    </div>
                    <small id="small_logo_recommendation">
                        <?php echo $is_individual_edit ? 'Subir solo si deseas cambiar la foto.' : 'Subir solo si deseas cambiar el logo. Recomendado: 500x500px, PNG transparente.'; ?>
                    </small>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 
                    <span id="label_btn_guardar">
                        <?php echo $is_edit ? ($is_individual_edit ? 'Actualizar Participante' : 'Actualizar Equipo') : 'Guardar Equipo'; ?>
                    </span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
const allSports = <?php echo $todos_deportes_json; ?>;
// Asumimos ID 1 = Equipo, ID 2 = Individual
const TIPO_EQUIPO = 1;
const TIPO_INDIVIDUAL = 2; 

// Referencias a elementos del DOM
const checkbox = document.getElementById('es_individual');
const tipoHidden = document.getElementById('tipo_participante_id');
const deporteSelect = document.getElementById('deporte_id');
const nombreLabel = document.getElementById('label_nombre_mostrado');
const nombreCortoGroup = document.getElementById('group_nombre_corto');
const logoLabel = document.getElementById('label_logo');
const logoSmall = document.getElementById('small_logo_recommendation');
const fieldsetLegend = document.getElementById('fieldset_legend_details');
const saveButtonLabel = document.getElementById('label_btn_guardar');
const pageTitle = document.querySelector('.page-header h1');

// Función para actualizar el formulario basado en el checkbox
function updateFormForSportType(isIndividual) {
    
    // 1. Actualizar valor del ID de tipo
    // (Asumimos que "Individual" es tipo 2, ajusta si es otro ID)
    tipoHidden.value = isIndividual ? TIPO_INDIVIDUAL : TIPO_EQUIPO;

    // 2. Actualizar etiquetas y visibilidad
    nombreLabel.textContent = isIndividual ? 'Nombre del Participante (Jugador):' : 'Nombre Oficial del Equipo:';
    logoLabel.textContent = isIndividual ? 'Foto del Participante (Opcional):' : 'Logo del Equipo (Opcional):';
    logoSmall.textContent = isIndividual ? 'Subir solo si deseas cambiar la foto.' : 'Subir solo si deseas cambiar el logo. Recomendado: 500x500px, PNG transparente.';
    fieldsetLegend.textContent = isIndividual ? '2. Detalles del Participante' : '2. Detalles del Equipo';
    
    // Ocultar "Nombre Corto" para individuales
    nombreCortoGroup.style.display = isIndividual ? 'none' : 'block';
    
    // Actualizar textos del botón y título (solo si NO estamos editando, para no sobreescribir el estado "Actualizar")
    <?php if (!$is_edit): ?>
        saveButtonLabel.textContent = isIndividual ? 'Guardar Participante' : 'Guardar Equipo';
        pageTitle.textContent = isIndividual ? 'Crear Nuevo Participante' : 'Crear Nuevo Equipo';
    <?php endif; ?>

    // 3. Repopular el dropdown de deportes
    deporteSelect.innerHTML = '<option value="">-- Seleccione --</option>'; // Limpiar opciones
    
    const targetSportType = isIndividual ? 0 : 1; // 0 = Individual, 1 = Equipo

    allSports.forEach(sport => {
        // 'es_por_equipos' viene como string, lo convertimos a número
        if (parseInt(sport.es_por_equipos) === targetSportType) {
            const option = document.createElement('option');
            option.value = sport.id;
            option.textContent = sport.nombre_mostrado;
            deporteSelect.appendChild(option);
        }
    });
}

// Añadir el 'listener' al checkbox
checkbox.addEventListener('change', function() {
    updateFormForSportType(this.checked);
});

// (Script existente para la preview de la imagen)
document.getElementById('logo').addEventListener('change', function(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('logo-preview-img');
        output.src = reader.result;
    };
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
});
</script>

<?php
require_once 'admin_footer.php';
?>