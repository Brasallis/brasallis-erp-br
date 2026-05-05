<?php
// admin/processa_preference.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';
require_once '../includes/MercadoPagoService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['empresa_id'])) {
    echo json_encode(['error' => 'Sessão expirada']);
    exit;
}

$plan = $_POST['plan'];
$prices = [
    'growth' => 149.90,
    'enterprise' => 499.90
];

if (!isset($prices[$plan])) {
    echo json_encode(['error' => 'Plano inválido']);
    exit;
}

$amount = $prices[$plan];
$empresa_id = $_SESSION['empresa_id'];
$user_email = $_SESSION['user_email'] ?? 'email@teste.com';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_url = "$protocol://$host/admin";

try {
    $mp = new MercadoPagoService();
    $successUrl = "$base_url/sucesso.php";
    $failureUrl = "$base_url/checkout.php?error=payment_failed";
    $pendingUrl = "$base_url/sucesso.php?status=pending";

    $notificationUrl = "$protocol://$host/api/webhook_mercadopago.php";
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $notificationUrl = null; 
    }

    $paymentData = [
        'amount' => $amount,
        'description' => "Assinatura Brasallis Hub - " . ucfirst($plan),
        'email' => $user_email,
        'first_name' => $_SESSION['username'],
        'last_name' => 'User', 
        'external_ref' => "EMP_{$empresa_id}_PLAN_{$plan}_" . time(),
        'back_urls' => [
            'success' => $successUrl,
            'failure' => $failureUrl,
            'pending' => $pendingUrl
        ],
        'notification_url' => $notificationUrl
    ];

    $response = $mp->createPreference($paymentData);

    if (isset($response['id'])) {
        echo json_encode([
            'preference_id' => $response['id'],
            'init_point' => $response['init_point'],
            'sandbox_init_point' => $response['sandbox_init_point']
        ]);
    } else {
        echo json_encode(['error' => 'Erro MP: ' . json_encode($response)]);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
