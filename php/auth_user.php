<?php
// php/auth_user.php
require_once __DIR__ . '/db_connect.php'; // Asegura que la sesión inicie

// Si no hay user_id en la sesión, redirigir al login
if (!isset($_SESSION['user_id'])) {
    // Guardar la URL a la que intentaba acceder para redirigir después del login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?error=Debes iniciar sesión para acceder a esta página.");
    exit;
}

// Opcional: Podrías añadir más validaciones aquí si tuvieras diferentes roles de usuario
// Por ejemplo, si solo usuarios con rol 2 pueden ver ciertas cosas.
// if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 2) {
//     header("Location: index.php?error=No tienes permiso para ver esta sección.");
//     exit;
// }

// Si llegó hasta aquí, el usuario está autenticado.
?>