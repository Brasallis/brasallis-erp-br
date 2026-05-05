<?php

namespace App\Modules\Estoque\Repositories;

use PDO;

/**
 * FornecedorRepository — gestão de fornecedores.
 */
class FornecedorRepository
{
    public function __construct(
        private PDO $pdo,
        private int $empresaId
    ) {}

    public function all(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM fornecedores WHERE empresa_id = ? ORDER BY nome ASC");
        $stmt->execute([$this->empresaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM fornecedores WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $this->empresaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO fornecedores (empresa_id, nome, email, telefone, cnpj, endereco) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $this->empresaId,
            $data['nome'],
            $data['email'] ?? null,
            $data['telefone'] ?? null,
            $data['cnpj'] ?? null,
            $data['endereco'] ?? null
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE fornecedores SET nome = ?, email = ?, telefone = ?, cnpj = ?, endereco = ? 
             WHERE id = ? AND empresa_id = ?"
        );
        return $stmt->execute([
            $data['nome'],
            $data['email'] ?? null,
            $data['telefone'] ?? null,
            $data['cnpj'] ?? null,
            $data['endereco'] ?? null,
            $id,
            $this->empresaId
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM fornecedores WHERE id = ? AND empresa_id = ?");
        return $stmt->execute([$id, $this->empresaId]);
    }
}
