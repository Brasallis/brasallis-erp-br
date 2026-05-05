<?php
// modules/rh/views/index.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';
require_once __DIR__ . '/../../../includes/cabecalho.php';

// Check Auth & Permission
// In future: checkPermission('rh', 'leitura');

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Mock Metrics (Simulando dados pois ainda não há tabelas de RH complexas)
$total_colaboradores = 0; // Teria que pegar de 'usuarios' associados
$folha_estimada = 0;

try {
    // Count users in company
    $stmt = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ?");
    $stmt->execute([$empresa_id]);
    $total_colaboradores = $stmt->fetchColumn();
    
    $folha_estimada = $total_colaboradores * 2500; // Mock avg salary
} catch (PDOException $e) {
    // Silent fail
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-users me-2"></i>Recursos Humanos</h2>
            <p class="text-secondary small">Gestão de talentos e departamento pessoal.</p>
        </div>
        <div>
            <a href="colaboradores.php" class="btn btn-outline-primary me-2 shadow-sm rounded-pill px-4 fw-bold"><i class="fas fa-list me-2"></i>Lista de Colaboradores</a>
            <a href="colaboradores.php?action=new" class="btn btn-trust-primary shadow-sm rounded-pill px-4 fw-bold"><i class="fas fa-plus me-2"></i>Novo Contrato</a>
        </div>
    </div>

    <!-- RH DASHBOARD WIDGETS -->
    <div class="row g-4">
        <!-- Metric 1 -->
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">Colaboradores Ativos</h6>
                    <div class="icon-shape bg-info bg-opacity-10 text-info rounded-circle">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy"><?= $total_colaboradores ?></h3>
                <small class="text-success"><i class="fas fa-arrow-up me-1"></i>Estável</small>
            </div>
        </div>
        
        <!-- Metric 2 -->
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-secondary text-uppercase small fw-bold">Folha Estimada</h6>
                    <div class="icon-shape bg-success bg-opacity-10 text-success rounded-circle">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-navy">R$ <?= number_format($folha_estimada, 2, ',', '.') ?></h3>
                <small class="text-muted">Baseado em média salarial</small>
            </div>
        </div>

        <!-- Metric 3 -->
        <div class="col-md-4">
            <div class="card card-dashboard h-100 p-4 border-0 shadow-sm bg-navy text-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-white-50 text-uppercase small fw-bold">Aniversariantes</h6>
                    <div class="icon-shape bg-white bg-opacity-25 text-white rounded-circle">
                        <i class="fas fa-birthday-cake"></i>
                    </div>
                </div>
                <h3 class="fw-bold">0</h3>
                <small class="text-white-50">Nenhum aniversariante hoje</small>
            </div>
        </div>
    </div>

    <!-- QUICK ACCESS -->
    <h5 class="fw-bold text-navy mb-3 mt-4">Gestão de Pessoas</h5>
    <div class="row g-3">
        <div class="col-md-3">
            <a href="colaboradores.php" class="card h-100 border-0 shadow-sm text-decoration-none card-hover-effect">
                <div class="card-body text-center p-3">
                    <div class="text-secondary mb-2" style="font-size: 1.5rem;"><i class="fas fa-users"></i></div>
                    <span class="text-dark small fw-bold d-block">Colaboradores</span>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="folha.php" class="card h-100 border-0 shadow-sm text-decoration-none card-hover-effect">
                <div class="card-body text-center p-3">
                    <div class="text-secondary mb-2" style="font-size: 1.5rem;"><i class="fas fa-file-invoice-dollar"></i></div>
                    <span class="text-dark small fw-bold d-block">Folha de Pagamento</span>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="ponto.php" class="card h-100 border-0 shadow-sm text-decoration-none card-hover-effect">
                <div class="card-body text-center p-3">
                    <div class="text-secondary mb-2" style="font-size: 1.5rem;"><i class="fas fa-clock"></i></div>
                    <span class="text-dark small fw-bold d-block">Controle de Ponto</span>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="#" class="card h-100 border-0 shadow-sm text-decoration-none card-hover-effect">
                <div class="card-body text-center p-3">
                    <div class="text-secondary mb-2" style="font-size: 1.5rem;"><i class="fas fa-calendar-check"></i></div>
                    <span class="text-dark small fw-bold d-block">Férias & Ausências</span>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    .text-navy { color: #0A2647; }
    .bg-navy { background-color: #0A2647; }
    .card-hover-effect:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
