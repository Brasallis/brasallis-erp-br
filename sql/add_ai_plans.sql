ALTER TABLE empresas ADD COLUMN IF NOT EXISTS ai_plan ENUM('free', 'starter', 'pro') DEFAULT 'free';
ALTER TABLE empresas ADD COLUMN IF NOT EXISTS ai_token_limit INT DEFAULT 100000; -- Default 100k for free
ALTER TABLE empresas ADD COLUMN IF NOT EXISTS ai_tokens_used_month INT DEFAULT 0;
