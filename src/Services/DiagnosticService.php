<?php

namespace App\Services;

/**
 * DiagnosticService - Motor de Inteligência Estratégica Brasallis IQ
 * Gera laudos técnicos de alta autoridade baseados em maturidade e setor.
 */
class DiagnosticService
{
    /**
     * Gera um laudo técnico completo e sugere o plano Brasallis ideal.
     */
    public function generateRoadmap(array $data): array
    {
        $segment = $data['segment'] ?? 'varejo';
        $maturity = $data['maturity'] ?? 'fundacao';
        $needs = $data['needs'] ?? ['financeiro'];

        // Determina o plano sugerido
        $plan = $this->determineIdealPlan($maturity, $needs);

        return [
            'score' => $this->calculateEfficiencyScore($maturity),
            'plan_suggested' => $plan['name'],
            'plan_reason' => $plan['reason'],
            'main_modules' => $this->mapModulesWithJustification($needs, $segment),
            'sector_bonus' => $this->getSectorSpecificBonus($segment),
            'action_plan' => $this->generateActionPlan($maturity, $needs),
            'security_note' => "Este roadmap foi gerado para garantir que sua transição para o Brasallis Hub seja a fundação sólida que sua empresa exige para escalar com segurança fiscal e operacional.",
            'next_step' => $plan['cta']
        ];
    }

    /**
     * Mapeia a maturidade para o plano Brasallis correto
     */
    private function determineIdealPlan(string $maturity, array $needs): array
    {
        if ($maturity === 'escala' || count($needs) > 3) {
            return [
                'name' => 'ENTERPRISE ELITE',
                'reason' => 'Sua operação atingiu um nível de complexidade onde a governança e a IA preditiva são vitais. O plano Enterprise oferece infraestrutura dedicada e o Brasallis IQ em sua capacidade total para prever gargalos antes que eles aconteçam.',
                'cta' => 'Agendar Reunião com Consultor de Expansão'
            ];
        }

        if ($maturity === 'tracao') {
            return [
                'name' => 'BRASALLIS PRO',
                'reason' => 'Para empresas em fase de tração, o controle de processos e a automação são os diferenciais competitivos. O plano PRO libera ferramentas de auditoria e multi-lojas necessárias para sustentar seu crescimento acelerado.',
                'cta' => 'Migrar para o Ecossistema PRO'
            ];
        }

        return [
            'name' => 'BRASALLIS START',
            'reason' => 'O foco agora é a fundação. O plano START elimina a dependência de planilhas e organiza seu fluxo de caixa e estoque primário, garantindo que você comece com processos profissionais desde o primeiro dia.',
            'cta' => 'Iniciar Fundação Digital (Teste Grátis)'
        ];
    }

    /**
     * Mapeia módulos e explica tecnicamente a ajuda que eles trazem
     */
    private function mapModulesWithJustification(array $needs, string $segment): array
    {
        $map = [];
        foreach ($needs as $need) {
            $map[] = match ($need) {
                'financeiro' => [
                    'module' => 'Fluxo de Caixa 360',
                    'why' => 'Garante visibilidade total sobre a saúde financeira, prevenindo quebras de caixa comuns em empresas de ' . $segment . '.'
                ],
                'estoque' => [
                    'module' => 'Inteligência de Inventário',
                    'why' => 'Otimiza o capital de giro parado e evita rupturas de estoque através de análise preditiva de demanda.'
                ],
                'processos' => [
                    'module' => 'Automação Operacional',
                    'why' => 'Padroniza entregas e reduz falhas humanas, criando uma operação replicável e escalável.'
                ],
                'vendas' => [
                    'module' => 'Dashboard de Performance',
                    'why' => 'Mapeia a origem das suas receitas e o custo de aquisição (CAC), permitindo investir onde há mais retorno.'
                ],
                default => ['module' => 'Core Brasallis', 'why' => 'Base sólida para gestão empresarial.']
            };
        }
        return $map;
    }

    private function calculateEfficiencyScore(string $maturity): int
    {
        return match ($maturity) {
            'fundacao' => 38,
            'tracao' => 62,
            'escala' => 89,
            default => 45,
        };
    }

    private function getSectorSpecificBonus(string $segment): string
    {
        return match ($segment) {
            'varejo' => 'Recurso de Frente de Caixa (PDV) Offline: venda sem depender da internet.',
            'servicos' => 'Gestão de Contratos Recorrentes: automação de notas fiscais de serviço mensal.',
            'industria' => 'Ficha Técnica de Produção: controle exato de insumos e custo por unidade.',
            'gastronomia' => 'Gestão de Mesas e Comandas: agilidade total no atendimento ao cliente.',
            'ecommerce' => 'Sincronização com Marketplaces: estoque unificado em todos os canais de venda.',
            default => 'Infraestrutura Cloud de alta disponibilidade.',
        };
    }

    private function generateActionPlan(string $maturity, array $needs): array
    {
        return [
            'Configurar a estrutura de ' . $needs[0] . ' para eliminar o gargalo imediato.',
            'Importar base histórica para análise preditiva do Brasallis IQ.',
            'Treinar a equipe nos novos fluxos de trabalho profissionais.'
        ];
    }
}
