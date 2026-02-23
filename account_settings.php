<?php 
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

$admin_user = $_SESSION['admin_user']; 

// Fetch admin data
$stmt = $conn->prepare("SELECT `admin_name`, `admin_user`, `admin_pass`, `admin_image` FROM `admin_creds` WHERE `admin_user` = ?");
$stmt->execute([$admin_user]);
$admin = $stmt->fetch();

$success_msg = "";
$error_msg   = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name     = trim($_POST['full_name']);
    $new_user     = trim($_POST['username']);
    $new_pass     = trim($_POST['password']);
    $confirm_pass = trim($_POST['confirm_password']);
    $has_new_image = !empty($_FILES['profile_image']['name']);

    // Check if nothing changed
    $name_unchanged  = $new_name === $admin['admin_name'];
    $user_unchanged  = $new_user === $admin['admin_user'];
    $pass_unchanged  = empty($new_pass);
    $image_unchanged = !$has_new_image;

    if ($name_unchanged && $user_unchanged && $pass_unchanged && $image_unchanged) {
        $no_change = true;
    } else {
        $image_name = $admin['admin_image'];
        if ($has_new_image) {
            $upload_dir = 'assets/admin_image/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            // Delete old image if it exists
            if (!empty($admin['admin_image']) && file_exists($upload_dir . $admin['admin_image'])) {
                unlink($upload_dir . $admin['admin_image']);
            }

            $ext        = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('admin_') . '.' . $ext;
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $image_name);
        }

        if (!empty($new_pass) && $new_pass !== $confirm_pass) {
            $error_msg = "Passwords do not match.";
        } else {
            try {
                if (!empty($new_pass)) {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE `admin_creds` SET `admin_name` = ?, `admin_user` = ?, `admin_pass` = ?, `admin_image` = ? WHERE `admin_user` = ?");
                    $upd->execute([$new_name, $new_user, $hashed, $image_name, $admin_user]);
                } else {
                    $upd = $conn->prepare("UPDATE `admin_creds` SET `admin_name` = ?, `admin_user` = ?, `admin_image` = ? WHERE `admin_user` = ?");
                    $upd->execute([$new_name, $new_user, $image_name, $admin_user]);
                }

                $_SESSION['admin_user'] = $new_user;
                $admin_user = $new_user;

                $stmt2 = $conn->prepare("SELECT `admin_name`, `admin_user`, `admin_pass`, `admin_image` FROM `admin_creds` WHERE `admin_user` = ?");
                $stmt2->execute([$admin_user]);
                $admin = $stmt2->fetch();

                $success_msg = "Changes saved successfully.";

            } catch (PDOException $e) {
                $error_msg = "Something went wrong: " . htmlspecialchars($e->getMessage());
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
    <title>GSIAS — Account Settings</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/account_settings.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green-dark:    #1a3d1f;
            --green-mid:     #2a5c30;
            --green-light:   #e8f5e9;
            --green-content: #d4edda;
            --sidebar-width: 220px;
            --header-height: 56px;
        }
        html, body { height: 100%; font-family: 'Source Sans 3', sans-serif; background: var(--green-light); color: #1a2e1c; }
        .app-shell  { display: flex; height: 100vh; overflow: hidden; }
        .app-sidebar { width: var(--sidebar-width); flex-shrink: 0; height: 100vh; overflow-y: auto; overflow-x: hidden; }
        .app-right  { flex: 1; display: flex; flex-direction: column; min-width: 0; overflow: hidden; }
        .app-header { flex-shrink: 0; }
        @media (max-width: 768px) { :root { --sidebar-width: 64px; } }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <?php include 'reusable_files/sidebar.php'; ?>
    </aside>
    <div class="app-right">
        <div class="app-header">
            <?php include 'reusable_files/header.php'; ?>
        </div>
        <!-- MAIN CONTENT -->
        <main class="main-content">

            <div class="as-wrapper">
                <h2 class="as-page-title">ACCOUNT SETTINGS</h2>

                <div class="as-card">
                    <h3 class="as-card-title">ACCOUNT DETAILS</h3>

                    <?php if (!empty($no_change)): ?>
                        <span id="asNoChangeFlag" style="display:none;"></span>
                    <?php endif; ?>
                    <?php if ($success_msg): ?>
                        <span id="asSuccessFlag" style="display:none;"></span>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="as-alert as-alert--error"><?= htmlspecialchars($error_msg) ?></div>
                    <?php endif; ?>

                    <form action="account_settings.php" method="POST" enctype="multipart/form-data" id="accountForm">

                        <div class="as-avatar-wrap">
                            <div class="as-avatar">
                                <?php if (!empty($admin['admin_image']) && file_exists('assets/admin_image/' . $admin['admin_image'])): ?>
                                    <img src="assets/admin_image/<?= htmlspecialchars($admin['admin_image']) ?>" alt="Profile" id="avatarPreview">
                                <?php else: ?>
                                    <img src="img/default_avatar.png" alt="Profile" id="avatarPreview">
                                <?php endif; ?>
                            </div>
                            <label class="as-avatar-edit" for="profile_image" title="Change photo">
                                <img src="assets/icons/add_image_icon.png" alt="Edit">
                            </label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" hidden>
                        </div>
                        <div class="as-fields">
                            <div class="as-field-row">
                                <label class="as-label" for="full_name">FULL NAME:</label>
                                <input class="as-input" type="text" id="full_name" name="full_name"
                                       value="<?= htmlspecialchars($admin['admin_name']) ?>" required>
                            </div>
                            <div class="as-field-row">
                                <label class="as-label" for="username">USERNAME:</label>
                                <input class="as-input" type="text" id="username" name="username"
                                       value="<?= htmlspecialchars($admin['admin_user']) ?>" required>
                            </div>
                            <div class="as-field-row">
                                <label class="as-label" for="password">PASSWORD:</label>
                                <input class="as-input" type="password" id="password" name="password"
                                       placeholder="Leave blank to keep current">
                            </div>
                            <div class="as-field-row">
                                <label class="as-label" for="confirm_password">CONFIRM PASSWORD:</label>
                                <input class="as-input" type="password" id="confirm_password" name="confirm_password"
                                       placeholder="Leave blank to keep current">
                            </div>
                        </div>

                        <div class="as-btn-row">
                            <button type="submit" class="as-save-btn">SAVE CHANGES</button>
                        </div>

                    </form>
                </div>
            </div>
        </main>

    </div>
</div>
<script src="js_files/account_settings.js"></script>
</body>
</html>