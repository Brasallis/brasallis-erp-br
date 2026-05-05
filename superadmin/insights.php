<?php
// superadmin/insights.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/funcoes.php';

// Proteção SuperAdmin
checkSuperAdmin();

$conn = connect_db();

// 1. Estatísticas de Segmentos
$stmt = $conn->query("SELECT segmento, COUNT(*) as total FROM empresas GROUP BY segmento");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$counts = [];
foreach ($stats as $s) {
    $labels[] = $s['segmento'] ?: 'Não Iniciado';
    $counts[] = $s['total'];
}

// 2. Total de Empresas por Plano
$stmt_planos = $conn->query("SELECT ai_plan, COUNT(*) as total FROM empresas GROUP BY ai_plan");
$plano_stats = $stmt_planos->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Insights de Público</h2>
        <p class="page-subtitle">Distribuição estratégica da base de clientes Brasallis</p>
    </div>
</div>

<div class="row g-4">
        <!-- Gráfico de Segmentos -->
        <div class="col-md-6">
            <div class="card stat-card p-4">
                <h5 class="fw-bold mb-4">Distribuição por Segmento</h5>
                <canvas id="segmentChart"></canvas>
            </div>
        </div>

        <!-- Gráfico de Planos -->
        <div class="col-md-6">
            <div class="card stat-card p-4">
                <h5 class="fw-bold mb-4">Empresas por Plano</h5>
                <canvas id="planChart"></canvas>
            </div>
        </div>

        <!-- Tabela de Resumo -->
        <div class="col-12 mt-4">
            <div class="card stat-card p-4">
                <h5 class="fw-bold mb-4">Resumo Detalhado</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Segmento</th>
                            <th>Quantidade de Empresas</th>
                            <th>Market Share Interno</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_empresas = array_sum($counts);
                        foreach ($stats as $s): 
                            $percentage = $total_empresas > 0 ? round(($s['total'] / $total_empresas) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td class="fw-bold"><?= $s['segmento'] ?: 'Setup Pendente' ?></td>
                            <td><?= $s['total'] ?></td>
                            <td>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%"></div>
                                </div>
                                <span class="small text-muted"><?= $percentage ?>%</span>
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
    // Gráfico de Segmentos
    const ctxSegment = document.getElementById('segmentChart').getContext('2d');
    new Chart(ctxSegment, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                data: <?= json_encode($counts) ?>,
                backgroundColor: ['#000', '#333', '#666', '#999', '#ccc', '#eee']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Gráfico de Planos
    const ctxPlan = document.getElementById('planChart').getContext('2d');
    new Chart(ctxPlan, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($plano_stats, 'ai_plan')) ?>,
            datasets: [{
                label: 'Empresas',
                data: <?= json_encode(array_column($plano_stats, 'total')) ?>,
                backgroundColor: '#0d6efd'
            }]
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
