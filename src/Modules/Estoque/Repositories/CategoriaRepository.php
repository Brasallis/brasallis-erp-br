<?php

namespace App\Modules\Estoque\Repositories;

use PDO;

/**
 * CategoriaRepository — queries de categorias isoladas.
 */
class CategoriaRepository
{
    public function __construct(
        private PDO $pdo,
        private int $empresaId
    ) {}

    public function getAll(string $search = ''): array
    {
        $sql    = "SELECT id, nome, created_at FROM categorias WHERE empresa_id = ?";
        $params = [$this->empresaId];

        if ($search !== '') {
            $sql     .= " AND nome LIKE ?";
            $params[] = '%' . $search . '%';
        }

        $sql .= " ORDER BY nome ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, nome FROM categorias WHERE id=? AND empresa_id=?");
        $stmt->execute([$id, $this->empresaId]);
        return $stmt->fetch() ?: null;
    }

    public function add(string $nome): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO categorias (empresa_id, nome) VALUES (?, ?)");
        $stmt->execute([$this->empresaId, $nome]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $nome): bool
    {
        $stmt = $this->pdo->prepare("UPDATE categorias SET nome=? WHERE id=? AND empresa_id=?");
        return $stmt->execute([$nome, $id, $this->empresaId]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM categorias WHERE id=? AND empresa_id=?");
        return $stmt->execute([$id, $this->empresaId]);
    }
}
