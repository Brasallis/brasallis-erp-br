<?php
require_once 'vendor/autoload.php';
use App\Core\Database;

// CONFIGURATION
$EMPRESA_ID = 3; // Pão de Açúcar
$USER_ID = 0; // System/Admin user for this company (we'll try to find one or use 0)

$conn = Database::getInstance()->getConnection();

// Find a user for this company to attribute actions
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE empresa_id = ? LIMIT 1");
$stmt->execute([$EMPRESA_ID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    $USER_ID = $user['id'];
}

echo "Seeding data for Empresa ID: $EMPRESA_ID (User ID: $USER_ID)\n";

// 1. CATEGORIAS
$categorias = ['Mercearia', 'Bebidas', 'Frios e Laticínios', 'Hortifruti', 'Padaria', 'Limpeza', 'Açougue'];
$cat_ids = [];

echo "Creating Categories...\n";
$stmt_check = $conn->prepare("SELECT id FROM categorias WHERE nome = ? AND empresa_id = ?");
$stmt_insert = $conn->prepare("INSERT INTO categorias (nome, empresa_id) VALUES (?, ?)");

foreach ($categorias as $cat) {
    $stmt_check->execute([$cat, $EMPRESA_ID]);
    $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $cat_ids[$cat] = $existing['id'];
    } else {
        $stmt_insert->execute([$cat, $EMPRESA_ID]);
        $cat_ids[$cat] = $conn->lastInsertId();
    }
}

// 2. FORNECEDORES
$fornecedores = [
    'Ambev' => ['email' => 'contato@ambev.com.br', 'phone' => '11999999999'],
    'Unilever' => ['email' => 'vendas@unilever.com', 'phone' => '11888888888'],
    'BRF Foods' => ['email' => 'comercial@brf.com', 'phone' => '11777777777'],
    'Nestlé' => ['email' => 'pedidos@nestle.com', 'phone' => '0800123456'],
    'Coca-Cola FEMSA' => ['email' => 'contato@cocacola.com', 'phone' => '0800987654'],
    'Hortifruti Fornecedor' => ['email' => 'contato@horti.com', 'phone' => '1155555555']
];
$forn_ids = [];

echo "Creating Suppliers...\n";
$stmt_check_forn = $conn->prepare("SELECT id FROM fornecedores WHERE name = ? AND empresa_id = ?");
$stmt_insert_forn = $conn->prepare("INSERT INTO fornecedores (name, email, phone, empresa_id) VALUES (?, ?, ?, ?)");

foreach ($fornecedores as $name => $data) {
    $stmt_check_forn->execute([$name, $EMPRESA_ID]);
    $existing = $stmt_check_forn->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $forn_ids[$name] = $existing['id'];
    } else {
        $stmt_insert_forn->execute([$name, $data['email'], $data['phone'], $EMPRESA_ID]);
        $forn_ids[$name] = $conn->lastInsertId();
    }
}

// 3. PRODUTOS
$produtos = [
    ['name' => 'Arroz Tio João 5kg', 'cat' => 'Mercearia', 'price' => 28.90, 'cost' => 22.00, 'stock' => 200, 'min' => 50],
    ['name' => 'Feijão Camil 1kg', 'cat' => 'Mercearia', 'price' => 8.50, 'cost' => 5.50, 'stock' => 150, 'min' => 30],
    ['name' => 'Óleo de Soja Liza', 'cat' => 'Mercearia', 'price' => 6.90, 'cost' => 4.20, 'stock' => 300, 'min' => 60],
    ['name' => 'Cerveja Skol 350ml', 'cat' => 'Bebidas', 'price' => 3.29, 'cost' => 2.10, 'stock' => 1000, 'min' => 200],
    ['name' => 'Coca-Cola 2L', 'cat' => 'Bebidas', 'price' => 9.90, 'cost' => 6.50, 'stock' => 500, 'min' => 100],
    ['name' => 'Suco Del Valle Uva', 'cat' => 'Bebidas', 'price' => 7.50, 'cost' => 4.80, 'stock' => 200, 'min' => 40],
    ['name' => 'Queijo Mussarela kg', 'cat' => 'Frios e Laticínios', 'price' => 45.90, 'cost' => 32.00, 'stock' => 50, 'min' => 10],
    ['name' => 'Presunto Sadia kg', 'cat' => 'Frios e Laticínios', 'price' => 38.90, 'cost' => 25.00, 'stock' => 40, 'min' => 10],
    ['name' => 'Iogurte Nestlé Morango', 'cat' => 'Frios e Laticínios', 'price' => 4.50, 'cost' => 2.80, 'stock' => 100, 'min' => 20],
    ['name' => 'Maçã Gala kg', 'cat' => 'Hortifruti', 'price' => 9.90, 'cost' => 5.00, 'stock' => 80, 'min' => 20],
    ['name' => 'Banana Prata kg', 'cat' => 'Hortifruti', 'price' => 6.90, 'cost' => 3.50, 'stock' => 100, 'min' => 30],
    ['name' => 'Pão Francês kg', 'cat' => 'Padaria', 'price' => 18.90, 'cost' => 8.00, 'stock' => 30, 'min' => 10],
    ['name' => 'Sabão em Pó Omo 1kg', 'cat' => 'Limpeza', 'price' => 14.90, 'cost' => 10.50, 'stock' => 150, 'min' => 40],
    ['name' => 'Detergente Ypê', 'cat' => 'Limpeza', 'price' => 2.50, 'cost' => 1.60, 'stock' => 400, 'min' => 80],
    ['name' => 'Picanha Bovina kg', 'cat' => 'Açougue', 'price' => 89.90, 'cost' => 65.00, 'stock' => 40, 'min' => 10],
];
$prod_ids = [];

