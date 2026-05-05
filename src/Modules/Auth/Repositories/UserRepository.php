<?php

namespace App\Modules\Auth\Repositories;

use PDO;

/**
 * Repositório de usuários — consultas ao banco isoladas aqui.
 */
class UserRepository
{
    public function __construct(private PDO $pdo) {}

    /**
     * Busca um usuário pelo e-mail.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, username, email, password, user_type, empresa_id, plan
             FROM usuarios
             WHERE email = :email
             LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Busca usuários pelo nome ou e-mail (para a listagem).
     */
    public function search(string $term = ''): array
    {
        $empresaId = $_SESSION['empresa_id'] ?? 0;
        $sql = "SELECT id, username, email, user_type, empresa_id, plan, 
                       data_admissao, cpf, celular, status_colaborador 
                FROM usuarios 
                WHERE empresa_id = :empresa_id";
        
        $params = [':empresa_id' => $empresaId];

        if (!empty($term)) {
            $sql .= " AND (username LIKE :term OR email LIKE :term OR cpf LIKE :term)";
            $params[':term'] = "%$term%";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Busca um usuário pelo ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, username, email, user_type, empresa_id, plan,
                    data_admissao, cpf, celular, status_colaborador
             FROM usuarios
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Verifica se um e-mail já existe.
     */
    public function existsByEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    /**
     * Cria uma nova empresa e retorna o ID.
     */
    public function createEmpresa(string $name, string $aiPlan = 'free'): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO empresas (name, owner_user_id, ai_plan, ai_token_limit, max_users, support_level, ai_tokens_used_month)
             VALUES (?, 0, ?, 100000, 1, 'community', 0)"
        );
        $stmt->execute([$name, $aiPlan]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Cria um novo usuário admin e retorna o ID.
     */
    public function createAdminUser(int $empresaId, string $username, string $email, string $hashedPassword): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (empresa_id, username, email, password, user_type)
             VALUES (?, ?, ?, ?, 'admin')"
        );
        $stmt->execute([$empresaId, $username, $email, $hashedPassword]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza o owner da empresa.
     */
    public function updateEmpresaOwner(int $empresaId, int $userId): void
    {
        $this->pdo->prepare("UPDATE empresas SET owner_user_id = ? WHERE id = ?")
                  ->execute([$userId, $empresaId]);
    }

    /**
     * Carrega dados de branding da empresa.
     */
    public function getBranding(int $empresaId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT branding_primary_color, branding_secondary_color, branding_bg_style
             FROM empresas
             WHERE id = ?"
        );
        $stmt->execute([$empresaId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Carrega dados organizacionais do usuário (setor/cargo).
     */
    public function getOrgData(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT setor_id, cargo_id FROM usuario_setor WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Carrega permissões do cargo do usuário.
     */
    public function getPermissionsByCargo(int $cargoId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT m.slug, pc.nivel_acesso
             FROM permissoes_cargo pc
             JOIN modulos m ON pc.modulo_id = m.id
             WHERE pc.cargo_id = ?"
        );
        $stmt->execute([$cargoId]);
        $perms = [];
        while ($row = $stmt->fetch()) {
            $perms[$row['slug']] = $row['nivel_acesso'];
        }
        return $perms;
    }

    /**
     * Salva token de reset de senha.
     */
    public function savePasswordResetToken(string $email, string $token, string $expiresAt): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE usuarios SET reset_token = ?, reset_token_expires = ? WHERE email = ?"
        );
        return $stmt->execute([$token, $expiresAt, $email]);
    }

    /**
     * Busca usuário pelo token de reset.
     */
    public function findByResetToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, email, reset_token_expires FROM usuarios WHERE reset_token = ? LIMIT 1"
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Atualiza a senha e limpa o token de reset.
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE usuarios SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?"
        );
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Cria um novo usuário (colaborador) no banco de dados.
     */
    public function create(array $data): int
    {
        $data['empresa_id'] = $data['empresa_id'] ?? ($_SESSION['empresa_id'] ?? null);
        
        $sql = "INSERT INTO usuarios (empresa_id, username, email, password, user_type, plan, 
                                     data_admissao, cpf, celular, status_colaborador)
                VALUES (:empresa_id, :username, :email, :password, :user_type, :plan,
                        :data_admissao, :cpf, :celular, :status_colaborador)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':empresa_id'     => $data['empresa_id'],
            ':username'       => $data['username'],
            ':email'          => $data['email'],
            ':password'       => $data['password'],
            ':user_type'      => $data['user_type'] ?? 'employee',
            ':plan'           => $data['plan'] ?? 'free',
            ':data_admissao'  => $data['data_admissao'] ?? null,
            ':cpf'            => $data['cpf'] ?? null,
            ':celular'        => $data['celular'] ?? null,
            ':status_colaborador' => $data['status_colaborador'] ?? 'ativo'
        ]);
        
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza os dados de um usuário existente.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        $updatableFields = [
            'username', 'email', 'password', 'user_type', 'plan',
            'data_admissao', 'cpf', 'celular', 'status_colaborador'
        ];

        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Remove um usuário pelo ID.
     */
    public function delete(int $id): bool
    {
        $empresaId = $_SESSION['empresa_id'] ?? 0;
        
        // Blindagem Multi-tenant: Garante que só pode deletar se pertencer à empresa
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ? AND empresa_id = ?");
        return $stmt->execute([$id, $empresaId]);
    }
}

