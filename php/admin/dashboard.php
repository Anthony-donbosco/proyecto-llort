<?php
require_once 'auth_admin.php';

require_once 'admin_header.php';
?>

<main class="dashboard">
    <h1>Panel de Control</h1>
    <p>Bienvenido al sistema de gestión deportiva. Desde aquí puedes administrar el contenido del sitio.</p>

    <div class="dashboard-section">
        <h2><i class="fas fa-trophy"></i> Gestión de Torneos y Equipos</h2>
        <div class="dashboard-widgets">
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-trophy"></i></div>
                <h3>Torneos</h3>
                <p>Gestiona torneos y competiciones</p>
                <a href="gestionar_torneos.php" class="btn btn-primary">Administrar Torneos</a>
            </div>
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-calendar-alt"></i></div>
                <h3>Calendario de Partidos</h3>
                <p>Visualiza partidos por fecha y torneo</p>
                <a href="calendario_partidos.php" class="btn btn-primary">Ver Calendario</a>
            </div>
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Equipos/Participantes</h3>
                <p>Administra equipos y participantes</p>
                <a href="gestionar_equipos.php" class="btn btn-primary">Administrar Equipos</a>
            </div>
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-users"></i></div>
                <h3>Jugadores</h3>
                <p>Gestiona jugadores y sus estadísticas</p>
                <a href="gestionar_jugadores.php" class="btn btn-primary">Administrar Jugadores</a>
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <h2><i class="fas fa-star"></i> Contenido Destacado</h2>
        <div class="dashboard-widgets">
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-star"></i></div>
                <h3>Jugadores Destacados</h3>
                <p>Destaca jugadores por torneo o deporte</p>
                <a href="gestionar_destacados.php" class="btn btn-primary">Gestionar Destacados</a>
            </div>
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-calendar-check"></i></div>
                <h3>Temporadas</h3>
                <p>Administra temporadas deportivas</p>
                <a href="gestionar_temporadas.php" class="btn btn-primary">Gestionar Temporadas</a>
            </div>
            <div class="widget">
                <div class="widget-icon"><i class="fas fa-images"></i></div>
                <h3>Galería de Fotos</h3>
                <p>Sube fotos de la selección por temporada</p>
                <a href="gestionar_galeria.php" class="btn btn-primary">Administrar Galería</a>
            </div>
        </div>
    </div>
</main>

<?php
require_once 'admin_footer.php';
?>