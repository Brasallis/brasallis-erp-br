<?php
// modules/financeiro/views/fluxo_caixa.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('financeiro', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Fetch Empresa Goals
$stmtE = $conn->prepare("SELECT monthly_revenue_goal, fixed_costs FROM empresas WHERE id = ?");
$stmtE->execute([$empresa_id]);
$empresa_data = $stmtE->fetch(PDO::FETCH_ASSOC);
$goal_monthly = (float)($empresa_data['monthly_revenue_goal'] ?? 0);
$fixed_costs = (float)($empresa_data['fixed_costs'] ?? 0);

// Filtro de Mês/Ano
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

// Fetch Receitas do Mês
try {
    $stmtR = $conn->prepare("SELECT data_vencimento as data, descricao, valor, status, 'receita' as tipo FROM contas_receber WHERE empresa_id = ? AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ? ORDER BY data_vencimento ASC");
    $stmtR->execute([$empresa_id, $mes, $ano]);
    $receitas = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    $stmtP = $conn->prepare("SELECT data_vencimento as data, descricao, valor, status, 'despesa' as tipo FROM contas_pagar WHERE empresa_id = ? AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ? ORDER BY data_vencimento ASC");
    $stmtP->execute([$empresa_id, $mes, $ano]);
    $despesas = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    // Merge and Sort by Date
    $lancamentos = array_merge($receitas, $despesas);
    usort($lancamentos, function($a, $b) {
        return strtotime($a['data']) - strtotime($b['data']);
    });

} catch (Exception $e) {
    $lancamentos = [];
}

// Calculate Summaries
$total_entradas = 0;
$total_saidas = 0;
$entradas_realizadas = 0;
$saidas_realizadas = 0;

foreach ($lancamentos as $l) {
    if ($l['tipo'] === 'receita') {
        $total_entradas += $l['valor'];
        if ($l['status'] === 'recebido') $entradas_realizadas += $l['valor'];
    } else {
        $total_saidas += $l['valor'];
        if ($l['status'] === 'pago') $saidas_realizadas += $l['valor'];
    }
}
$saldo_projetado = $total_entradas - $total_saidas;
$saldo_realizado = $entradas_realizadas - $saidas_realizadas;

// Health Calculations
$days_in_month = date('t', mktime(0,0,0,$mes,1, $ano));
$daily_goal = $goal_monthly > 0 ? $goal_monthly / $days_in_month : 0;

$stmtToday = $conn->prepare("SELECT SUM(valor) FROM contas_receber WHERE empresa_id = ? AND data_vencimento = CURDATE() AND status = 'recebido'");
$stmtToday->execute([$empresa_id]);
$today_sales = (float)$stmtToday->fetchColumn();

