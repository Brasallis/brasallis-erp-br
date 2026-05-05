<?php
// admin/notificacoes.php - CENTRO DE NOTIFICAÇÕES COMPLETO
require_once __DIR__ . '/../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['empresa_id'])) {
    header("Location: ../login.php");
    exit();
}

$empresa_id = $_SESSION['empresa_id'];
$conn = resolve('db');

// Buscar todas as notificações
$stmt = $conn->prepare("SELECT * FROM notificacoes WHERE empresa_id = ? ORDER BY created_at DESC");
$stmt->execute([$empresa_id]);
$notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once __DIR__ . '/../includes/cabecalho.php';
?>

<div class="page-header mb-5 d-flex justify-content-between align-items-end">
    <div>
        <h2 class="page-title">Centro de Mensagens</h2>
        <p class="page-subtitle">Gerencie todos os alertas e comunicados do sistema</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm" onclick="fullMarkAllRead()">
            <i class="fas fa-check-double me-2"></i> Marcar todas como lidas
        </button>
        <button class="btn btn-outline-danger rounded-pill px-4 shadow-sm" onclick="fullDeleteAll()">
            <i class="fas fa-trash-alt me-2"></i> Limpar Histórico
        </button>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="google-card p-0 overflow-hidden shadow-sm border-0">
            <?php if (empty($notificacoes)): ?>
                <div class="p-5 text-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-muted mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-bell-slash fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Tudo limpo por aqui!</h5>
                    <p class="text-muted">Você não possui notificações no momento.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 border-0 py-3" style="width: 60px;">Tipo</th>
                                <th class="border-0 py-3">Mensagem</th>
                                <th class="border-0 py-3">Data/Hora</th>
                                <th class="border-0 py-3">Status</th>
                                <th class="pe-4 border-0 py-3 text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notificacoes as $n): 
                                $iconClass = match($n['type']) {
                                    'low_stock' => 'orange',
                                    'nearing_expiration' => 'danger',
                                    default => 'primary'
                                };
                                $icon = match($n['type']) {
                                    'low_stock' => 'fa-boxes-stacked',
                                    'nearing_expiration' => 'fa-hourglass-half',
                                    default => 'fa-info-circle'
                                };
                            ?>
                            <tr class="<?= $n['is_read'] ? 'opacity-75' : 'fw-bold' ?>" id="row-<?= $n['id'] ?>">
                                <td class="ps-4">
                                    <div class="notif-icon-circle bg-<?= $iconClass ?>-subtle text-<?= $iconClass ?>">
                                        <i class="fas <?= $icon ?>"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-dark"><?= $n['message'] ?></div>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if($n['is_read']): ?>
                                        <span class="badge bg-light text-muted rounded-pill px-3 border">Lida</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3">Não lida</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group">
                                        <?php if(!$n['is_read']): ?>
                                            <button class="btn btn-sm btn-light rounded-pill me-2" onclick="fullMarkRead(<?= $n['id'] ?>)" title="Marcar como lida">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-light text-danger rounded-pill" onclick="fullDelete(<?= $n['id'] ?>)" title="Excluir">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.notif-icon-circle {
    width: 40px; height: 40px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
}
.bg-orange-subtle { background-color: #fff7ed; }
.text-orange { color: #f97316; }
.bg-danger-subtle { background-color: #fef2f2; }
.bg-primary-subtle { background-color: #eff6ff; }
</style>

<script>
async function fullMarkRead(id) {
    const formData = new FormData();
    formData.append('id', id);
    const response = await fetch('<?= $base_url ?>api/mark_notification_read.php', { method: 'POST', body: formData });
    const data = await response.json();
    if (data.success) location.reload();
}

async function fullMarkAllRead() {
    const formData = new FormData();
    formData.append('all', 'true');
    const response = await fetch('<?= $base_url ?>api/mark_notification_read.php', { method: 'POST', body: formData });
    const data = await response.json();
    if (data.success) location.reload();
}

async function fullDelete(id) {
    if (!confirm('Excluir esta notificação permanentemente?')) return;
    const formData = new FormData();
    formData.append('id', id);
    const response = await fetch('<?= $base_url ?>api/delete_notification.php', { method: 'POST', body: formData });
    const data = await response.json();
    if (data.success) {
        document.getElementById('row-' + id).remove();
        loadNotifications(); // Atualizar o sino
    }
}

async function fullDeleteAll() {
    if (!confirm('Deseja realmente apagar TODO o histórico de notificações? Esta ação não pode ser desfeita.')) return;
    const formData = new FormData();
    formData.append('all', 'true');
    const response = await fetch('<?= $base_url ?>api/delete_notification.php', { method: 'POST', body: formData });
    const data = await response.json();
    if (data.success) location.reload();
}
</script>

<?php include_once __DIR__ . '/../includes/rodape.php'; ?>
