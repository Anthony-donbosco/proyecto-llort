<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$is_edit = false;
$noticia = [
    'titulo' => '',
    'subtitulo' => '',
    'contenido' => '',
    'imagen_portada' => '',
    'autor' => 'Redacción',
    'deporte_id' => '',
    'temporada_id' => '',
    'etiquetas' => '',
    'destacada' => 0,
    'publicada' => 1,
    'fecha_publicacion' => date('Y-m-d\TH:i'),
    'orden' => 0
];
$imagen_preview = '../../img/noticias/default.png';
$page_title = 'Crear Noticia';

if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $noticia_id = (int)$_GET['edit_id'];
    $page_title = 'Editar Noticia';

    $stmt = $conn->prepare("SELECT * FROM noticias WHERE id = ?");
    $stmt->bind_param("i", $noticia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $noticia = $result->fetch_assoc();
        $noticia['fecha_publicacion'] = date('Y-m-d\TH:i', strtotime($noticia['fecha_publicacion']));
        if (!empty($noticia['imagen_portada'])) {
            $imagen_preview = htmlspecialchars($noticia['imagen_portada']);
        }
    } else {
        header("Location: gestionar_noticias.php?error=Noticia no encontrada.");
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
        <a href="gestionar_noticias.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Noticias
        </a>
    </div>

    <div class="form-container-admin">
        <form action="noticia_process.php" method="POST" class="admin-form" enctype="multipart/form-data">

            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="noticia_id" value="<?php echo $noticia['id']; ?>">
                <input type="hidden" name="current_imagen_path" value="<?php echo htmlspecialchars($noticia['imagen_portada']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="titulo">Título de la Noticia: *</label>
                <input type="text" name="titulo" id="titulo" value="<?php echo htmlspecialchars($noticia['titulo']); ?>" required placeholder="Ej: Inauguración del Torneo de Fútbol 2025">
            </div>

            <div class="form-group">
                <label for="subtitulo">Subtítulo (bajada):</label>
                <input type="text" name="subtitulo" id="subtitulo" value="<?php echo htmlspecialchars($noticia['subtitulo']); ?>" placeholder="Breve descripción de la noticia">
            </div>

            <div class="form-group">
                <label for="contenido">Contenido de la Noticia: *</label>
                <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 8px; font-size: 0.9rem; color: #666;">
                    <strong>Formato básico permitido:</strong><br>
                    • Usa saltos de línea para separar párrafos<br>
                    • **texto** para <strong>negritas</strong><br>
                    • *texto* para <em>cursivas</em><br>
                    • - al inicio de línea para listas
                </div>
                <textarea name="contenido" id="contenido" rows="15" required placeholder="Escribe aquí el contenido de la noticia..."><?php echo htmlspecialchars($noticia['contenido']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="imagen_portada">Imagen de Portada: <?php echo $is_edit ? '' : '*'; ?></label>
                <input type="file" name="imagen_portada" id="imagen_portada" class="form-input-file" accept="image/png, image/jpeg, image/webp, image/jpg" <?php echo $is_edit ? '' : 'required'; ?>>
                <div class="form-image-preview">
                    <img src="<?php echo $imagen_preview; ?>" alt="Preview" id="imagen-preview-img" style="max-width: 100%; height: 200px; object-fit: cover;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="autor">Autor:</label>
                    <input type="text" name="autor" id="autor" value="<?php echo htmlspecialchars($noticia['autor']); ?>" placeholder="Redacción">
                </div>

                <div class="form-group">
                    <label for="fecha_publicacion">Fecha de Publicación:</label>
                    <input type="datetime-local" name="fecha_publicacion" id="fecha_publicacion" value="<?php echo $noticia['fecha_publicacion']; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="deporte_id">Deporte (opcional):</label>
                    <select name="deporte_id" id="deporte_id">
                        <option value="">General (todos)</option>
                        <?php while($deporte = $deportes->fetch_assoc()): ?>
                            <option value="<?php echo $deporte['id']; ?>" <?php echo $noticia['deporte_id'] == $deporte['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deporte['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="temporada_id">Temporada (opcional):</label>
                    <select name="temporada_id" id="temporada_id">
                        <option value="">Ninguna</option>
                        <?php while($temporada = $temporadas->fetch_assoc()): ?>
                            <option value="<?php echo $temporada['id']; ?>" <?php echo $noticia['temporada_id'] == $temporada['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($temporada['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="etiquetas">Etiquetas (separadas por comas):</label>
                    <input type="text" name="etiquetas" id="etiquetas" value="<?php echo htmlspecialchars($noticia['etiquetas']); ?>" placeholder="torneo, fútbol, semifinales">
                    <small>Ej: torneo, campeonato, fútbol</small>
                </div>

                <div class="form-group">
                    <label for="orden">Orden de Visualización:</label>
                    <input type="number" name="orden" id="orden" value="<?php echo $noticia['orden']; ?>" min="0" placeholder="0">
                    <small>Menor número aparece primero</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="destacada">
                        <input type="checkbox" name="destacada" id="destacada" value="1" <?php echo $noticia['destacada'] ? 'checked' : ''; ?>>
                        Noticia Destacada (aparece en slider)
                    </label>
                </div>

                <div class="form-group">
                    <label for="publicada">
                        <input type="checkbox" name="publicada" id="publicada" value="1" <?php echo $noticia['publicada'] ? 'checked' : ''; ?>>
                        Publicar noticia (si no, queda como borrador)
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar Noticia' : 'Crear Noticia'; ?>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('imagen_portada').addEventListener('change', function(event) {
    if (event.target.files && event.target.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagen-preview-img').src = e.target.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }
});
</script>

<?php
require_once 'admin_footer.php';
?>
