<?php
// modules/crm/views/index.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';
require_once __DIR__ . '/../../../includes/cabecalho.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('crm', 'leitura')) { header('Location: ../../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$params = check_permission('crm', 'escrita'); 
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

try {
    // Total Clientes
    $stmt = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE empresa_id = ? AND status = 'ativo'");
    $stmt->execute([$empresa_id]);
    $total_clientes = $stmt->fetchColumn();

    // Leads/Oportunidades em Aberto (Negociação)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM crm_oportunidades WHERE empresa_id = ? AND status = 'aberto'");
    $stmt->execute([$empresa_id]);
    $leads_ativos = $stmt->fetchColumn();

    // Total de Oportunidades (Geral)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM crm_oportunidades WHERE empresa_id = ?");
    $stmt->execute([$empresa_id]);
    $oportunidades = $stmt->fetchColumn();

} catch (Exception $e) {
    // Silent fail or log
    $total_clientes = 0;
    $leads_ativos = 0;
    $oportunidades = 0;
}

?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-handshake me-2"></i>CRM & Vendas</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item active">Gestão de Clientes</li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if ($params): ?>
            <a href="cliente_form.php" class="btn btn-trust-primary me-2"><i class="fas fa-plus me-2"></i>Novo Cliente</a>
            <a href="kanban.php" class="btn btn-outline-primary"><i class="fas fa-bullhorn me-2"></i>Ver Pipeline</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- METRICS -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">Base de Clientes</h6>
                    <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-circle">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy"><?= $total_clientes ?></h3>
                <small class="text-muted">Cadastros ativos</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">Leads em Negociação</h6>
                    <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded-circle">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy"><?= $leads_ativos ?></h3>
                <small class="text-warning fw-bold">Potenciais vendas</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">Oportunidades</h6>
                    <div class="icon-shape bg-success bg-opacity-10 text-success rounded-circle">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy"><?= $oportunidades ?></h3>
                <small class="text-success">Fechamentos previstos</small>
            </div>
        </div>
    </div>

    <!-- ACTIONS -->
    <h5 class="fw-bold text-navy mb-3">Atalhos de Vendas</h5>
    <div class="row g-3">
        <?php
        $actions = [
            ['label' => 'Meus Clientes', 'icon' => 'fas fa-users', 'link' => 'clientes.php', 'perm' => true],
            ['label' => 'Pipeline de Vendas', 'icon' => 'fas fa-filter', 'link' => 'kanban.php', 'perm' => true],
            ['label' => 'Novo Negócio', 'icon' => 'fas fa-plus-circle', 'link' => 'kanban.php?action=new', 'perm' => $params],
        ];

        foreach($actions as $act):
            if(!$act['perm']) continue;
        ?>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="<?= $act['link'] ?>" class="card h-100 border-0 shadow-sm text-decoration-none card-hover-effect">
                <div class="card-body text-center p-3">
                    <div class="text-secondary mb-2" style="font-size: 1.5rem;"><i class="<?= $act['icon'] ?>"></i></div>
                    <span class="text-dark small fw-bold d-block"><?= $act['label'] ?></span>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .text-navy { color: #0A2647; }
    .card-hover-effect:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