echo "Creating Products...\n";
$stmt_check_prod = $conn->prepare("SELECT id FROM produtos WHERE name = ? AND empresa_id = ?");
$stmt_insert_prod = $conn->prepare("INSERT INTO produtos (name, description, price, cost_price, quantity, minimum_stock, categoria_id, empresa_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($produtos as $p) {
    $stmt_check_prod->execute([$p['name'], $EMPRESA_ID]);
    $existing = $stmt_check_prod->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $prod_ids[] = ['id' => $existing['id'], 'price' => $p['price'], 'cost' => $p['cost']];
    } else {
        $cat_id = $cat_ids[$p['cat']];
        $stmt_insert_prod->execute([$p['name'], 'Produto cadastrado via seed', $p['price'], $p['cost'], $p['stock'], $p['min'], $cat_id, $EMPRESA_ID]);
        $prod_ids[] = ['id' => $conn->lastInsertId(), 'price' => $p['price'], 'cost' => $p['cost']];
    }
}

// 4. VENDAS (HISTÓRICO)
echo "Generating Sales History (this may take a moment)...\n";
$stmt_venda = $conn->prepare("INSERT INTO vendas (empresa_id, user_id, total_amount, payment_method, created_at) VALUES (?, ?, ?, ?, ?)");
$stmt_item = $conn->prepare("INSERT INTO venda_itens (venda_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");

$start_date = strtotime('-12 months');
$end_date = time();
$payment_methods = ['credito', 'debito', 'dinheiro', 'pix'];

// Generate ~500 sales distributed over the last year
for ($i = 0; $i < 500; $i++) {
    $random_timestamp = rand($start_date, $end_date);
    $date = date('Y-m-d H:i:s', $random_timestamp);
    $method = $payment_methods[array_rand($payment_methods)];
    
    // Create Sale
    $stmt_venda->execute([$EMPRESA_ID, $USER_ID, 0, $method, $date]); // Total 0 initially
    $venda_id = $conn->lastInsertId();
    
    // Add Items (1 to 5 items per sale)
    $num_items = rand(1, 5);
    $total_venda = 0;
    
    for ($j = 0; $j < $num_items; $j++) {
        $prod = $prod_ids[array_rand($prod_ids)];
        $qty = rand(1, 3);
        $price = $prod['price'];
        
        $stmt_item->execute([$venda_id, $prod['id'], $qty, $price]);
        $total_venda += ($qty * $price);
    }
    
    // Update Sale Total
    $conn->query("UPDATE vendas SET total_amount = $total_venda WHERE id = $venda_id");
}

// 5. COMPRAS (HISTÓRICO)
$stmt_compra = $conn->prepare("INSERT INTO compras (empresa_id, supplier_id, user_id, purchase_date, total_amount) VALUES (?, ?, ?, ?, ?)");

$forn_ids_indexed = array_values($forn_ids); // Convert to indexed array for random selection

// Generate ~50 purchases
for ($i = 0; $i < 50; $i++) {
    try {
        $random_timestamp = rand($start_date, $end_date);
        $date = date('Y-m-d', $random_timestamp);
        
        if (empty($forn_ids_indexed)) {
            throw new Exception("No suppliers found to create purchases.");
        }

        $supplier_id = $forn_ids_indexed[array_rand($forn_ids_indexed)];
        $total = rand(500, 5000); // Simulated total
        
        $stmt_compra->execute([$EMPRESA_ID, $supplier_id, $USER_ID, $date, $total]);
    } catch (Exception $e) {
        echo "Error inserting purchase: " . $e->getMessage() . "\n";
        echo "Data: SupplierID=$supplier_id, Date=$date, Total=$total\n";
    }
}

echo "Seeding Complete!\n";
?>
