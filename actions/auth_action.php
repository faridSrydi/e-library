<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('danger', 'Token Keamanan tidak valid. Silakan coba lagi.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    $db = Database::getConnection();

    if ($action === 'login') {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            set_flash('warning', 'Email dan Password wajib diisi.');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND status_aktif = 1 LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Dukungan demo password fallback jika akun demo
        $is_password_valid = false;
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $is_password_valid = true;
            } elseif (($user['email'] === 'admin@gerbangliterasi.id' && $password === 'admin123') || 
                      ($user['email'] === 'user@gerbangliterasi.id' && $password === 'user123')) {
                // Update hash otomatis agar sesuai standar BCRYPT
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                $update_stmt = $db->prepare("UPDATE users SET password = :hash WHERE id = :id");
                $update_stmt->execute([':hash' => $new_hash, ':id' => $user['id']]);
                $is_password_valid = true;
            }
        }

        if ($user && $is_password_valid) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            set_flash('success', 'Selamat datang kembali, ' . htmlspecialchars($user['nama']) . '!');

            if ($user['role'] === 'admin' || $user['role'] === 'petugas') {
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . 'user/dashboard.php');
            }
            exit;
        } else {
            set_flash('danger', 'Email atau password yang Anda masukkan salah.');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    } 
    elseif ($action === 'register') {
        $nama = sanitize($_POST['nama'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $no_telepon = sanitize($_POST['no_telepon'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($nama) || empty($email) || empty($password)) {
            set_flash('warning', 'Semua kolom bertanda bintang wajib diisi.');
            header('Location: ' . BASE_URL . 'register.php');
            exit;
        }

        // Validasi Panjang Nama Lengkap
        if (strlen($nama) < 3) {
            set_flash('warning', 'Nama lengkap tidak boleh kurang dari 3 karakter.');
            header('Location: ' . BASE_URL . 'register.php');
            exit;
        }

        // Validasi Format Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('warning', 'Format email tidak valid. Pastikan alamat email benar.');
            header('Location: ' . BASE_URL . 'register.php');
            exit;
        }

        // Validasi Panjang Password
        if (strlen($password) < 8) {
            set_flash('warning', 'Password minimal harus 8 karakter.');
            header('Location: ' . BASE_URL . 'register.php');
            exit;
        }

        // Cek apakah email sudah terdaftar
        $check_stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $check_stmt->execute([':email' => $email]);
        if ($check_stmt->fetch()) {
            set_flash('danger', 'Email sudah terdaftar. Silakan gunakan email lain atau login.');
            header('Location: ' . BASE_URL . 'register.php');
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $insert_stmt = $db->prepare("INSERT INTO users (nama, email, password, role, no_telepon) VALUES (:nama, :email, :password, 'anggota', :telepon)");
        $result = $insert_stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':password' => $password_hash,
            ':telepon' => $no_telepon
        ]);

        if ($result) {
            set_flash('success', 'Pendaftaran berhasil! Silakan masuk dengan akun baru Anda.');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        } else {
            set_flash('danger', 'Gagal mendaftar. Silakan coba lagi.');
            header('Location: ' . BASE_URL . 'register.php');
            exit;
        }
    }
}

header('Location: ' . BASE_URL);
exit;
