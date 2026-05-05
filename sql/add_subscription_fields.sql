-- 1. Atualizar o ENUM de planos e adicionar novas colunas de controle de assinatura
ALTER TABLE empresas 
MODIFY COLUMN ai_plan ENUM('foundation', 'vision', 'enterprise_elite', 'free', 'growth', 'enterprise') DEFAULT 'foundation',
ADD COLUMN IF NOT EXISTS subscription_status ENUM('active', 'trial', 'overdue', 'blocked') DEFAULT 'trial',
ADD COLUMN IF NOT EXISTS last_payment_at DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS next_billing_at DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS blocked_at DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS iq_actions_used_month INT DEFAULT 0;

-- 2. Migrar empresas antigas para os novos planos (Mapeamento sugerido)
-- Plano 'free' ou 'iniciante' -> 'foundation'
UPDATE empresas SET ai_plan = 'foundation' WHERE ai_plan IN ('free', 'iniciante');
-- Plano 'growth' -> 'vision'
UPDATE empresas SET ai_plan = 'vision' WHERE ai_plan = 'growth';
-- Plano 'enterprise' -> 'enterprise_elite'
UPDATE empresas SET ai_plan = 'enterprise_elite' WHERE ai_plan = 'enterprise';

-- 3. Definir datas iniciais para empresas existentes que não possuem
UPDATE empresas SET next_billing_at = DATE_ADD(created_at, INTERVAL 1 MONTH) WHERE next_billing_at IS NULL;
