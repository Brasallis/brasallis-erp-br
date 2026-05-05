<?php
// modules/pdv/views/invoice_view.php
require_once __DIR__ . '/../../../includes/db_config.php';
require_once __DIR__ . '/../../../includes/funcoes.php';

session_start();
if (!isset($_SESSION['user_id'])) { die("Acesso negado."); }

$venda_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$venda_id) die("Venda não encontrada.");

$conn = connect_db();

// 1. Fetch Sale Details
$stmt = $conn->prepare("
    SELECT v.*, u.username as vendedor, e.name as empresa_nome
    FROM vendas v
    JOIN usuarios u ON v.user_id = u.id
    JOIN empresas e ON v.empresa_id = e.id
    WHERE v.id = ? AND v.empresa_id = ?
");
$stmt->execute([$venda_id, $_SESSION['empresa_id']]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venda) die("Venda não encontrada ou acesso negado.");

// 2. Fetch Items
$stmtItems = $conn->prepare("
    SELECT vi.*, p.name, p.sku, p.unidade_medida as unit
    FROM venda_itens vi
    JOIN produtos p ON vi.product_id = p.id
    WHERE vi.venda_id = ?
");
$stmtItems->execute([$venda_id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// 3. Tax Calculation (Simplified Estimation for "Padrão")
// In a real scenario, this would come from NCM/Product fields.
// Using fixed rates for demonstration based on Simples Nacional + IBPT average.
$imposto_federal_rate = 0.04; // 4% Est
$imposto_estadual_rate = 0.12; // 12% Est
$imposto_total = 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota #<?= $venda_id ?></title>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            @page { margin: 0; }
            .no-print { display: none; }
        }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 12px; 
            width: 80mm; 
            margin: auto; 
            background: #fff;
            color: #000;
        }
        .header, .footer { text-align: center; margin-bottom: 10px; }
        .separator { border-bottom: 1px dashed #000; margin: 5px 0; }
        .item-row { display: flex; justify-content: space-between; }
        .totals { margin-top: 10px; text-align: right; }
        .details { font-size: 10px; }
        .btn-print { 
            display: block; width: 100%; padding: 10px; 
            background: #000; color: #fff; text-align: center; 
            text-decoration: none; font-family: sans-serif; font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <a href="#" onclick="window.print(); return false;" class="btn-print no-print">IMPRIMIR (CTRL+P)</a>

    <div class="header">
        <strong><?= htmlspecialchars($venda['empresa_nome']) ?></strong><br>
        <span class="details">CNPJ: 00.000.000/0000-00 (Simulado)</span><br>
        <span class="details"><?= date('d/m/Y H:i:s', strtotime($venda['created_at'])) ?></span><br>
        <strong>CUPOM NÃO FISCAL</strong><br>
        Venda #: <?= str_pad((string)$venda['id'], 6, '0', STR_PAD_LEFT) ?>
    </div>

    <div class="separator"></div>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left;">
                <th colspan="2">ITEM</th>
            </tr>
            <tr style="text-align: right; font-size: 10px;">
                <th style="text-align: left;">QTD x UNIT</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): 
                $subtotal = $item['quantity'] * $item['unit_price'];
                $imposto_total += $subtotal * ($imposto_federal_rate + $imposto_estadual_rate);
            ?>
            <tr>
                <td colspan="2"><?= htmlspecialchars($item['name']) ?></td>
            </tr>
            <tr style="text-align: right;">
                <td style="text-align: left;"><?= $item['quantity'] ?> x <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                <td><?= number_format($subtotal, 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="separator"></div>

    <div class="totals">
        <strong>TOTAL: R$ <?= number_format($venda['total_amount'], 2, ',', '.') ?></strong><br>
        <small>Forma de Pagto: <?= ucfirst(str_replace('_', ' ', $venda['payment_method'])) ?></small>
    </div>

    <div class="separator"></div>

    <div class="footer details">
        Trib. Aprox. R$: <?= number_format($imposto_total, 2, ',', '.') ?> (<?= number_format(($imposto_federal_rate+$imposto_estadual_rate)*100, 2) ?>%)<br>
        Fonte: IBPT (Simulado)<br>
        Vendedor: <?= htmlspecialchars($venda['vendedor']) ?><br>
        <br>
        Obrigado pela preferência!
    </div>

    <script>
        // Auto-print prompt
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
