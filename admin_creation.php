<?php

// DB Configuration
$host = "localhost";
$dbname = "gsias_db";
$db_user = "root";
$db_pass = "pgso";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $admin_name = trim($_POST["admin_name"]);
    $admin_user = trim($_POST["admin_user"]);
    $admin_pass = $_POST["admin_pass"];
    $confirm_pass = $_POST["confirm_pass"];

    // Basic validation
    if (empty($admin_name) || empty($admin_user) || empty($admin_pass)) {
        die("All fields are required.");
    }

    if ($admin_pass !== $confirm_pass) {
        die("Passwords do not match.");
    }

    // Hash the password
    $hashed_pass = password_hash($admin_pass, PASSWORD_BCRYPT);

    // Connect to DB
    $conn = new mysqli($host, $db_user, $db_pass, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert using prepared statement
    $stmt = $conn->prepare("INSERT INTO `admin_creds` (`admin_name`, `admin_user`, `admin_pass`) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $admin_name, $admin_user, $hashed_pass);

    if ($stmt->execute()) {
        echo "Admin account created successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account</title>
</head>
<body>

    <h2>Create Admin Account</h2>

    <form method="POST" action="admin_creation.php">
        <label for="admin_name">Full Name:</label><br>
        <input type="text" id="admin_name" name="admin_name" required><br><br>

        <label for="admin_user">Username:</label><br>
        <input type="text" id="admin_user" name="admin_user" required><br><br>

        <label for="admin_pass">Password:</label><br>
        <input type="password" id="admin_pass" name="admin_pass" required><br><br>

        <label for="confirm_pass">Confirm Password:</label><br>
        <input type="password" id="confirm_pass" name="confirm_pass" required><br><br>

        <button type="submit">Create Account</button>
    </form>

</body>
</html>