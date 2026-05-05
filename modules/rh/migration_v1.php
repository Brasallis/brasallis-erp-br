<?php
// modules/rh/migration_v1.php
require_once __DIR__ . '/../../includes/db_config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando migração do Módulo RH...\n";

    // 1. Tabela Detalhes do Funcionário (Extensão de Usuarios)
    $sql1 = "CREATE TABLE IF NOT EXISTS rh_funcionarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        user_id INT NOT NULL,
        cargo_id INT NULL,
        salario_base DECIMAL(10,2) DEFAULT 0.00,
        data_admissao DATE NULL,
        cpf VARCHAR(14) NULL,
        pis VARCHAR(14) NULL,
        endereco TEXT NULL,
        telefone VARCHAR(20) NULL,
        status ENUM('ativo', 'ferias', 'desligado') DEFAULT 'ativo',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql1);
    echo "- Tabela rh_funcionarios verificada.\n";

    // 2. Tabela Folha de Pagamento
    $sql2 = "CREATE TABLE IF NOT EXISTS rh_folha_pagamento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        funcionario_id INT NOT NULL,
        mes_referencia DATE NOT NULL,
        salario_base DECIMAL(10,2) NOT NULL,
        proventos DECIMAL(10,2) DEFAULT 0.00,
        descontos DECIMAL(10,2) DEFAULT 0.00,
        liquido DECIMAL(10,2) NOT NULL,
        detalhes_json JSON NULL,
        status ENUM('rascunho', 'fechado', 'pago') DEFAULT 'rascunho',
        data_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (funcionario_id) REFERENCES rh_funcionarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql2);
    echo "- Tabela rh_folha_pagamento verificada.\n";

    // 3. Tabela Ponto (Timekeeping)
    $sql3 = "CREATE TABLE IF NOT EXISTS rh_ponto (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        user_id INT NOT NULL,
        data_registro DATE NOT NULL,
        entrada_1 TIME NULL,
        saida_1 TIME NULL,
        entrada_2 TIME NULL,
        saida_2 TIME NULL,
        observacao TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        UNIQUE KEY unique_ponto_dia (user_id, data_registro)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql3);
    echo "- Tabela rh_ponto verificada.\n";

    echo "Migração RH concluída com sucesso!\n";

} catch (PDOException $e) {
    die("Erro na migração: " . $e->getMessage());
}
?>
