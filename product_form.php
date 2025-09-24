<?php
require_once 'koneksi.php';
checkAdminLogin();

$edit_mode = isset($_GET['id']) && !empty($_GET['id']);
$product = null;

if ($edit_mode) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $_SESSION['message'] = 'Produk tidak ditemukan!';
        $_SESSION['message_type'] = 'danger';
        header('Location: products.php');
        exit();
    }
}

// Get categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY nama")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit Produk' : 'Tambah Produk'; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px dashed #ddd;
            padding: 10px;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="bi bi-speedometer2"></i> Admin Panel
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_nama']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php"><i class="bi bi-house"></i> Lihat Katalog</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin_dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="products.php">
                                <i class="bi bi-box-seam"></i> Kelola Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sales_list.php">
                                <i class="bi bi-receipt"></i> Laporan Penjualan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pos.php">
                                <i class="bi bi-calculator"></i> POS/Kasir
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-<?php echo $edit_mode ? 'pencil' : 'plus-circle'; ?>"></i>
                        <?php echo $edit_mode ? 'Edit Produk' : 'Tambah Produk'; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="products.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                <?php endif; ?>

                <!-- Form -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <form action="process_product.php" method="POST" enctype="multipart/form-data" id="productForm">
                                    <?php if ($edit_mode): ?>
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="action" value="edit">
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="add">
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="sku" name="sku" 
                                                   value="<?php echo $edit_mode ? htmlspecialchars($product['sku']) : ''; ?>" 
                                                   placeholder="Contoh: ELK001" required>
                                            <div class="form-text">SKU harus unik untuk setiap produk</div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="nama" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nama" name="nama" 
                                                   value="<?php echo $edit_mode ? htmlspecialchars($product['nama']) : ''; ?>" 
                                                   placeholder="Nama produk" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Kategori</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">Pilih Kategori</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>" 
                                                        <?php echo ($edit_mode && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['nama']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="deskripsi" class="form-label">Deskripsi</label>
                                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" 
                                                  placeholder="Deskripsi produk..."><?php echo $edit_mode ? htmlspecialchars($product['deskripsi']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="harga_jual" class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" id="harga_jual" name="harga_jual" 
                                                       value="<?php echo $edit_mode ? $product['harga_jual'] : ''; ?>" 
                                                       min="0" step="0.01" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="harga_beli" class="form-label">Harga Beli</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" id="harga_beli" name="harga_beli" 
                                                       value="<?php echo $edit_mode ? $product['harga_beli'] : ''; ?>" 
                                                       min="0" step="0.01">
                                            </div>
                                            <div class="form-text">Opsional, untuk laporan profit</div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="stok" class="form-label">Stok <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="stok" name="stok" 
                                                   value="<?php echo $edit_mode ? $product['stok'] : ''; ?>" 
                                                   min="0" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="gambar" class="form-label">Gambar Produk</label>
                                        <input type="file" class="form-control" id="gambar" name="gambar" 
                                               accept="image/jpeg,image/png,image/jpg">
                                        <div class="form-text">
                                            Format: JPG, PNG. Maksimal 2MB.
                                            <?php if ($edit_mode && $product['gambar_path']): ?>
                                                <br><small class="text-success">Gambar saat ini: <?php echo htmlspecialchars($product['gambar_path']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-success btn-lg">
                                                    <i class="bi bi-check-circle"></i>
                                                    <?php echo $edit_mode ? 'Update Produk' : 'Simpan Produk'; ?>
                                                </button>
                                                <a href="products.php" class="btn btn-secondary">
                                                    <i class="bi bi-x-circle"></i> Batal
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <!-- Image Preview -->
                                            <div class="image-preview" id="imagePreview">
                                                <?php if ($edit_mode && $product['gambar_path'] && file_exists('uploads/products/' . $product['gambar_path'])): ?>
                                                    <img src="uploads/products/<?php echo htmlspecialchars($product['gambar_path']); ?>" 
                                                         alt="Preview" id="previewImg">
                                                <?php else: ?>
                                                    <div class="text-center text-muted" id="previewPlaceholder">
                                                        <i class="bi bi-image" style="font-size: 3rem;"></i>
                                                        <p class="mt-2">Preview gambar</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Help Card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-info-circle"></i> Panduan
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6>Field Wajib:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success"></i> SKU (unik)</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Nama Produk</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Harga Jual</li>
                                    <li><i class="bi bi-check-circle text-success"></i> Stok</li>
                                </ul>
                                
                                <hr>
                                
                                <h6>Tips:</h6>
                                <ul class="small text-muted">
                                    <li>SKU sebaiknya menggunakan kombinasi huruf dan angka</li>
                                    <li>Gunakan deskripsi yang jelas dan menarik</li>
                                    <li>Harga beli digunakan untuk menghitung profit</li>
                                    <li>Upload gambar untuk tampilan yang menarik</li>
                                </ul>
                            </div>
                        </div>
                        
                        <?php if ($edit_mode): ?>
                        <!-- Product Info -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-info-circle"></i> Info Produk
                                </h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <tr>
                                        <td>Dibuat:</td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Diupdate:</td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($product['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('gambar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('previewPlaceholder');
            const previewImg = document.getElementById('previewImg');
            
            if (file) {
                // Validate file size (2MB = 2097152 bytes)
                if (file.size > 2097152) {
                    alert('Ukuran file terlalu besar! Maksimal 2MB.');
                    e.target.value = '';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung! Gunakan JPG atau PNG.');
                    e.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (previewImg) {
                        previewImg.src = e.target.result;
                    } else {
                        if (placeholder) placeholder.style.display = 'none';
                        preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" id="previewImg">';
                    }
                };
                reader.readAsDataURL(file);
            } else {
                if (previewImg && !<?php echo $edit_mode ? 'true' : 'false'; ?>) {
                    preview.innerHTML = '<div class="text-center text-muted" id="previewPlaceholder"><i class="bi bi-image" style="font-size: 3rem;"></i><p class="mt-2">Preview gambar</p></div>';
                }
            }
        });
        
        // Auto-generate SKU suggestion
        document.getElementById('nama').addEventListener('input', function(e) {
            const skuField = document.getElementById('sku');
            if (!skuField.value) {
                const nama = e.target.value.trim();
                if (nama) {
                    const suggestion = nama.substring(0, 3).toUpperCase() + '001';
                    skuField.placeholder = 'Saran: ' + suggestion;
                }
            }
        });
        
        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const hargaJual = parseFloat(document.getElementById('harga_jual').value);
            const hargaBeli = parseFloat(document.getElementById('harga_beli').value) || 0;
            
            if (hargaBeli > 0 && hargaBeli >= hargaJual) {
                if (!confirm('Harga beli sama atau lebih besar dari harga jual. Yakin ingin melanjutkan?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>
</html>