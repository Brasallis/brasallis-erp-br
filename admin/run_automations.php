<?php

function runNotificationAutomations($conn, $empresa_id) {
    if (!$empresa_id) {
        return; // Não fazer nada se não houver empresa na sessão
    }

    // 1. Automação para Estoque Baixo
    try {
        $stmt_low_stock = $conn->prepare("SELECT id, name, quantity, minimum_stock FROM produtos WHERE quantity <= minimum_stock AND empresa_id = ?");
        $stmt_low_stock->execute([$empresa_id]);
        
        while ($product = $stmt_low_stock->fetch(PDO::FETCH_ASSOC)) {
            // Verifica se uma notificação para este problema já foi criada alguma vez.
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM notificacoes WHERE product_id = ? AND type = 'low_stock' AND empresa_id = ?");
            $stmt_check->execute([$product['id'], $empresa_id]);
            $existing_count = $stmt_check->fetchColumn();

            // Só cria a notificação se ela nunca existiu antes.
            if ($existing_count == 0) {
                $message = "Estoque baixo para o produto: <b>" . htmlspecialchars($product['name']) . "</b>. Quantidade atual: " . $product['quantity'] . ", mínimo: " . $product['minimum_stock'] . ".";
                $stmt_insert = $conn->prepare("INSERT INTO notificacoes (empresa_id, type, message, product_id) VALUES (?, 'low_stock', ?, ?)");
                $stmt_insert->execute([$empresa_id, $message, $product['id']]);
            }
            // Se já existe (mesmo que dispensada), não faz nada.
        }
    } catch (PDOException $e) {
        error_log("Erro na automação de estoque baixo: " . $e->getMessage());
    }

    // 2. Automação para Produtos Próximos ao Vencimento (nos próximos 30 dias)
    try {
        $threshold_date = date('Y-m-d', strtotime('+30 days'));
        $stmt_expiring = $conn->prepare("SELECT id, name, validade FROM produtos WHERE validade IS NOT NULL AND validade BETWEEN CURDATE() AND ? AND empresa_id = ?");
        $stmt_expiring->execute([$threshold_date, $empresa_id]);

        while ($product = $stmt_expiring->fetch(PDO::FETCH_ASSOC)) {
            // Verifica se uma notificação para este problema já foi criada alguma vez.
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM notificacoes WHERE product_id = ? AND type = 'nearing_expiration' AND empresa_id = ?");
            $stmt_check->execute([$product['id'], $empresa_id]);
            $existing_count = $stmt_check->fetchColumn();

            // Só cria a notificação se ela nunca existiu antes.
            if ($existing_count == 0) {
                $formatted_date = date('d/m/Y', strtotime($product['validade']));
                $message = "Produto próximo ao vencimento: <b>" . htmlspecialchars($product['name']) . "</b>. Vence em: " . $formatted_date . ".";
                $stmt_insert = $conn->prepare("INSERT INTO notificacoes (empresa_id, type, message, product_id) VALUES (?, 'nearing_expiration', ?, ?)");
                $stmt_insert->execute([$empresa_id, $message, $product['id']]);
            }
            // Se já existe (mesmo que dispensada), não faz nada.
        }
    } catch (PDOException $e) {
        error_log("Erro na automação de produtos perto do vencimento: " . $e->getMessage());
    }

}

?>
