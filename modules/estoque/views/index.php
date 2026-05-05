<?php
// modules/estoque/views/index.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';
// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('estoque', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

require_once __DIR__ . '/../../../includes/cabecalho.php';

$params = check_permission('estoque', 'escrita'); // Boolean for UI control

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Metrics
$total_produtos = 0;
$baixo_estoque = 0;
$valor_estoque = 0;

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN estoque_atual <= estoque_minimo THEN 1 ELSE 0 END) as baixo, SUM(estoque_atual * preco_compra) as valor FROM produtos WHERE empresa_id = ?");
    $stmt->execute([$empresa_id]);
    $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_produtos = $metrics['total'] ?? 0;
    $baixo_estoque = $metrics['baixo'] ?? 0;
    $valor_estoque = $metrics['valor'] ?? 0;
} catch (Exception $e) { /* silent */ }
?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-5 pb-4 border-bottom border-light">
        <div class="col-lg-8">
            <div class="metric-label mb-2"><i class="fas fa-boxes me-1 text-primary"></i> Brasallis Inventory</div>
            <h1 class="greeting">Estoque Inteligente</h1>
            <p class="text-muted mb-0 mt-2" style="font-weight: 500;">Gestão estratégica de ativos e suprimentos.</p>
        </div>
        <div class="col-lg-4 text-end">
             <a href="../../../admin/produtos.php?action=new" class="btn btn-dark shadow-sm rounded-pill px-4 py-2 fw-bold" style="font-size: 0.8rem;">
                <i class="fas fa-plus me-2 opacity-50"></i> Novo Produto
            </a>
        </div>
    </div>

    <!-- METRICS -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <a href="../../../admin/produtos.php" class="clickable-card">
                <div class="exec-card">
                    <div class="d-flex justify-content-between mb-4">
                        <span class="metric-label">Total de Itens</span>
                        <i class="fas fa-cube text-dark opacity-10 fs-5"></i>
                    </div>
                    <div class="metric-value"><?= $total_produtos ?></div>
                    <div class="metric-label mb-3">Produtos Cadastrados</div>
                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="metric-label">Status:</span>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-bold" style="font-size: 0.65rem;">OPERACIONAL</span>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="../../../admin/produtos.php?filter=low_stock" class="clickable-card">
                <div class="exec-card <?= $baixo_estoque > 0 ? 'border-danger border-opacity-10' : '' ?>">
                    <div class="d-flex justify-content-between mb-4">
                        <span class="metric-label">Alertas Críticos</span>
                        <i class="fas fa-exclamation-triangle <?= $baixo_estoque > 0 ? 'text-danger' : 'text-dark opacity-10' ?> fs-5"></i>
                    </div>
                    <div class="metric-value" style="<?= $baixo_estoque > 0 ? 'color: #ff3b30;' : '' ?>"><?= $baixo_estoque ?></div>
                    <div class="metric-label mb-3">Abaixo do Mínimo</div>
                    <div class="mt-auto pt-3 border-top">
                        <div class="progress-track"><div class="progress-fill <?= $baixo_estoque > 0 ? 'bg-danger' : '' ?>" style="width: <?= $total_produtos > 0 ? ($baixo_estoque/$total_produtos)*100 : 0 ?>%"></div></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <div class="exec-card dark-panel">
                <div class="d-flex justify-content-between mb-4">
                    <span class="metric-label text-white-50">Patrimônio em Estoque</span>
                    <i class="fas fa-sack-dollar text-white opacity-20 fs-5"></i>
                </div>
                <div class="metric-value">R$ <?= number_format($valor_estoque, 2, ',', '.') ?></div>
                <div class="metric-label text-white-50 mb-3">Valor de Venda Potencial</div>
                <p class="small mt-auto pt-3 mb-0 border-top border-white border-opacity-10 text-muted" style="font-size: 0.75rem;">Baseado no preço de custo atual.</p>
            </div>
        </div>
    </div>

    <!-- OPERATIONS: APP LIBRARY STYLE -->
    <div class="mb-5">
        <h6 class="metric-label mb-4 opacity-50">Biblioteca de Operações</h6>
        <div class="row g-4">
            <?php
            $actions = [
                ['label' => 'Catálogo', 'icon' => 'fas fa-list-ul', 'link' => '../../../admin/produtos.php', 'color' => '#f2f2f7'],
                ['label' => 'Entradas', 'icon' => 'fas fa-arrow-down-long', 'link' => '../../../admin/movimentacoes.php?type=entrada', 'color' => '#e5f9f2'],
                ['label' => 'Saídas', 'icon' => 'fas fa-arrow-up-long', 'link' => '../../../admin/movimentacoes.php?type=saida', 'color' => '#fff2f2'],
                ['label' => 'Categorias', 'icon' => 'fas fa-tags', 'link' => '../../../admin/categorias.php', 'color' => '#f2f2f7'],
                ['label' => 'Fornecedores', 'icon' => 'fas fa-handshake', 'link' => '../../../admin/fornecedores.php', 'color' => '#f2f2f7'],
                ['label' => 'Dashboards', 'icon' => 'fas fa-chart-pie', 'link' => '../../../admin/relatorios.php', 'color' => '#f2f2f7'],
            ];

            foreach($actions as $act):
            ?>
            <div class="col-6 col-md-4 col-lg-2 text-center">
                <a href="<?= $act['link'] ?>" class="text-decoration-none d-block">
                    <div class="app-library-icon mb-3" style="background: <?= $act['color'] ?>; width: 80px; height: 80px; margin: 0 auto; border-radius: 22px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <i class="<?= $act['icon'] ?>" style="color: #1d1d1f; opacity: 0.8;"></i>
                    </div>
                    <span class="d-block fw-bold text-dark" style="font-size: 0.75rem; letter-spacing: -0.2px;"><?= $act['label'] ?></span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
    .app-library-icon { transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease; cursor: pointer; }
    .app-library-icon:hover { transform: translateY(-5px); box-shadow: 0 8px 24px rgba(0,0,0,0.06) !important; }
    .app-library-icon:active { transform: scale(0.9); }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
