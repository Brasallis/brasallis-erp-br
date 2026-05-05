<?php
/**
 * View: estoque/produtos/index
 */
$title = "Catálogo de Produtos";
require BASE_PATH . '/resources/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1"><?= $title ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">Início</a></li>
                <li class="breadcrumb-item active">Produtos</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="/estoque/categorias" class="btn btn-premium btn-light border">
            <i class="fas fa-tags me-2"></i>Categorias
        </a>
        <button type="button" class="btn btn-premium btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus me-2"></i>Novo Produto
        </button>
    </div>
</div>

<div class="card-premium mb-4">
    <div class="card-header-premium">
        <form action="/estoque/produtos" method="GET" class="d-flex flex-wrap gap-2 w-100">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0 ps-0 bg-light" 
                       placeholder="Buscar produto ou SKU..." value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            
            <select name="categoria_id" class="form-select bg-light" style="max-width: 200px;" onchange="this.form.submit()">
                <option value="all">Todas as Categorias</option>
                <?php foreach ($categorias as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= ($categoria_id == $category['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="form-check form-switch d-flex align-items-center ms-md-2">
                <input class="form-check-input me-2" type="checkbox" name="filter" value="low_stock" id="lowStockFilter" 
                       <?= ($low_stock ?? false) ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="form-check-label small fw-bold text-secondary" for="lowStockFilter">Baixo Estoque</label>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr class="small text-uppercase text-muted fw-bold">
                    <th class="ps-4">Produto / SKU</th>
                    <th>Categoria</th>
                    <th>Preço Venda</th>
                    <th>Estoque</th>
                    <th class="text-end pe-4">Ações</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php if(empty($items)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-light mb-3 d-block"></i>
                            <span class="text-muted">Nenhum produto encontrado.</span>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $product): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-navy"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($product['sku'] ?? 'S/ SKU') ?></div>
                                <?php if($product['quantity'] <= $product['minimum_stock']): ?>
                                    <span class="badge bg-danger-light text-danger mt-1" style="font-size: 0.65rem;">BAIXO ESTOQUE</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if(!empty($product['categoria_nome'])): ?>
                                    <span class="badge bg-light text-navy border font-weight-500">
                                        <?= htmlspecialchars($product['categoria_nome']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-emerald">
                                R$ <?= number_format($product['price'], 2, ',', '.') ?>
                            </td>
                            <td>
                                <span class="<?= $product['quantity'] <= $product['minimum_stock'] ? 'text-danger fw-bold' : 'fw-medium' ?>">
                                    <?= $product['quantity'] ?>
                                </span> 
                                <span class="small text-muted"><?= htmlspecialchars($product['unidade_medida'] ?? 'un') ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-light border edit-btn" 
                                            data-id="<?= $product['id'] ?>" data-bs-toggle="modal" data-bs-target="#editProductModal">
                                        <i class="fas fa-pencil-alt text-muted"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border text-danger delete-btn" 
                                            data-id="<?= $product['id'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>" 
                                            data-bs-toggle="modal" data-bs-target="#deleteProductModal">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if(($total_pages ?? 1) > 1): ?>
        <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Página <strong><?= $page ?></strong> de <strong><?= $total_pages ?></strong>
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&categoria_id=<?= $categoria_id ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Adicionar Produto -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form action="/estoque/produtos" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy h4">Adicionar Novo Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-uppercase text-muted">Nome do Produto</label>
                            <input type="text" name="name" class="form-control rounded-3" required placeholder="Ex: Cimento 50kg">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">SKU / Código</label>
                            <input type="text" name="sku" class="form-control rounded-3" placeholder="Ex: CIM50">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-muted">Descrição</label>
                            <textarea name="description" class="form-control rounded-3" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Preço de Custo (R$)</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Preço de Venda (R$)</label>
                            <input type="number" step="0.01" name="price" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Unidade de Medida</label>
                            <select name="unidade_medida" class="form-select rounded-3" required>
                                <option value="un">Unidade (un)</option>
                                <option value="kg">Quilograma (kg)</option>
                                <option value="l">Litro (l)</option>
                                <option value="m">Metro (m)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Categoria</label>
                            <select name="categoria_id" class="form-select rounded-3">
                                <option value="">Sem Categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Qtd. Inicial</label>
                            <input type="number" name="quantity" class="form-control rounded-3" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Estoque Mínimo</label>
                            <input type="number" name="minimum_stock" class="form-control rounded-3" value="5" required>
                        </div>
                        
                        <!-- Campos Fiscais -->
                        <div class="col-12"><hr class="my-2"> <span class="small fw-bold text-navy">INFORMAÇÕES FISCAIS (Obrigatório para Nota Fiscal)</span></div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">NCM</label>
                            <input type="text" name="ncm" class="form-control rounded-3" placeholder="Ex: 25232910">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">CFOP Padrão</label>
                            <input type="text" name="cfop" class="form-control rounded-3" placeholder="Ex: 5102" value="5102">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Origem</label>
                            <select name="origem" class="form-select rounded-3">
                                <option value="0">0 - Nacional</option>
                                <option value="1">1 - Importação Direta</option>
                                <option value="2">2 - Estrangeira (Mercado Interno)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-premium btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-premium btn-primary px-4">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Produto (Populado via AJAX) -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form id="editProductForm" method="POST">
                <input type="hidden" name="id" id="editProductId">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy h4">Editar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Mesmos campos do add, IDs diferentes -->
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-uppercase text-muted">Nome do Produto</label>
                            <input type="text" name="name" id="editProductName" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">SKU / Código</label>
                            <input type="text" name="sku" id="editProductSku" class="form-control rounded-3">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-uppercase text-muted">Descrição</label>
                            <textarea name="description" id="editProductDescription" class="form-control rounded-3" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Preço de Custo</label>
                            <input type="number" step="0.01" name="cost_price" id="editProductCostPrice" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Preço de Venda</label>
                            <input type="number" step="0.01" name="price" id="editProductPrice" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Unidade</label>
                            <select name="unidade_medida" id="editProductUnidade" class="form-select rounded-3">
                                <option value="un">un</option><option value="kg">kg</option><option value="l">l</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Categoria</label>
                            <select name="categoria_id" id="editProductCategoria" class="form-select rounded-3">
                                <option value="">Sem Categoria</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Estoque Atual</label>
                            <input type="number" name="quantity" id="editProductQuantity" class="form-control rounded-3" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-uppercase text-muted">Estoque Mínimo</label>
                            <input type="number" name="minimum_stock" id="editProductMinStock" class="form-control rounded-3" required>
                        </div>

                        <!-- Campos Fiscais -->
                        <div class="col-12"><hr class="my-2"> <span class="small fw-bold text-navy">INFORMAÇÕES FISCAIS</span></div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">NCM</label>
                            <input type="text" name="ncm" id="editProductNcm" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">CFOP</label>
                            <input type="text" name="cfop" id="editProductCfop" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Origem</label>
                            <select name="origem" id="editProductOrigem" class="form-select rounded-3">
                                <option value="0">0 - Nacional</option>
                                <option value="1">1 - Importação Direta</option>
                                <option value="2">2 - Estrangeira (Mercado Interno)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-premium btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-premium btn-primary px-4">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Deletar -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form id="deleteProductForm" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy h4">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                    </div>
                    <p class="mb-0">Deseja realmente excluir <strong><span id="deleteProductNameText"></span></strong>?</p>
                    <p class="small text-muted">Esta ação não poderá ser desfeita após a confirmação.</p>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 justify-content-center">
                    <button type="button" class="btn btn-premium btn-light mx-2" data-bs-dismiss="modal">Não, manter</button>
                    <button type="submit" class="btn btn-premium btn-danger mx-2 px-4 shadow-sm">Sim, excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Modal de Edição
    const editModal = document.getElementById('editProductModal');
    editModal.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        const id  = btn.getAttribute('data-id');
        const form = document.getElementById('editProductForm');
        
        form.action = `/estoque/produtos/${id}/update`;
        
        // Fetch dados do produto via API v1
        fetch(`/api/v1/estoque/produtos/${id}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('editProductId').value = data.id;
                document.getElementById('editProductName').value = data.name;
                document.getElementById('editProductSku').value = data.sku || '';
                document.getElementById('editProductDescription').value = data.description || '';
                document.getElementById('editProductCostPrice').value = data.cost_price;
                document.getElementById('editProductPrice').value = data.price;
                document.getElementById('editProductUnidade').value = data.unidade_medida || 'un';
                document.getElementById('editProductCategoria').value = data.categoria_id || '';
                document.getElementById('editProductQuantity').value = data.quantity;
                document.getElementById('editProductMinStock').value = data.minimum_stock;
                
                // Novos campos fiscais
                document.getElementById('editProductNcm').value = data.ncm || '';
                document.getElementById('editProductCfop').value = data.cfop || '';
                document.getElementById('editProductOrigem').value = data.origem || '0';
            });
    });

    // Modal de Deletar
    const deleteModal = document.getElementById('deleteProductModal');
    deleteModal.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        const id  = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        
        document.getElementById('deleteProductNameText').textContent = name;
        document.getElementById('deleteProductForm').action = `/estoque/produtos/${id}/delete`;
    });
});
</script>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
