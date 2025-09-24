<?php
// Konfigurasi database untuk XAMPP
$hostname = "localhost";
$username = "root";
$password = "";
$database = "datapenjualan_db";

try {
    // Koneksi menggunakan PDO
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Fungsi untuk sanitasi input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Fungsi untuk validasi email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Fungsi untuk generate invoice number
function generateInvoiceNo() {
    return 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Fungsi untuk format rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Fungsi untuk memulai session jika belum dimulai
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Fungsi untuk cek login admin
function checkAdminLogin() {
    startSession();
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
        header('Location: admin_login.php');
        exit();
    }
}

// Fungsi untuk logout
function logout() {
    startSession();
    session_destroy();
    header('Location: admin_login.php');
    exit();
}
?>