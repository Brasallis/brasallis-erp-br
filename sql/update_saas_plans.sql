-- Rename/Modify ai_plan column to support new values
ALTER TABLE empresas MODIFY COLUMN ai_plan ENUM('free', 'growth', 'enterprise') DEFAULT 'free';

-- Add SaaS columns if they don't exist
ALTER TABLE empresas ADD COLUMN IF NOT EXISTS max_users INT DEFAULT 1;
ALTER TABLE empresas ADD COLUMN IF NOT EXISTS support_level ENUM('community', 'priority', 'dedicated') DEFAULT 'community';

-- Update existing records mapping
-- 'starter' (old) -> 'growth'
UPDATE empresas SET ai_plan = 'growth', max_users = 5, support_level = 'priority', ai_token_limit = 2000000 WHERE ai_plan = 'starter';

-- 'pro' (old) -> 'enterprise'
UPDATE empresas SET ai_plan = 'enterprise', max_users = 999, support_level = 'dedicated', ai_token_limit = 10000000 WHERE ai_plan = 'pro';

-- 'free' (default) adjustments
UPDATE empresas SET max_users = 1, support_level = 'community', ai_token_limit = 100000 WHERE ai_plan = 'free';
