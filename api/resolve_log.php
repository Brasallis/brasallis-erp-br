<?php
declare(strict_types=1);

require_once '../vendor/autoload.php';
require_once '../includes/funcoes.php';

use App\Core\Database;
use App\Repository\LogRepository;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? 'resolve';

if (!$id && $action !== 'resolve_all') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID missing']);
    exit;
}

try {
    $repo = new LogRepository(Database::getInstance());
    
    if ($action === 'resolve_all') {
        $repo->resolveAll();
        echo json_encode(['success' => true, 'message' => 'All logs resolved']);
    } else {
        $repo->resolve((int)$id, (int)$_SESSION['user_id']);
        echo json_encode(['success' => true, 'message' => 'Log marked as resolved']);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
