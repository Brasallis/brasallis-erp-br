<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../includes/funcoes.php';
checkAuth();
use App\Repository\ProdutoRepository;
use App\Core\Database;

$empresa_id = $_SESSION['empresa_id'];
$produtoRepository = new ProdutoRepository(Database::getInstance(), $empresa_id);

$suppliers_stmt = Database::getInstance()->prepare("SELECT id, name FROM fornecedores WHERE empresa_id = ? ORDER BY name");
$suppliers_stmt->execute([$empresa_id]);
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Administradores e SuperAdmins têm permissão total por padrão
$is_admin = (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['admin', 'super_admin']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        // Administradores e SuperAdmins têm permissão total por padrão
        $is_admin = (isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['admin', 'super_admin']));

        // Proteção CSRF
        if ($action !== '') {
            verify_csrf_token($_POST['csrf_token'] ?? '');
        }
        if ($action === 'add') {
            if (!$is_admin && !has_permission('estoque', 2)) throw new Exception("Nível de autoridade insuficiente (Nível 2 necessário).");
            $produtoRepository->add($_POST); 
            $_SESSION['message'] = 'Produto adicionado com sucesso!'; 
        } elseif ($action === 'edit') { 
            if (!$is_admin && !has_permission('estoque', 2)) throw new Exception("Nível de autoridade insuficiente (Nível 2 necessário).");
            if ($produtoRepository->update($_POST)) {
                $_SESSION['message'] = 'Produto atualizado com sucesso!'; 
            } else {
                throw new Exception("Não foi possível atualizar o produto. Verifique os dados.");
            }
        } elseif ($action === 'stock_entry') {
            if (!$is_admin && !has_permission('estoque', 2)) throw new Exception("Nível de autoridade insuficiente.");
            if ($produtoRepository->registerEntry($_POST)) {
                $_SESSION['message'] = 'Entrada de mercadoria registrada com sucesso!';
            } else {
                throw new Exception("Falha ao registrar entrada de mercadoria.");
            }
        } elseif ($action === 'delete') { 
            if (!$is_admin && !has_permission('estoque', 3)) throw new Exception("Nível de autoridade insuficiente (Gerente necessário).");
            $produtoRepository->delete($_POST['id']); 
            $_SESSION['message'] = 'Produto excluído!'; 
        }
    } catch (Exception $e) { reportar_erro($e, 'Produtos'); }
    header("Location: produtos.php"); exit;
}

include_once '../includes/cabecalho.php';

$search_term = $_GET['search'] ?? '';
$selected_category = $_GET['categoria_id'] ?? 'all';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$filter_low_stock = ($_GET['filter'] ?? '') === 'low_stock';

$total_results = $produtoRepository->countAll($search_term, $selected_category, $filter_low_stock);
$total_pages = ceil($total_results / $limit);
$products = $produtoRepository->getAll($search_term, $selected_category, $limit, $offset, $filter_low_stock);
$categories = $produtoRepository->getCategories();
?>

