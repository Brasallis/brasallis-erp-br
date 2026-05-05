<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Controllers\EmployeeController;

try {
    $db = resolve('db');
} catch (Exception $e) {
    die("Erro crítico: " . $e->getMessage());
}

$controller = new EmployeeController($db);
$controller->index();
