<?php
// api/chat_agent.php
header('Content-Type: application/json');
require_once '../includes/db_config.php';
require_once '../classes/AIAgent.php';
require_once '../classes/AITools.php';
require_once '../classes/AIPlanManager.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['empresa_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$agentId = $input['agent_id'] ?? null;
$message = $input['message'] ?? null;
$history = $input['history'] ?? []; // Histórico da conversa (opcional)

if (!$agentId || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Agent ID e mensagem são obrigatórios']);
    exit;
}

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 0. Verificar Plano e Limites
    $planManager = new App\AIPlanManager($conn, $_SESSION['empresa_id']);
    try {
        $planManager->checkLimit();
    } catch (Exception $e) {
        throw new Exception($e->getMessage()); // Repassa erro amigável
    }

    // 1. Carregar Agente
    $aiAgent = new App\AIAgent($conn);
    $agentData = $aiAgent->getById($agentId, $_SESSION['empresa_id']);

    if (!$agentData) {
        throw new Exception("Agente não encontrado");
    }

    // 2. Preparar Ferramentas
    $aiTools = new App\AITools($conn, $_SESSION['empresa_id']);
    $toolsSchema = $aiTools->getToolsSchema();

    // 3. Obter API Key (Prioridade: Empresa -> Environment)
    $stmt = $conn->prepare("SELECT gemini_api_key FROM empresas WHERE id = ?");
    $stmt->execute([$_SESSION['empresa_id']]);
    $apiKeyGoogle = $stmt->fetchColumn();

    if (!$apiKeyGoogle) {
         $apiKeyGoogle = getenv('GEMINI_API_KEY_GLOBAL'); 
    }

    // OpenAI Key (Placeholder or Environment)
    $apiKeyOpenAI = getenv('OPENAI_API_KEY') ?: 'sk-placeholder'; 

    // 4. Determinar Modelo e Provedor
    $modelName = $input['model'] ?? $agentData['model'] ?? 'gemini-2.5-flash';
    $provider = $input['provider'] ?? 'google';

    // Se o modelo for explicitamente OpenAI (gpt-...), forçar provider
    if (strpos($modelName, 'gpt') === 0) {
        $provider = 'openai';
    }

    // Instanciar AIService
    require_once '../classes/AIService.php';
    $aiService = new App\AIService($conn, $apiKeyGoogle, $apiKeyOpenAI);

    // 5. Gerar Resposta via Service
    try {
        $result = $aiService->generateResponse(
            $provider, 
            $modelName, 
            $agentData['system_instruction'] . "\n\nIMPORTANTE:\n1. Você tem acesso a ferramentas de banco de dados. Use-as sempre que pedir dados factuais.\n2. Responda em Português do Brasil.\n3. SEJA CONCISO E DIRETO. Use listas (bullets) para facilitar a leitura.\n4. Se a resposta tiver múltiplos assuntos distintos ou for longa, divida-a usando estritamente o separador <<<SPLIT>>>. Exemplo: 'Assunto A... <<<SPLIT>>> Assunto B...'.\n5. Use Markdown para formatação (negrito **texto**, títulos ### Título).", 
            $history, 
            $message, 
            $toolsSchema, 
            $aiTools
        );
        
        $finalResponseText = $result['response'];
        $richWidget = $result['widget'];

    } catch (Exception $e) {
        throw new Exception("Erro no Serviço de IA ($provider): " . $e->getMessage());
    }

    // 8. Responder ao Frontend
    echo json_encode([
        'response' => $finalResponseText,
        'widget' => $richWidget,
        'used_model' => $modelName // Útil para debug
    ]);

    // 9. Registrar Uso (Logging Simplificado por enquanto)
    if (isset($_SESSION['user_id'])) {
        // ... (Logging logic can be refined later via AIService or kept here)
        $aiAgent->logUsage($agentId, $_SESSION['user_id'], strlen($message)/4, strlen($finalResponseText)/4);
        $planManager->incrementUsage(strlen($message)/4 + strlen($finalResponseText)/4);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
