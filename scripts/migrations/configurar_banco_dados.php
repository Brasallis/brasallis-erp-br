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
    // Tabelas de Migrações (Novas)
    $conn->exec("DROP TABLE IF EXISTS ai_agent_logs;");
    $conn->exec("DROP TABLE IF EXISTS ai_agents;");
    $conn->exec("DROP TABLE IF EXISTS api_logs;");
    $conn->exec("DROP TABLE IF EXISTS api_keys;");
    $conn->exec("DROP TABLE IF EXISTS fiscal_impostos;");
    $conn->exec("DROP TABLE IF EXISTS fiscal_notas;");
    $conn->exec("DROP TABLE IF EXISTS fin_movimentacoes;");
    $conn->exec("DROP TABLE IF EXISTS fin_categorias;");
    $conn->exec("DROP TABLE IF EXISTS crm_oportunidades;");
    $conn->exec("DROP TABLE IF EXISTS crm_etapas;");
    $conn->exec("DROP TABLE IF EXISTS clientes;");
    $conn->exec("DROP TABLE IF EXISTS usuario_setor;");
    $conn->exec("DROP TABLE IF EXISTS cargos;");
    $conn->exec("DROP TABLE IF EXISTS permissoes_setor;");
    $conn->exec("DROP TABLE IF EXISTS setores;");
    $conn->exec("DROP TABLE IF EXISTS modulos;");

    // Reabilitar verificação de chaves estrangeiras
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "Tabelas existentes excluídas (se houver).\n";

    // Tabela de Empresas (padronizada SaaS)
    $sql_empresas = "CREATE TABLE IF NOT EXISTS empresas (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        owner_user_id INT(11) UNSIGNED DEFAULT 0,
        address TEXT NULL,
        phone VARCHAR(50) NULL,
        email VARCHAR(100) NULL,
        cnpj VARCHAR(20) NULL,
        website VARCHAR(255) NULL,
        segmento VARCHAR(100) NULL,
        ai_plan VARCHAR(50) DEFAULT 'foundation',
        ai_token_limit INT DEFAULT 100000,
        ai_tokens_used_month INT DEFAULT 0,
        iq_actions_used_month INT DEFAULT 0,
        max_users INT DEFAULT 5,
        support_level VARCHAR(50) DEFAULT 'community',
        subscription_status VARCHAR(50) DEFAULT 'trial',
        onboarding_completed TINYINT(1) DEFAULT 0,
        branding_primary_color VARCHAR(20) DEFAULT '#1e3a8a',
        branding_secondary_color VARCHAR(20) DEFAULT '#3b82f6',
        branding_bg_style VARCHAR(50) DEFAULT 'modern_light',
        active_modules JSON DEFAULT NULL,
        last_payment_at DATETIME DEFAULT NULL,
        next_billing_at DATETIME DEFAULT NULL,
        blocked_at DATETIME DEFAULT NULL,
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
        user_type ENUM('admin', 'employee', 'super_admin') NOT NULL DEFAULT 'employee',
        plan VARCHAR(50) NOT NULL DEFAULT 'basico',
        trial_ends_at DATETIME NULL,
        subscription_status VARCHAR(50) NOT NULL DEFAULT 'active',
        permissions JSON DEFAULT NULL,
        data_admissao DATE DEFAULT NULL,
        cpf VARCHAR(14) DEFAULT NULL,
        celular VARCHAR(20) DEFAULT NULL,
        status_colaborador ENUM('ativo', 'inativo', 'ferias', 'afastado') DEFAULT 'ativo',
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
    $sql_produtos = "CREATE TABLE IF NOT EXISTS produtos (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        categoria_id INT(11) UNSIGNED NULL, -- MODIFICADO
        fornecedor_id INT(11) UNSIGNED NULL,
        name VARCHAR(255) NOT NULL,
        sku VARCHAR(50) NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        cost_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        quantity INT(11) NOT NULL DEFAULT 0,
        minimum_stock INT(11) NOT NULL DEFAULT 0,
        unidade_medida VARCHAR(50) NOT NULL DEFAULT 'unidade',
        lote VARCHAR(255) NULL,
        validade DATE NULL,
        observacoes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
        FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;";
    $conn->exec($sql_produtos);
    echo "Tabela 'produtos' verificada/criada com sucesso.\n";

    // Tabela de Lotes (NOVA/RESTAURADA)
    $sql_lotes = "CREATE TABLE IF NOT EXISTS lotes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produto_id INT(11) UNSIGNED NOT NULL,
        numero_lote VARCHAR(50) NOT NULL,
        data_validade DATE DEFAULT NULL,
        quantidade_inicial INT NOT NULL,
        quantidade_atual INT NOT NULL,
        fornecedor VARCHAR(100) DEFAULT NULL,
        data_entrada DATETIME DEFAULT CURRENT_TIMESTAMP,
        empresa_id INT(11) UNSIGNED NOT NULL,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql_lotes);
    echo "Tabela 'lotes' verificada/criada com sucesso.\n";

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

    // Tabela de Clientes (Base CRM/PDV)
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
    $conn->exec($sql_clientes);
    echo "Tabela 'clientes' verificada/criada com sucesso.\n";

    // Tabela de Vendas (MODIFICADA)
    $sql_vendas = "CREATE TABLE IF NOT EXISTS vendas (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        cliente_id INT(11) UNSIGNED NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        discount_amount DECIMAL(10, 2) DEFAULT 0.00,
        payment_method VARCHAR(50) DEFAULT 'múltiplos',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
        FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;";
    $conn->exec($sql_vendas);
    echo "Tabela 'vendas' verificada/atualizada com sucesso.\n";

    // Tabela de Pagamentos de Venda (Suporte a múltiplos métodos)
    $sql_venda_pagamentos = "CREATE TABLE IF NOT EXISTS venda_pagamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        venda_id INT(11) UNSIGNED NOT NULL,
        metodo_pagamento VARCHAR(50) NOT NULL,
        valor DECIMAL(10,2) NOT NULL,
        data_pagamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql_venda_pagamentos);
    echo "Tabela 'venda_pagamentos' verificada/criada com sucesso.\n";

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

    // Tabela de Regras Fiscais (Tax Engine)
    $sql_tax_rules = "CREATE TABLE IF NOT EXISTS tax_rules (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ncm VARCHAR(20) NOT NULL,
        cest VARCHAR(20) NULL,
        type ENUM('monofasico', 'substituicao_tributaria', 'isento', 'tributado') NOT NULL DEFAULT 'tributado',
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ncm (ncm)
    ) ENGINE=InnoDB;";
    $conn->exec($sql_tax_rules);
    echo "Tabela 'tax_rules' verificada/criada com sucesso.\n";

    // Tabela de Análise Tributária
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
    $conn->exec($sql_analise_tributaria);
    echo "Tabela 'analise_tributaria' verificada/criada com sucesso.\n";

    // Tabela de Notas Fiscais (Fiscal)
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
    $conn->exec($sql_fiscal_notas);
    echo "Tabela 'fiscal_notas' verificada/criada com sucesso.\n";

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
        details TEXT NULL,
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

    // Tabela de Logs do Sistema (Cloud Logging)
    $sql_system_logs = "CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NULL,
        user_id INT(11) UNSIGNED NULL,
        severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
        source VARCHAR(100) DEFAULT 'System',
        message TEXT NOT NULL,
        stack_trace TEXT NULL,
        url VARCHAR(255) NULL,
        ip_address VARCHAR(45) NULL,
        status ENUM('new', 'resolved', 'ignored') DEFAULT 'new',
        resolved_at TIMESTAMP NULL,
        resolved_by INT(11) UNSIGNED NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    $conn->exec($sql_system_logs);
    echo "Tabela 'system_logs' verificada/criada com sucesso.\n";

    // Tabela de Chamados de Suporte (Helpdesk SaaS)
    $sql_suporte = "CREATE TABLE IF NOT EXISTS chamados_suporte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT(11) UNSIGNED NOT NULL,
        assunto VARCHAR(255) NOT NULL,
        mensagem TEXT NOT NULL,
        resposta TEXT NULL,
        status ENUM('aberto', 'respondido', 'fechado') DEFAULT 'aberto',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    $conn->exec($sql_suporte);
    echo "Tabela 'chamados_suporte' verificada/criada com sucesso.\n";

    // Tabela de Avisos Globais (SuperAdmin)
    $sql_avisos_globais = "CREATE TABLE IF NOT EXISTS avisos_globais (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'info',
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    $conn->exec($sql_avisos_globais);
    echo "Tabela 'avisos_globais' verificada/criada com sucesso.\n";

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
