<?php
require_once __DIR__ . '/includes/db_config.php';

try {
    $port = defined('DB_PORT') ? DB_PORT : 3306;
    $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>🔥 Reparo Mestre de Tabelas e Colunas (Tenant)</h1>";

    // 1. Tabela de Clientes
    $sql_clientes = "CREATE TABLE IF NOT EXISTS clientes (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        nome VARCHAR(150) NOT NULL,
        tipo ENUM('PF', 'PJ') DEFAULT 'PF',
        cpf_cnpj VARCHAR(20),
        email VARCHAR(100),
        telefone VARCHAR(20),
        endereco TEXT,
        cidade VARCHAR(100),
        estado VARCHAR(2),
        status ENUM('ativo', 'inativo') DEFAULT 'ativo',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql_clientes);
    echo "<p style='color:green;'>Tabela 'clientes' verificada/criada.</p>";

    // 2. Reparar Vendas
    $vendas_cols = [
        "cliente_id" => "INT(11) UNSIGNED NULL AFTER user_id",
        "discount_amount" => "DECIMAL(10, 2) DEFAULT 0.00 AFTER total_amount"
    ];

    foreach ($vendas_cols as $col => $def) {
        $check = $pdo->query("SHOW COLUMNS FROM vendas LIKE '$col'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE vendas ADD COLUMN $col $def");
            echo "<p style='color:green;'>Coluna '$col' adicionada em 'vendas'.</p>";
        }
    }
    
    // FK de cliente em vendas se não existir
    try {
        $pdo->exec("ALTER TABLE vendas ADD CONSTRAINT fk_vendas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL");
        echo "<p style='color:green;'>Chave estrangeira 'cliente_id' vinculada.</p>";
    } catch(Exception $e) {}

    // 3. Tabela venda_pagamentos
    $sql_venda_pagamentos = "CREATE TABLE IF NOT EXISTS venda_pagamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venda_id INT(11) UNSIGNED NOT NULL,
        metodo_pagamento VARCHAR(50) NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        data_pagamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_venda_pagamentos);
    echo "<p style='color:green;'>Tabela 'venda_pagamentos' verificada/criada.</p>";

    // 4. Tax Rules
    $sql_tax_rules = "CREATE TABLE IF NOT EXISTS tax_rules (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ncm VARCHAR(20) NOT NULL,
        cest VARCHAR(20) NULL,
        type ENUM('monofasico', 'substituicao_tributaria', 'isento', 'tributado') NOT NULL DEFAULT 'tributado',
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ncm (ncm)
    ) ENGINE=InnoDB;";
    $pdo->exec($sql_tax_rules);
    echo "<p style='color:green;'>Tabela 'tax_rules' verificada/criada.</p>";

    // 5. Analise Tributaria
    $sql_analise_tributaria = "CREATE TABLE IF NOT EXISTS analise_tributaria (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        compra_id INT(11) UNSIGNED NOT NULL,
        product_id INT(11) UNSIGNED NULL,
        item_name_xml VARCHAR(255) NULL,
        ncm_detectado VARCHAR(20) NULL,
        cfop_entrada VARCHAR(10) NULL,
        cst_csosn_entrada VARCHAR(10) NULL,
        alert_level ENUM('info', 'warning', 'critical', 'ok') NOT NULL DEFAULT 'info',
        ai_suggestion TEXT NULL,
        savings_potential DECIMAL(10, 2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES produtos(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;";
    $pdo->exec($sql_analise_tributaria);
    echo "<p style='color:green;'>Tabela 'analise_tributaria' verificada/criada.</p>";

    // 6. Fiscal Notas
    $sql_fiscal_notas = "CREATE TABLE IF NOT EXISTS fiscal_notas (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        numero VARCHAR(20) NOT NULL,
        serie VARCHAR(5),
        tipo ENUM('entrada', 'saida') NOT NULL,
        modelo ENUM('nfe', 'nfse', 'cte', 'cupom') DEFAULT 'nfe',
        chave_acesso VARCHAR(44),
        emitente_destinatario VARCHAR(150),
        cpf_cnpj VARCHAR(20),
        data_emissao DATE NOT NULL,
        valor_total DECIMAL(10, 2) DEFAULT 0.00,
        valor_impostos DECIMAL(10, 2) DEFAULT 0.00,
        status ENUM('autorizada', 'cancelada', 'denegada', 'rascunho') DEFAULT 'rascunho',
        xml_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    $pdo->exec($sql_fiscal_notas);
    echo "<p style='color:green;'>Tabela 'fiscal_notas' verificada/criada.</p>";

    echo "<h2>🎉 Todas as tabelas de suporte ao PDV, CRM e Fiscal foram reparadas!</h2>";

} catch (Exception $e) {
    echo "<h1>❌ Erro Crítico</h1><p>" . $e->getMessage() . "</p>";
}

@unlink(__FILE__);
?>
