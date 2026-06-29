<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'user/dashboard.php');
    exit;
}

$db = Database::getConnection();
$action = $_GET['action'] ?? '';

// ==========================================
// ALUR 1: SIMULASI / MOCK LOGIN (BYPASS API)
// ==========================================
if ($action === 'mock') {
    $email = 'google-demo@gerbangliterasi.id';
    $nama = 'Google Demo Member';

    // Cek apakah user demo sudah terdaftar di database
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Buat user demo baru
        $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $insert_stmt = $db->prepare("INSERT INTO users (nama, email, password, role, status_aktif, foto_profil, oauth_provider) VALUES (:nama, :email, :password, 'anggota', 1, 'default_avatar.png', 'google')");
        $insert_stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':password' => $random_password
        ]);

        // Fetch user yang baru dimasukkan
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
    }

    if ($user) {
        if (empty($user['oauth_provider'])) {
            $update_provider = $db->prepare("UPDATE users SET oauth_provider = 'google' WHERE id = :id");
            $update_provider->execute([':id' => $user['id']]);
        }
        if ($user['status_aktif'] == 0) {
            set_flash('danger', 'Akun demo ditangguhkan.');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }

        // Jalankan session login
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        set_flash('success', 'Simulasi Login Google sukses! Selamat datang, ' . htmlspecialchars($user['nama']) . '!');
        header('Location: ' . BASE_URL . 'user/dashboard.php');
        exit;
    }
}

// ==========================================
// ALUR 2: LOGIN VIA GOOGLE OAUTH 2.0 RIIL
// ==========================================
$redirect_uri = BASE_URL . 'actions/google_auth_action.php';

// Jika tidak ada kode otorisasi dari Google, arahkan ke Google Login
if (!isset($_GET['code'])) {
    $google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => $redirect_uri,
        'response_type' => 'code',
        'scope'         => 'email profile',
        'state'         => 'google_oauth'
    ]);
    header('Location: ' . $google_auth_url);
    exit;
}

// Proses callback kode otorisasi dari Google
$code = $_GET['code'];

// 1. Tukarkan code dengan access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    set_flash('danger', 'Gagal melakukan autentikasi dengan Google (Masa berlaku token habis atau Client ID salah).');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$access_token = $token_data['access_token'];

// 2. Ambil informasi profil pengguna dari Google
$ch_user = curl_init();
curl_setopt($ch_user, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($ch_user, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch_user, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_user, CURLOPT_SSL_VERIFYPEER, false);
$user_response = curl_exec($ch_user);
curl_close($ch_user);

$google_user = json_decode($user_response, true);

if (!isset($google_user['email'])) {
    set_flash('danger', 'Gagal mendapatkan profil pengguna dari Google.');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$email = $google_user['email'];
$nama = $google_user['name'];
$google_picture = $google_user['picture'] ?? '';

// 3. Cari user di database lokal
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    // Daftarkan sebagai user baru otomatis
    $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    $insert_stmt = $db->prepare("INSERT INTO users (nama, email, password, role, status_aktif, foto_profil, oauth_provider) VALUES (:nama, :email, :password, 'anggota', 1, 'default_avatar.png', 'google')");
    $insert_stmt->execute([
        ':nama' => $nama,
        ':email' => $email,
        ':password' => $random_password
    ]);

    // Ambil data user yang baru terdaftar
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
}

if ($user) {
    if (empty($user['oauth_provider'])) {
        $update_provider = $db->prepare("UPDATE users SET oauth_provider = 'google' WHERE id = :id");
        $update_provider->execute([':id' => $user['id']]);
    }
    if ($user['status_aktif'] == 0) {
        set_flash('danger', 'Akun Anda telah dinonaktifkan oleh administrator.');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    // Download & simpan foto profil Google ke lokal jika user belum mengubahnya dari default
    if (!empty($google_picture) && ($user['foto_profil'] === 'default_avatar.png' || empty($user['foto_profil']))) {
        // Gunakan timeout cURL agar loading tidak menggantung
        $ch_img = curl_init();
        curl_setopt($ch_img, CURLOPT_URL, $google_picture);
        curl_setopt($ch_img, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_img, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch_img, CURLOPT_SSL_VERIFYPEER, false);
        $img_data = curl_exec($ch_img);
        curl_close($ch_img);

        if ($img_data) {
            $filename = 'avatar_' . $user['id'] . '_' . time() . '.jpg';
            $save_path = __DIR__ . '/../storage/avatars/' . $filename;
            if (file_put_contents($save_path, $img_data)) {
                $update_img = $db->prepare("UPDATE users SET foto_profil = :img WHERE id = :id");
                $update_img->execute([':img' => $filename, ':id' => $user['id']]);
            }
        }
    }

    // Set Session Login
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nama'] = $user['nama'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    set_flash('success', 'Berhasil masuk dengan akun Google! Selamat datang, ' . htmlspecialchars($user['nama']) . '!');
    
    if ($user['role'] === 'admin' || $user['role'] === 'petugas') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'user/dashboard.php');
    }
    exit;
}

header('Location: ' . BASE_URL . 'login.php');
exit;
