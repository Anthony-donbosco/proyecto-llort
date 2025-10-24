<?php
if (!empty($_SESSION['user_id'])){
    if($_SESSION['user_id'] = 1){
        header("Location = admin/index.php")
    } else[
        header("Location = usuario/index.php")
    ]
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Sistema Deportivo</title>
    <link rel="stylesheet" href="css/style.css">
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

        <form action="php/login_process.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Entrar</button>
        </form>
        <p class="form-link">¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
    </div>
</body>
</html>