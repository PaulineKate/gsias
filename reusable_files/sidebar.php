<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Pages that belong under the Settings dropdown
$settingsPages = ['account_settings.php', 'general_settings.php'];
$settingsActive = in_array($currentPage, $settingsPages);
?>

<link rel="stylesheet" href="../css_files/sidebar.css">

<aside class="sidebar">

    <div class="sidebar-brand">
        <div class="sidebar-logo">
            <img src="assets/pgso_logo.png" alt="GSO Logo">
        </div>
        <div class="sidebar-brand-text">
            <span class="brand-sub">GENERAL SERVICES OFFICE</span>
            <span class="brand-title">INFORMATION<br>AND ARCHIVING<br>SYSTEM (GSIAS)</span>
        </div>
    </div>

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
            <li class="nav-item <?= in_array($currentPage, ['jo_contracts.php', 'jo_contract_reg.php']) ? 'active' : '' ?>">
                <a href="jo_contracts.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/jo_contracts_icon.png" alt="Job Order Contracts">
                    </span>
                    <span class="nav-label">Job Order Contracts</span>
                    <?php if (in_array($currentPage, ['jo_contracts.php', 'jo_contract_reg.php'])): ?>
                        <span class="nav-active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- Settings (Dropdown) -->
            <li class="nav-item has-dropdown <?= $settingsActive ? 'open parent-active' : '' ?>"
                id="settings-dropdown">
                <a href="#" class="nav-link" onclick="toggleDropdown(event, 'settings-dropdown')">
                    <span class="nav-icon">
                        <img src="assets/icons/settings_icon.png" alt="Settings">
                    </span>
                    <span class="nav-label">Settings</span>
                </a>

                <ul class="nav-dropdown">
                    <li class="nav-sub-item <?= $currentPage === 'account_settings.php' ? 'active' : '' ?>">
                        <a href="account_settings.php" class="nav-sub-link">
                            Account Settings
                            <?php if ($currentPage === 'account_settings.php'): ?>
                                <span class="nav-sub-active-bar"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-sub-item <?= $currentPage === 'general_settings.php' ? 'active' : '' ?>">
                        <a href="general_settings.php" class="nav-sub-link">
                            General Settings
                            <?php if ($currentPage === 'general_settings.php'): ?>
                                <span class="nav-sub-active-bar"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Log Out -->
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/logout_icon.png" alt="Log Out">
                    </span>
                    <span class="nav-label">Log Out</span>
                </a>
            </li>

        </ul>
    </nav>

</aside>

<script>
function toggleDropdown(e, id) {
    e.preventDefault();
    const item = document.getElementById(id);
    item.classList.toggle('open');
}
</script>