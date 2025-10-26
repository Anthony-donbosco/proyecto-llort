<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

// Obtener configuración actual
$config_sql = "SELECT * FROM configuracion_galeria";
$config_result = $conn->query($config_sql);
$config = [];
while($row = $config_result->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}

// Obtener todas las temporadas
$temporadas_sql = "SELECT id, nombre FROM temporadas ORDER BY es_actual DESC, ano DESC";
$temporadas = $conn->query($temporadas_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $temporada_activa = !empty($_POST['temporada_galeria_activa']) ? (int)$_POST['temporada_galeria_activa'] : NULL;
    $mostrar_todas = isset($_POST['mostrar_todas_temporadas']) ? 1 : 0;

    try {
        // Actualizar configuración
        $sql1 = "UPDATE configuracion_galeria SET valor = ? WHERE clave = 'temporada_galeria_activa'";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("s", $temporada_activa);
        $stmt1->execute();
        $stmt1->close();

        $sql2 = "UPDATE configuracion_galeria SET valor = ? WHERE clave = 'mostrar_todas_temporadas'";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $mostrar_todas);
        $stmt2->execute();
        $stmt2->close();

        $success_msg = "Configuración actualizada exitosamente.";

    } catch (Exception $e) {
        $error_msg = "Error al actualizar: " . $e->getMessage();
    }
}

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Configuración de Galería</h1>
        <a href="gestionar_galeria.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Galería
        </a>
    </div>

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="form-container-admin">
        <form action="configurar_galeria.php" method="POST" class="admin-form">

            <div class="form-section-title">
                <h3>Control de Visualización de Temporadas</h3>
                <p>Controla qué fotos se muestran en la galería pública del sitio web.</p>
            </div>

            <div class="form-group">
                <label for="mostrar_todas_temporadas">
                    <input type="checkbox" name="mostrar_todas_temporadas" id="mostrar_todas_temporadas" value="1" <?php echo $config['mostrar_todas_temporadas'] == '1' ? 'checked' : ''; ?>>
                    Mostrar fotos de todas las temporadas
                </label>
                <small>Si está marcado, se mostrarán fotos de todas las temporadas activas. Si no, solo se mostrarán las de la temporada seleccionada.</small>
            </div>

            <div class="form-group" id="temporada-select-group">
                <label for="temporada_galeria_activa">Temporada Activa en Galería:</label>
                <select name="temporada_galeria_activa" id="temporada_galeria_activa">
                    <option value="">Ninguna (no mostrar galería)</option>
                    <?php while($temporada = $temporadas->fetch_assoc()): ?>
                        <option value="<?php echo $temporada['id']; ?>" <?php echo $config['temporada_galeria_activa'] == $temporada['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($temporada['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <small>Esta es la temporada cuyas fotos se mostrarán en la galería pública (si no está marcado "Mostrar todas").</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</main>

<script>
// Mostrar/ocultar selector de temporada según checkbox
document.getElementById('mostrar_todas_temporadas').addEventListener('change', function() {
    const selectGroup = document.getElementById('temporada-select-group');
    if (this.checked) {
        selectGroup.style.opacity = '0.5';
        selectGroup.querySelector('select').disabled = true;
    } else {
        selectGroup.style.opacity = '1';
        selectGroup.querySelector('select').disabled = false;
    }
});

// Ejecutar al cargar la página
window.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('mostrar_todas_temporadas');
    if (checkbox.checked) {
        document.getElementById('temporada-select-group').style.opacity = '0.5';
        document.getElementById('temporada_galeria_activa').disabled = true;
    }
});
</script>

<?php
require_once 'admin_footer.php';
?>
