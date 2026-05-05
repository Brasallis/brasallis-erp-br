<?php
// modules/fiscal/views/index.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';
require_once __DIR__ . '/../../../includes/cabecalho.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('fiscal', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$params = check_permission('fiscal', 'escrita'); 
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Metrics Logic
$nfs_emitidas = 0;
$impostos_mes = 0;
$nfs_recebidas = 0;
$mes_atual = date('m');
$ano_atual = date('Y');

try {
    // NFs Emitidas (Mês Atual)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM fiscal_notas WHERE empresa_id = ? AND tipo = 'saida' AND MONTH(data_emissao) = ? AND YEAR(data_emissao) = ?");
    $stmt->execute([$empresa_id, $mes_atual, $ano_atual]);
    $nfs_emitidas = $stmt->fetchColumn() ?: 0;

    // NFs Recebidas (Mês Atual)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM fiscal_notas WHERE empresa_id = ? AND tipo = 'entrada' AND MONTH(data_emissao) = ? AND YEAR(data_emissao) = ?");
    $stmt->execute([$empresa_id, $mes_atual, $ano_atual]);
    $nfs_recebidas = $stmt->fetchColumn() ?: 0;

    // Impostos Estimados (Soma de valor_impostos das notas emitidas)
    $stmt = $conn->prepare("SELECT SUM(valor_impostos) FROM fiscal_notas WHERE empresa_id = ? AND tipo = 'saida' AND MONTH(data_emissao) = ? AND YEAR(data_emissao) = ? AND status = 'autorizada'");
    $stmt->execute([$empresa_id, $mes_atual, $ano_atual]);
    $impostos_mes = $stmt->fetchColumn() ?: 0;

    // Recent Notes (Last 5)
    $stmt = $conn->prepare("SELECT * FROM fiscal_notas WHERE empresa_id = ? ORDER BY data_emissao DESC LIMIT 5");
    $stmt->execute([$empresa_id]);
    $recent_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Silent fail
}

?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-file-invoice-dollar me-2"></i>Fiscal & Tributário</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item active">Fiscal</li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if ($params): ?>
            <a href="nota_form.php" class="btn btn-trust-primary me-2"><i class="fas fa-file-signature me-2"></i>Lançar Nota (Manual)</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- WARNING BANNER -->
    <div class="alert alert-warning border-0 shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x me-3"></i>
            <div>
                <h6 class="fw-bold mb-0">Configuração de Certificado Digital</h6>
                <p class="mb-0 small">Este módulo atualmente opera em modo de LANÇAMENTO MANUAL. A emissão real via SEFAZ requer certificado A1.</p>
            </div>
            <a href="#" class="btn btn-warning btn-sm ms-auto fw-bold">Saiba mais</a>
        </div>
    </div>

    <!-- METRICS -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">Notas Emitidas (Mês)</h6>
                    <div class="icon-shape bg-info bg-opacity-10 text-info rounded-circle">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy"><?= $nfs_emitidas ?></h3>
                <small class="text-muted">Saídas registradas</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">Notas Recebidas (Mês)</h6>
                    <div class="icon-shape bg-success bg-opacity-10 text-success rounded-circle">
                        <i class="fas fa-inbox"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy"><?= $nfs_recebidas ?></h3>
                <small class="text-muted">Entradas registradas</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">Impostos (Estimado)</h6>
                    <div class="icon-shape bg-danger bg-opacity-10 text-danger rounded-circle">
                        <i class="fas fa-percent"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy">R$ <?= number_format($impostos_mes, 2, ',', '.') ?></h3>
                <small class="text-danger">Baseado em NFs autorizadas</small>
            </div>
        </div>
    </div>

    <!-- ACTIONS -->
    <h5 class="fw-bold text-navy mb-3">Livros Fiscais</h5>
    <div class="row g-3">
        <?php
        $actions = [
            ['label' => 'Todas as Notas', 'icon' => 'fas fa-list', 'link' => 'notas.php', 'perm' => true],
            ['label' => 'Lançar Saída', 'icon' => 'fas fa-file-export', 'link' => 'nota_form.php?tipo=saida', 'perm' => $params],
            ['label' => 'Lançar Entrada', 'icon' => 'fas fa-file-import', 'link' => 'nota_form.php?tipo=entrada', 'perm' => $params],
            ['label' => 'Relatórios XML', 'icon' => 'fas fa-file-code', 'link' => '#', 'perm' => true],
        ];

        foreach($actions as $act):
            if(!$act['perm']) continue;
        ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= $act['link'] ?>" class="card h-100 border-0 shadow-sm text-decoration-none card-hover-effect">
                <div class="card-body text-center p-3">
                    <div class="text-secondary mb-2" style="font-size: 1.5rem;"><i class="<?= $act['icon'] ?>"></i></div>
                    <span class="text-dark small fw-bold d-block"><?= $act['label'] ?></span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- RECENT ACTIVITY -->
    <h5 class="fw-bold text-navy mb-3 mt-4">Últimas Movimentações</h5>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Número</th>
                            <th>Entidade</th>
                            <th>Emissão</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recent_notes)): ?>
                        <tr><td colspan="6" class="text-center py-3 text-muted">Nenhuma nota recente.</td></tr>
                        <?php else: ?>
                            <?php foreach($recent_notes as $n): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= $n['numero'] ?></td>
                                <td><span class="d-block text-truncate" style="max-width: 200px;"><?= $n['emitente_destinatario'] ?></span></td>
                                <td><?= date('d/m/y', strtotime($n['data_emissao'])) ?></td>
                                <td>R$ <?= number_format($n['valor_total'], 2, ',', '.') ?></td>
                                <td>
                                    <span class="badge bg-<?= $n['status'] == 'autorizada' ? 'success' : ($n['status'] == 'cancelada' ? 'danger' : 'secondary') ?> bg-opacity-10 text-<?= $n['status'] == 'autorizada' ? 'success' : ($n['status'] == 'cancelada' ? 'danger' : 'secondary') ?>">
                                        <?= strtoupper($n['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="invoice_view.php?id=<?= $n['id'] ?>" target="_blank" class="text-secondary small me-2"><i class="fas fa-print"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3 text-center border-top">
                <a href="notas.php" class="text-decoration-none fw-bold small">Ver Todas as Notas <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<style>
    .text-navy { color: #0A2647; }
    .card-hover-effect:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
