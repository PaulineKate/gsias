<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

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

/* ── AJAX: toggle emp_status ── */
if (isset($_POST['ajax']) && $_POST['ajax'] === 'toggle_status') {
    header('Content-Type: application/json');
    $emp_id     = trim($_POST['emp_id'] ?? '');
    $new_status = ($_POST['new_status'] ?? '') === '1' ? 1 : 0;
    try {
        $stmt = $conn->prepare("UPDATE `employee_info` SET `emp_status` = :s WHERE `emp_id` = :id");
        $stmt->execute([':s' => $new_status, ':id' => $emp_id]);
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/* ── Load employee record ── */
$emp_id = trim($_GET['emp_id'] ?? '');
$emp    = null;

if ($emp_id !== '') {
    try {
        $stmt = $conn->prepare(
            "SELECT * FROM `employee_info` WHERE `emp_id` = :id LIMIT 1"
        );
        $stmt->execute([':id' => $emp_id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
}

if (!$emp) {
    header('Location: regular_employees.php');
    exit;
}

/* ── Handle UPDATE ── */
$modal_type    = '';
$modal_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    /* DELETE */
    if ($_POST['action'] === 'delete') {
        try {
            $stmt = $conn->prepare("DELETE FROM `employee_info` WHERE `emp_id` = :id");
            $stmt->execute([':id' => $emp_id]);
            $modal_type    = 'success';
            $modal_message = 'Employee record deleted successfully. Redirecting…';
            // redirect handled by JS after modal
        } catch (PDOException $e) {
            $modal_type    = 'error';
            $modal_message = 'Delete failed: ' . htmlspecialchars($e->getMessage());
        }
    }

    /* UPDATE */
    if ($_POST['action'] === 'update') {
        $designation  = trim($_POST['designation']  ?? '');
        $emp_standing = in_array($_POST['emp_standing'] ?? '', ['regular','casual'])
                        ? $_POST['emp_standing'] : $emp['emp_standing'];
        $salary       = trim($_POST['salary']       ?? '');

        $errors = [];
        if ($designation === '') $errors[] = 'Designation is required.';
        if ($salary === '')      $errors[] = 'Salary is required.';
        if ($salary !== '' && !is_numeric($salary)) $errors[] = 'Salary must be a number.';

        if (!empty($errors)) {
            $modal_type    = 'error';
            $modal_message = implode('<br>', $errors);
        } else {
            try {
                $stmt = $conn->prepare(
                    "UPDATE `employee_info`
                     SET `emp_designation` = :d, `emp_standing` = :s, `salary` = :sal
                     WHERE `emp_id` = :id"
                );
                $stmt->execute([
                    ':d'   => strtoupper($designation),
                    ':s'   => $emp_standing,
                    ':sal' => $salary,
                    ':id'  => $emp_id,
                ]);
                /* Refresh record */
                $stmt2 = $conn->prepare("SELECT * FROM `employee_info` WHERE `emp_id` = :id LIMIT 1");
                $stmt2->execute([':id' => $emp_id]);
                $emp = $stmt2->fetch(PDO::FETCH_ASSOC);

                $modal_type    = 'success';
                $modal_message = 'Employee record updated successfully.';
            } catch (PDOException $e) {
                $modal_type    = 'error';
                $modal_message = 'Update failed: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — Update Employee</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/employee_credentials.css">
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

                <div class="emp-top-bar">
                    <h1 class="emp-title">Update Employee Record</h1>
                    <a href="regular_employees.php" class="emp-btn-back">← Back to List</a>
                </div>

                <div id="empAlert" class="emp-alert"></div>

                <div class="emp-card">
                    <form id="empForm" method="POST"
                          action="employee_credentials.php?emp_id=<?= urlencode($emp_id) ?>">

                        <!-- ── Read-only identity block ── -->
                        <div class="emp-section-label">Employee Identity <span class="emp-section-note">(read-only)</span></div>

                        <div class="emp-row">
                            <div class="emp-group">
                                <label class="emp-label">Employee ID</label>
                                <input type="text" class="emp-input" value="<?= htmlspecialchars($emp['emp_id']) ?>" readonly>
                            </div>
                            <div class="emp-group">
                                <label class="emp-label">Surname</label>
                                <input type="text" class="emp-input" value="<?= htmlspecialchars($emp['last_name']) ?>" readonly>
                            </div>
                            <div class="emp-group">
                                <label class="emp-label">First Name</label>
                                <input type="text" class="emp-input" value="<?= htmlspecialchars($emp['first_name']) ?>" readonly>
                            </div>
                            <div class="emp-group">
                                <label class="emp-label">Middle Name</label>
                                <input type="text" class="emp-input" value="<?= htmlspecialchars($emp['middle_name']) ?>" readonly>
                            </div>
                        </div>

                        <div class="emp-row">
                            <div class="emp-group">
                                <label class="emp-label">Department</label>
                                <input type="text" class="emp-input" value="<?= htmlspecialchars($emp['department']) ?>" readonly>
                            </div>
                            <div class="emp-group emp-group--spacer"></div>
                            <div class="emp-group emp-group--spacer"></div>
                            <div class="emp-group emp-group--spacer"></div>
                        </div>

                        <div class="emp-divider"></div>

                        <!-- ── Editable fields ── -->
                        <div class="emp-section-label">Editable Fields</div>

                        <div class="emp-row">
                            <div class="emp-group emp-group--desig">
                                <label class="emp-label">Designation <span class="emp-required">*</span></label>
                                <div class="emp-ac-wrap">
                                    <input type="text" id="empDesignation" name="designation" class="emp-input"
                                           placeholder="Type to search…" autocomplete="off"
                                           value="<?= htmlspecialchars($emp['emp_designation']) ?>" required>
                                    <ul id="empDesigDropdown" class="emp-ac-dropdown"></ul>
                                </div>
                                <span class="emp-field-error" id="errDesignation"></span>
                            </div>
                            <div class="emp-group">
                                <label class="emp-label">Employee Standing <span class="emp-required">*</span></label>
                                <select name="emp_standing" id="empStanding" class="emp-input emp-select" required>
                                    <option value="regular" <?= $emp['emp_standing'] === 'regular' ? 'selected' : '' ?>>Regular</option>
                                    <option value="casual"  <?= $emp['emp_standing'] === 'casual'  ? 'selected' : '' ?>>Casual</option>
                                </select>
                            </div>
                            <div class="emp-group">
                                <label class="emp-label">Salary <span class="emp-required">*</span></label>
                                <div class="emp-salary-wrap">
                                    <span class="emp-prefix">₱</span>
                                    <input type="text" id="empSalary" name="salary" class="emp-input"
                                           placeholder="0.00" inputmode="decimal"
                                           value="<?= htmlspecialchars($emp['salary']) ?>" required>
                                </div>
                                <span class="emp-field-error" id="errSalary"></span>
                            </div>
                        </div>

                        <!-- ── Active / Inactive toggle ── -->
                        <div class="emp-row emp-row--status">
                            <div class="emp-group emp-group--toggle">
                                <label class="emp-label">Employee Status</label>
                                <div class="emp-toggle-wrap">
                                    <span class="emp-toggle-label" id="statusLabel">
                                        <?= $emp['emp_status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                    <label class="emp-toggle">
                                        <input type="checkbox" id="empStatusToggle"
                                               <?= $emp['emp_status'] ? 'checked' : '' ?>>
                                        <span class="emp-toggle-track">
                                            <span class="emp-toggle-thumb"></span>
                                        </span>
                                    </label>
                                </div>
                                <span class="emp-field-hint">Toggle to set Active / Inactive — saves immediately.</span>
                            </div>
                        </div>

                        <input type="hidden" name="action" id="formAction" value="update">

                        <div class="emp-footer">
                            <button type="button" id="empDeleteBtn" class="emp-btn-delete">Delete Record</button>
                            <button type="button" id="empUpdateBtn" class="emp-btn-save">Update Record</button>
                        </div>

                    </form>
                </div>

            </div>
        </main>

    </div>
</div>

<!-- ── Result modal ── -->
<div class="emp-overlay" id="empOverlay">
    <div class="emp-modal">
        <div class="emp-modal-icon" id="empModalIcon">✅</div>
        <div class="emp-modal-title" id="empModalTitle">Success</div>
        <div class="emp-modal-body"  id="empModalBody"></div>
        <div class="emp-modal-footer">
            <button class="emp-btn-confirm" id="empModalClose">OK</button>
        </div>
    </div>
</div>

<!-- ── Confirm update modal ── -->
<div class="emp-overlay" id="confirmOverlay">
    <div class="emp-modal">
        <div class="emp-modal-icon">💾</div>
        <div class="emp-modal-title">Save Changes?</div>
        <div class="emp-modal-body">You are about to update the record for <strong><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></strong>. Continue?</div>
        <div class="emp-modal-footer">
            <button class="emp-btn-cancel"  id="confirmCancel">Cancel</button>
            <button class="emp-btn-confirm" id="confirmProceed">Yes, Update</button>
        </div>
    </div>
</div>

<!-- ── Confirm delete modal ── -->
<div class="emp-overlay" id="deleteOverlay">
    <div class="emp-modal">
        <div class="emp-modal-icon">🗑️</div>
        <div class="emp-modal-title">Delete Record?</div>
        <div class="emp-modal-body">This will permanently delete the record for <strong><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></strong>. This cannot be undone.</div>
        <div class="emp-modal-footer">
            <button class="emp-btn-cancel"  id="deleteCancel">Cancel</button>
            <button class="emp-btn-delete-confirm" id="deleteProceed">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
    /* Pass PHP modal state to JS */
    const PHP_MODAL = {
        type:    <?= json_encode($modal_type) ?>,
        message: <?= json_encode($modal_message) ?>,
        isDelete: <?= json_encode($_POST['action'] ?? '' === 'delete') ?>
    };
    const EMP_ID = <?= json_encode($emp_id) ?>;
</script>
<script src="js_files/employee_credentials.js"></script>

</body>
</html>