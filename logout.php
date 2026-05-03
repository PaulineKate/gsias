<?php 
session_start();

// Update logout_datetime in logs table before destroying session
if (isset($_SESSION['log_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    require_once __DIR__ . '/load_env.php';
    
    $conn = new mysqli(
        $_ENV['DB_HOST'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        $_ENV['DB_NAME']
    );

    if (!$conn->connect_error) {
        $stmt = $conn->prepare("UPDATE logs SET logout_datetime = NOW() WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['log_id']);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
}

session_destroy();
header("Location: login.php");
exit();
?>