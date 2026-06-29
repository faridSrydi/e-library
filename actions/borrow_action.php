<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/format_helper.php';
require_once __DIR__ . '/../helpers/borrow_helper.php';

require_login();

$action = $_GET['action'] ?? '';
$db = Database::getConnection();
$user = get_user();

// Jalankan auto-expiration check
process_auto_expiration($db);

if ($action === 'pinjam' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . 'katalog.php');
        exit;
    }

    $book_id = (int)($_POST['book_id'] ?? 0);
    $durasi = (int)($_POST['durasi'] ?? DURASI_PINJAM_DEFAULT);

    // Validasi durasi (1 sampai 14 hari)
    if ($durasi < DURASI_PINJAM_MIN || $durasi > DURASI_PINJAM_MAX) {
        $durasi = DURASI_PINJAM_DEFAULT;
    }

    if ($book_id <= 0) {
        set_flash('danger', 'Buku tidak ditemukan.');
        header('Location: ' . BASE_URL . 'katalog.php');
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM books WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $book_id]);
    $book = $stmt->fetch();

    if (!$book) {
        set_flash('danger', 'Buku tidak ditemukan.');
        header('Location: ' . BASE_URL . 'katalog.php');
        exit;
    }

    // Cek apakah user sedang meminjam atau sudah masuk antrean buku ini
    $check_stmt = $db->prepare("SELECT id, status FROM borrowings WHERE user_id = :uid AND book_id = :bid AND status IN ('dipinjam', 'antre') LIMIT 1");
    $check_stmt->execute([':uid' => $user['id'], ':bid' => $book_id]);
    $existing = $check_stmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'dipinjam') {
            set_flash('info', 'Buku ini sudah ada di Rak Buku Aktif Anda.');
        } else {
            set_flash('info', 'Anda sudah terdaftar dalam antrean buku ini.');
        }
        header('Location: ' . BASE_URL . 'user/dashboard.php');
        exit;
    }

    $now = date('Y-m-d H:i:s');

    // ALUR 1: Jika Lisensi Digital Masih Ada (Stok > 0)
    if ($book['stok'] > 0) {
        $tgl_pinjam = $now;
        $tgl_jatuh_tempo = date('Y-m-d H:i:s', strtotime('+' . $durasi . ' days'));

        $insert = $db->prepare("INSERT INTO borrowings (user_id, book_id, tanggal_pinjam, tanggal_jatuh_tempo, status) VALUES (:uid, :bid, :tgl_p, :tgl_jt, 'dipinjam')");
        $insert->execute([
            ':uid' => $user['id'],
            ':bid' => $book_id,
            ':tgl_p' => $tgl_pinjam,
            ':tgl_jt' => $tgl_jatuh_tempo
        ]);

        // Kurangi kuota stok lisensi digital
        $update_stok = $db->prepare("UPDATE books SET stok = stok - 1 WHERE id = :id");
        $update_stok->execute([':id' => $book_id]);

        set_flash('success', 'Berhasil meminjam digital (' . $durasi . ' Hari)! Buku "' . htmlspecialchars($book['judul']) . '" telah masuk ke Rak Buku Saya.');
    } 
    // ALUR 2: Jika Lisensi Digital Habis (Stok <= 0) ➔ Masuk Antrean (Waitlist)
    else {
        $insert = $db->prepare("INSERT INTO borrowings (user_id, book_id, status) VALUES (:uid, :bid, 'antre')");
        $insert->execute([
            ':uid' => $user['id'],
            ':bid' => $book_id
        ]);

        $pos = get_user_queue_position($db, $user['id'], $book_id);
        set_flash('warning', 'Lisensi digital saat ini penuh. Anda berhasil masuk ke Antrean ke-' . $pos . '. Buku akan otomatis masuk Rak Buku Anda begitu lisensi tersedia!');
    }

    header('Location: ' . BASE_URL . 'user/dashboard.php');
    exit;
}

// Batal Antrean
if ($action === 'batal_antrean' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . 'user/dashboard.php');
        exit;
    }

    $borrow_id = (int)($_POST['borrow_id'] ?? 0);
    $delete = $db->prepare("DELETE FROM borrowings WHERE id = :id AND user_id = :uid AND status = 'antre'");
    $delete->execute([':id' => $borrow_id, ':uid' => $user['id']]);

    set_flash('info', 'Anda telah keluar dari daftar antrean buku.');
    header('Location: ' . BASE_URL . 'user/dashboard.php');
    exit;
}

// Pengembalian Awal Mandiri / Admin
if ($action === 'kembalikan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . 'user/dashboard.php');
        exit;
    }

    $borrow_id = (int)($_POST['borrow_id'] ?? 0);
    $stmt = $db->prepare("SELECT b.*, bk.judul, bk.id as book_id FROM borrowings b JOIN books bk ON b.book_id = bk.id WHERE b.id = :id AND b.status = 'dipinjam' LIMIT 1");
    $stmt->execute([':id' => $borrow_id]);
    $borrow = $stmt->fetch();

    if ($borrow) {
        // Cek otorisasi user jika role anggota
        if ($user['role'] === 'anggota' && (int)$borrow['user_id'] !== (int)$user['id']) {
            set_flash('danger', 'Anda tidak memiliki akses untuk mengembalikan peminjaman ini.');
        } else {
            $now = date('Y-m-d H:i:s');
            $update = $db->prepare("UPDATE borrowings SET tanggal_kembali = :tgl_k, status = 'dikembalikan' WHERE id = :id");
            $update->execute([':tgl_k' => $now, ':id' => $borrow_id]);

            // Cek pengantre selanjutnya
            $queue_stmt = $db->prepare("SELECT id FROM borrowings WHERE book_id = :bid AND status = 'antre' ORDER BY created_at ASC LIMIT 1");
            $queue_stmt->execute([':bid' => $borrow['book_id']]);
            $next_in_queue = $queue_stmt->fetch();

            if ($next_in_queue) {
                $tgl_pinjam = $now;
                $tgl_jt = date('Y-m-d H:i:s', strtotime('+' . DURASI_PINJAM_DEFAULT . ' days'));
                $promote = $db->prepare("UPDATE borrowings SET status = 'dipinjam', tanggal_pinjam = :tgl_p, tanggal_jatuh_tempo = :tgl_jt WHERE id = :qid");
                $promote->execute([':tgl_p' => $tgl_pinjam, ':tgl_jt' => $tgl_jt, ':qid' => $next_in_queue['id']]);
            } else {
                $update_stok = $db->prepare("UPDATE books SET stok = stok + 1 WHERE id = :id");
                $update_stok->execute([':id' => $borrow['book_id']]);
            }

            set_flash('success', 'Buku "' . htmlspecialchars($borrow['judul']) . '" telah berhasil dikembalikan dari Rak Buku Anda.');
        }
    } else {
        set_flash('info', 'Peminjaman buku ini sudah tidak aktif atau telah dikembalikan sebelumnya.');
    }

    if ($user['role'] === 'admin' || $user['role'] === 'petugas') {
        header('Location: ' . BASE_URL . 'admin/peminjaman.php');
    } else {
        header('Location: ' . BASE_URL . 'user/dashboard.php');
    }
    exit;
}

header('Location: ' . BASE_URL);
exit;
