<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
         header("Location: admin/dashboard.php");
    } else {
         header("Location: usuario/index.php"); 
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema Deportivo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css"> 
</head>
<body>
    <div class="form-container">
        <h2>Iniciar Sesión</h2>
        <p>Bienvenido al Sistema de Gestión Deportiva</p>

        <?php
            if (isset($_SESSION['login_message'])) {
                $msg = $_SESSION['login_message'];
                $class = $msg['type'] === 'success' ? 'success-message' : 'error-message';
                echo '<div class="' . $class . '">' . htmlspecialchars($msg['text']) . '</div>';
                
                unset($_SESSION['login_message']);
            }
        ?>

        <form action="login_process.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required placeholder="tu@email.com">

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required placeholder="••••••••">

            <button type="submit">Entrar</button>
        </form>
        <p class="form-link">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
    </div>
</body>
</html>