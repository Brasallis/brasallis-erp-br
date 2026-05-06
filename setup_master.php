<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
/**
 * 🚀 Brasallis Hub 360 - Master Setup Script
 * Este script automatiza a inicialização do banco de dados e migrações.
 */

require_once __DIR__ . '/bootstrap.php';

// Permite execução via CLI sem chave
if (php_sapi_name() !== 'cli') {
    check_master_key();
}

echo "--- INICIANDO SETUP BRASALLIS HUB ---\n";

try {
    // 1. Conexão Base
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . $port, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Criar Banco
    echo "[1/3] Criando banco de dados " . DB_NAME . "...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $pdo->exec("USE " . DB_NAME);

    // 3. Executar Script Base (PHP)
    echo "[2/3] Executando estrutura base (PHP)...\n";
    ob_start();
    include __DIR__ . '/scripts/migrations/configurar_banco_dados.php';
    $output = ob_get_clean();
    echo "Estrutura base concluída.\n";

    // 4. Executar Migrações SQL Sequenciais
    echo "[3/3] Aplicando migrações de arquitetura (SQL)...\n";
    $migrations = [
        'scripts/migrations/001_create_pyramid_architecture.sql',
        'scripts/migrations/002_create_crm_tables.sql',
        'scripts/migrations/003_create_financial_tables.sql',
        'scripts/migrations/004_create_fiscal_tables.sql',
        'scripts/migrations/005_create_api_structure.sql',
        'sql/create_ai_agents_table_corrigido.sql',
        'sql/add_ai_plans.sql'
    ];

    foreach ($migrations as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "-> Aplicando: $file\n";
            $sql = file_get_contents(__DIR__ . '/' . $file);
            // Remover comentários e quebras de linha extras para evitar erros no exec() simples
            // Idealmente usar um loop por statement se o SQL for complexo
            try {
                $pdo->exec($sql);
            } catch (Exception $e) {
                echo "   ! Aviso em $file: " . $e->getMessage() . " (Ignorando se for erro de tabela existente)\n";
            }
        } else {
            echo "-> Arquivo não encontrado: $file\n";
        }
    }

    echo "\n🛡️ Imortalizando Super Admin (God Mode)...\n";
    $pwd_hash = password_hash('brasallismaster', PASSWORD_DEFAULT);
    
    // 1. Criar Empresa Mestre (SaaS Owner)
    $stmt_empresa = $pdo->prepare("INSERT INTO empresas (name, ai_plan, max_users, support_level) VALUES ('Brasallis Corporate', 'enterprise_elite', 999, 'dedicated')");
    $stmt_empresa->execute();
    $master_empresa_id = $pdo->lastInsertId();

    // 2. Criar Conta Super Admin
    $stmt_sa = $pdo->prepare("INSERT INTO usuarios (empresa_id, username, password, email, user_type, plan) VALUES (?, 'Super Admin (God Mode)', ?, 'admin@brasallis.com.br', 'super_admin', 'enterprise_elite')");
    $stmt_sa->execute([$master_empresa_id, $pwd_hash]);
    
    // 3. Atualizar o dono da empresa mestre
    $sa_id = $pdo->lastInsertId();
    $pdo->exec("UPDATE empresas SET owner_user_id = {$sa_id} WHERE id = {$master_empresa_id}");

    echo "--- SETUP CONCLUÍDO COM SUCESSO! ---\n";
    echo "Super Admin: admin@brasallis.com.br / brasallismaster\n";
    echo "Acesse: http://localhost:8001\n";

} catch (Exception $e) {
    echo "\n❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    exit(1);
}