$goal_percent = $goal_monthly > 0 ? min(($total_entradas / $goal_monthly) * 100, 100) : 0;
$breakeven_percent = $fixed_costs > 0 ? min(($total_entradas / $fixed_costs) * 100, 100) : 0;

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-chart-line me-2 text-primary"></i>Fluxo de Caixa</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Financeiro</a></li>
                    <li class="breadcrumb-item active">Fluxo de Caixa</li>
                </ol>
            </nav>
        </div>
        <div class="w-100 w-md-auto">
            <form method="GET" class="d-flex gap-2">
                <select name="mes" class="form-select border-0 shadow-sm rounded-pill px-3" onchange="this.form.submit()">
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $mes ? 'selected' : '' ?>><?= sprintf('%02d', $i) ?> - <?= date('M', mktime(0,0,0,$i,10)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="ano" class="form-select border-0 shadow-sm rounded-pill px-3" style="width: 120px;" onchange="this.form.submit()">
                    <?php for($i = date('Y')-1; $i <= date('Y')+1; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $ano ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>

    </div>

    <!-- SMART HEALTH DASHBOARD (NOVO v1.1) -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="icon-box bg-primary bg-opacity-10 text-primary me-3"><i class="fas fa-heartbeat"></i></div>
                <h5 class="fw-bold mb-0">Saúde Financeira & Lucratividade</h5>
            </div>

            <div class="row g-4">
                <!-- Meta Mensal -->
                <div class="col-lg-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small fw-bold text-muted">Progresso Meta Mensal</span>
                        <span class="small fw-bold"><?= number_format($goal_percent, 1) ?>%</span>
                    </div>
                    <div class="progress mb-2" style="height: 12px; border-radius: 6px;">
                        <div class="progress-bar <?= $goal_percent >= 100 ? 'bg-success' : 'bg-primary' ?> shadow-sm" style="width: <?= $goal_percent ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">Atual: R$ <?= number_format($total_entradas, 2, ',', '.') ?></small>
                        <small class="text-muted">Meta: R$ <?= number_format($goal_monthly, 2, ',', '.') ?></small>
                    </div>
                </div>

                <!-- Ponto de Equilíbrio -->
                <div class="col-lg-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small fw-bold text-muted">Cobertura de Custos Fixos</span>
                        <span class="small fw-bold"><?= number_format($breakeven_percent, 1) ?>%</span>
                    </div>
                    <div class="progress mb-2" style="height: 12px; border-radius: 6px;">
                        <div class="progress-bar <?= $breakeven_percent >= 100 ? 'bg-success' : 'bg-warning' ?> shadow-sm" style="width: <?= $breakeven_percent ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">Entradas: R$ <?= number_format($total_entradas, 2, ',', '.') ?></small>
                        <small class="text-muted">Custos: R$ <?= number_format($fixed_costs, 2, ',', '.') ?></small>
                    </div>
                </div>

                <!-- Meta Diária -->
                <div class="col-lg-4">
                    <div class="card bg-light border-0 rounded-4 p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-bold text-navy">Performance Diária</span>
                            <?php if($today_sales >= $daily_goal && $daily_goal > 0): ?>
                                <span class="badge bg-success rounded-pill">SUPERADA</span>
                            <?php else: ?>
                                <span class="badge bg-danger rounded-pill">PENDENTE</span>
                            <?php endif; ?>
                        </div>
                        <h4 class="fw-bold mb-1">R$ <?= number_format($today_sales, 2, ',', '.') ?></h4>
                        <p class="text-muted small mb-0">Meta do dia: <span class="fw-bold text-dark">R$ <?= number_format($daily_goal, 2, ',', '.') ?></span></p>
                    </div>
                </div>
            </div>
            
            <?php if($goal_monthly == 0 || $fixed_costs == 0): ?>
                <div class="alert bg-info bg-opacity-10 border-0 text-info mt-4 mb-0 rounded-4 small">
                    <i class="fas fa-info-circle me-2"></i> Configure suas <strong>Metas Financeiras</strong> nas Configurações da Organização para desbloquear insights avançados de lucratividade.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- BALANCE METRICS -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h6 class="text-secondary text-uppercase small fw-bold mb-3">Resumo de Realização</h6>
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted d-block">Recebido</small>
                        <h4 class="fw-bold text-success">R$ <?= number_format($entradas_realizadas, 2, ',', '.') ?></h4>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Pago</small>
                        <h4 class="fw-bold text-danger">R$ <?= number_format($saidas_realizadas, 2, ',', '.') ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-navy text-white">
                <h6 class="text-white-50 text-uppercase small fw-bold mb-3">Saldo Operacional (Realizado)</h6>
                <h2 class="fw-bold <?= $saldo_realizado < 0 ? 'text-danger' : 'text-success' ?>">R$ <?= number_format($saldo_realizado, 2, ',', '.') ?></h2>
                <small class="text-white-50">Diferença entre o que já entrou e o que já saiu efetivamente.</small>
            </div>
        </div>
    </div>

    <!-- Lancamentos Table -->
    <div class="apple-table-container">
        <div class="table-responsive">
            <table class="table apple-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Data</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lancamentos)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhum lançamento encontrado para este período.</td></tr>
                    <?php else: ?>
                        <?php foreach($lancamentos as $l): ?>
                        <tr>
                            <td class="ps-4 text-muted" data-label="Data"><?= date('d/m/Y', strtotime($l['data'])) ?></td>
                            <td class="fw-bold text-dark" data-label="Descrição"><?= htmlspecialchars($l['descricao']) ?></td>
                            <td data-label="Tipo">
                                <?php if($l['tipo'] == 'receita'): ?>
                                    <span class="text-success small fw-bold"><i class="fas fa-arrow-down me-1"></i>RECEITA</span>
                                <?php else: ?>
                                    <span class="text-danger small fw-bold"><i class="fas fa-arrow-up me-1"></i>DESPESA</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-md-center" data-label="Status">
                                <?php if($l['status'] == 'pago' || $l['status'] == 'recebido'): ?>
                                    <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2">EFETIVADO</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning px-3 py-2">PENDENTE</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4 fw-bold <?= $l['tipo'] == 'receita' ? 'text-success' : 'text-danger' ?>" data-label="Valor">
                                <?= $l['tipo'] == 'receita' ? '+' : '-' ?> R$ <?= number_format($l['valor'], 2, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .text-navy { color: #0A2647; }
    .bg-navy { background-color: #0A2647; }
    .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }
    .bg-success-light { background-color: rgba(40,167,69,0.1); }
    .bg-warning-light { background-color: rgba(255,193,7,0.1); }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
