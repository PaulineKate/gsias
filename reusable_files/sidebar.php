<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Pages that belong under the Settings dropdown
$settingsPages = ['account_settings.php', 'general_settings.php'];
$payrollPages = ['regular_employee_payroll.php', 'payment_index_add.php', 'regular_employee_payroll_add.php', 'view_excel.php'];
$settingsActive = in_array($currentPage, $settingsPages);
$payrollActive = in_array($currentPage, $payrollPages);

// User level from session (already set and sanitized in the including page)
$isAdmin = isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin';
$isUser  = isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'user';
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

            <!-- Dashboard — visible to both roles -->
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

            <!-- Regular Employee List — admin only -->
            <?php if ($isAdmin): ?>
            <li class="nav-item <?= in_array($currentPage, ['regular_employees.php', 'regular_employees_inactive_list.php']) ? 'active' : '' ?>">
                <a href="regular_employees.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/employee_list_icon.png" alt="Regular Employee">
                    </span>
                    <span class="nav-label">Regular Employee List</span>
                    <?php if (in_array($currentPage, ['regular_employees.php', 'regular_employees_inactive_list.php'])): ?>
                        <span class="nav-active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- Casual Employee List — admin only -->
            <?php if ($isAdmin): ?>
            <li class="nav-item <?= in_array($currentPage, ['casual_employees.php']) ? 'active' : '' ?>">
                <a href="casual_employees.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/casual_employee_icon.png" alt="Casual Employee">
                    </span>
                    <span class="nav-label">Casual Employee List</span>
                    <?php if (in_array($currentPage, ['casual_employees.php'])): ?>
                        <span class="nav-active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- Job Order Contracts — visible to both roles -->
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

            <!-- Payroll Management dropdown -->
            <!-- Admin sees full dropdown; user sees Job Order Payroll sub-item only -->
            <li class="nav-item has-dropdown <?= $payrollActive ? 'open parent-active' : '' ?>"
                id="payroll-dropdown">
                <a href="#" class="nav-link" onclick="toggleDropdown(event, 'payroll-dropdown')">
                    <span class="nav-icon">
                        <img src="assets/icons/payroll_icon.png" alt="Payroll Management">
                    </span>
                    <span class="nav-label">Payroll Management</span>
                </a>
                <ul class="nav-dropdown">
                    <!-- Job Order Payroll — visible to both roles -->
                    <li class="nav-sub-item <?= in_array($currentPage, ['payment_index_add.php', 'view_excel.php']) ? 'active' : '' ?>">
                        <a href="payment_index_add.php" class="nav-sub-link">
                            Job Order Payroll
                            <?php if ($currentPage === 'payment_index_add.php'): ?>
                                <span class="nav-sub-active-bar"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Settings dropdown — both roles see Account Settings; General Settings is admin only -->
            <li class="nav-item has-dropdown <?= $settingsActive ? 'open parent-active' : '' ?>"
                id="settings-dropdown">
                <a href="#" class="nav-link" onclick="toggleDropdown(event, 'settings-dropdown')">
                    <span class="nav-icon">
                        <img src="assets/icons/settings_icon.png" alt="Settings">
                    </span>
                    <span class="nav-label">Settings</span>
                </a>
                <ul class="nav-dropdown">
                    <!-- Account Settings — visible to both roles -->
                    <li class="nav-sub-item <?= $currentPage === 'account_settings.php' ? 'active' : '' ?>">
                        <a href="account_settings.php" class="nav-sub-link">
                            Account Settings
                            <?php if ($currentPage === 'account_settings.php'): ?>
                                <span class="nav-sub-active-bar"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <!-- General Settings — admin only -->
                    <?php if ($isAdmin): ?>
                    <li class="nav-sub-item <?= $currentPage === 'general_settings.php' ? 'active' : '' ?>">
                        <a href="general_settings.php" class="nav-sub-link">
                            General Settings
                            <?php if ($currentPage === 'general_settings.php'): ?>
                                <span class="nav-sub-active-bar"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- account List — admin only -->
                    <?php if ($isAdmin): ?>
                    <li class="nav-sub-item <?= $currentPage === 'account_list.php' ? 'active' : '' ?>">
                        <a href="account_list.php" class="nav-sub-link">
                            Account List
                            <?php if ($currentPage === 'account_list.php'): ?>
                                <span class="nav-sub-active-bar"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- account creation — admin only -->
                    <?php if ($isAdmin): ?>
                    <li class="nav-sub-item <?= $currentPage === 'account_creation.php' ? 'active' : '' ?>">
                        <a href="account_creation.php" class="nav-sub-link">
                            Create New Account
                            <?php if ($currentPage === 'account_creation.php'): ?>
                                <span class="nav-sub-active-bar"></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>

            <!-- Logs — admin only -->
            <?php if ($isAdmin): ?>
            <li class="nav-item <?= $currentPage === 'logs.php' ? 'active' : '' ?>">
                <a href="logs.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/logs_icon.png" alt="Logs">
                    </span>
                    <span class="nav-label">Logs</span>
                    <?php if ($currentPage === 'logs.php'): ?>
                        <span class="nav-active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- Log Out — visible to both roles -->
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
// Unified toggle function (replaces the two separate ones)
function toggleDropdown(e, id) {
    e.preventDefault();
    const item = document.getElementById(id);
    item.classList.toggle('open');
}
</script>