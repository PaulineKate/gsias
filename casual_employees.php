<?php 
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

/* ── Database Query ── */
$reg_records = [];

try {
    $sql = "SELECT `emp_id`, CONCAT(last_name, ', ', first_name, ' ', SUBSTR(middle_name, 1, 1), '.') as full_name, `emp_designation`, `salary`, `department`, 
    `sss_num`, `philhealth_num`, `tin_num`, `gsis_num`, `pagibig_num`, `emp_standing`, `emp_status` FROM `employee_info`WHERE emp_standing = 'casual' ORDER BY emp_id DESC";

    $stmt       = $conn->query($sql);
    $reg_records = $stmt->fetchAll();

} catch (PDOException $e) {

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — Regular Employee List</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">

    <link rel="stylesheet" href="css_files/regular_employees.css">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        :root {
            --green-dark:    #1a3d1f;
            --green-mid:     #2a5c30;
            --green-light:   #e8f5e9;
            --green-content: #d4edda;
            --sidebar-width: 220px;
            --header-height: 56px;
        }
        html, body {
            height: 100%;
            font-family: 'Source Sans 3', sans-serif;
            background: var(--green-light);
            color: #1a2e1c;
        }
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .app-sidebar {
            width: var(--sidebar-width);
            flex-shrink: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .app-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
        }
        .app-header {
            flex-shrink: 0;
        }
        @media (max-width: 768px) {
            :root { --sidebar-width: 64px; }
        }
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

        <main class="main-content">

            <div class="reg-wrapper">
                <h1 class="reg-title">Casual Employee List</h1>
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
                                <th style="width:42px;">Employee_ID</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Salary</th>
                                <th>Department</th>
                                <th>SSS No.</th>
                                <th>Philhealth No.</th>
                                <th>Tin No.</th>
                                <th>GSIS No.</th>
                                <th>Pag-ibig No.</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="regTableBody">

                            <?php if (!empty($reg_records)) : ?>
                                <?php $total = count($reg_records); $counter = $total; ?>
                                <?php foreach ($reg_records as $row) : ?>
                                <tr class="reg-data-row">
                                    <td><?= htmlspecialchars($row['emp_id']) ?></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['emp_designation']) ?></td>
                                    <td><?= number_format((float)$row['salary'], 2) ?></td>
                                    <td><?= htmlspecialchars($row['department']) ?></td>
                                    <td><?= htmlspecialchars($row['sss_num']) ?></td>
                                    <td><?= htmlspecialchars($row['philhealth_num']) ?></td>
                                    <td><?= htmlspecialchars($row['tin_num']) ?></td>
                                    <td><?= htmlspecialchars($row['gsis_num']) ?></td>
                                    <td><?= htmlspecialchars($row['pagibig_num']) ?></td>
                                    <td>
                                        <button type="button" class="btn-view-details" onclick="noFileAlert(this)">
                                            pending
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="no-results">
                                    <td colspan="10">No records found.</td>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>

            </div>

        </main>
    </div>
</div>

<script src="js_files/regular_employee  s.js"></script>
</body>
</html>