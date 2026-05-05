<?php
/**
 * View: admin/dashboard (Legacy Path)
 * Estética: Apple Pure + Salesforce Insights
 * Adaptado para variáveis do repositório legado.
 */
include_once __DIR__ . '/../../includes/cabecalho.php';

// Formatação amigável
$formatMoney = fn($val) => 'R$ ' . number_format($val ?? 0, 2, ',', '.');
$username = $_SESSION['username'] ?? 'Usuário';
$empresa_nome = $_SESSION['empresa_nome'] ?? 'Gestão Brasallis';

// Mapeamento de variáveis legadas
$revenue_val = $metas_exec['vendas']['atual'] ?? 0;
$revenue_growth = $kpis['revenue_month']['change'] ?? 0;
$profit_val = ($revenue_val * ($exec_health['margem_lucro'] ?? 0) / 100);
$margin_val = $exec_health['margem_lucro'] ?? 0;
$ticket_medio = $exec_health['ticket_medio'] ?? 0;
$low_stock = $kpis['low_stock_items']['current'] ?? 0;
$overdue_receivables = $fin_kpis['receivables']['overdue'] ?? 0;
$upcoming_payables = $fin_kpis['payables_next_7_days'] ?? 0;

// Novos Datasets para Gráficos v3.0
$weekly_labels = json_encode($weekly_data['labels'] ?? ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom']);
$weekly_values = json_encode($weekly_data['data'] ?? [0,0,0,0,0,0,0]);
$cross_labels = json_encode(array_column($cross_data ?? [], 'month'));
$cross_revenue = json_encode(array_map(fn($v) => (float)$v, array_column($cross_data ?? [], 'revenue')));
$cross_expenses = json_encode(array_map(fn($v) => (float)$v, array_column($cross_data ?? [], 'expenses')));
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<div class="page-container">

    <div class="page-header">
        <div class="page-title-group">
            <h1>Olá, <?= explode(' ', $username)[0] ?>.</h1>
            <p>Resumo estratégico para <span class="fw-bold text-navy"><?= htmlspecialchars($empresa_nome) ?></span>.</p>
        </div>
        <div class="actions">
            <button class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-bold d-none d-md-block" onclick="window.location.href='registrar_compra.php'">
                <i class="fas fa-plus me-2"></i> Nova Entrada (Nota Fiscal)
            </button>
        </div>
    </div>

    <!-- Mobile FAB -->
    <button class="btn btn-primary rounded-pill shadow-lg d-md-none position-fixed" 
            style="bottom: 100px; right: 24px; width: 60px; height: 60px; z-index: 2000;" 
            onclick="window.location.href='registrar_compra.php'">
        <i class="fas fa-plus fa-lg"></i>
    </button>

    <!-- 2. Executive Scorecard -->
    <div class="row g-4 mb-4">
        <!-- Faturamento -->
        <div class="col-md-3">
            <div class="section-card p-4 mb-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-circle bg-blue-soft text-primary">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span class="text-muted fw-bold small text-uppercase">Faturamento</span>
                </div>
                <div class="h3 fw-bold text-navy mb-2"><?= $formatMoney($revenue_val) ?></div>
                <div class="growth-indicator <?= $revenue_growth >= 0 ? 'text-success' : 'text-danger' ?> small fw-bold">
                    <i class="fas fa-arrow-<?= $revenue_growth >= 0 ? 'up' : 'down' ?> me-1"></i>
                    <?= number_format(abs($revenue_growth), 1) ?>% <span class="text-muted fw-normal ms-1">vs mês ant.</span>
                </div>
            </div>
        </div>

        <!-- Lucro Bruto -->
        <div class="col-md-3">
            <div class="section-card p-4 mb-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-circle bg-emerald-soft text-success">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <span class="text-muted fw-bold small text-uppercase">Lucro Estimado</span>
                </div>
                <div class="h3 fw-bold text-navy mb-2"><?= $formatMoney($profit_val) ?></div>
                <div class="small fw-bold text-navy">
                    Margem: <span class="text-success"><?= number_format($margin_val, 1) ?>%</span>
                </div>
            </div>
        </div>

        <!-- Ticket Médio -->
        <div class="col-md-3">
            <div class="section-card p-4 mb-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-circle bg-purple-soft text-purple">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <span class="text-muted fw-bold small text-uppercase">Ticket Médio</span>
                </div>
                <div class="h3 fw-bold text-navy mb-2"><?= $formatMoney($ticket_medio) ?></div>
                <p class="small text-muted mb-0">Base: últimos 30 dias</p>
            </div>
        </div>

        <!-- Saúde de Estoque -->
        <div class="col-md-3">
            <div class="section-card p-4 mb-0">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-circle bg-orange-soft text-warning">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <span class="text-muted fw-bold small text-uppercase">Alertas Estoque</span>
                </div>
                <div class="h3 fw-bold text-navy mb-2"><?= $low_stock ?> <span class="fs-6 text-muted fw-normal">itens</span></div>
                <a href="produtos.php" class="small text-warning fw-bold text-decoration-none">
                    Ver reposições <i class="fas fa-chevron-right ms-1 smaller"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. ANÁLISE GRÁFICA AVANÇADA (Novo) -->
    <div class="row g-4 mb-4">
        <!-- Gráfico Semanal -->
        <div class="col-lg-7">
            <div class="section-card p-4 h-100 mb-0 border-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold text-navy mb-1">Performance por Dia</h5>
                        <p class="small text-muted mb-0">Volume de vendas nos últimos 7 dias</p>
                    </div>
                    <div class="badge bg-light text-navy px-3 py-2 rounded-pill border">Semanal</div>
                </div>
                <div style="height: 300px;">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Análise Cruzada -->
        <div class="col-lg-5">
            <div class="section-card p-4 h-100 mb-0 border-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold text-navy mb-1">Vendas vs Compras</h5>
                        <p class="small text-muted mb-0">Equilíbrio de caixa (6 meses)</p>
                    </div>
                </div>
                <div style="height: 300px;">
                    <canvas id="crossChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. INFORMAÇÕES DIFERENCIADAS BRASALLIS -->
    <div class="row g-4 mb-5">
        <!-- Eficiência Operacional -->
        <div class="col-md-4">
            <div class="section-card p-4 h-100 mb-0 border-0">
                <h6 class="fw-bold text-navy mb-4 text-uppercase small opacity-50">Eficiência Operacional</h6>
                <div class="d-flex flex-column gap-4">
                    <div class="efficiency-item">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small fw-bold">Giro de Estoque</span>
                            <span class="small text-primary fw-bold"><?= $efficiency_data['giro_estoque'] ?>%</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: <?= $efficiency_data['giro_estoque'] ?>%"></div>
                        </div>
                    </div>
                    <div class="efficiency-item">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small fw-bold">Aproveitamento AI</span>
                            <span class="small text-success fw-bold"><?= $efficiency_data['ai_usage'] ?>%</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: <?= $efficiency_data['ai_usage'] ?>%"></div>
                        </div>
                    </div>
                    <div class="efficiency-item">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small fw-bold">Saúde Financeira</span>
                            <span class="small text-warning fw-bold"><?= $efficiency_data['finance_health'] ?>%</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: <?= $efficiency_data['finance_health'] ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insights Estratégicos -->
        <div class="col-md-8">
            <div class="section-card p-4 h-100 mb-0 border-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold text-navy mb-0 text-uppercase small opacity-50">Insights Estratégicos</h6>
                    <span class="small text-muted"><i class="fas fa-robot me-2"></i>Brasallis Brain v2.5</span>
                </div>
                <div class="row g-3">
                    <?php foreach(array_slice($insights ?? [], 0, 2) as $insight): ?>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-4 border h-100">
                            <div class="d-flex gap-3">
                                <div class="icon-circle bg-white shadow-sm text-primary"><i class="fas <?= $insight['icon'] ?? 'fa-bolt' ?>"></i></div>
                                <div>
                                    <div class="fw-bold text-navy small mb-1"><?= $insight['title'] ?? 'Oportunidade' ?></div>
                                    <p class="small text-muted mb-0"><?= $insight['description'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 p-3 bg-blue-soft rounded-4 border border-primary border-opacity-10 d-flex align-items-center justify-content-between">
                    <div class="small text-navy fw-bold">
                        <i class="fas fa-star me-2 text-primary"></i> 
                        Produto Estrela: <span class="text-primary"><?= $top_produtos[0]['name'] ?? 'Nenhum' ?></span>
                    </div>
                    <a href="relatorios.php" class="btn btn-primary btn-sm rounded-pill px-3">Análise BI</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --blue-soft: #e0f2fe;
        --emerald-soft: #dcfce7;
        --purple-soft: #f3e8ff;
        --orange-soft: #ffedd5;
        --purple: #9333ea;
    }

    .icon-circle {
        width: 44px;
        height: 44px;
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
    .progress-bar { transition: width 1s ease-in-out; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Gráfico Semanal (Barras)
    const ctxWeekly = document.getElementById('weeklyChart');
    if (ctxWeekly) {
        new Chart(ctxWeekly.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= $weekly_labels ?>,
                datasets: [{
                    label: 'Faturamento',
                    data: <?= $weekly_values ?>,
                    backgroundColor: '#0070F2',
                    borderRadius: 8,
                    hoverBackgroundColor: '#0056b3'
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' } },
                    x: { border: { display: false }, grid: { display: false } }
                }
            }
        });
    }

    // 2. Gráfico Cruzado (Linhas)
    const ctxCross = document.getElementById('crossChart');
    if (ctxCross) {
        new Chart(ctxCross.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= $cross_labels ?>,
                datasets: [
                    {
                        label: 'Vendas',
                        data: <?= $cross_revenue ?>,
                        borderColor: '#0070F2',
                        backgroundColor: 'rgba(0, 112, 242, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4
                    },
                    {
                        label: 'Compras',
                        data: <?= $cross_expenses ?>,
                        borderColor: '#f43f5e',
                        backgroundColor: 'rgba(244, 63, 94, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6 } } },
                scales: {
                    y: { border: { display: false }, grid: { color: '#f1f5f9' } },
                    x: { border: { display: false }, grid: { display: false } }
                }
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/../../includes/rodape.php'; ?>
