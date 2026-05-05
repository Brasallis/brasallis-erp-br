<?php
// modules/fiscal/views/notas.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('fiscal', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$params = check_permission('fiscal', 'escrita'); 
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Filtro de mês
$mes_filtro = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano_filtro = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

// Fetch Notas
try {
    $stmt = $conn->prepare("
        SELECT * FROM fiscal_notas 
        WHERE empresa_id = ? AND MONTH(data_emissao) = ? AND YEAR(data_emissao) = ? 
        ORDER BY data_emissao DESC, id DESC
    ");
    $stmt->execute([$empresa_id, $mes_filtro, $ano_filtro]);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $notas = [];
}

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-list me-2 text-primary"></i>Livro de Notas Fiscais</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Fiscal</a></li>
                    <li class="breadcrumb-item active">Todas as Notas</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <select name="mes" class="form-select border-0 shadow-sm" onchange="this.form.submit()">
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $mes_filtro ? 'selected' : '' ?>><?= sprintf('%02d', $i) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="ano" class="form-select border-0 shadow-sm" style="width: 100px;" onchange="this.form.submit()">
                    <?php for($i = date('Y')-1; $i <= date('Y')+1; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $ano_filtro ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <?php if ($params): ?>
            <a href="nota_form.php?tipo=saida" class="btn btn-primary shadow-sm fw-bold">
                <i class="fas fa-plus me-2"></i>Nova NFe/NFCe
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 px-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Número / Tipo</th>
                        <th class="py-3 px-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Emitente / Destinatário</th>
                        <th class="py-3 px-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Data</th>
                        <th class="py-3 px-4 text-secondary text-uppercase text-center" style="font-size: 0.8rem;">Status</th>
                        <th class="py-3 px-4 text-end text-secondary text-uppercase" style="font-size: 0.8rem;">Valores (Total / Imposto)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notas)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhuma nota encontrada no período selecionado.</td></tr>
                    <?php else: ?>
                        <?php foreach($notas as $n): ?>
                        <tr>
                            <td class="py-3 px-4">
                                <div class="fw-bold text-navy">Nº <?= htmlspecialchars($n['numero']) ?></div>
                                <small class="fw-bold <?= $n['tipo'] == 'entrada' ? 'text-success' : 'text-danger' ?>">
                                    <i class="fas fa-arrow-<?= $n['tipo'] == 'entrada' ? 'down' : 'up' ?> me-1"></i><?= ucfirst($n['tipo']) ?>
                                </small>
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-dark fw-bold"><?= htmlspecialchars($n['emitente_destinatario']) ?></div>
                                <?php if($n['chave_acesso']): ?>
                                <small class="text-muted" style="font-family: monospace; font-size: 0.75rem;"><?= htmlspecialchars($n['chave_acesso']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-dark"><?= date('d/m/Y', strtotime($n['data_emissao'])) ?></td>
                            <td class="py-3 px-4 text-center">
                                <?php
                                $badge_class = 'secondary';
                                if($n['status'] == 'autorizada') $badge_class = 'success';
                                if($n['status'] == 'cancelada') $badge_class = 'danger';
                                if($n['status'] == 'pendente') $badge_class = 'warning';
                                ?>
                                <span class="badge bg-<?= $badge_class ?>-light text-<?= $badge_class ?> px-3 py-2 rounded-pill">
                                    <?= strtoupper($n['status']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-end">
                                <div class="fw-bold text-dark">R$ <?= number_format($n['valor_total'], 2, ',', '.') ?></div>
                                <small class="text-danger">Imp: R$ <?= number_format($n['valor_impostos'], 2, ',', '.') ?></small>
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
    .bg-success-light { background-color: rgba(40,167,69,0.1); }
    .bg-danger-light { background-color: rgba(220,53,69,0.1); }
    .bg-warning-light { background-color: rgba(255,193,7,0.1); }
    .bg-secondary-light { background-color: rgba(108,117,125,0.1); }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
