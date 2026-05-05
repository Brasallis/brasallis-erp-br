<?php
// modules/crm/views/api_save_deal.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

if (!isset($_SESSION['user_id']) || !check_permission('crm', 'escrita')) {
    header('Location: kanban.php?error=denied');
    exit;
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];

$titulo = sanitize_input($_POST['titulo']);
$cliente_id = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
$valor = !empty($_POST['valor']) ? (float)$_POST['valor'] : 0;

// Default stage = 1st one
$stmtEtapa = $conn->prepare("SELECT id FROM crm_etapas WHERE empresa_id = ? ORDER BY ordem ASC LIMIT 1");
$stmtEtapa->execute([$empresa_id]);
$etapa_id = $stmtEtapa->fetchColumn();

if ($titulo && $etapa_id) {
    $stmt = $conn->prepare("INSERT INTO crm_oportunidades (empresa_id, titulo, cliente_id, valor_estimado, etapa_id, responsavel_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$empresa_id, $titulo, $cliente_id, $valor, $etapa_id, $_SESSION['user_id']]);
}

header('Location: kanban.php');
exit;
?>
