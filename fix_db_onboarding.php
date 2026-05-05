<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
require_once 'bootstrap.php';
check_master_key();

require_once 'includes/db_config.php';
require_once 'includes/funcoes.php';

try {
    $conn = connect_db();
    
    // Adiciona coluna de segmento e flag de onboarding
    $conn->exec("ALTER TABLE empresas ADD COLUMN IF NOT EXISTS segmento VARCHAR(100) DEFAULT NULL AFTER ai_plan");
    $conn->exec("ALTER TABLE empresas ADD COLUMN IF NOT EXISTS onboarding_completed TINYINT(1) DEFAULT 0 AFTER segmento");
    
    echo "<h1>Sucesso!</h1><p>Banco de dados preparado para o Onboarding.</p>";
} catch (Exception $e) {
    echo "<h1>Erro: " . $e->getMessage() . "</h1>";
}

