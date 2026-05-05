<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';

if (!isset($_SESSION['user_id'])) {
    die("Acesso negado.");
}

if (!isset($_GET['id'])) {
    die("ID da compra não fornecido.");
}

$conn = connect_db();
$empresa_id = $_SESSION['empresa_id'];
$purchase_id = $_GET['id'];

// Buscar o caminho do arquivo no banco
$stmt = $conn->prepare("SELECT fiscal_note_path FROM compras WHERE id = ? AND empresa_id = ?");
$stmt->execute([$purchase_id, $empresa_id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase || empty($purchase['fiscal_note_path'])) {
    die("Nota fiscal não encontrada ou você não tem permissão para visualizá-la.");
}

$file_path = '../' . $purchase['fiscal_note_path'];

// Segurança: garantir que o caminho está dentro do diretório de uploads
$real_path = realpath($file_path);
$uploads_dir = realpath('../uploads');

if ($real_path === false || strpos($real_path, $uploads_dir) !== 0 || !file_exists($real_path)) {
    die("Arquivo não encontrado no servidor.");
}

// Determinar o Content-Type
$ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
$content_type = 'application/octet-stream';
switch ($ext) {
    case 'pdf': $content_type = 'application/pdf'; break;
    case 'jpg': 
    case 'jpeg': $content_type = 'image/jpeg'; break;
    case 'png': $content_type = 'image/png'; break;
}

// Enviar headers e o arquivo
header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . basename($real_path) . '"');
header('Content-Length: ' . filesize($real_path));
readfile($real_path);
exit;
