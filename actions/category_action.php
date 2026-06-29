<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

require_role(['admin', 'petugas']);

$action = $_GET['action'] ?? '';
$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
        exit;
    }

    if ($action === 'tambah') {
        $inputs = $_POST['kategori'] ?? [];
        // Bersihkan input dan buang jika kosong
        $kategori_list = array_filter(array_map('trim', $inputs));

        if (empty($kategori_list)) {
            set_flash('warning', 'Nama kategori tidak boleh kosong.');
            header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
            exit;
        }

        // Gabungkan kategori dengan ' & ' jika diinput lebih dari 1
        if (count($kategori_list) === 1) {
            $nama_kategori = reset($kategori_list);
        } else {
            $nama_kategori = implode(' & ', $kategori_list);
        }

        // Generate slug (misal: "Bisnis & Keuangan" -> "bisnis-keuangan")
        $slug = strtolower($nama_kategori);
        $slug = str_replace(' & ', '-', $slug);
        $slug = str_replace('&', '-', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Cek apakah kategori atau slug sudah ada di database
        $check_stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE nama_kategori = :nama OR slug = :slug");
        $check_stmt->execute([':nama' => $nama_kategori, ':slug' => $slug]);
        if ($check_stmt->fetchColumn() > 0) {
            set_flash('danger', 'Kategori "' . htmlspecialchars($nama_kategori) . '" sudah terdaftar.');
            header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
            exit;
        }

        // Simpan ke database
        $stmt = $db->prepare("INSERT INTO categories (nama_kategori, slug) VALUES (:nama, :slug)");
        $stmt->execute([':nama' => $nama_kategori, ':slug' => $slug]);

        set_flash('success', 'Kategori "' . htmlspecialchars($nama_kategori) . '" berhasil ditambahkan.');
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = html_entity_decode(trim($_POST['nama_kategori'] ?? ''), ENT_QUOTES, 'UTF-8');

        if (empty($nama)) {
            set_flash('warning', 'Nama kategori tidak boleh kosong.');
            header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
            exit;
        }

        // Generate slug
        $slug = strtolower($nama);
        $slug = str_replace(' & ', '-', $slug);
        $slug = str_replace('&', '-', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Cek duplikasi
        $check_stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE (nama_kategori = :nama OR slug = :slug) AND id != :id");
        $check_stmt->execute([':nama' => $nama, ':slug' => $slug, ':id' => $id]);
        if ($check_stmt->fetchColumn() > 0) {
            set_flash('danger', 'Nama kategori atau slug sudah terdaftar.');
            header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
            exit;
        }

        // Update
        $stmt = $db->prepare("UPDATE categories SET nama_kategori = :nama, slug = :slug WHERE id = :id");
        $stmt->execute([':nama' => $nama, ':slug' => $slug, ':id' => $id]);

        set_flash('success', 'Kategori berhasil diperbarui.');
    }

    if ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);

        // Hapus kategori
        $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);

        set_flash('success', 'Kategori berhasil dihapus.');
    }
}

header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
exit;
