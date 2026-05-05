<?php
/**
 * View: relatorios.php (Relatório DRE)
 * Módulo Financeiro - Brasallis Hub
 */
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Verificação de Autenticação e Permissão
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../login.php');
    exit;
}
if (!check_permission('financeiro', 'leitura')) {
    header('Location: ../../../admin/painel_admin.php?error=acesso_negado');
    exit;
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Filtro de Mês/Ano
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

// Inicialização de Variáveis DRE
$receita_bruta = 0;
$impostos = 0;
$despesas_operacionais = 0;
$outras_receitas = 0;

try {
    // 1. Receita Operacional Bruta (Vendas do Mês)
    $stmtV = $conn->prepare("SELECT SUM(total_amount) FROM vendas WHERE empresa_id = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ?");
    $stmtV->execute([$empresa_id, $mes, $ano]);
    $receita_bruta = (float)$stmtV->fetchColumn() ?: 0;

    // 2. Outras Receitas (Contas a Receber que não são vendas diretas, se houver)
    // Para simplificar, pegamos todas as receitas registradas no dia que não tenham 'Venda' na descrição
    $stmtR = $conn->prepare("SELECT SUM(valor) FROM contas_receber WHERE empresa_id = ? AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ? AND status = 'recebido' AND descricao NOT LIKE '%Venda%'");
    $stmtR->execute([$empresa_id, $mes, $ano]);
    $outras_receitas = (float)$stmtR->fetchColumn() ?: 0;

    // 3. Impostos e Deduções (da tabela fiscal_notas)
    $stmtI = $conn->prepare("SELECT SUM(valor_impostos) FROM fiscal_notas WHERE empresa_id = ? AND MONTH(data_emissao) = ? AND YEAR(data_emissao) = ? AND status = 'autorizada'");
    $stmtI->execute([$empresa_id, $mes, $ano]);
    $impostos = (float)$stmtI->fetchColumn() ?: 0;

    // 4. Despesas Operacionais (Contas a Pagar pagas no mês)
    $stmtD = $conn->prepare("SELECT SUM(valor) FROM contas_pagar WHERE empresa_id = ? AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = ? AND status = 'pago'");
    $stmtD->execute([$empresa_id, $mes, $ano]);
    $despesas_operacionais = (float)$stmtD->fetchColumn() ?: 0;

} catch (Exception $e) {
    $error = "Erro ao processar relatório: " . $e->getMessage();
}

// Cálculos Intermediários
$receita_liquida = ($receita_bruta + $outras_receitas) - $impostos;
$lucro_liquido = $receita_liquida - $despesas_operacionais;
$margem_lucro = ($receita_bruta > 0) ? ($lucro_liquido / ($receita_bruta + $outras_receitas)) * 100 : 0;

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-file-contract me-2 text-secondary"></i>Demonstrativo de Resultado (DRE)</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Financeiro</a></li>
                    <li class="breadcrumb-item active">Relatório DRE</li>
                </ol>
            </nav>
        </div>
        <div>
            <form method="GET" class="d-flex gap-2">
                <select name="mes" class="form-select border-0 shadow-sm" onchange="this.form.submit()">
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $mes ? 'selected' : '' ?>><?= sprintf('%02d', $i) ?> - <?= date('M', mktime(0,0,0,$i,10)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="ano" class="form-select border-0 shadow-sm" style="width: 100px;" onchange="this.form.submit()">
                    <?php for($i = date('Y')-2; $i <= date('Y'); $i++): ?>
                        <option value="<?= $i ?>" <?= $i == $ano ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger shadow-sm border-0"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header bg-navy text-white p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-uppercase ls-1">Estrutura DRE - <?= sprintf('%02d/%d', $mes, $ano) ?></h5>
                        <img src="/assets/img/pureza.png" alt="Logo" style="height: 30px; filter: brightness(0) invert(1);">
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <tbody>
                            <!-- RECEITAS -->
                            <tr class="bg-light">
                                <td class="py-3 px-4 fw-bold">1. RECEITA OPERACIONAL BRUTA</td>
                                <td class="py-3 px-4 text-end fw-bold">R$ <?= number_format($receita_bruta + $outras_receitas, 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="py-2 px-4 ps-5 text-muted">Vendas de Produtos/Serviços</td>
                                <td class="py-2 px-4 text-end">R$ <?= number_format($receita_bruta, 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="py-2 px-4 ps-5 text-muted">Outras Receitas Operacionais</td>
                                <td class="py-2 px-4 text-end">R$ <?= number_format($outras_receitas, 2, ',', '.') ?></td>
                            </tr>

                            <!-- DEDUÇÕES -->
                            <tr>
                                <td class="py-3 px-4 text-danger fw-bold">2. (-) DEDUÇÕES E IMPOSTOS</td>
                                <td class="py-3 px-4 text-end text-danger fw-bold">- R$ <?= number_format($impostos, 2, ',', '.') ?></td>
                            </tr>

                            <!-- RECEITA LÍQUIDA -->
                            <tr class="bg-light">
                                <td class="py-3 px-4 fw-bold">3. (=) RECEITA OPERACIONAL LÍQUIDA</td>
                                <td class="py-3 px-4 text-end fw-bold text-primary">R$ <?= number_format($receita_liquida, 2, ',', '.') ?></td>
                            </tr>

                            <!-- DESPESAS -->
                            <tr>
                                <td class="py-3 px-4 text-danger fw-bold">4. (-) DESPESAS OPERACIONAIS</td>
                                <td class="py-3 px-4 text-end text-danger fw-bold">- R$ <?= number_format($despesas_operacionais, 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td class="py-2 px-4 ps-5 text-muted small">Custos Fixos, Variáveis e Administrativos</td>
                                <td class="py-2 px-4 text-end small">R$ <?= number_format($despesas_operacionais, 2, ',', '.') ?></td>
                            </tr>

                            <!-- RESULTADO FINAL -->
                            <tr class="<?= $lucro_liquido >= 0 ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10' ?>">
                                <td class="py-4 px-4 fw-black fs-5">(=) RESULTADO LÍQUIDO DO EXERCÍCIO</td>
                                <td class="py-4 px-4 text-end fw-black fs-5 <?= $lucro_liquido >= 0 ? 'text-success' : 'text-danger' ?>">
                                    R$ <?= number_format($lucro_liquido, 2, ',', '.') ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-0 p-4">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h6 class="text-muted small text-uppercase fw-bold mb-1">Margem Líquida</h6>
                            <h4 class="fw-bold mb-0 <?= $margem_lucro >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($margem_lucro, 1, ',', '.') ?>%</h4>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted small text-uppercase fw-bold mb-1">Status do Período</h6>
                            <span class="badge rounded-pill <?= $lucro_liquido >= 0 ? 'bg-success' : 'bg-danger' ?> px-3 py-2">
                                <?= $lucro_liquido >= 0 ? 'SUPERÁVIT' : 'DÉFICIT' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                    <i class="fas fa-print me-2"></i>Imprimir Relatório
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .text-navy { color: #0A2647; }
    .bg-navy { background-color: #0A2647; }
    .fw-black { font-weight: 900; }
    .ls-1 { letter-spacing: 1px; }
    @media print {
        .ultra-nav, .breadcrumb, form, .btn, .ambient-bg { display: none !important; }
        .container-fluid { padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #eee !important; }
        body { background: white !important; }
    }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
