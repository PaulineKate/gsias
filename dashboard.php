<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

/* ── Stat Card Counts ── */
$regular_count = 0;
$casual_count  = 0;
$jo_count      = 0;

try {
    $stmt          = $conn->query("SELECT COUNT(emp_id) AS total FROM employee_info WHERE emp_standing = 'regular'");
    $regular_count = $stmt->fetch()['total'];
} catch (PDOException $e) {}

try {
    $stmt         = $conn->query("SELECT COUNT(emp_id) AS total FROM employee_info WHERE emp_standing = 'casual'");
    $casual_count = $stmt->fetch()['total'];
} catch (PDOException $e) {}

try {
    $stmt     = $conn->query("
        SELECT COUNT(name) AS total
        FROM jo_contracts
        WHERE date_from <= CURRENT_DATE()
          AND date_to >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
    ");
    $jo_count = $stmt->fetch()['total'];
} catch (PDOException $e) {}

$gso_total = $regular_count + $casual_count + $jo_count;

/* ── Bar Chart Data by Role ── */
$regular_by_role = [];
$casual_by_role  = [];
$jo_by_role      = [];

try {
    $stmt            = $conn->query("
        SELECT emp_designation AS role, COUNT(*) AS count
        FROM employee_info
        WHERE emp_standing = 'regular'
        GROUP BY emp_designation
        ORDER BY emp_designation ASC
    ");
    $regular_by_role = $stmt->fetchAll();
} catch (PDOException $e) {}

try {
    $stmt           = $conn->query("
        SELECT emp_designation AS role, COUNT(*) AS count
        FROM employee_info
        WHERE emp_standing = 'casual'
        GROUP BY emp_designation
        ORDER BY emp_designation ASC
    ");
    $casual_by_role = $stmt->fetchAll();
} catch (PDOException $e) {}

try {
    $stmt        = $conn->query("
        SELECT designation AS role, COUNT(*) AS count
        FROM jo_contracts
        WHERE date_from <= CURRENT_DATE()
          AND date_to >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        GROUP BY designation
        ORDER BY designation ASC
    ");
    $jo_by_role  = $stmt->fetchAll();
} catch (PDOException $e) {}

/* ── Recently Added JO Contracts ── */
$recent_jo = [];

try {
    $stmt      = $conn->query("
        SELECT name, ref_folder
        FROM jo_contracts
        WHERE date_from <= CURRENT_DATE()
          AND date_to >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        ORDER BY jo_id ASC  
        LIMIT 5
    ");
    $recent_jo = $stmt->fetchAll();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — Dashboard</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <!-- Component Styles -->
    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/dashboard.css">

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
            :root {
                --sidebar-width: 64px;
            }
        }
    </style>
</head>
<body>

<div class="app-shell">

    <!-- SIDEBAR -->
    <aside class="app-sidebar">
        <?php include 'reusable_files/sidebar.php'; ?>
    </aside>

    <!-- RIGHT COLUMN -->
    <div class="app-right">

        <!-- HEADER -->
        <div class="app-header">
            <?php include 'reusable_files/header.php'; ?>
        </div>

        <!-- MAIN CONTENT -->
        <main class="main-content">

            <!-- STAT CARDS -->
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-label">Total No. of<br>Regular Employees</div>
                    <div class="stat-number"><?php echo number_format($regular_count); ?></div>
                    <div class="stat-sub">As of this year</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total No. of<br>Casual Employees</div>
                    <div class="stat-number"><?php echo number_format($casual_count); ?></div>
                    <div class="stat-sub">As of this year</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total No. of<br>Job Order Employees</div>
                    <div class="stat-number"><?php echo number_format($jo_count); ?></div>
                    <div class="stat-sub">As of this year</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total No. of GSO Employees<br><small>(Regular, Casual, and Job Order)</small></div>
                    <div class="stat-number"><?php echo number_format($gso_total); ?></div>
                    <div class="stat-sub">As of this year</div>
                </div>
            </div>

            <!-- EMPLOYEE COUNT + BAR CHART -->
            <div class="chart-section">
                <div class="chart-header">
                    <div class="chart-title">Employee Count</div>
                    <div class="tab-group">
                        <button class="tab active" onclick="setTab(this, 'regular')">Regular</button>
                        <button class="tab" onclick="setTab(this, 'casual')">Casual</button>
                        <button class="tab" onclick="setTab(this, 'joborder')">Job Order</button>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="chart-labels" id="bar-labels"></div>
                    <div class="bars-area" id="bars"></div>
                </div>
            </div>

            <!-- RECENTLY ADDED JO CONTRACTS -->
            <div class="table-section">
                <div class="table-title">Recently Added JO Contracts</div>
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Reference Folder</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_jo)): ?>
                        <tr>
                            <td colspan="3" style="text-align:center; color:#888; padding: 20px;">No records found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_jo as $i => $row): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['ref_folder']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>

    </div>

</div>

<!-- DASHBOARD SCRIPT -->
<script src="js_files/dashboard.js"></script>
<script>
    const employeeData = {
        regular:  <?php echo json_encode(array_values($regular_by_role)); ?>,
        casual:   <?php echo json_encode(array_values($casual_by_role)); ?>,
        joborder: <?php echo json_encode(array_values($jo_by_role)); ?>
    };

    renderBars('regular');
</script>

</body>
</html>