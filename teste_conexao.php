<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
// teste_conexao.php
$apiKey = "abc_dev_nLZtDkjjSZZrEz5PwU5uQx4q";
$url = "https://api.abacatepay.com/v2/store/get";

$options = [
    "http" => [
        "header" => "Authorization: Bearer " . trim($apiKey) . "\r\n" .
                    "Content-Type: application/json\r\n",
        "method" => "GET",
        "ignore_errors" => true
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "<h1>Resultado do Teste:</h1>";
echo "<pre>";
print_r($http_response_header);
echo "\n\nBody:\n";
echo htmlspecialchars($result);
echo "</pre>";

