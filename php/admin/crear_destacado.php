<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$is_edit = false;
$destacado = [
    'deporte_id' => '',
    'torneo_id' => '',
    'miembro_plantel_id' => '',
    'temporada_id' => '',
    'tipo_destacado' => 'general',
    'descripcion' => '',
    'fecha_destacado' => date('Y-m-d'),
    'orden' => 0,
    'esta_activo' => 1
];
$page_title = 'Agregar Jugador Destacado';

if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $destacado_id = (int)$_GET['edit_id'];
    $page_title = 'Editar Jugador Destacado';

    $stmt = $conn->prepare("SELECT * FROM jugadores_destacados WHERE id = ?");
    $stmt->bind_param("i", $destacado_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $destacado = $result->fetch_assoc();
    } else {
        header("Location: gestionar_destacados.php?error=Destacado no encontrado.");
        exit;
    }
    $stmt->close();
}

$deportes_sql = "SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado";
$deportes = $conn->query($deportes_sql);

$torneos_sql = "SELECT id, nombre, deporte_id FROM torneos ORDER BY nombre";
$torneos = $conn->query($torneos_sql);

$temporadas_sql = "SELECT id, nombre FROM temporadas ORDER BY es_actual DESC, ano DESC";
$temporadas = $conn->query($temporadas_sql);

$jugadores_sql = "SELECT m.id, m.nombre_jugador, m.posicion, p.nombre_mostrado AS equipo, pe.participante_id
                  FROM miembros_plantel m
                  JOIN planteles_equipo pe ON m.plantel_id = pe.id
                  JOIN participantes p ON pe.participante_id = p.id
                  ORDER BY m.nombre_jugador";
$jugadores = $conn->query($jugadores_sql);

?>

<main class="admin-page">
    <div class="page-header">
        <h1><?php echo $page_title; ?></h1>
        <a href="gestionar_destacados.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Destacados
        </a>
    </div>

    <div class="form-container-admin">
        <form action="destacado_process.php" method="POST" class="admin-form">

            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="destacado_id" value="<?php echo $destacado['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="deporte_id">Deporte: *</label>
                    <select name="deporte_id" id="deporte_id" required>
                        <option value="">Seleccione un deporte</option>
                        <?php
                        $deportes->data_seek(0);
                        while($deporte = $deportes->fetch_assoc()):
                        ?>
                            <option value="<?php echo $deporte['id']; ?>" <?php echo $destacado['deporte_id'] == $deporte['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deporte['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tipo_destacado">Tipo de Destacado: *</label>
                    <select name="tipo_destacado" id="tipo_destacado" required>
                        <option value="general" <?php echo $destacado['tipo_destacado'] == 'general' ? 'selected' : ''; ?>>General</option>
                        <option value="torneo" <?php echo $destacado['tipo_destacado'] == 'torneo' ? 'selected' : ''; ?>>Por Torneo</option>
                        <option value="seleccion" <?php echo $destacado['tipo_destacado'] == 'seleccion' ? 'selected' : ''; ?>>Selección</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="miembro_plantel_id">Jugador: *</label>
                <select name="miembro_plantel_id" id="miembro_plantel_id" required>
                    <option value="">Seleccione un jugador</option>
                    <?php while($jugador = $jugadores->fetch_assoc()): ?>
                        <option value="<?php echo $jugador['id']; ?>" <?php echo $destacado['miembro_plantel_id'] == $jugador['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($jugador['nombre_jugador']) . ' - ' . htmlspecialchars($jugador['posicion']) . ' (' . htmlspecialchars($jugador['equipo']) . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="torneo_id">Torneo (opcional):</label>
                    <select name="torneo_id" id="torneo_id">
                        <option value="">Sin torneo específico</option>
                        <?php while($torneo = $torneos->fetch_assoc()): ?>
                            <option value="<?php echo $torneo['id']; ?>" data-deporte="<?php echo $torneo['deporte_id']; ?>" <?php echo $destacado['torneo_id'] == $torneo['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($torneo['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="temporada_id">Temporada (opcional):</label>
                    <select name="temporada_id" id="temporada_id">
                        <option value="">Sin temporada específica</option>
                        <?php while($temporada = $temporadas->fetch_assoc()): ?>
                            <option value="<?php echo $temporada['id']; ?>" <?php echo $destacado['temporada_id'] == $temporada['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($temporada['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción del Logro:</label>
                <textarea name="descripcion" id="descripcion" rows="4" placeholder="Ej: Máximo goleador del torneo con 15 goles"><?php echo htmlspecialchars($destacado['descripcion']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_destacado">Fecha:</label>
                    <input type="date" name="fecha_destacado" id="fecha_destacado" value="<?php echo $destacado['fecha_destacado']; ?>">
                </div>

                <div class="form-group">
                    <label for="orden">Orden de Visualización:</label>
                    <input type="number" name="orden" id="orden" value="<?php echo $destacado['orden']; ?>" min="0" placeholder="0">
                    <small>Menor número aparece primero</small>
                </div>
            </div>

            <div class="form-group">
                <label for="esta_activo">
                    <input type="checkbox" name="esta_activo" id="esta_activo" value="1" <?php echo $destacado['esta_activo'] ? 'checked' : ''; ?>>
                    Mostrar en el sitio web
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar Destacado' : 'Guardar Destacado'; ?>
                </button>
            </div>
        </form>
    </div>
</main>

<?php
require_once 'admin_footer.php';
?>
