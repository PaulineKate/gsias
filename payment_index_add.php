<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

$alert_msg  = '';
$alert_type = '';

$saved_year   = '';
$saved_month  = '';
$saved_period = '';

$year_options = [];
try {
    $year_options = $conn->query(
        "SELECT DISTINCT YEAR(`date_to`) AS `yr` FROM `jo_contracts` ORDER BY `yr` DESC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_names') {
    header('Content-Type: application/json');
    $year  = (int)($_GET['year']  ?? 0);
    $month = (int)($_GET['month'] ?? 0);

    if ($year <= 0 || $month <= 0) { echo json_encode([]); exit; }

    try {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        $stmt = $conn->prepare(
            "SELECT `jo_id`, `name`, `designation`, `rate`, `date_from`, `date_to`
             FROM `jo_contracts`
             WHERE `date_from` <= :end_date AND `date_to` >= :start_date
             ORDER BY `name` ASC"
        );
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

function periodToYearMonth(string $period, int $fallbackYear): ?array
{
    $detectedYear = $fallbackYear;
    if (preg_match('/\b(20\d{2})\b/', $period, $ym)) {
        $detectedYear = (int)$ym[1];
    }

    $monthMap = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
        'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
        'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    $lower = strtolower($period);
    foreach ($monthMap as $abbr => $num) {
        if (strpos($lower, $abbr) !== false) {
            return [$detectedYear, $num];
        }
    }
    return null;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_periods') {
    header('Content-Type: application/json');
    $jo_id = (int)($_GET['jo_id'] ?? 0);

    if ($jo_id <= 0) { echo json_encode([]); exit; }

    try {
        $stmt = $conn->prepare(
            "SELECT `period_covered` FROM `payment_index` WHERE `jo_id` = :jo_id"
        );
        $stmt->execute([':jo_id' => $jo_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jo_id          = (int)($_POST['jo_id']           ?? 0);
    $period_covered = trim($_POST['period_covered']   ?? '');
    $num_days       = (float)($_POST['num_days']       ?? 0);
    $lbp            = (float)($_POST['lbp']            ?? 0);
    $pagibig_cont   = (float)($_POST['pagibig_cont']   ?? 0);
    $pagibig_mpl    = (float)($_POST['pagibig_mpl']    ?? 0);
    $sss_cont       = (float)($_POST['sss_cont']       ?? 0);
    $late_deduction = (float)($_POST['late_deduction'] ?? 0);
    $nursery_prod   = (float)($_POST['nursery_prod']   ?? 0);
    $year           = (int)($_POST['year']             ?? 0);

    $saved_year   = $year;
    $saved_period = $period_covered;

    $errors = [];
    if ($jo_id <= 0)            $errors[] = 'Please select a valid contract / name.';
    if ($period_covered === '') $errors[] = 'Period covered is required.';
    if ($num_days <= 0)         $errors[] = 'Number of days must be greater than 0.';
    if ($year <= 0)             $errors[] = 'A valid year must be selected.';

    foreach ([
        'LBP'             => $lbp,
        'Pag-ibig Cont.'  => $pagibig_cont,
        'Pag-ibig MPL'    => $pagibig_mpl,
        'SSS Cont.'       => $sss_cont,
        'Late Deduction'  => $late_deduction,
        'Nursery Product' => $nursery_prod,
    ] as $label => $val) {
        if ($val < 0) $errors[] = "$label cannot be negative.";
    }

    if (!empty($errors)) {
        $alert_msg  = implode('<br>', $errors);
        $alert_type = 'error';
    } else {
        $periodYM = periodToYearMonth($period_covered, $year);
        if ($periodYM === null) {
            $alert_msg  = 'Could not determine the month from the Period Covered value. '
                        . 'Please include a recognisable month name (e.g. "Jan. 3-31").';
            $alert_type = 'error';
        } else {
            [$periodYear, $periodMonth] = $periodYM;
            $saved_month = $periodMonth;

            try {
                $stmt = $conn->prepare(
                    "SELECT `name`, `designation`, `rate`, `date_from`, `date_to`
                     FROM `jo_contracts` WHERE `jo_id` = :id"
                );
                $stmt->execute([':id' => $jo_id]);
                $contract = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$contract) {
                    $alert_msg  = 'Invalid contract selected. It may have been deleted.';
                    $alert_type = 'error';
                } else {
                    $rate             = (float)$contract['rate'];
                    $total_wage       = $num_days * $rate;
                    $total_deductions = $lbp + $pagibig_cont + $pagibig_mpl + $sss_cont
                                      + $late_deduction + $nursery_prod;
                    $total_amount_due = $total_wage - $total_deductions;

                    $conn->beginTransaction();

                    try {
                        $existingRows = $conn->prepare(
                            "SELECT pi.`payindex_id`, pi.`deduct_id`, pi.`period_covered`
                             FROM `payment_index` pi WHERE pi.`jo_id` = :jo_id"
                        );
                        $existingRows->execute([':jo_id' => $jo_id]);
                        $allRows = $existingRows->fetchAll(PDO::FETCH_ASSOC);

                        $matchedRows = array_filter($allRows, function ($row) use ($periodYear, $periodMonth, $year) {
                            $ym = periodToYearMonth($row['period_covered'], $year);
                            return $ym !== null && $ym[0] === $periodYear && $ym[1] === $periodMonth;
                        });

                        if (!empty($matchedRows)) {
                            foreach ($matchedRows as $row) {
                                $conn->prepare("DELETE FROM `deductions` WHERE `deduct_id` = :did")
                                     ->execute([':did' => $row['deduct_id']]);
                                $conn->prepare("DELETE FROM `payment_index` WHERE `payindex_id` = :pid")
                                     ->execute([':pid' => $row['payindex_id']]);
                            }
                            $replacedCount = count($matchedRows);
                        } else {
                            $replacedCount = 0;
                        }

                        $conn->prepare(
                            "INSERT INTO `deductions`
                             (`lbp`,`pagibig_cont`,`pagibig_mpl`,`sss_cont`,`late_deduction`,`nursery_prod`)
                             VALUES (:lbp,:pc2,:pm,:sc,:ld,:np)"
                        )->execute([
                            ':lbp' => $lbp,        ':pc2' => $pagibig_cont,
                            ':pm'  => $pagibig_mpl, ':sc'  => $sss_cont,
                            ':ld'  => $late_deduction, ':np' => $nursery_prod,
                        ]);
                        $deduct_id = $conn->lastInsertId();

                        $conn->prepare(
                            "INSERT INTO `payment_index`
                             (`jo_id`,`period_covered`,`num_days`,`total_wage`,`deduct_id`,`total_amount_due`)
                             VALUES (:jid,:pc,:nd,:tw,:did,:tad)"
                        )->execute([
                            ':jid' => $jo_id,     ':pc'  => $period_covered,
                            ':nd'  => $num_days,  ':tw'  => $total_wage,
                            ':did' => $deduct_id, ':tad' => $total_amount_due,
                        ]);

                        $conn->commit();

                        $payload = [
                            'year'             => $year,
                            'name'             => $contract['name'],
                            'designation'      => $contract['designation'],
                            'date_from'        => $contract['date_from'],
                            'period_covered'   => $period_covered,
                            'num_days'         => $num_days,
                            'rate'             => $rate,
                            'total_wage'       => $total_wage,
                            'lbp'              => $lbp,
                            'pagibig_cont'     => $pagibig_cont,
                            'pagibig_mpl'      => $pagibig_mpl,
                            'sss_cont'         => $sss_cont,
                            'late_deduction'   => $late_deduction,
                            'nursery_prod'     => $nursery_prod,
                            'total_amount_due' => $total_amount_due,
                        ];

                        $py_dir   = __DIR__ . DIRECTORY_SEPARATOR . 'python_files';
                        $pi_dir   = __DIR__ . DIRECTORY_SEPARATOR . 'payment_index_file';
                        $tmp_file = $pi_dir . DIRECTORY_SEPARATOR . 'tmp_' . uniqid() . '.json';
                        $script   = $py_dir . DIRECTORY_SEPARATOR . 'update_excel.py';

                        if (!is_dir($py_dir)) {
                            throw new RuntimeException("python_files directory not found: $py_dir");
                        }
                        if (!is_dir($pi_dir)) {
                            throw new RuntimeException("payment_index_file directory not found: $pi_dir");
                        }
                        if (!is_writable($pi_dir)) {
                            throw new RuntimeException("payment_index_file directory is not writable.");
                        }
                        if (!file_exists($script)) {
                            throw new RuntimeException("update_excel.py not found in python_files/.");
                        }

                        file_put_contents($tmp_file, json_encode($payload));
                        $pyOutput = shell_exec('py ' . escapeshellarg($script) . ' ' . escapeshellarg($tmp_file) . ' 2>&1');
                        if (file_exists($tmp_file)) unlink($tmp_file);

                        if (!empty($pyOutput) && stripos($pyOutput, 'error') !== false) {
                            error_log("update_excel.py output: $pyOutput");
                            $alert_msg  = $replacedCount > 0
                                ? "Record replaced (removed $replacedCount previous entry) and saved, but Excel update reported a warning. Check server logs."
                                : 'Record saved, but Excel update reported a warning. Check server logs.';
                            $alert_type = 'warning';
                        } else {
                            $alert_msg  = $replacedCount > 0
                                ? "Previous entry for this person in the same month was replaced. Record saved and Excel updated successfully."
                                : 'Record saved successfully and Excel updated.';
                            $alert_type = 'success';
                        }

                    } catch (Exception $innerEx) {
                        if ($conn->inTransaction()) $conn->rollBack();
                        throw $innerEx;
                    }
                }
            } catch (RuntimeException $e) {
                $alert_msg  = 'File system error: ' . htmlspecialchars($e->getMessage());
                $alert_type = 'error';
            } catch (PDOException $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                $alert_msg  = 'Database error: ' . htmlspecialchars($e->getMessage());
                $alert_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GSIAS — Add Payment Index</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/payment_index_add.css">
    <style>
        .pi-name-wrap { position: relative; }
        .pi-name-wrap .pi-input { width: 100%; }
        #piNameDropdown {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0; right: 0;
            background: #fff;
            border: 1px solid #c8e0c8;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(26,61,31,0.13);
            max-height: 220px;
            overflow-y: auto;
            z-index: 500;
            list-style: none;
            padding: 4px 0;
        }
        #piNameDropdown .pi-dd-item {
            padding: 8px 14px; font-size: 0.84rem;
            cursor: pointer; color: #1a2e1c; transition: background 0.12s;
        }
        #piNameDropdown .pi-dd-item:hover,
        #piNameDropdown .pi-dd-item.active { background: #e8f5e9; }
        #piNameDropdown .pi-dd-item mark {
            background: #b9f0c0; color: #1a3d1f; border-radius: 2px; padding: 0 1px;
        }
        #piNameDropdown .pi-dd-empty {
            padding: 10px 14px; font-size: 0.82rem; color: #999; font-style: italic;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar"><?php include 'reusable_files/sidebar.php'; ?></aside>
    <div class="app-right">
        <div class="app-header"><?php include 'reusable_files/header.php'; ?></div>
        <main class="main-content">
            <div class="pi-wrapper">
                <h1 class="pi-title">Add Payment Index Entry</h1>

                <?php if ($alert_msg): ?>
                <div class="pi-alert <?= htmlspecialchars($alert_type) ?>"><?= $alert_msg ?></div>
                <?php endif; ?>
                <div id="piAlert" class="pi-alert"></div>

                <div class="pi-card">
                    <form id="piForm" method="POST" action="">
                        <div class="pi-section-label" style="margin-bottom:10px;">Step 1 — Filter Contract</div>
                        <div class="pi-row" style="margin-bottom:20px;">
                            <div class="pi-group w-sm" style="flex:0 0 100px;">
                                <label class="pi-label">Year</label>
                                <select id="piYear" name="year" class="pi-select" required>
                                    <option value="">— Year —</option>
                                    <?php foreach ($year_options as $yr): ?>
                                    <option value="<?= (int)$yr ?>" <?= ((int)$yr === (int)$saved_year) ? 'selected' : '' ?>>
                                        <?= (int)$yr ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="pi-group w-md" style="flex:0 0 150px;">
                                <label class="pi-label">Month</label>
                                <select id="piMonth" class="pi-select">
                                    <option value="">— Select Month —</option>
                                    <?php
                                    $months = ['January','February','March','April','May','June',
                                               'July','August','September','October','November','December'];
                                    foreach ($months as $i => $mName):
                                    ?>
                                    <option value="<?= $i + 1 ?>" <?= (($i + 1) === (int)$saved_month) ? 'selected' : '' ?>>
                                        <?= $mName ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="pi-group" id="piNameGroup" style="flex:2; opacity:0.4; pointer-events:none; min-width:250px;">
                                <label class="pi-label">Name (Contract)</label>
                                <input type="hidden" id="piNameHidden" name="jo_id">
                                <div class="pi-name-wrap">
                                    <input type="text" id="piNameSearch" class="pi-input"
                                           placeholder="— Select Year &amp; Month —"
                                           autocomplete="off" disabled>
                                    <ul id="piNameDropdown"></ul>
                                </div>
                            </div>

                            <div class="pi-group" id="piDesigGroup" style="flex:1; opacity:0.4; min-width:180px;">
                                <label class="pi-label">Designation</label>
                                <input type="text" id="piDesignation" class="pi-input" readonly placeholder="Auto-filled" style="cursor:default;">
                            </div>
                        </div>

                        <div class="pi-divider"></div>

                        <div class="pi-section-label" style="margin-top:4px;margin-bottom:14px;">Step 2 — Period &amp; Days</div>
                        <div class="pi-row" style="margin-bottom:20px;">
                            <div class="pi-group w-md">
                                <label class="pi-label">Period Covered</label>
                                <input type="text" id="piPeriod" name="period_covered" class="pi-input"
                                       placeholder="Select a month first" required
                                       value="<?= htmlspecialchars($saved_period) ?>">
                                <span id="piPeriodHint" style="font-size:0.7rem;color:#888;margin-top:2px;display:none;">
                                    ! An entry for this person already exists in this month and will be replaced.
                                </span>
                            </div>
                            <div class="pi-group w-sm">
                                <label class="pi-label">No. of Days</label>
                                <input type="number" id="piNumDays" name="num_days" class="pi-input"
                                       placeholder="0" min="0.5" step="0.5" required>
                            </div>
                            <div class="pi-group w-sm">
                                <label class="pi-label">Rate / Day</label>
                                <input type="text" id="piRate" class="pi-input" readonly placeholder="—" style="font-weight:700;">
                            </div>
                        </div>

                        <div class="pi-divider"></div>

                        <div class="pi-section-label" style="margin-top:4px;margin-bottom:14px;">Step 3 — Deductions</div>
                        <div class="pi-row">
                            <div class="pi-group w-sm">
                                <label class="pi-label">LBP</label>
                                <input type="number" name="lbp" id="piLbp" class="pi-input deduction-input" value="0" step="0.01" min="0">
                            </div>
                            <div class="pi-group w-sm">
                                <label class="pi-label">Pag-ibig Cont.</label>
                                <input type="number" name="pagibig_cont" id="piPagibigCont" class="pi-input deduction-input" value="0" step="0.01" min="0">
                            </div>
                            <div class="pi-group w-sm">
                                <label class="pi-label">Pag-ibig MPL</label>
                                <input type="number" name="pagibig_mpl" id="piPagibigMpl" class="pi-input deduction-input" value="0" step="0.01" min="0">
                            </div>
                            <div class="pi-group w-sm">
                                <label class="pi-label">SSS Cont.</label>
                                <input type="number" name="sss_cont" id="piSssCont" class="pi-input deduction-input" value="0" step="0.01" min="0">
                            </div>
                            <div class="pi-group w-sm">
                                <label class="pi-label">Late Deduction</label>
                                <input type="number" name="late_deduction" id="piLate" class="pi-input deduction-input" value="0" step="0.01" min="0">
                            </div>
                            <div class="pi-group w-sm">
                                <label class="pi-label">Nursery Product</label>
                                <input type="number" name="nursery_prod" id="piNursery" class="pi-input deduction-input" value="0" step="0.01" min="0">
                            </div>
                        </div>

                        <div class="pi-divider"></div>

                        <div class="pi-summary-row">
                            <div class="pi-summary-item">
                                <span class="pi-summary-label">Total Wage</span>
                                <span class="pi-summary-value" id="sumTotalWage">0.00</span>
                            </div>
                            <div class="pi-summary-item">
                                <span class="pi-summary-label">Total Deductions</span>
                                <span class="pi-summary-value" id="sumTotalDed">0.00</span>
                            </div>
                            <div class="pi-summary-item">
                                <span class="pi-summary-label">Amount Due</span>
                                <span class="pi-summary-value" id="sumAmtDue" style="font-size:1.2rem;color:#2a5c30;">0.00</span>
                            </div>
                        </div>

                        <div class="pi-footer" style="display:flex;justify-content:space-between;margin-top:20px;">
                            <a href="view_excel.php" class="pi-excel-link">View Excel</a>
                            <button type="button" class="pi-btn-save" id="piBtnSave">Save &amp; Update Excel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="pi-overlay" id="piOverlay">
    <div class="pi-modal">
        <div class="pi-modal-title">! Replace Existing Entry?</div>
        <div class="pi-modal-body" id="piModalBody">
            An entry for this person in the same month already exists. Saving will permanently delete the previous entry and replace it with this one.
        </div>
        <div class="pi-modal-footer">
            <button class="pi-btn-cancel"  id="piModalCancel">Cancel</button>
            <button class="pi-btn-confirm" id="piModalConfirm">Yes, Replace It</button>
        </div>
    </div>
</div>

<script>
    const PHP_SAVED = {
        year:  <?= json_encode((string)$saved_year) ?>,
        month: <?= json_encode((string)$saved_month) ?>,
        jo_id: <?= json_encode((string)($_POST['jo_id'] ?? '')) ?>
    };
</script>
<script src="js_files/payment_index_add.js"></script>
</body>
</html>