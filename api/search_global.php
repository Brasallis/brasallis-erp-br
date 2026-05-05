<?php
// api/search_global.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/funcoes.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// 1. Clientes
$stmt = $conn->prepare("SELECT id, nome as title, 'cliente' as type FROM clientes WHERE empresa_id = ? AND nome LIKE ? LIMIT 5");
$stmt->execute([$empresa_id, "%$query%"]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['url'] = "/modules/crm/views/cliente_form.php?id=" . $row['id'];
    $row['icon'] = "fas fa-user-tie";
    $results[] = $row;
}

// 2. Produtos
$stmt = $conn->prepare("SELECT id, name as title, 'produto' as type FROM produtos WHERE empresa_id = ? AND (name LIKE ? OR sku LIKE ?) LIMIT 5");
$stmt->execute([$empresa_id, "%$query%", "%$query%"]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['url'] = "/admin/produtos.php?search=" . urlencode($row['title']);
    $row['icon'] = "fas fa-box";
    $results[] = $row;
}

// 3. Setores
$stmt = $conn->prepare("SELECT id, nome as title, 'setor' as type FROM setores WHERE empresa_id = ? AND nome LIKE ? LIMIT 3");
$stmt->execute([$empresa_id, "%$query%"]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row['url'] = "/admin/setor_config.php?id=" . $row['id'];
    $row['icon'] = "fas fa-folder";
    $results[] = $row;
}

// 4. Paginas Estaticas (Menu)
$menus = [
    ['title' => 'Dashboard', 'url' => '/admin/painel_admin.php', 'icon' => 'fas fa-chart-pie'],
    ['title' => 'Fluxo de Caixa', 'url' => '/modules/financeiro/views/fluxo_caixa.php', 'icon' => 'fas fa-wallet'],
    ['title' => 'Ponto Eletrônico', 'url' => '/modules/rh/views/ponto.php', 'icon' => 'fas fa-clock'],
    ['title' => 'Gestão de Clientes', 'url' => '/modules/crm/views/clientes.php', 'icon' => 'fas fa-users'],
    ['title' => 'Configurações da Empresa', 'url' => '/admin/organizacao.php', 'icon' => 'fas fa-hotel']
];

foreach($menus as $m) {
    if (stripos($m['title'], $query) !== false) {
        $m['type'] = 'pagina';
        $results[] = $m;
    }
}

echo json_encode(array_values($results));
