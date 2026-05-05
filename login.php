<?php

// Bootstrap da aplicação
require_once __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;

// Resolve a conexão com o banco de dados via Container
try {
    $db = resolve('db');
} catch (Exception $e) {
    die("Erro crítico: " . $e->getMessage());
}

// Instancia o Controller de Autenticação
$auth = new AuthController($db);

// Roteamento simples para Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->authenticate($_POST);
} else {
    $auth->login();
}
