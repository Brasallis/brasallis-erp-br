<?php
// api/get_agents_list.php
header('Content-Type: application/json');
require_once '../includes/db_config.php';
require_once '../classes/AIAgent.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['empresa_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

try {
    $conn = connect_db();
    $aiAgent = new App\AIAgent($conn);
    $agents = $aiAgent->getAll($_SESSION['empresa_id']);
    
    // Return only necessary fields
    $result = array_map(function($a) {
        return [
            'id' => $a['id'],
            'name' => $a['name'],
            'role' => $a['role'],
            'status' => $a['status']
        ];
    }, $agents);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([]); // Fail silently in UI
}

function connect_db() { 
    return new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}
