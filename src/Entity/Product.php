<?php

namespace App\Entity;

class Product
{
    private $id;
    private $empresa_id;
    private $categoria_id;
    private $name;
    private $description;
    private $price;
    private $cost_price;
    private $quantity;
    private $minimum_stock;
    private $unidade_medida;
    private $lote;
    private $validade;
    private $observacoes;
    private $created_at;

    public function __construct(array $data = [])
    {
        $this->fill($data);
    }

    public function fill(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    // Getters
    public function getId() { return $this->id; }
    public function getEmpresaId() { return $this->empresa_id; }
    public function getName() { return $this->name; }
    public function getPrice() { return $this->price; }
    public function getQuantity() { return $this->quantity; }
    public function getMinimumStock() { return $this->minimum_stock; }
    public function getValidade() { return $this->validade; }

    // Domain Logic
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_stock;
    }

    public function isExpired(): bool
    {
        if (!$this->validade) return false;
        return strtotime($this->validade) < time();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->validade) return false;
        $expiration = strtotime($this->validade);
        $threshold = strtotime("+$days days");
        return $expiration <= $threshold && $expiration >= time();
    }
}
