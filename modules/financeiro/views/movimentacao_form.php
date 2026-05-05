<?php
// modules/financeiro/views/movimentacao_form.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// 1. Auth & Permissions
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('financeiro', 'escrita')) { header('Location: index.php?error=acesso_negado'); exit; }

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$movimentacao = null;
$error = '';

// 2. Fetch Categories for Dropdown
$stmt = $conn->prepare("SELECT * FROM fin_categorias WHERE empresa_id = ? ORDER BY nome ASC");
$stmt->execute([$empresa_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = sanitize_input($_POST['descricao']);
    $valor = str_replace(',', '.', str_replace('.', '', $_POST['valor'])); // Format BRL to Decimal
    $tipo = sanitize_input($_POST['tipo']);
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $data_vencimento = sanitize_input($_POST['data_vencimento']);
    $data_pagamento = !empty($_POST['data_pagamento']) ? sanitize_input($_POST['data_pagamento']) : null;
    $status = sanitize_input($_POST['status']);
    $obs = sanitize_input($_POST['obs']);

    if ($id) {
        $stmt = $conn->prepare("UPDATE fin_movimentacoes SET descricao=?, valor=?, tipo=?, categoria_id=?, data_vencimento=?, data_pagamento=?, status=?, obs=? WHERE id=? AND empresa_id=?");
        $stmt->execute([$descricao, $valor, $tipo, $categoria_id, $data_vencimento, $data_pagamento, $status, $obs, $id, $empresa_id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO fin_movimentacoes (empresa_id, descricao, valor, tipo, categoria_id, data_vencimento, data_pagamento, status, obs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$empresa_id, $descricao, $valor, $tipo, $categoria_id, $data_vencimento, $data_pagamento, $status, $obs]);
    }
    
    header('Location: movimentacoes.php?msg=saved');
    exit;
}

// 4. Fetch Record if Edit
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM fin_movimentacoes WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$id, $empresa_id]);
    $movimentacao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$movimentacao) {
        require_once __DIR__ . '/../../../includes/cabecalho.php';
        echo "<div class='container py-5'><div class='alert alert-danger'>Registro não encontrado. <a href='movimentacoes.php'>Voltar</a></div></div>";
        require_once __DIR__ . '/../../../includes/rodape.php';
        exit;
    }
}

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><?= $id ? 'Editar Movimentação' : 'Nova Movimentação' ?></h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="index.php">Financeiro</a></li>
                    <li class="breadcrumb-item"><a href="movimentacoes.php">Extrato</a></li>
                    <li class="breadcrumb-item active"><?= $id ? 'Editar' : 'Novo' ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold small text-secondary">Descrição</label>
                        <input type="text" name="descricao" class="form-control" required value="<?= $movimentacao['descricao'] ?? '' ?>" placeholder="Ex: Pagamento de Fornecedor X">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-secondary">Tipo</label>
                        <select name="tipo" class="form-select" id="tipoSelect" required>
                            <option value="receita" <?= ($movimentacao['tipo'] ?? '') === 'receita' ? 'selected' : '' ?>>Receita (Entrada)</option>
                            <option value="despesa" <?= ($movimentacao['tipo'] ?? '') === 'despesa' ? 'selected' : '' ?>>Despesa (Saída)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-secondary">Valor (R$)</label>
                        <input type="text" name="valor" class="form-control" required value="<?= $movimentacao ? number_format($movimentacao['valor'], 2, ',', '.') : '' ?>" placeholder="0,00" id="valorInput">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-secondary">Categoria</label>
                        <select name="categoria_id" class="form-select">
                            <option value="">Selecione...</option>
                            <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-tipo="<?= $cat['tipo'] ?>" <?= ($movimentacao['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= $cat['nome'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-secondary">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="pendente" <?= ($movimentacao['status'] ?? '') === 'pendente' ? 'selected' : '' ?>>Pendente / A Receber</option>
                            <option value="pago" <?= ($movimentacao['status'] ?? '') === 'pago' ? 'selected' : '' ?>>Pago / Recebido</option>
                            <option value="atrasado" <?= ($movimentacao['status'] ?? '') === 'atrasado' ? 'selected' : '' ?>>Atrasado</option>
                            <option value="cancelado" <?= ($movimentacao['status'] ?? '') === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-secondary">Data de Vencimento</label>
                        <input type="date" name="data_vencimento" class="form-control" required value="<?= $movimentacao['data_vencimento'] ?? date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-secondary">Data de Pagamento (Opcional)</label>
                        <input type="date" name="data_pagamento" class="form-control" value="<?= $movimentacao['data_pagamento'] ?? '' ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small text-secondary">Observações</label>
                        <textarea name="obs" class="form-control" rows="2"><?= $movimentacao['obs'] ?? '' ?></textarea>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="movimentacoes.php" class="btn btn-light text-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-trust-primary px-4">Salvar Movimentação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Simple mask for currency
    const valorInput = document.getElementById('valorInput');
    valorInput.addEventListener('keyup', function(e) {
        let v = e.target.value.replace(/\D/g, '');
        v = (v/100).toFixed(2) + '';
        v = v.replace(".", ",");
        v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
        v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
        e.target.value = v;
    });

    // Dynamic Category Filtering (Optional, basic implementation)
    const tipoSelect = document.getElementById('tipoSelect');
    const categoriaSelect = document.querySelector('select[name="categoria_id"]');
    const options = Array.from(categoriaSelect.options);

    function filterCategories() {
        const tipo = tipoSelect.value;
        options.forEach(opt => {
            if (opt.value === "") return;
            const catTipo = opt.getAttribute('data-tipo');
            if (catTipo && catTipo !== tipo) {
                opt.style.display = 'none';
            } else {
                opt.style.display = 'block';
            }
        });
        // Reset selection if hidden
        const selected = categoriaSelect.options[categoriaSelect.selectedIndex];
        if (selected.style.display === 'none') {
            categoriaSelect.value = "";
        }
    }

    tipoSelect.addEventListener('change', filterCategories);
    // Init
    filterCategories();
</script>

<style>
    .text-navy { color: #0A2647; }
    .btn-trust-primary { background-color: #0A2647; color: white; border: none; }
    .btn-trust-primary:hover { background-color: #0d325e; color: white; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
