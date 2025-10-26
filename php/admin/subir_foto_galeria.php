<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$is_edit = false;
$foto = [
    'temporada_id' => '',
    'deporte_id' => '',
    'titulo' => '',
    'descripcion' => '',
    'url_foto' => '',
    'es_foto_grupo' => 1,
    'orden' => 0,
    'fecha_captura' => date('Y-m-d'),
    'esta_activa' => 1
];
$foto_preview = '../../img/galeria/default.png';
$page_title = 'Subir Foto a Galería';

if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $foto_id = (int)$_GET['edit_id'];
    $page_title = 'Editar Foto de Galería';

    $stmt = $conn->prepare("SELECT * FROM galeria_temporadas WHERE id = ?");
    $stmt->bind_param("i", $foto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $foto = $result->fetch_assoc();
        if (!empty($foto['url_foto'])) {
            $foto_preview = htmlspecialchars($foto['url_foto']);
        }
    } else {
        header("Location: gestionar_galeria.php?error=Foto no encontrada.");
        exit;
    }
    $stmt->close();
}

$temporadas_sql = "SELECT id, nombre FROM temporadas ORDER BY es_actual DESC, ano DESC";
$temporadas = $conn->query($temporadas_sql);

$deportes_sql = "SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado";
$deportes = $conn->query($deportes_sql);

?>

<main class="admin-page">
    <div class="page-header">
        <h1><?php echo $page_title; ?></h1>
        <a href="gestionar_galeria.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Galería
        </a>
    </div>

    <div class="form-container-admin">
        <form action="galeria_process.php" method="POST" class="admin-form" enctype="multipart/form-data">

            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="foto_id" value="<?php echo $foto['id']; ?>">
                <input type="hidden" name="current_foto_path" value="<?php echo htmlspecialchars($foto['url_foto']); ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="temporada_id">Temporada: *</label>
                    <select name="temporada_id" id="temporada_id" required>
                        <option value="">Seleccione una temporada</option>
                        <?php while($temporada = $temporadas->fetch_assoc()): ?>
                            <option value="<?php echo $temporada['id']; ?>" <?php echo $foto['temporada_id'] == $temporada['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($temporada['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="deporte_id">Deporte (opcional):</label>
                    <select name="deporte_id" id="deporte_id">
                        <option value="">General (todos los deportes)</option>
                        <?php while($deporte = $deportes->fetch_assoc()): ?>
                            <option value="<?php echo $deporte['id']; ?>" <?php echo $foto['deporte_id'] == $deporte['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deporte['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="titulo">Título de la Foto: *</label>
                <input type="text" name="titulo" id="titulo" value="<?php echo htmlspecialchars($foto['titulo']); ?>" required placeholder="Ej: Selección de Fútbol 2024">
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea name="descripcion" id="descripcion" rows="3" placeholder="Describe la foto..."><?php echo htmlspecialchars($foto['descripcion']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="foto">Foto: <?php echo $is_edit ? '' : '*'; ?></label>
                <input type="file" name="foto" id="foto" class="form-input-file" accept="image/png, image/jpeg, image/webp" <?php echo $is_edit ? '' : 'required'; ?>>
                <div class="form-image-preview">
                    <img src="<?php echo $foto_preview; ?>" alt="Preview" id="foto-preview-img">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_captura">Fecha de Captura:</label>
                    <input type="date" name="fecha_captura" id="fecha_captura" value="<?php echo $foto['fecha_captura']; ?>">
                </div>

                <div class="form-group">
                    <label for="orden">Orden de Visualización:</label>
                    <input type="number" name="orden" id="orden" value="<?php echo $foto['orden']; ?>" min="0" placeholder="0">
                    <small>Menor número aparece primero</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="es_foto_grupo">
                        <input type="checkbox" name="es_foto_grupo" id="es_foto_grupo" value="1" <?php echo $foto['es_foto_grupo'] ? 'checked' : ''; ?>>
                        Es foto grupal de selección
                    </label>
                </div>

                <div class="form-group">
                    <label for="esta_activa">
                        <input type="checkbox" name="esta_activa" id="esta_activa" value="1" <?php echo $foto['esta_activa'] ? 'checked' : ''; ?>>
                        Mostrar en el sitio web
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar Foto' : 'Subir Foto'; ?>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('foto').addEventListener('change', function(event) {
    if (event.target.files && event.target.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('foto-preview-img').src = e.target.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
});
</script>

<?php
require_once 'admin_footer.php';
?>
