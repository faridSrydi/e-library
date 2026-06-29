<?php
/**
 * Authentication & Authorization Middleware Helpers
 */
require_once __DIR__ . '/../config/config.php';

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('get_user')) {
    function get_user() {
        if (is_logged_in()) {
            require_once __DIR__ . '/../config/database.php';
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $_SESSION['user_id']]);
                $user = $stmt->fetch();
                if ($user) {
                    return $user;
                }
            } catch (Exception $e) {
                // Fallback to session data
            }
            return [
                'id' => $_SESSION['user_id'],
                'nama' => $_SESSION['user_nama'] ?? '',
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['user_role'] ?? '',
                'foto_profil' => 'default_avatar.png'
            ];
        }
        return null;
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            require_once __DIR__ . '/../config/security.php';
            set_flash('warning', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($allowed_roles = []) {
        require_login();
        $user = get_user();
        if (!in_array($user['role'], (array)$allowed_roles)) {
            require_once __DIR__ . '/../config/security.php';
            set_flash('danger', 'Akses ditolak: Anda tidak memiliki hak akses ke halaman ini.');
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }
    }
}
