<?php
// api/get_notifications.php
header('Content-Type: application/json');
require_once __DIR__ . '/../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['empresa_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $conn = resolve('db');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database resolution failed: ' . $e->getMessage()]);
    exit;
}
$empresa_id = $_SESSION['empresa_id'] ?? null;

if (!$empresa_id) {
    echo json_encode(['success' => false, 'error' => 'Session expired or invalid']);
    exit;
}

try {
    // Buscar últimas 15 notificações não lidas primeiro, depois lidas
    $stmt = $conn->prepare("
        SELECT id, type, message, is_read, created_at 
        FROM notificacoes 
        WHERE empresa_id = ? 
        ORDER BY is_read ASC, created_at DESC 
        LIMIT 15
    ");
    $stmt->execute([$empresa_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar datas
    foreach ($notifications as &$n) {
        $n['time_ago'] = date('d/m H:i', strtotime($n['created_at']));
    }

    $unread_count = 0;
    foreach ($notifications as $n) {
        if (!$n['is_read']) $unread_count++;
    }

    echo json_encode([
        'success' => true, 
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
