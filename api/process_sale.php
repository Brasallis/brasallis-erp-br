<?php
// api/process_sale.php - PROCESSAMENTO ASSÍNCRONO
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$user_id = $_SESSION['user_id'];

// Receber dados
$data = json_decode(file_get_contents('php://input'), true);
$cart = $data['cart'] ?? [];
$payments = $data['payments'] ?? [];

if (empty($cart)) {
    echo json_encode(['success' => false, 'error' => 'Carrinho vazio']);
    exit;
}

try {
    $conn->beginTransaction();
    
    $cliente_id = $data['cliente_id'] ?? null;
    $discount_amount = (float)($data['discount_amount'] ?? 0);
    
    $total = 0;
    foreach ($cart as $i) { 
        $total += (float)$i['price'] * (int)$i['quantity']; 
    }
    
    // O total final da venda deve considerar o desconto
    $final_total = max(0, $total - $discount_amount);

    // Inserir Venda (Incluindo cliente e desconto)
    $stmt = $conn->prepare("INSERT INTO vendas (empresa_id, user_id, cliente_id, total_amount, discount_amount, payment_method) VALUES (?, ?, ?, ?, ?, 'múltiplos')");
    $stmt->execute([$empresa_id, $user_id, $cliente_id, $final_total, $discount_amount]);
    $venda_id = $conn->lastInsertId();

    // Inserir Pagamentos
    $p_stmt = $conn->prepare("INSERT INTO venda_pagamentos (venda_id, metodo_pagamento, valor) VALUES (?, ?, ?)");
    foreach ($payments as $p) {
        $p_stmt->execute([$venda_id, $p['method'], $p['value']]);
    }

    // Inserir Itens e Baixa de Estoque
    $i_stmt = $conn->prepare("INSERT INTO venda_itens (venda_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    $s_stmt = $conn->prepare("UPDATE produtos SET quantity = quantity - ? WHERE id = ?");
    foreach ($cart as $item) {
        $i_stmt->execute([$venda_id, $item['id'], $item['quantity'], $item['price']]);
        $s_stmt->execute([$item['quantity'], $item['id']]);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'venda_id' => $venda_id]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
