<?php

namespace App\Modules\Estoque\Services;

use App\Modules\Estoque\Repositories\CompraRepository;
use App\Modules\Estoque\Repositories\ProdutoRepository;
use PDO;
use Exception;

/**
 * CompraService — regras de negócio para entradas de estoque e compras.
 */
class CompraService
{
    public function __construct(
        private PDO $pdo,
        private CompraRepository $compraRepo,
        private ProdutoRepository $produtoRepo
    ) {}

    /**
     * Registra uma nova compra, atualiza estoque e gera histórico.
     */
    public function registrarCompra(array $data, array $items): int
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Calcular Total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += ($item['quantity'] * $item['cost_price']);
            }
            $data['total_amount'] = $totalAmount;

            // 2. Criar registro da Compra
            $purchaseId = $this->compraRepo->create($data);

            // 3. Processar Itens
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                
                // Snapshot do estoque atual
                $produto = $this->produtoRepo->findById($productId);
                $stockAtPurchase = $produto ? $produto['quantity'] : 0;

                // Adicionar item da compra
                $this->compraRepo->addItem([
                    'purchase_id' => $purchaseId,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['cost_price'],
                    'stock_at_purchase' => $stockAtPurchase
                ]);

                // Atualizar estoque e preço de custo no produto
                $this->produtoRepo->atualizarEstoque($productId, (float)$item['quantity'], 'entrada');
                $this->produtoRepo->update($productId, [
                    'cost_price' => $item['cost_price'],
                    'name' => $produto['name'] // Manter nome original ou atualizar se necessário
                ]);
            }

            // TODO: Integrar TaxIntelligenceService e FiscalIntegrator se necessário nesta fase

            $this->pdo->commit();
            return $purchaseId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
