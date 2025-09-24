# Aplikasi Penjualan Web

## Deskripsi
Aplikasi penjualan web responsive dengan fitur manajemen produk, transaksi penjualan, dan laporan.

## Persyaratan
- XAMPP atau server lokal dengan PHP dan MySQL
- Browser modern

## Instalasi
1. Ekstrak folder proyek ke dalam direktori `htdocs` di XAMPP
2. Import database:
   - Buka phpMyAdmin
   - Buat database baru bernama `penjualan_db`
   - Pilih tab "Import"
   - Pilih file `install.sql`
   - Klik "Go"

3. Konfigurasi koneksi database:
   - Buka file `includes/koneksi.php`
   - Pastikan username dan password MySQL sesuai dengan konfigurasi XAMPP Anda

4. Buat folder upload:
   - Buat folder `uploads/products/` di root direktori proyek
   - Atur permission agar dapat diakses oleh PHP (misalnya 755)

5. Jalankan aplikasi:
   - Buka browser dan akses `http://localhost/nama-folder-proyek/`

## Fitur Utama
- **Manajemen Produk**: Tambah, edit, hapus produk dengan validasi
- **Transaksi Penjualan**: Form kasir dengan autocomplete produk
- **Pelanggan**: Manajemen data pelanggan opsional
- **Login Admin**: Autentikasi dengan password hashing
- **Dashboard Admin**: Ringkasan penjualan dan laporan
- **Export Data**: Ekspor transaksi ke CSV
- **Responsive Design**: Tampilan mobile-friendly

## Catatan Penting
- Semua input divalidasi untuk mencegah SQL injection
- Password disimpan menggunakan `password_hash()` PHP
- Gambar produk disimpan di server dan dilindungi akses
- Session digunakan untuk proteksi halaman admin

## Struktur Folder
