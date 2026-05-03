<?php
// PHPMailer use statements MUST be at the very top, before any HTML or logic
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/load_env.php';

$message     = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);

    if (empty($username)) {
        $message     = "Please enter your username.";
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

        $stmt = $conn->prepare("SELECT admin_name, gmail_account FROM admin_creds WHERE admin_user = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($admin_name, $gmail_account);
            $stmt->fetch();
            $stmt->close();

            // Generate a random 10-character temporary password
            $temp_password      = bin2hex(random_bytes(5));
            $temp_password_hash = password_hash($temp_password, PASSWORD_BCRYPT);

            // Save hashed temp password and set temp_password flag = 1
            $update = $conn->prepare("UPDATE admin_creds SET admin_pass = ?, temp_password = 1 WHERE admin_user = ?");
            $update->bind_param("ss", $temp_password_hash, $username);
            $update->execute();
            $update->close();
            $conn->close();

            // Send email via PHPMailer + Gmail SMTP
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['MAIL_USER'];
                $mail->Password   = $_ENV['MAIL_PASS'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom($_ENV['MAIL_USER'], 'GSIAS System');
                $mail->addAddress($gmail_account, $admin_name);

                $mail->isHTML(true);
                $mail->Subject = 'GSIAS - Your Temporary Password';
                $mail->Body    = "
                    <div style='font-family:Open Sans,sans-serif;color:#1a3a1a;max-width:480px;'>
                        <h2 style='color:#0E4B12;'>GSIAS Password Reset</h2>
                        <p>Hello, <strong>" . htmlspecialchars($admin_name) . "</strong>,</p>
                        <p>A temporary password has been generated for your account:</p>
                        <p style='font-size:24px;font-weight:bold;letter-spacing:4px;color:#0E4B12;background:#e8f5e8;padding:12px 20px;border-radius:8px;display:inline-block;'>" . htmlspecialchars($temp_password) . "</p>
                        <p style='margin-top:16px;'>Use this to log in, then <strong>change your password immediately</strong> when prompted.</p>
                        <p style='font-size:12px;color:#888;margin-top:24px;'>If you did not request this, please contact the system administrator immediately.</p>
                    </div>
                ";
                $mail->AltBody = "Hello " . $admin_name . ",\n\nYour temporary password is: " . $temp_password . "\n\nPlease log in and change it immediately.";

                $mail->send();

                $message     = "A temporary password has been sent to the email address linked to this account.";
                $messageType = "success";

            } catch (Exception $e) {
                $message     = "Email could not be sent. Please contact the administrator. (Error: " . htmlspecialchars($mail->ErrorInfo) . ")";
                $messageType = "danger";
            }

        } else {
            $stmt->close();
            $conn->close();
            $message     = "If that username exists in the system, a temporary password will be sent to its linked email.";
            $messageType = "info";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css_files/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <title>GSIAS - Forgot Password</title>
    <style>
        .back-link {
            display: inline-block;
            font-size: 12px;
            color: var(--mid-green, #2d6a2d);
            text-decoration: none;
            font-family: 'Open Sans', sans-serif;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .back-link:hover { text-decoration: underline; color: var(--dark-green, #1a4a1a); }
        .sub-text {
            font-size: 13px;
            color: #3a5a3a;
            margin-bottom: 20px;
            font-family: 'Open Sans', sans-serif;
        }
    </style>
</head>
<body>

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
                        <a href="login.php" class="back-link">← Back to Login</a>
                        <h1 class="login-heading" style="font-size:34px;">FORGOT<br>PASSWORD</h1>
                        <p class="sub-text">Enter your username and a temporary password will be sent to your registered Gmail account.</p>

                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= htmlspecialchars($messageType) ?> py-2">
                                <?= htmlspecialchars($message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($messageType !== 'success'): ?>
                        <form action="forgot_password.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label field-label">Username</label>
                                <input type="text" class="form-control login-input" id="username" name="username" required autocomplete="username">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn login-btn">SEND TEMPORARY PASSWORD</button>
                            </div>
                        </form>
                        <?php else: ?>
                            <a href="login.php" class="btn login-btn d-block text-center text-decoration-none mt-2">GO TO LOGIN</a>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>