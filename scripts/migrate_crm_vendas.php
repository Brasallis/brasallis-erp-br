<?php
// scripts/migrate_crm_vendas.php
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/funcoes.php';

$conn = connect_db();
if (!$conn) { die("Erro de conexão.\n"); }

echo "Iniciando migração de CRM e Vendas dentro do Docker...\n";

try {
    // 1. Tabela de Clientes (se não existir, embora já deva existir)
    $conn->exec("CREATE TABLE IF NOT EXISTS `clientes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `empresa_id` int(11) NOT NULL,
        `nome` varchar(150) NOT NULL,
        `tipo` enum('PF','PJ') DEFAULT 'PF',
        `cpf_cnpj` varchar(20) DEFAULT NULL,
        `email` varchar(100) DEFAULT NULL,
        `telefone` varchar(20) DEFAULT NULL,
        `status` enum('ativo','inativo') DEFAULT 'ativo',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Tabela de Vendas
    $conn->exec("CREATE TABLE IF NOT EXISTS `vendas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `empresa_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `total_amount` decimal(10,2) NOT NULL,
        `payment_method` varchar(50) DEFAULT 'dinheiro',
        `status_pagamento` enum('pendente','pago','cancelado') DEFAULT 'pago',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Tabela de Itens de Venda
    $conn->exec("CREATE TABLE IF NOT EXISTS `venda_itens` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `venda_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL,
        `unit_price` decimal(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_venda` (`venda_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Tabelas de CRM
    $conn->exec("CREATE TABLE IF NOT EXISTS `crm_oportunidades` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `empresa_id` int(11) NOT NULL,
        `cliente_id` int(11) NOT NULL,
        `titulo` varchar(255) NOT NULL,
        `valor_estimado` decimal(10,2) NOT NULL DEFAULT 0.00,
        `status` enum('lead','negociacao','ganho','perdido') DEFAULT 'lead',
        `responsavel_id` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Migração concluída com sucesso!\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
