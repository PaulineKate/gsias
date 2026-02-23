<?php
$adminName  = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
$adminRole  = htmlspecialchars($_SESSION['admin_user'] ?? 'Unknown');
$adminImage = $_SESSION['admin_image'] ?? ''; // e.g. assets/admin_image/john.jpg
?>

<link rel="stylesheet" href="./css_files/header.css">

<header class="gsias-header">

    <!-- Left: Clock & Date -->
    <div class="header-datetime">
        <span id="header-time" class="header-time">--:-- --</span>
        <span id="header-date" class="header-date">Loading date...</span>
    </div>

    <!-- Right: User Info -->
    <div class="header-user">
        <div class="header-avatar">
            <div class="header-avatar-container">
                <img src="assets/admin_image/<?= htmlspecialchars($adminImage) ?>"
                     alt="<?= $adminName ?>"
                     class="header-avatar-img">
            </div>
        </div>
        <div class="header-user-info">
            <span class="header-username"><?= $adminName ?></span>
            <span class="header-role"><?= $adminRole ?></span>
        </div>
    </div>

</header>

<script src="./js_files/header.js"></script>