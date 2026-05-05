<?php
// modules/financeiro/views/movimentacoes.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';
require_once __DIR__ . '/../../../includes/cabecalho.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('financeiro', 'leitura')) { header('Location: ../../../admin/painel_admin.php?error=acesso_negado'); exit; }

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$search = $_GET['search'] ?? '';

// Filtros básicos
$where = "WHERE m.empresa_id = :empresa_id";
$params = [':empresa_id' => $empresa_id];

if ($search) {
    $where .= " AND (m.descricao LIKE :search OR c.nome LIKE :search)";
    $params[':search'] = "%$search%";
}

$query = "
    SELECT m.*, c.nome as categoria_nome, c.cor_hex as categoria_cor 
    FROM fin_movimentacoes m
    LEFT JOIN fin_categorias c ON m.categoria_id = c.id
    $where
    ORDER BY m.data_vencimento DESC
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy mb-1">Movimentações Financeiras</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="index.php">Financeiro</a></li>
                    <li class="breadcrumb-item active">Extrato</li>
                </ol>
            </nav>
        </div>
        <?php if (check_permission('financeiro', 'escrita')): ?>
        <a href="movimentacao_form.php" class="btn btn-trust-primary"><i class="fas fa-plus me-2"></i>Nova Movimentação</a>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Descrição</th>
                            <th>Categoria</th>
                            <th>Vencimento</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimentacoes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-file-invoice-dollar fa-3x mb-3 opacity-25 d-block"></i>
                                Nenhuma movimentação encontrada.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($movimentacoes as $mov): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($mov['descricao']) ?></td>
                                <td>
                                    <span class="badge rounded-pill" style="background-color: <?= $mov['categoria_cor'] ?>20; color: <?= $mov['categoria_cor'] ?>;">
                                        <?= htmlspecialchars($mov['categoria_nome']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($mov['data_vencimento'])) ?></td>
                                <td class="fw-bold <?= $mov['tipo'] == 'receita' ? 'text-success' : 'text-danger' ?>">
                                    <?= $mov['tipo'] == 'receita' ? '+' : '-' ?> R$ <?= number_format($mov['valor'], 2, ',', '.') ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pendente' => 'bg-warning text-dark',
                                        'pago' => 'bg-success text-white',
                                        'atrasado' => 'bg-danger text-white',
                                        'cancelado' => 'bg-secondary text-white'
                                    ];
                                    ?>
                                    <span class="badge <?= $statusClass[$mov['status']] ?? 'bg-light text-dark' ?>">
                                        <?= ucfirst($mov['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (check_permission('financeiro', 'escrita')): ?>
                                    <a href="movimentacao_form.php?id=<?= $mov['id'] ?>" class="btn btn-sm btn-light border"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .text-navy { color: #0A2647; }
    .btn-trust-primary { background-color: #0A2647; color: white; border: none; }
    .btn-trust-primary:hover { background-color: #0d325e; color: white; }
</style>

<?php require_once __DIR__ . '/../../../includes/rodape.php'; ?>
