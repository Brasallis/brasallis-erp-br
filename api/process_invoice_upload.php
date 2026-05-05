<?php
if (session_status() === PHP_SESSION_NONE) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
}
require_once '../includes/funcoes.php';
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método inválido.']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Nenhum arquivo enviado ou erro no upload.']);
    exit;
}

$upload_dir = '../uploads/temp/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($file_ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
    echo json_encode(['error' => 'Formato inválido. Apenas PDF, JPG e PNG.']);
    exit;
}

$new_file_name = 'temp_invoice_' . uniqid() . '.' . $file_ext;
$dest_path = $upload_dir . $new_file_name;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest_path)) {
    echo json_encode(['error' => 'Falha ao salvar arquivo temporário.']);
    exit;
}

// Configurar execução do Python nativo com suporte cros-platform
$python_executable = (PHP_OS_FAMILY === 'Windows') ? 'py' : 'python3';
$gemini_api_key = GEMINI_API_KEY;
$script_path = realpath(__DIR__ . '/../scripts/process_invoice.py');
$absolute_file_path = realpath($dest_path);

// Argumentos: compra_id (dummy), file_path, --service, --preview
// No Windows, quando o caminho do executável ou dos argumentos tem espaços, o escapamento do escapeshellarg pode ser insuficiente para o cmd.exe.
// A solução mais robusta é passar 'cmd /c "..."' envolvendo todo o comando.

$cmd_string = '"' . $python_executable . '" "' . $script_path . '" 0 "' . $absolute_file_path . '" --service gemini --preview';

// Log debugging
error_log("Executando comando Python: " . $cmd_string);

$command = $cmd_string; 


$env = [
    'DB_HOST' => DB_HOST,
    'DB_USER' => DB_USER,
    'DB_PASS' => DB_PASS,
    'DB_NAME' => DB_NAME,
    'GEMINI_API_KEY' => $gemini_api_key,
    'PYTHONIOENCODING' => 'utf-8',
    'SystemRoot' => $_SERVER['SystemRoot'] ?? '',
    'PATH' => $_SERVER['PATH'] ?? ''
];

$descriptorspec = [
   0 => ["pipe", "r"],  // stdin
   1 => ["pipe", "w"],  // stdout
   2 => ["pipe", "w"]   // stderr
];

$process = proc_open($command, $descriptorspec, $pipes, realpath(__DIR__ . '/../scripts/'), $env);

if (is_resource($process)) {
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $return_value = proc_close($process);

    if ($return_value === 0) {
        // Isolar o conteúdo JSON usando Expressão Regular para evitar lixo de stdout (Warnings do Python/PIP)
        preg_match('/\{.*\}/s', $stdout, $matches);
        $clean_json = $matches[0] ?? $stdout;

        $json_data = json_decode($clean_json, true);
        if ($json_data) {
            file_put_contents('../logs/ai_success.log', $clean_json);
            echo json_encode([
                'success' => true,
                'data' => $json_data,
                'temp_file' => 'uploads/temp/' . $new_file_name // Retorna caminho relativo para uso posterior
            ]);
        } else {
            file_put_contents('../logs/ai_debug.log', "RAW STDOUT: " . $stdout . "\n\nCLEAN: " . $clean_json . "\n\nSTDERR: " . $stderr);
            echo json_encode(['error' => 'Falha ao decodificar resposta da IA.', 'raw_output' => $stdout, 'stderr' => $stderr]);
        }
    } else {
        echo json_encode(['error' => 'Erro na execução do script Python.', 'stderr' => $stderr]);
    }
} else {
    echo json_encode(['error' => 'Falha ao iniciar processo Python.']);
}
