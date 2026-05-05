<?php
include_once '../includes/cabecalho.php';
require_once '../vendor/autoload.php';

use App\Repository\DashboardRepository;
use App\Core\Database;

if (!isset($_SESSION['empresa_id'])) {
    header('Location: ../login.php');
    exit;
}
$empresa_id = $_SESSION['empresa_id'];
$dashboardRepo = new DashboardRepository(Database::getInstance(), $empresa_id);

// --- FILTROS GERAIS ---
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$activeTab = $_GET['tab'] ?? 'financeiro';

// --- DADOS POR ABA ---

// 1. FINANCEIRO
$financialSummary = $dashboardRepo->getFinancialSummary($startDate, $endDate);
$sales_profit_data = $dashboardRepo->getSalesAndProfitOverTime('month', $startDate, $endDate);
$sales_profit_labels = array_column($sales_profit_data, 'label');
$sales_data = array_column($sales_profit_data, 'sales');
$profit_data = array_column($sales_profit_data, 'profit');
$payment_data = $dashboardRepo->getPaymentMethodDistribution($startDate, $endDate);
$payment_labels = array_map('ucfirst', array_column($payment_data, 'payment_method'));
$payment_counts = array_column($payment_data, 'count');

// 2. PRODUTOS
$top_products_data = $dashboardRepo->getTopSellingProducts(10, $startDate, $endDate);
$top_products_labels = array_reverse(array_column($top_products_data, 'name'));
$top_products_counts = array_reverse(array_column($top_products_data, 'total_quantity_sold'));
$top_profit_data = $dashboardRepo->getTopProfitableProducts(10, $startDate, $endDate);
$top_profit_labels = array_reverse(array_column($top_profit_data, 'name'));
$top_profit_values = array_reverse(array_column($top_profit_data, 'total_profit'));
$category_data = $dashboardRepo->getCategoryDistribution();
$category_labels = array_map('ucfirst', array_column($category_data, 'nome'));
$category_counts = array_column($category_data, 'count');

// 3. FUNCIONÁRIOS
$sellers_data = $dashboardRepo->getSalesBySeller($startDate, $endDate);
$seller_labels = array_column($sellers_data, 'username');
$seller_revenue = array_column($sellers_data, 'total_revenue');
$seller_count = array_column($sellers_data, 'total_sales_count');

// 4. CONTABILIDADE
// 4. CONTABILIDADE
$supplier_purchase_data = $dashboardRepo->getPurchaseBySupplier($startDate, $endDate);
$supplier_labels = array_column($supplier_purchase_data, 'name');
$supplier_data = array_column($supplier_purchase_data, 'total_purchased');
$total_purchased = array_sum($supplier_data);

// 5. MOVIMENTAÇÕES (NOVO)
$stock_stats = $dashboardRepo->getStockMovementStats($startDate, $endDate);
$stock_labels = array_map('ucfirst', array_column($stock_stats, 'action'));
$stock_values = array_column($stock_stats, 'total_quantity');
$stock_counts = array_column($stock_stats, 'total_moves');

// 6. INSIGHTS (NOVO)
$abc_data = $dashboardRepo->getProductABCAnalysis($startDate, $endDate);
$hourly_sales = $dashboardRepo->getSalesByHour($startDate, $endDate);
$hourly_labels = array_column($hourly_sales, 'hour');
$hourly_values = array_column($hourly_sales, 'count');

// 7. MÓDULOS (NOVO)
$module_stats = $dashboardRepo->getModuleActivityStats($startDate, $endDate);
$module_labels = array_keys($module_stats);
$module_values = array_values($module_stats);

?>

