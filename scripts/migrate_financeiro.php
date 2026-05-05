<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/funcoes.php';

echo "Iniciando migração do módulo financeiro...\n";

try {
    $conn = connect_db();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Tabela Contas a Pagar
    $conn->exec("
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
    echo "Tabela contas_pagar verificada/criada com sucesso.\n";

    // 2. Tabela Contas a Receber
    $conn->exec("
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
    echo "Tabela contas_receber verificada/criada com sucesso.\n";

    // Check if the tables actually exist now
    $stmt = $conn->query("SHOW TABLES LIKE 'contas_pagar'");
    if ($stmt->rowCount() > 0) {
        echo "Tudo certo! Tabelas criadas.\n";
    }

} catch (PDOException $e) {
    die("Erro na migração: " . $e->getMessage() . "\n");
}
