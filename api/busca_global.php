<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

// Security: Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Acesso não autorizado.']);
    exit();
}

require_once '../includes/funcoes.php';
header('Content-Type: application/json');

$term = $_GET['q'] ?? '';
$empresa_id = $_SESSION['empresa_id'];
$results = [];

if (empty($term) || strlen($term) < 2) {
    echo json_encode([]);
    exit;
}

$conn = connect_db();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com o banco de dados.']);
    exit;
}

$like_term = '%' . $term . '%';

// Search Products
$stmt = $conn->prepare("SELECT id, name FROM produtos WHERE name LIKE ? AND empresa_id = ? LIMIT 5");
$stmt->execute([$like_term, $empresa_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($products) {
    $results['Produtos'] = [];
    foreach ($products as $p) {
        $results['Produtos'][] = [
            'name' => $p['name'],
            'url' => '/admin/produtos.php?search=' . urlencode($p['name'])
        ];
    }
}

// Search Suppliers
$stmt = $conn->prepare("SELECT id, name FROM fornecedores WHERE name LIKE ? AND empresa_id = ? LIMIT 5");
$stmt->execute([$like_term, $empresa_id]);
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($suppliers) {
    $results['Fornecedores'] = [];
    foreach ($suppliers as $s) {
        $results['Fornecedores'][] = [
            'name' => $s['name'],
            'url' => '/admin/fornecedores.php' 
        ];
    }
}

// Search Users (if admin)
if ($_SESSION['user_type'] === 'admin') {
    $stmt = $conn->prepare("SELECT id, username FROM usuarios WHERE username LIKE ? AND empresa_id = ? LIMIT 5");
    $stmt->execute([$like_term, $empresa_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($users) {
        $results['Usuários'] = [];
        foreach ($users as $u) {
            $results['Usuários'][] = [
                'name' => $u['username'],
                'url' => '/admin/usuarios.php'
            ];
        }
    }
}

// Search AI Agents
$stmt = $conn->prepare("SELECT id, name, role FROM ai_agents WHERE (name LIKE ? OR role LIKE ?) AND empresa_id = ? AND status = 'active' LIMIT 5");
$stmt->execute([$like_term, $like_term, $empresa_id]);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($agents) {
    $results['Agentes IA'] = [];
    foreach ($agents as $a) {
        $results['Agentes IA'][] = [
            'name' => "🤖 " . $a['name'] . ' (' . $a['role'] . ')',
            'url' => 'javascript:openAgentChat(' . $a['id'] . ')'
        ];
    }
}

echo json_encode($results);
?>
