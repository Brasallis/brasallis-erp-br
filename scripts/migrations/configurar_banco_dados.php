<?php
require_once __DIR__ . '/../../includes/db_config.php';

echo "<pre>";

try {
    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedida.\n";

    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    $conn->exec("USE " . DB_NAME);
    echo "Banco de dados '" . DB_NAME . "' selecionado.\n";

    // Desabilitar verificação de chaves estrangeiras temporariamente
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Excluir tabelas existentes (ordem inversa de dependência)
    $conn->exec("DROP TABLE IF EXISTS notificacoes;");
    $conn->exec("DROP TABLE IF EXISTS historico_estoque;");
    $conn->exec("DROP TABLE IF EXISTS venda_itens;");
    $conn->exec("DROP TABLE IF EXISTS vendas;");
    $conn->exec("DROP TABLE IF EXISTS itens_compra;");
    $conn->exec("DROP TABLE IF EXISTS dados_nota_fiscal;");
    $conn->exec("DROP TABLE IF EXISTS compras;");
    $conn->exec("DROP TABLE IF EXISTS produtos;");
    $conn->exec("DROP TABLE IF EXISTS categorias;"); // Adicionado
    $conn->exec("DROP TABLE IF EXISTS fornecedores;");
    $conn->exec("DROP TABLE IF EXISTS usuarios;");
    $conn->exec("DROP TABLE IF EXISTS empresas;");
    $conn->exec("DROP TABLE IF EXISTS redefinicoes_senha;");
    $conn->exec("DROP TABLE IF EXISTS leads;");

    // Reabilitar verificação de chaves estrangeiras
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "Tabelas existentes excluídas (se houver).\n";

    // Tabela de Empresas (DEVE SER CRIADA PRIMEIRO)
    $sql_empresas = "CREATE TABLE IF NOT EXISTS empresas (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        owner_user_id INT(11) UNSIGNED NOT NULL,
        address TEXT NULL,
        phone VARCHAR(50) NULL,
        email VARCHAR(100) NULL,
        cnpj VARCHAR(20) NULL,
        website VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    $conn->exec($sql_empresas);
    echo "Tabela 'empresas' verificada/criada com sucesso.\n";

    // Tabela de Usuários
    $sql_usuarios = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        user_type ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
        plan VARCHAR(50) NOT NULL DEFAULT 'basico',
        trial_ends_at DATETIME NULL,
        subscription_status VARCHAR(50) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    $conn->exec($sql_usuarios);
    echo "Tabela 'usuarios' verificada/criada com sucesso.\n";

    // Tabela de Fornecedores
    $sql_fornecedores = "CREATE TABLE IF NOT EXISTS fornecedores (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        contact_person VARCHAR(255),
        phone VARCHAR(50),
        email VARCHAR(100),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    $conn->exec($sql_fornecedores);
    echo "Tabela 'fornecedores' verificada/criada com sucesso.\n";

    // Tabela de Categorias (NOVA)
    $sql_categorias = "CREATE TABLE IF NOT EXISTS categorias (\n        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n        empresa_id INT(11) UNSIGNED NOT NULL,\n        nome VARCHAR(255) NOT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE\n    ) ENGINE=InnoDB;";
    $conn->exec($sql_categorias);
    echo "Tabela 'categorias' verificada/criada com sucesso.\n";

    // Tabela de Produtos (MODIFICADA)
    $sql_produtos = "CREATE TABLE IF NOT EXISTS produtos (\n        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n        empresa_id INT(11) UNSIGNED NOT NULL,\n        categoria_id INT(11) UNSIGNED NULL, -- MODIFICADO\n        fornecedor_id INT(11) UNSIGNED NULL,\n        name VARCHAR(255) NOT NULL,\n        sku VARCHAR(50) NULL,\n        description TEXT,\n        price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,\n        cost_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,\n        quantity INT(11) NOT NULL DEFAULT 0,\n        minimum_stock INT(11) NOT NULL DEFAULT 0,\n        unidade_medida VARCHAR(50) NOT NULL DEFAULT 'unidade',\n        lote VARCHAR(255) NULL,\n        validade DATE NULL,\n        observacoes TEXT NULL,\n        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,\n        FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,\n        FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL\n    ) ENGINE=InnoDB;";
    $conn->exec($sql_produtos);
    echo "Tabela 'produtos' verificada/criada com sucesso.\n";

    // Tabela de Compras
    $sql_compras = "CREATE TABLE IF NOT EXISTS compras (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        supplier_id INT(11) UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        purchase_date DATE NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        fiscal_note_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (supplier_id) REFERENCES fornecedores(id) ON DELETE RESTRICT,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB;";
    $conn->exec($sql_compras);
    echo "Tabela 'compras' verificada/criada com sucesso.\n";

    // Tabela de Itens de Compra
    $sql_itens_compra = "CREATE TABLE IF NOT EXISTS itens_compra (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        purchase_id INT(11) UNSIGNED NOT NULL,
        product_id INT(11) UNSIGNED NOT NULL,
        quantity INT(11) NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        stock_at_purchase INT(11) NULL, -- Snapshot do estoque antes da entrada
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_id) REFERENCES compras(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES produtos(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB;";
    $conn->exec($sql_itens_compra);
    echo "Tabela 'itens_compra' verificada/criada com sucesso.\n";

    // Tabela de Dados da Nota Fiscal (para IA)
    $sql_dados_nota_fiscal = "CREATE TABLE IF NOT EXISTS dados_nota_fiscal (\n        compra_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,\n        status ENUM('pendente', 'processado', 'erro') NOT NULL DEFAULT 'pendente',\n        numero_nota VARCHAR(255) NULL,\n        data_emissao DATE NULL,\n        valor_total DECIMAL(10, 2) NULL,\n        nome_fornecedor VARCHAR(255) NULL,\n        cnpj_fornecedor VARCHAR(50) NULL,\n        itens_json TEXT NULL,\n        texto_completo TEXT NULL,\n        raw_ai_response TEXT NULL, -- Coluna para depuração\n        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n        FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE\n    ) ENGINE=InnoDB;";
    $conn->exec($sql_dados_nota_fiscal);
    echo "Tabela 'dados_nota_fiscal' verificada/criada com sucesso.\n";

    // Tabela de Vendas
    $sql_vendas = "CREATE TABLE IF NOT EXISTS vendas (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'dinheiro',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB;";
    $conn->exec($sql_vendas);
    echo "Tabela 'vendas' verificada/criada com sucesso.\n";

    // Tabela de Itens da Venda
    $sql_venda_itens = "CREATE TABLE IF NOT EXISTS venda_itens (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        venda_id INT(11) UNSIGNED NOT NULL,
        product_id INT(11) UNSIGNED NOT NULL,
        quantity INT(11) NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES produtos(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB;";
    $conn->exec($sql_venda_itens);
    echo "Tabela 'venda_itens' verificada/criada com sucesso.\n";

    // Tabela de Histórico de Estoque
    $sql_historico_estoque = "CREATE TABLE IF NOT EXISTS historico_estoque (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        product_id INT(11) UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        action ENUM('entrada', 'saida', 'ajuste') NOT NULL,
        quantity INT(11) NOT NULL,
        new_quantity INT(11) NULL,
        venda_id INT(11) UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES produtos(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    $conn->exec($sql_historico_estoque);
    echo "Tabela 'historico_estoque' verificada/criada com sucesso.\n";

    // Tabela de Notificações
    $sql_notificacoes = "CREATE TABLE IF NOT EXISTS notificacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        product_id INT(11) UNSIGNED,
        is_read BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES produtos(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;";
    $conn->exec($sql_notificacoes);
    echo "Tabela 'notificacoes' verificada/criada com sucesso.\n";

    // Tabela de Leads (não pertence a uma empresa específica)
    $sql_leads = "CREATE TABLE IF NOT EXISTS leads (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        company_name VARCHAR(255) NULL,
        challenge TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    $conn->exec($sql_leads);
    echo "Tabela 'leads' verificada/criada com sucesso.\n";
    
    // Tabela de Reset de Senha (não pertence a uma empresa específica)
    $sql_redefinicoes_senha = "CREATE TABLE IF NOT EXISTS redefinicoes_senha (
        email VARCHAR(100) NOT NULL PRIMARY KEY,
        code VARCHAR(6) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    $conn->exec($sql_redefinicoes_senha);
    echo "Tabela 'redefinicoes_senha' verificada/criada com sucesso.\n";

    echo "\n\nConfiguração do banco de dados multi-tenant concluída com sucesso!";

} catch (PDOException $e) {
    echo "\nERRO: " . $e->getMessage();
}

echo "</pre>";

?>
