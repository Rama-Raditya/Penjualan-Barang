<?php
require_once 'koneksi.php';

// Get customers for dropdown
$customers = $pdo->query("SELECT * FROM customers ORDER BY nama")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS/Kasir - Toko Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .pos-container {
            max-height: 100vh;
            overflow-y: auto;
        }
        .cart-item {
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }
        .total-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px;
        }
        .search-results {
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
        }
        .product-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .product-item:hover {
            background-color: #f8f9fa;
        }
        .qty-control {
            width: 60px;
        }
        @media (max-width: 768px) {
            .pos-container {
                padding: 10px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-calculator"></i> POS/Kasir
            </a>
            <div class="navbar-nav flex-row">
                <a class="nav-link me-3" href="index.php">
                    <i class="bi bi-house"></i> Katalog
                </a>
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Admin
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <div class="row">
            <!-- Left Panel - Product Search & Cart -->
            <div class="col-lg-8">
                <!-- Product Search -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-search"></i> Cari Produk</h5>
                    </div>
                    <div class="card-body">
                        <div class="position-relative">
                            <input type="text" class="form-control form-control-lg" id="productSearch" 
                                   placeholder="Ketik nama produk atau SKU..." autocomplete="off">
                            <div class="position-absolute w-100 search-results bg-white border rounded shadow-lg" 
                                 id="searchResults" style="display: none; top: 100%; left: 0;"></div>
                        </div>
                    </div>
                </div>

                <!-- Shopping Cart -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-cart"></i> Keranjang Belanja</h5>
                        <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                            <i class="bi bi-trash"></i> Kosongkan
                        </button>
                    </div>
                    <div class="card-body" id="cartItems">
                        <div class="text-center text-muted py-4" id="emptyCart">
                            <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                            <p class="mt-2">Keranjang kosong<br>Mulai dengan mencari produk di atas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Transaction Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top">
                    <!-- Customer Selection -->
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Detail Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="customer" class="form-label">Pelanggan</label>
                            <select class="form-select" id="customer">
                                <option value="">Guest/Umum</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>">
                                        <?php echo htmlspecialchars($customer['nama']); ?>
                                        (<?php echo htmlspecialchars($customer['telepon']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="paymentMethod" class="form-label">Metode Pembayaran</label>
                            <select class="form-select" id="paymentMethod">
                                <option value="cash">Tunai</option>
                                <option value="transfer">Transfer</option>
                                <option value="card">Kartu</option>
                            </select>
                        </div>
                    </div>

                    <!-- Total Summary -->
                    <div class="total-section p-4 mx-3 mb-3">
                        <div class="row text-center">
                            <div class="col-6">
                                <h6>Total Item</h6>
                                <h4 id="totalItems">0</h4>
                            </div>
                            <div class="col-6">
                                <h6>Total Harga</h6>
                                <h4 id="totalAmount">Rp 0</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-lg" onclick="processTransaction()" id="processBtn" disabled>
                                <i class="bi bi-check-circle"></i> Proses Transaksi
                            </button>
                            <button class="btn btn-outline-secondary" onclick="printReceipt()" id="printBtn" style="display: none;">
                                <i class="bi bi-printer"></i> Cetak Struk
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle"></i> Transaksi Berhasil
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <h4 class="text-success mb-3">Pembayaran Berhasil!</h4>
                    <p><strong>Invoice:</strong> <span id="invoiceNumber"></span></p>
                    <p><strong>Total:</strong> <span id="finalAmount"></span></p>
                    <p class="text-muted">Terima kasih atas pembeliannya!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="printReceipt()">
                        <i class="bi bi-printer"></i> Cetak Struk
                    </button>
                    <button type="button" class="btn btn-success" onclick="newTransaction()">
                        <i class="bi bi-plus"></i> Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];
        let searchTimeout;
        
        // Format rupiah
        function formatRupiah(number) {
            return 'Rp ' + number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Search products
        document.getElementById('productSearch').addEventListener('input', function() {
            const query = this.value.trim();
            const results = document.getElementById('searchResults');
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                results.style.display = 'none';
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch(`search_products.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        displaySearchResults(data);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }, 300);
        });
        
        // Display search results
        function displaySearchResults(products) {
            const results = document.getElementById('searchResults');
            
            if (products.length === 0) {
                results.innerHTML = '<div class="p-3 text-muted">Produk tidak ditemukan</div>';
                results.style.display = 'block';
                return;
            }
            
            let html = '';
            products.forEach(product => {
                html += `
                    <div class="product-item p-3 border-bottom" onclick="addToCart(${product.id})">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${product.nama}</strong><br>
                                <small class="text-muted">SKU: ${product.sku} | Stok: ${product.stok}</small>
                            </div>
                            <div class="text-end">
                                <div class="text-success fw-bold">${formatRupiah(product.harga_jual)}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            results.innerHTML = html;
            results.style.display = 'block';
        }
        
        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            const searchResults = document.getElementById('searchResults');
            const searchInput = document.getElementById('productSearch');
            
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.style.display = 'none';
            }
        });
        
        // Add product to cart
        function addToCart(productId) {
            fetch(`get_product.php?id=${productId}`)
                .then(response => response.json())
                .then(product => {
                    if (product.error) {
                        alert(product.error);
                        return;
                    }
                    
                    // Check if product already in cart
                    const existingItem = cart.find(item => item.id === product.id);
                    
                    if (existingItem) {
                        if (existingItem.qty < product.stok) {
                            existingItem.qty++;
                            existingItem.subtotal = existingItem.qty * existingItem.price;
                        } else {
                            alert('Stok tidak mencukupi!');
                            return;
                        }
                    } else {
                        cart.push({
                            id: product.id,
                            nama: product.nama,
                            sku: product.sku,
                            price: parseFloat(product.harga_jual),
                            qty: 1,
                            stok: product.stok,
                            subtotal: parseFloat(product.harga_jual)
                        });
                    }
                    
                    updateCartDisplay();
                    document.getElementById('productSearch').value = '';
                    document.getElementById('searchResults').style.display = 'none';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal menambahkan produk ke keranjang');
                });
        }
        
        // Update cart display
        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const emptyCart = document.getElementById('emptyCart');
            
            if (cart.length === 0) {
                emptyCart.style.display = 'block';
                cartItems.innerHTML = emptyCart.outerHTML;
                updateTotals();
                return;
            }
            
            let html = '';
            cart.forEach((item, index) => {
                html += `
                    <div class="cart-item p-3 mb-2 rounded">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${item.nama}</h6>
                                <small class="text-muted">SKU: ${item.sku}</small><br>
                                <span class="text-success fw-bold">${formatRupiah(item.price)}</span>
                            </div>
                            <button class="btn btn-outline-danger btn-sm" onclick="removeFromCart(${index})">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        
                        <div class="row mt-2 align-items-center">
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, -1)">-</button>
                                    <input type="number" class="form-control qty-control text-center" 
                                           value="${item.qty}" min="1" max="${item.stok}" 
                                           onchange="updateQuantity(${index}, 0, this.value)">
                                    <button class="btn btn-outline-secondary" onclick="updateQuantity(${index}, 1)">+</button>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <strong>${formatRupiah(item.subtotal)}</strong>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            cartItems.innerHTML = html;
            updateTotals();
        }
        
        // Update quantity
        function updateQuantity(index, change, newValue = null) {
            if (newValue !== null) {
                cart[index].qty = parseInt(newValue) || 1;
            } else {
                cart[index].qty += change;
            }
            
            // Validate quantity
            if (cart[index].qty < 1) {
                cart[index].qty = 1;
            }
            if (cart[index].qty > cart[index].stok) {
                cart[index].qty = cart[index].stok;
                alert('Stok tidak mencukupi!');
            }
            
            cart[index].subtotal = cart[index].qty * cart[index].price;
            updateCartDisplay();
        }
        
        // Remove from cart
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }
        
        // Clear cart
        function clearCart() {
            if (cart.length === 0) return;
            
            if (confirm('Yakin ingin mengosongkan keranjang?')) {
                cart = [];
                updateCartDisplay();
            }
        }
        
        // Update totals
        function updateTotals() {
            const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
            const totalAmount = cart.reduce((sum, item) => sum + item.subtotal, 0);
            
            document.getElementById('totalItems').textContent = totalItems;
            document.getElementById('totalAmount').textContent = formatRupiah(totalAmount);
            
            // Enable/disable process button
            document.getElementById('processBtn').disabled = cart.length === 0;
        }
        
        // Process transaction
        function processTransaction() {
            if (cart.length === 0) {
                alert('Keranjang kosong!');
                return;
            }
            
            const customer_id = document.getElementById('customer').value;
            const payment_method = document.getElementById('paymentMethod').value;
            
            const transactionData = {
                customer_id: customer_id || null,
                payment_method: payment_method,
                items: cart
            };
            
            // Disable button to prevent double submission
            document.getElementById('processBtn').disabled = true;
            document.getElementById('processBtn').innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
            
            fetch('process_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(transactionData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success modal
                    document.getElementById('invoiceNumber').textContent = data.invoice_no;
                    document.getElementById('finalAmount').textContent = formatRupiah(data.total_amount);
                    
                    const modal = new bootstrap.Modal(document.getElementById('successModal'));
                    modal.show();
                    
                    // Store transaction data for printing
                    window.lastTransaction = data;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memproses transaksi');
            })
            .finally(() => {
                // Re-enable button
                document.getElementById('processBtn').disabled = false;
                document.getElementById('processBtn').innerHTML = '<i class="bi bi-check-circle"></i> Proses Transaksi';
            });
        }
        
        // New transaction
        function newTransaction() {
            cart = [];
            updateCartDisplay();
            document.getElementById('customer').value = '';
            document.getElementById('paymentMethod').value = 'cash';
            document.getElementById('productSearch').focus();
            
            // Hide modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('successModal'));
            if (modal) modal.hide();
        }
        
        // Print receipt (simple version)
        function printReceipt() {
            if (!window.lastTransaction) {
                alert('Tidak ada data transaksi untuk dicetak');
                return;
            }
            
            const transaction = window.lastTransaction;
            let receiptContent = `
                <html>
                <head>
                    <title>Struk Pembelian</title>
                    <style>
                        body { font-family: monospace; font-size: 12px; width: 300px; margin: 0 auto; }
                        .center { text-align: center; }
                        .right { text-align: right; }
                        .line { border-top: 1px dashed #000; margin: 10px 0; }
                        table { width: 100%; }
                        .total { font-weight: bold; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <div class="center">
                        <h2>TOKO ONLINE</h2>
                        <p>Struk Pembelian</p>
                    </div>
                    <div class="line"></div>
                    <table>
                        <tr><td>Invoice:</td><td class="right">${transaction.invoice_no}</td></tr>
                        <tr><td>Tanggal:</td><td class="right">${new Date().toLocaleString('id-ID')}</td></tr>
                        <tr><td>Kasir:</td><td class="right">Admin</td></tr>
                    </table>
                    <div class="line"></div>
                    
                    <table>
            `;
            
            transaction.items.forEach(item => {
                receiptContent += `
                    <tr>
                        <td colspan="2"><strong>${item.nama}</strong></td>
                    </tr>
                    <tr>
                        <td>${item.qty} x ${formatRupiah(item.price)}</td>
                        <td class="right">${formatRupiah(item.subtotal)}</td>
                    </tr>
                `;
            });
            
            receiptContent += `
                    </table>
                    <div class="line"></div>
                    <table>
                        <tr class="total">
                            <td>TOTAL:</td>
                            <td class="right">${formatRupiah(transaction.total_amount)}</td>
                        </tr>
                        <tr>
                            <td>Pembayaran:</td>
                            <td class="right">${transaction.payment_method.toUpperCase()}</td>
                        </tr>
                    </table>
                    <div class="line"></div>
                    <div class="center">
                        <p>Terima kasih atas kunjungan Anda!</p>
                        <p>Barang yang sudah dibeli tidak dapat ditukar</p>
                    </div>
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(receiptContent);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Initialize
        updateCartDisplay();
        document.getElementById('productSearch').focus();
    </script>
</body>
</html>