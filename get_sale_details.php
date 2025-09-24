<?php
require_once 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Sale ID required']);
    exit();
}

$sale_id = (int)$_GET['id'];

try {
    // Get sale info
    $stmt = $pdo->prepare("
        SELECT s.*, c.nama as customer_nama 
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        echo json_encode(['error' => 'Transaksi tidak ditemukan']);
        exit();
    }
    
    // Get sale items
    $stmt = $pdo->prepare("
        SELECT si.*, p.nama as product_nama, p.sku as product_sku
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
        ORDER BY si.id
    ");
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll();
    
    echo json_encode([
        'sale' => $sale,
        'items' => $items
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>