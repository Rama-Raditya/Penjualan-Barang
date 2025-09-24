<?php
require_once 'koneksi.php';
checkAdminLogin();

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    
    $query = "SELECT s.*, c.nama as customer_nama 
              FROM sales s 
              LEFT JOIN customers c ON s.customer_id = c.id 
              WHERE DATE(s.created_at) BETWEEN ? AND ? 
              ORDER BY s.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $sales = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan_penjualan_' . $start_date . '_to_' . $end_date . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['Invoice', 'Tanggal', 'Pelanggan', 'Total Items', 'Total Amount', 'Payment Method']);
    
    // CSV data
    foreach ($sales as $sale) {
        fputcsv($output, [
            $sale['invoice_no'],
            $sale['created_at'],
            $sale['customer_nama'] ?: 'Guest',
            $sale['total_items'],
            $sale['total_amount'],
            $sale['pembayaran_method']
        ]);
    }
    
    fclose($output);
    exit();
}

// Handle filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$customer_filter = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;

// Get customers for filter
$customers = $pdo->query("SELECT * FROM customers ORDER BY nama")->fetchAll();

// Build query
$query = "SELECT s.*, c.nama as customer_nama 
          FROM sales s 
          LEFT JOIN customers c ON s.customer_id = c.id 
          WHERE DATE(s.created_at) BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($customer_filter > 0) {
    $query .= " AND s.customer_id = ?";
    $params[] = $customer_filter;
}

$query .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// Calculate summary
$total_transactions = count($sales);
$total_revenue = array_sum(array_column($sales, 'total_amount'));
$total_items_sold = array_sum(array_column($sales, 'total_items'));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .summary-card.success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
        .summary-card.info {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
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
                            <a class="nav-link" href="products.php">
                                <i class="bi bi-box-seam"></i> Kelola Produk
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="sales_list.php">
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
                    <h1 class="h2">Laporan Penjualan</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="pos.php" class="btn btn-primary me-2">
                            <i class="bi bi-plus-circle"></i> Transaksi Baru
                        </a>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card summary-card text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Transaksi</h6>
                                        <h3><?php echo $total_transactions; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-receipt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card summary-card success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Pendapatan</h6>
                                        <h3><?php echo formatRupiah($total_revenue); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-currency-dollar fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card summary-card info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Item Terjual</h6>
                                        <h3><?php echo $total_items_sold; ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="bi bi-box fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="customer" class="form-label">Pelanggan</label>
                                <select name="customer" id="customer" class="form-select">
                                    <option value="0">Semua Pelanggan</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>" 
                                                <?php echo $customer_filter == $customer['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($customer['nama']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Export Section -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
                               class="btn btn-success">
                                <i class="bi bi-download"></i> Export CSV
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($sales)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-3">Tidak ada transaksi pada periode ini.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Tanggal</th>
                                            <th>Pelanggan</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Pembayaran</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sales as $sale): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($sale['invoice_no']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <?php echo $sale['customer_nama'] ? htmlspecialchars($sale['customer_nama']) : '<em>Guest</em>'; ?>
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
                                                    <button class="btn btn-outline-info btn-sm" 
                                                            onclick="viewDetails(<?php echo $sale['id']; ?>)">
                                                        <i class="bi bi-eye"></i> Detail
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination could be added here if needed -->
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Sale Detail Modal -->
    <div class="modal fade" id="saleDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="saleDetailContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View sale details
        function viewDetails(saleId) {
            const modal = new bootstrap.Modal(document.getElementById('saleDetailModal'));
            const content = document.getElementById('saleDetailContent');
            
            // Show loading
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            modal.show();
            
            // Fetch sale details
            fetch(`get_sale_details.php?id=${saleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        content.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }
                    
                    let html = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Invoice:</strong> ${data.sale.invoice_no}<br>
                                <strong>Tanggal:</strong> ${new Date(data.sale.created_at).toLocaleString('id-ID')}<br>
                                <strong>Pelanggan:</strong> ${data.sale.customer_nama || 'Guest'}
                            </div>
                            <div class="col-md-6">
                                <strong>Total Items:</strong> ${data.sale.total_items}<br>
                                <strong>Total Amount:</strong> ${formatRupiah(data.sale.total_amount)}<br>
                                <strong>Pembayaran:</strong> ${data.sale.pembayaran_method.toUpperCase()}
                            </div>
                        </div>
                        
                        <h6>Item yang dibeli:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th>SKU</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    data.items.forEach(item => {
                        html += `
                            <tr>
                                <td>${item.product_nama}</td>
                                <td><code>${item.product_sku}</code></td>
                                <td>${formatRupiah(item.price)}</td>
                                <td>${item.qty}</td>
                                <td class="text-success fw-bold">${formatRupiah(item.subtotal)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4">TOTAL</th>
                                        <th class="text-success">${formatRupiah(data.sale.total_amount)}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    
                    content.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = '<div class="alert alert-danger">Gagal memuat detail transaksi</div>';
                });
        }
        
        // Format rupiah function
        function formatRupiah(number) {
            return 'Rp ' + number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Set default dates if empty
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
            
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            
            if (!startDate.value) startDate.value = firstDay;
            if (!endDate.value) endDate.value = today;
        });
    </script>
</body>
</html>