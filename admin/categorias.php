<?php
// A lógica de processamento de formulário DEVE vir antes de qualquer output HTML.
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../includes/funcoes.php';

use App\Repository\CategoriaRepository;
use App\Core\Database;

$empresa_id = $_SESSION['empresa_id'];
$categoriaRepository = new CategoriaRepository(Database::getInstance(), $empresa_id);

// Apenas processa se for um POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Adicionar Categoria
    if (isset($_POST['add_categoria'])) {
        $nome = trim($_POST['nome']);
        if (!empty($nome)) {
            try {
                $categoriaRepository->add($nome);
                $_SESSION['message'] = 'Categoria adicionada com sucesso!';
                $_SESSION['message_type'] = 'success';
            } catch (PDOException $e) {
                $_SESSION['message'] = 'Erro ao adicionar categoria: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = 'O nome da categoria não pode ser vazio.';
            $_SESSION['message_type'] = 'warning';
        }
    }

    // Editar Categoria
    if (isset($_POST['edit_categoria'])) {
        $id = $_POST['edit_id'];
        $nome = trim($_POST['edit_nome']);
        if (!empty($nome) && !empty($id)) {
            try {
                $categoriaRepository->update($id, $nome);
                $_SESSION['message'] = 'Categoria atualizada com sucesso!';
                $_SESSION['message_type'] = 'success';
            } catch (PDOException $e) {
                $_SESSION['message'] = 'Erro ao atualizar categoria: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = 'O nome da categoria não pode ser vazio.';
            $_SESSION['message_type'] = 'warning';
        }
    }

    // Excluir Categoria
    if (isset($_POST['delete_categoria'])) {
        $id = $_POST['delete_id'];
        try {
            $categoriaRepository->delete($id);
            $_SESSION['message'] = 'Categoria excluída com sucesso!';
            $_SESSION['message_type'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Erro ao excluir categoria: ' . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }
    }

    // Redireciona após o processamento para evitar reenvio
    header("Location: categorias.php");
    exit();
}

// --- A partir daqui, começa a renderização da página ---
include_once '../includes/cabecalho.php';

// --- LÓGICA DE VISUALIZAÇÃO ---
$search = $_GET['search'] ?? '';
$categorias = $categoriaRepository->getAll($search);

?>

<div class="container-fluid py-4">
    <!-- Header Estratégico -->
    <div class="row align-items-center mb-5 pb-4 border-bottom border-light">
        <div class="col-md-7 col-lg-8">
            <div class="metric-label mb-2"><i class="fas fa-tags me-1 text-primary"></i> Taxonomia</div>
            <h1 class="greeting">Categorias</h1>
            <p class="text-muted mb-0 mt-2" style="font-weight: 500;">Organização lógica de produtos.</p>
        </div>
        <div class="col-md-5 col-lg-4 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-dark shadow-sm rounded-pill px-4 py-2 fw-bold" style="font-size: 0.8rem;" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus me-2 opacity-50"></i> Nova Categoria
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show shadow-sm rounded-4 border-0" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Busca -->
    <div class="exec-card p-0 mb-4" style="background: rgba(255, 255, 255, 0.4); border: 1px solid rgba(0,0,0,0.05);">
        <div class="px-4 py-3">
            <form method="GET" action="" class="d-flex flex-wrap gap-3">
                <div class="input-group" style="max-width: 400px; background: rgba(0,0,0,0.03); border-radius: 12px; padding: 2px 10px;">
                    <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted opacity-50"></i></span>
                    <input type="text" name="search" class="form-control bg-transparent border-0 ps-0 shadow-none" placeholder="Buscar por nome..." value="<?php echo htmlspecialchars($search); ?>" style="font-size: 0.85rem; font-weight: 500;">
                    <button class="btn btn-link text-navy p-0 border-0" type="submit"><i class="fas fa-arrow-right"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela Responsiva -->
    <div class="apple-table-container">
        <div class="table-responsive">
            <table class="table apple-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-5">Nome</th>
                        <th>Data de Criação</th>
                        <th class="text-end pe-5">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr><td colspan="3" class="text-center py-5 text-muted">Nenhuma categoria encontrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td class="ps-5" data-label="Nome">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($categoria['nome']); ?></div>
                                        <span class="badge rounded-pill bg-light text-muted border fw-normal" style="font-size: 0.65rem;">
                                            <?php echo $categoria['total_produtos']; ?> itens
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Data de Criação">
                                    <span class="text-muted small"><?php echo date('d/m/Y', strtotime($categoria['created_at'])); ?></span>
                                </td>
                                <td class="text-end pe-5" data-label="Ações">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="produtos.php?categoria_id=<?php echo $categoria['id']; ?>" class="btn btn-icon-action" title="Ver Produtos">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-icon-action edit-btn" data-id="<?php echo $categoria['id']; ?>" data-nome="<?php echo htmlspecialchars($categoria['nome']); ?>" data-bs-toggle="modal" data-bs-target="#editCategoryModal">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button class="btn btn-icon-action text-danger delete-btn" data-id="<?php echo $categoria['id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal">
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

<style>
    .btn-icon-action {
        width: 36px; height: 36px; border-radius: 12px; border: none; background: rgba(0,0,0,0.03);
        color: #64748b; display: inline-flex; align-items: center; justify-content: center;
        transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .btn-icon-action:hover { background: rgba(0,0,0,0.06); color: #0A2647; transform: translateY(-2px); }
</style>

<!-- Modal Adicionar Categoria -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="fw-bold text-navy mb-0">Nova Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label for="nome" class="metric-label d-block mb-2">Nome da Categoria</label>
                        <input type="text" class="form-control rounded-3 border-light py-2" id="nome" name="nome" required placeholder="Ex: Bebidas, Eletrônicos...">
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="add_categoria" class="btn btn-dark rounded-pill px-4 fw-bold">Criar Categoria</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Categoria -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="fw-bold text-navy mb-0">Editar Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="mb-3">
                        <label for="edit_nome" class="metric-label d-block mb-2">Nome da Categoria</label>
                        <input type="text" class="form-control rounded-3 border-light py-2" id="edit_nome" name="edit_nome" required>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="edit_categoria" class="btn btn-dark rounded-pill px-4 fw-bold">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Excluir Categoria -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 text-center">
            <form method="POST" action="">
                <div class="modal-body p-5">
                    <div class="mb-4 text-danger opacity-25">
                        <i class="fas fa-trash-alt fa-4x"></i>
                    </div>
                    <h4 class="fw-bold text-navy mb-3">Excluir Categoria?</h4>
                    <p class="text-secondary small mb-4">Os produtos associados a ela não serão excluídos, mas ficarão sem categoria no catálogo.</p>
                    <input type="hidden" id="delete_id" name="delete_id">
                    
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Manter</button>
                        <button type="submit" name="delete_categoria" class="btn btn-danger rounded-pill px-4 fw-bold">Sim, Excluir</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Passar dados para o modal de edição
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
        });
    });

    // Passar dados para o modal de exclusão
    const deleteBtns = document.querySelectorAll('.delete-btn');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('delete_id').value = id;
        });
    });
});
</script>

<?php include_once '../includes/rodape.php'; ?>
