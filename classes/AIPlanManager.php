<?php
namespace App;

use PDO;

require_once __DIR__ . '/../includes/planos_config.php';

class AIPlanManager {
    private $pdo;
    private $empresa_id;

    public function __construct(PDO $pdo, $empresa_id) {
        $this->pdo = $pdo;
        $this->empresa_id = $empresa_id;
    }

    public function getPlanStatus() {
        $stmt = $this->pdo->prepare("SELECT ai_plan, ai_tokens_used_month FROM empresas WHERE id = :id");
        $stmt->execute([':id' => $this->empresa_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) return null;

        $planKey = $data['ai_plan'] ?: 'foundation';
        
        // Mapeamento Centralizado
        $central_config = get_planos_config();
        $planos = $central_config['planos'];

        // Fallback e Normalização de Slugs Legados
        if (!array_key_exists($planKey, $planos)) {
            if ($planKey == 'free' || $planKey == 'starter') $planKey = 'foundation';
            else if ($planKey == 'growth' || $planKey == 'vision') $planKey = 'vision';
            else if ($planKey == 'pro' || $planKey == 'enterprise_elite') $planKey = 'enterprise';
            else $planKey = 'foundation';
        }

        $planConfig = $planos[$planKey];
        $limit = $planConfig['ai_token_limit'];
        $used = (int)$data['ai_tokens_used_month'];
        $percentage = ($limit > 0) ? min(100, round(($used / $limit) * 100)) : 0;

        return [
            'plan' => $planKey,
            'label' => $planConfig['nome'] ?? $planConfig['name'] ?? 'Plano Padrão',
            'color' => $this->mapColor($planKey),
            'limit' => $limit,
            'used' => $used,
            'percentage' => $percentage,
            'remaining' => max(0, $limit - $used),
            'is_exhausted' => $used >= $limit,
            'features' => ($planKey === 'foundation' ? ['standard_agents'] : ['standard_agents', 'custom_agents'])
        ];
    }

    private function mapColor($plan) {
        switch($plan) {
            case 'foundation': return 'secondary';
            case 'vision': return 'primary';
            case 'enterprise': return 'indigo';
            default: return 'dark';
        }
    }

    public function checkLimit() {
        $status = $this->getPlanStatus();
        if ($status && $status['is_exhausted']) {
            throw new \Exception("Limite de tokens do plano {$status['label']} atingido. Faça upgrade para continuar.");
        }
        return true;
    }

    public function canCreateCustomAgent() {
        $status = $this->getPlanStatus();
        if (!$status) return false;
        return in_array('custom_agents', $status['features']);
    }

    public function incrementUsage($tokens) {
        $sql = "UPDATE empresas SET ai_tokens_used_month = ai_tokens_used_month + :tokens WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tokens' => $tokens, ':id' => $this->empresa_id]);
    }
}
