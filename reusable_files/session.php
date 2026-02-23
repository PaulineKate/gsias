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

// ── Fetch admin image if not yet in session ───────────────────────────────────
if (!isset($_SESSION['admin_image'])) {
    // Only include db_connect if $conn isn't already available on this page
    if (!isset($conn)) {
        include 'reusable_files/db_connect.php';
    }
    try {
        $stmt = $conn->prepare(
            "SELECT `admin_image` FROM `admin_creds`
             WHERE  `admin_user` = :user
             LIMIT  1"
        );
        $stmt->execute([':user' => $_SESSION['admin_user']]);
        $row = $stmt->fetch();
        $_SESSION['admin_image'] = $row['admin_image'] ?? '';
    } catch (PDOException $e) {
        $_SESSION['admin_image'] = '';
    }
}