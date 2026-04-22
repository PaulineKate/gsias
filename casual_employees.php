<?php 
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

/* ── Database Query ── */
$reg_records = [];

try {
    $sql = "SELECT `emp_id`,
            CONCAT(last_name, ', ', first_name, IF(middle_name != '' AND middle_name IS NOT NULL, CONCAT(' ', SUBSTR(middle_name, 1, 1), '.'), '')) AS full_name,
            `emp_designation`, `salary`, `department`,
            `emp_standing`, `emp_status`
            FROM `employee_info`
            WHERE emp_standing = 'casual'
            ORDER BY emp_id DESC";

    $stmt        = $conn->query($sql);
    $reg_records = $stmt->fetchAll();

} catch (PDOException $e) {
    // silent fail
}
?>
<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — Employee List</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/regular_employees.css">
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

        <main class="main-content">
            <div class="reg-wrapper">

                <h1 class="reg-title">Employee List</h1>

                <div class="reg-toolbar">
                    <div class="reg-search-wrap">
                        <span class="search-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </span>
                        <input type="text" id="regSearch" placeholder="Search..." autocomplete="off">
                    </div>

                    <button class="reg-btn-add" onclick="window.location.href='regular_employee_add.php'">
                        <span class="btn-plus">+</span>
                        Add New Employee Record
                    </button>
                </div>

                <div class="reg-table-container">
                    <table class="reg-table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Salary</th>
                                <th>Department</th>
                                <th>Standing</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="regTableBody">
                            <?php if (!empty($reg_records)) : ?>
                                <?php foreach ($reg_records as $row) : ?>
                                <tr class="reg-data-row"
                                    data-name="<?= htmlspecialchars(strtolower($row['full_name'])) ?>"
                                    data-id="<?= htmlspecialchars(strtolower($row['emp_id'])) ?>">
                                    <td><?= htmlspecialchars($row['emp_id']) ?></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['emp_designation']) ?></td>
                                    <td>₱<?= number_format((float)$row['salary'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['department']) ?></td>
                                    <td>
                                        <span class="reg-badge reg-badge--<?= strtolower(htmlspecialchars($row['emp_standing'])) ?>">
                                            <?= ucfirst(htmlspecialchars($row['emp_standing'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="reg-badge reg-badge--<?= $row['emp_status'] ? 'active' : 'inactive' ?>">
                                            <?= $row['emp_status'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-update-info"
                                            onclick="window.location.href='employee_credentials.php?emp_id=<?= urlencode($row['emp_id']) ?>'">
                                            Update Info
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="no-results">
                                    <td colspan="8">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="js_files/regular_employees.js"></script>
</body>
</html>