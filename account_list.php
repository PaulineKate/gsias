<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">
    <link rel="stylesheet" href="css_files/account_list.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <title>GSIAS - Account List</title>
</head>
<body>

<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

// The logged-in admin's username (set by session.php)
$logged_in_user = $_SESSION['admin_user'] ?? '';

$delete_error   = '';
$delete_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $delete_user      = trim($_POST['delete_user']);
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch the logged-in admin's hashed password
    $stmt = $conn->prepare("SELECT admin_pass FROM admin_creds WHERE admin_user = ?");
    $stmt->execute([$logged_in_user]);
    $row       = $stmt->fetch(PDO::FETCH_ASSOC);
    $hashed_pw = $row['admin_pass'] ?? '';

    if (!$hashed_pw || !password_verify($confirm_password, $hashed_pw)) {
        $delete_error = 'Incorrect password. Account was not deleted.';

    } elseif ($delete_user === $logged_in_user) {
        $delete_error = 'You cannot delete your own account.';

    } else {
        $del = $conn->prepare("DELETE FROM admin_creds WHERE admin_user = ?");
        if ($del->execute([$delete_user])) {
            $delete_success = 'Account deleted successfully.';
        } else {
            $delete_error = 'Failed to delete account. Please try again.';
        }
    }
}

// Fetch account list
$stmt     = $conn->query(
    "SELECT admin_name, gmail_account, admin_user, user_level FROM admin_creds ORDER BY admin_name ASC"
);
$accounts = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<div class="app-shell">

    <aside class="app-sidebar">
        <?php include 'reusable_files/sidebar.php'; ?>
    </aside>

    <div class="app-right">

        <div class="app-header">
            <?php include 'reusable_files/header.php'; ?>
        </div>

        <main class="acct-wrapper">

            <h1 class="acct-title">Account List</h1>

            <?php if ($delete_success): ?>
                <div class="alert alert-success py-2 acct-alert"><?= htmlspecialchars($delete_success) ?></div>
            <?php endif; ?>

            <!-- Toolbar -->
            <div class="acct-toolbar">
                <div class="acct-search-wrap">
                    <span class="search-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </span>
                    <input type="text" id="acctSearch" placeholder="Search name, email, or level…" autocomplete="off">
                </div>

                <a href="account_creation.php" class="btn acct-add-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add Employee Account
                </a>
            </div>

            <!-- Table -->
            <div class="acct-table-container">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Gmail Account</th>
                            <th>User Level</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="acctTableBody">
                        <?php if (empty($accounts)): ?>
                            <tr class="no-results">
                                <td colspan="5">No accounts found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $i => $acct): ?>
                                <tr class="acct-data-row"
                                    data-name="<?= htmlspecialchars(strtolower($acct['admin_name'])) ?>"
                                    data-gmail="<?= htmlspecialchars(strtolower($acct['gmail_account'])) ?>"
                                    data-level="<?= htmlspecialchars(strtolower($acct['user_level'])) ?>">
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($acct['admin_name']) ?></td>
                                    <td><?= htmlspecialchars($acct['gmail_account']) ?></td>
                                    <td>
                                        <span class="level-badge level-<?= strtolower(str_replace(' ', '-', htmlspecialchars($acct['user_level']))) ?>">
                                            <?= htmlspecialchars($acct['user_level']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($acct['admin_user'] !== $logged_in_user): ?>
                                            <button class="btn acct-delete-btn"
                                                data-user="<?= htmlspecialchars($acct['admin_user']) ?>"
                                                data-name="<?= htmlspecialchars($acct['admin_name']) ?>"
                                                onclick="openDeleteModal(this)">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"/>
                                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                                </svg>
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <span class="acct-own-label">You</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</div>

<!-- ── Delete Confirmation Modal ───────────────────────────────────────── -->
<div class="modal-backdrop-custom" id="deleteModalBackdrop"></div>

<div class="modal-custom" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
    <div class="modal-custom-content">

        <div class="modal-custom-header">
            <div class="modal-icon-wrap danger">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/>
                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                </svg>
            </div>
            <h2 class="modal-custom-title" id="deleteModalTitle">Delete Account</h2>
            <button class="modal-close-btn" onclick="closeDeleteModal()" aria-label="Close">&times;</button>
        </div>

        <div class="modal-custom-body">
            <p class="modal-desc">
                You are about to delete the account of <strong id="deleteTargetName"></strong>.
                This action <strong>cannot be undone</strong>.
            </p>
            <p class="modal-desc mb-3">Please enter your password to confirm.</p>

            <?php if ($delete_error): ?>
                <div class="alert alert-danger py-2 mb-3" id="modalErrorStatic">
                    <?= htmlspecialchars($delete_error) ?>
                </div>
            <?php endif; ?>

            <div id="modalErrorLive" class="alert alert-danger py-2 mb-3" style="display:none;"></div>

            <form method="POST" action="account_list.php" id="deleteForm">
                <!-- Using admin_user as the identifier instead of admin_id -->
                <input type="hidden" name="delete_user" id="deleteUserInput">
                <div class="modal-field">
                    <label for="confirm_password" class="modal-label">Your Password</label>
                    <div class="pass-wrap">
                        <input type="password" class="modal-input" id="confirm_password"
                               name="confirm_password" required autocomplete="current-password"
                               placeholder="Enter your password">
                        <button type="button" class="pass-toggle" id="toggleModalPass" aria-label="Toggle password visibility">
                            <img src="assets/icons/password_invisible_icon.png" id="eyeModalIcon" alt="Show password">
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="modal-custom-footer">
            <button type="button" class="btn modal-cancel-btn" onclick="closeDeleteModal()">Cancel</button>
            <button type="submit" form="deleteForm" class="btn modal-confirm-btn">
                Confirm Delete
            </button>
        </div>

    </div>
</div>

<script src="js_files/account_list.js"></script>
</body>
</html>