<?php
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: ../login.php?error=Email y contraseña son requeridos.");
        exit;
    }

    $sql = "SELECT u.id, u.nombre, u.hash_contrasena, u.estado_id, ur.rol_id 
            FROM usuarios u
            LEFT JOIN usuario_roles ur ON u.id = ur.usuario_id
            WHERE u.email = ? AND u.estado_id = 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['hash_contrasena'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['role_id'] = $user['rol_id'];

            if ($user['rol_id'] == 1) { 
                header("Location: admin/dashboard.php");
            } else {
                header("Location: ../login.php?error=Rol de usuario aún no implementado."); 
            }
            exit;

        } else {
            header("Location: ../login.php?error=Contraseña incorrecta.");
            exit;
        }
    } else {
        header("Location: ../login.php?error=Usuario no encontrado o está inactivo.");
        exit;
    }
    
    $stmt->close();
    $conn->close();

} else {
    header("Location: ../login.php");
    exit;
}
?>