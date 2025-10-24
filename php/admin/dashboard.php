<?php
require_once 'auth_admin.php';

require_once 'admin_header.php';

// (Aquí irá la lógica para obtener estadísticas, ej: contar torneos, usuarios, etc.)
// $total_torneos = $conn->query("SELECT COUNT(*) FROM torneos")->fetch_row()[0];
// $total_jugadores = $conn->query("SELECT COUNT(*) FROM miembros_plantel")->fetch_row()[0];
?>

<main class="dashboard">
    <h1>Panel de Control</h1>
    <p>Bienvenido al sistema de gestión deportiva. Desde aquí puedes administrar el contenido del sitio.</p>

    <div class="dashboard-widgets">
        <div class="widget">
            <h3>Torneos</h3>
            <p>Total: (conteo)</p>
            <a href="gestionar_torneos.php">Administrar Torneos</a>
        </div>
        <div class="widget">
            <h3>Equipos/Participantes</h3>
            <p>Total: (conteo)</p>
            <a href="gestionar_equipos.php">Administrar Equipos</a>
        </div>
        <div class="widget">
            <h3>Jugadores</h3>
            <p>Total: (conteo)</p>
            <a href="gestionar_jugadores.php">Administrar Jugadores</a>
        </div>
    </div>
</main>

<?php
require_once 'admin_footer.php';
?>