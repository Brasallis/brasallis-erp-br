<?php

namespace App\Modules\RH\Repositories;

use PDO;

/**
 * SetorRepository — gestão de departamentos/setores da empresa.
 */
class SetorRepository
{
    public function __construct(
        private PDO $pdo,
        private int $empresaId
    ) {}

    public function all(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM setores WHERE empresa_id = ? ORDER BY nome ASC");
        $stmt->execute([$this->empresaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM setores WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $this->empresaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO setores (empresa_id, nome) VALUES (?, ?)");
        $stmt->execute([$this->empresaId, $data['nome']]);
        return (int)$this->pdo->lastInsertId();
    }
}
