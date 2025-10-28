<?php
// Usar require_once aquí para conectar a la BD e iniciar sesión
require_once 'db_connect.php'; // Asumiendo que db_connect.php inicia la sesión

// Variable para almacenar mensajes (errores o éxito)
$message = null;
$message_type = 'info'; // Puede ser 'error' o 'success'

// --- PROCESAR FORMULARIO SI ES POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // --- Validación ---
    if (empty($nombre) || empty($email) || empty($password)) {
        $message = 'Por favor complete todos los campos.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'El formato del email no es válido.';
        $message_type = 'error';
    } elseif (strlen($password) < 6) { // Validación de 6 caracteres
        $message = 'La contraseña debe tener al menos 6 caracteres.';
        $message_type = 'error';
    } else {
        // --- Si la validación básica pasa, intentar registrar ---
        $hash_contrasena = password_hash($password, PASSWORD_BCRYPT);
        $conn->begin_transaction();
        $stmt_user = null;
        $stmt_role = null;

        try {
            // Insertar usuario
            $sql_user = "INSERT INTO usuarios (nombre, email, hash_contrasena, estado_id) VALUES (?, ?, ?, 1)";
            $stmt_user = $conn->prepare($sql_user);
             if (!$stmt_user) {
                 throw new Exception("Error al preparar la consulta de usuario.");
             }
            $stmt_user->bind_param("sss", $nombre, $email, $hash_contrasena);

            if (!$stmt_user->execute()) {
                if ($conn->errno == 1062) {
                    throw new Exception("El email ya está registrado.");
                } else {
                    throw new Exception("Error al crear el usuario.");
                }
            }
            $user_id = $conn->insert_id;
            $stmt_user->close();

            // Asignar rol de usuario (ID 2)
            $sql_role = "INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (?, 2)";
            $stmt_role = $conn->prepare($sql_role);
            if (!$stmt_role) {
                 throw new Exception("Error al preparar la consulta de rol.");
             }
            $stmt_role->bind_param("i", $user_id);
             if (!$stmt_role->execute()) {
                 throw new Exception("Error al asignar rol.");
             }
            $stmt_role->close();

            $conn->commit();

            // --- Éxito: Guardar mensaje en sesión para login y redirigir ---
            $_SESSION['login_message'] = ['type' => 'success', 'text' => '¡Registro exitoso! Ya puedes iniciar sesión.'];
            header("Location: login.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            // --- Error: Guardar mensaje para mostrar en ESTA página ---
            $message = $e->getMessage();
            $message_type = 'error';
        } finally {
            // Cerrar statements si existen y no fueron cerrados
             if (isset($stmt_user) && $stmt_user instanceof mysqli_stmt && $stmt_user->errno === 0) {
                $stmt_user->close();
             }
             if (isset($stmt_role) && $stmt_role instanceof mysqli_stmt && $stmt_role->errno === 0) {
                 $stmt_role->close();
             }
            // No cerramos la conexión aquí porque el script continúa para mostrar HTML
            // $conn->close(); // <- No cerrar aquí
        }
    }
} // Fin del bloque if ($_SERVER["REQUEST_METHOD"] == "POST")

// --- Redirigir si ya está logueado (Se ejecuta tanto en GET como después de POST si no hubo redirección) ---
if (!empty($_SESSION['user_id'])) {
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        header("Location: php/admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Sistema Deportivo</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-hint { display: block; font-size: 0.85em; color: #666; margin-top: -1rem; margin-bottom: 1.5rem; text-align: left; }
        /* Ocultar mensajes de error/éxito de URL si aún estuvieran */
        .error-message, .success-message { display: none; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Crear Cuenta</h2>

        <?php
        // --- MOSTRAR MENSAJE SI EXISTE (después del procesamiento POST) ---
        if ($message !== null) {
            $icon = ($message_type === 'success') ? '✅' : '❌';
            $alert_message = addslashes($icon . " " . $message); // Escapar para JS
            echo "<script type='text/javascript'>alert('{$alert_message}');</script>";
        }
        ?>

        <form action="registro.php" method="POST">
            <label for="nombre">Nombre Completo:</label>
            <input type="text" id="nombre" name="nombre" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required minlength="6" title="La contraseña debe tener al menos 6 caracteres.">
            <small class="form-hint">Debe tener al menos 6 caracteres.</small>

            <button type="submit">Registrarme</button>
        </form>
        <p class="form-link">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
    </div>
</body>
</html>
<?php
// Cierra la conexión a la base de datos al final si la conexión sigue abierta
if (isset($conn) && $conn instanceof mysqli && $conn->thread_id) {
    $conn->close();
}
?>