<?php
// superadmin/index.php - BRASALLIS MASTER CONSOLE v8.1 (Google Design Language - Fix Edition)
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';
require_once '../vendor/autoload.php';
use App\Repository\SuperDashboardRepository;
use App\Core\Database;

checkSuperAdmin();

$repo = new SuperDashboardRepository(Database::getInstance());
$stats = $repo->getGlobalStats();
$saas = $repo->getSaaSMetrics();
$health = $repo->getInfrastructureHealth();
$rev_data = $repo->getPlatformRevenueChart();
$eff_data = $repo->getEfficiencyMetrics();
$rev_plan = $repo->getRevenueByPlan();
$recent_emp = $repo->getRecentCompanies(5);

$chart_labels = json_encode(array_column($rev_data, 'label'));
$chart_values = json_encode(array_column($rev_data, 'value'));

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-end mb-5">
    <div>
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Brasallis Hub</a></li>
                <li class="breadcrumb-item active" aria-current="page">Master Console</li>
            </ol>
        </nav>
        <h2 class="fw-bold m-0" style="color: #202124;">Visão Geral da Operação</h2>
    </div>
    <div class="d-flex gap-2">
        <a href="export_audit.php" target="_blank" class="btn btn-white border rounded-pill px-3 py-2 fw-600 shadow-sm small">
            <i class="fas fa-file-pdf me-2 text-danger"></i>Relatório de Auditoria
        </a>
        <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm small" onclick="location.reload()">
            <i class="fas fa-sync-alt me-2"></i>Sincronizar Nodes
        </button>
    </div>
</div>

