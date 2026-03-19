<?php
// delete_customer.php
// Accepts a POST request with MEMB_ID and deletes the member record

require_once __DIR__ . "/includes/session_manager.php";
require_once __DIR__ . "/db_connection.php";

start_secure_session();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$memb_id = isset($_POST['MEMB_ID']) && is_numeric($_POST['MEMB_ID'])
    ? (int) $_POST['MEMB_ID']
    : null;

if (!$memb_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing MEMB_ID.']);
    exit;
}

try {
    $pdo = create_database_connection();

    $stmt = $pdo->prepare("DELETE FROM member WHERE MEMB_ID = :MEMB_ID");
    $stmt->bindValue(':MEMB_ID', $memb_id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Customer not found.']);
    } else {
        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}