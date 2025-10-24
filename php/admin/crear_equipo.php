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
    } else {
        header("Location: gestionar_equipos.php?error=Equipo no encontrado.");
        exit;
    }
    $stmt->close();
}

$deportes = $conn->query("SELECT id, nombre_mostrado FROM deportes WHERE es_por_equipos = 1 ORDER BY nombre_mostrado");
$tipos = $conn->query("SELECT id, nombre_mostrado FROM tipos_participante WHERE id = 1"); 

?>

<main class="admin-page">
    <div class="page-header">
        <h1><?php echo $is_edit ? 'Editar Equipo' : 'Crear Nuevo Equipo'; ?></h1>
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

            <div class="form-group">
                <label for="nombre_mostrado">Nombre Oficial del Equipo:</label>
                <input type="text" id="nombre_mostrado" name="nombre_mostrado" value="<?php echo htmlspecialchars($equipo['nombre_mostrado']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="nombre_corto">Nombre Corto (3-5 letras):</label>
                    <input type="text" id="nombre_corto" name="nombre_corto" value="<?php echo htmlspecialchars($equipo['nombre_corto']); ?>" placeholder="Ej: CFL">
                </div>
                <div class="form-group">
                    <label for="deporte_id">Deporte:</label>
                    <select id="deporte_id" name="deporte_id" required>
                        <option value="">-- Seleccione --</option>
                        <?php while($d = $deportes->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo ($d['id'] == $equipo['deporte_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="logo">Logo del Equipo:</label>
                <input type="file" id="logo" name="logo" class="form-input-file" accept="image/png, image/jpeg, image/webp">
                <div class="form-image-preview">
                    <img src="<?php echo $logo_preview; ?>" alt="Logo Preview" id="logo-preview-img">
                </div>
                <small>Subir solo si deseas cambiar el logo. Recomendado: 500x500px, PNG transparente.</small>
            </div>

            <input type="hidden" name="tipo_participante_id" value="1">
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar Equipo' : 'Guardar Equipo'; ?>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('logo').addEventListener('change', function(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('logo-preview-img');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
});
</script>

<?php
require_once 'admin_footer.php';
?>