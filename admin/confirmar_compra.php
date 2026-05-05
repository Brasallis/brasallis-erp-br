<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';
require_once '../vendor/autoload.php';

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$compra_id = $_GET['id'] ?? null;

if (!$compra_id) {
    header("Location: compras.php");
    exit;
}

// Buscar dados da nota fiscal extraídos pela IA
$stmt_nf = $conn->prepare("SELECT * FROM dados_nota_fiscal WHERE compra_id = ?");
$stmt_nf->execute([$compra_id]);
$nf_data = $stmt_nf->fetch(PDO::FETCH_ASSOC);

if (!$nf_data || $nf_data['status'] !== 'pendente_confirmacao') {
    $_SESSION['message'] = "Esta compra não está pendente de confirmação.";
    $_SESSION['message_type'] = "warning";
    header("Location: compras.php");
    exit;
}

$extracted_items = json_decode($nf_data['itens_json'], true);

use App\Repository\ProdutoRepository;
use App\Core\Database;

// Buscar produtos e categorias para os dropdowns
$produtoRepo = new ProdutoRepository(Database::getInstance(), $empresa_id);
$produtos_existentes = $produtoRepo->getAll('', 'all', 9999, 0);
$categorias_existentes = $produtoRepo->getCategories();


include_once '../includes/cabecalho.php';
?>

<h1 class="mb-4">Revisar e Confirmar Itens da Compra #<?php echo $compra_id; ?></h1>
<p class="text-muted">Revise os itens extraídos pela IA, associe-os aos seus produtos em estoque ou crie novos, e confirme as quantidades para adicioná-los ao sistema.</p>

<form action="processar_confirmacao_compra.php" method="POST">
    <input type="hidden" name="compra_id" value="<?php echo $compra_id; ?>">
    
    <div class="card shadow-sm">
        <div class="card-header">
            <h5>Itens Extraídos da Nota Fiscal</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Item da Nota</th>
                            <th>Produto no Sistema</th>
                            <th>Categoria</th>
                            <th style="width: 100px;">Qtd.</th>
                            <th style="width: 120px;">Custo Un.</th>
                            <th style="width: 100px;">Fiscal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($extracted_items as $index => $item): ?>
                            <tr>
                                <!-- Coluna 1: Item da Nota -->
                                <td>
                                    <p class="fw-bold mb-1"><?php echo htmlspecialchars($item['descricao']); ?></p>
                                    <small class="text-muted">
                                        Qtd: <?php echo htmlspecialchars($item['quantidade']); ?> | 
                                        Vl. Un: R$ <?php echo htmlspecialchars($item['valor_unitario']); ?>
                                    </small>
                                    <input type="hidden" name="items[<?php echo $index; ?>][descricao_nf]" value="<?php echo htmlspecialchars($item['descricao']); ?>">
                                </td>

                                <!-- Coluna 2: Produto no Sistema -->
                                <td>
                                    <select class="form-select product-select" name="items[<?php echo $index; ?>][produto_id]">
                                        <option value="new_product">-- CRIAR NOVO PRODUTO --</option>
                                        <?php
                                        $best_match_id = null;
                                        $highest_similarity = 0;
                                        foreach ($produtos_existentes as $produto) {
                                            similar_text(strtolower($item['descricao']), strtolower($produto['name']), $percent);
                                            if ($percent > $highest_similarity) {
                                                $highest_similarity = $percent;
                                                $best_match_id = $produto['id'];
                                            }
                                        }
                                        
                                        foreach ($produtos_existentes as $produto) {
                                            $selected = ($highest_similarity > 70 && $produto['id'] == $best_match_id) ? 'selected' : '';
                                            echo "<option value=\"{$produto['id']}\" {$selected}>" . htmlspecialchars($produto['name']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </td>

                                <!-- Coluna 3: Categoria (para novos produtos) -->
                                <td>
                                    <select class="form-select category-select" name="items[<?php echo $index; ?>][categoria_id]">
                                        <option value="">Nenhuma</option>
                                        <?php foreach ($categorias_existentes as $categoria): ?>
                                            <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <!-- Coluna 4: Quantidade -->
                                <td>
                                    <input type="number" class="form-control" name="items[<?php echo $index; ?>][quantidade]" value="<?php echo htmlspecialchars($item['quantidade']); ?>" step="any" required>
                                </td>

                                <!-- Coluna 5: Custo Unitário -->
                                    <input type="number" class="form-control" name="items[<?php echo $index; ?>][custo]" value="<?php echo htmlspecialchars($item['valor_unitario']); ?>" step="0.01" required>
                                </td>

                                <!-- Coluna 6: Fiscal (NCM/CST/CFOP) -->
                                <td>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text">NCM</span>
                                        <input type="text" class="form-control" name="items[<?php echo $index; ?>][ncm]" value="<?php echo htmlspecialchars($item['ncm'] ?? ''); ?>" placeholder="0000.00.00">
                                    </div>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text">CST</span>
                                        <input type="text" class="form-control" name="items[<?php echo $index; ?>][cst]" value="<?php echo htmlspecialchars($item['cst_csosn'] ?? ''); ?>" placeholder="000">
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">CFOP</span>
                                        <input type="text" class="form-control" name="items[<?php echo $index; ?>][cfop]" value="<?php echo htmlspecialchars($item['cfop'] ?? ''); ?>" placeholder="5102">
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-end mt-4">
        <a href="compras.php" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check-double me-2"></i> Confirmar e Adicionar ao Estoque</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.product-select').forEach(select => {
        const categorySelect = select.closest('tr').querySelector('.category-select');
        
        function toggleCategory() {
            categorySelect.disabled = select.value !== 'new_product';
        }
        
        toggleCategory(); // Seta o estado inicial
        select.addEventListener('change', toggleCategory);
    });
});
</script>

<?php include_once '../includes/rodape.php'; ?>
