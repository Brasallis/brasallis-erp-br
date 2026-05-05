<?php
// registrar_pagamento.php
session_start();
require_once 'bootstrap.php';
require_once 'includes/funcoes.php';
require_once 'includes/planos_config.php';
require_once 'includes/abacatepay_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$plan_type = $_GET['plan'] ?? $_SESSION['ai_plan'] ?? 'foundation';
$empresa_id = $_SESSION['empresa_id'];

$config = get_planos_config();
$plano_detalhes = $config['detalhes'][$plan_type];
$amount = $config['limites'][$plan_type]['preco_decimal'] * 100; // Centavos

$abacate = new AbacatePay();

// 1. Criamos ou garantimos que o produto existe (campos mínimos)
$product_data = [
    'externalId' => 'plan_' . $plan_type . '_' . (int)$amount,
    'name' => 'Plano ' . $plano_detalhes['nome'],
    'price' => (int)$amount,
    'currency' => 'BRL'
];

$product_res = $abacate->request('/products/create', $product_data);

// Pegamos o ID retornado ou usamos o externalId como fallback
$product_id = $product_res['data']['id'] ?? null;

if (!$product_id) {
    // Se falhou ao criar (ex: já existe), tentamos listar para achar o ID
    $products_list = $abacate->request('/products/list');
    if (isset($products_list['data'])) {
        foreach ($products_list['data'] as $p) {
            if ($p['externalId'] == $product_data['externalId']) {
                $product_id = $p['id'];
                break;
            }
        }
    }
}

// Se ainda não tivermos o ID, usamos o externalId e torcemos para a API aceitar
if (!$product_id) $product_id = $product_data['externalId'];

// 2. Criamos o Checkout
$checkout_data = [
    'items' => [
        [
            'id' => $product_id,
            'quantity' => 1
        ]
    ],
    'returnUrl' => 'http://' . $_SERVER['HTTP_HOST'] . '/admin/configuracoes.php?payment=success',
    'completionUrl' => 'http://' . $_SERVER['HTTP_HOST'] . '/admin/configuracoes.php?payment=success'
];

$response = $abacate->request('/checkouts/create', $checkout_data);

if ($response && isset($response['data']['url'])) {
    $checkout_url = $response['data']['url'];
    $external_id = $response['data']['id'];

    // Salvar na tabela de pagamentos
    $conn = connect_db();
    $stmt = $conn->prepare("INSERT INTO pagamentos (empresa_id, external_ref, amount, status, payment_method, plan_type, checkout_url) VALUES (?, ?, ?, 'pending', 'card', ?, ?)");
    $stmt->execute([$empresa_id, $external_id, ($amount / 100), $plan_type, $checkout_url]);

    // Redirecionar para o Checkout da AbacatePay
    header("Location: " . $checkout_url);
    exit;
} else {
    // Erro amigável para o usuário
    $error_msg = $response['error'] ?? 'Não foi possível gerar o link de pagamento. Tente novamente.';
    if (is_array($error_msg)) $error_msg = json_encode($error_msg);
    $_SESSION['error'] = "Erro AbacatePay: " . $error_msg;
    header("Location: admin/configuracoes.php");
    exit;
}
