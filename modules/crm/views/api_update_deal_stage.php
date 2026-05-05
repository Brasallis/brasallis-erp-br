<?php
// modules/crm/views/api_update_deal_stage.php
session_start();
require_once __DIR__ . '/../../../includes/funcoes.php';

if (!isset($_SESSION['user_id']) || !check_permission('crm', 'escrita')) exit('Acesso negado');

$conn = connect_db();
$id = (int)($_POST['id'] ?? 0);
$stage_id = (int)($_POST['stage_id'] ?? 0);
$empresa_id = $_SESSION['empresa_id'];

if ($id && $stage_id) {
    $stmt = $conn->prepare("UPDATE crm_oportunidades SET etapa_id = ? WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$stage_id, $id, $empresa_id]);
}
?>
