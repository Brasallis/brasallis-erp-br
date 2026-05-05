<?php
// views/widgets/financeiro_revenue.php
// Requer $kpis e $total_sales_period
?>
<!-- Revenue -->
<div class="col-12 col-md-6 col-xl-4" data-id="financeiro_revenue">
    <div class="card card-dashboard h-100 p-4 position-relative overflow-hidden">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="kpi-label">Receita Mensal</div>
            <?php 
                $rev_change = $kpis['revenue_month']['change'] ?? 0;
                $rev_current = $kpis['revenue_month']['current'] ?? 0;
                $rev_target = 50000; // Meta sugerida ou vinda de config
                $rev_progress = ($rev_current / $rev_target) * 100;
                $rev_progress = min(max($rev_progress, 0), 100);
            ?>
            <span class="badge <?php echo $rev_change >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> rounded-pill px-3">
                <?php echo ($rev_change >= 0 ? '+' : '') . number_format($rev_change, 1) . '%'; ?>
            </span>
        </div>
        <h2 class="display-6 fw-bold mb-3">R$ <?php echo number_format($rev_current, 2, ',', '.'); ?></h2>
        
        <div class="progress mb-3" style="height: 6px;">
            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $rev_progress; ?>%"></div>
        </div>
        <div class="d-flex justify-content-between text-muted small">
            <span>Meta: R$ <?php echo number_format($rev_target, 0, ',', '.'); ?></span>
            <span><?php echo round($rev_progress); ?>%</span>
        </div>

        <!-- Mini Chart (Static SVG) -->
        <div class="position-absolute bottom-0 end-0 opacity-10 mb-n2 me-n2">
            <svg width="120" height="60" viewBox="0 0 120 60" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M0 60 C30 60, 30 20, 60 20 C90 20, 90 40, 120 10" />
            </svg>
        </div>
    </div>
</div>
