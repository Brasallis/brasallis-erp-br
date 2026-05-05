<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';
require_once '../vendor/autoload.php';

use App\Repository\ProdutoRepository;
use App\Core\Database;

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: compras.php");
    exit;
}

$compra_id = $_POST['compra_id'] ?? null;
$items = $_POST['items'] ?? [];

if (!$compra_id || empty($items)) {
    $_SESSION['message'] = "Dados inválidos para processar a compra.";
    $_SESSION['message_type'] = "danger";
    header("Location: compras.php");
    exit;
}

$conn->beginTransaction();
try {
    $produtoRepo = new ProdutoRepository(Database::getInstance(), $empresa_id);
    $total_compra = 0;

    foreach ($items as $item) {
        $produto_id = $item['produto_id'];
        $quantidade = $item['quantidade'];
        $custo = $item['custo'];
        $total_compra += ($quantidade * $custo);

        if ($produto_id === 'new_product') {
            // --- Criar novo produto ---
            $data = [
                'name' => $item['descricao_nf'],
                'sku' => 'AUTOGERADO-' . uniqid(),
                'description' => 'Produto cadastrado via extração de nota fiscal.',
                'cost_price' => $custo,
                'price' => $custo * 1.5, // Sugestão de preço de venda (margem de 50%)
                'quantity' => $quantidade,
                'minimum_stock' => 1,
                'categoria_id' => $item['categoria_id'] ?: null,
                'unidade_medida' => 'un',
                'lote' => null,
                'validade' => null,
                'observacoes' => 'Cadastro automático.'
            ];
            $produtoRepo->add($data);
            $produto_id = $conn->lastInsertId();
        } else {
            // --- Atualizar estoque do produto existente ---
            $stmt_update = $conn->prepare("UPDATE produtos SET quantity = quantity + ?, cost_price = ? WHERE id = ? AND empresa_id = ?");
            $stmt_update->execute([$quantidade, $custo, $produto_id, $empresa_id]);
        }

        // --- Registrar no histórico de estoque ---
        $stmt_hist = $conn->prepare("INSERT INTO historico_estoque (product_id, user_id, empresa_id, action, quantity, details) VALUES (?, ?, ?, 'entrada', ?, ?)");
        $details = "Entrada de estoque via confirmação da Compra #" . $compra_id;
        $stmt_hist->execute([$produto_id, $user_id, $empresa_id, $quantidade, $details]);
        
        // --- Registrar item na compra ---
        $stmt_item = $conn->prepare("INSERT INTO itens_compra (purchase_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $stmt_item->execute([$compra_id, $produto_id, $quantidade, $custo]);

        // --- INTELIGÊNCIA TRIBUTÁRIA (FASE 2) ---
        // Analisa o item e salva os insights
        try {
            // Instantiate service manually (simple DI)
            require_once __DIR__ . '/../src/Services/TaxIntelligenceService.php';
            $taxService = new \App\Services\TaxIntelligenceService($conn);

            $ncm = $item['ncm'] ?? '';
            $cfop = $item['cfop'] ?? '';
            $valorTotalItem = $quantidade * $custo;

            $analise = $taxService->analyzeItem($ncm, $cfop, $valorTotalItem);
            
            // Dados para salvar
            $itemData = [
                'name' => $item['descricao_nf'] ?? 'Item',
                'ncm' => $ncm,
                'cfop' => $cfop,
                'cst' => $item['cst'] ?? ''
            ];

            $taxService->saveAnalysis($compra_id, $produto_id, $itemData, $analise);

        } catch (Exception $e) {
            // Não deve bloquear a compra se a análise falhar
            error_log("Erro na Inteligência Tributária: " . $e->getMessage());
        }
    }

    // --- Atualizar o total da compra e o status da nota fiscal ---
    $conn->prepare("UPDATE compras SET total_amount = ? WHERE id = ?")->execute([$total_compra, $compra_id]);
    $conn->prepare("UPDATE dados_nota_fiscal SET status = 'processado' WHERE compra_id = ?")->execute([$compra_id]);

    $conn->commit();

    $_SESSION['message'] = "Compra #" . $compra_id . " confirmada e estoque atualizado com sucesso!";
    $_SESSION['message_type'] = "success";

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['message'] = "Erro ao processar a confirmação da compra: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    error_log("Erro em processar_confirmacao_compra.php: " . $e->getMessage());
}

header("Location: compras.php");
exit;
