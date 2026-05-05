<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// --- CÁLCULO DE MÉTRICAS ---

// 1. Total de Economia Potencial (Recuperada/Evitada)
$savings_stmt = $conn->prepare("
    SELECT SUM(at.savings_potential) 
    FROM analise_tributaria at
    JOIN compras c ON at.compra_id = c.id
    WHERE c.empresa_id = ?
");
$savings_stmt->execute([$empresa_id]);
$total_savings = $savings_stmt->fetchColumn() ?: 0.00;

// 2. Total de Itens Analisados
$count_stmt = $conn->prepare("
    SELECT COUNT(at.id)
    FROM analise_tributaria at
    JOIN compras c ON at.compra_id = c.id
    WHERE c.empresa_id = ?
");
$count_stmt->execute([$empresa_id]);
$total_analyzed = $count_stmt->fetchColumn();

// 3. Alertas Críticos (CFOP errado, etc)
$alerts_stmt = $conn->prepare("
    SELECT COUNT(at.id)
    FROM analise_tributaria at
    JOIN compras c ON at.compra_id = c.id
    WHERE c.empresa_id = ? AND at.alert_level IN ('warning', 'critical')
");
$alerts_stmt->execute([$empresa_id]);
$total_alerts = $alerts_stmt->fetchColumn();

// 4. Buscar últimas análises para a tabela
$recent_stmt = $conn->prepare("
    SELECT at.*, p.name as product_name, c.purchase_date
    FROM analise_tributaria at
    JOIN compras c ON at.compra_id = c.id
    LEFT JOIN produtos p ON at.product_id = p.id
    WHERE c.empresa_id = ?
    ORDER BY at.created_at DESC
    LIMIT 10
");
$recent_stmt->execute([$empresa_id]);
$recent_analysis = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Dados para o Gráfico (Economia por Mês)
$chart_stmt = $conn->prepare("
    SELECT DATE_FORMAT(c.purchase_date, '%Y-%m') as mes, SUM(at.savings_potential) as economia
    FROM analise_tributaria at
    JOIN compras c ON at.compra_id = c.id
    WHERE c.empresa_id = ?
    GROUP BY mes
    ORDER BY mes ASC
    LIMIT 6
");
$chart_stmt->execute([$empresa_id]);
$chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);

$chart_labels = [];
$chart_values = [];
foreach ($chart_data as $row) {
    $chart_labels[] = date('M/Y', strtotime($row['mes'] . '-01'));
    $chart_values[] = $row['economia'];
}

include_once '../includes/cabecalho.php';
?>

<style>
    .metric-card { border-left: 4px solid var(--brasallis-primary); }
    .metric-card.success { border-left-color: #10b981; }
    .metric-card.warning { border-left-color: #f59e0b; }
    
    .icon-box {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; margin-bottom: 1rem;
    }
    .bg-success-soft { background: #ecfdf5; color: #10b981; }
    .bg-primary-soft { background: #eff6ff; color: #3b82f6; }
    .bg-warning-soft { background: #fffbeb; color: #f59e0b; }
</style>

<div class="page-container">
    <div class="page-header">
        <div class="page-title-group">
            <h1>Inteligência Tributária & Fiscal</h1>
            <p>Auditoria de conformidade e identificação de economia tributária via IA</p>
        </div>
        <div class="d-flex gap-2">
            <a href="registrar_compra.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fas fa-robot me-2"></i>Lançar Nota com IA
            </a>
            <a href="vendas.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-history me-2"></i>Histórico de Vendas
            </a>
        </div>
    </div>

    <!-- Cards de Resumo -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="section-card metric-card success h-100 mb-0">
                <div class="icon-box bg-success-soft"><i class="fas fa-piggy-bank"></i></div>
                <h6 class="text-muted small fw-bold text-uppercase mb-1">Economia Potencial</h6>
                <h2 class="fw-bold mb-1">R$ <?= number_format($total_savings, 2, ',', '.') ?></h2>
                <p class="text-muted small mb-0">Créditos de PIS/COFINS identificados</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="section-card metric-card h-100 mb-0">
                <div class="icon-box bg-primary-soft"><i class="fas fa-microchip"></i></div>
                <h6 class="text-muted small fw-bold text-uppercase mb-1">Itens Auditados</h6>
                <h2 class="fw-bold mb-1"><?= $total_analyzed ?></h2>
                <p class="text-muted small mb-0">Processados pela Inteligência Artificial</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="section-card metric-card warning h-100 mb-0">
                <div class="icon-box bg-warning-soft"><i class="fas fa-shield-virus"></i></div>
                <h6 class="text-muted small fw-bold text-uppercase mb-1">Alertas de Conformidade</h6>
                <h2 class="fw-bold mb-1"><?= $total_alerts ?></h2>
                <p class="text-muted small mb-0">Riscos de CFOP ou NCM detectados</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="section-card h-100 mb-0">
                <h6 class="fw-bold text-navy mb-4">Economia Gerada por Mês</h6>
                <div style="height: 300px;">
                    <canvas id="savingsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="section-card h-100 mb-0">
                <h6 class="fw-bold text-navy mb-4">Diagnóstico Rápido</h6>
                <div class="list-group list-group-flush">
                    <?php if (empty($recent_analysis)): ?>
                        <div class="text-center py-5 text-muted small">Nenhuma análise pendente.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($recent_analysis, 0, 5) as $item): ?>
                            <div class="list-group-item px-0 border-0 mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold text-navy small text-truncate" style="max-width: 180px;"><?= htmlspecialchars($item['item_name_xml']) ?></span>
                                    <span class="badge bg-light text-muted fw-normal" style="font-size: 0.65rem;"><?= date('d/m', strtotime($item['created_at'])) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">NCM: <?= $item['ncm_detectado'] ?></span>
                                    <?php if ($item['savings_potential'] > 0): ?>
                                        <span class="text-success fw-bold small">+ R$ <?= number_format($item['savings_potential'], 2, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <h6 class="fw-bold text-navy mb-4">Auditoria Detalhada de Itens</h6>
        <div class="table-responsive">
            <table class="table-elite">
                <thead>
                    <tr>
                        <th>Produto / XML</th>
                        <th>NCM / CFOP</th>
                        <th>Nível de Risco</th>
                        <th>Economia Est.</th>
                        <th>Sugestão da IA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_analysis as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-navy"><?= htmlspecialchars($row['item_name_xml']) ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;">ID Compra: #<?= $row['compra_id'] ?></div>
                            </td>
                            <td>
                                <div class="badge bg-light text-navy border">NCM: <?= $row['ncm_detectado'] ?></div>
                                <div class="mt-1 small text-muted">CFOP: <?= $row['cfop_entrada'] ?></div>
                            </td>
                            <td>
                                <?php if ($row['alert_level'] == 'critical'): ?>
                                    <span class="badge bg-danger rounded-pill px-3">Alto Risco</span>
                                <?php elseif ($row['alert_level'] == 'warning'): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-3">Atenção</span>
                                <?php else: ?>
                                    <span class="badge bg-success rounded-pill px-3">Conforme</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-success fw-bold">
                                <?= $row['savings_potential'] > 0 ? 'R$ ' . number_format($row['savings_potential'], 2, ',', '.') : '-' ?>
                            </td>
                            <td class="small text-muted" style="max-width: 250px;">
                                <i class="fas fa-robot me-1 text-primary"></i> <?= htmlspecialchars($row['ai_suggestion']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Configuração do Gráfico
const ctx = document.getElementById('savingsChart').getContext('2d');
const savingsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Economia Identificada (R$)',
            data: <?= json_encode($chart_values) ?>,
            backgroundColor: 'rgba(25, 135, 84, 0.1)',
            borderColor: '#198754',
            borderWidth: 2,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#198754',
            pointRadius: 5,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { borderDash: [2, 2] }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>

<?php include_once '../includes/rodape.php'; ?>
