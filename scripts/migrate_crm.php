<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/funcoes.php';

echo "Iniciando migração do módulo de CRM...\n";

try {
    $conn = connect_db();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->exec("
        CREATE TABLE IF NOT EXISTS `crm_oportunidades` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `empresa_id` int(11) NOT NULL,
            `cliente_id` int(11) NOT NULL,
            `titulo` varchar(255) NOT NULL,
            `valor_estimado` decimal(10,2) DEFAULT '0.00',
            `status` enum('lead','negociacao','ganho','perdido') DEFAULT 'lead',
            `responsavel_id` int(11) DEFAULT NULL,
            `data_fechamento_esperada` date DEFAULT NULL,
            `observacoes` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela crm_oportunidades verificada/criada com sucesso.\n";

} catch (PDOException $e) {
    die("Erro na migração CRM: " . $e->getMessage() . "\n");
}
