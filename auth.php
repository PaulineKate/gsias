<?php
session_start();
require_once __DIR__ . '/load_env.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=1");
        exit();
    }

    $conn = new mysqli(
        $_ENV['DB_HOST'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        $_ENV['DB_NAME']
    );

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT admin_name, admin_user, admin_pass FROM admin_creds WHERE admin_user = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($admin_name, $admin_user, $admin_pass_hash);
        $stmt->fetch();

        if ($admin_pass_hash !== null && password_verify($password, $admin_pass_hash)) {
            $_SESSION["logged_in"]  = true;
            $_SESSION["admin_user"] = $admin_user;
            $_SESSION["admin_name"] = $admin_name;

            $stmt->close();
            $conn->close();

            header("Location: dashboard.php");
            exit();
        }
    }

    $stmt->close();
    $conn->close();

    header("Location: login.php?error=1");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>
