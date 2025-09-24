<?php
require_once 'koneksi.php';
checkAdminLogin();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get product info first
    $stmt = $pdo->prepare("SELECT gambar_path FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Delete image file
        if ($product['gambar_path'] && file_exists('uploads/products/' . $product['gambar_path'])) {
            unlink('uploads/products/' . $product['gambar_path']);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['message'] = 'Produk berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    }
    
    header('Location: products.php');
    exit();
}

// Handle filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';

// Get categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY nama")->fetchAll();

// Build query
$query = "SELECT p.*, c.nama as kategori_nama FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
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

if ($filter == 'low_stock') {
    $query .= " AND p.stok < 10";
} elseif ($filter == 'out_stock') {
    $query .= " AND p.stok = 0";
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
    <title>Kelola Produk - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
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
                    <h1 class="h2">Kelola Produk</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="product_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Tambah Produk
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

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-3">
                                <select name="filter" class="form-select">
                                    <option value="">Semua Stok</option>
                                    <option value="low_stock" <?php echo $filter == 'low_stock' ? 'selected' : ''; ?>>Stok Rendah (&lt; 10)</option>
                                    <option value="out_stock" <?php echo $filter == 'out_stock' ? 'selected' : ''; ?>>Stok Habis</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-search"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-box text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">Tidak ada produk yang ditemukan.</p>
                                <a href="product_form.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Tambah Produk Pertama
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Gambar</th>
                                            <th>SKU</th>
                                            <th>Nama Produk</th>
                                            <th>Kategori</th>
                                            <th>Harga Jual</th>
                                            <th>Stok</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($product['gambar_path'] && file_exists('uploads/products/' . $product['gambar_path'])): ?>
                                                        <img src="uploads/products/<?php echo htmlspecialchars($product['gambar_path']); ?>" 
                                                             class="product-image" alt="<?php echo htmlspecialchars($product['nama']); ?>">
                                                    <?php else: ?>
                                                        <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($product['sku']); ?></code>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['nama']); ?></strong>
                                                    <?php if ($product['deskripsi']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($product['deskripsi'], 0, 50)); ?>...</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $product['kategori_nama'] ? htmlspecialchars($product['kategori_nama']) : '-'; ?>
                                                </td>
                                                <td class="text-success fw-bold">
                                                    <?php echo formatRupiah($product['harga_jual']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $product['stok'] == 0 ? 'bg-danger' : ($product['stok'] < 10 ? 'bg-warning text-dark' : 'bg-success'); ?>">
                                                        <?php echo $product['stok']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($product['stok'] == 0): ?>
                                                        <span class="badge bg-danger">Habis</span>
                                                    <?php elseif ($product['stok'] < 10): ?>
                                                        <span class="badge bg-warning text-dark">Rendah</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Tersedia</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="product_form.php?id=<?php echo $product['id']; ?>" 
                                                           class="btn btn-outline-primary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['nama']); ?>')" 
                                                                title="Hapus">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, nama) {
            if (confirm(`Apakah Anda yakin ingin menghapus produk "${nama}"?\n\nPeringatan: Data yang dihapus tidak dapat dikembalikan!`)) {
                window.location.href = `products.php?delete=${id}`;
            }
        }
    </script>
</body>
</html>