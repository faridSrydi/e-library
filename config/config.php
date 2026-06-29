<?php
/**
 * Konfigurasi Utama Aplikasi & Inisialisasi Session Aman
 */

if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan Session Aman (Production Standard)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Otomatisasi Penentuan BASE_URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base_path = rtrim($script_name, '/');

// Jika script berada di dalam folder admin/user/actions/dll, naikkan ke root path
$base_path = preg_replace('/\\/(admin|user|actions|includes|config|helpers)$/', '', $base_path);

define('BASE_URL', $protocol . $host . $base_path . '/');
define('STORAGE_PATH', __DIR__ . '/../storage/');

// Aturan Fleksibilitas Durasi Peminjaman E-Perpus (Dalam Hari)
define('DURASI_PINJAM_MIN', 1);     // Minimal 1 hari
define('DURASI_PINJAM_MAX', 14);    // Maksimal 14 hari
define('DURASI_PINJAM_DEFAULT', 7); // Default 7 hari

// Google OAuth 2.0 Credentials
define('GOOGLE_CLIENT_ID', '619026806779-vur18ele3bb2an3r4so6ai06fij7i7f7.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

