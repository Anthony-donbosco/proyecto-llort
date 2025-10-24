<?php
require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    
    session_unset();
    session_destroy();
    
    header("Location: ../../login.php?error=Acceso no autorizado.");
    exit;
}

?>