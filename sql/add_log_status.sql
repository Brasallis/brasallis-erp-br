-- Adicionar colunas de controle de resolução na tabela system_logs
ALTER TABLE system_logs 
ADD COLUMN IF NOT EXISTS status ENUM('new', 'resolved') DEFAULT 'new',
ADD COLUMN IF NOT EXISTS resolved_at DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS resolved_by INT DEFAULT NULL,
ADD CONSTRAINT fk_logs_resolved_by FOREIGN KEY (resolved_by) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Criar um índice para facilitar a contagem de logs novos
CREATE INDEX idx_logs_status ON system_logs(status);
