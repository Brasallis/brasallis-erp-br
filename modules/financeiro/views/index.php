<?php
// modules/financeiro/views/index.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';
require_once __DIR__ . '/../../../includes/cabecalho.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('financeiro', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$params = check_permission('financeiro', 'escrita'); 
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Metrics
$receber_mes = 0;
$pagar_mes = 0;
$saldo_mes = 0;

$mes_atual = date('m');
$ano_atual = date('Y');

try {
    // Total Contas a Receber no Mês (Pendentes + Atrasadas)
    $stmtR = $conn->prepare("SELECT SUM(valor) FROM contas_receber WHERE empresa_id = ? AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ? AND status IN ('pendente', 'atrasado')");
    $stmtR->execute([$empresa_id, $mes_atual, $ano_atual]);
    $receber_mes = $stmtR->fetchColumn() ?: 0;

    // Total Contas a Pagar no Mês (Pendentes + Atrasadas)
    $stmtP = $conn->prepare("SELECT SUM(valor) FROM contas_pagar WHERE empresa_id = ? AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ? AND status IN ('pendente', 'atrasado')");
    $stmtP->execute([$empresa_id, $mes_atual, $ano_atual]);
    $pagar_mes = $stmtP->fetchColumn() ?: 0;

    $saldo_mes = $receber_mes - $pagar_mes;

} catch (Exception $e) { /* silent fail */ }
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-wallet me-2"></i>Gestão Financeira</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item active">Financeiro</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 w-100 w-md-auto">
            <?php if ($params): ?>
            <a href="contas_receber.php?action=new" class="btn btn-outline-success flex-grow-1 flex-md-grow-0"><i class="fas fa-arrow-down me-2"></i>Nova Receita</a>
            <a href="contas_pagar.php?action=new" class="btn btn-trust-primary flex-grow-1 flex-md-grow-0"><i class="fas fa-arrow-up me-2"></i>Nova Despesa</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- METRICS -->
    <div class="row g-4 mb-4">
        <!-- Contas a Receber -->
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm border-start border-success border-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">A Receber (Mês)</h6>
                    <div class="icon-shape bg-success bg-opacity-10 text-success rounded-circle">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy">R$ <?= number_format($receber_mes, 2, ',', '.') ?></h3>
                <small class="text-success"><i class="fas fa-clock me-1"></i>Pendentes e Atrasadas</small>
            </div>
        </div>

        <!-- Contas a Pagar -->
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm border-start border-danger border-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">A Pagar (Mês)</h6>
                    <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-circle">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy">R$ <?= number_format($pagar_mes, 2, ',', '.') ?></h3>
                <small class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Previsão de Saída</small>
            </div>
        </div>

        <!-- Saldo Previsto -->
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm border-start <?= $saldo_mes >= 0 ? 'border-primary' : 'border-warning' ?> border-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">Balanço Previsto</h6>
                    <div class="icon-shape <?= $saldo_mes >= 0 ? 'bg-primary text-primary' : 'bg-warning text-warning' ?> bg-opacity-10 rounded-circle">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy">R$ <?= number_format($saldo_mes, 2, ',', '.') ?></h3>
                <small class="text-muted">Projeção do mês atual</small>
            </div>
        </div>
    </div>

    <!-- ACTIONS -->
    <h5 class="fw-bold text-navy mb-3">Painéis de Controle</h5>
    <div class="row g-3">
        <?php
        $actions = [
            ['label' => 'Contas a Receber', 'icon' => 'fas fa-hand-holding-usd', 'link' => 'contas_receber.php', 'color' => 'text-success'],
            ['label' => 'Contas a Pagar', 'icon' => 'fas fa-file-invoice-dollar', 'link' => 'contas_pagar.php', 'color' => 'text-danger'],
            ['label' => 'Fluxo de Caixa', 'icon' => 'fas fa-chart-line', 'link' => 'fluxo_caixa.php', 'color' => 'text-primary'],
            ['label' => 'Relatórios DRE', 'icon' => 'fas fa-file-contract', 'link' => 'relatorios.php', 'color' => 'text-secondary'],
        ];

        foreach($actions as $act):
        ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= $act['link'] ?>" class="card h-100 border-0 shadow-sm text-decoration-none card-hover-effect">
                <div class="card-body text-center p-4">
                    <div class="<?= $act['color'] ?> mb-3" style="font-size: 2rem;"><i class="<?= $act['icon'] ?>"></i></div>
                    <span class="text-dark fw-bold d-block"><?= $act['label'] ?></span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .text-navy { color: #0A2647; }
    .card-hover-effect:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,.08)!important; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
