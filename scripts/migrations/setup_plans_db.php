<?php
require_once 'includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents('sql/add_ai_plans.sql');
    
    // Split for safety if needed, but simple add column is usually fine in one go or separate execs
    // PDO exec handles multiple statements if previously configured, but let's be safe and split by ;
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $conn->exec($stmt);
            echo "Executed: " . substr($stmt, 0, 50) . "...\n";
        }
    }
    
    // Set admin company to PRO for testing if needed
    // $conn->exec("UPDATE empresas SET ai_plan = 'pro', ai_token_limit = 99999999 WHERE id = 1"); 

    echo "Database updated successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
