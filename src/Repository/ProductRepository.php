<?php

namespace App\Repository;

use PDO;
use App\Entity\Product;

class ProductRepository
{
    private $conn;
    private $empresa_id;

    public function __construct(PDO $conn, $empresa_id)
    {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;
    }

    public function find($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM produtos WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $this->empresa_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) return null;

        return new Product($data);
    }

    public function findAll()
    {
        $stmt = $this->conn->prepare("SELECT * FROM produtos WHERE empresa_id = ?");
        $stmt->execute([$this->empresa_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $products = [];
        foreach ($results as $row) {
            $products[] = new Product($row);
        }

        return $products;
    }

    public function save(Product $product)
    {
        // Implementation for save (INSERT/UPDATE) would go here
        // For now, we are focusing on read operations for the dashboard/analysis
    }
}
