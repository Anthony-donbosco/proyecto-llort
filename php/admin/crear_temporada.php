<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$is_edit = false;
$temporada = [
    'nombre' => '',
    'ano' => date('Y'),
    'fecha_inicio' => date('Y-01-01'),
    'fecha_fin' => date('Y-12-31'),
    'es_actual' => 0
];
$page_title = 'Agregar Temporada';

if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $temporada_id = (int)$_GET['edit_id'];
    $page_title = 'Editar Temporada';

    $stmt = $conn->prepare("SELECT * FROM temporadas WHERE id = ?");
    $stmt->bind_param("i", $temporada_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $temporada = $result->fetch_assoc();
    } else {
        header("Location: gestionar_temporadas.php?error=Temporada no encontrada.");
        exit;
    }
    $stmt->close();
}

?>

<main class="admin-page">
    <div class="page-header">
        <h1><?php echo $page_title; ?></h1>
        <a href="gestionar_temporadas.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Temporadas
        </a>
    </div>

    <div class="form-container-admin">
        <form action="temporada_process.php" method="POST" class="admin-form">

            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="temporada_id" value="<?php echo $temporada['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nombre">Nombre de la Temporada: *</label>
                <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($temporada['nombre']); ?>" required placeholder="Ej: Temporada 2024-2025">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="ano">Año: *</label>
                    <input type="number" name="ano" id="ano" value="<?php echo htmlspecialchars($temporada['ano']); ?>" required min="2000" max="2100">
                </div>

                <div class="form-group">
                    <label for="es_actual">
                        <input type="checkbox" name="es_actual" id="es_actual" value="1" <?php echo $temporada['es_actual'] ? 'checked' : ''; ?>>
                        Temporada Actual
                    </label>
                    <small>Solo puede haber una temporada actual</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_inicio">Fecha de Inicio: *</label>
                    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo $temporada['fecha_inicio']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="fecha_fin">Fecha de Fin: *</label>
                    <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo $temporada['fecha_fin']; ?>" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar Temporada' : 'Guardar Temporada'; ?>
                </button>
            </div>
        </form>
    </div>
</main>

<?php
require_once 'admin_footer.php';
?>
