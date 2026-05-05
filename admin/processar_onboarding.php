<?php
// admin/processar_onboarding.php v2.0
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados do Onboarding
    $empresa_id = $_POST['empresa_id'] ?? $_SESSION['empresa_id'] ?? 1;
    $plan = $_POST['plan'] ?? 'foundation';
    $segmento = $_POST['segmento'] ?? 'Outros';
    $modules = $_POST['modules'] ?? ['estoque']; 
    $branding_color = $_POST['branding_color'] ?? '#000000';
    $qtd_funcionarios = (int)($_POST['qtd_funcionarios'] ?? 0);
    
    // Limites de Token e Usuários por Plano (Usando Configuração Centralizada)
    require_once __DIR__ . '/../includes/planos_config.php';
    $token_limit = get_ai_limit_by_plan($plan);
    
    $central_config = get_planos_config();
    $max_users = $central_config['planos'][$plan]['users_limit'] ?? 3;

    $conn = connect_db();

    // 1. Tenta adicionar as colunas caso não existam (FORA DA TRANSAÇÃO para evitar implicit commits)
    try { @$conn->exec("ALTER TABLE empresas ADD COLUMN branding_color VARCHAR(20) DEFAULT '#000000'"); } catch(Exception $e){}
    try { @$conn->exec("ALTER TABLE empresas ADD COLUMN ai_token_limit BIGINT DEFAULT 200000"); } catch(Exception $e){}

    try {
        $conn->beginTransaction();

        // 2. Salva os dados na empresa
        $modules_json = json_encode($modules);
        $stmt = $conn->prepare("UPDATE empresas SET 
            ai_plan = ?, 
            active_modules = ?, 
            segmento = ?, 
            branding_color = ?, 
            ai_token_limit = ?,
            max_users = ?,
            onboarding_completed = 1 
            WHERE id = ?");
        $stmt->execute([$plan, $modules_json, $segmento, $branding_color, $token_limit, $max_users, $empresa_id]);

        // 3. Define setores padrão por segmento
        $setores_padrao = [
            'Mercado' => ['Frente de Caixa', 'Estoque', 'Administrativo'],
            'Loja de Roupas' => ['Vendas', 'Estoque', 'Financeiro'],
            'Marcenaria' => ['Produção', 'Comercial', 'Financeiro'],
            'Adega' => ['Caixa', 'Estoque', 'Distribuição'],
            'Industrial' => ['Chão de Fábrica', 'Logística', 'RH'],
            'Outros' => ['Geral', 'Administrativo']
        ];

        $setores = $setores_padrao[$segmento] ?? $setores_padrao['Outros'];

        // 3. Cria os setores no banco
        foreach ($setores as $nome_setor) {
            $check = $conn->prepare("SELECT id FROM setores WHERE nome = ? AND empresa_id = ?");
            $check->execute([$nome_setor, $empresa_id]);
            
            if (!$check->fetch()) {
                $ins = $conn->prepare("INSERT INTO setores (empresa_id, nome) VALUES (?, ?)");
                $ins->execute([$empresa_id, $nome_setor]);
                $setor_id = $conn->lastInsertId();

                $ins_cargo = $conn->prepare("INSERT INTO cargos (setor_id, nome, nivel_hierarquia) VALUES (?, ?, ?)");
                $ins_cargo->execute([$setor_id, 'Responsável ' . $nome_setor, 1]);
            }
        }

        $conn->commit();
        
        $_SESSION['message'] = "Setup completo! O Brasallis foi moldado para o seu negócio.";
        $_SESSION['message_type'] = "success";
        
        if ($_SESSION['user_type'] === 'super_admin') {
            header("Location: ../superadmin/empresas.php");
        } else {
            header("Location: painel_admin.php");
        }
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['message'] = "Erro no setup: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header("Location: onboarding.php");
        exit;
    }
}
