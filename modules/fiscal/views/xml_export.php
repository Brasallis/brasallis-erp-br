<?php
// modules/fiscal/views/xml_export.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

// Auth checks
if (!isset($_SESSION['user_id']) || !check_permission('fiscal', 'leitura')) {
    die("Acesso negado");
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$id) die("ID Inválido");

$stmt = $conn->prepare("SELECT * FROM fiscal_notas WHERE id = ? AND empresa_id = ?");
$stmt->execute([$id, $empresa_id]);
$nota = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$nota) die("Nota não encontrada");

// Create XML
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><nfeProc version="4.00" xmlns="http://www.portalfiscal.inf.br/nfe"></nfeProc>');

$nfe = $xml->addChild('NFe');
$infNFe = $nfe->addChild('infNFe');
$infNFe->addAttribute('Id', 'NFe' . ($nota['chave_acesso'] ?: '00000000000000000000000000000000000000000000'));
$infNFe->addAttribute('versao', '4.00');

// IDE
$ide = $infNFe->addChild('ide');
$ide->addChild('cUF', '35'); // SP placeholder
$ide->addChild('cNF', $nota['numero']);
$ide->addChild('natOp', $nota['tipo'] == 'saida' ? 'VENDA' : 'COMPRA');
$ide->addChild('mod', '55');
$ide->addChild('serie', $nota['serie']);
$ide->addChild('nNF', $nota['numero']);
$ide->addChild('dhEmi', $nota['data_emissao'] . 'T12:00:00-03:00');
$ide->addChild('tpNF', $nota['tipo'] == 'entrada' ? '0' : '1');

// EMIT
$emit = $infNFe->addChild('emit');
if($nota['tipo'] == 'saida') {
    $emit->addChild('CNPJ', '00000000000000'); // Fake company CNPJ
    $emit->addChild('xNome', $_SESSION['empresa_nome'] ?? 'Brasallis Hub');
} else {
    $emit->addChild('CNPJ', preg_replace('/\D/', '', $nota['cpf_cnpj']));
    $emit->addChild('xNome', $nota['emitente_destinatario']);
}

// DEST
$dest = $infNFe->addChild('dest');
if($nota['tipo'] == 'saida') {
    $dest->addChild('CNPJ', preg_replace('/\D/', '', $nota['cpf_cnpj']));
    $dest->addChild('xNome', $nota['emitente_destinatario']);
} else {
    $dest->addChild('CNPJ', '00000000000000');
    $dest->addChild('xNome', $_SESSION['empresa_nome'] ?? 'Brasallis Hub');
}

// TOTAL
$total = $infNFe->addChild('total');
$ICMSTot = $total->addChild('ICMSTot');
$ICMSTot->addChild('vBC', number_format($nota['icms_base'] ?? 0, 2, '.', ''));
$ICMSTot->addChild('vICMS', number_format($nota['icms_valor'] ?? 0, 2, '.', ''));
$ICMSTot->addChild('vIPI', number_format($nota['ipi_valor'] ?? 0, 2, '.', ''));
$ICMSTot->addChild('vPIS', number_format($nota['pis_valor'] ?? 0, 2, '.', ''));
$ICMSTot->addChild('vCOFINS', number_format($nota['cofins_valor'] ?? 0, 2, '.', ''));
$ICMSTot->addChild('vNF', number_format($nota['valor_total'], 2, '.', ''));
$ICMSTot->addChild('vTotTrib', number_format($nota['valor_impostos'], 2, '.', ''));

// DET (Product Mockup)
$det = $infNFe->addChild('det');
$det->addAttribute('nItem', '1');
$prod = $det->addChild('prod');
$prod->addChild('cProd', '001');
$prod->addChild('xProd', 'SERVICOS / MERCADORIAS DIVERSAS');
$prod->addChild('NCM', '00000000');
$prod->addChild('CFOP', '5102');
$prod->addChild('uCom', 'UN');
$prod->addChild('qCom', '1.0000');
$prod->addChild('vUnCom', number_format($nota['valor_total'], 2, '.', ''));
$prod->addChild('vProd', number_format($nota['valor_total'], 2, '.', ''));

// Send Download Headers
header('Content-Description: File Transfer');
header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="nf-' . $nota['numero'] . '.xml"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

echo $xml->asXML();
exit;
