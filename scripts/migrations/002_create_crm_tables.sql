-- MIGRATION: 002_create_crm_tables.sql
-- Module: CRM & Vendas

-- 1. CLIENTES (Base de Contatos)
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CRM ETAPAS (Configuração do Funil)
CREATE TABLE IF NOT EXISTS crm_etapas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL, -- Ex: Prospecção, Qualificação, Proposta, Negociação, Fechado
    ordem INT NOT NULL DEFAULT 1,
    cor_hex VARCHAR(7) DEFAULT '#e9ecef',
    is_final BOOLEAN DEFAULT FALSE, -- Se é uma etapa de conclusão (Ganho/Perdido)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CRM OPORTUNIDADES (Deals / Leads)
CREATE TABLE IF NOT EXISTS crm_oportunidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    cliente_id INT, -- Pode ser null se for um lead sem cadastro completo ainda
    titulo VARCHAR(100) NOT NULL, -- Ex: "Venda de 50 Notebooks"
    valor_estimado DECIMAL(10, 2) DEFAULT 0.00,
    etapa_id INT NOT NULL,
    responsavel_id INT, -- Usuário responsável
    origem VARCHAR(50), -- Ex: Indicação, Site, Ads
    status ENUM('aberto', 'ganho', 'perdido') DEFAULT 'aberto',
    data_fechamento DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (etapa_id) REFERENCES crm_etapas(id),
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEED: Etapas Padrão para novas empresas (será rodado na criação, mas aqui garantimos para as existentes)
-- Nota: Isso requer lógica de aplicação para popular para cada empresa existente, 
-- mas aqui podemos inserir um seed genérico se quisermos, ou deixar vazio.
-- Vamos deixar vazio pois depende do ID da empresa. O setup inicial deve criar isso.
