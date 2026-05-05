<?php

namespace App\Modules\Estoque\Repositories;

use PDO;

/**
 * CompraRepository — gestão de pedidos de compra e entradas de nota.
 */
class CompraRepository
{
    public function __construct(
        private PDO $pdo,
        private int $empresaId
    ) {}

    public function all(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, f.nome as fornecedor_nome 
             FROM compras c 
             LEFT JOIN fornecedores f ON c.supplier_id = f.id 
             WHERE c.empresa_id = ? 
             ORDER BY c.purchase_date DESC, c.id DESC LIMIT ?"
        );
        $stmt->execute([$this->empresaId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, f.nome as fornecedor_nome, f.cnpj as fornecedor_cnpj 
             FROM compras c 
             LEFT JOIN fornecedores f ON c.supplier_id = f.id 
             WHERE c.id = ? AND c.empresa_id = ?"
        );
        $stmt->execute([$id, $this->empresaId]);
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$compra) return null;

        $stmtItems = $this->pdo->prepare(
            "SELECT ic.*, p.name as produto_nome, p.sku as produto_sku 
             FROM itens_compra ic 
             LEFT JOIN produtos p ON ic.product_id = p.id 
             WHERE ic.purchase_id = ?"
        );
        $stmtItems->execute([$id]);
        $compra['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $compra;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO compras (empresa_id, supplier_id, purchase_date, user_id, total_amount, fiscal_note_path, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $this->empresaId,
            $data['supplier_id'],
            $data['purchase_date'],
            $data['user_id'],
            $data['total_amount'],
            $data['fiscal_note_path'] ?? null,
            $data['status'] ?? 'confirmado'
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function addItem(array $itemData): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO itens_compra (purchase_id, product_id, quantity, unit_price, stock_at_purchase) 
             VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $itemData['purchase_id'],
            $itemData['product_id'],
            $itemData['quantity'],
            $itemData['unit_price'],
            $itemData['stock_at_purchase']
        ]);
    }
}
