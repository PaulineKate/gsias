<?php 
include 'reusable_files/session.php';

// ── DB connection ─────────────────────────────────────────────────────────────
include 'reusable_files/db_connect.php'; // provides $conn (PDO)

// ── Handle AJAX via POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    // INSERT designation
    if ($action === 'add_designation') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { echo json_encode(['success' => false, 'message' => 'Name cannot be empty.']); exit; }
        try {
            $stmt = $conn->prepare("INSERT INTO `designation_list` (`d_name`) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            echo json_encode(['success' => true, 'id' => (int)$conn->lastInsertId(), 'name' => $name]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        exit;
    }

    // DELETE designation
    if ($action === 'delete_designation') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("DELETE FROM `designation_list` WHERE `d_id` = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        exit;
    }

    // INSERT funding charge
    if ($action === 'add_funding') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { echo json_encode(['success' => false, 'message' => 'Name cannot be empty.']); exit; }
        try {
            $stmt = $conn->prepare("INSERT INTO `funding_charges_list` (`fc_name`) VALUES (:name)");
            $stmt->execute([':name' => $name]);
            echo json_encode(['success' => true, 'id' => (int)$conn->lastInsertId(), 'name' => $name]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        exit;
    }

    // DELETE funding charge
    if ($action === 'delete_funding') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("DELETE FROM `funding_charges_list` WHERE `fc_id` = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']); exit;
}

// ── SELECT both lists for initial page render ─────────────────────────────────
$designations    = $conn->query("SELECT `d_id`, `d_name` FROM `designation_list` ORDER BY `d_id` ASC")->fetchAll();
$funding_charges = $conn->query("SELECT `fc_id`, `fc_name` FROM `funding_charges_list` ORDER BY `fc_id` ASC")->fetchAll();
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
        /* ↓ Key: main-content fills leftover height */
        .main-content { flex: 1; overflow: hidden; display: flex; flex-direction: column; }
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

            <div class="gs-wrapper">

                <div class="gs-page-title">General Settings</div>

                <div class="gs-card">
                    <div class="gs-card-title">Table Details</div>

                    <div class="gs-panels">

                        <!-- ══ DESIGNATION PANEL ══ -->
                        <div class="gs-panel">
                            <div class="gs-panel-title">Designation List</div>

                            <div class="gs-table-wrapper">
                                <table class="gs-table">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Designation</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="designation-body">
                                        <?php if (!empty($designations)): $i = 1;
                                              foreach ($designations as $row): ?>
                                        <tr data-id="<?= $row['d_id'] ?>">
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['d_name']) ?></td>
                                            <td>
                                                <button class="gs-btn-delete" onclick="deleteRecord('designation', <?= $row['d_id'] ?>, this)" title="Delete">
                                                    <img src="assets/icons/delete_icon.png" alt="Delete">
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr class="gs-empty-row" id="designation-empty">
                                            <td colspan="3">No designations yet.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div id="designation-alert" class="gs-alert"></div>

                            <div class="gs-add-section">
                                <label class="gs-add-label" for="designation-input">Add New Designation</label>
                                <input class="gs-input" id="designation-input" type="text"
                                       placeholder="Enter designation name…" maxlength="100" style="text-transform:uppercase;" autocomplete="off">
                                <span class="gs-input-error" id="designation-input-error"></span>
                                <button class="gs-btn-save" onclick="saveRecord('designation')">Save Changes</button>
                            </div>
                        </div>

                        <!-- ══ FUNDING CHARGES PANEL ══ -->
                        <div class="gs-panel">
                            <div class="gs-panel-title">Funding Charges List</div>

                            <div class="gs-table-wrapper">
                                <table class="gs-table">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Designation</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="funding-body">
                                        <?php if (!empty($funding_charges)): $i = 1;
                                              foreach ($funding_charges as $row): ?>
                                        <tr data-id="<?= $row['fc_id'] ?>">
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['fc_name']) ?></td>
                                            <td>
                                                <button class="gs-btn-delete" onclick="deleteRecord('funding', <?= $row['fc_id'] ?>, this)" title="Delete">
                                                    <img src="assets/icons/delete_icon.png" alt="Delete">
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr class="gs-empty-row" id="funding-empty">
                                            <td colspan="3">No funding charges yet.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div id="funding-alert" class="gs-alert"></div>

                            <div class="gs-add-section">
                                <label class="gs-add-label" for="funding-input">Add New Funding Charge</label>
                                <input class="gs-input" id="funding-input" type="text"
                                       placeholder="Enter funding charge name…" maxlength="100" style="text-transform:uppercase;" autocomplete="off">
                                <span class="gs-input-error" id="funding-input-error"></span>
                                <button class="gs-btn-save" onclick="saveRecord('funding')">Save Changes</button>
                            </div>
                        </div>

                    </div><!-- /.gs-panels -->
                </div><!-- /.gs-card -->

            </div><!-- /.gs-wrapper -->

        </main>

    </div>

</div>

<script src="js_files/general_settings.js"></script>
</body>
</html>