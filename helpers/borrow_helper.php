<?php
/**
 * Helper Otomatisasi Lisensi E-Perpus & Antrean Waitlist
 */
require_once __DIR__ . '/../config/config.php';

if (!function_exists('process_auto_expiration')) {
    function process_auto_expiration($db) {
        try {
            $now = date('Y-m-d H:i:s');
            // 1. Cari peminjaman aktif yang sudah melewati tanggal/waktu jatuh tempo
            $stmt = $db->prepare("SELECT id, book_id, user_id FROM borrowings WHERE status = 'dipinjam' AND tanggal_jatuh_tempo <= :now");
            $stmt->execute([':now' => $now]);
            $expired_list = $stmt->fetchAll();

            foreach ($expired_list as $exp) {
                $borrow_id = $exp['id'];
                $book_id = $exp['book_id'];

                // Ubah status peminjaman lama menjadi dikembalikan (Otomatis Terhapus/Kembali dari Rak Buku)
                $update_exp = $db->prepare("UPDATE borrowings SET status = 'dikembalikan', tanggal_kembali = :now WHERE id = :id");
                $update_exp->execute([':now' => $now, ':id' => $borrow_id]);

                // 2. Cek apakah ada anggota yang sedang ANTRE untuk buku ini
                $queue_stmt = $db->prepare("SELECT id, user_id FROM borrowings WHERE book_id = :bid AND status = 'antre' ORDER BY created_at ASC LIMIT 1");
                $queue_stmt->execute([':bid' => $book_id]);
                $next_in_queue = $queue_stmt->fetch();

                if ($next_in_queue) {
                    // Promosikan pengantre terdepan menjadi DIPINJAM otomatis
                    $tgl_pinjam = $now;
                    $tgl_jt = date('Y-m-d H:i:s', strtotime('+' . DURASI_PINJAM_DEFAULT . ' days'));

                    $promote_stmt = $db->prepare("UPDATE borrowings SET status = 'dipinjam', tanggal_pinjam = :tgl_p, tanggal_jatuh_tempo = :tgl_jt WHERE id = :qid");
                    $promote_stmt->execute([
                        ':tgl_p' => $tgl_pinjam,
                        ':tgl_jt' => $tgl_jt,
                        ':qid' => $next_in_queue['id']
                    ]);
                } else {
                    // Jika tidak ada antrean, kembalikan 1 kuota stok lisensi digital
                    $stok_stmt = $db->prepare("UPDATE books SET stok = stok + 1 WHERE id = :bid");
                    $stok_stmt->execute([':bid' => $book_id]);
                }
            }
        } catch (Exception $e) {
            error_log("Auto Expiration Processing Error: " . $e->getMessage());
        }
    }
}

if (!function_exists('get_queue_count')) {
    function get_queue_count($db, $book_id) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM borrowings WHERE book_id = :bid AND status = 'antre'");
        $stmt->execute([':bid' => $book_id]);
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('get_user_queue_position')) {
    function get_user_queue_position($db, $user_id, $book_id) {
        // Cek ID transaksi antre user ini
        $stmt = $db->prepare("SELECT id, created_at FROM borrowings WHERE user_id = :uid AND book_id = :bid AND status = 'antre' LIMIT 1");
        $stmt->execute([':uid' => $user_id, ':bid' => $book_id]);
        $user_queue = $stmt->fetch();

        if (!$user_queue) return null;

        // Hitung berapa orang yang antre sebelum user ini
        $pos_stmt = $db->prepare("SELECT COUNT(*) FROM borrowings WHERE book_id = :bid AND status = 'antre' AND created_at <= :created");
        $pos_stmt->execute([':bid' => $book_id, ':created' => $user_queue['created_at']]);
        return (int)$pos_stmt->fetchColumn();
    }
}
