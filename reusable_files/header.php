<?php
$username = isset($username) ? htmlspecialchars($username) : 'Username';
$userRole = isset($userRole) ? htmlspecialchars($userRole) : 'User Role';
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
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </div>
        <div class="header-user-info">
            <span class="header-username"><?= $username ?></span>
            <span class="header-role"><?= $userRole ?></span>
        </div>
    </div>

</header>

<script src="./js_files/header.js"></script>