<?php
// includes/planos_config.php v4.0 - FONTE ÚNICA DE VERDADE
// Brasallis ERP - Regras de Negócio, Módulos e Tokens

function get_planos_config() {
    return [
        'planos' => [
            'foundation' => [
                'nome' => 'Foundation Hub',
                'modulos' => ['estoque', 'rh', 'relatorios', 'pdv', 'ai_hub'],
                'ai_token_limit' => 200000,
                'users_limit' => 3,
                'color' => '#64748b',
                'precos' => [
                    'mensal' => 189.90,
                    'semestral' => 1025.00, // ~10% desc
                    'anual' => 1823.00,    // ~20% desc
                    'bienal' => 3190.00    // ~30% desc
                ]
            ],
            'vision' => [
                'nome' => 'Vision AI Hub',
                'modulos' => ['estoque', 'rh', 'relatorios', 'pdv', 'ai_hub', 'financeiro', 'crm'],
                'ai_token_limit' => 2000000,
                'users_limit' => 10,
                'color' => '#3b82f6',
                'precos' => [
                    'mensal' => 389.90,
                    'semestral' => 2105.00,
                    'anual' => 3743.00,
                    'bienal' => 6550.00
                ]
            ],
            'enterprise' => [
                'nome' => 'Enterprise Elite',
                'modulos' => ['estoque', 'rh', 'relatorios', 'pdv', 'ai_hub', 'financeiro', 'crm', 'fiscal'],
                'ai_token_limit' => 10000000,
                'users_limit' => 999, 
                'color' => '#8b5cf6',
                'precos' => [
                    'mensal' => 899.90,
                    'semestral' => 4859.00,
                    'anual' => 8639.00,
                    'bienal' => 15118.00
                ]
            ]
        ],
        'modulos_info' => [
            'pdv' => ['nome' => 'PDV / Caixa', 'icon' => 'fa-cash-register', 'desc' => 'Vendas rápidas e emissão.'],
            'estoque' => ['nome' => 'Estoque', 'icon' => 'fa-boxes-stacked', 'desc' => 'Gestão de SKUs e lotes.'],
            'financeiro' => ['nome' => 'Financeiro', 'icon' => 'fa-wallet', 'desc' => 'Fluxo de caixa e contas.'],
            'crm' => ['nome' => 'CRM Vendas', 'icon' => 'fa-handshake', 'desc' => 'Funil de vendas e leads.'],
            'rh' => ['nome' => 'Equipe / RH', 'icon' => 'fa-users', 'desc' => 'Gestão de colaboradores.'],
            'ai_hub' => ['nome' => 'Brasallis AI', 'icon' => 'fa-robot', 'desc' => 'Agentes inteligentes.'],
            'fiscal' => ['nome' => 'Fiscal', 'icon' => 'fa-university', 'desc' => 'Notas e impostos.'],
            'relatorios' => ['nome' => 'BI Relatórios', 'icon' => 'fa-chart-pie', 'desc' => 'Análise de dados.']
        ]
    ];
}

/**
 * Retorna os módulos permitidos para um plano específico
 */
function get_modules_by_plan($plan_slug) {
    $config = get_planos_config();
    // Normalizar slug (ex: enterprise_elite -> enterprise)
    $slug = (strpos($plan_slug, 'enterprise') !== false) ? 'enterprise' : $plan_slug;
    return $config['planos'][$slug]['modulos'] ?? $config['planos']['foundation']['modulos'];
}

/**
 * Retorna o limite de tokens para um plano específico
 */
function get_ai_limit_by_plan($plan_slug) {
    $config = get_planos_config();
    $slug = (strpos($plan_slug, 'enterprise') !== false) ? 'enterprise' : $plan_slug;
    return $config['planos'][$slug]['ai_token_limit'] ?? 200000;
}
?>