<!-- Infrastructure Health Banner -->
<div class="google-card mb-5 p-3" style="background: #f8f9fa; border-left: 4px solid <?= strtolower($health['status']) === 'healthy' ? '#34a853' : '#fbbc04' ?>">
    <div class="d-flex align-items-center justify-content-between px-2">
        <div class="d-flex align-items-center gap-3">
            <div class="status-pill bg-white border shadow-sm">
                <span class="status-dot status-<?= strtolower($health['status']) === 'healthy' ? 'healthy' : 'warning' ?>"></span>
                <span class="text-dark">Cluster Status: <?= $health['status'] ?></span>
            </div>
            <span class="small text-muted"><i class="fas fa-server me-1"></i> Monitorando <?= $stats['total_empresas'] ?? 0 ?> instâncias ativas</span>
        </div>
        <div class="d-flex gap-4 small fw-600">
            <span class="text-muted">API Latency: <span class="text-dark">42ms</span></span>
            <span class="text-muted">Uptime: <span class="text-dark">99.99%</span></span>
            <span class="text-muted">Erros (24h): <span class="text-<?= ($health['error_count'] ?? 0) > 0 ? 'danger' : 'success' ?>"><?= $health['error_count'] ?? 0 ?></span></span>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-12">
        <div class="google-card p-3 d-flex align-items-center justify-content-between bg-dark text-white border-0 shadow-lg" style="border-radius: 12px; background: linear-gradient(90deg, #202124 0%, #3c4043 100%);">
            <div class="d-flex align-items-center gap-4 ps-2">
                <div class="pulse-icon"><i class="fas fa-wave-square text-success"></i></div>
                <div>
                    <div class="text-uppercase small fw-bold opacity-75" style="font-size: 10px; letter-spacing: 1px;">Live Traffic Pulse</div>
                    <div class="h5 m-0 fw-bold">Atividade do Ecossistema</div>
                </div>
            </div>
            <div class="d-flex gap-5 pe-3">
                <div class="text-center">
                    <div class="small opacity-50">Requests/sec</div>
                    <div class="fw-bold">142.8</div>
                </div>
                <div class="text-center">
                    <div class="small opacity-50">DB Load</div>
                    <div class="fw-bold text-success">12%</div>
                </div>
                <div class="text-center">
                    <div class="small opacity-50">AI Tokens/min</div>
                    <div class="fw-bold text-info">8.4k</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Core Metrics Grid -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="google-card">
            <div class="metric-title">Faturamento Global</div>
            <div class="metric-value">R$ <?= number_format((float)($stats['faturamento_total'] ?? 0), 0, ',', '.') ?></div>
            <div class="trend-indicator <?= ($saas['growth_rate'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                <i class="fas fa-caret-<?= ($saas['growth_rate'] ?? 0) >= 0 ? 'up' : 'down' ?> me-1"></i> 
                <?= number_format($saas['growth_rate'] ?? 0, 1) ?>% vs mês anterior
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="google-card">
            <div class="metric-title">ARPU (Receita/User)</div>
            <div class="metric-value">R$ <?= number_format((float)($saas['arpu'] ?? 0), 2, ',', '.') ?></div>
            <div class="small text-muted">Eficiência média de monetização</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="google-card">
            <div class="metric-title">Churn Risk (Inatividade)</div>
            <div class="metric-value text-<?= ($saas['churn_risk'] ?? 0) > 0 ? 'warning' : 'success' ?>"><?= $saas['churn_risk'] ?? 0 ?></div>
            <div class="small text-muted">Empresas com alerta de evasão</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="google-card">
            <div class="metric-title">Usuários na Rede</div>
            <div class="metric-value"><?= number_format($stats['total_usuarios'] ?? 0, 0, '', '.') ?></div>
            <div class="small text-muted">Crescimento orgânico estável</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Main Revenue Timeline -->
    <div class="col-lg-8">
        <div class="google-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="section-title mb-0">Performance de Faturamento</h6>
                <div class="btn-group btn-group-sm border rounded-pill overflow-hidden">
                    <button class="btn btn-white border-0 px-3">15D</button>
                    <button class="btn btn-light border-0 px-3 active">30D</button>
                    <button class="btn btn-white border-0 px-3">90D</button>
                </div>
            </div>
            <div style="height: 380px;">
                <canvas id="mainTimelineChart"></canvas>
            </div>
        </div>
    </div>
    <!-- Tier Distribution -->
    <div class="col-lg-4">
        <div class="google-card h-100">
            <h6 class="section-title mb-4">Distribuição de Receita por Plano</h6>
            <div style="height: 250px;">
                <canvas id="tierDonutChart"></canvas>
            </div>
            <div class="mt-4">
                <table class="table table-sm table-borderless small mb-0">
                    <tbody>
                        <?php foreach($rev_plan as $rp): ?>
                        <tr>
                            <td class="text-muted"><i class="fas fa-circle me-2" style="font-size: 8px; color: <?= $rp['label'] == 'elite' ? '#1a73e8' : ($rp['label'] == 'vision' ? '#34a853' : '#fbbc04') ?>"></i> <?= ucfirst($rp['label']) ?></td>
                            <td class="text-end fw-bold">R$ <?= number_format((float)($rp['value'] ?? 0), 0, ',', '.') ?></td>
                            <td class="text-end text-muted"><?= $stats['faturamento_total'] > 0 ? round(($rp['value'] / $stats['faturamento_total']) * 100) : 0 ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Operational Efficiency -->
    <div class="col-lg-7">
        <div class="google-card h-100">
            <h6 class="section-title mb-4">Eficiência de Uso (IA vs GMV)</h6>
            <div style="height: 350px; position: relative;">
                <canvas id="efficiencyBarChart"></canvas>
            </div>
        </div>
    </div>
    <!-- Activity Log Snippet -->
    <div class="col-lg-5">
        <div class="google-card p-0 overflow-hidden h-100">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="section-title mb-0">Atividades Recentes</h6>
                <a href="logs.php" class="text-primary small fw-bold text-decoration-none">Ver Todos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-clean mb-0">
                    <tbody>
                        <?php foreach($recent_emp as $e): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold small"><?= htmlspecialchars($e['name']) ?></div>
                                <div class="text-muted" style="font-size: 0.7rem;">Provisionamento Completo</div>
                            </td>
                            <td class="small text-muted"><?= date('d M, H:i', strtotime($e['created_at'])) ?></td>
                            <td class="text-end pe-4">
                                <span class="status-pill bg-success-subtle text-success small py-1 px-2" style="font-size: 0.65rem;">SUCCESS</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurações comuns do Google Style
    const commonScales = {
        y: { grid: { color: '#f1f3f4', drawBorder: false }, ticks: { font: { size: 10 } } },
        x: { grid: { display: false }, ticks: { font: { size: 10 } } }
    };

    const timelineCtx = document.getElementById('mainTimelineChart').getContext('2d');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: <?= $chart_labels ?>,
            datasets: [{
                label: 'Faturamento Diário',
                data: <?= $chart_values ?>,
                borderColor: '#1a73e8',
                backgroundColor: 'rgba(26, 115, 232, 0.05)',
                borderWidth: 3, tension: 0.4, fill: true, pointRadius: 0,
                hoverPointRadius: 6
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { legend: { display: false } },
            scales: commonScales
        }
    });

    const donutCtx = document.getElementById('tierDonutChart').getContext('2d');
    new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($rev_plan, 'label')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($rev_plan, 'value')) ?>,
                backgroundColor: ['#1a73e8', '#34a853', '#fbbc04', '#ea4335'],
                borderWidth: 4, borderColor: '#fff'
            }]
        },
        options: { cutout: '80%', plugins: { legend: { display: false } } }
    });

    const barCtx = document.getElementById('efficiencyBarChart').getContext('2d');
    const effData = <?= json_encode($eff_data) ?>;
    
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: effData.map(d => d.name),
            datasets: [
                { 
                    label: 'GMV (R$)', 
                    data: effData.map(d => d.revenue), 
                    backgroundColor: 'rgba(26, 115, 232, 0.8)', 
                    borderRadius: 6,
                    yAxisID: 'y'
                },
                { 
                    label: 'IA (Tokens)', 
                    data: effData.map(d => d.tokens), 
                    type: 'line',
                    borderColor: '#10b981',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: false,
                    pointRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            interaction: { mode: 'index', intersect: false },
            plugins: { 
                legend: { position: 'top', labels: { usePointStyle: true, font: { size: 10 } } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let val = context.parsed.y || 0;
                            if (context.datasetIndex === 0) {
                                return label + ': R$ ' + val.toLocaleString('pt-BR');
                            }
                            return label + ': ' + val.toLocaleString('pt-BR');
                        }
                    }
                }
            },
            scales: { 
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { 
                    type: 'linear', display: true, position: 'left',
                    title: { display: true, text: 'Receita', font: { size: 9, weight: 'bold' } },
                    grid: { color: '#f1f3f4', drawBorder: false },
                    ticks: { font: { size: 10 } }
                },
                y1: { 
                    type: 'linear', display: true, position: 'right',
                    title: { display: true, text: 'Tokens', font: { size: 9, weight: 'bold' } },
                    grid: { drawOnChartArea: false },
                    ticks: { font: { size: 10 } }
                }
            } 
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
