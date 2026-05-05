<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../includes/funcoes.php';

$conn = connect_db();

// --- LÓGICA DE VISUALIZAÇÃO (GET) ---
$filter_start_date = $_GET['start_date'] ?? '';
$filter_end_date = $_GET['end_date'] ?? '';
$filter_product_id = $_GET['product_id'] ?? 'all';
$filter_user_id = $_GET['user_id'] ?? 'all';
$filter_action = $_GET['action'] ?? 'all';

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$sql_where = " WHERE h.empresa_id = ?";
$params_where = [$_SESSION['empresa_id']];

if (!empty($filter_start_date)) { $sql_where .= " AND DATE(h.created_at) >= ?"; $params_where[] = $filter_start_date; }
if (!empty($filter_end_date)) { $sql_where .= " AND DATE(h.created_at) <= ?"; $params_where[] = $filter_end_date; }
if ($filter_product_id !== 'all') { $sql_where .= " AND h.product_id = ?"; $params_where[] = $filter_product_id; }
if ($filter_user_id !== 'all') { $sql_where .= " AND h.user_id = ?"; $params_where[] = $filter_user_id; }
if ($filter_action !== 'all') { $sql_where .= " AND h.action = ?"; $params_where[] = $filter_action; }

$base_sql = "FROM historico_estoque h JOIN produtos p ON h.product_id = p.id JOIN usuarios u ON h.user_id = u.id" . $sql_where;

$total_stmt = $conn->prepare("SELECT COUNT(*) " . $base_sql);
$total_stmt->execute($params_where);
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

$movements_sql = "SELECT h.id, h.action, h.quantity, h.created_at, p.name as product_name, u.username as user_name " . $base_sql . " ORDER BY h.created_at DESC LIMIT ? OFFSET ?";
$movements_stmt = $conn->prepare($movements_sql);
$i = 1;
foreach ($params_where as $param) { $movements_stmt->bindValue($i++, $param); }
$movements_stmt->bindValue($i++, $limit, PDO::PARAM_INT);
$movements_stmt->bindValue($i, $offset, PDO::PARAM_INT);
$movements_stmt->execute();
$movements = $movements_stmt->fetchAll(PDO::FETCH_ASSOC);

// Dados para os filtros
$products_stmt = $conn->prepare("SELECT id, name FROM produtos WHERE empresa_id = ? ORDER BY name ASC");
$products_stmt->execute([$_SESSION['empresa_id']]);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$users_stmt = $conn->prepare("SELECT id, username FROM usuarios WHERE empresa_id = ? ORDER BY username ASC");
$users_stmt->execute([$_SESSION['empresa_id']]);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Geração do HTML dos filtros de forma segura
$product_options_html = '';
foreach($products as $p) {
    $selected = ($filter_product_id == $p['id']) ? 'selected' : '';
    $product_options_html .= sprintf('<option value="%s" %s>%s</option>', $p['id'], $selected, htmlspecialchars($p['name']));
}

$user_options_html = '';
foreach($users as $u) {
    $selected = ($filter_user_id == $u['id']) ? 'selected' : '';
    $user_options_html .= sprintf('<option value="%s" %s>%s</option>', $u['id'], $selected, htmlspecialchars($u['username']));
}

include_once '../includes/cabecalho.php';
?>

<h1 class="mb-4">Histórico de Movimentações</h1>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <form action="movimentacoes.php" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-3"><label class="form-label">Produto</label><select name="product_id" class="form-select"><option value="all">Todos</option><?php echo $product_options_html; ?></select></div>
                <div class="col-md-2"><label class="form-label">Usuário</label><select name="user_id" class="form-select"><option value="all">Todos</option><?php echo $user_options_html; ?></select></div>
                <div class="col-md-2"><label class="form-label">Ação</label><select name="action" class="form-select"><option value="all">Todas</option><option value="entrada" <?php echo $filter_action === 'entrada' ? 'selected' : ''; ?>>Entrada</option><option value="saida" <?php echo $filter_action === 'saida' ? 'selected' : ''; ?>>Saída</option></select></div>
                <div class="col-md-2"><label class="form-label">De</label><input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filter_start_date); ?>"></div>
                <div class="col-md-2"><label class="form-label">Até</label><input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filter_end_date); ?>"></div>
                <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">Filtrar</button></div>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>Produto</th><th>Ação</th><th>Quantidade</th><th>Usuário</th><th>Data</th></tr></thead>
                <tbody>
                    <?php if (empty($movements)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Nenhuma movimentação encontrada para os filtros selecionados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movements as $m): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($m['product_name']); ?></strong></td>
                                <td><span class="badge bg-<?php echo $m['action'] === 'entrada' ? 'success' : 'warning'; ?>"><?php echo ucfirst($m['action']); ?></span></td>
                                <td><?php echo $m['quantity']; ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($m['user_name']); ?></td>
                                <td class="text-muted"><?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        <nav><ul class="pagination justify-content-center mb-0">
            <?php 
            $query_params = ['product_id' => $filter_product_id, 'user_id' => $filter_user_id, 'action' => $filter_action, 'start_date' => $filter_start_date, 'end_date' => $filter_end_date];
            for ($i = 1; $i <= $total_pages; $i++): 
                $page_query_params = array_merge($query_params, ['page' => $i]);
            ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>"><a class="page-link" href="?<?php echo http_build_query($page_query_params); ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
        </ul></nav>
    </div>
</div>

<?php include_once '../includes/rodape.php'; ?>
