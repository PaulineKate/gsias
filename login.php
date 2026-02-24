<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css_files/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <title>GSIAS - Log In</title>
</head>
<body>

    <div class="bg-overlay"></div>

    <div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
        <div class="login-card">
            <div class="row g-0 align-items-center">

                <div class="col-md-5">
                    <div class="left-panel text-center">
                        <div class="logo-wrapper mx-auto mb-3">
                            <img src="assets/pgso_logo.png" alt="PGSO Logo" class="logo-img">
                        </div>
                        <h2 class="system-title">GENERAL SERVICES INFORMATION AND ARCHIVING SYSTEM (GSIAS)</h2>
                        <p class="office-name">GENERAL SERVICE OFFICE</p>
                        <p class="province-name">PROVINCE OF CAMARINES NORTE</p>
                    </div>
                </div>
                <div class="col-auto d-none d-md-flex">
                    <div class="vertical-divider"></div>
                </div>

                <div class="col-md-6">
                    <div class="right-panel">
                        <h1 class="login-heading">LOG IN</h1>

                        <?php
                        if (isset($_GET['error']) && $_GET['error'] === '1') {
                            echo '<div class="alert alert-danger py-2">Invalid username or password.</div>';
                        }
                        ?>

                        <form action="auth.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label field-label">Username</label>
                                <input type="text" class="form-control login-input" id="username" name="username" required autocomplete="username">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label field-label">Password</label>
                                <input type="password" class="form-control login-input" id="password" name="password" required autocomplete="current-password">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn login-btn">LOG IN</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>