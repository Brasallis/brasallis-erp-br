<?php
namespace App;

class GeminiClient {
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function generateContent($model, $prompt, $systemInstruction = '', $temperature = 0.7) {
        $url = $this->baseUrl . $model . ':generateContent?key=' . $this->apiKey;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => (float)$temperature
            ]
        ];

        if (!empty($systemInstruction)) {
            $data['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new \Exception('Erro cURL: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new \Exception('Erro API Gemini: ' . $result['error']['message']);
        }

        return $result;
    }
}
