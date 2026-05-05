<?php
require_once __DIR__ . '/db_config.php';

function get_db_connection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        // In a real application, you would log this error and show a generic message.
        die("Connection failed: " . $e->getMessage());
    }
}
?>
