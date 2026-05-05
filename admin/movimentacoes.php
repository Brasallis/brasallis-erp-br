<?php
// admin/movimentacoes.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/cabecalho.php';

// Auth
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if (!check_permission('estoque', 'escrita')) { header('Location: painel_admin.php?error=acesso_negado'); exit; }

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$type = $_GET['type'] ?? 'entrada'; // 'entrada' ou 'saida'

// POST HANDLER FOR MANUAL ADJUSTMENT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $tipo = $_POST['tipo'];
    $qtd = (float)$_POST['quantity'];
    $motivo = $_POST['motivo'];
    $user_id = $_SESSION['user_id'];

    if ($product_id && $qtd > 0) {
        try {
            $conn->beginTransaction();
            
            // Get current stock
            $stmt = $conn->prepare("SELECT quantity FROM produtos WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$product_id, $empresa_id]);
            $current = $stmt->fetchColumn();

            if ($tipo === 'saida' && $current < $qtd) {
                throw new Exception("Estoque insuficiente.");
            }

            // Update Stock
            if ($tipo === 'entrada') {
                $upd = $conn->prepare("UPDATE produtos SET quantity = quantity + ? WHERE id = ?");
            } else {
                $upd = $conn->prepare("UPDATE produtos SET quantity = quantity - ? WHERE id = ?");
            }
            $upd->execute([$qtd, $product_id]);

            // History Log
            $log = $conn->prepare("INSERT INTO historico_estoque (empresa_id, product_id, user_id, action, quantity, details, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $action = ($tipo === 'entrada' ? 'entrada_manual' : 'saida_manual');
            $log->execute([$empresa_id, $product_id, $user_id, $action, $qtd, $motivo]);

            $conn->commit();
            $msg = "Ajuste realizado com sucesso!";
            $msg_type = "success";
        } catch (Exception $e) {
            $conn->rollBack();
            $msg = "Erro: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

// Fetch Log
$stmt = $conn->prepare("
    SELECT h.*, p.name as produto, u.username as usuario 
    FROM historico_estoque h
    JOIN produtos p ON h.product_id = p.id
    LEFT JOIN usuarios u ON h.user_id = u.id
    WHERE h.empresa_id = ? AND (h.action = 'entrada_manual' OR h.action = 'saida_manual')
    ORDER BY h.created_at DESC LIMIT 50
");
$stmt->execute([$empresa_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Products for Select
$prods = $conn->prepare("SELECT id, name, quantity, sku FROM produtos WHERE empresa_id = ? ORDER BY name ASC");
$prods->execute([$empresa_id]);
$products = $prods->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-5 pb-4 border-bottom border-light">
        <div class="col-md-7 col-lg-8">
            <div class="metric-label mb-2"><i class="fas fa-exchange-alt me-1 text-primary"></i> Brasallis Log</div>
            <h1 class="greeting">Ajuste Manual</h1>
            <p class="text-muted mb-0 mt-2" style="font-weight: 500;">Correção rápida de saldo e inventário.</p>
        </div>
    </div>

    <?php if (isset($msg)): ?>
    <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm rounded-4 border-0 mb-4" role="alert">
        <i class="fas fa-info-circle me-2"></i><?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- FORM -->
        <div class="col-lg-4">
            <div class="card exec-card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-navy mb-4"><i class="fas fa-edit me-2"></i>Novo Ajuste</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="metric-label d-block mb-2">Produto</label>
                            <select name="product_id" class="form-select rounded-3 border-light py-2" required>
                                <option value="">Selecione um item...</option>
                                <?php foreach($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (Saldo: <?= $p['quantity'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="metric-label d-block mb-2">Tipo</label>
                                <select name="tipo" class="form-select rounded-3 border-light py-2">
                                    <option value="entrada" <?= $type == 'entrada' ? 'selected' : '' ?>>Entrada (+)</option>
                                    <option value="saida" <?= $type == 'saida' ? 'selected' : '' ?>>Saída (-)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="metric-label d-block mb-2">Quantidade</label>
                                <input type="number" name="quantity" step="0.001" class="form-control rounded-3 border-light py-2" required min="0.001">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="metric-label d-block mb-2">Motivo / Observação</label>
                            <textarea name="motivo" class="form-control rounded-3 border-light py-2" rows="3" placeholder="Ex: Ajuste de contagem, Perda, Brinde..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 py-2 rounded-pill fw-bold shadow-sm">Confirmar Ajuste</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- LOG -->
        <div class="col-lg-8">
            <div class="apple-table-container">
                <div class="table-responsive">
                    <table class="table apple-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Data/Hora</th>
                                <th>Produto</th>
                                <th>Operação</th>
                                <th>Qtd</th>
                                <th>Responsável</th>
                                <th class="pe-4">Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($logs)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Nenhum ajuste manual registrado.</td></tr>
                            <?php else: ?>
                                <?php foreach($logs as $log): ?>
                                <tr>
                                    <td class="ps-4" data-label="Data/Hora"><span class="small text-muted"><?= date('d/m H:i', strtotime($log['created_at'])) ?></span></td>
                                    <td data-label="Produto"><strong><?= htmlspecialchars($log['produto']) ?></strong></td>
                                    <td data-label="Operação">
                                        <span class="badge rounded-pill bg-opacity-10 <?= $log['action'] === 'entrada_manual' ? 'bg-success text-success' : 'bg-danger text-danger' ?> px-3">
                                            <?= $log['action'] === 'entrada_manual' ? 'ENTRADA' : 'SAÍDA' ?>
                                        </span>
                                    </td>
                                    <td data-label="Qtd" class="fw-bold"><?= number_format($log['quantity'], 2) ?></td>
                                    <td data-label="Responsável"><span class="small"><?= htmlspecialchars($log['usuario'] ?? 'Sistema') ?></span></td>
                                    <td class="pe-4" data-label="Detalhes"><span class="small text-secondary"><?= htmlspecialchars($log['details']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/rodape.php'; ?>
