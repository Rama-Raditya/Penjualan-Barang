<?php
require_once 'koneksi.php';

// Check if file parameter exists
if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die('Parameter file tidak ditemukan');
}

$filename = sanitize($_GET['file']);
$action = isset($_GET['action']) ? sanitize($_GET['action']) : 'view'; // view or download

// Security: only allow files from products directory
$upload_dir = 'uploads/products/';
$file_path = $upload_dir . $filename;

// Validate file exists
if (!file_exists($file_path)) {
    http_response_code(404);
    die('File tidak ditemukan');
}

// Security check: ensure file is actually from database (prevent direct access to any file)
$stmt = $pdo->prepare("SELECT id FROM products WHERE gambar_path = ?");
$stmt->execute([$filename]);

if (!$stmt->fetch()) {
    http_response_code(403);
    die('Akses ditolak');
}

// Get file info
$file_info = pathinfo($file_path);
$file_size = filesize($file_path);
$mime_type = mime_content_type($file_path);

// Validate file type (additional security)
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
if (!in_array($mime_type, $allowed_types)) {
    http_response_code(415);
    die('Tipe file tidak didukung');
}

// Set appropriate headers based on action
if ($action === 'download') {
    // Force download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Transfer-Encoding: binary');
} else {
    // Display in browser
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: inline; filename="' . basename($filename) . '"');
}

// Common headers
header('Content-Length: ' . $file_size);
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file_path)) . ' GMT');

// Handle conditional requests (for caching)
$etag = md5_file($file_path);
header('ETag: "' . $etag . '"');

$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : '';
$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : '';

if ($if_none_match && $if_none_match === '"' . $etag . '"') {
    http_response_code(304); // Not Modified
    exit;
}

if ($if_modified_since && strtotime($if_modified_since) >= filemtime($file_path)) {
    http_response_code(304); // Not Modified
    exit;
}

// Output file content
if ($file_size > 0) {
    // For large files, use readfile for better memory management
    if ($file_size > 1024 * 1024) { // 1MB
        readfile($file_path);
    } else {
        echo file_get_contents($file_path);
    }
} else {
    http_response_code(500);
    die('File kosong atau corrupt');
}

exit;
?>