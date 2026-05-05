<?php
/**
 * View: estoque/compras/index
 */
$title = "Entradas de Estoque";
require BASE_PATH . '/resources/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1">Compras e Entradas</h2>
        <p class="text-secondary mb-0">Acompanhe as entradas de mercadorias no seu estoque.</p>
    </div>
    <a href="/estoque/compras/create" class="btn btn-premium btn-dark shadow-sm">
        <i class="fas fa-file-import me-2"></i>Nova Entrada
    </a>
</div>

<div class="card-premium border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-secondary small text-uppercase fw-bold">
                <tr>
                    <th class="ps-4">ID / Data</th>
                    <th>Fornecedor</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($compras)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">Nenhuma compra registrada.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($compras as $c): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-navy">#<?= $c['id'] ?></div>
                                <div class="small text-muted"><?= date('d/m/Y', strtotime($c['purchase_date'])) ?></div>
                            </td>
                            <td><?= htmlspecialchars($c['fornecedor_nome'] ?? 'N/A') ?></td>
                            <td class="fw-bold">R$ <?= number_format($c['total_amount'], 2, ',', '.') ?></td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    Concluída
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="/estoque/compras/<?= $c['id'] ?>" class="btn btn-sm btn-light border text-navy">
                                    <i class="fas fa-eye me-1"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
