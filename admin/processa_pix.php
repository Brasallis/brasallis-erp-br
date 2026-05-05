<?php
// admin/processa_pix.php
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
    'growth' => 99.00,
    'enterprise' => 299.00
];

if (!isset($prices[$plan])) {
    echo json_encode(['error' => 'Plano inválido']);
    exit;
}

$amount = $prices[$plan];
$empresa_id = $_SESSION['empresa_id'];
$user_email = $_SESSION['user_email'] ?? 'email@teste.com'; // Fallback se não tiver na sessão

try {
    $mp = new MercadoPagoService();
    $paymentData = [
        'amount' => $amount,
        'description' => "Assinatura Gestor Inteligente - " . ucfirst($plan),
        'email' => $user_email,
        'first_name' => $_SESSION['username'], // Usar nome da sessão
        'last_name' => 'User', 
        'external_ref' => "EMP_{$empresa_id}_PLAN_{$plan}_" . time()
    ];

    $response = $mp->createPixPayment($paymentData);

    if (isset($response['id'])) {
        // Salvar no Banco
        $conn = connect_db();
        $stmt = $conn->prepare("INSERT INTO pagamentos (empresa_id, external_ref, amount, status, payment_method, qr_code, qr_code_base64, plan_type) VALUES (?, ?, ?, ?, 'pix', ?, ?, ?)");
        
        $qr_code = $response['point_of_interaction']['transaction_data']['qr_code'];
        $qr_code_base64 = $response['point_of_interaction']['transaction_data']['qr_code_base64'];
        
        $stmt->execute([
            $empresa_id,
            $response['id'], // External Ref do MP
            $amount,
            $response['status'],
            $qr_code,
            $qr_code_base64,
            $plan
        ]);
        
        $paymentId = $conn->lastInsertId();

        echo json_encode([
            'payment_id' => $paymentId,
            'qr_code' => $qr_code,
            'qr_code_base64' => $qr_code_base64
        ]);
    } else {
        echo json_encode(['error' => 'Erro MP: ' . json_encode($response)]);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
