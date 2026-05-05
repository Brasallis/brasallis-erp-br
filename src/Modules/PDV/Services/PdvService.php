<?php

namespace App\Modules\PDV\Services;

use App\Modules\PDV\Repositories\VendaRepository;
use App\Modules\Estoque\Repositories\ProdutoRepository;
use App\Services\FiscalIntegrator;
use PDO;
use Exception;

/**
 * PdvService — lógica de checkout, baixa de estoque e pós-venda.
 */
class PdvService
{
    public function __construct(
        private PDO $pdo,
        private VendaRepository $vendaRepo,
        private ProdutoRepository $produtoRepo
    ) {}

    public function finalizarVenda(array $items, array $payments, ?int $clienteId = null, float $discountAmount = 0): int
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $empresaId = (int)$_SESSION['empresa_id'];
        $userId    = (int)$_SESSION['user_id'];

        try {
            $this->pdo->beginTransaction();

            // 1. Calcular Subtotal e Validar Estoque
            $subtotal = 0;
            foreach ($items as $item) {
                $productId = (int)$item['id'];
                $qty = (float)$item['qty'];
                
                $produto = $this->produtoRepo->findById($productId);
                if (!$produto || $produto['quantity'] < $qty) {
                    throw new Exception("Estoque insuficiente para o produto: " . ($produto['name'] ?? 'Desconhecido'));
                }

                $subtotal += ($qty * (float)$item['price']);
            }

            $totalAmount = max(0, $subtotal - $discountAmount);

            // Validar total dos pagamentos
            $totalPayment = 0;
            $mainMethod = 'dinheiro';
            if (!empty($payments)) {
                $mainMethod = count($payments) === 1 ? $payments[0]['method'] : 'multiplos';
                foreach ($payments as $payment) {
                    $totalPayment += (float)$payment['value'];
                }
            }

            // O total de pagamentos pode ser maior se houver troco (em dinheiro), mas não pode ser menor
            if (round($totalPayment, 2) < round($totalAmount, 2)) {
                throw new Exception("O valor pago (R$ {$totalPayment}) é menor que o total da venda (R$ {$totalAmount}).");
            }

            // 2. Criar Venda
            $vendaId = $this->vendaRepo->create([
                'user_id' => $userId,
                'cliente_id' => $clienteId,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'payment_method' => $mainMethod
            ]);

            // 3. Processar Pagamentos
            foreach ($payments as $payment) {
                $this->vendaRepo->addPayment([
                    'venda_id' => $vendaId,
                    'metodo_pagamento' => $payment['method'],
                    'valor' => (float)$payment['value']
                ]);
            }

            // 4. Processar Itens
            foreach ($items as $item) {
                $productId = (int)$item['id'];
                $qty = (float)$item['qty'];
                $price = (float)$item['price'];

                $this->vendaRepo->addItem([
                    'venda_id' => $vendaId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price' => $price
                ]);

                // Baixar Estoque
                $this->produtoRepo->atualizarEstoque($productId, $qty, 'saida');
            }

            // 5. Integração com Financeiro (Contas a Receber)
            $stmtFin = $this->pdo->prepare(
                "INSERT INTO contas_receber (empresa_id, descricao, valor, data_vencimento, data_recebimento, status, venda_id) 
                 VALUES (?, ?, ?, CURDATE(), NOW(), 'recebido', ?)"
            );
            $stmtFin->execute([$empresaId, "Venda PDV #$vendaId", $totalAmount, $vendaId]);

            $this->pdo->commit();
            return $vendaId;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
