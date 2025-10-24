<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

$search_term = '';
$sql_where = '';
$params = [];
$types = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $sql_where = " WHERE m.nombre_jugador LIKE ? OR p.nombre_mostrado LIKE ?";
    $like_term = "%" . $search_term . "%";
    $params = [$like_term, $like_term];
    $types = "ss";
}

$sql = "SELECT 
            m.id, m.nombre_jugador, m.posicion, m.edad, m.grado, m.url_foto,
            p.nombre_mostrado AS nombre_equipo,
            p.id AS equipo_id
        FROM miembros_plantel m
        JOIN planteles_equipo pe ON m.plantel_id = pe.id
        JOIN participantes p ON pe.participante_id = p.id
        $sql_where
        ORDER BY m.nombre_jugador ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$jugadores = $stmt->get_result();

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Gestionar Jugadores</h1>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-home"></i> Volver a Inicio
        </a>
    </div>

    <div class="search-bar">
        <form action="gestionar_jugadores.php" method="GET">
            <input type="text" name="search" placeholder="Buscar por nombre o equipo..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
            <?php if (!empty($search_term)): ?>
                <a href="gestionar_jugadores.php" class="btn btn-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
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
                    <th>Nombre Jugador</th>
                    <th>Equipo</th>
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
                        $foto_url = !empty($row['url_foto']) ? htmlspecialchars($row['url_foto']) : '../img/jugadores/default.png';
                ?>
                    <tr>
                        <td><img src="<?php echo $foto_url; ?>" alt="Foto" class="table-avatar"></td>
                        <td><?php echo htmlspecialchars($row['nombre_jugador']); ?></td>
                        <td>
                            <a href="ver_plantel.php?equipo_id=<?php echo $row['equipo_id']; ?>">
                                <?php echo htmlspecialchars($row['nombre_equipo']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($row['posicion']); ?></td>
                        <td><?php echo htmlspecialchars($row['edad']); ?></td>
                        <td><?php echo htmlspecialchars($row['grado']); ?></td>
                        <td class="action-buttons">
                            <a href="crear_jugador.php?edit_id=<?php echo $row['id']; ?>&equipo_id=<?php echo $row['equipo_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="jugador_process.php?delete_id=<?php echo $row['id']; ?>&equipo_id=<?php echo $row['equipo_id']; ?>" class="btn btn-danger" onclick="return confirm('¿Seguro que quieres eliminar a este jugador?');">
                                <i class="fas fa-trash"></i> Eliminar
                            </a>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='7'>No se encontraron jugadores";
                    if (!empty($search_term)) {
                        echo " con el término '" . htmlspecialchars($search_term) . "'";
                    }
                    echo ".</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</main>

<?php
$stmt->close();
require_once 'admin_footer.php';
?>