<?php
session_start();

// DB Configuration
$host     = "localhost";
$dbname   = "gsias_db";
$db_user  = "root";
$db_pass  = "pgso";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=1");
        exit();
    }

    // Connect to DB
    $conn = new mysqli($host, $db_user, $db_pass, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch admin by username
    $stmt = $conn->prepare("SELECT admin_name, admin_user, admin_pass FROM admin_creds WHERE admin_user = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($admin_name, $admin_user, $admin_pass_hash);
        $stmt->fetch();

        // Verify password
        if ($admin_pass_hash !== null && password_verify($password, $admin_pass_hash)) {
            // Login successful — set session variables
            $_SESSION["logged_in"]  = true;
            $_SESSION["admin_user"] = $admin_user;
            $_SESSION["admin_name"] = $admin_name;

            header("Location: dashboard.php"); // Change to your actual dashboard page
            exit();
        }
    }

    // If we reach here, login failed
    header("Location: login.php?error=1");
    exit();

    $stmt->close();
    $conn->close();
} else {
    // Direct access without POST — redirect to login
    header("Location: login.php");
    exit();
}
?>