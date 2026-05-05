<?php
// views/widgets/financeiro_kpis.php
// Requer $kpis, $total_profit_period, $total_sales_period, $forecast_data
?>
<!-- Revenue -->
<div class="col-12 col-md-6 col-xl-6" data-id="financeiro_revenue">
    <div class="card card-dashboard h-100 p-4 position-relative overflow-hidden">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="kpi-label">Receita Mensal</div>
            <div class="icon-shape bg-primary-subtle text-primary rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
        <div class="kpi-value mb-3" id="kpiRevenueMonth">R$ <?php echo number_format($kpis['revenue_month']['current'] ?? 0, 2, ',', '.'); ?></div>
        <div class="d-flex align-items-center gap-2">
            <?php 
            $change = $kpis['revenue_month']['change'] ?? 0;
            $trendClass = $change >= 0 ? 'trend-up' : 'trend-down';
            $trendIcon = $change >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
            ?>
            <span class="trend-badge <?php echo $trendClass; ?>">
                <i class="fas <?php echo $trendIcon; ?>"></i> <?php echo abs(round($change)); ?>%
            </span>
            <span class="text-muted small">vs mês anterior</span>
        </div>
    </div>
</div>

<!-- Profit & Forecast -->
<div class="col-12 col-md-6 col-xl-6" data-id="financeiro_profit">
    <div class="d-flex flex-column gap-3 gap-md-4 h-100">
        <div class="card card-dashboard flex-grow-1 p-4 d-flex flex-row align-items-center justify-content-between">
            <div>
                <div class="kpi-label text-success">Lucro Líquido</div>
                <div class="fs-3 fw-bold text-main" id="kpiProfit">R$ <?php echo number_format($total_profit_period ?? 0, 2, ',', '.'); ?></div>
            </div>
            <div class="text-end">
                <div class="kpi-label">Margem</div>
                <div class="fs-4 fw-bold text-primary" id="kpiMargin">
                    <?php echo ($total_sales_period ?? 0) > 0 ? number_format(($total_profit_period / $total_sales_period) * 100, 1) : 0; ?>%
                </div>
            </div>
        </div>
        <div class="card card-dashboard flex-grow-1 p-3 d-flex flex-row align-items-center gap-3">
            <div class="bg-warning-subtle text-warning rounded-3 p-3 d-flex align-items-center justify-content-center">
                <i class="fas fa-bolt fa-lg"></i>
            </div>
            <div>
                <div class="kpi-label mb-1">Previsão (7 dias)</div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fw-bold text-main fs-5" id="kpiForecastGrowth">
                        <?php echo ($forecast_data['avg_growth'] ?? 0) > 0 ? '+' : ''; ?><?php echo number_format($forecast_data['avg_growth'] ?? 0, 1); ?>%
                    </span>
                    <span class="text-muted small">crescimento diário</span>
                </div>
            </div>
        </div>
    </div>
</div>
