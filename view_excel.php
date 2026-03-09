<?php

include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

$excel_file = __DIR__ . '/payment_index_file/payroll.xlsx';
$exists     = file_exists($excel_file);
$file_size  = $exists ? round(filesize($excel_file) / 1024, 1) . ' KB' : '—';

if (isset($_GET['dl']) && $_GET['dl'] === '1' && $exists) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="payroll.xlsx"');
    header('Content-Length: ' . filesize($excel_file));
    readfile($excel_file);
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_names') {
    header('Content-Type: application/json');
    $year = (int)($_GET['year'] ?? 0);
    if ($year <= 0) { echo json_encode([]); exit; }

    $stmt = $conn->prepare(
        "SELECT DISTINCT jc.name
         FROM jo_contracts jc
         INNER JOIN payment_index pi ON jc.jo_id = pi.jo_id
         WHERE YEAR(jc.date_to) = :yr
         ORDER BY jc.name ASC"
    );
    $stmt->execute([':yr' => $year]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_details') {
    header('Content-Type: application/json');
    $name = $_GET['name'] ?? '';
    $year = (int)($_GET['year'] ?? 0);
    if ($name === '' || $year <= 0) { echo json_encode([]); exit; }

    $stmt = $conn->prepare(
        "SELECT
             pi.payindex_id, jc.name, jc.designation, YEAR(jc.date_to) AS yr,
             pi.period_covered, pi.num_days, jc.rate, pi.total_wage,
             d.lbp, d.pagibig_cont, d.pagibig_mpl, d.sss_cont,
             d.late_deduction, d.nursery_prod, pi.total_amount_due
         FROM payment_index pi
         LEFT JOIN jo_contracts jc ON pi.jo_id = jc.jo_id
         LEFT JOIN deductions d ON pi.deduct_id = d.deduct_id
         WHERE jc.name = :name AND YEAR(jc.date_to) = :yr
         ORDER BY pi.payindex_id ASC"
    );
    $stmt->execute([':name' => $name, ':yr' => $year]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

$year_options = [];
try {
    $year_options = $conn->query(
        "SELECT DISTINCT YEAR(date_to) AS yr FROM jo_contracts ORDER BY yr DESC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GSIAS — View Payment Records</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/payment_index_add.css">
    <link rel="stylesheet" href="css_files/view_excel.css">
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar"><?php include 'reusable_files/sidebar.php'; ?></aside>
    <div class="app-right">
        <div class="app-header"><?php include 'reusable_files/header.php'; ?></div>
        <main class="main-content">
            <div class="pi-wrapper">

                <h1 class="pi-title">📊 Payment Index Viewer</h1>

                <div class="pi-card">

                    <div class="pi-section-label">Filter Records</div>
                    <div class="pi-row">

                        <div class="pi-group" style="flex:0 0 110px;">
                            <label class="pi-label">Year</label>
                            <select id="veYear" class="pi-select">
                                <option value="">— Year —</option>
                                <?php foreach ($year_options as $y): ?>
                                    <option value="<?= (int)$y ?>"><?= (int)$y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Searchable Name -->
                        <div class="pi-group" id="veNameGroup" style="flex:2; min-width:260px; opacity:0.4; pointer-events:none;">
                            <label class="pi-label">Search Name</label>
                            <input type="hidden" id="veNameHidden">
                            <div class="pi-name-wrap">
                                <input type="text"
                                       id="veNameSearch"
                                       class="pi-input"
                                       placeholder="— Select a Year First —"
                                       autocomplete="off"
                                       disabled>
                                <ul id="veNameDropdown"></ul>
                            </div>
                        </div>

                        <div class="pi-group" style="flex:0 0 auto; justify-content:flex-end;">
                            <label class="pi-label">&nbsp;</label>
                            <?php if ($exists): ?>
                                <a href="?dl=1" class="pi-excel-link" style="display:inline-flex;align-items:center;gap:8px;white-space:nowrap;">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                                    </svg>
                                    Download Excel (<?= $file_size ?>)
                                </a>
                            <?php else: ?>
                                <span class="pi-excel-link" style="opacity:0.5;pointer-events:none;cursor:default;display:inline-flex;align-items:center;">No Excel Generated</span>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div id="noData" style="display:none; text-align:center; color:#7a9e7a; padding:10px 0;">
                        No records found for this selection.
                    </div>

                </div>

                <!-- Results area -->
                <div id="resultsArea" style="display:none; display:flex; flex-direction:column; gap:18px;">

                    <!-- Summary table -->
                    <div class="pi-card" style="gap:12px;">
                        <div class="pi-section-label">Payment Summary &amp; Wage</div>
                        <div class="ve-table-wrap">
                            <table class="ve-table">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Days</th>
                                        <th>Rate</th>
                                        <th>Gross Wage</th>
                                        <th>Amount Due</th>
                                    </tr>
                                </thead>
                                <tbody id="bodySummary"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Deductions table -->
                    <div class="pi-card" style="gap:12px;">
                        <div class="pi-section-label">Detailed Deductions</div>
                        <div class="ve-table-wrap">
                            <table class="ve-table">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>LBP</th>
                                        <th>Pag-Ibig Cont.</th>
                                        <th>Pag-Ibig MPL</th>
                                        <th>SSS Cont.</th>
                                        <th>Late</th>
                                        <th>Nursery</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyDeductions"></tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="pi-footer" style="display:flex; justify-content:flex-start; margin-top:0;">
                    <a href="payment_index_add.php" class="pi-excel-link">← Back to Payment Index Form</a>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="js_files/view_excel.js"></script>
</body>
</html>