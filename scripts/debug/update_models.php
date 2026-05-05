<?php
require_once 'includes/db_config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Upgrading agents to Gemini 2.5...\n";
    
    // Upgrade 1.5-flash -> 2.5-flash
    $sql1 = "UPDATE ai_agents SET model = 'gemini-2.5-flash' WHERE model LIKE 'gemini-1.5%' OR model LIKE 'gemini-flash%'";
    $stmt1 = $conn->exec($sql1);
    
    // Always default to 2.5-flash for safety if unknown
    $sql2 = "UPDATE ai_agents SET model = 'gemini-2.5-flash' WHERE model NOT LIKE 'gemini-2.5%'";
    $stmt2 = $conn->exec($sql2);
    
    echo "Updated to Gemini 2.5 Flash.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
