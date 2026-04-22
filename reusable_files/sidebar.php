<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Pages that belong under the Settings dropdown
$settingsPages = ['account_settings.php', 'general_settings.php'];
$payrollPages = ['regular_employee_payroll.php', 'payment_index_add.php', 'regular_employee_payroll_add.php', 'view_excel.php'];
$settingsActive = in_array($currentPage, $settingsPages);
$payrollActive = in_array($currentPage, $payrollPages);
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
            <!-- Regular Employee List -->
             <li class="nav-item <?= in_array($currentPage, ['regular_employees.php']) ? 'active' : '' ?>">
                <a href="regular_employees.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/employee_list_icon.png" alt="Job Order Contracts">
                    </span>
                    <span class="nav-label">Regular Employee List</span>
                    <?php if (in_array($currentPage, ['regular_employees.php'])): ?>
                        <span class="nav-active-bar"></span>
                    <?php endif; ?>
                </a>
            </li>
            <!-- Casual Employee List -->
             <li class="nav-item <?= in_array($currentPage, ['casual_employees.php']) ? 'active' : '' ?>">
                <a href="casual_employees.php" class="nav-link">
                    <span class="nav-icon">
                        <img src="assets/icons/casual_employee_icon.png" alt="Job Order Contracts">
                    </span>
                    <span class="nav-label">Casual Employee List</span>
                    <?php if (in_array($currentPage, ['casual_employees.php'])): ?>
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
              <!--Payroll -->
            <li class="nav-item has-dropdown <?= $payrollActive ? 'open parent-active' : '' ?>"
                id="payroll-dropdown">
                <a href="#" class="nav-link" onclick="toggleDropdownpayroll(event, 'payroll-dropdown')">
                    <span class="nav-icon">
                        <img src="assets/icons/payroll_icon.png" alt="Payroll Management">
                    </span>
                    <span class="nav-label">Payroll Management</span>
                </a>
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
            <!-- Settings (Dropdown) -->
            <li class="nav-item has-dropdown <?= $settingsActive ? 'open parent-active' : '' ?>"
                id="settings-dropdown">
                <a href="#" class="nav-link" onclick="toggleDropdownsettings(event, 'settings-dropdown')">
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
function toggleDropdownsettings(e, id) {
    e.preventDefault();
    const item = document.getElementById(id);
    item.classList.toggle('open');
}
function toggleDropdownpayroll(e, id) {
    e.preventDefault();
    const item = document.getElementById(id);
    item.classList.toggle('open');
}
</script>