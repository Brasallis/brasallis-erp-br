<?php
// includes/MercadoPagoService.php

class MercadoPagoService {
    private $accessToken;

    public function __construct() {
        // Obter do ENV ou definir aqui
        $this->accessToken = getenv('MP_ACCESS_TOKEN') ?: 'TEST-5795992890758151-123009-5792e225da346d5d8652713c1950c277-199907225';
    }

    public function createPixPayment($paymentData) {
        $url = 'https://api.mercadopago.com/v1/payments';
        
        $body = [
            'transaction_amount' => (float)$paymentData['amount'],
            'description' => $paymentData['description'],
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $paymentData['email'],
                'first_name' => $paymentData['first_name'],
                'last_name' => $paymentData['last_name'],
                'identification' => [
                    'type' => 'CPF',
                    'number' => '19119119100' // Placeholder se não tiver no cadastro
                ]
            ],
            'external_reference' => $paymentData['external_ref'],
            'notification_url' => 'https://seusite.com/api/webhook_mercadopago.php' // Ajustar em PROD
        ];

        return $this->post($url, $body);
    }

    private function post($url, $data) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Idempotency-Key: ' . uniqid(),
                'Authorization: Bearer ' . $this->accessToken
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception("cURL Error: " . $err);
        }

        return json_decode($response, true);
    }

    public function createPreference($paymentData) {
        $url = 'https://api.mercadopago.com/checkout/preferences';

        $body = [
            'items' => [
                [
                    'title' => $paymentData['description'],
                    'quantity' => 1,
                    'unit_price' => (float)$paymentData['amount'],
                    'currency_id' => 'BRL'
                ]
            ],
            'payer' => [
                'email' => $paymentData['email'],
                'name' => $paymentData['first_name'],
                'surname' => $paymentData['last_name']
            ],
            // 'auto_return' => 'approved', // Removido do padrão para evitar erro
            'external_reference' => $paymentData['external_ref'],
            'notification_url' => $paymentData['notification_url'] ?? null
        ];

        // Configuração de Back URLs (Retorno)
        if (isset($paymentData['back_urls'])) {
            $body['back_urls'] = $paymentData['back_urls'];
        } elseif (isset($paymentData['back_url'])) {
            $body['back_urls'] = [
                'success' => $paymentData['back_url'] . '?status=success',
                'failure' => $paymentData['back_url'] . '?status=failure',
                'pending' => $paymentData['back_url'] . '?status=pending'
            ];
        }

        // Se tiver back_urls (success), define auto_return
        /*
        if (isset($body['back_urls']['success'])) {
            $body['auto_return'] = 'approved';
        }
        */
        
        // Log para debug (opcional)
        // file_put_contents('mp_log.txt', print_r($body, true), FILE_APPEND);

        return $this->post($url, $body);
    }

    public function getPayment($id) {
        $url = 'https://api.mercadopago.com/v1/payments/' . $id;
        return $this->get($url);
    }

    private function get($url) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
}

