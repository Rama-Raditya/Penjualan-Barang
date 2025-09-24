<?php
require_once 'koneksi.php';
checkAdminLogin();

// Get dashboard statistics
$today = date('Y-m-d');

// Total penjualan hari ini
$stmt = $pdo->prepare("SELECT COUNT(*) as total_transaksi, COALESCE(SUM(total_amount), 0) as total_pendapatan 
                       FROM sales WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$today_stats = $stmt->fetch();

// Produk stok rendah (< 10)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stok < 10");
$low_stock = $stmt->fetch()['total'];

// Produk habis
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stok = 0");
$out_stock = $stmt->fetch()['total'];

// Total produk
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$total_products = $stmt->fetch()['total'];

// Total pelanggan
$stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
$total_customers = $stmt->fetch()['total'];

// Transaksi terakhir
$stmt = $pdo->prepare("SELECT s.*, c.nama as customer_nama 
                       FROM sales s 
                       LEFT JOIN customers c ON s.customer_id = c.id 
                       ORDER BY s.created_at DESC LIMIT 10");
$stmt->execute();
$recent_sales = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Toko Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .stat-card.danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        .stat-card.success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
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
                            <a class="nav-link active" href="admin_dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="pos.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Transaksi Baru
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-white-50 small">Penjualan Hari Ini</div>
                                        <div class="h5 mb-0"><?php echo $today_stats['total_transaksi']; ?> transaksi</div>
                                        <div class="h6"><?php echo formatRupiah($today_stats['total_pendapatan']); ?></div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-cash-coin fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-white-50 small">Total Produk</div>
                                        <div class="h5 mb-0"><?php echo $total_products; ?></div>
                                        <div class="small">Pelanggan: <?php echo $total_customers; ?></div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-box-seam fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card warning text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-white-50 small">Stok Rendah</div>
                                        <div class="h5 mb-0"><?php echo $low_stock; ?> produk</div>
                                        <div class="small">< 10 stok</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card danger text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="text-white-50 small">Stok Habis</div>
                                        <div class="h5 mb-0"><?php echo $out_stock; ?> produk</div>
                                        <div class="small">Perlu restock</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-x-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-clock-history"></i> Transaksi Terakhir
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_sales)): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        Belum ada transaksi hari ini.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Invoice</th>
                                                    <th>Pelanggan</th>
                                                    <th>Items</th>
                                                    <th>Total</th>
                                                    <th>Pembayaran</th>
                                                    <th>Tanggal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_sales as $sale): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($sale['invoice_no']); ?></strong>
                                                        </td>
                                                        <td>
                                                            <?php echo $sale['customer_nama'] ? htmlspecialchars($sale['customer_nama']) : 'Guest'; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo $sale['total_items']; ?></span>
                                                        </td>
                                                        <td class="text-success fw-bold">
                                                            <?php echo formatRupiah($sale['total_amount']); ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-secondary">
                                                                <?php echo ucfirst($sale['pembayaran_method']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted">
                                                                <?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?>
                                                            </small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="text-center mt-3">
                                        <a href="sales_list.php" class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i> Lihat Semua Transaksi
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-lightning"></i> Aksi Cepat
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3 col-sm-6">
                                        <a href="product_form.php" class="btn btn-success w-100">
                                            <i class="bi bi-plus-circle"></i><br>
                                            Tambah Produk
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="pos.php" class="btn btn-primary w-100">
                                            <i class="bi bi-calculator"></i><br>
                                            POS/Kasir
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="sales_list.php?export=csv" class="btn btn-info w-100">
                                            <i class="bi bi-download"></i><br>
                                            Export CSV
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <a href="products.php?filter=low_stock" class="btn btn-warning w-100">
                                            <i class="bi bi-exclamation-triangle"></i><br>
                                            Cek Stok Rendah
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>