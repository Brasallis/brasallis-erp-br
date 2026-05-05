-- Atualizar a tabela de pagamentos para os novos planos
ALTER TABLE pagamentos 
MODIFY COLUMN plan_type ENUM('foundation', 'vision', 'enterprise_elite') NOT NULL;

-- Adicionar campo para URL de checkout se não existir
ALTER TABLE pagamentos ADD COLUMN IF NOT EXISTS checkout_url TEXT AFTER qr_code_base64;
