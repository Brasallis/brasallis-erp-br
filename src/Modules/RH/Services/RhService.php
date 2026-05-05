<?php

namespace App\Modules\RH\Services;

use App\Modules\Auth\Repositories\UserRepository;
use App\Modules\RH\Repositories\SetorRepository;
use App\Modules\RH\Repositories\CargoRepository;
use PDO;
use Exception;

/**
 * RhService — orquestra usuários, setores e cargos.
 */
class RhService
{
    public function __construct(
        private PDO $pdo,
        private UserRepository $userRepo,
        private SetorRepository $setorRepo,
        private CargoRepository $cargoRepo
    ) {}

    /**
     * Cria um usuário e vincula opcionalmente a setor/cargo.
     */
    public function cadastrarFuncionario(array $userData, ?int $setorId = null, $cargoInput = null): int
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Criar Usuário (Agora com campos de RH inclusos no $userData)
            $userId = $this->userRepo->create($userData);

            // 2. Tratar Cargo e Vínculo
            if ($setorId) {
                $cargoId = $this->resolverCargo($setorId, $cargoInput);
                
                $stmt = $this->pdo->prepare("INSERT INTO usuario_setor (user_id, setor_id, cargo_id) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $setorId, $cargoId]);
            }

            $this->pdo->commit();
            return $userId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Atualiza dados de um funcionário.
     */
    public function atualizarFuncionario(int $id, array $userData, ?int $setorId = null, $cargoInput = null): bool
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Atualizar Usuário (Agora com campos de RH inclusos no $userData)
            $this->userRepo->update($id, $userData);

            // 2. Atualizar Vínculo (Remove e recria)
            $this->pdo->prepare("DELETE FROM usuario_setor WHERE user_id = ?")->execute([$id]);

            if ($setorId) {
                $cargoId = $this->resolverCargo($setorId, $cargoInput);
                $stmt = $this->pdo->prepare("INSERT INTO usuario_setor (user_id, setor_id, cargo_id) VALUES (?, ?, ?)");
                $stmt->execute([$id, $setorId, $cargoId]);
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Resolve se deve usar um cargo existente ou criar um novo on-the-fly.
     */
    private function resolverCargo(int $setorId, $cargoInput): ?int
    {
        if (empty($cargoInput)) return null;

        // Se for um array com 'nome', cria novo
        if (is_array($cargoInput) && !empty($cargoInput['nome'])) {
            $existing = $this->cargoRepo->findByName($setorId, $cargoInput['nome']);
            if ($existing) return $existing['id'];

            return $this->cargoRepo->create([
                'setor_id' => $setorId,
                'nome' => $cargoInput['nome']
            ]);
        }

        // Caso contrário, assume que é um ID numérico existente
        return is_numeric($cargoInput) ? (int)$cargoInput : null;
    }
}
