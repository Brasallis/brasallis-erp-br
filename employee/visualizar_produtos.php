<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../includes/cabecalho.php';
require_once '../includes/funcoes.php';

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

// --- LÓGICA DE VISUALIZAÇÃO ---

// Filtros e Busca
$search_term = $_GET['search'] ?? '';
$show_expired = isset($_GET['show_expired']);

// Paginação
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Montagem da Query
$sql_where = " WHERE p.empresa_id = ?";
$params_where = [$empresa_id];

if (!empty($search_term)) {
    $sql_where .= " AND (p.name LIKE ? OR p.lote LIKE ?)";
    $params_where[] = '%' . $search_term . '%';
    $params_where[] = '%' . $search_term . '%';
}

if ($show_expired) {
    $sql_where .= " AND p.validade IS NOT NULL AND p.validade < CURDATE()";
}

// Total de registros para paginação
$total_stmt = $conn->prepare("SELECT COUNT(*) FROM produtos p" . $sql_where);
$total_stmt->execute($params_where);
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Busca dos produtos com JOIN na tabela de categorias
$query = "SELECT p.*, c.nome as categoria_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id" . $sql_where . " ORDER BY p.name ASC LIMIT ? OFFSET ?";
$products_stmt = $conn->prepare($query);
$i = 1;
foreach ($params_where as $param) {
    $products_stmt->bindValue($i++, $param);
}
$products_stmt->bindValue($i++, $limit, PDO::PARAM_INT);
$products_stmt->bindValue($i, $offset, PDO::PARAM_INT);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h1 class="mb-4">Visualização de Produtos</h1>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
        <h5 class="mb-0">Todos os Produtos</h5>
        <form action="" method="GET" class="d-flex flex-wrap gap-2">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou lote..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
            </div>
            <div class="form-check form-switch ms-3">
                <input class="form-check-input" type="checkbox" role="switch" id="showExpiredSwitch" name="show_expired" <?php echo $show_expired ? 'checked' : ''; ?> onchange="this.form.submit()">
                <label class="form-check-label" for="showExpiredSwitch">Mostrar Vencidos</label>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Estoque</th>
                        <th>Lote</th>
                        <th>Data de Validade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Nenhum produto encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                                $validade = $product['validade'];
                                $row_class = '';
                                $validade_text = 'N/A';
                                if ($validade) {
                                    $validade_date = new DateTime($validade);
                                    $hoje = new DateTime();
                                    $diff = $hoje->diff($validade_date)->days;
                                    $validade_text = $validade_date->format('d/m/Y');

                                    if ($validade_date < $hoje) {
                                        $row_class = 'table-danger'; // Vencido
                                    } elseif ($diff <= 30) {
                                        $row_class = 'table-warning'; // Vence em 30 dias
                                    }
                                }
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($product['categoria_nome'] ?? 'Sem categoria'); ?></span></td>
                                <td><?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unidade_medida']); ?></td>
                                <td><?php echo htmlspecialchars($product['lote'] ?? 'N/A'); ?></td>
                                <td><?php echo $validade_text; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewLots(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                        <i class="fas fa-boxes me-1"></i> Ver Lotes
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>&show_expired=<?php echo $show_expired ? 'on' : ''; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Modal de Lotes -->
<div class="modal fade" id="lotsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Histórico de Lotes - <span id="modalProductName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="lotsTable">
                        <thead>
                            <tr>
                                <th>Lote</th>
                                <th>Validade</th>
                                <th>Qtd. Inicial</th>
                                <th>Qtd. Atual</th>
                                <th>Fornecedor</th>
                                <th>Entrada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dados via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewLots(productId, productName) {
    document.getElementById('modalProductName').textContent = productName;
    const tbody = document.querySelector('#lotsTable tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Carregando...</td></tr>';
    
    const modal = new bootstrap.Modal(document.getElementById('lotsModal'));
    modal.show();

    fetch(`../api/get_product_lots.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            tbody.innerHTML = '';
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhum lote registrado para este produto.</td></tr>';
                return;
            }
            
            data.forEach(lot => {
                const validade = lot.data_validade ? new Date(lot.data_validade).toLocaleDateString('pt-BR') : 'N/A';
                const entrada = new Date(lot.data_entrada).toLocaleDateString('pt-BR');
                
                // Destaque para lotes ativos vs esgotados
                const rowClass = lot.quantidade_atual > 0 ? '' : 'text-muted bg-light';
                
                tbody.innerHTML += `
                    <tr class="${rowClass}">
                        <td><strong>${lot.numero_lote}</strong></td>
                        <td>${validade}</td>
                        <td>${lot.quantidade_inicial}</td>
                        <td><span class="badge ${lot.quantidade_atual > 0 ? 'bg-success' : 'bg-secondary'}">${lot.quantidade_atual}</span></td>
                        <td>${lot.fornecedor || '-'}</td>
                        <td>${entrada}</td>
                    </tr>
                `;
            });
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Erro ao carregar lotes.</td></tr>';
        });
}
</script>

<?php include_once '../includes/rodape.php'; ?>
