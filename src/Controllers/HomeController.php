<?php

namespace App\Controllers;

class HomeController {
    public function index() {
        // Renderiza a view da landing page
        require __DIR__ . '/../../views/landing.php';
    }
}
