<?php
/**
 * View: estoque/compras/show
 */
$title = "Detalhes da Compra #" . $compra['id'];
require BASE_PATH . '/resources/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1">Detalhes da Compra #<?= $compra['id'] ?></h2>
        <p class="text-secondary mb-0">Visualização do pedido e itens recebidos.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-premium btn-outline-dark" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Imprimir
        </button>
        <a href="/estoque/compras" class="btn btn-premium btn-dark shadow-sm">
            <i class="fas fa-arrow-left me-2"></i>Voltar para Lista
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Resumo lateral -->
    <div class="col-lg-4">
        <div class="card-premium border-0 p-4 shadow-sm mb-4 h-100">
            <h5 class="fw-bold text-navy mb-4">Informações Gerais</h5>
            
            <div class="mb-3">
                <label class="small text-muted text-uppercase fw-bold d-block">Fornecedor</label>
                <div class="fw-bold text-navy h6 mb-1"><?= htmlspecialchars($compra['fornecedor_nome'] ?? 'N/A') ?></div>
                <div class="small text-muted"><?= htmlspecialchars($compra['fornecedor_cnpj'] ?? 'CNPJ não informado') ?></div>
            </div>

            <div class="mb-3">
                <label class="small text-muted text-uppercase fw-bold d-block">Data da Entrada</label>
                <div class="fw-bold text-navy h6"><?= date('d/m/Y', strtotime($compra['purchase_date'])) ?></div>
            </div>

            <div class="mb-3">
                <label class="small text-muted text-uppercase fw-bold d-block">Status</label>
                <span class="badge bg-success bg-opacity-10 text-success p-2 px-3">Confirmado / Entregue</span>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total de Itens</span>
                <span class="fw-bold"><?= count($compra['items']) ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="h5 fw-bold text-navy mb-0">Total Pago</span>
                <span class="h5 fw-bold text-success mb-0">R$ <?= number_format($compra['total_amount'], 2, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <!-- Lista de Itens -->
    <div class="col-lg-8">
        <div class="card-premium border-0 p-4 shadow-sm h-100">
            <h5 class="fw-bold text-navy mb-4">Produtos Recebidos</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4">Item / SKU</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-center">V. Unitário</th>
                            <th class="text-end pe-4">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php foreach ($compra['items'] as $item): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-navy"><?= htmlspecialchars($item['produto_nome']) ?></div>
                                    <div class="small text-muted">SKU: <?= htmlspecialchars($item['produto_sku'] ?? '-') ?></div>
                                </td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-center">R$ <?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                                <td class="text-end pe-4 fw-bold text-navy">R$ <?= number_format($item['quantity'] * $item['unit_price'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
