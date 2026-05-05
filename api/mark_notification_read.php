<?php
// api/mark_notification_read.php
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
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
$empresa_id = $_SESSION['empresa_id'];
$notification_id = $_POST['id'] ?? null;
$mark_all = isset($_POST['all']) && $_POST['all'] == 'true';

try {
    if ($mark_all) {
        $stmt = $conn->prepare("UPDATE notificacoes SET is_read = 1 WHERE empresa_id = ?");
        $stmt->execute([$empresa_id]);
    } elseif ($notification_id) {
        $stmt = $conn->prepare("UPDATE notificacoes SET is_read = 1 WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$notification_id, $empresa_id]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
