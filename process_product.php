<?php
require_once 'koneksi.php';
checkAdminLogin();

// Create upload directory if not exists
$upload_dir = 'uploads/products/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit();
}

$action = sanitize($_POST['action']);
$errors = [];

// Common validation
$sku = sanitize($_POST['sku']);
$nama = sanitize($_POST['nama']);
$category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
$deskripsi = sanitize($_POST['deskripsi']);
$harga_jual = (float)$_POST['harga_jual'];
$harga_beli = !empty($_POST['harga_beli']) ? (float)$_POST['harga_beli'] : null;
$stok = (int)$_POST['stok'];

// Validate required fields
if (empty($sku)) $errors[] = 'SKU harus diisi!';
if (empty($nama)) $errors[] = 'Nama produk harus diisi!';
if ($harga_jual <= 0) $errors[] = 'Harga jual harus lebih dari 0!';
if ($stok < 0) $errors[] = 'Stok tidak boleh negatif!';

// Handle file upload
$gambar_path = null;
$upload_success = true;

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['gambar'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = 'Format file tidak didukung! Gunakan JPG atau PNG.';
        $upload_success = false;
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        $errors[] = 'Ukuran file terlalu besar! Maksimal 2MB.';
        $upload_success = false;
    }
    
    if ($upload_success && empty($errors)) {
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $extension;
        $target_path = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $gambar_path = $filename;
        } else {
            $errors[] = 'Gagal mengupload gambar!';
        }
    }
}

// Process based on action
try {
    if ($action == 'add') {
        // Check SKU uniqueness for add
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        if ($stmt->fetch()) {
            $errors[] = 'SKU sudah digunakan! Gunakan SKU yang lain.';
        }
        
        if (empty($errors)) {
            $sql = "INSERT INTO products (sku, nama, category_id, deskripsi, harga_jual, harga_beli, stok, gambar_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $nama, $category_id, $deskripsi, $harga_jual, $harga_beli, $stok, $gambar_path]);
            
            $_SESSION['message'] = 'Produk berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
            header('Location: products.php');
            exit();
        }
        
    } elseif ($action == 'edit') {
        $id = (int)$_POST['id'];
        
        // Check SKU uniqueness for edit (exclude current product)
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $stmt->execute([$sku, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'SKU sudah digunakan! Gunakan SKU yang lain.';
        }
        
        if (empty($errors)) {
            // Get current product data
            $stmt = $pdo->prepare("SELECT gambar_path FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $current_product = $stmt->fetch();
            
            if ($gambar_path) {
                // Delete old image if new one uploaded
                if ($current_product['gambar_path'] && file_exists($upload_dir . $current_product['gambar_path'])) {
                    unlink($upload_dir . $current_product['gambar_path']);
                }
            } else {
                // Keep current image if no new image uploaded
                $gambar_path = $current_product['gambar_path'];
            }
            
            $sql = "UPDATE products SET sku=?, nama=?, category_id=?, deskripsi=?, harga_jual=?, harga_beli=?, stok=?, gambar_path=? 
                    WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $nama, $category_id, $deskripsi, $harga_jual, $harga_beli, $stok, $gambar_path, $id]);
            
            $_SESSION['message'] = 'Produk berhasil diupdate!';
            $_SESSION['message_type'] = 'success';
            header('Location: products.php');
            exit();
        }
    }
    
} catch (PDOException $e) {
    $errors[] = 'Error database: ' . $e->getMessage();
}

// If there are errors, go back to form
if (!empty($errors)) {
    $_SESSION['message'] = implode('<br>', $errors);
    $_SESSION['message_type'] = 'danger';
    
    // Clean up uploaded file if there were other errors
    if ($gambar_path && file_exists($upload_dir . $gambar_path)) {
        unlink($upload_dir . $gambar_path);
    }
    
    if ($action == 'edit') {
        header('Location: product_form.php?id=' . $id);
    } else {
        header('Location: product_form.php');
    }
    exit();
}
?>