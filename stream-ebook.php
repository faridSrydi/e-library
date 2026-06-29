<?php
/**
 * Secure E-Book Streaming Binary Endpoint
 * Mencegah direct download & menyembunyikan path asli PDF
 */
ob_start(); // Tangkap semua output dari file include

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth_helper.php';

require_login();

$book_id = (int)($_GET['id'] ?? 0);
if ($book_id <= 0) {
    http_response_code(404);
    die('Buku tidak ditemukan.');
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id, judul, file_ebook FROM books WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $book_id]);
$book = $stmt->fetch();

if (!$book || empty($book['file_ebook'])) {
    http_response_code(404);
    die('File E-Book tidak tersedia untuk buku ini.');
}

$file_path = STORAGE_PATH . 'ebooks/' . $book['file_ebook'];

if (!file_exists($file_path)) {
    // Fallback demo sample jika file belum diupload di server lokal
    $file_path = __DIR__ . '/assets/sample.pdf';
    if (!file_exists($file_path)) {
        // Buat PDF dummy jika belum ada
        file_put_contents($file_path, "%PDF-1.4 %...\n1 0 obj<<>>endobj trailer<</Root 1 0 R>>");
    }
}

// Bersihkan SEMUA output buffers (termasuk yang nested)
while (ob_get_level()) {
    ob_end_clean();
}

// Header streaming terproteksi (Disguise as octet-stream to bypass IDM)
header('Content-Type: application/octet-stream');
header('Content-Disposition: inline; filename="stream_' . md5($book['id']) . '.bin"');
header('Content-Transfer-Encoding: binary');
header('Accept-Ranges: bytes');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit;
