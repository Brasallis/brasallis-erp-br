<?php

namespace App\Modules\RH\Repositories;

use PDO;

/**
 * CargoRepository — gestão de cargos vinculados a setores.
 */
class CargoRepository
{
    public function __construct(private PDO $pdo) {}

    public function findBySetor(int $setorId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cargos WHERE setor_id = ? ORDER BY nome ASC");
        $stmt->execute([$setorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByName(int $setorId, string $nome): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cargos WHERE setor_id = ? AND nome = ?");
        $stmt->execute([$setorId, $nome]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO cargos (setor_id, nome, nivel_hierarquia) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['setor_id'],
            $data['nome'],
            $data['nivel_hierarquia'] ?? 1
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
