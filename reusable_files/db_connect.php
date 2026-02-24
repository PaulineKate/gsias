<?php
require_once __DIR__ . '/../load_env.php';

try {
    $conn = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=' . $_ENV['DB_CHAR'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<p style="font-family:sans-serif;color:#c0392b;padding:20px;">
            <strong>Database connection failed.</strong><br>
            ' . htmlspecialchars($e->getMessage()) . '
         </p>');
}