<?php
// scripts/create_role_permissions.php
require_once __DIR__ . '/../includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create permissoes_cargo table
    $sql = "CREATE TABLE IF NOT EXISTS permissoes_cargo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cargo_id INT NOT NULL,
        modulo_id INT NOT NULL,
        nivel_acesso ENUM('leitura', 'escrita', 'admin') DEFAULT 'leitura',
        FOREIGN KEY (cargo_id) REFERENCES cargos(id) ON DELETE CASCADE,
        FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE,
        UNIQUE KEY unique_perm (cargo_id, modulo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->exec($sql);
    echo "Table 'permissoes_cargo' created successfully.\n";

    // Optional: Migrate existing sector permissions to existing roles?
    // For now, let's start fresh or user will configure it.
    // Actually, it's better to leave them empty so user can set them up "Google Style".

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
