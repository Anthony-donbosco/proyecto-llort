<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$is_edit = false;
$torneo_id = null;
$torneo = [
    'nombre' => '',
    'deporte_id' => '',
    'temporada_id' => null,
    'descripcion' => '',
    'fecha_inicio' => '',
    'fecha_fin' => '',
    'max_participantes' => 16,
    'estado_id' => 1
];

if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $torneo_id = $_GET['edit_id'];
    
    $stmt = $conn->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $torneo = $result->fetch_assoc();
    } else {
        header("Location: gestionar_torneos.php?error=Torneo no encontrado.");
        exit;
    }
    $stmt->close();
}

$deportes = $conn->query("SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado");
$temporadas = $conn->query("SELECT id, nombre FROM temporadas ORDER BY ano DESC");
$estados = $conn->query("SELECT id, nombre_mostrado FROM estados_torneo ORDER BY orden");

?>

<main class="admin-page">
    <div class="page-header">
        <h1><?php echo $is_edit ? 'Editar Torneo' : 'Crear Nuevo Torneo'; ?></h1>
        <a href="gestionar_torneos.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a la lista
        </a>
    </div>

    <div class="form-container-admin">
        <form action="torneo_process.php" method="POST" class="admin-form">
            
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="torneo_id" value="<?php echo $torneo_id; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nombre">Nombre del Torneo:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($torneo['nombre']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="deporte_id">Deporte:</label>
                    <select id="deporte_id" name="deporte_id" required>
                        <option value="">-- Seleccione un deporte --</option>
                        <?php while($d = $deportes->fetch_assoc()): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo ($d['id'] == $torneo['deporte_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="temporada_id">Temporada:</label>
                    <select id="temporada_id" name="temporada_id">
                        <option value="">-- (Opcional) --</option>
                         <?php while($t = $temporadas->fetch_assoc()): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo ($t['id'] == $torneo['temporada_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($torneo['descripcion']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_inicio">Fecha de Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($torneo['fecha_inicio']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha de Finalización:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($torneo['fecha_fin']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="max_participantes">Max. Participantes:</label>
                    <input type="number" id="max_participantes" name="max_participantes" value="<?php echo htmlspecialchars($torneo['max_participantes']); ?>" required min="2">
                </div>
                 <div class="form-group">
                    <label for="estado_id">Estado:</label>
                    <select id="estado_id" name="estado_id" required>
                         <?php while($e = $estados->fetch_assoc()): ?>
                            <option value="<?php echo $e['id']; ?>" <?php echo ($e['id'] == $torneo['estado_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar Torneo' : 'Guardar Torneo'; ?>
                </button>
            </div>

        </form>
    </div>
</main>

<?php
require_once 'admin_footer.php';
?>