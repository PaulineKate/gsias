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

    // Fetch user including temp_password flag
    $stmt = $conn->prepare("
        SELECT admin_name, gmail_account, admin_user, user_level, admin_image, admin_pass, temp_password
        FROM admin_creds
        WHERE admin_user = ?
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($admin_name, $gmail_account, $admin_user, $user_level, $admin_image, $admin_pass_hash, $temp_password_flag);
        $stmt->fetch();

        if ($admin_pass_hash !== null && password_verify($password, $admin_pass_hash)) {
            // Set session variables
            $_SESSION["logged_in"]        = true;
            $_SESSION["admin_user"]        = $admin_user;
            $_SESSION["admin_name"]        = $admin_name;
            $_SESSION["gmail_account"]     = $gmail_account;
            $_SESSION["user_level"]        = $user_level;
            $_SESSION["admin_image"]       = $admin_image;
            $_SESSION["temp_password"]     = (bool)$temp_password_flag;

            $stmt->close();

            // Log the login
            $log_stmt = $conn->prepare("INSERT INTO logs (gmail_account, name, login_datetime, logout_datetime, user_level) VALUES (?, ?, NOW(), NULL, ?)");
            $log_stmt->bind_param("sss", $gmail_account, $admin_name, $user_level);
            $log_stmt->execute();
            $_SESSION["log_id"] = $conn->insert_id;
            $log_stmt->close();
            $conn->close();

            // Redirect to password reset page if using a temp password
            if ($temp_password_flag) {
                header("Location: reset_password.php");
                exit();
            }

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