<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

require_role(['admin', 'petugas']);

$action = $_GET['action'] ?? '';
$db = Database::getConnection();

function generate_unique_book_slug($db, $title, $book_id = 0) {
    // replace non-letter or digits by -
    $slug = preg_replace('~[^\pL\d]+~u', '-', $title);
    // transliterate
    $slug = iconv('utf-8', 'us-ascii//TRANSLIT', $slug);
    // remove unwanted characters
    $slug = preg_replace('~[^-\w]+~', '', $slug);
    // trim
    $slug = trim($slug, '-');
    // remove duplicate -
    $slug = preg_replace('~-+~', '-', $slug);
    // lowercase
    $slug = strtolower($slug);
    if (empty($slug)) {
        $slug = 'n-a';
    }
    
    // Check if unique
    $check_stmt = $db->prepare("SELECT id FROM books WHERE slug = :slug AND id != :id");
    $check_stmt->execute([':slug' => $slug, ':id' => $book_id]);
    if ($check_stmt->fetch()) {
        $slug = $slug . '-' . time() . rand(10, 99);
    }
    return $slug;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token keamanan tidak valid.');
        header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
        exit;
    }

    if ($action === 'tambah' || $action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $judul = sanitize($_POST['judul'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 1);
        $pengarang = sanitize($_POST['pengarang'] ?? '');
        $penerbit = sanitize($_POST['penerbit'] ?? '');
        $tahun_terbit = (int)($_POST['tahun_terbit'] ?? date('Y'));
        $isbn = sanitize($_POST['isbn'] ?? '');
        $stok = (int)($_POST['stok'] ?? 0);
        $deskripsi = sanitize($_POST['deskripsi'] ?? '');

        if (empty($judul) || empty($pengarang)) {
            set_flash('warning', 'Judul dan Pengarang wajib diisi.');
            header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
            exit;
        }

        // Handle File Cover Upload
        $cover_name = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $cover_name = 'cover_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (!is_dir(STORAGE_PATH . 'covers/')) {
                    mkdir(STORAGE_PATH . 'covers/', 0777, true);
                }
                move_uploaded_file($_FILES['cover_image']['tmp_name'], STORAGE_PATH . 'covers/' . $cover_name);
                // Juga simpan ke assets/img/ jika dikonsumsi publik
                @copy(STORAGE_PATH . 'covers/' . $cover_name, __DIR__ . '/../assets/img/' . $cover_name);
            }
        }

        // Handle File E-Book PDF Upload
        $ebook_name = null;
        if (isset($_FILES['file_ebook']) && $_FILES['file_ebook']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['file_ebook']['name'], PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $ebook_name = 'ebook_' . time() . '_' . rand(100, 999) . '.pdf';
                if (!is_dir(STORAGE_PATH . 'ebooks/')) {
                    mkdir(STORAGE_PATH . 'ebooks/', 0777, true);
                }
                move_uploaded_file($_FILES['file_ebook']['tmp_name'], STORAGE_PATH . 'ebooks/' . $ebook_name);
            }
        }

        if ($action === 'tambah') {
            $slug = generate_unique_book_slug($db, $judul);
            $cover_final = $cover_name ?? 'default_cover.jpg';
            $stmt = $db->prepare("INSERT INTO books (category_id, slug, judul, pengarang, penerbit, tahun_terbit, isbn, stok, deskripsi, cover_image, file_ebook) VALUES (:cat, :slug, :judul, :pengarang, :penerbit, :tahun, :isbn, :stok, :deskripsi, :cover, :ebook)");
            $stmt->execute([
                ':cat' => $category_id,
                ':slug' => $slug,
                ':judul' => $judul,
                ':pengarang' => $pengarang,
                ':penerbit' => $penerbit,
                ':tahun' => $tahun_terbit,
                ':isbn' => $isbn,
                ':stok' => $stok,
                ':deskripsi' => $deskripsi,
                ':cover' => $cover_final,
                ':ebook' => $ebook_name
            ]);
            set_flash('success', 'Buku baru berhasil ditambahkan.');
        } else {
            // Edit
            $slug = generate_unique_book_slug($db, $judul, $id);
            $sql = "UPDATE books SET category_id=:cat, slug=:slug, judul=:judul, pengarang=:pengarang, penerbit=:penerbit, tahun_terbit=:tahun, isbn=:isbn, stok=:stok, deskripsi=:deskripsi";
            $params = [
                ':cat' => $category_id,
                ':slug' => $slug,
                ':judul' => $judul,
                ':pengarang' => $pengarang,
                ':penerbit' => $penerbit,
                ':tahun' => $tahun_terbit,
                ':isbn' => $isbn,
                ':stok' => $stok,
                ':deskripsi' => $deskripsi,
                ':id' => $id
            ];
            if ($cover_name) {
                $sql .= ", cover_image=:cover";
                $params[':cover'] = $cover_name;
            }
            if ($ebook_name) {
                $sql .= ", file_ebook=:ebook";
                $params[':ebook'] = $ebook_name;
            }
            $sql .= " WHERE id=:id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            set_flash('success', 'Data buku berhasil diperbarui.');
        }
        header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
        exit;
    }

    if ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM books WHERE id = :id");
        $stmt->execute([':id' => $id]);
        set_flash('success', 'Buku berhasil dihapus.');
        header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
        exit;
    }
}

header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
exit;
