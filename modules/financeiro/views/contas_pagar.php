<?php
// modules/financeiro/views/contas_pagar.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('financeiro', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$params = check_permission('financeiro', 'escrita'); 
$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $params) {
    if (isset($_POST['action']) && $_POST['action'] === 'new') {
        $descricao = trim($_POST['descricao']);
        $valor = (float)str_replace(['R$', '.', ','], ['', '', '.'], $_POST['valor']);
        $vencimento = $_POST['data_vencimento'];
        $status = $_POST['status'] ?? 'pendente';

        try {
            $stmt = $conn->prepare("INSERT INTO contas_pagar (empresa_id, descricao, valor, data_vencimento, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$empresa_id, $descricao, $valor, $vencimento, $status]);
            header('Location: contas_pagar.php?msg=success');
            exit;
        } catch (Exception $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'pay') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $conn->prepare("UPDATE contas_pagar SET status = 'pago', data_pagamento = CURDATE() WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$id, $empresa_id]);
            header('Location: contas_pagar.php?msg=success_pay');
            exit;
        } catch (Exception $e) {}
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $conn->prepare("DELETE FROM contas_pagar WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$id, $empresa_id]);
            header('Location: contas_pagar.php?msg=deleted');
            exit;
        } catch (Exception $e) {}
    }
}

// Fetch Records
try {
    $stmt = $conn->prepare("SELECT * FROM contas_pagar WHERE empresa_id = ? ORDER BY data_vencimento ASC");
    $stmt->execute([$empresa_id]);
    $contas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $contas = [];
}

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-file-invoice-dollar me-2" style="color: var(--bs-danger);"></i>Contas a Pagar</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Financeiro</a></li>
                    <li class="breadcrumb-item active">Contas a Pagar</li>
                </ol>
            </nav>
        </div>
        <div class="w-100 w-md-auto text-md-end">
            <?php if ($params): ?>
            <button class="btn btn-dark shadow-sm fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newContaModal">
                <i class="fas fa-plus me-2"></i>Nova Despesa
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Messages -->
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'success'): ?>
            <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>Despesa registrada com sucesso!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif ($_GET['msg'] === 'success_pay'): ?>
            <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>Despesa marcada como paga!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-secondary border-0 shadow-sm alert-dismissible fade show"><i class="fas fa-trash me-2"></i>Despesa excluída com sucesso!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($error)): ?>
         <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Table Card -->
    <div class="apple-table-container">
        <div class="table-responsive">
            <table class="table apple-table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Descrição</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contas)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhuma despesa registrada.</td></tr>
                    <?php else: ?>
                        <?php foreach($contas as $c): 
                            $status_class = '';
                            $status_text = '';
                            if ($c['status'] == 'pago') { $status_class = 'bg-success'; $status_text = 'Pago'; }
                            elseif ($c['status'] == 'atrasado' || (strtotime($c['data_vencimento']) < time() && $c['status'] == 'pendente')) { $status_class = 'bg-danger'; $status_text = 'Atrasado'; }
                            else { $status_class = 'bg-warning text-dark'; $status_text = 'Pendente'; }
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark" data-label="Descrição"><?= htmlspecialchars($c['descricao']) ?></td>
                            <td data-label="Vencimento"><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
                            <td class="fw-bold text-danger" data-label="Valor">R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                            <td class="text-md-center" data-label="Status">
                                <span class="badge rounded-pill <?= $status_class ?> px-3 py-2"><?= $status_text ?></span>
                            </td>
                            <td class="text-end pe-4" data-label="Ações">
                                <div class="d-flex justify-content-end gap-2">
                                    <?php if($params): ?>
                                        <?php if($c['status'] != 'pago'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="pay">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-icon-action text-success" title="Marcar como Pago"><i class="fas fa-check"></i></button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Deseja realmente excluir esta despesa?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-icon-action text-danger" title="Excluir"><i class="fas fa-trash"></i></button>
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
</style>

<!-- Modal Nova Despesa -->
<div class="modal fade" id="newContaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form method="POST">
          <input type="hidden" name="action" value="new">
          <div class="modal-header bg-light border-0">
            <h5 class="modal-title fw-bold text-navy"><i class="fas fa-file-invoice-dollar me-2 text-danger"></i>Nova Despesa</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Descrição</label>
                  <input type="text" class="form-control" name="descricao" required placeholder="Ex: Aluguel, Luz, Internet">
              </div>
              <div class="row mb-3">
                  <div class="col-6">
                      <label class="form-label text-muted small fw-bold">Valor (R$)</label>
                      <input type="number" step="0.01" class="form-control" name="valor" required placeholder="0.00">
                  </div>
                  <div class="col-6">
                      <label class="form-label text-muted small fw-bold">Vencimento</label>
                      <input type="date" class="form-control" name="data_vencimento" required>
                  </div>
              </div>
              <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Status Inicial</label>
                  <select class="form-select" name="status">
                      <option value="pendente">Pendente</option>
                      <option value="pago">Já Pago</option>
                  </select>
              </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-trust-primary rounded-pill">Salvar Despesa</button>
          </div>
      </form>
    </div>
  </div>
</div>

<style>
    .text-navy { color: #0A2647; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
