<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
require_once 'bootstrap.php';
check_master_key();

require_once 'includes/db_config.php';
require_once 'includes/funcoes.php';

try {
    $conn = connect_db();
    
    // Adiciona coluna para armazenar os módulos ativos (JSON)
    $conn->exec("ALTER TABLE empresas ADD COLUMN IF NOT EXISTS active_modules TEXT DEFAULT NULL AFTER onboarding_completed");
    
    echo "<h1>Sucesso!</h1><p>Estrutura de módulos dinâmicos preparada.</p>";
} catch (Exception $e) {
    echo "<h1>Erro: " . $e->getMessage() . "</h1>";
}

