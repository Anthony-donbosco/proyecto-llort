<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!empty($_SESSION['user_id'])) {
    // Redirigir admin al dashboard, usuario normal al index
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
         header("Location: php/admin/dashboard.php");
    } else {
         header("Location: index.php"); // Redirige usuario normal al index
    }
    exit;
}
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['role_id'] == 1) {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: login.php?error=Sección de usuario en construcción");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Sistema Deportivo</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Iniciar Sesión</h2>
        <p>Bienvenido al Sistema de Gestión Deportiva</p>

        <?php 
            if (isset($_GET['error'])) {
                echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
            }
        ?>

        <form action="login_process.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Entrar</button>
        </form>
        <p class="form-link">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
    </div>
</body>
</html>