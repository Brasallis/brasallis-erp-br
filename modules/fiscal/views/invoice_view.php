<?php
// modules/fiscal/views/invoice_view.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Auth & Permissions
if (!isset($_SESSION['user_id'])) { header('Location: ../../../login.php'); exit; }
if (!check_permission('fiscal', 'leitura')) { die('Acesso negado'); }

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) die("ID inválido");

// Fetch Note
$stmt = $conn->prepare("SELECT * FROM fiscal_notas WHERE id = ? AND empresa_id = ?");
$stmt->execute([$id, $empresa_id]);
$nota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$nota) die("Nota não encontrada");

// Helper to format money
function fmt($val) { return number_format((float)$val, 2, ',', '.'); }

// Determine Issuer/Recipient based on Type
// For 'saida', Issuer = Search Current Company (placeholder), Recipient = DB Value
// For 'entrada', Issuer = DB Value, Recipient = Search Current Company
$my_company = "Minha Empresa LTDA (Demo)"; // Placeholder if we don't have config table
$my_cnpj = "00.000.000/0000-00"; 

if ($nota['tipo'] == 'saida') {
    $emitente_nome = $my_company;
    $emitente_doc = $my_cnpj;
    $dest_nome = $nota['emitente_destinatario'];
    $dest_doc = $nota['cpf_cnpj'];
} else {
    $emitente_nome = $nota['emitente_destinatario'];
    $emitente_doc = $nota['cpf_cnpj'];
    $dest_nome = $my_company;
    $dest_doc = $my_cnpj;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>DANFE - Nota Fiscal <?= $nota['numero'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #e9ecef; color: #000; font-family: 'Arial', sans-serif; font-size: 12px; }
        .danfe-container {
            background: #fff;
            max-width: 210mm; /* A4 width */
            margin: 20px auto;
            padding: 10mm;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .box { border: 1px solid #000; padding: 2px 5px; min-height: 25px; margin-bottom: -1px; margin-right: -1px; }
        .box-label { font-size: 9px; font-weight: bold; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .box-content { font-size: 12px; font-weight: bold; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; background: #eee; border: 1px solid #000; padding: 2px 5px; margin-bottom: -1px; margin-top: 10px; }
        
        /* Barcode emulation */
        .barcode {
            height: 40px;
            background: repeating-linear-gradient(
                90deg,
                #000 0px, #000 2px,
                transparent 2px, transparent 4px
            );
            width: 100%;
        }

        @media print {
            body { background: #fff; margin: 0; }
            .danfe-container { margin: 0; border: none; box-shadow: none; width: 100%; max-width: none; }
            .no-print { display: none !important; }
            @page { margin: 10mm; size: auto; }
        }
    </style>
</head>
<body>

    <div class="container py-3 no-print">
        <div class="d-flex justify-content-between">
            <a href="notas.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            <div>
                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimir</button>
            </div>
        </div>
    </div>

    <div class="danfe-container">
        
        <!-- HEADER -->
        <div class="row g-0">
            <div class="col-8">
                <div class="box d-flex align-items-center" style="height: 120px;">
                    <div class="me-3 ps-2">
                        <!-- Placeholder Logo -->
                         <i class="fas fa-cube fa-4x text-secondary"></i>
                    </div>
                    <div>
                        <h4 class="mb-1 fw-bold"><?= strtoupper($emitente_nome) ?></h4>
                        <div class="small">
                            Rua Exemplo, 123 - Centro<br>
                            São Paulo - SP<br>
                            Fone: (11) 9999-9999
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="box text-center" style="height: 120px;">
                    <h5 class="fw-bold mt-2">DANFE</h5>
                    <div class="small">Documento Auxiliar da<br>Nota Fiscal Eletrônica</div>
                    <div class="row mt-2">
                        <div class="col-4 text-start ps-3">
                            <span class="d-block small">0 - Entrada</span>
                            <span class="d-block small">1 - Saída</span>
                        </div>
                        <div class="col-8 text-start">
                            <div class="border border-dark text-center fw-bold fs-5 px-2" style="width: 40px; margin-left: -20px;">
                                <?= $nota['tipo'] == 'entrada' ? '0' : '1' ?>
                            </div>
                        </div>
                    </div>
                    <div class="fw-bold mt-1">Nº <?= $nota['numero'] ?></div>
                    <div class="small">SÉRIE <?= $nota['serie'] ?></div>
                </div>
            </div>
        </div>

        <div class="row g-0">
            <div class="col-7">
                <div class="box">
                    <span class="box-label">Chave de Acesso</span>
                    <div class="barcode mt-1 mb-1" style="opacity: 0.7;"></div>
                    <div class="text-center small letter-spacing-2"><?= implode(' ', str_split($nota['chave_acesso'] ?: '00000000000000000000000000000000000000000000', 4)) ?></div>
                </div>
            </div>
            <div class="col-5">
                <div class="box">
                    <span class="box-label">Consulta de autenticidade no portal nacional da NF-e</span>
                    <span class="d-block mt-2 text-center text-primary text-decoration-underline">www.nfe.fazenda.gov.br/portal</span>
                </div>
            </div>
        </div>

        <div class="row g-0">
            <div class="col-12">
                <div class="box">
                    <span class="box-label">Natureza da Operação</span>
                    <span class="box-content"><?= $nota['tipo'] == 'saida' ? 'VENDA DE MERCADORIA' : 'COMPRA DE MERCADORIA' ?></span>
                </div>
            </div>
        </div>
        
        <div class="row g-0 mb-2">
            <div class="col-4">
                <div class="box">
                    <span class="box-label">Inscrição Estadual</span>
                    <span class="box-content">ISENTO</span>
                </div>
            </div>
            <div class="col-4">
                <div class="box">
                    <span class="box-label">Insc. Estadual do Subst. Trib.</span>
                    <span class="box-content"></span>
                </div>
            </div>
            <div class="col-4">
                <div class="box">
                    <span class="box-label">CNPJ</span>
                    <span class="box-content"><?= $emitente_doc ?></span>
                </div>
            </div>
        </div>

        <!-- DESTINATÁRIO -->
        <div class="section-title">Destinatário / Remetente</div>
        <div class="row g-0">
            <div class="col-8">
                <div class="box">
                    <span class="box-label">Nome / Razão Social</span>
                    <span class="box-content"><?= strtoupper($dest_nome) ?></span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">CNPJ / CPF</span>
                    <span class="box-content"><?= $dest_doc ?></span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Data da Emissão</span>
                    <span class="box-content"><?= date('d/m/Y', strtotime($nota['data_emissao'])) ?></span>
                </div>
            </div>
        </div>
        <div class="row g-0 mb-2">
            <div class="col-6">
                <div class="box">
                    <span class="box-label">Endereço</span>
                    <span class="box-content">ENDEREÇO NÃO INFORMADO</span>
                </div>
            </div>
            <div class="col-3">
                <div class="box">
                    <span class="box-label">Bairro / Distrito</span>
                    <span class="box-content">-</span>
                </div>
            </div>
            <div class="col-3">
                <div class="box">
                    <span class="box-label">Data Saída/Entrada</span>
                    <span class="box-content"><?= date('d/m/Y', strtotime($nota['data_emissao'])) ?></span>
                </div>
            </div>
        </div>

        <!-- IMPOSTOS -->
        <div class="section-title">Cálculo do Imposto</div>
        <div class="row g-0">
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Base de Cálculo do ICMS</span>
                    <span class="box-content text-end">R$ <?= fmt($nota['icms_base'] ?? 0) ?></span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Valor do ICMS</span>
                    <span class="box-content text-end">R$ <?= fmt($nota['icms_valor'] ?? 0) ?></span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Base Cálc. ICMS S.T.</span>
                    <span class="box-content text-end">R$ 0,00</span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Valor do ICMS S.T.</span>
                    <span class="box-content text-end">R$ 0,00</span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Valor Total Produtos</span>
                    <span class="box-content text-end">R$ <?= fmt($nota['valor_total'] - ($nota['valor_impostos'] ?? 0)) // Aprox ?></span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Valor Total da Nota</span>
                    <span class="box-content text-end">R$ <?= fmt($nota['valor_total']) ?></span>
                </div>
            </div>
        </div>
        <div class="row g-0 mb-2">
            <div class="col-3">
                <div class="box">
                    <span class="box-label">Valor do Frete</span>
                    <span class="box-content text-end">R$ 0,00</span>
                </div>
            </div>
            <div class="col-3">
                <div class="box">
                    <span class="box-label">Valor do Seguro</span>
                    <span class="box-content text-end">R$ 0,00</span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Desconto</span>
                    <span class="box-content text-end">R$ 0,00</span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Valor do IPI</span>
                    <span class="box-content text-end">R$ <?= fmt($nota['ipi_valor'] ?? 0) ?></span>
                </div>
            </div>
            <div class="col-2">
                <div class="box">
                    <span class="box-label">Valor Aprox. Tributos</span>
                    <span class="box-content text-end">R$ <?= fmt($nota['valor_impostos']) ?></span>
                </div>
            </div>
        </div>

        <!-- TRANSPORTADOR -->
        <div class="section-title">Transportador / Volumes Transportados</div>
        <div class="row g-0 mb-2">
             <div class="col-12">
                <div class="box">
                    <span class="box-label">Razão Social</span>
                    <span class="box-content">FRETE POR CONTA DO EMITENTE</span>
                </div>
             </div>
        </div>

        <!-- DADOS DOS PRODUTOS / SERVIÇOS -->
        <div class="section-title">Dados do Produto / Serviço</div>
        <div class="table-responsive border border-dark mb-2">
            <table class="table table-sm table-borderless mb-0">
                <thead>
                    <tr class="border-bottom border-dark small fw-bold text-center" style="font-size: 9px;">
                        <th>CÓDIGO</th>
                        <th class="text-start">DESCRIÇÃO</th>
                        <th>NCM/SH</th>
                        <th>CST</th>
                        <th>CFOP</th>
                        <th>UNID.</th>
                        <th>QTD.</th>
                        <th>VLR. UNIT.</th>
                        <th>VLR. TOTAL</th>
                        <th>BC ICMS</th>
                        <th>VLR. ICMS</th>
                        <th>VLR. IPI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // MOCKUP PRODUCT: since we don't have items table, we show 1 line summarizing
                    ?>
                    <tr class="text-center" style="font-size: 10px;">
                        <td>001</td>
                        <td class="text-start fw-bold">MERCADORIAS / SERVIÇOS DIVERSOS REF N.F. <?= $nota['numero'] ?></td>
                        <td>0000.00.00</td>
                        <td>000</td>
                        <td>5102</td>
                        <td>UN</td>
                        <td>1</td>
                        <td><?= fmt($nota['valor_total']) ?></td>
                        <td><?= fmt($nota['valor_total']) ?></td>
                        <td><?= fmt($nota['icms_base'] ?? 0) ?></td>
                        <td><?= fmt($nota['icms_valor'] ?? 0) ?></td>
                        <td><?= fmt($nota['ipi_valor'] ?? 0) ?></td>
                    </tr>
                    <!-- Empty rows to fill space -->
                    <?php for($i=0; $i<5; $i++): ?>
                    <tr style="height: 25px;"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <!-- DADOS ADICIONAIS -->
        <div class="section-title">Dados Adicionais</div>
        <div class="row g-0">
            <div class="col-8">
                <div class="box" style="height: 100px;">
                    <span class="box-label">Informações Complementares</span>
                    <span class="box-content" style="white-space: pre-wrap; font-weight: normal;">Documento emitido por ME ou EPP optante pelo Simples Nacional. 
Não gera direito a crédito fiscal de IPI.</span>
                </div>
            </div>
            <div class="col-4">
                <div class="box" style="height: 100px;">
                    <span class="box-label">Reservado ao Fisco</span>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
