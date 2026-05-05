-- scripts/migrations/005_create_api_structure.sql
-- Estrutura para API RESTful e Otimização de Performance

-- 1. Tabela de Chaves de API
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE, -- Hash SHA-256 ou similar
    descricao VARCHAR(100), -- Ex: "Integração Site", "App Mobile"
    permissions JSON, -- Escopo: ["crm:read", "crm:write"]
    is_active BOOLEAN DEFAULT TRUE,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (api_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Índices de Performance para Grandes Volumes (CRM)
-- Evita Full Table Scans em buscas comuns

-- Clientes: Busca por nome e documento são as mais comuns
CREATE INDEX idx_clientes_nome ON clientes(nome);
CREATE INDEX idx_clientes_cpf_cnpj ON clientes(cpf_cnpj);
CREATE INDEX idx_clientes_email ON clientes(email);

-- Oportunidades: Filtros de funil e status
CREATE INDEX idx_crm_ops_status ON crm_oportunidades(status);
CREATE INDEX idx_crm_ops_etapa ON crm_oportunidades(etapa_id);

-- Fiscal: Buscas por período
CREATE INDEX idx_fiscal_notas_periodo ON fiscal_notas(data_emissao);

-- 3. Webhook Logs (Opcional, para debug de integrações)
CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT,
    endpoint VARCHAR(100),
    method VARCHAR(10),
    request_body TEXT,
    response_code INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: Criar uma chave de API de teste para a Empresa 1
INSERT INTO api_keys (empresa_id, api_key, descricao, permissions) 
SELECT 1, 'sk_test_1234567890abcdef', 'Chave de Teste - Desenvolvimento', '["crm:read", "crm:write", "fiscal:read"]'
WHERE NOT EXISTS (SELECT 1 FROM api_keys WHERE api_key = 'sk_test_1234567890abcdef');
