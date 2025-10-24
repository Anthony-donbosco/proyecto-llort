<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

// Validar que tengamos el ID del equipo
if (!isset($_GET['equipo_id'])) {
    header("Location: gestionar_equipos.php?error=No se especificó un equipo.");
    exit;
}

$equipo_id = (int)$_GET['equipo_id'];

// 1. Obtener info del Equipo
$stmt_equipo = $conn->prepare("SELECT nombre_mostrado FROM participantes WHERE id = ?");
$stmt_equipo->bind_param("i", $equipo_id);
$stmt_equipo->execute();
$equipo_result = $stmt_equipo->get_result();
if ($equipo_result->num_rows == 0) {
    header("Location: gestionar_equipos.php?error=Equipo no encontrado.");
    exit;
}
$equipo = $equipo_result->fetch_assoc();
$nombre_equipo = $equipo['nombre_mostrado'];

// 2. Encontrar el Plantel ID (asumimos el "principal" activo)
$stmt_plantel = $conn->prepare("SELECT id FROM planteles_equipo WHERE participante_id = ? AND esta_activo = 1 LIMIT 1");
$stmt_plantel->bind_param("i", $equipo_id);
$stmt_plantel->execute();
$plantel_result = $stmt_plantel->get_result();
if ($plantel_result->num_rows == 0) {
    // Esto no debería pasar si la lógica de 'equipo_process' funciona
    echo "Error: No se encontró un plantel activo para este equipo.";
    require_once 'admin_footer.php';
    exit;
}
$plantel_id = $plantel_result->fetch_assoc()['id'];

// 3. Consultar los jugadores de ESE plantel
$stmt_jugadores = $conn->prepare("SELECT * FROM miembros_plantel WHERE plantel_id = ? ORDER BY numero_camiseta, nombre_jugador");
$stmt_jugadores->bind_param("i", $plantel_id);
$stmt_jugadores->execute();
$jugadores = $stmt_jugadores->get_result();
?>

<main class="admin-page">
    <div class="page-header">
        <h1>Plantel de "<?php echo htmlspecialchars($nombre_equipo); ?>"</h1>
        <div>
            <a href="crear_jugador.php?plantel_id=<?php echo $plantel_id; ?>&equipo_id=<?php echo $equipo_id; ?>" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Agregar Jugador
            </a>
            <a href="gestionar_equipos.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Equipos
            </a>
        </div>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>N°</th>
                    <th>Nombre Jugador</th>
                    <th>Posición</th>
                    <th>Edad</th>
                    <th>Grado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($jugadores->num_rows > 0) {
                    while($row = $jugadores->fetch_assoc()) {
                        $foto_url = !empty($row['url_foto']) ? '../' . htmlspecialchars($row['url_foto']) : '../img/jugadores/default.png';
                ?>
                    <tr>
                        <td><img src="<?php echo $foto_url; ?>" alt="Foto" class="table-avatar"></td>
                        <td><?php echo htmlspecialchars($row['numero_camiseta']); ?></td>
                        <td><?php echo htmlspecialchars($row['nombre_jugador']); ?></td>
                        <td><?php echo htmlspecialchars($row['posicion']); ?></td>
                        <td><?php echo htmlspecialchars($row['edad']); ?></td>
                        <td><?php echo htmlspecialchars($row['grado']); ?></td>
                        <td class="action-buttons">
                            <a href="crear_jugador.php?edit_id=<?php echo $row['id']; ?>&equipo_id=<?php echo $equipo_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="jugador_process.php?delete_id=<?php echo $row['id']; ?>&equipo_id=<?php echo $equipo_id; ?>" class="btn btn-danger" onclick="return confirm('¿Seguro que quieres eliminar a este jugador?');">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='7'>Este equipo aún no tiene jugadores en su plantel.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<?php
$stmt_equipo->close();
$stmt_plantel->close();
$stmt_jugadores->close();
require_once 'admin_footer.php';
?>