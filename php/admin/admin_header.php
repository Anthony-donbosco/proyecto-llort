<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="logo">
                <a href="dashboard.php">Admin Colegio Llort</a>
            </div>

            <button class="hamburger-menu" id="hamburger-menu" aria-label="Menú">
                <span></span>
                <span></span>
                <span></span
            </button>

            <div class="nav-overlay" id="nav-overlay"></div>
            <nav class="admin-nav" id="admin-nav">
                <button class="nav-close" id="nav-close" aria-label="Cerrar menú">
                    <i class="fas fa-times"></i>
                </button>
                <a href="dashboard.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="gestionar_torneos.php"><i class="fas fa-trophy"></i> Torneos</a>
                <a href="gestionar_equipos.php"><i class="fas fa-users"></i> Equipos</a>
                <a href="gestionar_jugadores.php"><i class="fas fa-user"></i> Jugadores</a>
                <a href="../logout.php" class="nav-logout"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </nav>

            <div class="user-info">
                <a href="../logout.php" class="logout-button">Cerrar Sesión</a>
            </div>
        </div>
    </header>
    <div class="admin-content">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger-menu');
    const nav = document.getElementById('admin-nav');
    const navClose = document.getElementById('nav-close');
    const overlay = document.getElementById('nav-overlay');

    function openMenu() {
        nav.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        nav.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', openMenu);
    navClose.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);

    const navLinks = nav.querySelectorAll('a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeMenu();
            }
        });
    });
});
</script>