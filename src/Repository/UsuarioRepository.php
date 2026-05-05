<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

class UsuarioRepository {
    private PDO $db;
    private int $empresa_id;

    public function __construct(PDO $db, int $empresa_id) {
        $this->db = $db;
        $this->empresa_id = $empresa_id;
    }

    public function getAll($search = '') {
        $sql = "SELECT * FROM usuarios WHERE empresa_id = :empresa_id";
        if ($search) {
            $sql .= " AND (username LIKE :search OR email LIKE :search OR cpf LIKE :search)";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':empresa_id', $this->empresa_id);
        if ($search) {
            $stmt->bindValue(':search', "%$search%");
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $this->empresa_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add($data) {
        // 1. Obter plano da empresa e limite
        $stmt_plan = $this->db->prepare("SELECT ai_plan FROM empresas WHERE id = ?");
        $stmt_plan->execute([$this->empresa_id]);
        $empresa = $stmt_plan->fetch(PDO::FETCH_ASSOC);
        $plano_atual = $empresa['ai_plan'] ?? 'foundation';

        $config = \get_planos_config();
        $limite = $config['planos'][$plano_atual]['users_limit'] ?? 3;

        // 2. Contar usuários atuais
        $stmt_count = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ?");
        $stmt_count->execute([$this->empresa_id]);
        $atual = $stmt_count->fetchColumn();

        // 3. Validar limite
        if ($atual >= $limite) {
            throw new \Exception("Limite de usuários atingido para o plano " . ucfirst($plano_atual) . " ($limite usuários).");
        }

        $sql = "INSERT INTO usuarios (empresa_id, username, email, password, user_type, cpf, celular, status_colaborador, permissions, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $this->empresa_id,
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['user_type'] ?? 'employee',
            $data['cpf'] ?? null,
            $data['celular'] ?? null,
            $data['status_colaborador'] ?? 'ativo',
            json_encode($data['permissions'] ?? [])
        ]);
    }

    public function update($data) {
        $sql = "UPDATE usuarios SET 
                username = ?, 
                email = ?, 
                user_type = ?, 
                cpf = ?, 
                celular = ?, 
                status_colaborador = ?,
                permissions = ? ";
        
        $params = [
            $data['username'],
            $data['email'],
            $data['user_type'],
            $data['cpf'],
            $data['celular'],
            $data['status_colaborador'],
            json_encode($data['permissions'] ?? [])
        ];

        if (!empty($data['password'])) {
            $sql .= ", password = ? ";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ? AND empresa_id = ?";
        $params[] = $data['id'];
        $params[] = $this->empresa_id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ? AND empresa_id = ? AND user_type != 'admin'");
        return $stmt->execute([$id, $this->empresa_id]);
    }
}
