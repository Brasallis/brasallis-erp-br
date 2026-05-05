<?php
// scripts/add_pdv_module.php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM modulos WHERE slug = 'pdv'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "Module 'pdv' already exists.";
    } else {
        // Insert
        $sql = "INSERT INTO modulos (nome, slug, icone, descricao) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'Frente de Caixa (PDV)',
            'pdv',
            'fas fa-cash-register',
            'Acesso ao terminal de vendas e processamento de caixa.'
        ]);
        echo "Module 'pdv' added successfully.";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