<style>
    /* Card Customizado para manter consistência com o resto do admin se necessário, 
       mas removendo overrides globais de fonte/body */
    .card-custom {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* Padrão do sistema observado no header */
        background: #fff;
        height: 100%;
        transition: transform 0.2s;
    }
    .card-custom:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }

    .kpi-card { padding: 1.5rem; position: relative; overflow: hidden; }
    .kpi-label { font-size: 0.85rem; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; }
    .kpi-value { font-size: 2rem; font-weight: 700; color: #343a40; }
    
    /* Cores de borda esquerda - Utilizando cores do Bootstrap se possível, ou mantendo essas se forem específicas do KPI */
    .border-left-primary { border-left: 4px solid #0d6efd; }
    .border-left-success { border-left: 4px solid #198754; }
    .border-left-danger { border-left: 4px solid #dc3545; }
    .border-left-warning { border-left: 4px solid #ffc107; }
    .border-left-info { border-left: 4px solid #0dcaf0; }

    .kpi-icon { position: absolute; right: 1.5rem; top: 1.5rem; font-size: 2.5rem; opacity: 0.1; }

    .chart-container { position: relative; height: 350px; width: 100%; }
    .filter-bar { background: #fff; padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 2rem; display: flex; align-items: flex-end; gap: 1rem; flex-wrap: wrap; }
    
    .card-header-custom { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f3f4; background: transparent; }
    .card-title-custom { font-size: 1.1rem; font-weight: 600; color: #343a40; margin: 0; }
</style>

<div class="container-fluid">

    <div class="row align-items-center mb-5 pb-4 border-bottom border-light">
        <div class="col-md-7 col-lg-8">
            <div class="metric-label mb-2"><i class="fas fa-chart-pie me-1 text-primary"></i> Business Intelligence</div>
            <h1 class="greeting">Relatórios</h1>
            <p class="text-muted mb-0 mt-2" style="font-weight: 500;">Performance e saúde do negócio em tempo real.</p>
        </div>
        <div class="col-md-5 col-lg-4 text-md-end mt-3 mt-md-0">
            <a href="imprimir_relatorio.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" target="_blank" class="btn btn-white shadow-sm rounded-pill px-4 py-2 fw-bold" style="font-size: 0.8rem; background: white;">
                <i class="fas fa-file-pdf me-2 opacity-50"></i> Gerar Relatório Executivo
            </a>
        </div>
    </div>

    <!-- Filtro de Data Global -->
    <div class="exec-card p-0 mb-4" style="background: rgba(255, 255, 255, 0.4); border: 1px solid rgba(0,0,0,0.05);">
        <div class="px-4 py-3">
            <form method="GET" class="d-flex flex-wrap gap-3 align-items-end">
                <input type="hidden" name="tab" value="<?php echo $activeTab; ?>">
                <div class="flex-grow-1" style="min-width: 150px; max-width: 200px;">
                    <label class="metric-label d-block mb-2">Data Início</label>
                    <input type="date" name="start_date" class="form-control rounded-3 border-light py-2" value="<?php echo $startDate; ?>">
                </div>
                <div class="flex-grow-1" style="min-width: 150px; max-width: 200px;">
                    <label class="metric-label d-block mb-2">Data Fim</label>
                    <input type="date" name="end_date" class="form-control rounded-3 border-light py-2" value="<?php echo $endDate; ?>">
                </div>
                <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 fw-bold">
                    <i class="fas fa-filter me-2 small"></i> Filtrar Período
                </button>
            </form>
        </div>
    </div>

    <!-- Abas de Navegação (Deslizável no Mobile) -->
    <div class="table-responsive mb-4" style="scrollbar-width: none; -ms-overflow-style: none;">
        <style> .nav-tabs-apple::-webkit-scrollbar { display: none; } </style>
        <ul class="nav nav-pills nav-tabs-apple flex-nowrap gap-2" id="reportTabs" style="padding-bottom: 5px;">
            <li class="nav-item">
                <a class="nav-link rounded-pill px-4 fw-bold <?php echo $activeTab == 'financeiro' ? 'active bg-navy' : 'text-navy bg-light'; ?>" href="?tab=financeiro&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                    <i class="fas fa-chart-line me-2"></i>Financeiro
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill px-4 fw-bold <?php echo $activeTab == 'produtos' ? 'active bg-navy' : 'text-navy bg-light'; ?>" href="?tab=produtos&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                    <i class="fas fa-box me-2"></i>Produtos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill px-4 fw-bold <?php echo $activeTab == 'funcionarios' ? 'active bg-navy' : 'text-navy bg-light'; ?>" href="?tab=funcionarios&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                    <i class="fas fa-users me-2"></i>Equipe
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill px-4 fw-bold <?php echo $activeTab == 'movimentacoes' ? 'active bg-navy' : 'text-navy bg-light'; ?>" href="?tab=movimentacoes&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                    <i class="fas fa-exchange-alt me-2"></i>Logística
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill px-4 fw-bold <?php echo $activeTab == 'insights' ? 'active bg-navy' : 'text-navy bg-light'; ?>" href="?tab=insights&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                    <i class="fas fa-brain me-2"></i>Insights de IA
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill px-4 fw-bold <?php echo $activeTab == 'contabilidade' ? 'active bg-navy' : 'text-navy bg-light'; ?>" href="?tab=contabilidade&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                    <i class="fas fa-file-invoice me-2"></i>Contabilidade
                </a>
            </li>
        </ul>
    </div>

    <div class="tab-content pt-2">
        
        <!-- 1. ABA FINANCEIRO -->
        <?php if ($activeTab == 'financeiro'): ?>
        <div class="tab-pane fade show active">
            <!-- KPIs Financeiros -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card-custom kpi-card border-left-primary">
                        <div class="kpi-label text-primary">Receita Bruta</div>
                        <div class="kpi-value">R$ <?php echo number_format($financialSummary['revenue'], 2, ',', '.'); ?></div>
                        <i class="fas fa-dollar-sign kpi-icon text-primary"></i>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card-custom kpi-card border-left-danger">
                        <div class="kpi-label text-danger">Custo Estimado</div>
                        <div class="kpi-value">R$ <?php echo number_format($financialSummary['cost'], 2, ',', '.'); ?></div>
                        <i class="fas fa-arrow-down kpi-icon text-danger"></i>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card-custom kpi-card border-left-success">
                        <div class="kpi-label text-success">Lucro Líquido</div>
                        <div class="kpi-value">R$ <?php echo number_format($financialSummary['profit'], 2, ',', '.'); ?></div>
                        <i class="fas fa-coins kpi-icon text-success"></i>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card-custom kpi-card border-left-warning">
                        <div class="kpi-label text-warning">Margem de Lucro</div>
                        <div class="kpi-value"><?php echo number_format($financialSummary['margin'], 1, ',', '.'); ?>%</div>
                        <i class="fas fa-percentage kpi-icon text-warning"></i>
                    </div>
                </div>
            </div>

            <!-- Gráficos Financeiros -->
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Fluxo de Caixa (Vendas vs Lucro)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesProfitChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Métodos de Pagamento</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <?php if(empty($payment_data)): ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">Sem dados</div>
                                <?php else: ?>
                                    <canvas id="paymentMethodChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 2. ABA PRODUTOS -->
        <?php if ($activeTab == 'produtos'): ?>
        <div class="tab-pane fade show active">
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Top 10 Mais Vendidos (Qtd)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <?php if(empty($top_products_data)): ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">Sem dados</div>
                                <?php else: ?>
                                    <canvas id="topProductsChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Top 10 Mais Lucrativos</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <?php if(empty($top_profit_data)): ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">Sem dados</div>
                                <?php else: ?>
                                    <canvas id="topProfitChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Distribuição por Categoria</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <?php if(empty($category_data)): ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">Sem dados</div>
                                <?php else: ?>
                                    <canvas id="categoryDoughnutChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 3. ABA FUNCIONÁRIOS -->
        <?php if ($activeTab == 'funcionarios'): ?>
        <div class="tab-pane fade show active">
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Ranking de Vendedores (Receita)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 400px;">
                                <?php if(empty($sellers_data)): ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">Sem dados de vendas por vendedor</div>
                                <?php else: ?>
                                    <canvas id="sellersChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabela Detalhada -->
            <div class="card-custom">
                <div class="card-header-custom">
                    <h6 class="card-title-custom">Detalhes de Performance</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Vendedor</th>
                                    <th class="text-center">Vendas (Qtd)</th>
                                    <th class="text-end pe-4">Receita Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sellers_data as $seller): ?>
                                <tr>
                                    <td class="ps-4 fw-500" data-label="Vendedor"><?php echo htmlspecialchars($seller['username']); ?></td>
                                    <td class="text-md-center" data-label="Vendas (Qtd)"><?php echo $seller['total_sales_count']; ?></td>
                                    <td class="text-md-end pe-md-4" data-label="Receita Total">R$ <?php echo number_format($seller['total_revenue'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($sellers_data)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Nenhum dado encontrado</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>



        <!-- 5. ABA MOVIMENTAÇÕES -->
        <?php if ($activeTab == 'movimentacoes'): ?>
        <div class="tab-pane fade show active">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Tipos de Movimentação</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <?php if(empty($stock_stats)): ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">Sem dados</div>
                                <?php else: ?>
                                    <canvas id="stockMovementChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 6. ABA INSIGHTS -->
        <?php if ($activeTab == 'insights'): ?>
        <div class="tab-pane fade show active">
            <div class="row mb-4">
                <div class="col-lg-4 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Curva ABC (Receita)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="abcChart"></canvas>
                            </div>
                            <div class="mt-3 small text-muted text-center">
                                A: 80% da Receita | B: 15% | C: 5%
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Horários de Pico (Vendas)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="hourlySalesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 7. ABA MÓDULOS -->
        <?php if ($activeTab == 'modulos'): ?>
        <div class="tab-pane fade show active">
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Atividade por Módulo (Registros)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="moduleActivityChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 4. ABA CONTABILIDADE -->
        <?php if ($activeTab == 'contabilidade'): ?>
        <div class="tab-pane fade show active">
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card-custom kpi-card border-left-info">
                        <div class="kpi-label text-info">Total em Compras</div>
                        <div class="kpi-value">R$ <?php echo number_format($total_purchased, 2, ',', '.'); ?></div>
                        <i class="fas fa-truck-loading kpi-icon text-info"></i>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card-custom">
                        <div class="card-header-custom">
                            <h6 class="card-title-custom">Compras por Fornecedor</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <?php if(empty($supplier_purchase_data)): ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">Sem dados</div>
                                <?php else: ?>
                                    <canvas id="supplierPurchaseChart"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php include_once '../includes/rodape.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const colors = {
            blue: '#1a73e8', green: '#1e8e3e', yellow: '#f9ab00', red: '#d93025',
            purple: '#9334e6', cyan: '#12b5cb', orange: '#e8710a', grey: '#5f6368'
        };
        const palette = Object.values(colors);

        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#5f6368';
        Chart.defaults.scale.grid.color = '#f1f3f4';
        Chart.defaults.scale.grid.borderColor = 'transparent';

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } },
                tooltip: {
                    backgroundColor: 'rgba(32, 33, 36, 0.9)',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true
                }
            }
        };

        // Render charts based on active tab
        <?php if ($activeTab == 'financeiro'): ?>
            new Chart(document.getElementById('salesProfitChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($sales_profit_labels); ?>,
                    datasets: [
                        { label: 'Vendas', data: <?php echo json_encode($sales_data); ?>, backgroundColor: colors.blue, borderRadius: 4 },
                        { label: 'Lucro', data: <?php echo json_encode($profit_data); ?>, backgroundColor: colors.green, borderRadius: 4 }
                    ]
                },
                options: { ...commonOptions, scales: { y: { beginAtZero: true, ticks: { callback: v => 'R$ ' + v } }, x: { grid: { display: false } } } }
            });

            <?php if (!empty($payment_data)): ?>
            new Chart(document.getElementById('paymentMethodChart'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($payment_labels); ?>,
                    datasets: [{ data: <?php echo json_encode($payment_counts); ?>, backgroundColor: palette, borderWidth: 0 }]
                },
                options: { ...commonOptions, cutout: '70%' }
            });
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($activeTab == 'produtos'): ?>
            <?php if (!empty($top_products_data)): ?>
            new Chart(document.getElementById('topProductsChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($top_products_labels); ?>,
                    datasets: [{ label: 'Qtd Vendida', data: <?php echo json_encode($top_products_counts); ?>, backgroundColor: colors.orange, borderRadius: 4 }]
                },
                options: { ...commonOptions, indexAxis: 'y', scales: { x: { beginAtZero: true }, y: { grid: { display: false } } } }
            });
            <?php endif; ?>

            <?php if (!empty($top_profit_data)): ?>
            new Chart(document.getElementById('topProfitChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($top_profit_labels); ?>,
                    datasets: [{ label: 'Lucro (R$)', data: <?php echo json_encode($top_profit_values); ?>, backgroundColor: colors.green, borderRadius: 4 }]
                },
                options: { ...commonOptions, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { callback: v => 'R$ ' + v } }, y: { grid: { display: false } } } }
            });
            <?php endif; ?>

            <?php if (!empty($category_data)): ?>
            new Chart(document.getElementById('categoryDoughnutChart'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($category_labels); ?>,
                    datasets: [{ data: <?php echo json_encode($category_counts); ?>, backgroundColor: palette.slice().reverse(), borderWidth: 0 }]
                },
                options: { ...commonOptions, cutout: '70%' }
            });
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($activeTab == 'funcionarios' && !empty($sellers_data)): ?>
            new Chart(document.getElementById('sellersChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($seller_labels); ?>,
                    datasets: [{ label: 'Receita Gerada (R$)', data: <?php echo json_encode($seller_revenue); ?>, backgroundColor: colors.purple, borderRadius: 4 }]
                },
                options: { ...commonOptions, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { callback: v => 'R$ ' + v } }, y: { grid: { display: false } } } }
            });
        <?php endif; ?>

        <?php if ($activeTab == 'contabilidade' && !empty($supplier_purchase_data)): ?>
            new Chart(document.getElementById('supplierPurchaseChart'), {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode($supplier_labels); ?>,
                    datasets: [{ data: <?php echo json_encode($supplier_data); ?>, backgroundColor: palette, borderWidth: 0 }]
                },
                options: commonOptions
            });
        <?php endif; ?>
        
        <?php if ($activeTab == 'movimentacoes' && !empty($stock_stats)): ?>
            new Chart(document.getElementById('stockMovementChart'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($stock_labels); ?>,
                    datasets: [{ data: <?php echo json_encode($stock_counts); ?>, backgroundColor: palette, borderWidth: 0 }]
                },
                options: commonOptions
            });
        <?php endif; ?>

        <?php if ($activeTab == 'insights'): ?>
            new Chart(document.getElementById('abcChart'), {
                type: 'pie',
                data: {
                    labels: ['Classe A', 'Classe B', 'Classe C'],
                    datasets: [{ 
                        data: [<?php echo $abc_data['A']; ?>, <?php echo $abc_data['B']; ?>, <?php echo $abc_data['C']; ?>], 
                        backgroundColor: [colors.green, colors.yellow, colors.red], borderWidth: 0 
                    }]
                },
                options: commonOptions
            });

            new Chart(document.getElementById('hourlySalesChart'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($hourly_labels); ?>,
                    datasets: [{ 
                        label: 'Vendas por Hora', 
                        data: <?php echo json_encode($hourly_values); ?>, 
                        borderColor: colors.blue, 
                        backgroundColor: 'rgba(26, 115, 232, 0.1)', 
                        borderWidth: 2, 
                        fill: true,
                        tension: 0.4 
                    }]
                },
                options: { ...commonOptions, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
            });
        <?php endif; ?>

        <?php if ($activeTab == 'modulos'): ?>
            new Chart(document.getElementById('moduleActivityChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($module_labels); ?>,
                    datasets: [{ 
                        label: 'Registros Críados', 
                        data: <?php echo json_encode($module_values); ?>, 
                        backgroundColor: [colors.blue, colors.orange, colors.green, colors.purple], 
                        borderRadius: 4 
                    }]
                },
                options: { ...commonOptions, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
            });
        <?php endif; ?>
    });
</script>
