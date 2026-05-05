-- scripts/migrations/003_create_financial_tables.sql
-- Módulo Financeiro: Contas a Pagar, Receber e Categorias

CREATE TABLE IF NOT EXISTS fin_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    cor_hex VARCHAR(7) DEFAULT '#6c757d',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fin_movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    categoria_id INT,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    status ENUM('pendente', 'pago', 'atrasado', 'cancelado') DEFAULT 'pendente',
    obs TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES fin_categorias(id) ON DELETE SET NULL,
    INDEX (empresa_id),
    INDEX (data_vencimento),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed inicial de categorias
INSERT INTO fin_categorias (empresa_id, nome, tipo, cor_hex) VALUES 
(1, 'Vendas de Produtos', 'receita', '#28a745'),
(1, 'Serviços Prestados', 'receita', '#20c997'),
(1, 'Salários', 'despesa', '#dc3545'),
(1, 'Aluguel', 'despesa', '#fd7e14'),
(1, 'Fornecedores', 'despesa', '#6f42c1'),
(1, 'Impostos', 'despesa', '#343a40');