<style>
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
    
    .product-card { 
        background: #fff; border-radius: 20px; padding: 20px; 
        border: 1px solid rgba(0,0,0,0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex; flex-wrap: wrap; align-items: center; gap: 16px; cursor: pointer; position: relative;
    }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); border-color: var(--brasallis-primary); }
    
    .product-icon { 
        width: 54px; height: 54px; background: #f8fafc; border-radius: 14px; 
        display: flex; align-items: center; justify-content: center; 
        color: var(--navy); font-size: 1.3rem; flex-shrink: 0;
        border: 1px solid rgba(0,0,0,0.03);
    }
    
    .product-info { flex: 1; min-width: 150px; }
    .product-title { font-size: 1rem; font-weight: 700; color: var(--navy); margin-bottom: 4px; }
    
    .product-meta { display: flex; flex-wrap: wrap; gap: 6px; }
    .badge-google { padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
    .badge-price { background: #e0f2fe; color: #0369a1; }
    .badge-cat { background: #f1f5f9; color: #64748b; }
    .badge-danger { background: #fee2e2; color: #dc2626; }

    .product-stock { text-align: right; }
    .stock-val { font-size: 1.25rem; font-weight: 800; color: var(--navy); line-height: 1; }
    .stock-label { font-size: 0.65rem; font-weight: 700; color: #94a3b8; margin-top: 4px; }

    .card-actions { width: 100%; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.05); }

    .fab {
        position: fixed; bottom: 100px; right: 24px; width: 56px; height: 56px;
        background: var(--brasallis-primary); color: #fff; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 112, 242, 0.3); border: none; z-index: 1000;
    }

    @media (max-width: 768px) {
        .desktop-only { display: none !important; }
        .page-container { padding: 10px 10px 120px 10px; }
        .product-card { padding: 15px; }
        .product-info { flex: 1; min-width: 0; }
        .product-stock { width: auto; }
        .card-actions { flex-direction: column; }
        .card-actions .btn { width: 100%; text-align: center; justify-content: center; }
    }
</style>

<div class="page-container">
    <?php if (isset($_SESSION['message'])) : ?>
        <div class="alert alert-info alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-info-circle me-2"></i><?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <div class="page-title-group">
            <h1 class="fw-bold">Estoque Central</h1>
            <p class="text-muted"><?= $total_results ?> itens encontrados</p>
        </div>
        <?php if ($is_admin || has_permission('estoque', 2)): ?>
        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-none d-md-inline-block" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus me-2"></i>Novo Produto
        </button>
        <?php endif; ?>
    </div>

    <?php if ($is_admin || has_permission('estoque', 2)): ?>
    <button class="fab shadow-lg d-md-none" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="fas fa-plus fa-lg"></i></button>
    <?php endif; ?>

    <div class="section-card border-0 p-3 mb-4">
        <form action="produtos.php" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm bg-light rounded-pill px-3">
                    <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-transparent border-0" placeholder="Buscar por nome ou SKU..." value="<?= htmlspecialchars($search_term) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="categoria_id" class="form-select form-select-sm border-0 bg-light rounded-pill px-3" onchange="this.form.submit()">
                    <option value="all">Todas Categorias</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($selected_category == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <a href="?filter=low_stock" class="btn btn-sm <?= $filter_low_stock ? 'btn-danger' : 'btn-outline-danger' ?> rounded-pill px-3 fw-bold">
                    <i class="fas fa-exclamation-triangle me-1"></i> Estoque Baixo
                </a>
            </div>
        </form>
    </div>

    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card" onclick="openEdit(<?= $product['id'] ?>)">
                <div class="product-icon"><i class="fas fa-cube"></i></div>
                <div class="product-info">
                    <div class="product-title"><?= htmlspecialchars($product['name']) ?></div>
                    <div class="product-meta">
                        <span class="badge-google badge-price">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                        <span class="badge-google badge-cat"><?= htmlspecialchars($product['categoria_nome'] ?? 'Geral') ?></span>
                        <?php if($product['quantity'] <= $product['minimum_stock']): ?>
                            <span class="badge-google badge-danger">Crítico</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="product-stock text-end">
                    <div class="stock-val <?= $product['quantity'] <= $product['minimum_stock'] ? 'text-danger' : '' ?>"><?= $product['quantity'] ?></div>
                    <div class="stock-label text-muted"><?= strtoupper($product['unidade_medida']) ?></div>
                </div>
                <div class="card-actions mt-3 pt-3 border-top d-flex gap-2 w-100">
                    <button class="btn btn-sm btn-light rounded-pill flex-grow-1 fw-bold text-primary" onclick="event.stopPropagation(); openEdit(<?= $product['id'] ?>)">
                        <i class="fas fa-edit me-1"></i> Detalhes
                    </button>
                    <?php if ($is_admin || has_permission('estoque', 2)): ?>
                    <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" onclick="event.stopPropagation(); openEntry(<?= $product['id'] ?>)">
                        <i class="fas fa-cart-plus me-1"></i> Entrada
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if($total_pages > 1): ?>
    <div class="mt-5 d-flex justify-content-center">
        <nav><ul class="pagination pagination-sm gap-2">
            <?php for($i=1; $i<=$total_pages; $i++): ?>
            <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                <a class="page-link rounded-circle border-0 shadow-sm <?= $page == $i ? 'bg-primary text-white' : 'bg-white text-muted' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search_term) ?>&categoria_id=<?= $selected_category ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL ADD (FULLSCREEN GOOGLE) -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-mobile">
        <div class="modal-content border-0">
            <form action="produtos.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="modal-header border-0">
                    <h5 class="fw-bold m-0">Cadastrar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="section-title mb-3">Informações de Catálogo</div>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label small fw-bold text-muted">NOME DO ITEM</label><input type="text" name="name" class="form-control form-control-lg" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">DESCRIÇÃO</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                        <div class="col-6"><label class="form-label small fw-bold text-muted">SKU / CÓDIGO</label><input type="text" name="sku" class="form-control form-control-lg"></div>
                        <div class="col-6"><label class="form-label small fw-bold text-muted">CATEGORIA</label>
                            <select name="categoria_id" class="form-select form-select-lg">
                                <option value="">Nenhuma</option>
                                <?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="section-title mt-4 mb-3">Financeiro e Estoque</div>
                    <div class="row g-3">
                        <div class="col-6 col-md-3"><label class="form-label small fw-bold text-muted">PREÇO CUSTO</label><input type="number" step="0.01" name="cost_price" class="form-control form-control-lg" required></div>
                        <div class="col-6 col-md-3"><label class="form-label small fw-bold text-muted">PREÇO VENDA</label><input type="number" step="0.01" name="price" class="form-control form-control-lg" required></div>
                        <div class="col-6 col-md-3"><label class="form-label small fw-bold text-muted">UNIDADE</label>
                            <select name="unidade_medida" class="form-select form-select-lg">
                                <option value="un">un</option><option value="kg">kg</option><option value="l">l</option><option value="ml">ml</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3"><label class="form-label small fw-bold text-muted">QTD INICIAL</label><input type="number" name="quantity" class="form-control form-control-lg" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">ALERTA DE ESTOQUE MÍNIMO</label><input type="number" name="minimum_stock" class="form-control form-control-lg" value="5"></div>
                    </div>

                    <div class="section-title mt-4 mb-3">Rastreio (Opcional)</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6"><label class="form-label small fw-bold text-muted">LOTE</label><input type="text" name="lote" class="form-control form-control-lg"></div>
                        <div class="col-12 col-md-6"><label class="form-label small fw-bold text-muted">VALIDADE</label><input type="date" name="validade" class="form-control form-control-lg"></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">OBSERVAÇÕES</label><textarea name="observacoes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Descartar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Salvar Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT (FULLSCREEN GOOGLE) -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-mobile">
        <div class="modal-content border-0">
            <form action="produtos.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editProductId">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="modal-header border-0 shadow-sm">
                    <h5 class="fw-bold m-0">Editar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="section-title mb-3">Dados Gerais</div>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label small fw-bold text-muted">NOME</label><input type="text" name="name" id="editProductName" class="form-control form-control-lg" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">DESCRIÇÃO</label><textarea name="description" id="editProductDescription" class="form-control" rows="2"></textarea></div>
                        <div class="col-12 col-md-6"><label class="form-label small fw-bold text-muted">SKU</label><input type="text" name="sku" id="editProductSku" class="form-control form-control-lg"></div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">CATEGORIA</label>
                            <select name="categoria_id" id="editProductCategory" class="form-select form-select-lg">
                                <option value="">Nenhuma</option>
                                <?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">UNIDADE</label>
                            <select name="unidade_medida" id="editProductUnidadeMedida" class="form-select form-select-lg">
                                <option value="un">un</option><option value="kg">kg</option><option value="l">l</option><option value="ml">ml</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-title mt-4 mb-3">Financeiro e Saldo</div>
                    <div class="row g-3">
                        <div class="col-6 col-md-4"><label class="form-label small fw-bold text-muted">PREÇO CUSTO</label><input type="number" step="0.01" name="cost_price" id="editProductCostPrice" class="form-control form-control-lg"></div>
                        <div class="col-6 col-md-4"><label class="form-label small fw-bold text-muted">PREÇO VENDA</label><input type="number" step="0.01" name="price" id="editProductPrice" class="form-control form-control-lg" required></div>
                        <div class="col-12 col-md-4"><label class="form-label small fw-bold text-muted">ESTOQUE ATUAL</label><input type="number" name="quantity" id="editProductQuantity" class="form-control form-control-lg" required></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">MÍNIMO PARA ALERTA</label><input type="number" name="minimum_stock" id="editProductMinimumStock" class="form-control form-control-lg" required></div>
                    </div>

                    <div class="section-title mt-4 mb-3">Logística</div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6"><label class="form-label small fw-bold text-muted">LOTE</label><input type="text" name="lote" id="editProductLote" class="form-control form-control-lg"></div>
                        <div class="col-12 col-md-6"><label class="form-label small fw-bold text-muted">VALIDADE</label><input type="date" name="validade" id="editProductValidade" class="form-control form-control-lg"></div>
                        <div class="col-12"><label class="form-label small fw-bold text-muted">OBSERVAÇÕES</label><textarea name="observacoes" id="editProductObservacoes" class="form-control" rows="2"></textarea></div>
                    </div>

                    <?php if ($is_admin || has_permission('estoque', 3)): ?>
                    <div class="mt-5 p-3 bg-light rounded-4 d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-muted text-uppercase">Ações Críticas</span>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="confirmDelete()">Excluir Item</button>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Fechar</button>
                    <?php if ($is_admin || has_permission('estoque', 2)): ?>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Atualizar</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL ENTRADA (MÉTODO MANUAL FOUNDATION) -->
<div class="modal fade" id="entryProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="produtos.php" method="POST">
                <input type="hidden" name="action" value="stock_entry">
                <input type="hidden" name="product_id" id="entryProductId">
                <input type="hidden" name="product_name" id="entryProductNameRef">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="modal-header border-0 bg-success text-white">
                    <h5 class="fw-bold m-0"><i class="fas fa-cart-plus me-2"></i>Registrar Nova Entrada</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <h6 class="text-muted small fw-bold text-uppercase mb-1">PRODUTO SELECIONADO</h6>
                        <h4 class="fw-bold text-navy" id="entryProductNameDisplay">Nome do Produto</h4>
                        <span class="badge bg-light text-muted border" id="entryProductSkuDisplay">SKU: -</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">FORNECEDOR</label>
                            <select name="supplier_id" class="form-select form-select-lg">
                                <option value="">Não informado / Compra Avulsa</option>
                                <?php foreach ($suppliers as $sup): ?><option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">QUANTIDADE ENTRADA</label>
                            <input type="number" name="quantity" class="form-control form-control-lg border-primary text-primary fw-bold" step="any" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">CUSTO UNITÁRIO (R$)</label>
                            <input type="number" name="cost_price" id="entryProductCost" class="form-control form-control-lg" step="0.01" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">NOVO PREÇO DE VENDA (R$)</label>
                            <input type="number" name="sell_price" id="entryProductSell" class="form-control form-control-lg bg-light" step="0.01" required>
                            <small class="text-muted d-block mt-1">Sugestão: Mantenha ou atualize conforme nova margem.</small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">LOTE</label>
                            <input type="text" name="lote" class="form-control" placeholder="Ex: LOT-2024-001">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">VALIDADE</label>
                            <input type="date" name="validade" class="form-control">
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-0 py-2 border-0 rounded-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <span class="small">Esta ação atualizará o saldo em estoque e registrará uma despesa no seu <strong>Fluxo de Caixa</strong>.</span>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm">Confirmar Entrada</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let editModal, entryModal;
document.addEventListener('DOMContentLoaded', function() {
    editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
    entryModal = new bootstrap.Modal(document.getElementById('entryProductModal'));
});

function openEdit(id) {
    fetch(`../api/get_product.php?id=${id}`)
    .then(r => r.json())
    .then(data => {
        document.getElementById('editProductId').value = data.id;
        document.getElementById('editProductName').value = data.name;
        document.getElementById('editProductDescription').value = data.description || '';
        document.getElementById('editProductSku').value = data.sku || '';
        document.getElementById('editProductCategory').value = data.categoria_id || '';
        document.getElementById('editProductUnidadeMedida').value = data.unidade_medida || 'un';
        document.getElementById('editProductCostPrice').value = data.cost_price || '';
        document.getElementById('editProductPrice').value = data.price;
        document.getElementById('editProductQuantity').value = data.quantity;
        document.getElementById('editProductMinimumStock').value = data.minimum_stock;
        document.getElementById('editProductLote').value = data.lote || '';
        document.getElementById('editProductValidade').value = data.validade || '';
        document.getElementById('editProductObservacoes').value = data.observacoes || '';
        editModal.show();
    });
}

function openEntry(id) {
    fetch(`../api/get_product.php?id=${id}`)
    .then(r => r.json())
    .then(data => {
        document.getElementById('entryProductId').value = data.id;
        document.getElementById('entryProductNameRef').value = data.name;
        document.getElementById('entryProductNameDisplay').textContent = data.name;
        document.getElementById('entryProductSkuDisplay').textContent = 'SKU: ' + (data.sku || 'N/A');
        document.getElementById('entryProductCost').value = data.cost_price;
        document.getElementById('entryProductSell').value = data.price;
        entryModal.show();
    });
}
function confirmDelete() {
    const name = document.getElementById('editProductName').value;
    if(confirm(`Deseja excluir "${name}" permanentemente?`)) {
        const id = document.getElementById('editProductId').value;
        const f = document.createElement('form');
        f.method = 'POST'; f.action = 'produtos.php';
        f.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}"><input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">`;
        document.body.appendChild(f); f.submit();
    }
}
</script>
<?php include_once '../includes/rodape.php'; ?>
