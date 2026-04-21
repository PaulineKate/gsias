<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

$alert_msg  = '';
$alert_type = '';

/* AJAX endpoint — returns designation list for autocomplete */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_designations') {
    header('Content-Type: application/json');
    try {
        $rows = $conn->query(
            "SELECT `d_name` FROM `designation_list` ORDER BY `d_name` ASC"
        )->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode($rows);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/* Format validators */
function valid_emp_id(string $v): bool     { return (bool) preg_match('/^\d{4}\.\d-\d{3}$/', $v); }
function valid_sss(string $v): bool        { return (bool) preg_match('/^\d+$/', $v); }
function valid_philhealth(string $v): bool { return (bool) preg_match('/^\d{2}-\d{9}-\d$/', $v); }
function valid_tin(string $v): bool        { return (bool) preg_match('/^\d{3}-\d{3}-\d{3}-\d{4}$/', $v); }
function valid_gsis(string $v): bool       { return (bool) preg_match('/^\d{12}$/', $v); }
function valid_pagibig(string $v): bool    { return (bool) preg_match('/^\d{12}$/', $v); }

/* Handle form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id         = trim($_POST['emp_id']         ?? '');
    $last_name      = trim($_POST['last_name']       ?? '');
    $first_name     = trim($_POST['first_name']      ?? '');
    $middle_name    = trim($_POST['middle_name']     ?? '');
    $designation    = trim($_POST['designation']     ?? '');
    $salary         = trim($_POST['salary']          ?? '');
    $department     = 'PGSO';
    $sss_num        = trim($_POST['sss_num']         ?? '');
    $philhealth_num = trim($_POST['philhealth_num']  ?? '');
    $tin_num        = trim($_POST['tin_num']         ?? '');
    $gsis_num       = trim($_POST['gsis_num']        ?? '');
    $pagibig_num    = trim($_POST['pagibig_num']     ?? '');

    $errors = [];

    if ($emp_id === '')      $errors[] = 'Employee ID is required.';
    if ($last_name === '')   $errors[] = 'Last name is required.';
    if ($first_name === '')  $errors[] = 'First name is required.';
    if ($designation === '') $errors[] = 'Designation is required.';
    if ($salary === '')      $errors[] = 'Salary is required.';

    if ($last_name !== '' && mb_strlen($last_name) < 2)
        $errors[] = 'Last name must be at least 2 characters.';
    if ($first_name !== '' && mb_strlen($first_name) < 2)
        $errors[] = 'First name must be at least 2 characters.';
    if ($salary !== '' && !is_numeric($salary))
        $errors[] = 'Salary must be a number.';

    /* N/A is always accepted for deduction numbers */
    $na = fn(string $v): bool => strtoupper(trim($v)) === 'N/A';

    if ($emp_id !== '' && !valid_emp_id($emp_id))
        $errors[] = 'Employee ID format must be ####.#-### (e.g. 1061.0-001).';
    if ($sss_num !== '' && !$na($sss_num) && !valid_sss($sss_num))
        $errors[] = 'SSS No. must contain digits only (or N/A).';
    if ($philhealth_num !== '' && !$na($philhealth_num) && !valid_philhealth($philhealth_num))
        $errors[] = 'PhilHealth No. format must be ##-#########-# (e.g. 12-123456789-0) or N/A.';
    if ($tin_num !== '' && !$na($tin_num) && !valid_tin($tin_num))
        $errors[] = 'TIN (BIR) No. format must be ###-###-###-#### (e.g. 123-456-789-0000) or N/A.';
    if ($gsis_num !== '' && !$na($gsis_num) && !valid_gsis($gsis_num))
        $errors[] = 'GSIS No. must be exactly 12 digits (or N/A).';
    if ($pagibig_num !== '' && !$na($pagibig_num) && !valid_pagibig($pagibig_num))
        $errors[] = 'Pag-IBIG No. must be exactly 12 digits (or N/A).';

    if (!empty($errors)) {
        $alert_msg  = implode('<br>', $errors);
        $alert_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO `employee_info`
                 (`emp_id`,`last_name`,`first_name`,`middle_name`,`emp_designation`,
                  `salary`,`department`,`sss_num`,`philhealth_num`,`tin_num`,
                  `gsis_num`,`pagibig_num`,`emp_standing`,`emp_status`)
                 VALUES (:emp_id,:last_name,:first_name,:middle_name,:designation,
                         :salary,:department,:sss_num,:philhealth_num,:tin_num,
                         :gsis_num,:pagibig_num,'regular',1)"
            );
            $stmt->execute([
                ':emp_id'         => strtoupper($emp_id),
                ':last_name'      => strtoupper($last_name),
                ':first_name'     => strtoupper($first_name),
                ':middle_name'    => strtoupper($middle_name),
                ':designation'    => strtoupper($designation),
                ':salary'         => $salary,
                ':department'     => $department,
                ':sss_num'        => $sss_num,
                ':philhealth_num' => $philhealth_num,
                ':tin_num'        => $tin_num,
                ':gsis_num'       => $gsis_num,
                ':pagibig_num'    => $pagibig_num,
            ]);
            $alert_msg  = 'Employee record saved successfully.';
            $alert_type = 'success';
        } catch (PDOException $e) {
            $alert_msg  = 'Database error: ' . htmlspecialchars($e->getMessage());
            $alert_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — Regular Employee Add</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/regular_employee_add.css">
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
            <div class="emp-wrapper">

                <h1 class="emp-title">New Employee Record</h1>

                <?php if ($alert_msg): ?>
                <div class="emp-alert <?= htmlspecialchars($alert_type) ?>"><?= $alert_msg ?></div>
                <?php endif; ?>
                <div id="empAlert" class="emp-alert"></div>

                <div class="emp-card">
                    <form id="empForm" method="POST" action="">

                        <div class="emp-section-label">Employee Details</div>

                        <!-- Full name -->
                        <div class="emp-row">
                            <div class="emp-group">
                                <label class="emp-label">Surname <span style="color:#c0392b">*</span></label>
                                <input type="text" id="empLastName" name="last_name" class="emp-input"
                                       placeholder="SURNAME" autocomplete="off"
                                       value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                                <span class="emp-field-error" id="errLastName"></span>
                            </div>
                            <div class="emp-group">
                                <label class="emp-label">First Name <span style="color:#c0392b">*</span></label>
                                <input type="text" id="empFirstName" name="first_name" class="emp-input"
                                       placeholder="FIRST NAME" autocomplete="off"
                                       value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                                <span class="emp-field-error" id="errFirstName"></span>
                            </div>
                            <div class="emp-group">
                                <label class="emp-label">Middle Name</label>
                                <input type="text" id="empMiddleName" name="middle_name" class="emp-input"
                                       placeholder="MIDDLE NAME" autocomplete="off"
                                       value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                                <span class="emp-field-error" id="errMiddleName"></span>
                            </div>
                        </div>

                        <!-- Employee ID and department (readonly PGSO) -->
                        <div class="emp-row">
                            <div class="emp-group w-lg">
                                <label class="emp-label">Employee ID <span style="color:#c0392b">*</span></label>
                                <input type="text" id="empId" name="emp_id" class="emp-input"
                                       placeholder="####.#-###" maxlength="10"
                                       value="<?= htmlspecialchars($_POST['emp_id'] ?? '') ?>" required>
                                <span class="emp-field-error" id="errEmpId"></span>
                                <span class="emp-field-hint">Format: ####.#-### (e.g. 1061.0-001)</span>
                            </div>
                            <div class="emp-group w-lg">
                                <label class="emp-label">Department</label>
                                <input type="text" name="department" class="emp-input" value="PGSO" readonly>
                            </div>
                            <div class="emp-group w-lg">
                                <label class="emp-label">Employee Standing</label>
                                <input type="text" name="emp_standing" class="emp-input" value="regular" readonly>
                            </div>
                            <div class="emp-group w-lg">
                                <label class="emp-label">Designation <span style="color:#c0392b">*</span></label>
                                <div class="emp-ac-wrap">
                                    <input type="text" id="empDesignation" name="designation" class="emp-input"
                                           placeholder="Type to search…" autocomplete="off"
                                           value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>" required>
                                    <ul id="empDesigDropdown" class="emp-ac-dropdown"></ul>
                                </div>
                                <span class="emp-field-error" id="errDesignation"></span>
                            </div>
                            <div class="emp-group w-lg">
                                <label class="emp-label">Salary <span style="color:#c0392b">*</span></label>
                                <div class="emp-salary-wrap">
                                    <span class="emp-prefix">₱</span>
                                    <input type="text" id="empSalary" name="salary" class="emp-input"
                                           placeholder="0.00" inputmode="decimal"
                                           value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>" required>
                                </div>
                                <span class="emp-field-error" id="errSalary"></span>
                            </div>
                            <div class="emp-group" style="flex:0;"></div>
                        </div>

                        <div class="emp-divider"></div>

                        <div class="emp-section-label">Deductions Details</div>

                        <div class="emp-row">
                            <div class="emp-group">
                                <label class="emp-label">SSS No.</label>
                                <input type="text" id="empSss" name="sss_num" class="emp-input"
                                       placeholder="Digits only" inputmode="numeric"
                                       value="<?= htmlspecialchars($_POST['sss_num'] ?? '') ?>">
                                <span class="emp-field-error" id="errSss"></span>
                                <span class="emp-field-hint">Digits only, or type N/A</span>
                            </div>
                            <div class="emp-group">
                                <label class="emp-label">PhilHealth No.</label>
                                <input type="text" id="empPhilhealth" name="philhealth_num" class="emp-input"
                                       placeholder="##-#########-#" maxlength="14"
                                       value="<?= htmlspecialchars($_POST['philhealth_num'] ?? '') ?>">
                                <span class="emp-field-error" id="errPhilhealth"></span>
                                <span class="emp-field-hint">Format: ##-#########-#, or N/A</span>
                            </div>
                        </div>

                        <div class="emp-row">
                            <div class="emp-group">
                                <label class="emp-label">TIN No. (BIR)</label>
                                <input type="text" id="empTin" name="tin_num" class="emp-input"
                                       placeholder="###-###-###-####" maxlength="16"
                                       value="<?= htmlspecialchars($_POST['tin_num'] ?? '') ?>">
                                <span class="emp-field-error" id="errTin"></span>
                                <span class="emp-field-hint">Format: ###-###-###-####, or N/A</span>
                            </div>
                            <div class="emp-group">
                                <label class="emp-label">GSIS No.</label>
                                <input type="text" id="empGsis" name="gsis_num" class="emp-input"
                                       placeholder="############" maxlength="12" inputmode="numeric"
                                       value="<?= htmlspecialchars($_POST['gsis_num'] ?? '') ?>">
                                <span class="emp-field-error" id="errGsis"></span>
                                <span class="emp-field-hint">12 digits, or N/A</span>
                            </div>
                        </div>

                        <div class="emp-row">
                            <div class="emp-group w-lg">
                                <label class="emp-label">Pag-IBIG No.</label>
                                <input type="text" id="empPagibig" name="pagibig_num" class="emp-input"
                                       placeholder="############" maxlength="12" inputmode="numeric"
                                       value="<?= htmlspecialchars($_POST['pagibig_num'] ?? '') ?>">
                                <span class="emp-field-error" id="errPagibig"></span>
                                <span class="emp-field-hint">12 digits, or N/A</span>
                            </div>
                            <div class="emp-group" style="flex:3;"></div>
                        </div>

                        <div class="emp-footer">
                            <button type="button" id="empSaveBtn" class="emp-btn-save">Save Changes</button>
                        </div>

                    </form>
                </div>

            </div>
        </main>

    </div>

</div>

<!-- Confirm modal -->
<div class="emp-overlay" id="empOverlay">
    <div class="emp-modal">
        <div class="emp-modal-icon">💾</div>
        <div class="emp-modal-title">Save Employee Record?</div>
        <div class="emp-modal-body" id="empModalBody">Are you sure you want to save this new employee record?</div>
        <div class="emp-modal-footer">
            <button class="emp-btn-cancel"  id="empModalCancel">Cancel</button>
            <button class="emp-btn-confirm" id="empModalConfirm">Yes, Save It</button>
        </div>
    </div>
</div>

<script src="js_files/regular_employee_add.js"></script>

</body>
</html>