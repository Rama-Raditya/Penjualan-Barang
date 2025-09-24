<?php
// File: view_products.php
require_once 'koneksi.php'; // gunakan require_once supaya koneksi / fungsi tidak diload 2x

// Ambil ID dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Produk tidak ditemukan.");
}
$id = (int) $_GET['id'];

// Query detail produk (samakan nama kolom dengan tabelmu)
$stmt = $pdo->prepare("SELECT p.*, c.nama AS kategori_nama 
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produk tidak ditemukan.");
}

// DI SINI TIDAK ADA definisi function formatRupiah() karena sudah ada di koneksi.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Produk - <?= htmlspecialchars($product['nama']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-card { max-width: 900px; margin:auto; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,.12); }
        .product-img { width:100%; max-height:420px; object-fit:cover; }
        @media(min-width:768px){
            .product-horizontal { display:flex; gap:0; }
            .product-horizontal .col-img { flex:0 0 45%; }
            .product-horizontal .col-info { flex:1; padding:28px; }
            .product-img { height:100%; max-height:none; object-fit:cover; }
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <a href="index.php" class="btn btn-outline-secondary mb-4">&larr; Kembali ke Katalog</a>

    <div class="product-card bg-white">
        <div class="product-horizontal">
            <div class="col-img">
                <?php if (!empty($product['gambar_path']) && file_exists('uploads/products/' . $product['gambar_path'])): ?>
                    <img src="uploads/products/<?= htmlspecialchars($product['gambar_path']); ?>"
                         alt="<?= htmlspecialchars($product['nama']); ?>" class="product-img">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-secondary text-white" style="height:100%; min-height:250px;">
                        <span>Tidak ada gambar</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-info">
                <h2 class="fw-bold"><?= htmlspecialchars($product['nama']); ?></h2>
                <p class="text-muted mb-1">Kategori: <?= htmlspecialchars($product['kategori_nama'] ?? '-'); ?></p>
                <p class="text-muted mb-3">SKU: <?= htmlspecialchars($product['sku'] ?? '-'); ?></p>

                <h4 class="text-danger"><?= formatRupiah($product['harga_jual']); ?></h4>
                <p><strong>Stok:</strong> <?= htmlspecialchars($product['stok']); ?></p>

                <hr>
                <h5>Deskripsi</h5>
                <p><?= nl2br(htmlspecialchars($product['deskripsi'] ?? 'Tidak ada deskripsi.')); ?></p>

                <div class="mt-3">
                    <a href="index.php" class="btn btn-primary">Kembali ke Katalog</a>
                    <!-- contoh tombol beli (dummy) -->
                    <a href="mailto:info@toko.com?subject=Order%20<?= urlencode($product['nama']) ?>" class="btn btn-success ms-2">Beli / Tanyakan</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
