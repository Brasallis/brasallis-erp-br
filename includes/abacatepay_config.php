<?php
// includes/abacatepay_config.php

class AbacatePay {
    private $apiKey;
    private $baseUrl = 'https://api.abacatepay.com/v2';

    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: (($_ENV['ABACATEPAY_API_KEY'] ?? null) ?: (($_SERVER['ABACATEPAY_API_KEY'] ?? null) ?: getenv('ABACATEPAY_API_KEY')));
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function request($endpoint, $data = null) {
        $options = [
            "http" => [
                "header" => "Authorization: Bearer " . trim($this->apiKey) . "\r\n" .
                            "Content-Type: application/json\r\n" .
                            "Accept: application/json\r\n",
                "method" => $data ? "POST" : "GET",
                "content" => $data ? json_encode($data) : null,
                "ignore_errors" => true
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($this->baseUrl . $endpoint, false, $context);
        return json_decode($result, true);
    }

    public function createProduct($data) {
        return $this->request('/products/create', $data);
    }

    public function createCheckout($data) {
        // Para assinaturas SaaS, o endpoint de precisão é o /subscriptions/create
        return $this->request('/subscriptions/create', $data);
    }
}
