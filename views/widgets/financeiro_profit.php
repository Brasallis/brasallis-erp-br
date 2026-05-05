<?php
// views/widgets/financeiro_profit.php
// Requer $total_profit_period e $forecast_data
?>
<!-- Profit & Forecast -->
<div class="col-12 col-md-6 col-xl-4" data-id="financeiro_profit">
    <div class="d-flex flex-column gap-4 h-100">
        <div class="card card-dashboard flex-grow-1 p-4 d-flex flex-row align-items-center justify-content-between">
            <div>
                <div class="kpi-label mb-1">Lucro Mensal</div>
                <?php 
                    $profit_current = $kpis['profit_month']['current'] ?? 0;
                    $profit_change = $kpis['profit_month']['change'] ?? 0;
                ?>
                <h3 class="fw-bold mb-0 text-success">
                    R$ <?php echo number_format($profit_current, 2, ',', '.'); ?>
                </h3>
            </div>
            <div class="text-end">
                <span class="badge <?php echo $profit_change >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> rounded-pill px-2">
                    <?php echo ($profit_change >= 0 ? '+' : '') . number_format($profit_change, 1) . '%'; ?>
                </span>
            </div>
        </div>
        
        <div class="card card-dashboard flex-grow-1 p-4 d-flex flex-row align-items-center justify-content-between">
            <div>
                <div class="kpi-label mb-1">Previsão de Lucro (30d)</div>
                <h3 class="fw-bold mb-0 text-primary">
                    R$ <?php echo number_format($profit_current * 1.1, 2, ',', '.'); // Projeção simples de 10% baseada no real ?>
                </h3>
            </div>
            <div class="icon-circle bg-primary-subtle text-primary">
                <i class="fas fa-chart-line fa-lg"></i>
            </div>
        </div>
    </div>
</div>
