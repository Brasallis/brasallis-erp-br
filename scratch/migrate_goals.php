<?php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando migração financeira completa...\n";
    
    // 1. Criar Tabelas Base se não existirem
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `contas_pagar` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `empresa_id` int(11) NOT NULL,
            `descricao` varchar(255) NOT NULL,
            `valor` decimal(10,2) NOT NULL,
            `data_vencimento` date NOT NULL,
            `data_pagamento` date DEFAULT NULL,
            `status` enum('pendente','pago','atrasado','cancelado') DEFAULT 'pendente',
            `fornecedor_id` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "- Tabela contas_pagar OK\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `contas_receber` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `empresa_id` int(11) NOT NULL,
            `descricao` varchar(255) NOT NULL,
            `valor` decimal(10,2) NOT NULL,
            `data_vencimento` date NOT NULL,
            `data_recebimento` date DEFAULT NULL,
            `status` enum('pendente','recebido','atrasado','cancelado') DEFAULT 'pendente',
            `cliente_id` int(11) DEFAULT NULL,
            `venda_id` int(11) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "- Tabela contas_receber OK\n";

    // 2. Adicionar colunas de metas se não existirem
    function addColumnIfMissing($pdo, $table, $column, $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            return true;
        }
        return false;
    }

    addColumnIfMissing($pdo, 'empresas', 'monthly_revenue_goal', "DECIMAL(15,2) DEFAULT 0.00");
    addColumnIfMissing($pdo, 'empresas', 'fixed_costs', "DECIMAL(15,2) DEFAULT 0.00");
    echo "- Colunas de metas OK\n";

    echo "\nMigração concluída com sucesso! OK!\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
