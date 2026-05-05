<?php
// admin/imprimir_relatorio.php - RELATÓRIO EXECUTIVO BRASALLIS HUB
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Repository\DashboardRepository;
use App\Core\Database;

if (!isset($_SESSION['empresa_id'])) { exit('Acesso Negado'); }

$empresa_id = $_SESSION['empresa_id'];
$dashboardRepo = new DashboardRepository(Database::getInstance(), $empresa_id);

// BUSCA DIRETA DO NOME DA EMPRESA PARA GARANTIR PRECISÃO NO RELATÓRIO
$conn = $dashboardRepo->getConnection();
$stmt = $conn->prepare("SELECT razao_social FROM empresas WHERE id = ?");
$stmt->execute([$empresa_id]);
$empresa_db = $stmt->fetch();
$empresa_nome = !empty($empresa_db['razao_social']) ? $empresa_db['razao_social'] : ($_SESSION['empresa_nome'] ?? 'Minha Empresa');

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// BUSCA DE TODOS OS DADOS PARA O RELATÓRIO CONSOLIDADO
$financial = $dashboardRepo->getFinancialSummary($startDate, $endDate);
$top_products = $dashboardRepo->getTopSellingProducts(10, $startDate, $endDate);
$top_profit = $dashboardRepo->getTopProfitableProducts(10, $startDate, $endDate);
$sellers = $dashboardRepo->getSalesBySeller($startDate, $endDate);
$abc_data = $dashboardRepo->getProductABCAnalysis($startDate, $endDate);
$stock_stats = $dashboardRepo->getStockMovementStats($startDate, $endDate);
$insights = $dashboardRepo->getDashboardInsights();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório Executivo - <?php echo htmlspecialchars($empresa_nome); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #fff; color: #1a1a1a; font-size: 11pt; }
        .print-container { max-width: 900px; margin: 0 auto; padding: 40px; }
        
        .report-header { border-bottom: 2px solid #0A2647; padding-bottom: 20px; margin-bottom: 30px; }
        .company-logo { height: 40px; filter: grayscale(1); margin-bottom: 15px; }
        .report-title { font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #0A2647; margin: 0; }
        .report-period { font-size: 0.9rem; color: #666; font-weight: 600; }

        .section-title { 
            background: #f8fafc; 
            padding: 8px 15px; 
            border-radius: 6px; 
            font-weight: 700; 
            color: #0A2647; 
            border-left: 4px solid #0A2647;
            margin-bottom: 20px;
            margin-top: 40px;
            page-break-after: avoid;
        }

        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .kpi-box { border: 1px solid #e2e8f0; padding: 15px; border-radius: 10px; text-align: center; }
        .kpi-box .label { font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .kpi-box .value { font-size: 1.2rem; font-weight: 800; color: #0A2647; }

        .table-custom { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .table-custom th { background: #f1f5f9; color: #475569; font-weight: 700; padding: 10px; font-size: 0.75rem; text-transform: uppercase; }
        .table-custom td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .badge-abc { padding: 4px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; }
        .bg-a { background: #dcfce7; color: #166534; }
        .bg-b { background: #fef9c3; color: #854d0e; }
        .bg-c { background: #fee2e2; color: #991b1b; }

        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            .print-container { width: 100%; max-width: 100%; padding: 0; }
            .section-title { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="no-print bg-dark text-white p-3 text-center d-flex justify-content-between align-items-center">
    <div class="fw-bold">Visualização de Impressão do Relatório Executivo</div>
    <button class="btn btn-primary btn-sm px-4 fw-bold" onclick="window.print()">IMPRIMIR DOCUMENTO</button>
</div>

<div class="print-container">
    <!-- Header -->
    <header class="report-header d-flex justify-content-between align-items-end">
        <div>
            <div class="text-navy fw-bold small mb-1" style="letter-spacing: 2px; opacity: 0.7;">ANÁLISE DE PERFORMANCE EMPRESARIAL</div>
            <h1 class="report-title" style="color: #1a1a1a; font-size: 2.2rem; line-height: 1.1;"><?php echo strtoupper(htmlspecialchars($empresa_nome)); ?></h1>
            <div class="report-period mt-2">Período de Análise: <?php echo date('d/m/Y', strtotime($startDate)); ?> — <?php echo date('d/m/Y', strtotime($endDate)); ?></div>
        </div>
        <div class="text-end">
            <div class="small text-muted fw-bold">Brasallis Intelligence Hub</div>
            <div class="small text-muted">Relatório Gerado em <?php echo date('d/m/Y'); ?></div>
            <div class="small text-muted">Ref: <?php echo date('Ym'); ?>-<?php echo str_pad((string)(string)$empresa_id, 3, '0', STR_PAD_LEFT); ?></div>
        </div>
    </header>

    <!-- KPIs Principais -->
    <div class="kpi-grid">
        <div class="kpi-box">
            <div class="label">Receita Operacional</div>
            <div class="value">R$ <?php echo number_format($financial['revenue'], 2, ',', '.'); ?></div>
        </div>
        <div class="kpi-box">
            <div class="label">Investimento em Estoque</div>
            <div class="value">R$ <?php echo number_format($financial['cost'], 2, ',', '.'); ?></div>
        </div>
        <div class="kpi-box">
            <div class="label">Resultado Líquido</div>
            <div class="value">R$ <?php echo number_format($financial['profit'], 2, ',', '.'); ?></div>
        </div>
        <div class="kpi-box">
            <div class="label">Margem de Contribuição</div>
            <div class="value"><?php echo number_format($financial['margin'], 1, ',', '.'); ?>%</div>
        </div>
    </div>

    <!-- Seção Financeira -->
    <div class="section-title">DETALHAMENTO DE PERFORMANCE</div>
    <p class="text-muted small mb-4">Esta seção apresenta a decomposição dos resultados financeiros e a produtividade da força de vendas da <strong><?php echo htmlspecialchars($empresa_nome); ?></strong>.</p>
    
    <table class="table-custom">
        <thead>
            <tr>
                <th>Canal de Venda / Vendedor</th>
                <th class="text-center">Volume de Transações</th>
                <th class="text-end">Valor Consolidado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($sellers as $s): ?>
            <tr>
                <td><?php echo htmlspecialchars($s['username']); ?></td>
                <td class="text-center"><?php echo $s['total_sales_count']; ?></td>
                <td class="text-end fw-bold">R$ <?php echo number_format($s['total_revenue'], 2, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Seção Operacional -->
    <div class="section-title">ANÁLISE DE PRODUTOS E MIX DE VENDAS</div>
    <div class="row">
        <div class="col-6">
            <table class="table-custom">
                <thead>
                    <tr><th>Produto: Maior Giro</th><th class="text-end">Qtd</th></tr>
                </thead>
                <tbody>
                    <?php foreach(array_slice($top_products, 0, 5) as $p): ?>
                    <tr><td><?php echo htmlspecialchars($p['name']); ?></td><td class="text-end fw-bold"><?php echo $p['total_quantity_sold']; ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-6">
            <table class="table-custom">
                <thead>
                    <tr><th>Produto: Maior Lucratividade</th><th class="text-end">Lucro</th></tr>
                </thead>
                <tbody>
                    <?php foreach(array_slice($top_profit, 0, 5) as $p): ?>
                    <tr><td><?php echo htmlspecialchars($p['name']); ?></td><td class="text-end fw-bold">R$ <?php echo number_format($p['total_profit'], 2, ',', '.'); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Inteligência -->
    <div class="section-title">INTELIGÊNCIA ESTRATÉGICA (INSIGHTS)</div>
    <div class="row mb-5">
        <div class="col-4">
            <div class="kpi-box bg-light border-0">
                <div class="label">Mix Curva ABC</div>
                <div class="d-flex justify-content-around mt-2">
                    <span class="badge-abc bg-a">A: <?php echo $abc_data['A']; ?></span>
                    <span class="badge-abc bg-b">B: <?php echo $abc_data['B']; ?></span>
                    <span class="badge-abc bg-c">C: <?php echo $abc_data['C']; ?></span>
                </div>
            </div>
        </div>
        <div class="col-8">
            <div class="p-3 border rounded-4 bg-light small">
                <div class="fw-bold mb-2">Resumo de Insights Preditos:</div>
                <ul class="mb-0 ps-3">
                    <?php foreach(array_slice($insights, 0, 3) as $i): ?>
                    <li class="mb-2"><?php echo strip_tags($i['description']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Rodapé do Documento -->
    <footer class="mt-5 pt-5 border-top text-center text-muted small">
        Documento de análise estratégica gerado exclusivamente para <strong><?php echo htmlspecialchars($empresa_nome); ?></strong>. <br>
        Os dados apresentados são baseados na inteligência de dados do <strong>Brasallis Hub</strong>. <br>
        © <?php echo date('Y'); ?> Gestão Inteligente.
    </footer>
</div>

<script>
    // Iniciar impressão automaticamente se necessário ou apenas aguardar o botão
</script>

</body>
</html>
