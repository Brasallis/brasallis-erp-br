<?php
// superadmin/suporte.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/funcoes.php';
checkSuperAdmin();

$conn = connect_db();
$message = '';

// Responder Chamado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    $id = $_POST['ticket_id'];
    $resposta = $_POST['resposta'];
    
    $stmt = $conn->prepare("UPDATE chamados_suporte SET resposta = ?, status = 'respondido', updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$resposta, $id])) {
        $message = "Resposta enviada com sucesso!";
    }
}

// Fechar Chamado
if (isset($_GET['close'])) {
    $id = $_GET['close'];
    $conn->query("UPDATE chamados_suporte SET status = 'fechado' WHERE id = $id");
    header("Location: suporte.php");
    exit;
}

// Listar Chamados (Prioridade: Abertos primeiro)
$sql = "SELECT c.*, e.name as empresa_name, e.email as empresa_email, e.ai_plan 
        FROM chamados_suporte c 
        JOIN empresas e ON c.empresa_id = e.id 
        ORDER BY FIELD(c.status, 'aberto', 'respondido', 'fechado'), c.created_at DESC";
$chamados = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

    <div class="page-header">
        <div>
            <h2 class="page-title">Helpdesk Support</h2>
            <p class="page-subtitle">Central de atendimento aos clientes</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-white text-dark shadow-sm px-3 py-2 rounded-pill border"><i class="fas fa-inbox me-2 text-primary"></i> <?php echo count($chamados); ?> Total</span>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
             <i class="fas fa-check-circle fs-4"></i>
             <div><?php echo $message; ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php foreach($chamados as $ticket): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fs-5" style="width: 50px; height: 50px;">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <?php 
                                    $statusClass = match($ticket['status']) {
                                        'aberto' => 'warning',
                                        'respondido' => 'success',
                                        'fechado' => 'secondary',
                                    };
                                    $statusLabel = match($ticket['status']) {
                                        'aberto' => 'Aberto',
                                        'respondido' => 'Respondido',
                                        'fechado' => 'Fechado',
                                    };
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?> rounded-pill px-3">
                                    <?php echo $statusLabel; ?>
                                </span>
                                <?php 
                                    $prioLabel = match($ticket['ai_plan']) {
                                        'enterprise_elite' => '🔥 Prioridade Crítica',
                                        'vision' => '⚡ Prioridade Alta',
                                        default => '📅 Normal'
                                    };
                                ?>
                                <span class="badge bg-light text-dark border rounded-pill px-3"><?php echo $prioLabel; ?></span>
                                <small class="text-muted">#<?php echo $ticket['id']; ?></small>
                            </div>
                            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($ticket['assunto']); ?></h5>
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i> <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?> 
                                &bull; 
                                <span class="text-primary fw-bold"><?php echo htmlspecialchars($ticket['empresa_name']); ?></span>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <div class="bg-light bg-opacity-50 p-4 rounded-4 mb-4 border border-light">
                        <p class="mb-0 text-secondary" style="font-size: 0.95rem; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($ticket['mensagem'])); ?></p>
                    </div>

                    <?php if($ticket['status'] !== 'fechado'): ?>
                        <div class="ps-md-5 ms-md-4 border-start border-3 border-light ps-3">
                            <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-reply me-2"></i>Sua Resposta</h6>
                            <form method="POST">
                                <input type="hidden" name="reply_ticket" value="1">
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                <div class="mb-3">
                                    <textarea name="resposta" class="form-control border-0 bg-white shadow-sm rounded-4 p-3" rows="3" placeholder="Escreva uma resposta profissional..." style="resize: none;" required><?php echo htmlspecialchars($ticket['resposta'] ?? ''); ?></textarea>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="?close=<?php echo $ticket['id']; ?>" class="btn btn-outline-secondary rounded-pill px-4" onclick="return confirm('Tem certeza que deseja fechar este chamado?')">Fechar Ticket</a>
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">Enviar Resposta</button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <?php if(!empty($ticket['resposta'])): ?>
                            <div class="ps-md-5 ms-md-4 border-start border-3 border-success ps-3">
                                <div class="bg-success-subtle p-4 rounded-4">
                                    <h6 class="fw-bold text-success mb-2"><i class="fas fa-check-circle me-2"></i>Resolvido</h6>
                                    <p class="mb-0 text-dark opacity-75"><?php echo nl2br(htmlspecialchars($ticket['resposta'])); ?></p>
                                </div>
                                <div class="mt-2 text-end">
                                    <small class="text-muted">Finalizado em <?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php require_once 'includes/footer.php'; ?>
