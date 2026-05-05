<?php
/**
 * View: estoque/fornecedores/index
 */
$title = "Gestão de Fornecedores";
require BASE_PATH . '/resources/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-navy mb-1">Fornecedores</h2>
        <p class="text-secondary mb-0">Gerencie seus parceiros e contatos comerciais.</p>
    </div>
    <button class="btn btn-premium btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddFornecedor">
        <i class="fas fa-plus me-2"></i>Novo Fornecedor
    </button>
</div>

<div class="card-premium border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-secondary small text-uppercase fw-bold">
                <tr>
                    <th class="ps-4">Nome / Razão Social</th>
                    <th>CNPJ</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th class="text-end pe-4">Ações</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php if (empty($fornecedores)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">Nenhum fornecedor cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fornecedores as $f): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-navy"><?= htmlspecialchars($f['nome']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($f['endereco'] ?? '-') ?></div>
                            </td>
                            <td><?= htmlspecialchars($f['cnpj'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($f['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($f['telefone'] ?? '-') ?></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-light border text-navy me-1" onclick="editFornecedor(<?= $f['id'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-light border text-danger" onclick="deleteFornecedor(<?= $f['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add Fornecedor -->
<div class="modal fade" id="modalAddFornecedor" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
            <form action="/estoque/fornecedores" method="POST">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold text-navy mb-0">Novo Fornecedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nome / Razão Social</label>
                            <input type="text" name="nome" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">CNPJ</label>
                            <input type="text" name="cnpj" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Telefone</label>
                            <input type="text" name="telefone" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email</label>
                            <input type="email" name="email" class="form-control form-control-premium">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Endereço Completo</label>
                            <textarea name="endereco" class="form-control form-control-premium" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-premium btn-dark px-4">Salvar Fornecedor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Fornecedor (Stub) -->
<div class="modal fade" id="modalEditFornecedor" tabindex="-1">
    <!-- Similar ao Add, preenchido via JS -->
</div>

<script>
function editFornecedor(id) {
    fetch(`/api/v1/estoque/fornecedores/${id}`)
        .then(res => res.json())
        .then(data => {
            // Preencher campos e mostrar modal
            // (Para agilizar, podemos reaproveitar o modal de Add mudando action e titulo se preferir)
        });
}

function deleteFornecedor(id) {
    if (confirm('Deseja realmente remover este fornecedor?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/estoque/fornecedores/${id}/delete`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require BASE_PATH . '/resources/views/layouts/footer.php'; ?>
