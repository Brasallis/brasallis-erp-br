-- scripts/migrations/004_create_fiscal_tables.sql
-- Módulo Fiscal: Notas Fiscais e Configuração de Impostos

CREATE TABLE IF NOT EXISTS fiscal_notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    numero VARCHAR(20) NOT NULL,
    serie VARCHAR(5),
    tipo ENUM('entrada', 'saida') NOT NULL, -- Entrada (Compra) ou Saída (Venda)
    modelo ENUM('nfe', 'nfse', 'cte', 'cupom') DEFAULT 'nfe',
    chave_acesso VARCHAR(44), -- Chave da NFe
    emitente_destinatario VARCHAR(150), -- Nome do Cliente ou Fornecedor
    cpf_cnpj VARCHAR(20),
    data_emissao DATE NOT NULL,
    valor_total DECIMAL(10, 2) DEFAULT 0.00,
    valor_impostos DECIMAL(10, 2) DEFAULT 0.00, -- Soma de ICMS, IPI, ISS, etc.
    status ENUM('autorizada', 'cancelada', 'denegada', 'rascunho') DEFAULT 'rascunho',
    xml_path VARCHAR(255), -- Caminho par salvar XML se tiver upload
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (empresa_id),
    INDEX (data_emissao),
    INDEX (chave_acesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fiscal_impostos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL, -- Ex: ICMS, ISS
    aliquota_padrao DECIMAL(5, 2) DEFAULT 0.00, -- %
    descricao VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed inicial de impostos comuns
INSERT INTO fiscal_impostos (empresa_id, nome, aliquota_padrao, descricao) VALUES 
(1, 'ICMS', 18.00, 'Imposto sobre Circulação de Mercadorias'),
(1, 'ISS', 5.00, 'Imposto Sobre Serviços'),
(1, 'IPI', 0.00, 'Imposto sobre Produtos Industrializados'),
(1, 'PIS', 1.65, 'Programa de Integração Social'),
(1, 'COFINS', 7.60, 'Contribuição para Financiamento da Seguridade Social');
