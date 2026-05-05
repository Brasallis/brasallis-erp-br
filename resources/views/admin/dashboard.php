<?php
/**
 * View: admin/dashboard
 * Estética: Apple Pure + Salesforce Insights
 */
$title = "Dashboard Estratégico";
require BASE_PATH . '/resources/views/layouts/header.php';

$revenue = $executiveKpis['revenue'];
$profit = $executiveKpis['profit'];
$operational = $executiveKpis['operational'];
$financial = $executiveKpis['financial'];

// Formatação amigável
$fmt = new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);
$formatMoney = fn($val) => $fmt->formatCurrency($val, 'BRL');
?>

<div style="background: #0A2647; color: #fff; padding: 10px; text-align: center; font-weight: bold; border-radius: 0 0 20px 20px; margin-bottom: 20px;">
    <i class="fas fa-check-circle me-2"></i> Modern Dashboard Estratégico Atualizado
</div>

<div class="dashboard-executive">
    <!-- 1. Header Estratégico -->
    <header class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h1 class="display-6 fw-bold text-navy mb-1">Olá, <?= explode(' ', $username)[0] ?>.</h1>
            <p class="text-secondary mb-0">Aqui está o que aconteceu na <span class="fw-semibold text-navy"><?= $empresa_nome ?></span> este mês.</p>
        </div>
        <div class="actions">
            <button class="btn btn-premium btn-dark px-4 py-2 shadow-sm rounded-pill">
                <i class="fas fa-plus me-2 small"></i> Nova Venda
            </button>
        </div>
    </header>

    <!-- 2. Executive Scorecard (Apple Style Cards) -->
    <div class="row g-4 mb-5">
        <!-- Faturamento -->
        <div class="col-md-3">
            <div class="card-executive shadow-sm p-4 border-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-circle bg-blue-soft text-primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span class="text-secondary fw-medium small">Faturamento</span>
                </div>
                <div class="h3 fw-bold text-navy mb-2"><?= $formatMoney($revenue['current']) ?></div>
                <div class="growth-indicator <?= $revenue['growth'] >= 0 ? 'text-success' : 'text-danger' ?> small fw-bold">
                    <i class="fas fa-arrow-<?= $revenue['growth'] >= 0 ? 'up' : 'down' ?> me-1"></i>
                    <?= number_format(abs($revenue['growth']), 1) ?>% <span class="text-secondary fw-normal ms-1">vs mês ant.</span>
                </div>
            </div>
        </div>

        <!-- Lucro Bruto -->
        <div class="col-md-3">
            <div class="card-executive shadow-sm p-4 border-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-circle bg-emerald-soft text-success">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <span class="text-secondary fw-medium small">Lucro Estimado</span>
                </div>
                <div class="h3 fw-bold text-navy mb-2"><?= $formatMoney($profit['value']) ?></div>
                <div class="small fw-semibold text-navy">
                    Margem: <span class="text-success"><?= number_format($profit['margin'], 1) ?>%</span>
                </div>
            </div>
        </div>

        <!-- Ticket Médio -->
        <div class="col-md-3">
            <div class="card-executive shadow-sm p-4 border-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-circle bg-purple-soft text-purple">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <span class="text-secondary fw-medium small">Ticket Médio</span>
                </div>
                <div class="h3 fw-bold text-navy mb-2"><?= $formatMoney($operational['avg_ticket']) ?></div>
                <p class="small text-secondary mb-0">Base: últimos 30 dias</p>
            </div>
        </div>

        <!-- Saúde de Estoque -->
        <div class="col-md-3">
            <div class="card-executive shadow-sm p-4 border-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-circle bg-orange-soft text-warning">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <span class="text-secondary fw-medium small">Alertas Estoque</span>
                </div>
                <div class="h3 fw-bold text-navy mb-2"><?= $operational['low_stock'] ?> <span class="fs-6 text-secondary fw-normal">itens</span></div>
                <a href="/estoque/produtos?filter=low" class="small text-warning fw-bold text-decoration-none">
                    Ver reposições necessárias <i class="fas fa-chevron-right ms-1 smaller"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. Insights & Analytics -->
    <div class="row g-4">
        <!-- Gráfico Principal -->
        <div class="col-lg-8">
            <div class="card-executive shadow-sm border-0 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-navy mb-0">Performance de Vendas</h5>
                    <div class="btn-group btn-group-sm rounded-pill p-1 bg-light">
                        <button class="btn btn-white shadow-sm rounded-pill px-3 active">Receita</button>
                        <button class="btn btn-light rounded-pill px-3">Pedidos</button>
                    </div>
                </div>
                <div style="height: 300px;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Saúde Financeira -->
        <div class="col-lg-4">
            <div class="card-executive shadow-sm border-0 p-4 h-100">
                <h5 class="fw-bold text-navy mb-4">Fluxo de Caixa Próximo</h5>
                
                <div class="financial-item mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Contas a Receber (Vencidas)</span>
                        <span class="text-danger fw-bold small"><?= $formatMoney($financial['overdue_receivables']) ?></span>
                    </div>
                    <div class="progress rounded-pill" style="height: 6px; background: #fee2e2;">
                        <div class="progress-bar bg-danger" style="width: 75%"></div>
                    </div>
                </div>

                <div class="financial-item mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Compromissos (Próx. 7 dias)</span>
                        <span class="text-navy fw-bold small"><?= $formatMoney($financial['upcoming_payables']) ?></span>
                    </div>
                    <div class="progress rounded-pill" style="height: 6px; background: #f1f5f9;">
                        <div class="progress-bar bg-navy" style="width: 40%"></div>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-4 mt-auto">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-circle bg-white shadow-sm text-primary">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-navy small">Insight Salesforce</div>
                            <p class="small text-secondary mb-0">Sua margem está 12% acima da média do setor.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --navy: #0A2647;
        --blue-soft: #e0f2fe;
        --emerald-soft: #dcfce7;
        --purple-soft: #f3e8ff;
        --orange-soft: #ffedd5;
        --purple: #9333ea;
    }

    .dashboard-executive { padding: 0.5rem; }
    
    .card-executive {
        background: #fff;
        border-radius: 24px;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-executive:hover { transform: translateY(-4px); }

    .icon-circle {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .bg-navy { background-color: var(--navy) !important; }
    .text-navy { color: var(--navy) !important; }
    .bg-blue-soft { background-color: var(--blue-soft); }
    .bg-emerald-soft { background-color: var(--emerald-soft); }
    .bg-purple-soft { background-color: var(--purple-soft); }
    .bg-orange-soft { background-color: var(--orange-soft); }
    .text-purple { color: var(--purple); }

    .btn-white { background: #fff; border: 1px solid #e2e8f0; color: var(--navy); }
    .smaller { font-size: 0.7rem; }
    .smaller-text { font-size: 0.8rem; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('mainChart').getContext('2d');
    
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.1)');
    gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $chart_labels ?>,
            datasets: [{
                label: 'Receita',
                data: <?= $chart_sales ?>,
                borderColor: '#2563eb',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                borderWidth: 4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#2563eb',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#0A2647',
                    bodyColor: '#64748B',
                    borderColor: '#E2E8F0',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'R$ ' + context.parsed.y.toLocaleString('pt-BR');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { color: '#94A3B8', font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94A3B8', font: { size: 11 } }
                }
            }
        }
    });
});
</script>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>

