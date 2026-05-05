<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Acesso não autorizado.']);
    exit;
}

require_once '../includes/funcoes.php';

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$query = $_GET['term'] ?? '';

if (strlen(trim($query)) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$term = '%' . trim($query) . '%';
$exact_term = trim($query);
$results = [];

try {
    // 1. PRODUTOS & SKU
    $stmt = $conn->prepare("SELECT id, name as title, sku as subtitle, 'produto' as type, '/admin/produtos.php?search=' as url FROM produtos WHERE empresa_id = ? AND (name LIKE ? OR sku = ?) LIMIT 5");
    $stmt->execute([$empresa_id, $term, $exact_term]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 2. CLIENTES
    $stmt = $conn->prepare("SELECT id, nome as title, cpf_cnpj as subtitle, 'cliente' as type, '/modules/crm/views/clientes.php?search=' as url FROM clientes WHERE empresa_id = ? AND (nome LIKE ? OR cpf_cnpj = ?) LIMIT 5");
    $stmt->execute([$empresa_id, $term, $exact_term]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 3. VENDAS (ID ou Vendedor?)
    $stmt = $conn->prepare("SELECT id, CONCAT('Venda #', id) as title, created_at as subtitle, 'venda' as type, '/admin/vendas.php?search=' as url FROM vendas WHERE empresa_id = ? AND (id = ?) LIMIT 3");
    $stmt->execute([$empresa_id, $exact_term]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 4. COMPRAS / NF (ID ou Fornecedor?)
    $stmt = $conn->prepare("SELECT id, CONCAT('Nota/Compra #', id) as title, purchase_date as subtitle, 'nota' as type, '/admin/detalhes_compra.php?id=' as url FROM compras WHERE empresa_id = ? AND (id = ?) LIMIT 3");
    $stmt->execute([$empresa_id, $exact_term]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 5. USUÁRIOS / FUNCIONÁRIOS
    $stmt = $conn->prepare("SELECT id, username as title, user_type as subtitle, 'funcionario' as type, '/admin/usuarios.php?search=' as url FROM usuarios WHERE empresa_id = ? AND (username LIKE ?) LIMIT 3");
    $stmt->execute([$empresa_id, $term]);
    $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 6. MÓDULOS / CONFIGURAÇÕES (Hardcoded checks)
    $modules = [
        ['title' => 'Configurações do Sistema', 'subtitle' => 'Preferências globais', 'type' => 'config', 'url' => '/admin/configuracoes.php'],
        ['title' => 'Gestão de Equipe', 'subtitle' => 'Usuários e permissões', 'type' => 'config', 'url' => '/admin/usuarios.php'],
        ['title' => 'Financeiro Hub', 'subtitle' => 'Fluxo de caixa e contas', 'type' => 'modulo', 'url' => '/modules/financeiro/views/index.php'],
        ['title' => 'Inteligência Tributária', 'subtitle' => 'Fiscal e NF-e', 'type' => 'modulo', 'url' => '/admin/fiscal.php'],
        ['title' => 'CRM Kanban', 'subtitle' => 'Funil de vendas', 'type' => 'modulo', 'url' => '/modules/crm/views/kanban.php']
    ];

    foreach ($modules as $mod) {
        if (stripos($mod['title'], $exact_term) !== false || stripos($mod['subtitle'], $exact_term) !== false) {
            $results[] = $mod;
        }
    }

    echo json_encode(['results' => $results]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
