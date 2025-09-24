<?php
require_once 'koneksi.php';

// Get categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY nama")->fetchAll();

// Handle search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build query
$query = "SELECT p.*, c.nama as kategori_nama FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.stok > 0";
$params = [];

if ($search) {
    $query .= " AND (p.nama LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Online - Katalog Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        .price {
            color: #e74c3c;
            font-weight: bold;
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Toko Online
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="pos.php">
                            <i class="bi bi-calculator"></i> POS/Kasir
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_login.php">
                            <i class="bi bi-person-lock"></i> Login Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="mb-3">Katalog Produk</h1>
                
                <!-- Search and Filter -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="category" class="form-select">
                            <option value="0">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="row">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i>
                        Tidak ada produk yang ditemukan.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <a href="view_products.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                            <div class="card h-100 product-card">
                                <?php if ($product['gambar_path'] && file_exists('uploads/products/' . $product['gambar_path'])): ?>
                                    <img src="uploads/products/<?= htmlspecialchars($product['gambar_path']); ?>" 
                                         class="card-img-top product-image" 
                                         alt="<?= htmlspecialchars($product['nama']); ?>">
                                <?php else: ?>
                                    <div class="card-img-top product-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title"><?= htmlspecialchars($product['nama']); ?></h6>
                                    <p class="card-text text-muted small">
                                        SKU: <?= htmlspecialchars($product['sku']); ?>
                                        <?php if ($product['kategori_nama']): ?>
                                            <br>Kategori: <?= htmlspecialchars($product['kategori_nama']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($product['deskripsi']): ?>
                                        <p class="card-text small"><?= htmlspecialchars(substr($product['deskripsi'], 0, 100)); ?>...</p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="price fs-6"><?= formatRupiah($product['harga_jual']); ?></span>
                                            <small class="text-muted">Stok: <?= $product['stok']; ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p>&copy; 2024 Toko Online. Aplikasi Penjualan Sederhana.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>