<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$username = htmlspecialchars($_SESSION['admin_name'] ?? 'Unknown');
$userRole = htmlspecialchars($_SESSION['admin_user'] ?? 'Unknown');
?>