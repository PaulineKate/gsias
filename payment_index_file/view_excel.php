<?php
/**
 * payment_index_file/view_excel.php 
 * Backend Logic - Deduplicated Names
 */

include '../reusable_files/db_connect.php';

$base_dir   = __DIR__;
$excel_file = $base_dir . '/payroll.xlsx';
$exists     = file_exists($excel_file);
$file_size  = $exists ? round(filesize($excel_file) / 1024, 1) . ' KB' : '—';

/* ── 1. Download Trigger ── */
if (isset($_GET['dl']) && $_GET['dl'] === '1' && $exists) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="payroll.xlsx"');
    header('Content-Length: ' . filesize($excel_file));
    readfile($excel_file);
    exit;
}

/* ── 2. AJAX: Fetch UNIQUE names for the dropdown ── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_names') {
    header('Content-Type: application/json');
    $year = (int)($_GET['year'] ?? 0);
    
    // GROUP BY name removes the duplicates you saw in your screenshot
    $sql = "SELECT jc.name 
            FROM jo_contracts jc 
            INNER JOIN payment_index pi ON jc.jo_id = pi.jo_id 
            WHERE YEAR(jc.date_to) = :yr 
            GROUP BY jc.name 
            ORDER BY jc.name ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':yr' => $year]);
    
    /** @var PDOStatement $stmt */
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ── 3. AJAX: Fetch details using Name and Year ── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_details') {
    header('Content-Type: application/json');
    $name = $_GET['name'] ?? '';
    $year = (int)($_GET['year'] ?? 0);
    
    // We filter by NAME. This pulls ALL payment records (Jan, Feb, etc.)
    // for that person even if they have different jo_ids (multiple contracts).
    $sql = "SELECT 
                pi.`payindex_id`, jc.name, jc.designation, YEAR(jc.date_to) AS yr, 
                pi.`period_covered`, pi.`num_days`, jc.rate, pi.`total_wage`, 
                d.lbp, d.pagibig_cont, d.pagibig_mpl, d.sss_cont, d.late_deduction, d.nursery_prod, 
                pi.`total_amount_due` 
            FROM `payment_index` pi
            LEFT JOIN jo_contracts jc ON pi.jo_id = jc.jo_id
            LEFT JOIN deductions d ON pi.deduct_id = d.deduct_id
            WHERE jc.name = :name 
              AND YEAR(jc.date_to) = :yr
            ORDER BY pi.payindex_id ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':name' => $name, ':yr' => $year]);
    
    /** @var PDOStatement $stmt */
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* ── 4. Initial Load: Years ── */
$yearQuery = $conn->query("SELECT DISTINCT YEAR(date_to) as yr FROM jo_contracts ORDER BY yr DESC");
/** @var PDOStatement $yearQuery */
$years = $yearQuery->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — View Payment Records</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green-dark: #1a3d1f;
            --green-mid: #2a5c30;
            --green-light: #e8f5e9;
            --white: #ffffff;
        }
        body {
            font-family: 'Source Sans 3', sans-serif;
            background: var(--green-light);
            color: #1a2e1c;
            padding: 40px 20px;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .card {
            background: var(--white);
            border-radius: 14px;
            padding: 32px;
            box-shadow: 0 4px 24px rgba(26,61,31,0.10);
        }
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--green-mid);
            padding-bottom: 15px;
        }
        h1 {
            font-family: 'Barlow', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--green-dark);
            text-transform: uppercase;
        }

        /* ── Filters ── */
        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            background: #f0f7f0;
            padding: 20px;
            border-radius: 10px;
            align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-label { font-size: 0.75rem; font-weight: 700; color: var(--green-dark); text-transform: uppercase; }
        .filter-select {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #c8e0c8;
            font-family: inherit;
            min-width: 180px;
        }

        /* ── Tables ── */
        .table-section { margin-top: 30px; display: none; }
        .section-title {
            font-family: 'Barlow', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            color: var(--green-mid);
            text-transform: uppercase;
            margin-bottom: 12px;
            margin-top: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .table-container { overflow-x: auto; border-radius: 8px; border: 1px solid #c8e0c8; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            font-size: 0.88rem;
        }
        th {
            background: var(--green-dark);
            color: white;
            text-align: left;
            padding: 12px 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        tr:hover { background-color: #f9fff9; }
        .amt { font-weight: 700; color: var(--green-dark); }

        /* ── Buttons ── */
        .btn-dl {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #217346;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.85rem;
            transition: background 0.2s;
        }
        .btn-dl:hover { background: #1a5e38; }
        .btn-dl.disabled { opacity: 0.5; pointer-events: none; background: #888; }
        
        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: var(--green-mid);
            font-weight: 700;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header-flex">
            <h1>📊 Payment Index Viewer</h1>
            <?php if ($exists): ?>
                <a href="?dl=1" class="btn-dl">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Download Excel (<?= $file_size ?>)
                </a>
            <?php else: ?>
                <span class="btn-dl disabled">No Excel Generated</span>
            <?php endif; ?>
        </div>

        <!-- ── Filter Section ── -->
        <div class="filter-row">
            <div class="filter-group">
                <label class="filter-label">Select Year</label>
                <select id="selYear" class="filter-select">
                    <option value="">— Year —</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Select Name</label>
                <select id="selName" class="filter-select" disabled>
                    <option value="">— Select Year First —</option>
                </select>
            </div>
        </div>

        <div id="noData" style="text-align:center; color:#7a9e7a; padding: 20px; display:none;">No records found for this selection.</div>

        <!-- ── Table 1: Payment Summary ── -->
        <div id="resultsArea" class="table-section">
            <div class="section-title">
                <svg width="18" height="18" fill="var(--green-mid)" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                Payment Summary & Wage
            </div>
            <div class="table-container">
                <table>
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

            <!-- ── Table 2: Deduction Breakdown ── -->
            <div class="section-title">
                <svg width="18" height="18" fill="var(--green-mid)" viewBox="0 0 24 24"><path d="M19 13H5v-2h14v2z"/></svg>
                Detailed Deductions
            </div>
            <div class="table-container">
                <table>
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

        <a href="../payment_index_add.php" class="back-link">← Back to Payment Index Form</a>
    </div>
</div>

<script>
const selYear = document.getElementById('selYear');
const selName = document.getElementById('selName');
const resultsArea = document.getElementById('resultsArea');
const noData = document.getElementById('noData');

function fmt(n) {
    return parseFloat(n).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// 1. When Year changes, load names (Unique list)
selYear.addEventListener('change', function() {
    const yr = this.value;
    selName.innerHTML = '<option value="">Loading...</option>';
    selName.disabled = true;
    resultsArea.style.display = 'none';
    if (!yr) return;

    fetch(`?ajax=get_names&year=${yr}`)
        .then(r => r.json())
        .then(data => {
            selName.innerHTML = '<option value="">— Select Name —</option>';
            data.forEach(item => {
                let opt = document.createElement('option');
                // We set value to item.name instead of ID to avoid duplicates
                opt.value = item.name; 
                opt.textContent = item.name;
                selName.appendChild(opt);
            });
            selName.disabled = false;
        });
});

// 2. When Name changes, load table data
selName.addEventListener('change', function() {
    const nameVal = this.value; // This is now the name string
    const yr = selYear.value;
    if (!nameVal || !yr) return;

    // Use encodeURIComponent so names with spaces or dots work correctly in the URL
    fetch(`?ajax=get_details&name=${encodeURIComponent(nameVal)}&year=${yr}`)
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) {
                resultsArea.style.display = 'none';
                noData.style.display = 'block';
                return;
            }
            noData.style.display = 'none';
            resultsArea.style.display = 'block';

            let htmlSummary = '';
            let htmlDeduct  = '';

            data.forEach(row => {
                htmlSummary += `
                    <tr>
                        <td>${row.period_covered}</td>
                        <td>${row.num_days}</td>
                        <td>${fmt(row.rate)}</td>
                        <td>${fmt(row.total_wage)}</td>
                        <td class="amt">${fmt(row.total_amount_due)}</td>
                    </tr>`;
                htmlDeduct += `
                    <tr>
                        <td style="font-weight:600;">${row.period_covered}</td>
                        <td>${fmt(row.lbp)}</td>
                        <td>${fmt(row.pagibig_cont)}</td>
                        <td>${fmt(row.pagibig_mpl)}</td>
                        <td>${fmt(row.sss_cont)}</td>
                        <td>${fmt(row.late_deduction)}</td>
                        <td>${fmt(row.nursery_prod)}</td>
                    </tr>`;
            });

            document.getElementById('bodySummary').innerHTML = htmlSummary;
            document.getElementById('bodyDeductions').innerHTML = htmlDeduct;
        });
});
</script>

</body>
</html>