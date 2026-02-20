<?php
/**
 * sidebar.php — GSIAS Sidebar Component
 * Automatically highlights the active nav item based on the current page filename.
 * Uses local PNG icons saved in assets/icons/
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="../css_files/sidebar.css">

<!-- SIDEBAR -->
<aside class="sidebar">

    <!-- Logo / Branding -->
    <div class="sidebar-brand">
        <div class="sidebar-logo">
            <img src="assets/pgso_logo.png" alt="GSO Logo">
        </div>
        <div class="sidebar-brand-text">
            <span class="brand-sub">GENERAL SERVICES OFFICE</span>
            <span class="brand-title">GENERAL SERVICES<br>INFORMATION<br>AND ARCHIVING<br>SYSTEM (GSIAS)</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <ul>

            <!-- Dashboard -->
            <li class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/dashboard_icon.png" alt="Dashboard">
                    </span>
                    <span class="nav-label">Dashboard</span>
                    <?php if ($currentPage === 'dashboard.php'): ?>
                        <span class="nav-active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- Job Order Contracts -->
            <li class="nav-item <?= $currentPage === 'jo_contracts.php' ? 'active' : '' ?>">
                <a href="jo_contracts.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/jo_contracts_icon.png" alt="Job Order Contracts">
                    </span>
                    <span class="nav-label">Job Order Contracts</span>
                    <?php if ($currentPage === 'jo_contracts.php'): ?>
                        <span class="nav-active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- Account Settings -->
            <li class="nav-item <?= $currentPage === 'account_settings.php' ? 'active' : '' ?>">
                <a href="account_settings.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/acc_settings_icon.png" alt="Account Settings">
                    </span>
                    <span class="nav-label">Account Settings</span>
                    <?php if ($currentPage === 'account_settings.php'): ?>
                        <span class="nav-active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <!-- Log Out -->
            <li class="nav-item <?= $currentPage === 'account_settings.php' ? 'active' : '' ?>">
                <a href="logout.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/acc_settings_icon.png" alt="Account Settings">
                    </span>
                    <span class="nav-label">Log Out</span>
                    <?php if ($currentPage === 'account_settings.php'): ?>
                        <span class="nav-active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <!-- add new nav-item here copy nalang ng li -->
        </ul>
    </nav>

</aside>