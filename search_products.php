<?php
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([]);
    exit();
}

$query = sanitize($_GET['q']);

try {
    $sql = "SELECT id, sku, nama, harga_jual, stok 
            FROM products 
            WHERE (nama LIKE ? OR sku LIKE ?) AND stok > 0 
            ORDER BY nama 
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$query%", "%$query%"]);
    $products = $stmt->fetchAll();
    
    echo json_encode($products);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>