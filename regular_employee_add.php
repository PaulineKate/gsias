<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

$alert_msg  = '';
$alert_type = '';

/* ── AJAX: designation list ── */
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

/* ── AJAX: check if emp_id already exists ── */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_emp_id') {
    header('Content-Type: application/json');
    $id = trim($_GET['id'] ?? '');
    try {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) FROM `employee_info` WHERE `emp_id` = :id"
        );
        $stmt->execute([':id' => strtoupper($id)]);
        $exists = (int) $stmt->fetchColumn() > 0;
        echo json_encode(['exists' => $exists]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/* ── Format validators ── */
function valid_emp_id(string $v): bool { return (bool) preg_match('/^\d{4}\.\d-\d{3}$/', $v); }

/* ── Handle form submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id       = trim($_POST['emp_id']       ?? '');
    $last_name    = trim($_POST['last_name']    ?? '');
    $first_name   = trim($_POST['first_name']   ?? '');
    $middle_name  = trim($_POST['middle_name']  ?? '');
    $designation  = trim($_POST['designation']  ?? '');
    $salary       = trim($_POST['salary']       ?? '');
    $emp_standing = in_array($_POST['emp_standing'] ?? '', ['regular','casual'])
                    ? $_POST['emp_standing'] : 'regular';
    $department   = 'PGSO';

    $errors = [];

    if ($emp_id === '')      $errors[] = 'Employee ID is required.';
    if ($last_name === '')   $errors[] = 'Last name is required.';
    if ($first_name === '')  $errors[] = 'First name is required.';
    if ($designation === '') $errors[] = 'Designation is required.';
    if ($salary === '')      $errors[] = 'Salary is required.';

    if ($last_name !== ''  && mb_strlen($last_name)  < 2) $errors[] = 'Last name must be at least 2 characters.';
    if ($first_name !== '' && mb_strlen($first_name) < 2) $errors[] = 'First name must be at least 2 characters.';
    if ($salary !== ''     && !is_numeric($salary))        $errors[] = 'Salary must be a number.';

    if ($emp_id !== '' && !valid_emp_id($emp_id))
        $errors[] = 'Employee ID format must be ####.#-### (e.g. 1061.0-001).';

    /* Server-side duplicate ID check */
    if ($emp_id !== '' && valid_emp_id($emp_id) && empty($errors)) {
        try {
            $chk = $conn->prepare("SELECT COUNT(*) FROM `employee_info` WHERE `emp_id` = :id");
            $chk->execute([':id' => strtoupper($emp_id)]);
            if ((int) $chk->fetchColumn() > 0)
                $errors[] = 'Employee ID ' . htmlspecialchars($emp_id) . ' is already in use.';
        } catch (PDOException $e) { /* silently allow if DB check fails */ }
    }

    if (!empty($errors)) {
        $alert_msg  = implode('<br>', $errors);
        $alert_type = 'error';
    } else {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO `employee_info`
                 (`emp_id`,`last_name`,`first_name`,`middle_name`,`emp_designation`,
                  `salary`,`department`,`emp_standing`,`emp_status`)
                 VALUES (:emp_id,:last_name,:first_name,:middle_name,:designation,
                         :salary,:department,:emp_standing,1)"
            );
            $stmt->execute([
                ':emp_id'       => strtoupper($emp_id),
                ':last_name'    => strtoupper($last_name),
                ':first_name'   => strtoupper($first_name),
                ':middle_name'  => strtoupper($middle_name),
                ':designation'  => strtoupper($designation),
                ':salary'       => $salary,
                ':department'   => $department,
                ':emp_standing' => $emp_standing,
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
    <title>GSIAS — New Employee Record</title>

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

                        <!-- Row 1: Full name -->
                        <div class="emp-row">

                            <div class="emp-group">
                                <label class="emp-label">Surname <span class="emp-required">*</span></label>
                                <div class="emp-input-wrap">
                                    <input type="text" id="empLastName" name="last_name" class="emp-input"
                                           placeholder="SURNAME" autocomplete="off"
                                           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                                    <button type="button" class="emp-clear-btn" data-target="empLastName" aria-label="Clear">×</button>
                                </div>
                                <span class="emp-field-error" id="errLastName"></span>
                            </div>

                            <div class="emp-group">
                                <label class="emp-label">First Name <span class="emp-required">*</span></label>
                                <div class="emp-input-wrap">
                                    <input type="text" id="empFirstName" name="first_name" class="emp-input"
                                           placeholder="FIRST NAME" autocomplete="off"
                                           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                                    <button type="button" class="emp-clear-btn" data-target="empFirstName" aria-label="Clear">×</button>
                                </div>
                                <span class="emp-field-error" id="errFirstName"></span>
                            </div>

                            <div class="emp-group">
                                <label class="emp-label">Middle Name</label>
                                <div class="emp-input-wrap">
                                    <input type="text" id="empMiddleName" name="middle_name" class="emp-input"
                                           placeholder="MIDDLE NAME" autocomplete="off"
                                           value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                                    <button type="button" class="emp-clear-btn" data-target="empMiddleName" aria-label="Clear">×</button>
                                </div>
                                <span class="emp-field-error" id="errMiddleName"></span>
                            </div>

                        </div>

                        <!-- Row 2: ID, Department, Standing -->
                        <div class="emp-row">

                            <div class="emp-group">
                                <label class="emp-label">Employee ID <span class="emp-required">*</span></label>
                                <div class="emp-input-wrap">
                                    <input type="text" id="empId" name="emp_id" class="emp-input"
                                           placeholder="####.#-###" maxlength="10"
                                           value="<?= htmlspecialchars($_POST['emp_id'] ?? '') ?>" required>
                                    <span class="emp-id-spinner" id="empIdSpinner"></span>
                                    <span class="emp-id-status"  id="empIdStatus"></span>
                                    <button type="button" class="emp-clear-btn" data-target="empId" aria-label="Clear">×</button>
                                </div>
                                <span class="emp-field-error" id="errEmpId"></span>
                                <span class="emp-field-hint">Format: ####.#-### (e.g. 1061.0-001)</span>
                            </div>

                            <div class="emp-group">
                                <label class="emp-label">Department</label>
                                <input type="text" name="department" class="emp-input" value="PGSO" readonly>
                            </div>

                            <div class="emp-group">
                                <label class="emp-label">Employee Standing <span class="emp-required">*</span></label>
                                <select name="emp_standing" id="empStanding" class="emp-input emp-select" required>
                                    <option value="regular" <?= (($_POST['emp_standing'] ?? 'regular') === 'regular' ? 'selected' : '') ?>>Regular</option>
                                    <option value="casual"  <?= (($_POST['emp_standing'] ?? '') === 'casual' ? 'selected' : '') ?>>Casual</option>
                                </select>
                            </div>

                        </div>

                        <!-- Row 3: Designation, Salary -->
                        <div class="emp-row">

                            <div class="emp-group emp-group--desig">
                                <label class="emp-label">Designation <span class="emp-required">*</span></label>
                                <div class="emp-ac-wrap emp-input-wrap">
                                    <input type="text" id="empDesignation" name="designation" class="emp-input"
                                           placeholder="Type to search…" autocomplete="off"
                                           value="<?= htmlspecialchars($_POST['designation'] ?? '') ?>" required>
                                    <button type="button" class="emp-clear-btn" data-target="empDesignation" aria-label="Clear">×</button>
                                    <ul id="empDesigDropdown" class="emp-ac-dropdown"></ul>
                                </div>
                                <span class="emp-field-error" id="errDesignation"></span>
                            </div>

                            <div class="emp-group emp-group--salary">
                                <label class="emp-label">Salary <span class="emp-required">*</span></label>
                                <div class="emp-salary-wrap emp-input-wrap">
                                    <span class="emp-prefix">₱</span>
                                    <input type="text" id="empSalary" name="salary" class="emp-input"
                                           placeholder="0.00" inputmode="decimal"
                                           value="<?= htmlspecialchars($_POST['salary'] ?? '') ?>" required>
                                    <button type="button" class="emp-clear-btn" data-target="empSalary" aria-label="Clear">×</button>
                                </div>
                                <span class="emp-field-error" id="errSalary"></span>
                            </div>

                        </div>

                        <div class="emp-footer">
                            <button type="button" id="empSaveBtn" class="emp-btn-save">Save Record</button>
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
        <div class="emp-modal-body" id="empModalBody"></div>
        <div class="emp-modal-footer">
            <button class="emp-btn-cancel"  id="empModalCancel">Cancel</button>
            <button class="emp-btn-confirm" id="empModalConfirm">Yes, Save It</button>
        </div>
    </div>
</div>

<script src="js_files/regular_employee_add.js"></script>

</body>
</html>