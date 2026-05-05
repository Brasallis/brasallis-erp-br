<?php
// employee/imprimir_venda.php - FORMATO EXTRATO FISCAL (SAT/NFC-e)
session_start();
require_once '../includes/funcoes.php';

$venda_id = $_GET['id'] ?? null;
if (!$venda_id) { exit("Venda não encontrada."); }

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Dados da Venda e Cliente
$stmt = $conn->prepare("
    SELECT v.*, u.username, c.nome as cliente_nome, c.cpf_cnpj as cliente_documento 
    FROM vendas v 
    JOIN usuarios u ON v.user_id = u.id 
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = ? AND v.empresa_id = ?
");
$stmt->execute([$venda_id, $empresa_id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

// Itens
$stmt = $conn->prepare("SELECT vi.*, p.name, p.sku FROM venda_itens vi JOIN produtos p ON vi.product_id = p.id WHERE vi.venda_id = ?");
$stmt->execute([$venda_id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagamentos
$stmt = $conn->prepare("SELECT * FROM venda_pagamentos WHERE venda_id = ?");
$stmt->execute([$venda_id]);
$pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dados da Empresa
$stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->execute([$empresa_id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

$chave = date('Ym', strtotime($venda['created_at'])) . str_pad((string)$emp['id'], 14, '0', STR_PAD_LEFT) . "65" . str_pad((string)$venda['id'], 9, '0', STR_PAD_LEFT) . "1" . str_pad((string)rand(1000,9999), 8, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Extrato #<?php echo $venda_id; ?></title>
    <style>
        /* Estilo Extrato Fiscal */
        @page { size: 80mm auto; margin: 0; }
        body { 
            width: 72mm; /* Área útil real */
            margin: 0; 
            padding: 2mm 4mm; 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 10px; 
            color: #000;
            background: #fff;
            line-height: 1.1;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .fs-large { font-size: 12px; }
        .divider { border-top: 1px dashed #000; margin: 2mm 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 0.5mm 0; }
        
        .qr-area {
            width: 30mm;
            height: 30mm;
            border: 1px solid #000;
            margin: 3mm auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
        }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body onload="window.print();">

    <div class="text-center">
        <div class="fw-bold fs-large"><?php echo strtoupper(htmlspecialchars($emp['razao_social'] ?? 'BRASALLIS HUB')); ?></div>
        <div>CNPJ:<?php echo $emp['cnpj'] ?? '00.000.000/0001-00'; ?></div>
        <div>IE:<?php echo $emp['inscricao_estadual'] ?? 'ISENTO'; ?></div>
        <div><?php echo htmlspecialchars($emp['endereco'] ?? 'RUA BRASALLIS, 2026 - CENTRO'); ?></div>
        <div class="divider"></div>
        <div class="fw-bold">EXTRATO No. <?php echo str_pad((string)$venda['id'], 6, '0', STR_PAD_LEFT); ?></div>
        <div class="fw-bold">CUPOM FISCAL ELETRONICO - SAT</div>
    </div>

    <div class="divider"></div>

    <table style="font-size: 9px;">
        <thead>
            <tr class="fw-bold">
                <th align="left">#|COD|DESC|QTD|UN|VL UN|VL TOT</th>
            </tr>
        </thead>
        <tbody>
            <?php $count = 1; foreach($itens as $i): ?>
            <tr>
                <td>
                    <?php echo str_pad((string)$count++, 3, '0', STR_PAD_LEFT); ?> 
                    <?php echo str_pad((string)$i['sku'] ?? '00', 6, '0', STR_PAD_LEFT); ?> 
                    <?php echo strtoupper(substr(htmlspecialchars($i['name']), 0, 20)); ?>...<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $i['quantity']; ?> UN X <?php echo number_format($i['unit_price'], 2, ',', '.'); ?> = <?php echo number_format($i['unit_price'] * $i['quantity'], 2, ',', '.'); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="fw-bold fs-large" style="display: flex; justify-content: space-between;">
        <span>TOTAL R$</span>
        <span><?php echo number_format($venda['total_amount'], 2, ',', '.'); ?></span>
    </div>

    <div class="divider"></div>

    <div class="fw-bold">PAGAMENTOS</div>
    <?php foreach($pagamentos as $p): ?>
    <div style="display: flex; justify-content: space-between;">
        <span><?php echo strtoupper(str_replace('_', ' ', $p['metodo_pagamento'])); ?></span>
        <span><?php echo number_format($p['valor'], 2, ',', '.'); ?></span>
    </div>
    <?php endforeach; ?>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 8px;">
        * Valor aproximado dos tributos do item<br>
        (Lei Federal 12.741/2012) - R$ <?php echo number_format($venda['total_amount'] * 0.18, 2, ',', '.'); ?>
    </div>

    <div class="divider"></div>

    <div class="text-center">
        <div class="fw-bold">S A T</div>
        <div class="fw-bold">Extrato No. <?php echo str_pad((string)$venda['id'], 6, '0', STR_PAD_LEFT); ?></div>
        <div><?php echo date('d/m/Y - H:i:s', strtotime($venda['created_at'])); ?></div>
        
        <div style="font-size: 8px; margin-top: 2mm; word-break: break-all;">
            <?php echo implode(' ', str_split($chave, 4)); ?>
        </div>

        <div class="qr-area">
            QR CODE SAT
        </div>
    </div>

    <div class="divider"></div>

    <div class="text-center fw-bold">
        <?php if (!empty($venda['cliente_documento'])): ?>
            CONSUMIDOR: <?php echo htmlspecialchars($venda['cliente_nome']); ?><br>
            CPF/CNPJ: <?php echo htmlspecialchars($venda['cliente_documento']); ?>
        <?php else: ?>
            CONSUMIDOR NAO IDENTIFICADO
        <?php endif; ?>
    </div>

</body>
</html>
