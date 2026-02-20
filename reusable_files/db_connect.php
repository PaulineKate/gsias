<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gsias_db');
define('DB_USER', 'root');
define('DB_PASS', 'pgso');
define('DB_CHAR', 'utf8mb4');

try {
    $conn = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHAR,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Shows a clean error
    die('<p style="font-family:sans-serif;color:#c0392b;padding:20px;">
            <strong>Database connection failed.</strong><br>
            ' . htmlspecialchars($e->getMessage()) . '
         </p>');
}