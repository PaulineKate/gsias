<?php 
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

function findMysqldump() {
    $candidates = [
        'C:/xampp/mysql/bin/mysqldump.exe',
        'C:/wamp64/bin/mysql/mysql8.0/bin/mysqldump.exe',
        'C:/wamp64/bin/mysql/mysql8.1/bin/mysqldump.exe',
        'C:/wamp64/bin/mysql/mysql8.2/bin/mysqldump.exe',
        'C:/wamp64/bin/mysql/mysql8.4/bin/mysqldump.exe',
        'C:/wamp/bin/mysql/mysql8.0/bin/mysqldump.exe',
        'C:/wamp/bin/mysql/mysql5.7/bin/mysqldump.exe',
        'mysqldump',
    ];
    foreach ($candidates as $path) {
        if ($path === 'mysqldump' || file_exists($path)) return $path;
    }
    return 'mysqldump';
}



function getDbCredentials() {
    $creds = ['host' => 'localhost', 'dbname' => '', 'user' => 'root', 'pass' => ''];
    $config_file = __DIR__ . '/reusable_files/db_connect.php';
    if (!file_exists($config_file)) return $creds;

    $src = file_get_contents($config_file);

    if (preg_match("/define\s*\(\s*'DB_HOST'\s*,\s*'([^']+)'/", $src, $m)) $creds['host']   = $m[1];
    if (preg_match("/define\s*\(\s*'DB_NAME'\s*,\s*'([^']+)'/", $src, $m)) $creds['dbname'] = $m[1];
    if (preg_match("/define\s*\(\s*'DB_USER'\s*,\s*'([^']+)'/", $src, $m)) $creds['user']   = $m[1];
    if (preg_match("/define\s*\(\s*'DB_PASS'\s*,\s*'([^']*)'/", $src, $m)) $creds['pass']   = $m[1];

    if (!$creds['dbname']) {
        if (preg_match('/define\s*\(\s*"DB_HOST"\s*,\s*"([^"]+)"/', $src, $m)) $creds['host']   = $m[1];
        if (preg_match('/define\s*\(\s*"DB_NAME"\s*,\s*"([^"]+)"/', $src, $m)) $creds['dbname'] = $m[1];
        if (preg_match('/define\s*\(\s*"DB_USER"\s*,\s*"([^"]+)"/', $src, $m)) $creds['user']   = $m[1];
        if (preg_match('/define\s*\(\s*"DB_PASS"\s*,\s*"([^"]*)"/', $src, $m)) $creds['pass']   = $m[1];
    }

    return $creds;
}

function tryEnableZip() {
    if (extension_loaded('zip')) return true;
    if (function_exists('dl')) { @dl('php_zip.dll'); @dl('zip.so'); }
    return extension_loaded('zip');
}

function writePHPZip($zip_path, array $files) {
    $local_entries = '';
    $central_dir   = '';
    $offset        = 0;
    $count         = 0;

    foreach ($files as $f) {
        $name    = str_replace('\\', '/', $f['name']);
        $data    = $f['data'];
        $namelen = strlen($name);
        $datalen = strlen($data);
        $crc     = crc32($data);

        $local_header =
            "\x50\x4b\x03\x04"
            . pack('vvvvvVVVvv', 20, 0, 0, 0, 0, $crc, $datalen, $datalen, $namelen, 0)
            . $name
            . $data;

        $central_entry =
            "\x50\x4b\x01\x02"
            . pack('vvvvvvVVVvvvvvVV', 20, 20, 0, 0, 0, 0, $crc, $datalen, $datalen, $namelen, 0, 0, 0, 0, 0, $offset)
            . $name;

        $offset += strlen($local_header);
        $local_entries .= $local_header;
        $central_dir   .= $central_entry;
        $count++;
    }

    $central_size   = strlen($central_dir);
    $central_offset = $offset;
    $eocd =
        "\x50\x4b\x05\x06"
        . pack('vvvvVVv', 0, 0, $count, $count, $central_size, $central_offset, 0);

    return file_put_contents($zip_path, $local_entries . $central_dir . $eocd) !== false;
}

