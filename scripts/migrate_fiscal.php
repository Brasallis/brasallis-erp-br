<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/funcoes.php';

echo "Iniciando migração do módulo Fiscal...\n";

try {
    $conn = connect_db();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->exec("
        CREATE TABLE IF NOT EXISTS `fiscal_notas` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `empresa_id` int(11) NOT NULL,
            `tipo` enum('entrada','saida') NOT NULL DEFAULT 'saida',
            `numero` varchar(50) NOT NULL,
            `emitente_destinatario` varchar(255) NOT NULL,
            `data_emissao` date NOT NULL,
            `valor_total` decimal(10,2) NOT NULL DEFAULT '0.00',
            `valor_impostos` decimal(10,2) NOT NULL DEFAULT '0.00',
            `status` enum('autorizada','cancelada','pendente') NOT NULL DEFAULT 'pendente',
            `chave_acesso` varchar(100) DEFAULT NULL,
            `xml_caminho` varchar(255) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela fiscal_notas verificada/criada com sucesso.\n";

} catch (PDOException $e) {
    die("Erro na migração Fiscal: " . $e->getMessage() . "\n");
}
