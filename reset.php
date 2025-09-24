<?php
// File: reset_admin_password.php
// ⚠️ PERINGATAN: Hapus file ini setelah digunakan!

include 'koneksi.php';

try {
    // --- BATASI AKSES ---
    if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
        http_response_code(403);
        die("Akses ditolak. File ini hanya bisa diakses dari localhost.");
    }

    // --- PASSWORD BARU ---
    $new_password = 'admin123'; // Ubah kalau mau
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // --- UPDATE PASSWORD ADMIN ---
    $stmt = $pdo->prepare("UPDATE admins SET password_hash = :password WHERE email = :email");
$stmt->execute([
    ':password' => $hashed_password,
    ':email' => 'admin@toko.com'
]);


    // --- PESAN SUKSES ---
    echo "<div style='padding: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2>Reset Admin Password Successfully </h2>";
    echo "<p>Now the password is: <strong>{$new_password}</strong> and Email: <strong>admin@toko.com</strong></p>";
    echo "<p>Please <a href='admin_login.php'>log in again</a> with the new password.</p>";
    echo "<p style='color:red;'>⚠️ For security reasons, please <strong>delete this file</strong> immediately after resetting your password.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='color:red; font-family: Arial, sans-serif;'>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
