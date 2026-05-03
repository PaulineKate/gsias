<?php 
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

/* ── Database Query ── */
$log_records = [];

try {
    $sql = "SELECT `id`, `gmail_account`, `name`, `login_datetime`, `logout_datetime`, `user_level`
            FROM `logs`
            WHERE logout_datetime IS NOT NULL
            ORDER BY id DESC";

    $stmt        = $conn->query($sql);
    $log_records = $stmt->fetchAll();

} catch (PDOException $e) {
    // silent fail
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — Logs</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/logs.css">
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
            <div class="logs-wrapper">

                <h1 class="logs-title">Logs</h1>

                <div class="logs-toolbar">
                    <div class="logs-search-wrap">
                        <span class="search-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </span>
                        <input type="text" id="logsSearch" placeholder="Search..." autocomplete="off">
                    </div>
                </div>

                <div class="logs-table-container">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>Name</th>
                                <th>Gmail Account</th>
                                <th>Login Date &amp; Time</th>
                                <th>Logout Date &amp; Time</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <?php if (!empty($log_records)) : ?>
                                <?php foreach ($log_records as $row) : ?>
                                <tr class="logs-data-row"
                                    data-name="<?= htmlspecialchars(strtolower($row['name'])) ?>"
                                    data-gmail="<?= htmlspecialchars(strtolower($row['gmail_account'])) ?>"
                                    data-id="<?= htmlspecialchars(strtolower($row['id'])) ?>">
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['gmail_account']) ?></td>
                                    <td><?= htmlspecialchars($row['login_datetime']) ?></td>
                                    <td><?= htmlspecialchars($row['logout_datetime']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="no-results">
                                    <td colspan="5">No records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>
</div>

<script src="js_files/logs.js"></script>
</body>
</html>