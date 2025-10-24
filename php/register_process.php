<?php
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($nombre) || empty($email) || empty($password)) {
        header("Location: ../registro.php?error=Por favor complete todos los campos.");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../registro.php?error=El formato del email no es válido.");
        exit;
    }

    if (strlen($password) < 6) {
        header("Location: ../registro.php?error=La contraseña debe tener al menos 6 caracteres.");
        exit;
    }
    $hash_contrasena = password_hash($password, PASSWORD_BCRYPT);

    $conn->begin_transaction();

    try {
        $sql_user = "INSERT INTO usuarios (nombre, email, hash_contrasena, estado_id) VALUES (?, ?, ?, 1)";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("sss", $nombre, $email, $hash_contrasena);
        
        if (!$stmt_user->execute()) {
            if ($conn->errno == 1062) {
                throw new Exception("El email ya está registrado.");
            } else {
                throw new Exception("Error al crear el usuario: " . $stmt_user->error);
            }
        }
        
        $user_id = $conn->insert_id;
        $sql_role = "INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (?, 2)";
        $stmt_role = $conn->prepare($sql_role);
        $stmt_role->bind_param("i", $user_id);
        $stmt_role->execute();

        $conn->commit();
        
        header("Location: ../registro.php?success=¡Registro exitoso! Ya puedes iniciar sesión.");

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ../registro.php?error=" . urlencode($e->getMessage()));
    }
    
    $stmt_user->close();
    $stmt_role->close();
    $conn->close();

} else {
    header("Location: ../registro.php");
    exit;
}
?>