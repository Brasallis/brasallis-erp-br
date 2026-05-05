<?php
// api/v1/config.php

// 1. Configurações de Cabeçalho e CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle Preflight options
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/db_config.php';

// 2. Conexão DB
function get_db_connection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        send_json_response(['error' => 'Database connection failed'], 500);
        exit;
    }
}

// 3. Helper de Resposta JSON
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

// 4. Autenticação via Bearer Token
function authenticate_api_request($conn) {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    if (empty($headers)) {
        send_json_response(['error' => 'Authorization header missing'], 401);
    }

    if (!preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        send_json_response(['error' => 'Invalid authorization format'], 401);
    }

    $token = $matches[1];

    $stmt = $conn->prepare("SELECT * FROM api_keys WHERE api_key = ? AND is_active = 1");
    $stmt->execute([$token]);
    $api_key_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$api_key_data) {
        send_json_response(['error' => 'Invalid API Key'], 403);
    }

    // Update Last Used
    $stmt = $conn->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
    $stmt->execute([$api_key_data['id']]);

    return $api_key_data; // Retorna dados da chave (empresa_id, permissoes)
}
?>
