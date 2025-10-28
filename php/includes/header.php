<?php
// includes/header.php
// Asume que db_connect.php ya inició la sesión
require_once __DIR__ . '/../db_connect.php';

// Obtener nombre de usuario (email por defecto si no hay nombre)
$user_name = $_SESSION['user_name'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/usuario_style.css"> </head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo">
                <a href="index.php">
                    <img src="../img/logos/elllort.jpg" alt="Logo Colegio Llort" class="logo-img">
                    </a>
            </div>

            <button class="hamburger-menu" id="hamburger-menu" aria-label="Menú">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="nav-overlay" id="nav-overlay"></div>

            <nav class="main-nav" id="main-nav">
                 <button class="nav-close" id="nav-close" aria-label="Cerrar menú">
                    <i class="fas fa-times"></i>
                </button>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="torneos.php"><i class="fas fa-trophy"></i> Torneos</a>
                <a href="calendario.php"><i class="fas fa-calendar-alt"></i> Calendario</a>
                <a href="seleccion.php"><i class="fas fa-flag"></i> Selección</a>
                <a href="jugadores.php"><i class="fas fa-users"></i> Jugadores</a>
                <a href="noticias.php"><i class="fas fa-newspaper"></i> Noticias</a>
                <a href="galeria.php"><i class="fas fa-images"></i> Galería</a>
                <div class="nav-user-mobile">
                     <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?></span>
                     <a href="php/logout.php" class="btn-logout-mobile"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                </div>
            </nav>

            <div class="user-info-desktop">
                <span><?php echo htmlspecialchars($user_name); ?></span>
                <a href="php/logout.php" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>
        </div>
    </header>
    <main class="main-content">