<?php
/**
 * View: estoque/categorias/index
 */
$title = "Gestão de Categorias";
require BASE_PATH . '/resources/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1"><?= $title ?></h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="/admin/dashboard" class="text-decoration-none">Início</a></li>
                <li class="breadcrumb-item"><a href="/estoque/produtos" class="text-decoration-none">Estoque</a></li>
                <li class="breadcrumb-item active">Categorias</li>
            </ol>
        </nav>
    </div>
    <div>
        <button type="button" class="btn btn-premium btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus me-2"></i>Nova Categoria
        </button>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card-premium mb-4">
            <div class="card-header-premium">
                <h5 class="mb-0 fw-bold text-navy">Listagem de Categorias</h5>
                <form action="/estoque/categorias" method="GET" class="d-flex" style="max-width: 250px;">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control bg-light border-0" 
                               placeholder="Filtrar..." value="<?= htmlspecialchars($search ?? '') ?>">
                        <button class="btn btn-light border-0" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-uppercase text-muted fw-bold">
                            <th class="ps-4">Nome da Categoria</th>
                            <th>Criada em</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php if(empty($categorias)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <i class="fas fa-tags fa-3x text-light mb-3 d-block"></i>
                                    <span class="text-muted">Nenhuma categoria encontrada.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categorias as $cat): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-navy"><?= htmlspecialchars($cat['nome']) ?></div>
                                    </td>
                                    <td>
                                        <span class="text-muted small">
                                            <?= date('d/m/Y', strtotime($cat['created_at'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-light border edit-cat-btn" 
                                                    data-id="<?= $cat['id'] ?>" data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                                                <i class="fas fa-pencil-alt text-muted"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-light border text-danger delete-cat-btn" 
                                                    data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['nome']) ?>" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteCategoryModal">
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
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card-premium p-4 bg-navy text-white border-0 shadow-lg">
            <h5 class="fw-bold mb-3">Dica de Organização</h5>
            <p class="small opacity-75 mb-0">
                Mantenha suas categorias bem definidas para facilitar a emissão de relatórios e o controle de estoque mínimo por setor.
            </p>
            <div class="mt-4">
                <div class="small fw-bold text-uppercase opacity-50 mb-2">Total de Categorias</div>
                <div class="h2 fw-bold mb-0"><?= count($categorias) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-xl">
            <form action="/estoque/categorias" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy h4">Nova Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Nome da Categoria</label>
                    <input type="text" name="nome" class="form-control form-control-lg rounded-3 border-2" 
                           placeholder="Ex: Materiais de Construção" required>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-premium btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-premium btn-primary px-4">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-xl">
            <form id="editCategoryForm" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy h4">Editar Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Nome da Categoria</label>
                    <input type="text" name="nome" id="editCatNome" class="form-control form-control-lg rounded-3 border-2" required>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-premium btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-premium btn-primary px-4">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Deletar -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <form id="deleteCategoryForm" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold text-navy h4">Excluir Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3"><i class="fas fa-trash-alt fa-3x text-danger opacity-25"></i></div>
                    <p class="mb-0">Deseja realmente excluir a categoria <strong><span id="delCatName"></span></strong>?</p>
                    <p class="small text-muted">Os produtos desta categoria ficarão "Sem Categoria".</p>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 justify-content-center">
                    <button type="button" class="btn btn-premium btn-light mx-2" data-bs-dismiss="modal">Manter</button>
                    <button type="submit" class="btn btn-premium btn-danger mx-2 px-4">Sim, excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Editar
    document.querySelectorAll('.edit-cat-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const form = document.getElementById('editCategoryForm');
            form.action = `/estoque/categorias/${id}/update`;
            
            fetch(`/api/v1/estoque/categorias/${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('editCatNome').value = data.nome;
                });
        });
    });

    // Deletar
    document.querySelectorAll('.delete-cat-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            document.getElementById('delCatName').textContent = name;
            document.getElementById('deleteCategoryForm').action = `/estoque/categorias/${id}/delete`;
        });
    });
});
</script>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
