<?php
/**
 * payment_index_file/rebuild_excel.php
 * ─────────────────────────────────────────────────────────────────
 * TEMPORARY UTILITY — Re-generates payroll.xlsx from the database.
 *
 * HOW TO USE:
 *   1. Place this file inside payment_index_file/
 *   2. Open it in your browser (or run via CLI).
 *   3. It will call update_excel.py for every payment_index row.
 *   4. DELETE this file when you are done — it is not meant to stay.
 *
 * REQUIREMENTS:
 *   • Python (py / python3) must be available on the server.
 *   • openpyxl must be installed:  py -m pip install openpyxl
 *   • update_excel.py must exist in the same folder.
 * ─────────────────────────────────────────────────────────────────
 */

/* ── Safety gate: remove or change this to run ── */
define('REBUILD_ALLOWED', true);   // ← set to false after use!

if (!REBUILD_ALLOWED) {
    die('<h2 style="color:red;font-family:sans-serif;">Rebuild disabled. Set REBUILD_ALLOWED to true to run.</h2>');
}

/* ── Bootstrap ── */
set_time_limit(0);
ini_set('output_buffering', 'off');

include 'reusable_files/db_connect.php';

$pi_dir  = __DIR__ . DIRECTORY_SEPARATOR . 'payment_index_file';
$script  = __DIR__ . DIRECTORY_SEPARATOR . 'python_files' . DIRECTORY_SEPARATOR . 'update_excel.py';
$excel   = $pi_dir . DIRECTORY_SEPARATOR . 'payroll.xlsx';

/* ── Pre-flight checks ── */
$errors = [];
if (!file_exists($script))   $errors[] = "update_excel.py not found at: $script";
if (!is_writable($pi_dir))   $errors[] = "Directory is not writable: $pi_dir";

$html_open = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rebuild Excel — Payment Index</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #e8f5e9; padding: 30px; color: #1a2e1c; }
        h1   { font-size: 1.5rem; color: #1a3d1f; margin-bottom: 20px; }
        .card { background: #fff; border-radius: 10px; padding: 24px 30px;
                box-shadow: 0 2px 12px rgba(26,61,31,.08); max-width: 900px; }
        .ok   { color: #217346; font-weight: 700; }
        .err  { color: #c0392b; font-weight: 700; }
        .warn { color: #856404; font-weight: 600; }
        .row  { padding: 4px 0; font-size: 0.88rem; border-bottom: 1px solid #f0f0f0; }
        .done { margin-top: 20px; padding: 14px 18px; background: #d4edda;
                border-radius: 8px; font-weight: 700; color: #1a3d1f; }
        pre   { font-size: 0.78rem; color: #666; margin: 2px 0 0 10px; }
    </style>
</head>
<body>
<div class="card">
<h1>🔄 Rebuild payroll.xlsx from Database</h1>
HTML;

echo $html_open;
flush();

if (!empty($errors)) {
    foreach ($errors as $e) {
        echo "<p class='err'>❌ $e</p>";
    }
    echo '</div></body></html>';
    exit;
}

/* ── Fetch all payment records with full join ── */
$sql = "
    SELECT
        pi.payindex_id,
        jc.jo_id,
        jc.name,
        jc.designation,
        jc.rate,
        jc.date_from,
        jc.date_to,
        YEAR(jc.date_to)     AS yr,
        pi.period_covered,
        pi.num_days,
        pi.total_wage,
        d.lbp,
        d.pagibig_cont,
        d.pagibig_mpl,
        d.sss_cont,
        d.late_deduction,
        d.nursery_prod,
        pi.total_amount_due
    FROM payment_index pi
    LEFT JOIN jo_contracts jc ON pi.jo_id   = jc.jo_id
    LEFT JOIN deductions   d  ON pi.deduct_id = d.deduct_id
    ORDER BY jc.name ASC, pi.payindex_id ASC
";

try {
    $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p class='err'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p></div></body></html>";
    exit;
}

$total   = count($rows);
$success = 0;
$failed  = 0;

echo "<p>Found <strong>$total</strong> payment records. Processing…</p><hr style='margin:10px 0'>";
flush();

/* ── Delete existing Excel so we start fresh ── */
if (file_exists($excel)) {
    unlink($excel);
    echo "<p class='warn'>🗑️ Deleted existing payroll.xlsx — rebuilding from scratch.</p>";
    flush();
}

/* ── Process each row ── */
foreach ($rows as $row) {
    $payload = [
        'year'            => (string)$row['yr'],
        'name'            => $row['name'],
        'designation'     => $row['designation'],
        'date_from'       => $row['date_from'],
        'period_covered'  => $row['period_covered'],
        'num_days'        => (float)$row['num_days'],
        'rate'            => (float)$row['rate'],
        'total_wage'      => (float)$row['total_wage'],
        'lbp'             => (float)$row['lbp'],
        'pagibig_cont'    => (float)$row['pagibig_cont'],
        'pagibig_mpl'     => (float)$row['pagibig_mpl'],
        'sss_cont'        => (float)$row['sss_cont'],
        'late_deduction'  => (float)$row['late_deduction'],
        'nursery_prod'    => (float)$row['nursery_prod'],
        'total_amount_due'=> (float)$row['total_amount_due'],
    ];

    $tmp_file = $pi_dir . DIRECTORY_SEPARATOR . 'tmp_rebuild_' . uniqid() . '.json';
    file_put_contents($tmp_file, json_encode($payload));

    $cmd    = 'py ' . escapeshellarg($script) . ' ' . escapeshellarg($tmp_file) . ' 2>&1';
    $output = shell_exec($cmd);

    if (file_exists($tmp_file)) unlink($tmp_file);

    $name_safe   = htmlspecialchars($row['name']);
    $period_safe = htmlspecialchars($row['period_covered']);
    $yr_safe     = htmlspecialchars($row['yr']);

    if (!empty($output) && stripos($output, 'error') !== false) {
        $failed++;
        echo "<div class='row'><span class='err'>❌ FAILED</span> — {$name_safe} | {$period_safe} | {$yr_safe}";
        echo "<pre>" . htmlspecialchars(trim($output)) . "</pre></div>";
    } else {
        $success++;
        echo "<div class='row'><span class='ok'>✔</span> {$name_safe} | {$period_safe} | {$yr_safe}</div>";
    }

    flush();
}

/* ── Summary ── */
$size = file_exists($excel) ? round(filesize($excel) / 1024, 1) . ' KB' : '—';
echo "
<div class='done'>
    ✅ Rebuild complete — {$success} of {$total} records written.<br>
    " . ($failed ? "<span class='err'>⚠️ {$failed} record(s) had errors (see above).</span><br>" : "") . "
    Excel file size: <strong>{$size}</strong>
</div>
<p style='margin-top:16px; font-size:0.85rem; color:#888;'>
    ⚠️ Remember to <strong>delete rebuild_excel.php</strong> from your server when done.
</p>
</div></body></html>";