<?php

require_once __DIR__ . '/db_connect.php'; 


if (!isset($_SESSION['user_id'])) {
    
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?error=Debes iniciar sesión para acceder a esta página.");
    exit;
}









?>