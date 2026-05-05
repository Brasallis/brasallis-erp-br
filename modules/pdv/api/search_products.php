<?php
// modules/pdv/api/search_products.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../includes/db_config.php';

session_start();
if (!isset($_SESSION['empresa_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Search by Name or SKU
$sql = "SELECT id, name, sku, price, quantity 
        FROM produtos 
        WHERE empresa_id = ? 
        AND (name LIKE ? OR sku LIKE ?) 
        AND quantity > 0
        LIMIT 20";

$stmt = $conn->prepare($sql);
$term = "%$query%";
$stmt->execute([$_SESSION['empresa_id'], $term, $term]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
