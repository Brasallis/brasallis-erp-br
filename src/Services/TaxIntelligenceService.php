<?php
namespace App\Services;

use PDO;

class TaxIntelligenceService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Analisa um item baseado no NCM e CFOP para identificar oportunidades ou riscos.
     */
    public function analyzeItem($ncm, $cfop, $valorTotalItem) {
        $result = [
            'alert_level' => 'ok',
            'suggestion' => '',
            'savings_potential' => 0.00,
            'type' => 'tributado'
        ];

        // 1. Limpar NCM (remover pontos)
        $ncmClean = preg_replace('/[^0-9]/', '', $ncm);

        // 2. Buscar Regra na Tabela
        // Tenta match exato primeiro, depois match parcial (4 digitos - posição)
        $stmt = $this->pdo->prepare("SELECT * FROM tax_rules WHERE ncm = ? LIMIT 1");
        $stmt->execute([$ncmClean]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rule) {
            // Tenta buscar pelos 4 primeiros dígitos (Posição)
            $ncmRoot = substr($ncmClean, 0, 4);
            $stmt = $this->pdo->prepare("SELECT * FROM tax_rules WHERE ncm LIKE ? LIMIT 1");
            $stmt->execute([$ncmRoot . '%']);
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($rule) {
            $result['type'] = $rule['type'];

            if ($rule['type'] === 'monofasico') {
                $result['alert_level'] = 'info';
                $result['suggestion'] = "Produto Monofásico (PIS/COFINS zero). Verifique se o cadastro está correto para evitar pagamento duplicado.";
                // Estimativa: 9.25% de PIS/COFINS recuperável/evitado
                $result['savings_potential'] = $valorTotalItem * 0.0925;
            } elseif ($rule['type'] === 'substituicao_tributaria') {
                $result['alert_level'] = 'info';
                $result['suggestion'] = "Sujeito a ICMS SC. Certifique-se de que o ICMS já foi recolhido na fonte.";
            } elseif ($rule['type'] === 'isento') {
                $result['alert_level'] = 'info';
                $result['suggestion'] = "Produto Isento. Verifique a regra estadual.";
            }
        }

        // 3. Validação de CFOP (Exemplo simples)
        // Se CFOP começa com 5 (Saída Estadual) e estamos analisando uma entrada... 
        // Na verdade, na nota de entrada vem o CFOP da nota (que é saída do fornecedor).
        // Se vier 5102 (Venda mercadoria adquirida de terceiros), a entrada deve ser 1102 (Compra para comercialização).
        // Aqui apenas alertamos se houver incongruência óbvia.
        
        return $result;
    }

    public function saveAnalysis($compraId, $productId, $itemData, $analysisResult) {
        $stmt = $this->pdo->prepare("INSERT INTO analise_tributaria 
            (compra_id, product_id, item_name_xml, ncm_detectado, cfop_entrada, cst_csosn_entrada, alert_level, ai_suggestion, savings_potential) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $compraId,
            $productId,
            $itemData['name'],
            $itemData['ncm'] ?? null,
            $itemData['cfop'] ?? null,
            $itemData['cst'] ?? null,
            $analysisResult['alert_level'],
            $analysisResult['suggestion'],
            $analysisResult['savings_potential']
        ]);
    }
}
