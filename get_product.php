<?php
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Product ID required']);
    exit();
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT id, sku, nama, harga_jual, stok FROM products WHERE id = ? AND stok > 0");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['error' => 'Produk tidak ditemukan atau stok habis']);
    } else {
        echo json_encode($product);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>