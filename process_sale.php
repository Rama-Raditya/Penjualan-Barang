<?php
require_once 'koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Validate input
$customer_id = isset($input['customer_id']) && !empty($input['customer_id']) ? (int)$input['customer_id'] : null;
$payment_method = isset($input['payment_method']) ? sanitize($input['payment_method']) : 'cash';
$items = isset($input['items']) ? $input['items'] : [];

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
    exit();
}

// Validate payment method
$allowed_methods = ['cash', 'transfer', 'card'];
if (!in_array($payment_method, $allowed_methods)) {
    echo json_encode(['success' => false, 'message' => 'Metode pembayaran tidak valid']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Calculate totals and validate stock
    $total_amount = 0;
    $total_items = 0;
    
    foreach ($items as $item) {
        // Validate item data
        if (!isset($item['id']) || !isset($item['qty']) || !isset($item['price'])) {
            throw new Exception('Data item tidak lengkap');
        }
        
        $product_id = (int)$item['id'];
        $qty = (int)$item['qty'];
        $price = (float)$item['price'];
        
        if ($qty <= 0) {
            throw new Exception('Kuantitas harus lebih dari 0');
        }
        
        // Check current stock
        $stmt = $pdo->prepare("SELECT stok, nama FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception('Produk tidak ditemukan');
        }
        
        if ($product['stok'] < $qty) {
            throw new Exception("Stok {$product['nama']} tidak mencukupi. Stok tersedia: {$product['stok']}");
        }
        
        $total_items += $qty;
        $total_amount += $qty * $price;
    }
    
    // Generate unique invoice number
    $invoice_no = generateInvoiceNo();
    $attempts = 0;
    while ($attempts < 10) {
        $stmt = $pdo->prepare("SELECT id FROM sales WHERE invoice_no = ?");
        $stmt->execute([$invoice_no]);
        if (!$stmt->fetch()) {
            break;
        }
        $invoice_no = generateInvoiceNo();
        $attempts++;
    }
    
    if ($attempts >= 10) {
        throw new Exception('Gagal generate invoice number');
    }
    
    // Insert sale record
    $stmt = $pdo->prepare("INSERT INTO sales (invoice_no, customer_id, total_amount, total_items, pembayaran_method) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$invoice_no, $customer_id, $total_amount, $total_items, $payment_method]);
    $sale_id = $pdo->lastInsertId();
    
    // Insert sale items and update stock
    foreach ($items as $item) {
        $product_id = (int)$item['id'];
        $qty = (int)$item['qty'];
        $price = (float)$item['price'];
        $subtotal = $qty * $price;
        
        // Insert sale item
        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$sale_id, $product_id, $qty, $price, $subtotal]);
        
        // Update product stock
        $stmt = $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
        $stmt->execute([$qty, $product_id]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil',
        'invoice_no' => $invoice_no,
        'sale_id' => $sale_id,
        'total_amount' => $total_amount,
        'total_items' => $total_items,
        'payment_method' => $payment_method,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollBack();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>