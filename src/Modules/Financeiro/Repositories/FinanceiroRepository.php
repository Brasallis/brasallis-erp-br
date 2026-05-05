<?php

namespace App\Modules\Financeiro\Repositories;

use PDO;

/**
 * FinanceiroRepository — gestão de caixa e fluxo financeiro.
 */
class FinanceiroRepository
{
    public function __construct(
        private PDO $pdo,
        private int $empresaId
    ) {}

    public function getResumoCaixaToday(): array
    {
        // Entradas (Vendas)
        $stmtVendas = $this->pdo->prepare(
            "SELECT SUM(total_amount) FROM vendas 
             WHERE empresa_id = ? AND DATE(data_venda) = CURDATE()"
        );
        $stmtVendas->execute([$this->empresaId]);
        $entradas = (float)$stmtVendas->fetchColumn();

        // Saídas (Compras - confirmadas)
        $stmtCompras = $this->pdo->prepare(
            "SELECT SUM(total_amount) FROM compras 
             WHERE empresa_id = ? AND DATE(purchase_date) = CURDATE() AND status = 'confirmado'"
        );
        $stmtCompras->execute([$this->empresaId]);
        $saidas = (float)$stmtCompras->fetchColumn();

        return [
            'entradas' => $entradas,
            'saidas' => $saidas,
            'saldo' => $entradas - $saidas
        ];
    }

    public function getMovimentacoes(int $limit = 50): array
    {
        // União de vendas e compras para fluxo de caixa
        $sql = "(SELECT 'venda' as tipo, total_amount as valor, data_venda as data, payment_method as detalhe 
                 FROM vendas WHERE empresa_id = ?)
                UNION ALL
                (SELECT 'compra' as tipo, total_amount as valor, purchase_date as data, 'Compra de Mercadoria' as detalhe 
                 FROM compras WHERE empresa_id = ? AND status = 'confirmado')
                ORDER BY data DESC LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->empresaId, $this->empresaId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
