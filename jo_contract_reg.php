<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

$alert_msg  = '';
$alert_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date_from  = trim($_POST['date_from']  ?? '');
    $date_to    = trim($_POST['date_to']    ?? '');
    $ref_folder = trim($_POST['ref_folder'] ?? '');

    $names             = $_POST['name']            ?? [];
    $designations      = $_POST['designation']     ?? [];
    $funding_charges   = $_POST['funding_charges'] ?? [];
    $rates             = $_POST['rate']            ?? [];

    $ref_file = 0;

    if (!empty($_FILES['pdf_file']['name'])) {
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

            $save_name   = $ref_folder . '.pdf';           
            $target_path = $upload_dir . $save_name;

            if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_path)) {
                $alert_msg  = 'Failed to upload PDF. Check folder permissions.';
                $alert_type = 'error';
            } else {
                $ref_file = 1;   /* mark as has PDF */
            }
        }
    }

    /* ── Insert rows if no upload error ── */
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
                    ':ref_file'        => $ref_file,   /* 1 = has PDF, 0 = no PDF */
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

                <!-- Page Title -->
                <h1 class="joreg-title">Add New J. O. Contract</h1>

                <!-- Alert -->
                <?php if ($alert_msg) : ?>
                <div class="joreg-alert <?= $alert_type ?>">
                    <?= htmlspecialchars($alert_msg) ?>
                </div>
                <?php endif; ?>
                <div id="joregAlert" class="joreg-alert"></div>

                <!-- Card -->
                <div class="joreg-card">
                    <div class="joreg-card-title">Job Order Contract Details</div>

                    <form id="joregForm"
                          method="POST"
                          action=""
                          enctype="multipart/form-data">

                        <!-- ── Top Row: FROM / TO dates + Ref Folder ── -->
                        <div class="joreg-top-row">

                            <!-- FROM group -->
                            <div class="joreg-top-group">
                                <span class="joreg-top-group-label">From</span>
                                <div class="joreg-top-inner">
                                    <span class="joreg-field-label">Date :</span>
                                    <input  type="date"
                                            id="joregDateFrom"
                                            name="date_from"
                                            class="joreg-input w-date"
                                            required>
                                </div>
                            </div>

                            <!-- TO group -->
                            <div class="joreg-top-group">
                                <span class="joreg-top-group-label">To</span>
                                <div class="joreg-top-inner">
                                    <span class="joreg-field-label">Date :</span>
                                    <input  type="date"
                                            id="joregDateTo"
                                            name="date_to"
                                            class="joreg-input w-date"
                                            required>
                                </div>
                            </div>

                            <!-- Reference Folder -->
                            <div class="joreg-top-group">
                                <span class="joreg-top-group-label">&nbsp;</span>
                                <div class="joreg-top-inner">
                                    <span class="joreg-field-label">Reference Folder :</span>
                                    <input  type="text"
                                            id="joregRefFolder"
                                            name="ref_folder"
                                            class="joreg-input w-ref"
                                            placeholder="Type or upload PDF">
                                </div>
                            </div>

                        </div>

                        <!-- Divider -->
                        <div class="joreg-divider"></div>

                        <!-- ── PDF Upload (optional) ── -->
                        <div class="joreg-pdf-row">
                            <span class="joreg-pdf-label-text">Upload PDF (optional) :</span>
                            <button type="button" class="joreg-btn-upload">
                                <!-- PDF icon SVG -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="12" y1="18" x2="12" y2="12"/>
                                    <line x1="9" y1="15" x2="15" y2="15"/>
                                </svg>
                                Upload PDF
                                <input  type="file"
                                        id="joregPdfFile"
                                        name="pdf_file"
                                        accept=".pdf"
                                        class="joreg-file-input">
                            </button>
                            <span class="joreg-pdf-or">— or type the folder name above</span>
                            <span id="joregPdfName" class="joreg-pdf-name"></span>
                        </div>

                        <!-- ── Grid Column Headers ── -->
                        <div class="joreg-grid-header">
                            <span class="joreg-grid-col-label">Name :</span>
                            <span class="joreg-grid-col-label">Designation :</span>
                            <span class="joreg-grid-col-label">Funding Charges :</span>
                            <span class="joreg-grid-col-label">Rate Per Day :</span>
                            <span></span><!-- spacer for button col -->
                        </div>

                        <!-- ── Entry Rows ── -->
                        <div id="joregRows" class="joreg-rows-container">

                            <!-- First row (always present) -->
                            <div class="joreg-entry-row" data-row-id="1">
                                <input type="text"
                                       class="joreg-input"
                                       name="name[]"
                                       placeholder="Full name"
                                       required>
                                <input type="text"
                                       class="joreg-input"
                                       name="designation[]"
                                       placeholder="Designation"
                                       required>
                                <input type="text"
                                       class="joreg-input"
                                       name="funding_charges[]"
                                       placeholder="Funding charges"
                                       required>
                                <input type="text"
                                       class="joreg-input"
                                       name="rate[]"
                                       placeholder="0.00"
                                       required>
                                <!-- Add button only on first row -->
                                <button type="button"
                                        class="joreg-btn-add-row"
                                        onclick="addEntryRow()"
                                        title="Add another row">+</button>
                            </div>

                        </div><!-- /joregRows -->

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