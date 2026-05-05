<?php
// modules/crm/views/kanban.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('crm', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$params = check_permission('crm', 'escrita'); 
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Handle Form Submission (New Opportunity & Update Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $params) {
    if (isset($_POST['action']) && $_POST['action'] === 'new') {
        $cliente_id = (int)$_POST['cliente_id'];
        $titulo = trim($_POST['titulo']);
        $valor = (float)str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor']);
        $status = $_POST['status'] ?? 'lead';

        try {
            $stmt = $conn->prepare("INSERT INTO crm_oportunidades (empresa_id, cliente_id, titulo, valor_estimado, status, responsavel_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$empresa_id, $cliente_id, $titulo, $valor, $status, $_SESSION['user_id']]);
            header('Location: kanban.php?msg=success');
            exit;
        } catch (Exception $e) { $error = "Erro: " . $e->getMessage(); }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'faturar_oportunidade') {
        $id = (int)$_POST['oportunidade_id'];
        try {
            $stmt = $conn->prepare("SELECT * FROM crm_oportunidades WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$id, $empresa_id]);
            $op = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($op) {
                // Inserir no financeiro
                $stmtFin = $conn->prepare("INSERT INTO contas_receber (empresa_id, descricao, valor, data_vencimento, status, cliente_id) VALUES (?, ?, ?, CURDATE(), 'pendente', ?)");
                $stmtFin->execute([$empresa_id, "Faturamento Oportunidade: " . $op['titulo'], $op['valor_estimado'], $op['cliente_id']]);
                
                // Marcar como Ganho se já não estiver
                $stmtUp = $conn->prepare("UPDATE crm_oportunidades SET status = 'ganho' WHERE id = ?");
                $stmtUp->execute([$id]);
            }
            header('Location: kanban.php?msg=faturado');
            exit;
        } catch (Exception $e) { $error = "Erro ao faturar: " . $e->getMessage(); }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        // Handle drag and drop update
        $id = (int)$_POST['oportunidade_id'];
        $novo_status = $_POST['novo_status'];
        try {
            $stmt = $conn->prepare("UPDATE crm_oportunidades SET status = ? WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$novo_status, $id, $empresa_id]);
            exit; // Ajax call, no redirect needed
        } catch (Exception $e) { exit; }
    }
}

// Fetch Opportunities and group by status
try {
    $stmt = $conn->prepare("
        SELECT o.*, c.nome as cliente_nome, u.username as resp_nome 
        FROM crm_oportunidades o 
        JOIN clientes c ON o.cliente_id = c.id 
        LEFT JOIN usuarios u ON o.responsavel_id = u.id 
        WHERE o.empresa_id = ? 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$empresa_id]);
    $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $todas = []; }

$board = [
    'lead' => [],
    'negociacao' => [],
    'ganho' => [],
    'perdido' => []
];
$totals = ['lead' => 0, 'negociacao' => 0, 'ganho' => 0, 'perdido' => 0];

foreach ($todas as $op) {
    $status = $op['status'];
    // Fallback para status legados ou inesperados
    if (!isset($board[$status])) {
        $status = 'lead'; 
    }
    $board[$status][] = $op;
    $totals[$status] += (float)$op['valor_estimado'];
}

// Fetch Clients for the new opportunity modal
$clientesList = [];
if ($params) {
    try {
        $stmtC = $conn->prepare("SELECT id, nome FROM clientes WHERE empresa_id = ? AND status = 'ativo' ORDER BY nome ASC");
        $stmtC->execute([$empresa_id]);
        $clientesList = $stmtC->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="page-container">
    <div class="page-header">
        <div class="page-title-group">
            <h1>Pipeline de Vendas (CRM)</h1>
            <p>Gerencie suas oportunidades e funil de vendas em tempo real</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($params): ?>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newOppModal">
                <i class="fas fa-plus me-2"></i>Nova Oportunidade
            </button>
            <?php endif; ?>
        </div>
    </div>
...
    <!-- Kanban Board -->
    <div class="kanban-wrapper flex-grow-1 overflow-auto pb-3">
        <div class="kanban-board-container d-flex h-100 gap-4">
...
<style>
    .kanban-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .kanban-board-container { min-width: 1200px; padding-bottom: 2rem; }
    
    @media (max-width: 991px) {
        .kanban-board-container { 
            min-width: 100%; 
            flex-direction: column; 
            height: auto !important; 
        }
        .kanban-col { 
            min-width: 100% !important; 
            max-height: none !important; 
            margin-bottom: 1.5rem;
        }
        .kanban-list { min-height: 100px; }
    }
</style>
            
            <!-- COLS GENERATOR -->
            <?php 
                $columns = [
                    'lead' => ['title' => 'Leads / Contatos', 'color' => 'primary', 'icon' => 'fa-inbox'],
                    'negociacao' => ['title' => 'Em Negociação', 'color' => 'warning', 'icon' => 'fa-comments'],
                    'ganho' => ['title' => 'Propostas Ganhas', 'color' => 'success', 'icon' => 'fa-trophy'],
                    'perdido' => ['title' => 'Negócios Perdidos', 'color' => 'danger', 'icon' => 'fa-times-circle']
                ];
                foreach ($columns as $status => $col): 
            ?>
            <div class="kanban-col d-flex flex-column bg-light rounded-3 p-3 shadow-sm" style="flex: 1; min-width: 280px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-navy mb-0">
                        <i class="fas <?= $col['icon'] ?> text-<?= $col['color'] ?> me-2"></i><?= $col['title'] ?>
                        <span class="badge bg-secondary ms-2 rounded-pill"><?= count($board[$status]) ?></span>
                    </h6>
                    <small class="text-<?= $col['color'] ?> fw-bold">R$ <?= number_format($totals[$status], 2, ',', '.') ?></small>
                </div>

                <div class="kanban-cards-container flex-grow-1" ondrop="drop(event, '<?= $status ?>')" ondragover="allowDrop(event)">
                    <?php foreach($board[$status] as $item): ?>
                    <div class="card border-0 shadow-sm mb-3 kanban-card cursor-pointer" draggable="<?= $params ? 'true' : 'false' ?>" ondragstart="drag(event, <?= $item['id'] ?>)" id="card_<?= $item['id'] ?>">
                        <div class="card-body p-3">
                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($item['titulo']) ?></h6>
                            <small class="text-muted d-block mb-2"><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($item['cliente_nome']) ?></small>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <span class="badge bg-<?= $col['color'] ?>-light text-<?= $col['color'] ?>">R$ <?= number_format($item['valor_estimado'], 2, ',', '.') ?></span>
                                <small class="text-muted" title="Responsável"><?= strtoupper(substr($item['resp_nome'] ?? 'R', 0, 1)) ?></small>
                            </div>
                            <?php if ($status === 'ganho'): ?>
                            <div class="mt-2 text-center">
                                <form method="POST" onsubmit="return confirm('Deseja gerar um registro no financeiro para este negócio?')">
                                    <input type="hidden" name="action" value="faturar_oportunidade">
                                    <input type="hidden" name="oportunidade_id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success w-100 rounded-pill py-0 small" style="font-size: 0.7rem;">
                                        <i class="fas fa-file-invoice-dollar me-1"></i>Faturar Agora
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<!-- Modal Nova Oportunidade -->
<div class="modal fade" id="newOppModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form method="POST">
          <input type="hidden" name="action" value="new">
          <!-- Prefill client ID if action=new&cliente_id was passed via GET -->
          <?php $prefill_client = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0; ?>
          
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold text-navy"><i class="fas fa-bullhorn me-2 text-warning"></i>Nova Oportunidade</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Cliente</label>
                  <select class="form-select" name="cliente_id" required>
                      <option value="">Selecione o Cliente...</option>
                      <?php foreach($clientesList as $c): ?>
                      <option value="<?= $c['id'] ?>" <?= $prefill_client == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                      <?php endforeach; ?>
                  </select>
                  <?php if(empty($clientesList)): ?>
                      <small class="text-danger">Você precisa cadastrar clientes primeiro.</small>
                  <?php endif; ?>
              </div>
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">O que estamos negociando?</label>
                  <input type="text" class="form-control" name="titulo" required placeholder="Ex: Contrato Anual Serviços">
              </div>
              <div class="row mb-3">
                  <div class="col-6">
                      <label class="form-label text-muted small fw-bold">Valor Estimado (R$)</label>
                      <input type="number" step="0.01" class="form-control" name="valor" required placeholder="0.00">
                  </div>
                  <div class="col-6">
                      <label class="form-label text-muted small fw-bold">Fase Inicial</label>
                      <select class="form-select" name="status">
                          <option value="lead">Lead / Contato</option>
                          <option value="negociacao">Em Negociação</option>
                          <option value="ganho">Negócio Fechado</option>
                      </select>
                  </div>
              </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning fw-bold text-dark rounded-pill" <?= empty($clientesList) ? 'disabled' : '' ?>>Criar Oportunidade</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
// Kanban Drag and Drop Logic
function allowDrop(ev) {
    ev.preventDefault();
}

function drag(ev, id) {
    ev.dataTransfer.setData("id", id);
    ev.dataTransfer.setData("card_id", ev.target.id);
}

function drop(ev, status) {
    ev.preventDefault();
    var id = ev.dataTransfer.getData("id");
    var card_id = ev.dataTransfer.getData("card_id");
    
    // Find closest container
    var targetContainer = ev.target;
    if(!targetContainer.classList.contains('kanban-cards-container')) {
        targetContainer = targetContainer.closest('.kanban-cards-container');
    }
    
    if (targetContainer) {
        targetContainer.appendChild(document.getElementById(card_id));
        updateStatusOnServer(id, status);
    }
}

function updateStatusOnServer(oportunidade_id, novo_status) {
    var form = new FormData();
    form.append('action', 'update_status');
    form.append('oportunidade_id', oportunidade_id);
    form.append('novo_status', novo_status);

    fetch('kanban.php', {
        method: 'POST',
        body: form
    }).then(() => {
        // Optional: reload to refresh value totals
        setTimeout(() => window.location.reload(), 300);
    });
}
</script>

<style>
    .text-navy { color: #0A2647; }
    .bg-primary-light { background-color: rgba(13,110,253,0.08); }
    .bg-success-light { background-color: rgba(25,135,84,0.08); }
    .bg-warning-light { background-color: rgba(255,193,7,0.08); }
    .bg-danger-light { background-color: rgba(220,53,69,0.08); }
    
    .kanban-col { 
        border: 1px solid #eef2f7; 
        min-height: 70vh;
        transition: all 0.3s ease;
    }
    
    .kanban-cards-container { 
        min-height: 200px; 
    }
    
    .kanban-card { 
        border-radius: 12px !important;
        border: 1px solid rgba(0,0,0,0.05) !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        user-select: none;
    }
    
    .kanban-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        border-color: #0A2647 !important;
    }
    
    .cursor-pointer { cursor: grab; }
    .cursor-pointer:active { cursor: grabbing; }

    /* Custom scrollbar for Kanban */
    .kanban-board::-webkit-scrollbar { height: 8px; }
    .kanban-board::-webkit-scrollbar-track { background: #f1f1f1; }
    .kanban-board::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
    .kanban-board::-webkit-scrollbar-thumb:hover { background: #bbb; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
