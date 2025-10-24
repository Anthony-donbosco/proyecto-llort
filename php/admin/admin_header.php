<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../../../css/admin_style.css">
</head>
<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="logo">
                <a href="dashboard.php">Admin Colegio Llort</a>
            </div>
            <nav class="admin-nav">
                <a href="dashboard.php">Inicio</a>
                <a href="gestionar_torneos.php">Torneos</a>
                <a href="gestionar_equipos.php">Equipos</a>
                <a href="gestionar_jugadores.php">Jugadores</a>
            </nav>
            <div class="user-info">
                <span>Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="../logout.php" class="logout-button">Cerrar Sesión</a>
            </div>
        </div>
    </header>
    <div class="admin-content">