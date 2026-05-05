<?php
// admin/suporte.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';
checkAuth();

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$message = '';

// Criar Novo Chamado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $assunto = $_POST['assunto'];
    $mensagem = $_POST['mensagem'];

    $stmt = $conn->prepare("INSERT INTO chamados_suporte (empresa_id, assunto, mensagem) VALUES (?, ?, ?)");
    if ($stmt->execute([$empresa_id, $assunto, $mensagem])) {
        $message = "Chamado enviado! Nossa equipe responderá em breve.";
    }
}

// Listar Chamados
$stmt = $conn->prepare("SELECT * FROM chamados_suporte WHERE empresa_id = ? ORDER BY created_at DESC");
$stmt->execute([$empresa_id]);
$chamados = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/cabecalho.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold m-0 text-dark">Central de Suporte</h2>
    <span class="badge bg-primary rounded-pill px-3 py-2">Tempo médio de resposta: 2h</span>
</div>

<?php if($message): ?>
    <div class="alert alert-success rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i><?php echo $message; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Novo Chamado -->
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h5 class="fw-bold m-0"><i class="fas fa-pen-fancy me-2 text-primary"></i>Novo Chamado</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="create_ticket" value="1">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Assunto</label>
                        <select name="assunto" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option>Dúvida Técnica</option>
                            <option>Problema Financeiro</option>
                            <option>Sugestão de Melhoria</option>
                            <option>Reportar Bug</option>
                            <option>Outro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Mensagem</label>
                        <textarea name="mensagem" class="form-control" rows="5" required placeholder="Descreva seu problema com detalhes..."></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary rounded-pill fw-bold">Enviar Solicitação</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Histórico -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 p-4 pb-0">
                <h5 class="fw-bold m-0"><i class="fas fa-history me-2 text-secondary"></i>Seus Chamados</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if(empty($chamados)): ?>
                        <div class="text-center p-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                            <p>Nenhum chamado aberto.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($chamados as $ticket): ?>
                        <div class="list-group-item p-4 border-light">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($ticket['assunto']); ?></h6>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></small>
                                </div>
                                <?php 
                                    $statusClass = match($ticket['status']) {
                                        'aberto' => 'warning',
                                        'respondido' => 'success',
                                        'fechado' => 'secondary',
                                    };
                                    $statusLabel = ucfirst($ticket['status']);
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?> rounded-pill px-3">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </div>
                            
                            <p class="mb-3 text-secondary small bg-light p-3 rounded-3 fst-italic">"<?php echo nl2br(htmlspecialchars($ticket['mensagem'])); ?>"</p>

                            <?php if($ticket['resposta']): ?>
                                <div class="alert alert-primary border-0 shadow-sm rounded-3 mb-0">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <i class="fas fa-user-shield"></i>
                                        <strong class="small text-uppercase">Resposta do Suporte</strong>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($ticket['resposta'])); ?></p>
                                </div>
                            <?php else: ?>
                                <small class="text-muted fst-italic"><i class="fas fa-clock me-1"></i> Aguardando resposta...</small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/rodape.php'; ?>
