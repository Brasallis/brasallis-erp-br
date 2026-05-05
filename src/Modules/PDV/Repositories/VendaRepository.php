<?php

namespace App\Modules\PDV\Repositories;

use PDO;

/**
 * VendaRepository — gestão de vendas e itens vendidos.
 */
class VendaRepository
{
    public function __construct(
        private PDO $pdo,
        private int $empresaId
    ) {}

    public function all(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM vendas 
             WHERE empresa_id = ? 
             ORDER BY data_venda DESC, id DESC LIMIT ?"
        );
        $stmt->execute([$this->empresaId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM vendas WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $this->empresaId]);
        $venda = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venda) return null;

        $stmtItems = $this->pdo->prepare(
            "SELECT iv.*, p.name as produto_nome, p.sku as produto_sku 
             FROM venda_itens iv 
             LEFT JOIN produtos p ON iv.product_id = p.id 
             WHERE iv.venda_id = ?"
        );
        $stmtItems->execute([$id]);
        $venda['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Buscar pagamentos vinculados
        $stmtPayments = $this->pdo->prepare("SELECT metodo_pagamento, valor FROM venda_pagamentos WHERE venda_id = ?");
        $stmtPayments->execute([$id]);
        $venda['pagamentos'] = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

        return $venda;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO vendas (empresa_id, user_id, cliente_id, created_at, total_amount, discount_amount, payment_method) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $this->empresaId,
            $data['user_id'],
            $data['cliente_id'] ?? null,
            $data['created_at'] ?? date('Y-m-d H:i:s'),
            $data['total_amount'],
            $data['discount_amount'] ?? 0,
            $data['payment_method'] ?? 'dinheiro'
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function addItem(array $itemData): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO venda_itens (venda_id, product_id, quantity, unit_price) 
             VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([
            $itemData['venda_id'],
            $itemData['product_id'],
            $itemData['quantity'],
            $itemData['unit_price']
        ]);
    }

    public function addPayment(array $paymentData): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO venda_pagamentos (venda_id, metodo_pagamento, valor) 
             VALUES (?, ?, ?)"
        );
        return $stmt->execute([
            $paymentData['venda_id'],
            $paymentData['metodo_pagamento'],
            $paymentData['valor']
        ]);
    }
}