function runBackup($conn, $dest_dir) {
    $dest_dir = rtrim($dest_dir, '/\\');

    if (!is_dir($dest_dir)) {
        if (!@mkdir($dest_dir, 0755, true)) {
            return ['success' => false, 'message' => 'Directory does not exist and could not be created: ' . $dest_dir];
        }
    }

    $creds = getDbCredentials();
    if (!$creds['dbname']) {
        return ['success' => false, 'message' => 'Could not read database name from db_connect.php.'];
    }

    $timestamp = date('Y-m-d_H-i-s');
    $zip_name  = 'GSIAS_Backup_' . $timestamp . '.zip';
    $zip_path  = $dest_dir . DIRECTORY_SEPARATOR . $zip_name;
    $tmp_sql   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gsias_db_' . $timestamp . '.sql';
    $mysqldump  = findMysqldump();
    $pass_arg   = ($creds['pass'] !== '') ? '-p' . $creds['pass'] : '';
    $cmd = sprintf(
        '"%s" -h %s -u %s %s %s > "%s" 2>&1',
        str_replace('/', '\\', $mysqldump),
        $creds['host'],
        $creds['user'],
        $pass_arg,
        $creds['dbname'],
        str_replace('/', '\\', $tmp_sql)
    );
    exec($cmd, $dump_output, $dump_code);
    $sql_ok  = file_exists($tmp_sql) && filesize($tmp_sql) > 100;
    $sql_str = $sql_ok ? file_get_contents($tmp_sql)
                       : "mysqldump failed (exit $dump_code).\n" . implode("\n", $dump_output);
    if (file_exists($tmp_sql)) @unlink($tmp_sql);
    $jo_dir    = rtrim(__DIR__ . '/JO_Contract_files', '/\\') . DIRECTORY_SEPARATOR;
    $pdf_count = 0;
    $zip_files = [];
    $zip_files[] = ['name' => 'database/gsias_db_' . $timestamp . '.sql', 'data' => $sql_str];

    if (is_dir($jo_dir)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($jo_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iter as $file) {
            $real = $file->getRealPath();
            $relative = 'JO_Contract_files/' . ltrim(
                str_replace('\\', '/', substr($real, strlen($jo_dir))),
                '/'
            );

            $zip_files[] = ['name' => $relative, 'data' => file_get_contents($real)];
            $pdf_count++;
        }
    }
    $zip_ok = false;
    $method = '';

    if (tryEnableZip()) {
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($zip_files as $f) {
                $zip->addFromString($f['name'], $f['data']);
            }
            $zip->close();
            $zip_ok = file_exists($zip_path);
            $method = 'ZipArchive';
        }
    }

    if (!$zip_ok) {
        $zip_ok = writePHPZip($zip_path, $zip_files);
        $method = 'built-in';
    }

    if (!$zip_ok || !file_exists($zip_path)) {
        return ['success' => false, 'message' => 'Could not create ZIP file at: ' . $zip_path . '. Check folder write permissions.'];
    }

    return [
        'success'    => true,
        'zip_name'   => $zip_name,
        'zip_path'   => $zip_path,
        'size_kb'    => round(filesize($zip_path) / 1024, 1),
        'sql_ok'     => $sql_ok,
        'pdf_count'  => $pdf_count,
        'method'     => $method,
        'dump_error' => $sql_ok ? null : implode(' | ', $dump_output),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'add_designation') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { echo json_encode(['success' => false, 'message' => 'Name cannot be empty.']); exit; }
        try {
            $stmt = $conn->prepare("INSERT INTO `designation_list` (`d_name`) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            echo json_encode(['success' => true, 'id' => (int)$conn->lastInsertId(), 'name' => $name]);
        } catch (PDOException $e) { echo json_encode(['success' => false, 'message' => 'Database error.']); }
        exit;
    }

    if ($action === 'delete_designation') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $conn->prepare("DELETE FROM `designation_list` WHERE `d_id` = :id")->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) { echo json_encode(['success' => false, 'message' => 'Database error.']); }
        exit;
    }

    if ($action === 'add_funding') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { echo json_encode(['success' => false, 'message' => 'Name cannot be empty.']); exit; }
        try {
            $stmt = $conn->prepare("INSERT INTO `funding_charges_list` (`fc_name`) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            echo json_encode(['success' => true, 'id' => (int)$conn->lastInsertId(), 'name' => $name]);
        } catch (PDOException $e) { echo json_encode(['success' => false, 'message' => 'Database error.']); }
        exit;
    }

    if ($action === 'delete_funding') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $conn->prepare("DELETE FROM `funding_charges_list` WHERE `fc_id` = :id")->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) { echo json_encode(['success' => false, 'message' => 'Database error.']); }
        exit;
    }

    if ($action === 'delete_ref_folder') {
        $ref_folder = trim($_POST['ref_folder'] ?? '');
        if ($ref_folder === '') { echo json_encode(['success' => false, 'message' => 'Invalid folder.']); exit; }
        $safe_name = basename($ref_folder);
        $pdf_path  = __DIR__ . '/JO_Contract_files/' . strtoupper($safe_name) . '.pdf';
        try {
            $conn->prepare("DELETE FROM `jo_contracts` WHERE `ref_folder` = :ref_folder")->execute([':ref_folder' => $ref_folder]);
            $file_deleted = file_exists($pdf_path) ? unlink($pdf_path) : false;
            echo json_encode(['success' => true, 'file_deleted' => $file_deleted]);
        } catch (PDOException $e) { echo json_encode(['success' => false, 'message' => 'Database error.']); }
        exit;
    }

    if ($action === 'manual_backup') {
        $dest = trim($_POST['dest_dir'] ?? '');
        if ($dest === '') { echo json_encode(['success' => false, 'message' => 'No destination directory provided.']); exit; }
        echo json_encode(runBackup($conn, $dest));
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']); exit;
}

