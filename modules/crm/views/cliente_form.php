<?php
// modules/crm/views/cliente_form.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Check Auth & Permission
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('crm', 'escrita')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cliente = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $cpf_cnpj = trim($_POST['cpf_cnpj']);
    $endereco = trim($_POST['endereco']);

    try {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE clientes SET nome=?, email=?, telefone=?, cpf_cnpj=?, endereco=? WHERE id=? AND empresa_id=?");
            $stmt->execute([$nome, $email, $telefone, $cpf_cnpj, $endereco, $id, $empresa_id]);
            header("Location: clientes.php?msg=updated");
            exit;
        } else {
            $stmt = $conn->prepare("INSERT INTO clientes (empresa_id, nome, email, telefone, cpf_cnpj, endereco) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$empresa_id, $nome, $email, $telefone, $cpf_cnpj, $endereco]);
            header("Location: clientes.php?msg=created");
            exit;
        }
    } catch (Exception $e) {
        $error = "Erro ao salvar: " . $e->getMessage();
    }
}

// Fetch Existing
if ($id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $empresa_id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) { die("Cliente não encontrado."); }
    } catch (Exception $e) { die("Erro de banco de dados."); }
}

require_once __DIR__ . '/../../../includes/cabecalho.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1"><i class="fas fa-user-<?= $id > 0 ? 'edit' : 'plus' ?> me-2 text-primary"></i><?= $id > 0 ? 'Editar Cliente' : 'Novo Cliente' ?></h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="../../../admin/painel_admin.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">CRM & Vendas</a></li>
                    <li class="breadcrumb-item"><a href="clientes.php">Clientes</a></li>
                    <li class="breadcrumb-item active"><?= $id > 0 ? 'Editar' : 'Novo' ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (isset($error)): ?>
         <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card exec-card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="metric-label d-block mb-2">Nome / Razão Social <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-3 border-light py-2" name="nome" value="<?= htmlspecialchars($cliente['nome'] ?? '') ?>" required placeholder="Ex: Nome do Cliente ou Empresa">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="metric-label d-block mb-2">E-mail</label>
                                <input type="email" class="form-control rounded-3 border-light py-2" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" placeholder="exemplo@email.com">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="metric-label d-block mb-2">Telefone / WhatsApp</label>
                                <input type="text" class="form-control rounded-3 border-light py-2" name="telefone" value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
                            </div>

                            <div class="col-md-6">
                                <label class="metric-label d-block mb-2">CPF / CNPJ</label>
                                <input type="text" class="form-control rounded-3 border-light py-2" name="cpf_cnpj" value="<?= htmlspecialchars($cliente['cpf_cnpj'] ?? '') ?>" placeholder="00.000.000/0000-00">
                            </div>

                            <div class="col-12">
                                <label class="metric-label d-block mb-2">Endereço Completo</label>
                                <input type="text" class="form-control rounded-3 border-light py-2" name="endereco" value="<?= htmlspecialchars($cliente['endereco'] ?? '') ?>" placeholder="Rua, Número, Bairro, Cidade - UF">
                            </div>
                        </div>

                        <div class="mt-5 pt-4 border-top d-flex flex-wrap justify-content-end gap-2">
                            <a href="clientes.php" class="btn btn-light rounded-pill px-4 py-2 flex-grow-1 flex-md-grow-0">Cancelar</a>
                            <button type="submit" class="btn btn-dark rounded-pill px-5 py-2 fw-bold flex-grow-1 flex-md-grow-0">
                                <?= $id > 0 ? 'Salvar Alterações' : 'Cadastrar Cliente' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-navy { color: #0A2647; }
    .form-control-lg { font-size: 1rem; padding: 0.75rem 1rem; border-radius: 8px; }
    .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(10, 38, 71, 0.1); }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
