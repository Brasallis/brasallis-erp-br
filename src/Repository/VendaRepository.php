<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

class VendaRepository
{
    private PDO $conn;
    private int $empresa_id;

    public function __construct(PDO $conn, int $empresa_id)
    {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;
    }

    public function getVendasByDate(string $date, string $search = '', ?int $user_id = null): array
    {
        $sql = "SELECT v.*, u.username 
                FROM vendas v 
                JOIN usuarios u ON v.user_id = u.id 
                WHERE v.empresa_id = ? AND DATE(v.created_at) = ?";
        $params = [$this->empresa_id, $date];

        if (!empty($search)) {
            $sql .= " AND v.id = ?";
            $params[] = $search;
        }

        // Filtra por usuário (caixa atual) se não for admin
        if ($user_id !== null) {
            $sql .= " AND v.user_id = ?";
            $params[] = $user_id;
        }

        $sql .= " ORDER BY v.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVendaDetails(int $id): array
    {
        $sql = "SELECT i.*, p.name 
                FROM venda_itens i 
                JOIN produtos p ON i.product_id = p.id 
                WHERE i.venda_id = ? AND p.empresa_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id, $this->empresa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
