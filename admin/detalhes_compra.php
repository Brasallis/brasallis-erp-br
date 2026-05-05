<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';

if (!isset($_GET['id'])) {
    header("Location: compras.php");
    exit;
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$purchase_id = $_GET['id'];

// Buscar dados da compra
$stmt = $conn->prepare("
    SELECT c.*, f.name as supplier_name, u.username as user_name, dnf.status as ai_status
    FROM compras c
    LEFT JOIN fornecedores f ON c.supplier_id = f.id
    LEFT JOIN usuarios u ON c.user_id = u.id
    LEFT JOIN dados_nota_fiscal dnf ON c.id = dnf.compra_id
    WHERE c.id = ? AND c.empresa_id = ?
");
$stmt->execute([$purchase_id, $empresa_id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    die("Compra não encontrada.");
}

// Buscar itens da compra
$items_stmt = $conn->prepare("
    SELECT ic.*, p.name as product_name, p.sku
    FROM itens_compra ic
    JOIN produtos p ON ic.product_id = p.id
    WHERE ic.purchase_id = ?
");
$items_stmt->execute([$purchase_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/cabecalho.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detalhes da Compra #<?= $purchase['id'] ?></h1>
        <a href="compras.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Detalhes da Compra -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Itens da Compra</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th>SKU</th>
                                    <th>Estoque Anterior</th>
                                    <th>Qtd Entrada</th>
                                    <th>Custo Unit.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($item['sku'] ?? '-') ?></td>
                                        <td>
                                            <?php if (isset($item['stock_at_purchase'])): ?>
                                                <span class="badge bg-secondary"><?= $item['stock_at_purchase'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-success fw-bold">+<?= $item['quantity'] ?></td>
                                        <td>R$ <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($item['quantity'] * $item['unit_price'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Total da Compra:</th>
                                    <th class="text-primary">R$ <?= number_format($purchase['total_amount'], 2, ',', '.') ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações Laterais -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informações</h6>
                </div>
                <div class="card-body">
                    <p><strong>Fornecedor:</strong> <?= htmlspecialchars($purchase['supplier_name']) ?></p>
                    <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($purchase['purchase_date'])) ?></p>
                    <p><strong>Registrado por:</strong> <?= htmlspecialchars($purchase['user_name']) ?></p>
                    <p><strong>Status IA:</strong> <span class="badge bg-info"><?= $purchase['ai_status'] ?? 'Não enviado' ?></span></p>
                    
                    <?php if ($purchase['fiscal_note_path']): ?>
                        <hr>
                        <h6 class="font-weight-bold">Nota Fiscal</h6>
                        <div class="text-center mt-3">
                            <a href="ver_nota.php?id=<?= $purchase['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-file-invoice me-2"></i>Visualizar Nota
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/rodape.php'; ?>
