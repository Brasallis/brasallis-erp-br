ALTER TABLE empresas ADD COLUMN ai_plan ENUM('free', 'starter', 'pro') DEFAULT 'free';
ALTER TABLE empresas ADD COLUMN ai_token_limit INT DEFAULT 100000;
ALTER TABLE empresas ADD COLUMN ai_tokens_used_month INT DEFAULT 0;
