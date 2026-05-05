<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

$alert_msg  = '';
$alert_type = '';

/* Valid appointment natures */
$valid_appointment_natures = ['Original', 'Reappointment', 'Reemployment'];

/* ── Still validate position titles server-side via DB ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date_from  = trim($_POST['date_from']  ?? '');
    $date_to    = trim($_POST['date_to']    ?? '');
    $ref_folder = trim($_POST['ref_folder'] ?? '');

    $first_names          = $_POST['first_name']          ?? [];
    $last_names           = $_POST['last_name']           ?? [];
    $middle_names         = $_POST['middle_name']         ?? [];
    $name_extensions      = $_POST['name_extension']      ?? [];
    $position_titles      = $_POST['position_title']      ?? [];
    $pay_grades           = $_POST['pay_grade']           ?? [];
    $daily_wages          = $_POST['daily_wage']          ?? [];
    $appointment_natures  = $_POST['appointment_nature']  ?? [];

    /* ── Date validation ── */
    if (empty($date_from) || empty($date_to)) {
        $alert_msg  = 'Both FROM and TO dates are required.';
        $alert_type = 'error';
    } elseif ($date_to <= $date_from) {
        $alert_msg  = 'The "To" date must be after the "From" date.';
        $alert_type = 'error';
    }

    /* ── Ref folder required ── */
    if ($alert_type !== 'error' && $ref_folder === '') {
        $alert_msg  = 'Reference Folder name is required.';
        $alert_type = 'error';
    }

    /* ── First / Last name validation ── */
    if ($alert_type !== 'error') {
        foreach ($first_names as $i => $fn) {
            $fn = trim($fn);
            $ln = trim($last_names[$i] ?? '');

            if ($fn === '') { $alert_msg = 'First name is required for every row.'; $alert_type = 'error'; break; }
            if ($ln === '') { $alert_msg = 'Last name is required for every row.';  $alert_type = 'error'; break; }
            if (preg_match('/[0-9]/', $fn)) { $alert_msg = 'First names must not contain numbers.'; $alert_type = 'error'; break; }
            if (preg_match('/[0-9]/', $ln)) { $alert_msg = 'Last names must not contain numbers.';  $alert_type = 'error'; break; }
            if (mb_strlen($fn) < 2) { $alert_msg = 'Each first name must be at least 2 characters.'; $alert_type = 'error'; break; }
            if (mb_strlen($ln) < 2) { $alert_msg = 'Each last name must be at least 2 characters.';  $alert_type = 'error'; break; }

            $mn = trim($middle_names[$i] ?? '');
            if ($mn !== '' && preg_match('/[0-9]/', $mn)) {
                $alert_msg = 'Middle names must not contain numbers.'; $alert_type = 'error'; break;
            }

            $ext = trim($name_extensions[$i] ?? 'N/A');
        }
    }

    /* ── Position title validation (DB lookup) ── */
    if ($alert_type !== 'error') {
        try {
            $valid_positions_upper = $conn->query(
                "SELECT UPPER(`p_name`) FROM `position_titles`"
            )->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $valid_positions_upper = [];
        }

        foreach ($position_titles as $pt) {
            $pt = strtoupper(trim($pt));
            if (empty($pt) || !in_array($pt, $valid_positions_upper, true)) {
                $alert_msg  = 'Invalid position title: "' . htmlspecialchars(trim($pt)) . '". Please select from the list.';
                $alert_type = 'error';
                break;
            }
        }
    }

    /* ── Pay grade validation (1–10) ── */
    if ($alert_type !== 'error') {
        foreach ($pay_grades as $pg) {
            $pg = (int) trim($pg);
            if ($pg < 1 || $pg > 10) {
                $alert_msg = 'Pay grade must be between 1 and 10.'; $alert_type = 'error'; break;
            }
        }
    }

    /* ── Daily wage validation ── */
    if ($alert_type !== 'error') {
        foreach ($daily_wages as $dw) {
            $dw = trim($dw);
            if ($dw === '' || !preg_match('/^\d+(\.\d{1,2})?$/', $dw) || (float)$dw <= 0) {
                $alert_msg = 'Daily wage must be a positive number (e.g. 500 or 550.50).'; $alert_type = 'error'; break;
            }
        }
    }

    /* ── Appointment nature validation ── */
    if ($alert_type !== 'error') {
        foreach ($appointment_natures as $an) {
            if (!in_array(trim($an), $valid_appointment_natures, true)) {
                $alert_msg  = 'Invalid appointment nature: "' . htmlspecialchars(trim($an)) . '".';
                $alert_type = 'error';
                break;
            }
        }
    }

    /* ── PDF upload (optional) ── */
    $ref_file = 0;

    if ($alert_type !== 'error' && !empty($_FILES['pdf_file']['name'])) {
        $upload_dir = 'casual_contract_files/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $original_name = basename($_FILES['pdf_file']['name']);
        $safe_name     = preg_replace('/[^A-Za-z0-9._\-]/', '_', $original_name);
        $file_ext      = strtolower(pathinfo($safe_name, PATHINFO_EXTENSION));

        if ($file_ext !== 'pdf') {
            $alert_msg = 'Only PDF files are allowed.'; $alert_type = 'error';
        } elseif ($_FILES['pdf_file']['size'] > 20 * 1024 * 1024) {
            $alert_msg = 'PDF file must be under 20 MB.'; $alert_type = 'error';
        } else {
            if ($ref_folder === '') $ref_folder = strtoupper(pathinfo($safe_name, PATHINFO_FILENAME));
            $save_name   = strtoupper($ref_folder) . '.pdf';
            $target_path = $upload_dir . $save_name;
            if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_path)) {
                $alert_msg = 'Failed to upload PDF. Check folder permissions.'; $alert_type = 'error';
            } else {
                $ref_file = 1;
            }
        }
    }

    /* ── Insert rows if no error ── */
    if ($alert_type !== 'error') {
        try {
            $sql = "INSERT INTO `casual_contracts`
                (`first_name`, `last_name`, `middle_name`, `name_extension`,
                `position_title`, `pay_grade`, `daily_wage`,
                `employment_period_from`, `employment_period_to`,
                `appointment_nature`, `ref_folder`)
            VALUES
                (:first_name, :last_name, :middle_name, :name_extension,
                :position_title, :pay_grade, :daily_wage,
                :date_from, :date_to,
                :appointment_nature, :ref_folder)";

            $stmt     = $conn->prepare($sql);
            $inserted = 0;

            foreach ($first_names as $i => $fn) {
                $fn = trim($fn);
                if ($fn === '') continue;

                $ln  = trim($last_names[$i]        ?? '');
                $mn  = trim($middle_names[$i]       ?? '');
                $ext = trim($name_extensions[$i]    ?? 'N/A');
                $pt  = strtoupper(trim($position_titles[$i]   ?? ''));
                $pg  = (int) trim($pay_grades[$i]   ?? 0);
                $dw  = (float) trim($daily_wages[$i] ?? 0);
                $an  = trim($appointment_natures[$i] ?? '');

                if ($ext === '') $ext = 'N/A';

                $stmt->execute([
                    ':first_name'         => strtoupper($fn),
                    ':last_name'          => strtoupper($ln),
                    ':middle_name'        => $mn !== '' ? strtoupper($mn) : null,
                    ':name_extension'     => strtoupper($ext),
                    ':position_title'     => $pt,
                    ':pay_grade'          => $pg,
                    ':daily_wage'         => $dw,
                    ':date_from'          => $date_from,
                    ':date_to'            => $date_to,
                    ':appointment_nature' => $an,
                    ':ref_folder'         => strtoupper($ref_folder),
                ]);
                $inserted++;
            }

            if ($inserted > 0) {
                $alert_msg  = $inserted . ' record(s) saved successfully!';
                $alert_type = 'success';
            } else {
                $alert_msg  = 'No records were saved. Please fill in at least one row.';
                $alert_type = 'error';
            }

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
    <title>GSIAS — Add Casual Contract</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/casual_contract_reg.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green-dark:    #1a3d1f;
            --green-mid:     #2a5c30;
            --green-light:   #e8f5e9;
            --sidebar-width: 220px;
            --header-height: 56px;
        }
        html, body {
            height: 100%;
            font-family: 'Source Sans 3', sans-serif;
            background: var(--green-light);
            color: #1a2e1c;
        }
        .app-shell   { display: flex; height: 100vh; overflow: hidden; }
        .app-sidebar { width: var(--sidebar-width); flex-shrink: 0; height: 100vh; overflow-y: auto; overflow-x: hidden; }
        .app-right   { flex: 1; display: flex; flex-direction: column; min-width: 0; overflow: hidden; }
        .app-header  { flex-shrink: 0; }
        @media (max-width: 768px) { :root { --sidebar-width: 64px; } }

        /* ── AJAX loading shimmer on autocomplete inputs ── */
        .casc-ac-input.casc-ac-loading {
            background-image: linear-gradient(90deg, #e8f0e8 25%, #d4e8d4 50%, #e8f0e8 75%);
            background-size: 200% 100%;
            animation: cascShimmer 1.2s infinite;
            pointer-events: none;
        }
        @keyframes cascShimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .casc-ac-loading::placeholder { color: #a0b8a0; }
    </style>

    <!-- POSITION_TITLE_LIST is populated via AJAX — no inline PHP echo -->
    <script>
        /* Will be filled after AJAX completes; JS references this global */
        var POSITION_TITLE_LIST = [];
    </script>
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
            <div class="casc-wrapper">

                <h1 class="casc-title">Add New Casual Contract</h1>

                <?php if ($alert_msg) : ?>
                <div class="casc-alert <?= $alert_type ?>">
                    <?= htmlspecialchars($alert_msg) ?>
                </div>
                <?php endif; ?>
                <div id="cascAlert" class="casc-alert"></div>

                <div class="casc-card">
                    <div class="casc-card-title">Casual Contract Details</div>

                    <form id="cascForm" method="POST" action="" enctype="multipart/form-data">

                        <!-- ── Top Row: dates + ref folder ── -->
                        <div class="casc-top-row">
                            <div class="casc-top-group">
                                <span class="casc-top-group-label">From</span>
                                <div class="casc-top-inner">
                                    <span class="casc-field-label">Date :</span>
                                    <input type="date" id="cascDateFrom" name="date_from"
                                           class="casc-input w-date" required>
                                </div>
                            </div>
                            <div class="casc-top-group">
                                <span class="casc-top-group-label">To</span>
                                <div class="casc-top-inner">
                                    <span class="casc-field-label">Date :</span>
                                    <input type="date" id="cascDateTo" name="date_to"
                                           class="casc-input w-date" required>
                                </div>
                            </div>
                            <div class="casc-top-group">
                                <span class="casc-top-group-label">&nbsp;</span>
                                <div class="casc-top-inner">
                                    <span class="casc-field-label">Reference Folder :</span>
                                    <input type="text" id="cascRefFolder" name="ref_folder"
                                           class="casc-input w-ref" placeholder="Type or upload PDF">
                                </div>
                            </div>
                        </div>

                        <div class="casc-divider"></div>

                        <!-- ── Bulk-add + PDF Upload row ── -->
                        <div class="casc-pdf-row">

                            <!-- Bulk entry count -->
                            <span class="casc-bulk-label">Add rows :</span>
                            <input type="number" id="cascBulkCount" class="casc-bulk-input"
                                   value="1" min="1" max="50" placeholder="1">
                            <button type="button" class="casc-bulk-btn" onclick="cascBulkAdd()">
                                + Add Rows
                            </button>

                            <span style="border-left:2px solid #b2cfb5;height:24px;display:inline-block;margin:0 6px;"></span>

                            <!-- PDF upload -->
                            <span class="casc-pdf-label-text">Upload PDF (optional) :</span>
                            <button type="button" class="casc-btn-upload">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="12" y1="18" x2="12" y2="12"/>
                                    <line x1="9" y1="15" x2="15" y2="15"/>
                                </svg>
                                Upload PDF
                                <input type="file" id="cascPdfFile" name="pdf_file"
                                       accept=".pdf" class="casc-file-input">
                            </button>
                            <span class="casc-pdf-or">— or type the folder name above</span>
                            <span id="cascPdfName" class="casc-pdf-name"></span>
                            <span class="casc-pdf-or" style="color:#2a5c30;font-style:normal;font-size:0.72rem;">
                                - If a PDF with the same name already exists, it will be replaced.
                            </span>
                        </div>

                        <!-- ── Grid Headers ── -->
                        <div class="casc-grid-header">
                            <span class="casc-grid-col-label">Last Name :</span>
                            <span class="casc-grid-col-label">First Name :</span>
                            <span class="casc-grid-col-label">Ext :</span>
                            <span class="casc-grid-col-label">Middle Name :</span>
                            <span class="casc-grid-col-label">Position Title :</span>
                            <span class="casc-grid-col-label">Pay Grade :</span>
                            <span class="casc-grid-col-label">Daily Wage :</span>
                            <span class="casc-grid-col-label">Appt. Nature :</span>
                            <span></span>
                        </div>

                        <!-- ── Entry Rows ── -->
                        <div id="cascRows" class="casc-rows-container">
                            <div class="casc-entry-row">

                                <div class="casc-field-wrap">
                                    <input type="text" class="casc-input casc-name-input"
                                        name="last_name[]" placeholder="Last name"
                                        autocomplete="off" required>
                                    <span class="casc-field-error"></span>
                                </div>

                                <div class="casc-field-wrap">
                                    <input type="text" class="casc-input casc-name-input"
                                        name="first_name[]" placeholder="First name"
                                        autocomplete="off" required>
                                    <span class="casc-field-error"></span>
                                </div>

                                <div class="casc-field-wrap">
                                    <input type="text" class="casc-input casc-ext-input"
                                        name="name_extension[]" placeholder="N/A"
                                        value="N/A" maxlength="5" autocomplete="off">
                                    <span class="casc-field-error"></span>
                                </div>

                                <div class="casc-field-wrap">
                                    <input type="text" class="casc-input casc-name-input"
                                        name="middle_name[]" placeholder="Middle name"
                                        autocomplete="off">
                                    <span class="casc-field-error"></span>
                                </div>

                                <div class="casc-field-wrap casc-ac-wrap">
                                    <input type="text" class="casc-input casc-ac-input casc-ac-loading"
                                        name="position_title[]"
                                        placeholder="Loading…"
                                        autocomplete="off" required>
                                    <span class="casc-field-error"></span>
                                    <ul class="casc-ac-dropdown"></ul>
                                </div>

                                <div class="casc-field-wrap">
                                    <input type="number" class="casc-input casc-paygrade-input"
                                        name="pay_grade[]" placeholder="1–10"
                                        min="1" max="10" required>
                                    <span class="casc-field-error"></span>
                                </div>

                                <div class="casc-field-wrap">
                                    <input type="text" class="casc-input casc-wage-input"
                                        name="daily_wage[]" placeholder="0.00" required>
                                    <span class="casc-field-error"></span>
                                </div>

                                <div class="casc-field-wrap">
                                    <select class="casc-select casc-appt-select"
                                            name="appointment_nature[]" required>
                                        <option value="">— Select —</option>
                                        <option value="Original">Original</option>
                                        <option value="Reappointment">Reappointment</option>
                                        <option value="Reemployment">Reemployment</option>
                                    </select>
                                    <span class="casc-field-error"></span>
                                </div>

                                <button type="button" class="casc-btn-add-row"
                                        onclick="cascAddEntryRow()" title="Add another row">+</button>
                            </div>
                        </div>

                        <!-- ── Footer ── -->
                        <div class="casc-footer">
                            <button type="submit" class="casc-btn-save">Save</button>
                        </div>

                    </form>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="js_files/casual_contract_reg.js"></script>
</body>
</html>