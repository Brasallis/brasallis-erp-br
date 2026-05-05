-- MIGRATION: 001_create_pyramid_architecture.sql
-- Goal: Transform flat user system into Company > Sector > Module hierarchy

-- 1. MODULOS (Global System Modules)
-- Defines the available "lego blocks" of the ERP
CREATE TABLE IF NOT EXISTS modulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE, -- ex: 'financeiro', 'estoque', 'rh'
    icone VARCHAR(50), -- FontAwesome class
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. SETORES (Departments within a Company)
-- The building blocks of the organization
CREATE TABLE IF NOT EXISTS setores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL, -- ex: 'Marketing', 'Logística'
    cor_hex VARCHAR(7) DEFAULT '#6c757d',
    responsavel_id INT DEFAULT NULL, -- User ID (Manager)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PERMISSOES_SETOR (Access Control)
-- Which modules a sector can access
CREATE TABLE IF NOT EXISTS permissoes_setor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setor_id INT NOT NULL,
    modulo_id INT NOT NULL,
    nivel_acesso ENUM('leitura', 'escrita', 'admin') DEFAULT 'leitura',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE,
    FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CARGOS (Roles within a Sector)
-- Granular roles beyond just "Employee"
CREATE TABLE IF NOT EXISTS cargos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setor_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL, -- ex: 'Analista Jr', 'Gerente'
    nivel_hierarquia INT DEFAULT 1, -- 1=Base, 10=Chefe
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. USUARIO_SETOR_VINCULO (User Assignment)
-- Assigns users to sectors/roles (replaces basic 'tipo' eventually)
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
