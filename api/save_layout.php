<?php
// api/save_layout.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/funcoes.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['row1']) && !isset($data['row2'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data format. Expected row1 or row2 keys.']);
    exit;
}

$userId = $_SESSION['user_id'];
$layoutJson = json_encode($data);

try {
    $conn = connect_db();
    // Upsert (Insert or Update)
    $stmt = $conn->prepare("
        INSERT INTO dashboard_layouts (user_id, layout_json) 
        VALUES (:user_id, :layout_json)
        ON DUPLICATE KEY UPDATE layout_json = :layout_json_update
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':layout_json' => $layoutJson,
        ':layout_json_update' => $layoutJson
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Erro ao salvar layout: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
