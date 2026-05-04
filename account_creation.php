<?php
session_start();
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/load_env.php';

$success_msg = "";
$error_msg   = "";

if (isset($_POST['action']) && $_POST['action'] === 'send_code') {
    header('Content-Type: application/json');

    $gmail = trim($_POST['gmail'] ?? '');
    $name  = trim($_POST['name']  ?? 'User');

    if (empty($gmail) || !filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit();
    }

    // Generate 6-digit code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Store in session with 10-minute expiry
    $_SESSION['verify_code']        = $code;
    $_SESSION['verify_gmail']       = $gmail;
    $_SESSION['verify_code_expiry'] = time() + 600;
    $_SESSION['verify_confirmed']   = false;

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
        $mail->addAddress($gmail, $name);

        $mail->isHTML(true);
        $mail->Subject = 'GSIAS – Email Verification Code';
        $mail->Body    = "
            <div style='font-family:Open Sans,sans-serif;color:#1a3a1a;max-width:480px;'>
                <h2 style='color:#0E4B12;'>GSIAS Email Verification</h2>
                <p>Hello, <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>Use the code below to verify your email address for your new GSIAS account:</p>
                <p style='font-size:36px;font-weight:bold;letter-spacing:8px;color:#0E4B12;
                          background:#e8f5e8;padding:16px 24px;border-radius:8px;
                          display:inline-block;margin:12px 0;'>{$code}</p>
                <p style='color:#555;'>This code expires in <strong>10 minutes</strong>.</p>
                <p style='font-size:12px;color:#888;margin-top:24px;'>
                    If you did not request this, please ignore this email.
                </p>
            </div>
        ";
        $mail->AltBody = "Your GSIAS verification code is: {$code}\nThis code expires in 10 minutes.";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Verification code sent to ' . htmlspecialchars($gmail)]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Could not send email: ' . htmlspecialchars($mail->ErrorInfo)]);
    }
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'verify_code') {
    header('Content-Type: application/json');

    $entered = trim($_POST['code']  ?? '');
    $gmail   = trim($_POST['gmail'] ?? '');

    if (empty($_SESSION['verify_code']) || empty($_SESSION['verify_code_expiry'])) {
        echo json_encode(['success' => false, 'message' => 'No verification code found. Please request a new one.']);
        exit();
    }

    if (time() > $_SESSION['verify_code_expiry']) {
        unset($_SESSION['verify_code'], $_SESSION['verify_code_expiry'], $_SESSION['verify_gmail']);
        echo json_encode(['success' => false, 'message' => 'Code has expired. Please request a new one.']);
        exit();
    }

    if ($entered !== $_SESSION['verify_code'] || $gmail !== $_SESSION['verify_gmail']) {
        echo json_encode(['success' => false, 'message' => 'Incorrect code. Please try again.']);
        exit();
    }

    $_SESSION['verify_confirmed'] = true;
    echo json_encode(['success' => true, 'message' => 'Email verified successfully!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {

    $new_name   = trim($_POST['full_name']        ?? '');
    $new_gmail  = trim($_POST['gmail_account']    ?? '');
    $new_user   = trim($_POST['username']         ?? '');
    $new_pass   = trim($_POST['password']         ?? '');
    $conf_pass  = trim($_POST['confirm_password'] ?? '');
    $user_level = trim($_POST['user_level']       ?? '');

    // ── Basic validation ──────────────────────────────────
    if (empty($new_name) || empty($new_user) || empty($new_pass) || empty($conf_pass)) {
        $error_msg = "Please fill in all required fields.";

    } elseif (mb_strlen($new_name) > 100) {
        $error_msg = "Full name is too long. Please keep it under 100 characters.";

    } elseif (mb_strlen($new_user) > 50) {
        $error_msg = "Username is too long. Please keep it under 50 characters.";

    } elseif (empty($new_gmail)) {
        $error_msg = "Email address is required.";

    } elseif (!filter_var($new_gmail, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";

    } elseif (mb_strlen($new_gmail) > 100) {
        $error_msg = "Email address is too long (max 100 characters).";

    } elseif (strlen($new_pass) < 8) {
        $error_msg = "Password must be at least 8 characters long.";

    } elseif ($new_pass !== $conf_pass) {
        $error_msg = "Passwords do not match.";

    } elseif (empty($_SESSION['verify_confirmed']) || !$_SESSION['verify_confirmed']) {
        $error_msg = "Please verify your email address before creating the account.";

    } elseif ($_SESSION['verify_gmail'] !== $new_gmail) {
        $error_msg = "Verified email does not match the entered email. Please re-verify.";
    }

    if (empty($error_msg)) {
        // Check for duplicate username
        $check = $conn->prepare("SELECT COUNT(*) FROM `admin_creds` WHERE `admin_user` = ?");
        $check->execute([$new_user]);
        if ($check->fetchColumn() > 0) {
            $error_msg = "Username already exists. Please choose a different one.";
        }
    }

    if (empty($error_msg)) {
        // Check for duplicate email
        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM `admin_creds` WHERE `gmail_account` = ?");
        $checkEmail->execute([$new_gmail]);
        if ($checkEmail->fetchColumn() > 0) {
            $error_msg = "This email address is already registered.";
        }
    }

    if (empty($error_msg)) {
        try {
            // Handle optional profile image upload
            $image_name = '';
            if (!empty($_FILES['profile_image']['name'])) {
                $upload_dir = 'assets/admin_image/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type     = mime_content_type($_FILES['profile_image']['tmp_name']);

                if (!in_array($file_type, $allowed_types)) {
                    $error_msg = "Invalid image type. Only JPG, PNG, GIF, and WEBP are allowed.";
                } elseif ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                    $error_msg = "Profile image must be under 2MB.";
                } else {
                    $ext        = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $image_name = uniqid('admin_') . '.' . strtolower($ext);
                    move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $image_name);
                }
            }

            if (empty($error_msg)) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

                $ins = $conn->prepare(
                    "INSERT INTO `admin_creds`
                        (`admin_name`, `gmail_account`, `admin_user`, `admin_pass`, `user_level`, `admin_image`)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $ins->execute([$new_name, $new_gmail, $new_user, $hashed, $user_level, $image_name]);

                // Clear verification session data
                unset(
                    $_SESSION['verify_code'],
                    $_SESSION['verify_code_expiry'],
                    $_SESSION['verify_gmail'],
                    $_SESSION['verify_confirmed']
                );

                $success_msg = "Account created successfully.";
            }

        } catch (PDOException $e) {
            $code = $e->getCode();
            $msg  = $e->getMessage();

            if ($code == '22001' || strpos($msg, 'Data too long') !== false) {
                // Identify which column caused the overflow
                if (strpos($msg, 'admin_name') !== false) {
                    $error_msg = "Full name is too long. Please shorten it (max 100 characters).";
                } elseif (strpos($msg, 'gmail_account') !== false) {
                    $error_msg = "Email address is too long (max 100 characters).";
                } elseif (strpos($msg, 'admin_user') !== false) {
                    $error_msg = "Username is too long. Please shorten it (max 50 characters).";
                } elseif (strpos($msg, 'admin_pass') !== false) {
                    $error_msg = "An error occurred saving the password. Please try again.";
                } else {
                    $error_msg = "One of the fields is too long. Please shorten your input and try again.";
                }

            } elseif ($code == '23000' || strpos($msg, 'Duplicate entry') !== false) {
                if (strpos($msg, 'gmail_account') !== false) {
                    $error_msg = "This email address is already registered.";
                } elseif (strpos($msg, 'admin_user') !== false) {
                    $error_msg = "Username already exists. Please choose a different one.";
                } else {
                    $error_msg = "A duplicate entry was detected. Please check your details.";
                }

            } elseif ($code == '42S02' || strpos($msg, "doesn't exist") !== false) {
                $error_msg = "A database configuration error occurred. Please contact the administrator.";

            } elseif ($code == '42000' || strpos($msg, 'Access denied') !== false) {
                $error_msg = "Database access error. Please contact the administrator.";

            } else {
                // Generic fallback — log full error server-side, show safe message to user
                error_log('[account_creation] PDOException: ' . $msg);
                $error_msg = "Something went wrong while creating the account. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — Create Account</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/account_creation.css">
</head>
<body>
<div class="app-shell">

    <!-- Sidebar -->
    <aside class="app-sidebar">
        <?php include 'reusable_files/sidebar.php'; ?>
    </aside>

    <div class="app-right">

        <!-- Header -->
        <div class="app-header">
            <?php include 'reusable_files/header.php'; ?>
        </div>

        <!-- Main content -->
        <main class="main-content">
            <div class="ac-wrapper">
                <h2 class="ac-page-title">CREATE ACCOUNT</h2>

                <div class="ac-card">
                    <h3 class="ac-card-title">NEW ACCOUNT DETAILS</h3>

                    <?php if ($error_msg): ?>
                        <div class="ac-alert ac-alert--error">
                            <?= htmlspecialchars($error_msg) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_msg): ?>
                        <!-- JS will replace this hidden span with a visible alert and redirect -->
                        <span id="acSuccessFlag" style="display:none;"></span>
                    <?php endif; ?>

                    <form action="account_creation.php" method="POST" enctype="multipart/form-data" id="createForm">

                        <div class="ac-avatar-wrap">
                            <div class="ac-avatar">
                                <img src="img/default_avatar.png" alt="Profile" id="avatarPreview">
                            </div>
                            <label class="ac-avatar-edit" for="profile_image" title="Upload photo"></label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" hidden>
                            <p class="ac-avatar-hint">Optional</p>
                        </div>

                        <div class="ac-fields">

                            <!-- Full name -->
                            <div class="ac-field-row">
                                <label class="ac-label" for="full_name">
                                    FULL NAME: <span class="ac-required">*</span>
                                </label>
                                <input class="ac-input" type="text" id="full_name" name="full_name"
                                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                                       placeholder="Enter full name"
                                       maxlength="100" required>
                            </div>

                            <!-- Email with verify button -->
                            <div class="ac-field-row">
                                <label class="ac-label" for="gmail_account">
                                    EMAIL: <span class="ac-required">*</span>
                                </label>
                                <div class="ac-email-row">
                                    <input class="ac-input" type="email" id="gmail_account" name="gmail_account"
                                           value="<?= htmlspecialchars($_POST['gmail_account'] ?? '') ?>"
                                           placeholder="Enter email address"
                                           maxlength="100" autocomplete="off" required>
                                    <button type="button" class="ac-verify-btn" id="sendCodeBtn">VERIFY</button>
                                </div>

                                <!-- Verified badge (shown after successful OTP) -->
                                <div class="ac-verified-badge" id="verifiedBadge">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    Email verified
                                </div>

                                <input type="hidden" id="verifiedEmail" name="verified_email" value="">
                            </div>

                            <!-- Username -->
                            <div class="ac-field-row">
                                <label class="ac-label" for="username">
                                    USERNAME: <span class="ac-required">*</span>
                                </label>
                                <input class="ac-input" type="text" id="username" name="username"
                                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                       placeholder="Enter username"
                                       maxlength="50" required>
                            </div>

                            <!-- User level -->
                            <div class="ac-field-row">
                                <label class="ac-label" for="user_level">
                                    USER LEVEL: <span class="ac-required">*</span>
                                </label>
                                <div class="ac-select-wrap">
                                    <select class="ac-input ac-select" id="user_level" name="user_level" required>
                                        <option value="" disabled <?= empty($_POST['user_level']) ? 'selected' : '' ?>>
                                            Select level
                                        </option>
                                        <option value="user"  <?= (($_POST['user_level'] ?? '') === 'user')  ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= (($_POST['user_level'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <span class="ac-select-arrow">&#9660;</span>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="ac-field-row">
                                <label class="ac-label" for="password">
                                    PASSWORD: <span class="ac-required">*</span>
                                </label>
                                <div class="ac-pass-wrap">
                                    <input class="ac-input" type="password" id="password" name="password"
                                           placeholder="Enter password (min. 8 characters)"
                                           minlength="8" required>
                                    <button type="button" class="ac-pass-toggle" id="togglePass"
                                            aria-label="Toggle password visibility">
                                        <img src="assets/icons/password_invisible_icon.png" id="eyePassIcon" alt="Show password">
                                    </button>
                                </div>
                            </div>

                            <!-- Confirm password -->
                            <div class="ac-field-row">
                                <label class="ac-label" for="confirm_password">
                                    CONFIRM PASSWORD: <span class="ac-required">*</span>
                                </label>
                                <div class="ac-pass-wrap">
                                    <input class="ac-input" type="password" id="confirm_password" name="confirm_password"
                                           placeholder="Re-enter password"
                                           minlength="8" required>
                                    <button type="button" class="ac-pass-toggle" id="toggleConfirmPass"
                                            aria-label="Toggle confirm password visibility">
                                        <img src="assets/icons/password_invisible_icon.png" id="eyeConfirmIcon" alt="Show password">
                                    </button>
                                </div>
                            </div>

                        </div>

                        <div class="ac-btn-row">
                            <a href="dashboard.php" class="ac-cancel-btn">CANCEL</a>
                            <button type="submit" class="ac-save-btn">CREATE ACCOUNT</button>
                        </div>

                    </form>
                </div>
            </div>
        </main>

    </div>
</div>

<div class="ac-modal-overlay" id="verifyModal">
    <div class="ac-modal">

        <div class="ac-modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
        </div>

        <h3>CHECK YOUR EMAIL</h3>
        <p>A 6-digit code was sent to<br><strong id="modalEmailDisplay"></strong></p>

        <div class="ac-code-inputs" id="codeInputs">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        </div>

        <div class="ac-modal-error" id="codeError"></div>

        <div class="ac-modal-actions">
            <button type="button" class="ac-modal-cancel" id="modalCancelBtn">CANCEL</button>
            <button type="button" class="ac-modal-confirm" id="modalConfirmBtn">CONFIRM</button>
        </div>

        <a class="ac-resend-link" id="resendLink">Didn't receive it? Resend code</a>
        <div class="ac-countdown" id="countdownDisplay"></div>

    </div>
</div>

<script src="js_files/account_creation.js"></script>
</body>
</html>