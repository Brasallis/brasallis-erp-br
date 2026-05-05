<?php

namespace App\Modules\Estoque\Repositories;

use PDO;
use PDOException;

/**
 * ProdutoRepository — queries de produto isoladas.
 * Recebe PDO via injeção de dependências (DI Container).
 * Toda query é scopada por empresa_id (multi-tenant).
 */
class ProdutoRepository
{
    public function __construct(
        private PDO $pdo,
        private int $empresaId
    ) {}

    /**
     * Insere um novo produto e registra lote + histórico de estoque.
     *
     * @throws PDOException
     */
    public function add(array $data, int $userId): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO produtos
                    (empresa_id, name, sku, ncm, cfop, origem, description, cost_price, price, quantity,
                     minimum_stock, categoria_id, unidade_medida, lote, validade, observacoes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $this->empresaId,
                $data['name'],
                $data['sku']             ?? null,
                $data['ncm']             ?? null,
                $data['cfop']            ?? null,
                $data['origem']          ?? 0,
                $data['description']     ?? null,
                $data['cost_price'],
                $data['price'],
                $data['quantity']        ?? 0,
                $data['minimum_stock']   ?? 0,
                empty($data['categoria_id']) ? null : (int)$data['categoria_id'],
                $data['unidade_medida']  ?? 'un',
                $data['lote']            ?? null,
                empty($data['validade']) ? null : $data['validade'],
                $data['observacoes']     ?? null,
            ]);

            $productId = (int) $this->pdo->lastInsertId();

            // Registra lote inicial e histórico se houver quantidade
            $qty = (int)($data['quantity'] ?? 0);
            if ($qty > 0) {
                $lotNumber = !empty($data['lote']) ? $data['lote'] : 'LOTE-INICIAL-' . date('Ymd');
                $validade  = empty($data['validade']) ? null : $data['validade'];

                $this->pdo->prepare(
                    "INSERT INTO lotes (produto_id, numero_lote, data_validade, quantidade_inicial, quantidade_atual, empresa_id)
                     VALUES (?, ?, ?, ?, ?, ?)"
                )->execute([$productId, $lotNumber, $validade, $qty, $qty, $this->empresaId]);

                $this->pdo->prepare(
                    "INSERT INTO historico_estoque (empresa_id, product_id, user_id, action, quantity, new_quantity)
                     VALUES (?, ?, ?, 'entrada', ?, ?)"
                )->execute([$this->empresaId, $productId, $userId, $qty, $qty]);
            }

            $this->pdo->commit();
            return $productId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza um produto existente.
     */
    public function update(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE produtos
             SET name=?, sku=?, ncm=?, cfop=?, origem=?, description=?, cost_price=?, price=?, quantity=?,
                 minimum_stock=?, categoria_id=?, unidade_medida=?, lote=?, validade=?, observacoes=?
             WHERE id=? AND empresa_id=?"
        );
        return $stmt->execute([
            $data['name'],
            $data['sku']           ?? null,
            $data['ncm']           ?? null,
            $data['cfop']          ?? null,
            $data['origem']        ?? 0,
            $data['description']   ?? null,
            $data['cost_price'],
            $data['price'],
            $data['quantity'],
            $data['minimum_stock'],
            empty($data['categoria_id']) ? null : (int)$data['categoria_id'],
            $data['unidade_medida'],
            $data['lote']          ?? null,
            empty($data['validade']) ? null : $data['validade'],
            $data['observacoes']   ?? null,
            (int)$data['id'],
            $this->empresaId,
        ]);
    }

    /**
     * Remove um produto.
     *
     * @throws PDOException se houver FK constraint (produto em uso)
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM produtos WHERE id=? AND empresa_id=?");
        return $stmt->execute([$id, $this->empresaId]);
    }

    /**
     * Retorna lista paginada de produtos com filtros opcionais.
     */
    public function getAll(
        string $search      = '',
        string $categoriaId = 'all',
        int    $limit       = 10,
        int    $offset      = 0,
        bool   $lowStock    = false
    ): array {
        [$where, $params] = $this->buildWhere($search, $categoriaId, $lowStock);

        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                {$where}
                ORDER BY p.name ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $i = 1;
        foreach ($params as $v) $stmt->bindValue($i++, $v);
        $stmt->bindValue($i++, $limit,  PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta total de produtos para paginação.
     */
    public function countAll(
        string $search      = '',
        string $categoriaId = 'all',
        bool   $lowStock    = false
    ): int {
        [$where, $params] = $this->buildWhere($search, $categoriaId, $lowStock);

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM produtos p {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Busca um produto por ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM produtos WHERE id=? AND empresa_id=?");
        $stmt->execute([$id, $this->empresaId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Retorna todas as categorias da empresa.
     */
    public function getCategories(): array
    {
        $stmt = $this->pdo->prepare("SELECT id, nome FROM categorias WHERE empresa_id=? ORDER BY nome");
        $stmt->execute([$this->empresaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca rápida por nome ou SKU (para autocomplete).
     */
    public function search(string $term, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, sku, price, quantity, unidade_medida
             FROM produtos
             WHERE empresa_id=? AND (name LIKE ? OR sku LIKE ?)
             LIMIT ?"
        );
        $like = '%' . $term . '%';
        $stmt->execute([$this->empresaId, $like, $like, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ----------------------------------------------------------------
    // Helpers internos
    // ----------------------------------------------------------------

    private function buildWhere(string $search, string $categoriaId, bool $lowStock): array
    {
        $where  = "WHERE p.empresa_id = ?";
        $params = [$this->empresaId];

        if ($categoriaId !== 'all') {
            $where     .= " AND p.categoria_id = ?";
            $params[]   = $categoriaId;
        }
        if ($search !== '') {
            $where     .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $params[]   = '%' . $search . '%';
            $params[]   = '%' . $search . '%';
        }
        if ($lowStock) {
            $where .= " AND p.quantity <= p.minimum_stock";
        }

        return [$where, $params];
    }
}
