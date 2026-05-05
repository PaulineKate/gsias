<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css_files/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <title>GSIAS - Change Password</title>
    <style>
        .sub-text {
            font-size: 13px;
            color: #3a5a3a;
            margin-bottom: 20px;
            font-family: 'Open Sans', sans-serif;
        }
        .password-hint {
            font-size: 11px;
            color: #5a7a5a;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<?php
session_start();
require_once __DIR__ . '/load_env.php';

// Must be logged in to access this page
if (!isset($_SESSION["logged_in"]) || !$_SESSION["logged_in"]) {
    header("Location: login.php");
    exit();
}

$message     = '';
$messageType = '';
$success     = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password     = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($new_password) || empty($confirm_password)) {
        $message     = "All fields are required.";
        $messageType = "danger";
    } elseif (strlen($new_password) < 8) {
        $message     = "Password must be at least 8 characters long.";
        $messageType = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message     = "Passwords do not match.";
        $messageType = "danger";
    } else {
        $conn = new mysqli(
            $_ENV['DB_HOST'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            $_ENV['DB_NAME']
        );

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $admin_user = $_SESSION["admin_user"];

        // Update password and clear the temp_password flag
        $stmt = $conn->prepare("UPDATE admin_creds SET admin_pass = ?, temp_password = 0 WHERE admin_user = ?");
        $stmt->bind_param("ss", $new_hash, $admin_user);

        if ($stmt->execute()) {
            $_SESSION["temp_password"] = false;
            $message     = "Password changed successfully! Redirecting to dashboard...";
            $messageType = "success";
            $success     = true;
        } else {
            $message     = "An error occurred. Please try again.";
            $messageType = "danger";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

    <div class="bg-overlay"></div>

    <div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
        <div class="login-card">
            <div class="row g-0 align-items-center">

                <div class="col-md-5">
                    <div class="left-panel text-center">
                        <div class="logo-wrapper mx-auto mb-3">
                            <img src="assets/pgso_logo.png" alt="PGSO Logo" class="logo-img">
                        </div>
                        <h2 class="system-title">GENERAL SERVICES INFORMATION AND ARCHIVING SYSTEM (GSIAS)</h2>
                        <p class="office-name">GENERAL SERVICE OFFICE</p>
                        <p class="province-name">PROVINCE OF CAMARINES NORTE</p>
                    </div>
                </div>

                <div class="col-auto d-none d-md-flex">
                    <div class="vertical-divider"></div>
                </div>

                <div class="col-md-6">
                    <div class="right-panel">
                        <h1 class="login-heading" style="font-size:30px;">CHANGE<br>PASSWORD</h1>
                        <p class="sub-text">You are using a temporary password. Please set a new password to continue.</p>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= htmlspecialchars($messageType) ?> py-2">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$success): ?>
                        <form action="reset_password.php" method="POST">
                            <div class="mb-3">
                                <label for="new_password" class="form-label field-label">New Password</label>
                                <div class="pass-wrap">
                                    <input type="password" class="form-control login-input" id="new_password"
                                        name="new_password" required minlength="8">
                                    <button type="button" class="pass-toggle" id="toggleNewPass" aria-label="Toggle new password visibility">
                                        <img src="assets/icons/password_invisible_icon.png" id="eyeNewPassIcon" alt="Show password">
                                    </button>
                                </div>
                                <p class="password-hint">Minimum 8 characters.</p>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label field-label">Confirm Password</label>
                                <div class="pass-wrap">
                                    <input type="password" class="form-control login-input" id="confirm_password"
                                        name="confirm_password" required minlength="8">
                                    <button type="button" class="pass-toggle" id="toggleConfirmPass" aria-label="Toggle confirm password visibility">
                                        <img src="assets/icons/password_invisible_icon.png" id="eyeConfirmIcon" alt="Show password">
                                    </button>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn login-btn">SAVE NEW PASSWORD</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <script>
                                setTimeout(() => { window.location.href = 'dashboard.php'; }, 2000);
                            </script>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="js_files/reset_password.js"></script>
</body>
</html>