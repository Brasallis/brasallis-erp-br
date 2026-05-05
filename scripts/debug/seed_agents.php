<?php
require_once 'includes/db_config.php';

// Check if run from CLI or Browser
if (php_sapi_name() !== 'cli') {
    // Basic auth check for browser run
    session_start();
    if (!isset($_SESSION['empresa_id'])) {
        die("Acesso negado. Faça login.");
    }
    $empresa_id = $_SESSION['empresa_id'];
} else {
    // Default to empresa 1 for CLI testing, or ask user later. 
    // Assuming context is usually single tenant or user is id 1.
    // Let's use 1 for now, or fetch the first existing company.
    $empresa_id = 1; 
}

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get actual company ID if CLI and using 1 is risky
    if (php_sapi_name() === 'cli') {
        $stmt = $conn->query("SELECT id FROM empresas LIMIT 1");
        $empresa_id = $stmt->fetchColumn();
        if (!$empresa_id) die("Nenhuma empresa encontrada.");
    }

    $agents = [
        [
            'name' => 'Growth Manager',
            'role' => 'Estrategista de Negócios',
            'model' => 'gemini-2.5-pro', // Best model for strategy
            'instruction' => 'Você é um estrategista de negócios sênior focado em e-commerce e varejo. Seu objetivo é analisar dados financeiros e de vendas para encontrar oportunidades de redução de custos (CAC) e aumento de margem de lucro. Seja direto, use termos técnicos de negócios (LTV, Churn, ROI) mas explique-os. Sempre que possível, cruze dados de vendas com estoque.',
            'temperature' => 0.5 // Balanced
        ],
        [
            'name' => 'SEO Specialist',
            'role' => 'Especialista em Marketing',
            'model' => 'gemini-2.5-flash', // Good for text generation
            'instruction' => 'Você é um especialista em SEO para e-commerce. Sua função é criar títulos de produtos atraentes, descrições ricas em palavras-chave e sugerir tags para melhorar o ranqueamento no Google. Ao analisar um produto, foque em: Palavras-chave de cauda longa, Benefícios principais e Gatilhos mentais de compra.',
            'temperature' => 0.7 // Creative
        ],
        [
            'name' => 'Trend Hunter',
            'role' => 'Pesquisador de Produtos',
            'model' => 'gemini-2.5-flash',
            'instruction' => 'Você é um analista de tendências de mercado. Analise os produtos mais vendidos da loja e identifique padrões de consumo. Sugira promoções para produtos parados (Estoque > Vendas) e estratégias de upsell para os campeões de venda. Use a Curva ABC como base teórica.',
            'temperature' => 0.4
        ],
        [
            'name' => 'Sarah (Secretária)',
            'role' => 'Assistente Executiva',
            'model' => 'gemini-2.5-flash',
            'instruction' => 'Você é Sarah, uma secretária executiva eficiente e educada. Sua função é organizar informações, formatar e-mails corporativos, resumir reuniões e preparar pautas. Mantenha um tom extremamente profissional, polido e prestativo. Nunca invente dados, apenas formate o que for fornecido.',
            'temperature' => 0.3 // Precise
        ]
    ];

    echo "<h3>Criando Agentes para Empresa ID: $empresa_id</h3>";
    echo "<ul>";

    $sql = "INSERT INTO ai_agents (empresa_id, name, role, model, system_instruction, temperature, status) 
            VALUES (:empresa_id, :name, :role, :model, :instruction, :temperature, 'active')";
    $stmt = $conn->prepare($sql);

    foreach ($agents as $agent) {
        // Check duplication
        $check = $conn->prepare("SELECT id FROM ai_agents WHERE empresa_id = ? AND name = ?");
        $check->execute([$empresa_id, $agent['name']]);
        if ($check->fetch()) {
            echo "<li>Agente <strong>{$agent['name']}</strong> já existe. Pulando...</li>";
            continue;
        }

        $stmt->execute([
            ':empresa_id' => $empresa_id,
            ':name' => $agent['name'],
            ':role' => $agent['role'],
            ':model' => $agent['model'],
            ':instruction' => $agent['instruction'],
            ':temperature' => $agent['temperature']
        ]);
        echo "<li>Agente <strong>{$agent['name']}</strong> criado com sucesso! ✅</li>";
    }

    echo "</ul>";
    echo "<p>Concluído.</p>";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
