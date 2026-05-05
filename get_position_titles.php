<?php
include 'reusable_files/session.php';
include 'reusable_files/db_connect.php';

header('Content-Type: application/json');

try {
    $rows = $conn->query(
        "SELECT `id`, `p_name` FROM `position_titles` ORDER BY `p_name` ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}