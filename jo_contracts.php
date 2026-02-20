<?php 
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

/* ── Database Query ── */
$jo_records = [];

try {
    $sql = "SELECT `jo_id`, `name`, `designation`, `rate`, `date_from`, `date_to`, 
                   `funding_charges`, `ref_folder`, `ref_file` 
            FROM `jo_contracts`
            ORDER BY `jo_id` ASC";

    $stmt       = $conn->query($sql);
    $jo_records = $stmt->fetchAll();

} catch (PDOException $e) {
    // error_log($e->getMessage());
}

/* ── Helper: format date m/d/y ── */
function fmt_date($d) {
    if (!$d) return '—';
    $ts = strtotime($d);
    return $ts ? date('m/d/y', $ts) : htmlspecialchars($d);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — JO Contracts</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">
    <!-- Component Styles -->
    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <!-- JO Contracts Styles -->
    <link rel="stylesheet" href="css_files/jo_contracts.css">
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

            <div class="jo-wrapper">

                <!-- Page Title -->
                <h1 class="jo-title">Job Order Contracts</h1>

                <!-- Toolbar -->
                <div class="jo-toolbar">
                    <div class="jo-search-wrap">
                        <span class="search-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </span>
                        <input type="text" id="joSearch" placeholder="Search..." autocomplete="off">
                    </div>

                    <button class="jo-btn-add" onclick="window.location.href='jo_contract_reg.php'">
                        <span class="btn-plus">+</span>
                        Add New J.O. Contract
                    </button>
                </div>

                <!-- Table -->
                <div class="jo-table-container">
                    <table class="jo-table">
                        <thead>
                            <tr>
                                <th style="width:42px;">No.</th>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Rate Per Day</th>
                                <th colspan="3" class="th-period">
                                    Period of Employment
                                    <div class="th-period-sub">
                                        <span class="th-from">From</span>
                                        <span class="th-divider">&nbsp;|&nbsp;</span>
                                        <span class="th-to">To</span>
                                    </div>
                                </th>
                                <th>Funding Charges</th>
                                <th>Reference Folder</th>
                                <th>File Preview</th>
                            </tr>
                        </thead>
                        <tbody id="joTableBody">

                            <?php if (!empty($jo_records)) : ?>
                                <?php $counter = 1; ?>
                                <?php foreach ($jo_records as $row) : ?>
                                <tr class="jo-data-row"
                                    data-name="<?= htmlspecialchars(strtolower($row['name'])) ?>"
                                    data-ref-folder="<?= htmlspecialchars($row['ref_folder']) ?>"
                                    data-has-file="<?= (int)$row['ref_file'] ?>">
                                    <td><?= $counter++ ?></td>
                                    <td><?= htmlspecialchars(strtoupper($row['name'])) ?></td>
                                    <td><?= htmlspecialchars(strtoupper($row['designation'])) ?></td>
                                    <td>₱<?= number_format((float)$row['rate'], 2) ?></td>
                                    <td><?= fmt_date($row['date_from']) ?></td>
                                    <td class="td-pipe">|</td>
                                    <td><?= fmt_date($row['date_to']) ?></td>
                                    <td><?= htmlspecialchars(strtoupper($row['funding_charges'])) ?></td>
                                    <td>
                                        <span class="ref-folder-badge">
                                            <?= htmlspecialchars(strtoupper($row['ref_folder'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ((int)$row['ref_file'] === 1) : ?>
                                            <button type="button"
                                                    class="btn-view"
                                                    onclick="previewFile(this)">VIEW</button>
                                        <?php else : ?>
                                            <button type="button"
                                                    class="btn-unavailable"
                                                    onclick="noFileAlert(this)">UNAVAILABLE</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="no-results">
                                    <td colspan="10">No records found.</td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>

            </div>

        </main>
    </div>
</div>

<script src="js_files/jo_contracts.js"></script>
</body>
</html>