$designations    = $conn->query("SELECT `d_id`, `d_name` FROM `designation_list` ORDER BY `d_id` ASC")->fetchAll();
$funding_charges = $conn->query("SELECT `fc_id`, `fc_name` FROM `funding_charges_list` ORDER BY `fc_id` ASC")->fetchAll();
$ref_folders     = $conn->query("SELECT `jo_id`, `ref_folder` FROM `jo_contracts` ORDER BY `jo_id` ASC LIMIT 1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — General Settings</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/general_settings.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --green-dark: #1a3d1f; --green-mid: #2a5c30;
            --green-light: #e8f5e9; --green-content: #d4edda;
            --sidebar-width: 220px; --header-height: 56px;
        }
        html, body { height: 100%; font-family: 'Source Sans 3', sans-serif; background: var(--green-light); color: #1a2e1c; }
        .app-shell   { display: flex; height: 100vh; overflow: hidden; }
        .app-sidebar { width: var(--sidebar-width); flex-shrink: 0; height: 100vh; overflow-y: auto; overflow-x: hidden; }
        .app-right   { flex: 1; display: flex; flex-direction: column; min-width: 0; overflow: hidden; }
        .app-header  { flex-shrink: 0; }
        .main-content { flex: 1; overflow: hidden; display: flex; flex-direction: column; }
        @media (max-width: 768px) { :root { --sidebar-width: 64px; } }
    </style>
</head>
<body>

<div class="app-shell">
    <aside class="app-sidebar"><?php include 'reusable_files/sidebar.php'; ?></aside>

    <div class="app-right">
        <div class="app-header"><?php include 'reusable_files/header.php'; ?></div>

        <main class="main-content">
            <div class="gs-wrapper">

                <div class="gs-page-title">General Settings</div>

                <div class="gs-card">
                    <div class="gs-card-title">Table Details</div>

                    <div class="gs-panels">

                        <!-- DESIGNATION -->
                        <div class="gs-panel">
                            <div class="gs-panel-title">Designation List</div>
                            <div class="gs-table-wrapper">
                                <table class="gs-table">
                                    <thead><tr><th>No.</th><th>Designation</th><th></th></tr></thead>
                                    <tbody id="designation-body">
                                        <?php if (!empty($designations)): $i = 1; foreach ($designations as $row): ?>
                                        <tr data-id="<?= $row['d_id'] ?>">
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['d_name']) ?></td>
                                            <td><button class="gs-btn-delete" onclick="deleteRecord('designation', <?= $row['d_id'] ?>, this)" title="Delete"><img src="assets/icons/delete_icon.png" alt="Delete"></button></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr class="gs-empty-row" id="designation-empty"><td colspan="3">No designations yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div id="designation-alert" class="gs-alert"></div>
                            <div class="gs-add-section">
                                <label class="gs-add-label" for="designation-input">Add New Designation</label>
                                <input class="gs-input" id="designation-input" type="text" placeholder="Enter designation name…" maxlength="100" style="text-transform:uppercase;" autocomplete="off">
                                <span class="gs-input-error" id="designation-input-error"></span>
                                <button class="gs-btn-save" onclick="saveRecord('designation')">Save Changes</button>
                            </div>
                        </div>

                        <!-- FUNDING CHARGES -->
                        <div class="gs-panel">
                            <div class="gs-panel-title">Funding Charges List</div>
                            <div class="gs-table-wrapper">
                                <table class="gs-table">
                                    <thead><tr><th>No.</th><th>Funding Charge</th><th></th></tr></thead>
                                    <tbody id="funding-body">
                                        <?php if (!empty($funding_charges)): $i = 1; foreach ($funding_charges as $row): ?>
                                        <tr data-id="<?= $row['fc_id'] ?>">
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['fc_name']) ?></td>
                                            <td><button class="gs-btn-delete" onclick="deleteRecord('funding', <?= $row['fc_id'] ?>, this)" title="Delete"><img src="assets/icons/delete_icon.png" alt="Delete"></button></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr class="gs-empty-row" id="funding-empty"><td colspan="3">No funding charges yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div id="funding-alert" class="gs-alert"></div>
                            <div class="gs-add-section">
                                <label class="gs-add-label" for="funding-input">Add New Funding Charge</label>
                                <input class="gs-input" id="funding-input" type="text" placeholder="Enter funding charge name…" maxlength="100" style="text-transform:uppercase;" autocomplete="off">
                                <span class="gs-input-error" id="funding-input-error"></span>
                                <button class="gs-btn-save" onclick="saveRecord('funding')">Save Changes</button>
                            </div>
                        </div>

                    </div>

                    <div class="gs-bottom-row">

                        <!-- REF FOLDERS -->
                        <div class="gs-panel gs-panel--danger-zone gs-panel--bottom">
                            <div class="gs-panel-header-row">
                                <div>
                                    <div class="gs-panel-title">Job Order Contract Ref. Folders List Removal</div>
                                    <div class="gs-panel-subtitle">Removing a folder will permanently delete all registered personnel linked to it.</div>
                                </div>
                                <div class="gs-search-wrapper">
                                    <svg class="gs-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                    <input class="gs-search-input" id="ref-folder-search" type="text" placeholder="Search folders…" autocomplete="off">
                                </div>
                            </div>
                            <div class="gs-table-wrapper gs-table-wrapper--ref">
                                <table class="gs-table">
                                    <thead><tr><th>No.</th><th>Ref. Folder</th><th></th></tr></thead>
                                    <tbody id="ref-folder-body">
                                        <?php if (!empty($ref_folders)): $i = 1; foreach ($ref_folders as $row): ?>
                                        <tr data-ref-folder="<?= htmlspecialchars($row['ref_folder'], ENT_QUOTES) ?>">
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['ref_folder']) ?></td>
                                            <td><button class="gs-btn-delete gs-btn-delete--danger" onclick="deleteRefFolder(<?= htmlspecialchars(json_encode($row['ref_folder'])) ?>, this)" title="Delete Folder"><img src="assets/icons/delete_icon.png" alt="Delete"></button></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr class="gs-empty-row" id="ref-folder-empty"><td colspan="3">No ref. folders found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div id="ref-folder-alert" class="gs-alert"></div>
                        </div>

                        <!-- MANUAL BACKUP -->
                        <div class="gs-backup-panel gs-backup-panel--manual gs-panel--bottom">
                            <div class="gs-backup-panel-header">
                                <div>
                                    <div class="gs-backup-panel-title">Manual Backup</div>
                                    <div class="gs-backup-panel-sub">Create a full backup instantly. Choose where to save the .zip.</div>
                                </div>
                                <div class="gs-backup-icon-wrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2a5c30" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </div>
                            </div>

                            <div class="gs-backup-includes">
                                <span class="gs-includes-label">Includes:</span>
                                <span class="gs-includes-tag">🗄️ Database (.sql)</span>
                                <span class="gs-includes-tag">📄 PDFs</span>
                                <span class="gs-includes-tag">📦 One .zip file</span>
                            </div>

                            <div class="gs-backup-dir-row">
                                <label class="gs-backup-dir-label" for="manualBackupDir">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                    Save backup to directory
                                </label>
                                <input class="gs-input" id="manualBackupDir" type="text"
                                       placeholder="e.g. C:/Users/YourName/Desktop">
                            </div>

                            <div id="manual-backup-alert" class="gs-alert"></div>

                            <div class="gs-manual-backup-footer">
                                <button class="gs-btn-manual-backup" id="manualBackupBtn" onclick="runManualBackup()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Create Backup Now
                                </button>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </main>
    </div>
</div>

<script src="js_files/general_settings.js"></script>
</body>
</html>