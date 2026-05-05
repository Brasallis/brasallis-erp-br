<?php
// admin/agentes_ia.php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';
require_once '../classes/AIAgent.php';

// Check auth
if (!isset($_SESSION['empresa_id'])) {
    header('Location: ../login.php');
    exit;
}

$conn = connect_db();
$aiAgent = new App\AIAgent($conn);
$empresa_id = $_SESSION['empresa_id'];

// Initial Fetch
$agents = $aiAgent->getAll($empresa_id);
$stats = $aiAgent->getUsageStats($empresa_id);

include_once '../includes/cabecalho.php';

// Acesso agora é controlado pela navegação central e planos_config.php
?>

<style>
    /* Premium UI for Agents */
    .agent-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .agent-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .status-active { background: #e6f4ea; color: #1e8e3e; }
    .status-inactive { background: #f1f3f4; color: #5f6368; }

    /* Stats Card */
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        height: 100%;
    }
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a73e8;
    }
    .stat-label {
        color: #5f6368;
        font-size: 0.9rem;
        font-weight: 500;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-5 gap-3">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-robot me-2 text-primary"></i>Agentes Especialistas IA</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Inteligência</a></li>
                    <li class="breadcrumb-item active">Agentes</li>
                </ol>
            </nav>
            <p class="text-secondary mt-2 mb-0">Gerencie e monitore seus assistentes virtuais.</p>
        </div>
        <div class="w-100 w-md-auto text-md-end">
            <a href="agentes_ia_form.php" class="btn btn-dark shadow-sm fw-bold rounded-pill px-4">
                <i class="fas fa-plus me-2"></i>Novo Agente
            </a>
        </div>
    </div>

    <?php
    // Carregar Status do Plano
    require_once '../classes/AIPlanManager.php';
    $planManager = new App\AIPlanManager($conn, $empresa_id); 
    $planStatus = $planManager->getPlanStatus();
    ?>

    <!-- AI Consumption Widget -->
    <div class="card exec-card mb-5 border-0">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-4">
            
            <!-- Plan Info -->
            <div class="d-flex align-items-center gap-3">
                <div class="icon-circle bg-<?php echo $planStatus['color']; ?>-subtle text-<?php echo $planStatus['color']; ?> shadow-sm" style="width: 54px; height: 54px; font-size: 1.5rem;">
                    <i class="fas fa-microchip"></i>
                </div>
                <div>
                    <h6 class="metric-label mb-1">Seu Plano</h6>
                    <h3 class="fw-bold mb-0 text-navy">
                        <?php echo $planStatus['label']; ?>
                        <span class="badge badge-soft badge-soft-<?php echo $planStatus['color']; ?> align-middle ms-2">ATIVO</span>
                    </h3>
                </div>
            </div>

            <!-- Usage Bar -->
            <div class="flex-grow-1" style="max-width: 400px; min-width: 250px;">
                <div class="d-flex justify-content-between mb-2">
                    <span class="metric-label mb-0">Consumo Mensal</span>
                    <span class="small fw-bold text-<?php echo $planStatus['color']; ?>">
                        <?php echo number_format($planStatus['used']); ?> / <?php echo $planStatus['limit'] > 999999 ? 'Ilimitado' : number_format($planStatus['limit']); ?> tokens
                    </span>
                </div>
                <div class="progress-track" style="height: 8px;">
                    <?php if ($planStatus['limit'] > 999999): ?>
                         <div class="progress-fill bg-success" style="width: 100%"></div>
                    <?php else: ?>
                        <div class="progress-fill bg-<?php echo $planStatus['color']; ?>" style="width: <?php echo $planStatus['percentage']; ?>%"></div>
                    <?php endif; ?>
                </div>
                <div class="text-end mt-2">
                    <small class="text-muted fw-bold" style="font-size: 0.7rem;">RENOVA EM 01/<?php echo date('m', strtotime('+1 month')); ?></small>
                </div>
            </div>

            <!-- CTA -->
            <div>
                 <?php if ($planStatus['plan'] === 'free'): ?>
                    <button class="btn btn-primary rounded-pill d-flex align-items-center gap-2 shadow-sm pulse-btn" onclick="openUpgradeModal()">
                        <i class="fas fa-bolt"></i>
                        <span>Fazer Upgrade</span>
                    </button>
                <?php else: ?>
                    <button class="btn btn-light rounded-pill d-flex align-items-center gap-2 fw-bold" onclick="openUpgradeModal()">
                        <i class="fas fa-cog"></i>
                        <span>Gerenciar Plano</span>
                    </button>
                <?php endif; ?>
            </div>

        </div>
    </div>

<style>
.pulse-btn {
    animation: pulse-primary 2s infinite;
}
@keyframes pulse-primary {
    0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
    100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
}
</style>

<!-- Stats Overview -->
<div class="row g-4 mb-5">

    <?php
    $total_agents = count($agents);
    $active_agents = count(array_filter($agents, fn($a) => $a['status'] === 'active'));
    $total_uses = array_sum(array_column($stats, 'total_uses'));
    $total_tokens = array_sum(array_column($stats, 'total_input')) + array_sum(array_column($stats, 'total_output'));
    ?>
    <div class="col-md-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary-subtle p-3 text-primary">
                <i class="fas fa-robot fa-lg"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo $total_agents; ?></div>
                <div class="stat-label">Agentes Criados</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success-subtle p-3 text-success">
                <i class="fas fa-check-circle fa-lg"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo $active_agents; ?></div>
                <div class="stat-label">Agentes Ativos</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="rounded-circle bg-info-subtle p-3 text-info">
                <i class="fas fa-comments fa-lg"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo number_format($total_uses); ?></div>
                <div class="stat-label">Interações Totais</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning-subtle p-3 text-warning">
                <i class="fas fa-coins fa-lg"></i>
            </div>
            <div>
                <div class="stat-value"><?php echo number_format($total_tokens); ?></div>
                <div class="stat-label">Tokens Consumidos</div>
            </div>
        </div>
    </div>
</div>

<!-- Agents Grid -->
<div class="row g-4">
    <?php if (empty($agents)): ?>
        <div class="col-12 text-center py-5">
            <img src="../assets/img/empty_state_robot.svg" alt="Sem agentes" style="max-width: 200px; opacity: 0.5;" onerror="this.src='https://placehold.co/200?text=No+Agents'">
            <h4 class="mt-4 text-muted fw-bold">Nenhum agente criado</h4>
            <p class="text-secondary">Comece criando seu primeiro assistente virtual para ajudar em suas tarefas.</p>
            <a href="agentes_ia_form.php" class="btn btn-dark rounded-pill px-4 mt-2">Criar Primeiro Agente</a>
        </div>
    <?php else: ?>
        <?php foreach ($agents as $agent): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card exec-card border-0 h-100 p-0" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-gradient bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                                    <i class="fas fa-brain"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0 text-navy"><?php echo htmlspecialchars($agent['name']); ?></h5>
                                    <small class="text-muted fw-bold"><?php echo strtoupper(htmlspecialchars($agent['role'])); ?></small>
                                </div>
                            </div>
                            <?php 
                                $statusClass = $agent['status'] === 'active' ? 'badge-soft-success' : 'badge-soft-dark';
                                $statusLabel = $agent['status'] === 'active' ? 'Ativo' : 'Inativo';
                            ?>
                            <span class="badge badge-soft <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                        </div>
                        
                        <p class="text-secondary small mb-4 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6;">
                            <?php echo htmlspecialchars($agent['system_instruction']); ?>
                        </p>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top border-light">
                            <div class="d-flex flex-column">
                                <span class="metric-label mb-1">Modelo LLM</span>
                                <span class="small badge bg-light text-dark border-0 fw-bold px-2 py-1"><?php echo htmlspecialchars($agent['model']); ?></span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="agentes_ia_form.php?id=<?php echo $agent['id']; ?>" class="btn btn-icon-action" title="Editar"><i class="fas fa-edit"></i></a>
                                <button onclick="window.openAgentChat('<?php echo $agent['id']; ?>')" class="btn btn-icon-action text-primary bg-primary bg-opacity-10" title="Iniciar Conversa"><i class="fas fa-play"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Usage Charts Section -->
<div class="row mt-5">
    <div class="col-12">
        <h5 class="fw-bold text-navy mb-4"><i class="fas fa-chart-bar me-2"></i>Monitoramento de Consumo</h5>
        <div class="apple-table-container">
            <div class="table-responsive">
                <table class="table apple-table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Agente</th>
                            <th class="text-center">Uso Total</th>
                            <th class="text-center">Tokens (In)</th>
                            <th class="text-center">Tokens (Out)</th>
                            <th class="text-end pe-4">Último Uso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-5">Sem dados de uso processados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($stats as $stat): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark" data-label="Agente">
                                    <i class="fas fa-robot me-2 text-muted opacity-50"></i><?php echo htmlspecialchars($stat['agent_name']); ?>
                                </td>
                                <td class="text-md-center fw-bold" data-label="Uso Total"><?php echo $stat['total_uses']; ?></td>
                                <td class="text-md-center text-secondary" data-label="Tokens (In)"><?php echo number_format($stat['total_input']); ?></td>
                                <td class="text-md-center text-secondary" data-label="Tokens (Out)"><?php echo number_format($stat['total_output']); ?></td>
                                <td class="text-end pe-4 text-muted small" data-label="Último Uso"><?php echo $stat['last_used'] ? date('d/m/Y H:i', strtotime($stat['last_used'])) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'modal_planos.php'; ?>
<?php include_once '../includes/rodape.php'; ?>
