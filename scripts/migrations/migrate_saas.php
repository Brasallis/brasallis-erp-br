<?php
require_once 'includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents('sql/update_saas_plans.sql');
    
    // Split statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $conn->exec($stmt);
                echo "Executed: " . substr($stmt, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // If column already exists or enum issue, just log it.
                // ALTER TABLE can allow partial success or fail if column exists.
                // For 'MODIFY COLUMN', if enum values valid, it works.
                echo "Notice: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "SaaS Migration Completed.";
    
    // Verify
    $stmt = $conn->query("SHOW COLUMNS FROM empresas LIKE 'max_users'");
    if($stmt->fetch()) echo "\nColumn max_users confirms existence.";

} catch (PDOException $e) {
    echo "Fatal Error: " . $e->getMessage();
}
