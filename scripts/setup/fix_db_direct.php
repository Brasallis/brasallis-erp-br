<?php
// scripts/setup/fix_db_direct.php

// Tenta conectar com credenciais padrão do XAMPP/WAMP
$host = 'localhost';
$user = 'root';
$pass = ''; // Senha vazia é padrão no XAMPP
$dbname = 'gerenciador_estoque';

echo "--- Iniciando Correcao Forcada do BD ---\n";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Cria o banco se não existir (garantia extra)
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");
    echo "Conectado ao banco '$dbname'.\n";
    
    // SQL Direto (Cópia de 001_create_pyramid_architecture.sql)
    $sql = "
    -- 1. MODULOS
    CREATE TABLE IF NOT EXISTS modulos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        slug VARCHAR(50) NOT NULL UNIQUE,
        icone VARCHAR(50),
        descricao TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 2. SETORES
    CREATE TABLE IF NOT EXISTS setores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        empresa_id INT NOT NULL,
        nome VARCHAR(100) NOT NULL,
        cor_hex VARCHAR(7) DEFAULT '#6c757d',
        responsavel_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 3. PERMISSOES_SETOR
    CREATE TABLE IF NOT EXISTS permissoes_setor (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setor_id INT NOT NULL,
        modulo_id INT NOT NULL,
        nivel_acesso ENUM('leitura', 'escrita', 'admin') DEFAULT 'leitura',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
        FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 4. CARGOS
    CREATE TABLE IF NOT EXISTS cargos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setor_id INT NOT NULL,
        nome VARCHAR(100) NOT NULL,
        nivel_hierarquia INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 5. USUARIO_SETOR_VINCULO
    CREATE TABLE IF NOT EXISTS usuario_setor (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        setor_id INT NOT NULL,
        cargo_id INT DEFAULT NULL,
        is_chefe BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
        FOREIGN KEY (cargo_id) REFERENCES cargos(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- SEED: INITIAL MODULES
    INSERT IGNORE INTO modulos (nome, slug, icone, descricao) VALUES
    ('Estoque Inteligente', 'estoque', 'fas fa-boxes', 'Gestão de produtos, movimentações e inventário.'),
    ('Gestão Financeira', 'financeiro', 'fas fa-chart-line', 'Fluxo de caixa, DRE gerencial e contas.'),
    ('Recursos Humanos', 'rh', 'fas fa-users', 'Gestão de colaboradores, folhas e benefícios.'),
    ('CRM & Vendas', 'crm', 'fas fa-handshake', 'Gestão de clientes, leads e funil de vendas.'),
    ('Fiscal & Tributário', 'fiscal', 'fas fa-file-invoice-dollar', 'Emissão de NF-e e inteligência tributária.');
    ";

    $pdo->exec($sql);
    echo "Tabelas criadas e Módulos populados com sucesso!\n";

    // Opcional: Criar Setores Padrão para empresas existentes
    $stmt = $pdo->query("SELECT id FROM empresas");
    $companies = $stmt->fetchAll(PDO::FETCH_Column);
    
    foreach ($companies as $empId) {
        // Verifica se tem setor Adm
        $check = $pdo->prepare("SELECT id FROM setores WHERE empresa_id = ? AND nome = 'Administração'");
        $check->execute([$empId]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO setores (empresa_id, nome, cor_hex) VALUES (?, 'Administração', '#0A2647')")->execute([$empId]);
            echo "Setor 'Administração' criado para empresa $empId.\n";
        }
    }
    
    echo "--- Fim do Script ---\n";

} catch (PDOException $e) {
    die("Erro Fatal: " . $e->getMessage() . "\n");
}
?>
