<?php
require_once __DIR__ . '/includes/manutencao_guard.php';
require_once __DIR__ . '/bootstrap.php';
check_master_key();
try {
    $conn = connect_db();
    $conn->exec("ALTER TABLE pagamentos ADD COLUMN IF NOT EXISTS checkout_url TEXT AFTER plan_type");
    $conn->exec("ALTER TABLE pagamentos MODIFY COLUMN plan_type VARCHAR(50)");
    echo "<h1>Sucesso! Banco de dados atualizado.</h1><p>Pode voltar e clicar em Vincular Cartão.</p>";
} catch (Exception $e) {
    echo "<h1>Erro: " . $e->getMessage() . "</h1>";
}

