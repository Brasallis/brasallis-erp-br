<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';

require_once __DIR__ . '/../includes/config.php';

// --- CONFIGURAÇÃO DA IA ---
$gemini_api_key = GEMINI_API_KEY;

// Defina o serviço padrão: 'gemini' (online) ou 'ollama' (offline)
$ia_service = 'gemini'; 

// Segurança: Apenas admins podem executar esta ação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$compra_id = $_GET['id'] ?? null;
$file_path = isset($_GET['path']) ? urldecode($_GET['path']) : null;

if (!$compra_id || !$file_path) {
    header("Location: compras.php");
    exit;
}

$conn = connect_db();

if (!$file_path || !file_exists('../' . $file_path)) {
    $_SESSION['message'] = "Erro: Arquivo da nota fiscal não encontrado para esta compra.";
    $_SESSION['message_type'] = "danger";
    header("Location: compras.php");
    exit;
}

// Marcar o status como pendente antes de iniciar
$conn->prepare("INSERT INTO dados_nota_fiscal (compra_id, status) VALUES (?, 'pendente') ON DUPLICATE KEY UPDATE status = 'pendente'")->execute([$compra_id]);

// Construir o comando para executar o script Python
$python_executable = 'py'; // Usa o launcher do Python para Windows.
$script_path = realpath(__DIR__ . '/../scripts/process_invoice.py');
$absolute_file_path = realpath(__DIR__ . '/../' . $file_path);

if (!$script_path || !$absolute_file_path) {
    $conn->prepare("UPDATE dados_nota_fiscal SET status = 'erro', texto_completo = 'Erro interno: caminho do script ou do arquivo invalido.' WHERE compra_id = ?")->execute([$compra_id]);
    $_SESSION['message'] = "Erro ao construir os caminhos para o processamento. Verifique as permissões.";
    $_SESSION['message_type'] = "danger";
    header("Location: compras.php");
    exit;
}

// Passar os argumentos de forma segura
$command = escapeshellarg($python_executable) . ' ' . 
           escapeshellarg($script_path) . ' ' . 
           escapeshellarg($compra_id) . ' ' . 
           escapeshellarg($absolute_file_path) . ' ' . 
           '--service ' . escapeshellarg($ia_service);

// Definir as variáveis de ambiente para o processo filho
$env = [
    'DB_HOST' => DB_HOST,
    'DB_USER' => DB_USER,
    'DB_PASS' => DB_PASS,
    'DB_NAME' => DB_NAME,
    'GEMINI_API_KEY' => $gemini_api_key,
    // Passar variáveis de sistema para garantir que o Python encontre seus pacotes
    'SystemRoot' => $_SERVER['SystemRoot'] ?? '',
    'PATH' => $_SERVER['PATH'] ?? ''
];

// Executar o comando em segundo plano para não travar a interface do usuário
$log_file = __DIR__ . '/../logs/python_errors.log';
$descriptorspec = [
   0 => ["pipe", "r"],  // stdin
   1 => ["pipe", "w"],  // stdout
   2 => ["file", $log_file, "a"] // stderr
];

$process = proc_open($command, $descriptorspec, $pipes, realpath(__DIR__ . '/../scripts/'), $env);

if (is_resource($process)) {
    // Não precisamos esperar, então fechamos os pipes
    fclose($pipes[0]);
    fclose($pipes[1]);
    // proc_close($process); // Não feche o processo, deixe-o rodar em segundo plano

    $_SESSION['message'] = "O processamento da nota fiscal foi iniciado em segundo plano. O resultado aparecerá em breve.";
    $_SESSION['message_type'] = "info";
} else {
    $_SESSION['message'] = "Erro ao iniciar o processo de análise da nota fiscal.";
    $_SESSION['message_type'] = "danger";
    $conn->prepare("UPDATE dados_nota_fiscal SET status = 'erro', texto_completo = 'Falha ao invocar o script Python.' WHERE compra_id = ?")->execute([$compra_id]);
}

header("Location: compras.php");
exit;
?>
