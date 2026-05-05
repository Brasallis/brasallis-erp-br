<?php
// views/widgets/estoque_saude.php
// Requer $kpis
?>
<div class="col-12 col-xl-4" data-id="estoque_saude">
    <div class="card card-dashboard h-100 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="kpi-label mb-0">Saúde do Estoque</div>
            <span class="badge bg-secondary-subtle text-secondary border-0">Score: 98/100</span>
        </div>
        <div class="d-flex flex-column gap-4">
            <a href="produtos.php?filter=low_stock" class="text-decoration-none">
                <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-danger-subtle border border-danger-subtle transition-hover">
                    <i class="fas fa-exclamation-triangle text-danger fa-lg"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold text-danger">Estoque Crítico</h6>
                        <small class="text-danger-emphasis"><?php echo $kpis['low_stock_items']['current'] ?? 0; ?> produtos precisam de reposição</small>
                    </div>
                    <i class="fas fa-chevron-right text-danger opacity-50"></i>
                </div>
            </a>
            <a href="compras.php?filter=pending_review" class="text-decoration-none">
                <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-warning-subtle border border-warning-subtle transition-hover">
                    <i class="fas fa-clipboard-list text-warning fa-lg"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold text-warning-emphasis">Compras Pendentes</h6>
                        <small class="text-warning-emphasis"><?php echo $kpis['pending_review_purchases']['current'] ?? 0; ?> aguardando aprovação</small>
                    </div>
                    <i class="fas fa-chevron-right text-warning opacity-50"></i>
                </div>
            </a>
        </div>
    </div>
</div>
