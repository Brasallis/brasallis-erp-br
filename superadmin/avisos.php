<?php
// superadmin/avisos.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';

// Proteção
checkSuperAdmin();

$conn = connect_db();
$message = '';

// Criar Novo Aviso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_aviso'])) {
    $titulo = $_POST['titulo'];
    $mensagem = $_POST['mensagem'];
    $tipo = $_POST['tipo'];

    $stmt = $conn->prepare("INSERT INTO avisos_globais (titulo, mensagem, tipo) VALUES (?, ?, ?)");
    if ($stmt->execute([$titulo, $mensagem, $tipo])) {
        $message = "Aviso publicado com sucesso!";
    }
}

// Alternar Status (Ativo/Inativo)
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $conn->query("UPDATE avisos_globais SET active = NOT active WHERE id = $id");
    header("Location: avisos.php");
    exit;
}

// Excluir
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM avisos_globais WHERE id = $id");
    header("Location: avisos.php");
    exit;
}

// Listar Avisos
$avisos = $conn->query("SELECT * FROM avisos_globais ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

    <div class="page-header">
        <div>
            <h2 class="page-title">Broadcast Center</h2>
            <p class="page-subtitle">Comunicação direta com todos os usuários</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="collapse" data-bs-target="#newNoticeForm">
            <i class="fas fa-plus me-2"></i> Novo Aviso
        </button>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
             <i class="fas fa-check-circle fs-4"></i>
             <div><?php echo $message; ?></div>
        </div>
    <?php endif; ?>

    <!-- Form Creation (Collapsible) -->
    <div class="collapse show" id="newNoticeForm">
        <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden">
            <div class="card-header bg-white p-4 border-bottom border-light">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 40px; height: 40px;">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h5 class="fw-bold m-0 text-dark">Novo Comunicado Global</h5>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="create_aviso" value="1">
                    <div class="row g-4">
                        <div class="col-md-9">
                            <label class="form-label small text-uppercase fw-bold text-muted">Título do Aviso</label>
                            <input type="text" name="titulo" class="form-control form-control-lg border-0 bg-light rounded-3" required placeholder="Ex: Manutenção Programada no Sistema">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-uppercase fw-bold text-muted">Grau de Urgência</label>
                            <select name="tipo" class="form-select form-select-lg border-0 bg-light rounded-3">
                                <option value="info">🔵 Informativo (Azul)</option>
                                <option value="warning">🟡 Alerta (Amarelo)</option>
                                <option value="danger">🔴 Urgente (Vermelho)</option>
                                <option value="success">🟢 Novidade (Verde)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-uppercase fw-bold text-muted">Mensagem Detalhada</label>
                            <textarea name="mensagem" class="form-control border-0 bg-light rounded-4 p-3" rows="3" required placeholder="Escreva a mensagem que aparecerá para todos os usuários..." style="resize: none;"></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i>Publicar Aviso
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- List -->
    <h5 class="fw-bold mb-4 text-dark ps-2 border-start border-4 border-primary">&nbsp;Histórico de Transmissões</h5>
    
    <div class="premium-table border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Data Publicação</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Controles</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($avisos as $aviso): ?>
                    <tr>
                        <td class="ps-4 text-muted fw-bold" style="font-size: 0.85rem;">
                            <i class="far fa-calendar-alt me-2"></i><?php echo date('d/m/Y H:i', strtotime($aviso['created_at'])); ?>
                        </td>
                        <td>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($aviso['titulo']); ?></span>
                        </td>
                        <td>
                            <?php
                                $badgeClass = match($aviso['tipo']) {
                                    'info' => 'primary',
                                    'warning' => 'warning',
                                    'danger' => 'danger',
                                    'success' => 'success',
                                    default => 'secondary'
                                };
                            ?>
                            <span class="badge bg-<?php echo $badgeClass; ?>-subtle text-<?php echo $badgeClass; ?> rounded-pill px-3">
                                <?php echo ucfirst($aviso['tipo']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if($aviso['active']): ?>
                                <span class="badge bg-success text-white rounded-pill px-3 shadow-sm">Publicado</span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-25 text-secondary rounded-pill px-3">Arquivado</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="?toggle=<?php echo $aviso['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-circle shadow-sm" title="Alternar Status" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-power-off"></i>
                                </a>
                                <a href="?delete=<?php echo $aviso['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle shadow-sm ms-1" onclick="return confirm('Tem certeza que deseja apagar este aviso permanentemente?')" title="Excluir" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
