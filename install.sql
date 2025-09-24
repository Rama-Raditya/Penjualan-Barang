-- Database untuk Aplikasi Penjualan
CREATE DATABASE IF NOT EXISTS penjualan_db;
USE penjualan_db;

-- Tabel admin
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel kategori
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel pelanggan (opsional)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    telepon VARCHAR(20),
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel produk
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) UNIQUE NOT NULL,
    nama VARCHAR(200) NOT NULL,
    category_id INT,
    deskripsi TEXT,
    harga_jual DECIMAL(10,2) NOT NULL,
    harga_beli DECIMAL(10,2),
    stok INT NOT NULL DEFAULT 0,
    gambar_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Tabel penjualan
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    total_items INT NOT NULL,
    pembayaran_method ENUM('cash', 'transfer', 'card') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Tabel item penjualan
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Indeks untuk performa
CREATE INDEX idx_products_sku ON products(sku);
CREATE INDEX idx_products_nama ON products(nama);
CREATE INDEX idx_sales_invoice ON sales(invoice_no);
CREATE INDEX idx_sales_date ON sales(created_at);
CREATE INDEX idx_customers_email ON customers(email);

-- Data sample
INSERT INTO admins (nama, email, password_hash) VALUES 
('Administrator', 'admin@toko.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Password: password123

INSERT INTO categories (nama, slug) VALUES 
('Elektronik', 'elektronik'),
('Fashion', 'fashion'),
('Makanan & Minuman', 'makanan-minuman'),
('Kesehatan & Kecantikan', 'kesehatan-kecantikan'),
('Rumah Tangga', 'rumah-tangga');

INSERT INTO products (sku, nama, category_id, deskripsi, harga_jual, harga_beli, stok) VALUES
('ELK001', 'Smartphone Samsung Galaxy A54', 1, 'Smartphone Android dengan kamera 50MP', 4500000, 4000000, 15),
('ELK002', 'Headphone Wireless Sony', 1, 'Headphone wireless dengan noise canceling', 750000, 600000, 25),
('FSN001', 'Kemeja Pria Casual', 2, 'Kemeja pria bahan katun premium', 150000, 100000, 30),
('MNM001', 'Kopi Arabica Premium 250g', 3, 'Kopi arabica single origin kualitas premium', 85000, 60000, 50),
('KES001', 'Vitamin C 1000mg', 4, 'Suplemen vitamin C untuk daya tahan tubuh', 45000, 30000, 100);

INSERT INTO customers (nama, email, telepon, alamat) VALUES
('Budi Santoso', 'budi@gmail.com', '081234567890', 'Jl. Merdeka No. 123, Jakarta'),
('Siti Aminah', 'siti@yahoo.com', '081234567891', 'Jl. Sudirman No. 456, Surabaya'),
('Ahmad Rahman', 'ahmad@gmail.com', '081234567892', 'Jl. Diponegoro No. 789, Bandung');