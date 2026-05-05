<?php

require_once __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;

try {
    $db = resolve('db');
} catch (Exception $e) {
    die("Erro crítico: " . $e->getMessage());
}

$auth = new AuthController($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->store($_POST);
} else {
    header('Location: register.php');
}
