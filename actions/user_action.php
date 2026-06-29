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
        header('Location: ' . BASE_URL . 'admin/anggota.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            set_flash('danger', 'User tidak ditemukan.');
            header('Location: ' . BASE_URL . 'admin/anggota.php');
            exit;
        }

        // Prevent self-deactivation
        $current_user = get_user();
        if ($current_user['id'] === $user_id) {
            set_flash('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
            header('Location: ' . BASE_URL . 'admin/anggota.php');
            exit;
        }

        $stmt = $db->prepare("SELECT status_aktif FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $new_status = $user['status_aktif'] == 1 ? 0 : 1;
            $update_stmt = $db->prepare("UPDATE users SET status_aktif = :status WHERE id = :id");
            $update_stmt->execute([':status' => $new_status, ':id' => $user_id]);
            
            $status_str = $new_status == 1 ? 'diaktifkan' : 'dinonaktifkan';
            set_flash('success', 'Status anggota berhasil ' . $status_str . '.');
        } else {
            set_flash('danger', 'Anggota tidak ditemukan.');
        }
    }

    if ($action === 'delete') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        if ($user_id <= 0) {
            set_flash('danger', 'User tidak ditemukan.');
            header('Location: ' . BASE_URL . 'admin/anggota.php');
            exit;
        }

        // Prevent self-deletion
        $current_user = get_user();
        if ($current_user['id'] === $user_id) {
            set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri.');
            header('Location: ' . BASE_URL . 'admin/anggota.php');
            exit;
        }

        $delete_stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role = 'anggota'");
        $delete_stmt->execute([':id' => $user_id]);

        if ($delete_stmt->rowCount() > 0) {
            set_flash('success', 'Anggota berhasil dihapus.');
        } else {
            set_flash('danger', 'Gagal menghapus anggota atau anggota tidak ditemukan.');
        }
    }

    header('Location: ' . BASE_URL . 'admin/anggota.php');
    exit;
}

header('Location: ' . BASE_URL . 'admin/anggota.php');
exit;
