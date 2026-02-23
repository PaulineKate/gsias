<?php 
include 'reusable_files/session.php'; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSIAS — Dashboard</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Barlow:wght@500;700;800&display=swap" rel="stylesheet">

    <!-- Component Styles -->
    <link rel="stylesheet" href="css_files/sidebar.css">
    <link rel="stylesheet" href="css_files/header.css">
    <link rel="stylesheet" href="css_files/main_content.css">

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --green-dark:    #1a3d1f;
            --green-mid:     #2a5c30;
            --green-light:   #e8f5e9;
            --green-content: #d4edda;
            --sidebar-width: 220px;
            --header-height: 56px;
        }

        html, body {
            height: 100%;
            font-family: 'Source Sans 3', sans-serif;
            background: var(--green-light);
            color: #1a2e1c;
        }

        /* App Shell */
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Column */
        .app-sidebar {
            width: var(--sidebar-width);
            flex-shrink: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Right Column (header + content) */
        .app-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
        }

        /* Header row */
        .app-header {
            flex-shrink: 0;
        }

        /* Responsive collapse sidebar */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 64px;
            }
        }
    </style>
</head>
<body>

<div class="app-shell">

    <!-- SIDEBAR -->
    <aside class="app-sidebar">
        <?php include 'reusable_files/sidebar.php'; ?>
    </aside>

    <!-- RIGHT COLUMN (Header + Body) -->
    <div class="app-right">

        <!-- HEADER -->
        <div class="app-header">
            <?php include 'reusable_files/header.php'; ?>
        </div>

        <!-- MAIN CONTENT -->
        <main class="main-content">

            <!-- CONTENT GOES HERE -->

            <img src="assets/pgso_logo.png" alt="pgso logo" style="height: 100%; width: 80%; background-size: cover; margin:0 10% ;">

            <!-- END OF CONTENT -->

        </main>

    </div>

</div>

</body>
</html>