<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/funcoes.php';

echo "Iniciando migração do módulo de RH...\n";

try {
    $conn = connect_db();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Tabela Ponto (Time Tracking)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS `rh_ponto` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `empresa_id` int(11) NOT NULL,
            `usuario_id` int(11) NOT NULL,
            `data_registro` date NOT NULL,
            `hora_entrada` time DEFAULT NULL,
            `hora_saida_pausa` time DEFAULT NULL,
            `hora_retorno_pausa` time DEFAULT NULL,
            `hora_saida` time DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_dia_usuario` (`usuario_id`, `data_registro`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Tabela rh_ponto criada com sucesso.\n";

} catch (PDOException $e) {
    die("Erro na migração RH: " . $e->getMessage() . "\n");
}
