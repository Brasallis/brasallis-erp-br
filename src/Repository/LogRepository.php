<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

class LogRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getLogs(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT l.*, e.name as empresa_nome, u.username as user_name 
                FROM system_logs l 
                LEFT JOIN empresas e ON l.empresa_id = e.id 
                LEFT JOIN usuarios u ON l.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['severity'])) {
            $sql .= " AND l.severity = ?";
            $params[] = $filters['severity'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND l.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['source'])) {
            $sql .= " AND l.source = ?";
            $params[] = $filters['source'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (l.message LIKE ? OR l.stack_trace LIKE ?)";
            $params[] = "%" . $filters['search'] . "%";
            $params[] = "%" . $filters['search'] . "%";
        }
        if (!empty($filters['empresa_id'])) {
            $sql .= " AND l.empresa_id = ?";
            $params[] = $filters['empresa_id'];
        }

        $sql .= " ORDER BY l.created_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key + 1, $val);
        }
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array
    {
        $stats = [];
        $stats['total_24h']       = $this->conn->query("SELECT COUNT(*) FROM system_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $stats['errors_24h']      = $this->conn->query("SELECT COUNT(*) FROM system_logs WHERE severity = 'error' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $stats['unresolved']      = $this->conn->query("SELECT COUNT(*) FROM system_logs WHERE status = 'new'")->fetchColumn();
        $stats['security_events'] = $this->conn->query("SELECT COUNT(*) FROM system_logs WHERE source = 'Security' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $stats['sources']         = $this->conn->query("SELECT source, COUNT(*) as qtd FROM system_logs GROUP BY source ORDER BY qtd DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        return $stats;
    }

    public function resolve(int $id, int $userId): bool
    {
        $stmt = $this->conn->prepare("UPDATE system_logs SET status = 'resolved', resolved_at = NOW(), resolved_by = ? WHERE id = ?");
        return $stmt->execute([$userId, $id]);
    }

    public function resolveAll(): bool
    {
        return (bool)$this->conn->exec("UPDATE system_logs SET status = 'resolved', resolved_at = NOW() WHERE status = 'new'");
    }

    public function clearOld(int $days = 30): int
    {
        $stmt = $this->conn->prepare("DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }
}
