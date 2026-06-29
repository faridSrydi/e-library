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
        header('Location: ' . BASE_URL . 'user/settings.php');
        exit;
    }

    $nama = trim(sanitize($_POST['nama'] ?? ''));
    $no_telepon = trim(sanitize($_POST['no_telepon'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($nama)) {
        set_flash('warning', 'Nama lengkap wajib diisi.');
        header('Location: ' . BASE_URL . 'user/settings.php');
        exit;
    }

    // Prepare update query
    $update_fields = ["nama = :nama", "no_telepon = :no_telepon"];
    $params = [
        ':nama' => $nama,
        ':no_telepon' => $no_telepon,
        ':id' => $user['id']
    ];

    // Handle File Upload (Foto Profil)
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto_profil'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed_types)) {
            set_flash('danger', 'Tipe file tidak didukung. Gunakan JPG, PNG, atau WEBP.');
            header('Location: ' . BASE_URL . 'user/settings.php');
            exit;
        }

        if ($file['size'] > $max_size) {
            set_flash('danger', 'Ukuran file terlalu besar. Maksimal 2MB.');
            header('Location: ' . BASE_URL . 'user/settings.php');
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
        $target_dir = __DIR__ . '/../storage/avatars/';
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Delete old avatar if exists and not default
            if (!empty($user['foto_profil']) && $user['foto_profil'] !== 'default_avatar.png') {
                $old_file = $target_dir . $user['foto_profil'];
                if (file_exists($old_file)) {
                    @unlink($old_file);
                }
            }
            $update_fields[] = "foto_profil = :foto_profil";
            $params[':foto_profil'] = $filename;
        } else {
            set_flash('danger', 'Gagal mengupload foto profil.');
            header('Location: ' . BASE_URL . 'user/settings.php');
            exit;
        }
    }

    // Handle Password Change (Hanya untuk pengguna non-Google)
    if (!empty($password) && ($user['oauth_provider'] ?? '') !== 'google') {
        if (strlen($password) < 6) {
            set_flash('warning', 'Password minimal 6 karakter.');
            header('Location: ' . BASE_URL . 'user/settings.php');
            exit;
        }
        if ($password !== $confirm_password) {
            set_flash('warning', 'Konfirmasi password tidak cocok.');
            header('Location: ' . BASE_URL . 'user/settings.php');
            exit;
        }

        $update_fields[] = "password = :password";
        $params[':password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Update session name just in case
    $_SESSION['user_nama'] = $nama;

    set_flash('success', 'Pengaturan akun berhasil disimpan.');
    header('Location: ' . BASE_URL . 'user/settings.php');
    exit;
}

header('Location: ' . BASE_URL . 'user/settings.php');
exit;
