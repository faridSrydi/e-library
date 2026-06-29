<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

require_login();

$db = Database::getConnection();
$user = get_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . 'katalog.php');
        exit;
    }

    $book_id = (int)($_POST['book_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $ulasan = trim($_POST['ulasan'] ?? '');
    $redirect_to = $_POST['redirect_to'] ?? '';

    if ($book_id <= 0) {
        set_flash('danger', 'Buku tidak ditemukan.');
        header('Location: ' . BASE_URL . 'katalog.php');
        exit;
    }

    // Pastikan buku ada & ambil slug-nya
    $stmt_book = $db->prepare("SELECT id, slug FROM books WHERE id = :id LIMIT 1");
    $stmt_book->execute([':id' => $book_id]);
    $book_data = $stmt_book->fetch();
    if (!$book_data) {
        set_flash('danger', 'Buku tidak ditemukan.');
        header('Location: ' . BASE_URL . 'katalog.php');
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        set_flash('warning', 'Silakan beri rating bintang 1 hingga 5.');
        $target = !empty($redirect_to) ? $redirect_to : BASE_URL . 'detail-buku/' . $book_data['slug'];
        header('Location: ' . $target);
        exit;
    }

    // Simpan atau update ulasan pengguna
    $stmt_review = $db->prepare("INSERT INTO reviews (user_id, book_id, rating, ulasan, created_at, updated_at) 
                                 VALUES (:user_id, :book_id, :rating, :ulasan, NOW(), NOW())
                                 ON DUPLICATE KEY UPDATE rating = VALUES(rating), ulasan = VALUES(ulasan), updated_at = NOW()");
    $stmt_review->execute([
        ':user_id' => $user['id'],
        ':book_id' => $book_id,
        ':rating'  => $rating,
        ':ulasan'  => $ulasan
    ]);

    // Hitung ulang rating rata-rata buku
    $stmt_avg = $db->prepare("UPDATE books SET rating = (SELECT COALESCE(ROUND(AVG(rating), 1), 0.0) FROM reviews WHERE book_id = :bid) WHERE id = :bid2");
    $stmt_avg->execute([':bid' => $book_id, ':bid2' => $book_id]);

    set_flash('success', 'Ulasan dan rating Anda berhasil disimpan!');

    if (!empty($redirect_to)) {
        header('Location: ' . $redirect_to);
    } else {
        header('Location: ' . BASE_URL . 'detail-buku/' . $book_data['slug']);
    }
    exit;
}

header('Location: ' . BASE_URL . 'katalog.php');
exit;
