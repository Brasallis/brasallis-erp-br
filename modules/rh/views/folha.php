<?php
// modules/rh/views/folha.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('rh', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$params = check_permission('rh', 'escrita'); 
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Get all active employees to generate a mock payroll
try {
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.username as nome, u.user_type, s.nome as setor_nome 
        FROM usuarios u 
        LEFT JOIN setores s ON u.setor_id = s.id 
        WHERE u.empresa_id = ? AND u.status = 'ativo'
        ORDER BY u.username ASC
    ");
    $stmt->execute([$empresa_id]);
    $funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $funcionarios = [];
}

// Calculate totals
$salario_base_padrao = 2500.00; // Mock salary per employee if DB doesn't have it
$encargos_pct = 0.35; // 35% estimated taxes
$total_bruto = count($funcionarios) * $salario_base_padrao;
$total_encargos = $total_bruto * $encargos_pct;
$total_liquido = $total_bruto - ($total_bruto * 0.08); // Mock 8% INSS discount
$custo_total = $total_bruto + $total_encargos; // Custo empresa

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-file-invoice-dollar me-2 text-success"></i>Resumo da Folha</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Recursos Humanos</a></li>
                    <li class="breadcrumb-item active">Folha de Pagamento</li>
                </ol>
            </nav>
        </div>
        <div>
            <button class="btn btn-outline-secondary shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Imprimir Resumo
            </button>
        </div>
    </div>

    <!-- METRICS -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm border-start border-primary border-4">
                <h6 class="text-secondary text-uppercase small fw-bold mb-2">Total Bruto Estimado</h6>
                <h4 class="fw-bold text-navy mb-0">R$ <?= number_format($total_bruto, 2, ',', '.') ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm border-start border-warning border-4">
                <h6 class="text-secondary text-uppercase small fw-bold mb-2">Encargos (Aprox. 35%)</h6>
                <h4 class="fw-bold text-warning mb-0">R$ <?= number_format($total_encargos, 2, ',', '.') ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm border-start border-success border-4 bg-success bg-opacity-10">
                <h6 class="text-success text-uppercase small fw-bold mb-2">Líquido a Pagar</h6>
                <h4 class="fw-bold text-success mb-0">R$ <?= number_format($total_liquido, 2, ',', '.') ?></h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm border-start border-danger border-4 bg-navy text-white">
                <h6 class="text-white-50 text-uppercase small fw-bold mb-2">Custo Total (Empresa)</h6>
                <h4 class="fw-bold text-white mb-0">R$ <?= number_format($custo_total, 2, ',', '.') ?></h4>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold text-navy">Provisão por Colaborador (Valores Padrão)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 px-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Colaborador</th>
                        <th class="py-3 px-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Setor / Cargo</th>
                        <th class="py-3 px-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Salário Base</th>
                        <th class="py-3 px-4 text-secondary text-uppercase" style="font-size: 0.8rem;">Descontos Est. (8%)</th>
                        <th class="py-3 px-4 text-end text-secondary text-uppercase" style="font-size: 0.8rem;">Líquido Aprox.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($funcionarios)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhum funcionário ativo para calcular a folha.</td></tr>
                    <?php else: ?>
                        <?php foreach($funcionarios as $f): 
                            $liq = $salario_base_padrao - ($salario_base_padrao * 0.08); // Mock
                        ?>
                        <tr>
                            <td class="py-3 px-4 fw-bold text-dark"><?= htmlspecialchars($f['nome'] ?: $f['username']) ?></td>
                            <td class="py-3 px-4 text-muted"><?= htmlspecialchars($f['setor_nome'] ?? 'Geral') ?> (<?= ucfirst($f['user_type']) ?>)</td>
                            <td class="py-3 px-4 fw-bold text-dark">R$ <?= number_format($salario_base_padrao, 2, ',', '.') ?></td>
                            <td class="py-3 px-4 text-danger">- R$ <?= number_format($salario_base_padrao * 0.08, 2, ',', '.') ?></td>
                            <td class="py-3 px-4 fw-bold text-success text-end">R$ <?= number_format($liq, 2, ',', '.') ?></td>
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
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
