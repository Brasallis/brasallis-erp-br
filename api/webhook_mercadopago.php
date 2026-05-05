<?php
// api/webhook_mercadopago.php
require_once '../includes/db_config.php';
require_once '../includes/funcoes.php'; // Para connect_db e helpers
require_once '../includes/MercadoPagoService.php';

// Log da requisição (para debug)
$logFile = 'webhook_log.txt';
$body = file_get_contents('php://input');
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Body: " . $body . "\n", FILE_APPEND);

$data = json_decode($body, true);

// Verifica se é uma notificação de pagamento
if (isset($data['action']) && $data['action'] === 'payment.created') {
    $paymentId = $data['data']['id'];
} elseif (isset($_GET['type']) && $_GET['type'] === 'payment') {
    $paymentId = $_GET['data.id'];
} else {
    http_response_code(200); // MP espera 200 mesmo se ignorarmos
    exit;
}

try {
    $mp = new MercadoPagoService();
    $payment = $mp->getPayment($paymentId);

    if (isset($payment['status']) && $payment['status'] === 'approved') {
        $conn = connect_db();
        
        // 1. Parsear external_reference para identificar a empresa e o plano
        // Formato: EMP_{id}_PLAN_{plano}_{timestamp}
        $externalRef = $payment['external_reference'];
        $parts = explode('_', $externalRef);
        
        $empresaIds = null;
        $newPlan = null;
        
        // Verifica se o formato está correto
        if (count($parts) >= 4 && $parts[0] === 'EMP' && $parts[2] === 'PLAN') {
            $empresaIds = $parts[1];
            $newPlan = $parts[3];
        }

        if ($empresaIds && $newPlan) {
            $conn = connect_db();
            
            // 2. Atualizar Empresa (Plano e Validade)
            // Define validade de 30 dias a partir de agora
            $newExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            $tokenLimit = ($newPlan === 'enterprise') ? 10000000 : 2000000;
            $userLimit = ($newPlan === 'enterprise') ? 999 : 5;

            $stmt = $conn->prepare("UPDATE empresas SET ai_plan = ?, plan_expires_at = ?, support_level = 'priority', ai_token_limit = ?, max_users = ? WHERE id = ?");
            $stmt->execute([$newPlan, $newExpiry, $tokenLimit, $userLimit, $empresaIds]);

            // 3. Registrar o Pagamento no Histórico Local
            $stmt = $conn->prepare("INSERT INTO pagamentos (empresa_id, external_ref, amount, status, payment_method, plan_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $empresaIds, 
                $paymentId, 
                $payment['transaction_amount'], 
                'approved', 
                $payment['payment_method_id'], 
                $newPlan
            ]);

            file_put_contents($logFile, "Pagamento Aprovado! Empresa ID: $empresaIds atualizada para $newPlan.\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "Erro: External Ref inválido: $externalRef\n", FILE_APPEND);
        }
    }

    http_response_code(200);

} catch (Exception $e) {
    file_put_contents($logFile, "Erro: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
}
