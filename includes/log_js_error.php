<?php
/**
 * log_js_error.php
 * Endpoint AJAX para receber e salvar erros de JavaScript no painel do Super Admin.
 */

header('Content-Type: application/json');

// Impedir acesso direto se não for POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

require_once __DIR__ . '/funcoes.php';

// Pegar dados do JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data && isset($data['message'])) {
    $message = $data['message'];
    $stack = $data['stack'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $full_message = "[JS Error] " . $message . " | Browser: " . $userAgent;
    
    registrar_erro_sistema($full_message, 'error', 'JavaScript', $stack);
    
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
}
