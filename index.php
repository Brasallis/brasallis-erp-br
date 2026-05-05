<?php

// Bootstrap da aplicação (autoloader, configurações, container)
require_once __DIR__ . '/bootstrap.php';

use App\Controllers\HomeController;

// Instancia o controller da Home
$controller = new HomeController();

// Executa a ação index (renderiza a landing page)
$controller->index();
