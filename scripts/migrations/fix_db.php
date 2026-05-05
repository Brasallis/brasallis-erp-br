<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/db_config.php';

echo "Attempting connection to " . DB_HOST . " with user " . DB_USER . "...\n";

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connection successful!\n";
    
    // Try to run the SQL
    $sql = file_get_contents(__DIR__ . '/sql/create_ai_agents_table.sql');
    $parts = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($parts as $part) {
        if (empty($part)) continue;
        try {
            $conn->exec($part);
            echo "Executed query: " . substr($part, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "Query failed: " . $e->getMessage() . "\n";
        }
    }
    echo "Database setup completed.\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
