<?php
namespace App;

class AIService {
    private $googleApiKey;
    private $openaiApiKey;
    private $conn; // Para log

    public function __construct($conn, $googleApiKey, $openaiApiKey = null) {
        $this->conn = $conn;
        $this->googleApiKey = $googleApiKey;
        $this->openaiApiKey = $openaiApiKey;
    }

    public function generateResponse($modelType, $modelName, $systemInstruction, $history, $message, $toolsSchema, $aiToolsInstance) {
        if ($modelType === 'openai') {
            return $this->callOpenAI($modelName, $systemInstruction, $history, $message, $toolsSchema, $aiToolsInstance);
        } else {
            return $this->callGemini($modelName, $systemInstruction, $history, $message, $toolsSchema, $aiToolsInstance);
        }
    }

    private function callGemini($modelName, $systemInstruction, $history, $message, $toolsSchema, $aiToolsInstance) {
        // Normalização do nome do modelo
        if (strpos($modelName, 'gemini') === false) $modelName = 'gemini-2.5-flash';
        if ($modelName === 'gemini-1.5-flash' || $modelName === 'gemini-1.5-flash-latest') $modelName = 'gemini-2.5-flash'; // Fallback for old refs

        $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/' . $modelName . ':generateContent?key=' . $this->googleApiKey;

        // Converter histórico para formato Gemini
        $contents = [];
        foreach ($history as $msg) {
            $contents[] = ['role' => $msg['role'] === 'user' ? 'user' : 'model', 'parts' => [['text' => $msg['content']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        $payload = [
            'contents' => $contents,
            'systemInstruction' => ['parts' => [['text' => $systemInstruction . "\n\nIMPORTANTE: Use ferramentas para dados factuais."]]],
            'tools' => [['functionDeclarations' => $toolsSchema]]
        ];

        // Turno 1
        $response1 = $this->makeRequest($baseUrl, $payload);
        $responseJson = json_decode($response1, true);

        // Processar Tool Calls
        $finalText = "";
        $richWidget = null;
        $toolCallsResults = [];

        if (isset($responseJson['candidates'][0]['content']['parts'])) {
            foreach ($responseJson['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['functionCall'])) {
                    $fnName = $part['functionCall']['name'];
                    $fnArgs = $part['functionCall']['args'];

                    if (method_exists($aiToolsInstance, $fnName)) {
                        $toolResultRaw = $aiToolsInstance->$fnName($fnArgs);
                        $decoded = json_decode($toolResultRaw, true);
                        
                        if (is_array($decoded) && isset($decoded['type'])) {
                            $richWidget = $decoded; // Widget para o frontend
                            $toolResponseText = "Dados: " . json_encode($decoded['data']);
                        } else {
                            $toolResponseText = $toolResultRaw;
                        }

                        $toolCallsResults[] = [
                            'call' => $part['functionCall'],
                            'response' => ['name' => $fnName, 'response' => ['content' => $toolResponseText]]
                        ];
                    }
                } elseif (isset($part['text'])) {
                    $finalText .= $part['text'];
                }
            }
        }

        // Se houve chamadas de função, Turno 2
        if (!empty($toolCallsResults)) {
            $contents[] = ['role' => 'model', 'parts' => array_map(function($t) { return ['functionCall' => $t['call']]; }, $toolCallsResults)];
            
            $partsResponse = [];
            foreach($toolCallsResults as $tr) {
                $partsResponse[] = ['functionResponse' => $tr['response']];
            }
            $contents[] = ['role' => 'function', 'parts' => $partsResponse];

            $payload['contents'] = $contents;
            unset($payload['tools']); // Remove tools definitions for turn 2 to follow some API patterns or keep it

            $response2 = $this->makeRequest($baseUrl, $payload);
            $responseJson2 = json_decode($response2, true);
            
            if (isset($responseJson2['candidates'][0]['content']['parts'][0]['text'])) {
                $finalText = $responseJson2['candidates'][0]['content']['parts'][0]['text'];
            }
            
            // Usage Log Logic would adhere here
        }

        return ['response' => $finalText, 'widget' => $richWidget];
    }

    private function callOpenAI($modelName, $systemInstruction, $history, $message, $toolsSchema, $aiToolsInstance) {
        if (!$this->openaiApiKey) throw new \Exception("OpenAI API Key não configurada.");

        $baseUrl = 'https://api.openai.com/v1/chat/completions';
        
        // Converter Tools para formato OpenAI
        $openaiTools = [];
        foreach ($toolsSchema as $tool) {
            $openaiTools[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $tool['parameters']
                ]
            ];
        }

        // Converter Histórico
        $messages = [['role' => 'system', 'content' => $systemInstruction]];
        foreach ($history as $msg) {
            // OpenAI roles: system, user, assistant, tool
            $role = $msg['role'] === 'model' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $payload = [
            'model' => $modelName, // 'gpt-4o', 'gpt-3.5-turbo'
            'messages' => $messages,
            'tools' => $openaiTools,
            'tool_choice' => 'auto'
        ];

        // Turno 1
        $response1 = $this->makeRequest($baseUrl, $payload, ['Authorization: Bearer ' . $this->openaiApiKey]);
        $responseJson = json_decode($response1, true);
        
        if (isset($responseJson['error'])) throw new \Exception("OpenAI Error: " . $responseJson['error']['message']);

        $messageObj = $responseJson['choices'][0]['message'];
        $finalText = $messageObj['content'] ?? '';
        $richWidget = null;

        if (isset($messageObj['tool_calls'])) {
            $messages[] = $messageObj; // Adiciona a resposta do assistente (tool calls) ao histórico

            foreach ($messageObj['tool_calls'] as $toolCall) {
                $fnName = $toolCall['function']['name'];
                $fnArgs = json_decode($toolCall['function']['arguments'], true);

                if (method_exists($aiToolsInstance, $fnName)) {
                    $toolResultRaw = $aiToolsInstance->$fnName($fnArgs);
                    $decoded = json_decode($toolResultRaw, true);

                    if (is_array($decoded) && isset($decoded['type'])) {
                        $richWidget = $decoded;
                        $toolResponseText = "Dados: " . json_encode($decoded['data']);
                    } else {
                        $toolResponseText = $toolResultRaw;
                    }

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'name' => $fnName,
                        'content' => $toolResponseText
                    ];
                }
            }

            // Turno 2
            $payload['messages'] = $messages;
            unset($payload['tools']); // Opcional, mas economiza tokens
            unset($payload['tool_choice']);

            $response2 = $this->makeRequest($baseUrl, $payload, ['Authorization: Bearer ' . $this->openaiApiKey]);
            $responseJson2 = json_decode($response2, true);
            $finalText = $responseJson2['choices'][0]['message']['content'];
        }

        return ['response' => $finalText, 'widget' => $richWidget];
    }

    private function makeRequest($url, $data, $headers = []) {
        $ch = curl_init($url);
        $defaultHeaders = ['Content-Type: application/json'];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Curl Error: ' . $error);
        }
        
        curl_close($ch);
        
        if ($httpCode >= 400) {
            $json = json_decode($result, true);
            $msg = $json['error']['message'] ?? $json['error'] ?? 'HTTP Error ' . $httpCode;
            if (is_array($msg)) $msg = json_encode($msg);
            throw new \Exception("API Error ($httpCode): " . $msg);
        }

        return $result;
    }
}
