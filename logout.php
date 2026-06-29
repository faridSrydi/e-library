<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/security.php';

session_unset();
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
set_flash('info', 'Anda telah berhasil keluar dari akun.');
header('Location: ' . BASE_URL . 'login.php');
exit;
