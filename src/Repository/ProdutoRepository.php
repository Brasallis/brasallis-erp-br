<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;

class ProdutoRepository
{
    private PDO $conn;
    private int $empresa_id;

    public function __construct(PDO $conn, int $empresa_id)
    {
        $this->conn = $conn;
        $this->empresa_id = $empresa_id;
    }

    public function add($data)
    {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("INSERT INTO produtos (empresa_id, name, sku, description, cost_price, price, quantity, minimum_stock, categoria_id, unidade_medida, lote, validade, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $this->empresa_id,
                $data['name'], 
                $data['sku'] ?? null, 
                $data['description'] ?? '', 
                $data['cost_price'] ?? 0, 
                $data['price'] ?? 0, 
                $data['quantity'] ?? 0,
                $data['minimum_stock'] ?? 0, 
                empty($data['categoria_id']) ? null : $data['categoria_id'], 
                $data['unidade_medida'] ?? 'un', 
                $data['lote'] ?? null,
                empty($data['validade']) ? null : $data['validade'],
                $data['observacoes'] ?? ''
            ]);
            
            $product_id = $this->conn->lastInsertId();
            
            // Se houver quantidade inicial, registra o lote e o histórico
            if ($data['quantity'] > 0) {
                // Registrar Lote Inicial
                $lot_number = !empty($data['lote']) ? $data['lote'] : 'LOTE-INICIAL-' . date('Ymd');
                $validade = !empty($data['validade']) ? $data['validade'] : null;
                
                $lot_stmt = $this->conn->prepare("INSERT INTO lotes (produto_id, numero_lote, data_validade, quantidade_inicial, quantidade_atual, empresa_id) VALUES (?, ?, ?, ?, ?, ?)");
                $lot_stmt->execute([$product_id, $lot_number, $validade, $data['quantity'], $data['quantity'], $this->empresa_id]);
                
                // Registrar Histórico
                $history_stmt = $this->conn->prepare("INSERT INTO historico_estoque (empresa_id, product_id, user_id, action, quantity, new_quantity) VALUES (?, ?, ?, ?, ?, ?)");
                // Assumindo que o usuário logado é quem está criando (precisaria passar o user_id, mas vamos usar um padrão ou pegar da sessão se possível, mas aqui é repositório puro. 
                // Melhor: O controller deve passar o user_id. Mas para simplificar e manter compatibilidade, vamos pegar da sessão se existir, ou 0.)
                $user_id = $_SESSION['user_id'] ?? 0; 
                $history_stmt->execute([$this->empresa_id, $product_id, $user_id, 'entrada', $data['quantity'], $data['quantity']]);
            }
            
            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function update($data)
    {
        $stmt = $this->conn->prepare("UPDATE produtos SET name=?, sku=?, description=?, cost_price=?, price=?, quantity=?, minimum_stock=?, categoria_id=?, unidade_medida=?, lote=?, validade=?, observacoes=? WHERE id=? AND empresa_id = ?");
        return $stmt->execute([
            $data['name'], 
            $data['sku'] ?? null, 
            $data['description'] ?? '', 
            $data['cost_price'] ?? 0, 
            $data['price'] ?? 0, 
            $data['quantity'] ?? 0,
            $data['minimum_stock'] ?? 0, 
            empty($data['categoria_id']) ? null : $data['categoria_id'], 
            $data['unidade_medida'] ?? 'un', 
            $data['lote'] ?? null,
            empty($data['validade']) ? null : $data['validade'],
            $data['observacoes'] ?? '', 
            $data['id'],
            $this->empresa_id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM produtos WHERE id = ? AND empresa_id = ?");
        return $stmt->execute([$id, $this->empresa_id]);
    }

    public function getAll($search_term, $selected_category, $limit, $offset, $filter_low_stock = false)
    {
        $sql_where = " WHERE p.empresa_id = ?";
        $params_where = [$this->empresa_id];

        if ($selected_category !== 'all') {
            $sql_where .= " AND p.categoria_id = ?";
            $params_where[] = $selected_category;
        }
        if (!empty($search_term)) {
            $sql_where .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $params_where[] = '%' . $search_term . '%';
            $params_where[] = '%' . $search_term . '%';
        }
        if ($filter_low_stock) {
            $sql_where .= " AND p.quantity <= p.minimum_stock";
        }

        $query = "SELECT p.*, c.nome as categoria_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id" . $sql_where . " ORDER BY p.name ASC LIMIT ? OFFSET ?";
        $products_stmt = $this->conn->prepare($query);
        $i = 1;
        foreach ($params_where as $param) {
            $products_stmt->bindValue($i++, $param);
        }
        $products_stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $products_stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $products_stmt->execute();
        return $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($search_term, $selected_category, $filter_low_stock = false)
    {
        $sql_where = " WHERE p.empresa_id = ?";
        $params_where = [$this->empresa_id];

        if ($selected_category !== 'all') {
            $sql_where .= " AND p.categoria_id = ?";
            $params_where[] = $selected_category;
        }
        if (!empty($search_term)) {
            $sql_where .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $params_where[] = '%' . $search_term . '%';
            $params_where[] = '%' . $search_term . '%';
        }
        if ($filter_low_stock) {
            $sql_where .= " AND p.quantity <= p.minimum_stock";
        }

        $total_stmt = $this->conn->prepare("SELECT COUNT(*) FROM produtos p" . $sql_where);
        $total_stmt->execute($params_where);
        return $total_stmt->fetchColumn();
    }

    public function findById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM produtos WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $this->empresa_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getCategories()
    {
        $cat_stmt = $this->conn->prepare("SELECT id, nome FROM categorias WHERE empresa_id = ? ORDER BY nome");
        $cat_stmt->execute([$this->empresa_id]);
        return $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registerEntry($data)
    {
        $this->conn->beginTransaction();
        try {
            $product_id = (int)$data['product_id'];
            $quantity_to_add = (float)$data['quantity'];
            $cost_price = (float)$data['cost_price'];
            $sell_price = (float)$data['sell_price'];
            $lote = $data['lote'] ?? null;
            $validade = !empty($data['validade']) ? $data['validade'] : null;
            $user_id = $_SESSION['user_id'] ?? 0;

            // 1. Atualizar Produto (Estoque, Preços, Lote, Validade)
            $stmt = $this->conn->prepare("UPDATE produtos SET quantity = quantity + ?, cost_price = ?, price = ?, lote = ?, validade = ? WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$quantity_to_add, $cost_price, $sell_price, $lote, $validade, $product_id, $this->empresa_id]);

            // 2. Criar registro de Compra (Simplificado para Foundation)
            $supplier_id = !empty($data['supplier_id']) ? $data['supplier_id'] : null;
            
            // Fallback Google-Resilient: Se não houver fornecedor, usa/cria um "Fornecedor Avulso" para evitar erro de constraint
            if ($supplier_id === null) {
                $sup_check = $this->conn->prepare("SELECT id FROM fornecedores WHERE empresa_id = ? AND name = 'Fornecedor Avulso' LIMIT 1");
                $sup_check->execute([$this->empresa_id]);
                $supplier_id = $sup_check->fetchColumn();
                
                if (!$supplier_id) {
                    $ins_sup = $this->conn->prepare("INSERT INTO fornecedores (empresa_id, name, created_at) VALUES (?, 'Fornecedor Avulso', NOW())");
                    $ins_sup->execute([$this->empresa_id]);
                    $supplier_id = $this->conn->lastInsertId();
                }
            }

            $purchase_date = date('Y-m-d');
            $total_amount = $quantity_to_add * $cost_price;

            $purchase_stmt = $this->conn->prepare("INSERT INTO compras (empresa_id, supplier_id, purchase_date, user_id, total_amount) VALUES (?, ?, ?, ?, ?)");
            $purchase_stmt->execute([$this->empresa_id, $supplier_id, $purchase_date, $user_id, $total_amount]);
            $purchase_id = $this->conn->lastInsertId();

            // 3. Itens da Compra
            $item_stmt = $this->conn->prepare("INSERT INTO itens_compra (purchase_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $item_stmt->execute([$purchase_id, $product_id, $quantity_to_add, $cost_price]);

            // 4. Registrar Lote (Se houver lote informado)
            if (!empty($lote)) {
                 $lot_stmt = $this->conn->prepare("INSERT INTO lotes (produto_id, numero_lote, data_validade, quantidade_inicial, quantidade_atual, empresa_id) VALUES (?, ?, ?, ?, ?, ?)");
                 $lot_stmt->execute([$product_id, $lote, $validade, $quantity_to_add, $quantity_to_add, $this->empresa_id]);
            }

            // 5. Histórico de Estoque
            $history_stmt = $this->conn->prepare("INSERT INTO historico_estoque (empresa_id, product_id, user_id, action, quantity, details) VALUES (?, ?, ?, 'entrada', ?, ?)");
            $history_stmt->execute([$this->empresa_id, $product_id, $user_id, $quantity_to_add, "Entrada Manual - Compra #$purchase_id"]);

            // 6. Integração Financeira (Gera despesa no Fluxo de Caixa)
            if ($total_amount > 0) {
                $cp_stmt = $this->conn->prepare("INSERT INTO contas_pagar (empresa_id, descricao, valor, data_vencimento, status) VALUES (?, ?, ?, ?, 'pendente')");
                $cp_stmt->execute([$this->empresa_id, "Compra: " . ($data['product_name'] ?? 'Produto') . " ($quantity_to_add un)", $total_amount, $purchase_date]);
            }

            $this->conn->commit();
            return true;
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}
