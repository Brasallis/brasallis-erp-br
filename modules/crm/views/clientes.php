<?php
// modules/crm/views/clientes.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('crm', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$params = check_permission('crm', 'escrita'); 
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Handle Actions (e.g., Disable/Enable Client)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $params) {
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $id = (int)$_POST['id'];
        $novo_status = $_POST['status'] === 'ativo' ? 'inativo' : 'ativo';
        try {
            $stmt = $conn->prepare("UPDATE clientes SET status = ? WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$novo_status, $id, $empresa_id]);
            header("Location: clientes.php?msg=status_updated");
            exit;
        } catch (Exception $e) {}
    }
}

// Fetch Clientes
try {
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE empresa_id = ? ORDER BY nome ASC");
    $stmt->execute([$empresa_id]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clientes = [];
}

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-users me-2 text-primary"></i>Base de Clientes</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">CRM & Vendas</a></li>
                    <li class="breadcrumb-item active">Clientes</li>
                </ol>
            </nav>
        </div>
        <div class="w-100 w-md-auto text-md-end">
            <?php if ($params): ?>
            <a href="cliente_form.php" class="btn btn-dark shadow-sm fw-bold rounded-pill px-4">
                <i class="fas fa-user-plus me-2"></i>Novo Cliente
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Messages -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'status_updated'): ?>
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-4"><i class="fas fa-check-circle me-2"></i>Status do cliente atualizado com sucesso!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="apple-table-container">
        <div class="table-responsive">
            <table class="table apple-table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Cliente</th>
                        <th>Contato</th>
                        <th>Documento</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhum cliente cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach($clientes as $c): ?>
                        <tr>
                            <td class="ps-4" data-label="Cliente">
                                <div class="d-flex align-items-center">
                                    <div class="icon-shape bg-primary text-white rounded-circle me-3 d-none d-md-flex" style="width: 40px; height: 40px; font-size: 1.2rem; align-items: center; justify-content: center;">
                                        <?= strtoupper(substr($c['nome'] ?? 'C', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($c['nome']) ?></div>
                                        <small class="text-muted">ID: #<?= $c['id'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Contato">
                                <div class="text-dark small"><i class="fas fa-envelope me-2 text-muted opacity-50"></i><?= htmlspecialchars($c['email'] ?: '-') ?></div>
                                <div class="text-muted small mt-1"><i class="fas fa-phone me-2 text-muted opacity-50"></i><?= htmlspecialchars($c['telefone'] ?: '-') ?></div>
                            </td>
                            <td data-label="Documento" class="font-monospace small"><?= htmlspecialchars($c['cpf_cnpj'] ?: '-') ?></td>
                            <td class="text-md-center" data-label="Status">
                                <?php if($c['status'] == 'ativo'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill">ATIVO</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 rounded-pill">INATIVO</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4" data-label="Ações">
                                <div class="d-flex justify-content-end gap-2">
                                    <?php if($params): ?>
                                        <a href="kanban.php?action=new&cliente_id=<?= $c['id'] ?>" class="btn btn-icon-action text-primary" title="Nova Oportunidade">
                                            <i class="fas fa-bullhorn"></i>
                                        </a>
                                        <a href="cliente_form.php?id=<?= $c['id'] ?>" class="btn btn-icon-action text-navy" title="Editar">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $c['status'] ?>">
                                            <button type="submit" class="btn btn-icon-action <?= $c['status'] == 'ativo' ? 'text-danger' : 'text-success' ?>" title="<?= $c['status'] == 'ativo' ? 'Inativar' : 'Ativar' ?>">
                                                <i class="fas <?= $c['status'] == 'ativo' ? 'fa-ban' : 'fa-check' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .btn-icon-action {
        width: 32px; height: 32px; border-radius: 10px; border: none; background: rgba(0,0,0,0.03);
        display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;
    }
    .btn-icon-action:hover { background: rgba(0,0,0,0.06); transform: translateY(-2px); }
    .font-monospace { font-family: 'Courier New', Courier, monospace; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
