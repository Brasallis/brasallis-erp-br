<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/funcoes.php';

// Verificações de segurança e autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['empresa_id'])) {
    $_SESSION['message'] = 'Acesso não autorizado.';
    $_SESSION['message_type'] = 'danger';
    header("Location: atualizar_estoque.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? null;
    $empresa_id = $_SESSION['empresa_id'];
    $user_id = $_SESSION['user_id'];
    
    $message = '';
    $message_type = 'danger';

    if ($product_id && $quantity && $quantity > 0 && ($action === 'entrada' || $action === 'saida')) {
        $conn = connect_db();
        if ($conn) {
            $conn->beginTransaction();
            try {
                // Trava a linha do produto para evitar condições de corrida
                $stmt = $conn->prepare("SELECT quantity FROM produtos WHERE id = ? AND empresa_id = ? FOR UPDATE");
                $stmt->execute([$product_id, $empresa_id]);
                $current_stock = $stmt->fetchColumn();

                if ($current_stock === false) {
                    $message = 'Erro: Produto não encontrado ou não pertence à sua empresa.';
                    $conn->rollBack();
                } elseif ($action === 'saida' && $current_stock < $quantity) {
                    $message = 'Erro: Estoque insuficiente para realizar a saída.';
                    $conn->rollBack();
                } else {
                    // Lógica de Lotes
                    if ($action === 'entrada') {
                        // Captura dados do lote
                        $lot_number = $_POST['lot_number'] ?? 'N/A';
                        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
                        $supplier = $_POST['supplier'] ?? null;

                        // Insere novo lote
                        $lot_stmt = $conn->prepare("INSERT INTO lotes (produto_id, numero_lote, data_validade, quantidade_inicial, quantidade_atual, fornecedor, empresa_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $lot_stmt->execute([$product_id, $lot_number, $expiry_date, $quantity, $quantity, $supplier, $empresa_id]);
                    } elseif ($action === 'saida') {
                        // Verifica se é para registrar como venda
                        $is_sale = isset($_POST['is_sale']) && $_POST['is_sale'] == '1';
                        $venda_id = null;

                        if ($is_sale) {
                            // Busca preço do produto
                            $price_stmt = $conn->prepare("SELECT price FROM produtos WHERE id = ? AND empresa_id = ?");
                            $price_stmt->execute([$product_id, $empresa_id]);
                            $unit_price = $price_stmt->fetchColumn();
                            
                            if ($unit_price !== false) {
                                $total_amount = $unit_price * $quantity;
                                
                                // Inserir Venda
                                $venda_stmt = $conn->prepare("INSERT INTO vendas (empresa_id, user_id, total_amount, payment_method, created_at) VALUES (?, ?, ?, 'dinheiro', NOW())");
                                $venda_stmt->execute([$empresa_id, $user_id, $total_amount]);
                                $venda_id = $conn->lastInsertId();

                                // Inserir Item da Venda
                                $item_stmt = $conn->prepare("INSERT INTO venda_itens (venda_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                                $item_stmt->execute([$venda_id, $product_id, $quantity, $unit_price]);
                            }
                        }

                        // FIFO: Deduz dos lotes mais antigos com saldo > 0
                        $remaining_to_deduct = $quantity;
                        
                        // Busca lotes com saldo, ordenados por data de entrada (FIFO)
                        $lots_stmt = $conn->prepare("SELECT id, quantidade_atual FROM lotes WHERE produto_id = ? AND empresa_id = ? AND quantidade_atual > 0 ORDER BY data_entrada ASC FOR UPDATE");
                        $lots_stmt->execute([$product_id, $empresa_id]);
                        $lots = $lots_stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($lots as $lot) {
                            if ($remaining_to_deduct <= 0) break;

                            $deduct = min($lot['quantidade_atual'], $remaining_to_deduct);
                            $new_lot_qty = $lot['quantidade_atual'] - $deduct;

                            // Atualiza lote
                            $update_lot = $conn->prepare("UPDATE lotes SET quantidade_atual = ? WHERE id = ?");
                            $update_lot->execute([$new_lot_qty, $lot['id']]);

                            $remaining_to_deduct -= $deduct;
                        }
                        
                        // Se ainda sobrar algo (ex: estoque antigo sem lote), apenas abate do total (já garantido pela verificação inicial)
                    }

                    // Calcula o novo estoque total
                    $new_quantity = ($action === 'entrada') ? $current_stock + $quantity : $current_stock - $quantity;
                    
                    // Atualiza o estoque total do produto
                    $update_stmt = $conn->prepare("UPDATE produtos SET quantity = ? WHERE id = ? AND empresa_id = ?");
                    $update_stmt->execute([$new_quantity, $product_id, $empresa_id]);
                    
                    // Insere o registro no histórico
                    $history_stmt = $conn->prepare("INSERT INTO historico_estoque (empresa_id, product_id, user_id, action, quantity, new_quantity, venda_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $history_stmt->execute([$empresa_id, $product_id, $user_id, $action, $quantity, $new_quantity, $venda_id ?? null]);
                    
                    $conn->commit();
                    $message = 'Estoque atualizado com sucesso!';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                $conn->rollBack();
                $message = 'Ocorreu um erro na transação: ' . $e->getMessage();
                error_log("Stock update error: " . $e->getMessage());
            }
        } else {
            $message = "Não foi possível conectar ao banco de dados.";
        }
    } else {
        $message = 'Dados inválidos. Verifique o formulário.';
    }

    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $message_type;
    
    // Redireciona de volta para a página de atualização, que agora pode exibir a mensagem
    header("Location: atualizar_estoque.php");
    exit;
} else {
    // Se não for POST, redireciona para a página inicial do painel
    header("Location: painel_funcionario.php");
    exit;
}
?>
