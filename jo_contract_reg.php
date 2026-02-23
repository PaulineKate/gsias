<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

$alert_msg  = '';
$alert_type = '';

$funding_charges_options = [];
$designation_options     = [];

try {
    $funding_charges_options = $conn->query(
        "SELECT `fc_name` FROM `funding_charges_list` ORDER BY `fc_name` ASC"
    )->fetchAll(PDO::FETCH_COLUMN);

    $designation_options = $conn->query(
        "SELECT `d_name` FROM `designation_list` ORDER BY `d_name` ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    /* fail silently */
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date_from  = trim($_POST['date_from']  ?? '');
    $date_to    = trim($_POST['date_to']    ?? '');
    $ref_folder = trim($_POST['ref_folder'] ?? '');

    $names           = $_POST['name']            ?? [];
    $designations    = $_POST['designation']     ?? [];
    $funding_charges = $_POST['funding_charges'] ?? [];
    $rates           = $_POST['rate']            ?? [];

    /* ── Name validation ── */
    $name_error = '';
    foreach ($names as $name) {
        $n = trim($name);
        if ($n === '') continue;
        if (preg_match('/[0-9]/', $n)) {
            $name_error = 'Names must not contain numbers.';
            break;
        }
        if (mb_strlen($n) < 5) {
            $name_error = 'Each name must be at least 5 characters long.';
            break;
        }
    }
    if ($name_error) {
        $alert_msg  = $name_error;
        $alert_type = 'error';
    }

    if ($alert_type !== 'error') {
        $valid_designations    = array_map('strtoupper', $designation_options);
        $valid_funding_charges = array_map('strtoupper', $funding_charges_options);

        foreach ($designations as $d) {
            if (!in_array(strtoupper(trim($d)), $valid_designations, true)) {
                $alert_msg  = 'Invalid designation: "' . htmlspecialchars(trim($d)) . '". Please select from the list.';
                $alert_type = 'error';
                break;
            }
        }
    }

    if ($alert_type !== 'error') {
        $valid_funding_charges = array_map('strtoupper', $funding_charges_options);
        foreach ($funding_charges as $f) {
            if (!in_array(strtoupper(trim($f)), $valid_funding_charges, true)) {
                $alert_msg  = 'Invalid funding charge: "' . htmlspecialchars(trim($f)) . '". Please select from the list.';
                $alert_type = 'error';
                break;
            }
        }
    }

    $ref_file = 0;

    if ($alert_type !== 'error' && !empty($_FILES['pdf_file']['name'])) {
        $upload_dir = 'JO_Contract_files/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $original_name = basename($_FILES['pdf_file']['name']);
        $safe_name     = preg_replace('/[^A-Za-z0-9._\-]/', '_', $original_name);
        $file_ext      = strtolower(pathinfo($safe_name, PATHINFO_EXTENSION));

        if ($file_ext !== 'pdf') {
            $alert_msg  = 'Only PDF files are allowed.';
            $alert_type = 'error';
        } elseif ($_FILES['pdf_file']['size'] > 20 * 1024 * 1024) {
            $alert_msg  = 'PDF file must be under 20MB.';
            $alert_type = 'error';
        } else {
            if ($ref_folder === '') {
                $ref_folder = strtoupper(pathinfo($safe_name, PATHINFO_FILENAME));
            }

            $save_name   = strtoupper($ref_folder) . '.pdf';
            $target_path = $upload_dir . $save_name;

            if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_path)) {
                $alert_msg  = 'Failed to upload PDF. Check folder permissions.';
                $alert_type = 'error';
            } else {
                $ref_file = 1;
            }
        }
    }

    /* ── Insert rows if no error ── */
    if ($alert_type !== 'error') {
        try {
            $sql = "INSERT INTO `jo_contracts`
                        (`name`, `designation`, `rate`, `date_from`, `date_to`,
                         `funding_charges`, `ref_folder`, `ref_file`)
                    VALUES
                        (:name, :designation, :rate, :date_from, :date_to,
                         :funding_charges, :ref_folder, :ref_file)";

            $stmt     = $conn->prepare($sql);
            $inserted = 0;

            foreach ($names as $i => $name) {
                $name_val  = trim($name);
                $desig_val = trim($designations[$i]    ?? '');
                $fund_val  = trim($funding_charges[$i] ?? '');
                $rate_val  = trim($rates[$i]           ?? '');

                if ($name_val === '') continue;

                $stmt->execute([
                    ':name'            => strtoupper($name_val),
                    ':designation'     => strtoupper($desig_val),
                    ':rate'            => (float) $rate_val,
                    ':date_from'       => $date_from,
                    ':date_to'         => $date_to,
                    ':funding_charges' => strtoupper($fund_val),
                    ':ref_folder'      => strtoupper($ref_folder),
                    ':ref_file'        => $ref_file,
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
    <title>GSIAS — Add JO Contract</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/jo_contract_reg.css">
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
    </style>

    <!-- Pass DB lists to JS -->
    <script>
        const DESIGNATION_LIST     = <?= json_encode(array_values($designation_options)) ?>;
        const FUNDING_CHARGES_LIST = <?= json_encode(array_values($funding_charges_options)) ?>;
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
            <div class="joreg-wrapper">

                <h1 class="joreg-title">Add New J. O. Contract</h1>

                <?php if ($alert_msg) : ?>
                <div class="joreg-alert <?= $alert_type ?>">
                    <?= htmlspecialchars($alert_msg) ?>
                </div>
                <?php endif; ?>
                <div id="joregAlert" class="joreg-alert"></div>

                <div class="joreg-card">
                    <div class="joreg-card-title">Job Order Contract Details</div>

                    <form id="joregForm" method="POST" action="" enctype="multipart/form-data">

                        <!-- ── Top Row ── -->
                        <div class="joreg-top-row">
                            <div class="joreg-top-group">
                                <span class="joreg-top-group-label">From</span>
                                <div class="joreg-top-inner">
                                    <span class="joreg-field-label">Date :</span>
                                    <input type="date" id="joregDateFrom" name="date_from"
                                           class="joreg-input w-date" required>
                                </div>
                            </div>
                            <div class="joreg-top-group">
                                <span class="joreg-top-group-label">To</span>
                                <div class="joreg-top-inner">
                                    <span class="joreg-field-label">Date :</span>
                                    <input type="date" id="joregDateTo" name="date_to"
                                           class="joreg-input w-date" required>
                                </div>
                            </div>
                            <div class="joreg-top-group">
                                <span class="joreg-top-group-label">&nbsp;</span>
                                <div class="joreg-top-inner">
                                    <span class="joreg-field-label">Reference Folder :</span>
                                    <input type="text" id="joregRefFolder" name="ref_folder"
                                           class="joreg-input w-ref" placeholder="Type or upload PDF">
                                </div>
                            </div>
                        </div>

                        <div class="joreg-divider"></div>

                        <!-- ── PDF Upload ── -->
                        <div class="joreg-pdf-row">
                            <span class="joreg-pdf-label-text">Upload PDF (optional) :</span>
                            <button type="button" class="joreg-btn-upload">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="12" y1="18" x2="12" y2="12"/>
                                    <line x1="9" y1="15" x2="15" y2="15"/>
                                </svg>
                                Upload PDF
                                <input type="file" id="joregPdfFile" name="pdf_file"
                                       accept=".pdf" class="joreg-file-input">
                            </button>
                            <span class="joreg-pdf-or">— or type the folder name above</span>
                            <span id="joregPdfName" class="joreg-pdf-name"></span>
                            <span class="joreg-pdf-or" style="color:#2a5c30;font-style:normal;font-size:0.72rem;">
                                ℹ️ If a PDF with the same name already exists, it will be replaced.
                            </span>
                        </div>

                        <!-- ── Grid Headers ── -->
                        <div class="joreg-grid-header">
                            <span class="joreg-grid-col-label">Name :</span>
                            <span class="joreg-grid-col-label">Designation :</span>
                            <span class="joreg-grid-col-label">Funding Charges :</span>
                            <span class="joreg-grid-col-label">Rate Per Day :</span>
                            <span></span>
                        </div>

                        <!-- ── Entry Rows ── -->
                        <div id="joregRows" class="joreg-rows-container">
                            <div class="joreg-entry-row" data-row-id="1">

                                <!-- Name -->
                                <div class="joreg-field-wrap">
                                    <input type="text" class="joreg-input joreg-name-input"
                                           name="name[]" placeholder="Full name"
                                           autocomplete="off" required>
                                    <span class="joreg-field-error"></span>
                                </div>

                                <!-- Designation autocomplete -->
                                <div class="joreg-field-wrap joreg-ac-wrap">
                                    <input type="text" class="joreg-input joreg-ac-input"
                                           name="designation[]"
                                           placeholder="Designation"
                                           data-list="DESIGNATION_LIST"
                                           autocomplete="off" required>
                                    <span class="joreg-field-error"></span>
                                    <ul class="joreg-ac-dropdown"></ul>
                                </div>

                                <!-- Funding Charges autocomplete -->
                                <div class="joreg-field-wrap joreg-ac-wrap">
                                    <input type="text" class="joreg-input joreg-ac-input"
                                           name="funding_charges[]"
                                           placeholder="Funding charges"
                                           data-list="FUNDING_CHARGES_LIST"
                                           autocomplete="off" required>
                                    <span class="joreg-field-error"></span>
                                    <ul class="joreg-ac-dropdown"></ul>
                                </div>

                               <!-- Rate -->
                                <div class="joreg-field-wrap">
                                    <input type="text" class="joreg-input" name="rate[]"
                                        placeholder="0.00" required>
                                    <span class="joreg-field-error"></span>
                                </div>

                                <!-- Add button -->
                                <button type="button" class="joreg-btn-add-row"
                                        onclick="addEntryRow()" title="Add another row">+</button>
                            </div>
                        </div>

                        <!-- ── Footer ── -->
                        <div class="joreg-footer">
                            <button type="submit" class="joreg-btn-save">Save</button>
                        </div>

                    </form>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="js_files/jo_contract_reg.js"></script>
</body>
</html>