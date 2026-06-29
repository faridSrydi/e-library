<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

require_role(['admin', 'petugas']);
$user = get_user();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — Panel Admin' : 'Admin Dashboard — Gerbang Literasi'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="<?= BASE_URL; ?>assets/css/style.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </head>
  <body id="adminDashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand">
        <div class="brand-logo">
          <i class="bi bi-book-half"></i> <span>Gerbang Literasi</span>
        </div>
        <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Collapse sidebar">
          <i class="bi bi-chevron-double-left"></i>
        </button>
      </div>
      <nav class="sidebar-nav">
        <a href="<?= BASE_URL; ?>admin/dashboard.php" class="sidebar-link <?= ($active_admin_nav ?? '') === 'dashboard' ? 'active' : ''; ?>">
          <i class="bi bi-grid-1x2"></i> <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL; ?>admin/kelola-buku.php" class="sidebar-link <?= ($active_admin_nav ?? '') === 'buku' ? 'active' : ''; ?>">
          <i class="bi bi-book"></i> <span>Kelola Buku</span>
        </a>
        <a href="<?= BASE_URL; ?>admin/peminjaman.php" class="sidebar-link <?= ($active_admin_nav ?? '') === 'peminjaman' ? 'active' : ''; ?>">
          <i class="bi bi-arrow-left-right"></i> <span>Transaksi Peminjaman</span>
        </a>
        <a href="<?= BASE_URL; ?>admin/anggota.php" class="sidebar-link <?= ($active_admin_nav ?? '') === 'anggota' ? 'active' : ''; ?>">
          <i class="bi bi-people"></i> <span>Kelola Anggota</span>
        </a>
        <a href="<?= BASE_URL; ?>index.php" class="sidebar-link" target="_blank">
          <i class="bi bi-globe"></i> <span>Lihat Website Utama</span>
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="<?= BASE_URL; ?>logout.php" class="sidebar-link text-danger">
          <i class="bi bi-box-arrow-left"></i> <span>Keluar</span>
        </a>
      </div>
    </aside>

    <main class="main-content">
      <div class="topbar">
        <div class="d-flex align-items-center gap-3">
          <button class="sidebar-toggle btn btn-light border" id="sidebarToggleBtn" title="Toggle sidebar">
            <i class="bi bi-list"></i>
          </button>
          <span class="topbar-title">Admin Dashboard<span class="d-none d-md-inline"> — Gerbang Literasi</span></span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php 
          $sidebar_avatar_url = '';
          if (!empty($user['foto_profil'])) {
              $sidebar_avatar_path = __DIR__ . '/../storage/avatars/' . $user['foto_profil'];
              if (file_exists($sidebar_avatar_path)) {
                  $sidebar_avatar_url = BASE_URL . 'storage/avatars/' . $user['foto_profil'];
              }
          }
          if (empty($sidebar_avatar_url)) {
              $sidebar_avatar_url = 'https://placehold.co/100?text=' . urlencode(substr($user['nama'], 0, 1));
          }
          ?>
          <img src="<?= $sidebar_avatar_url; ?>" alt="Avatar" class="rounded-circle border" style="width:36px; height:36px; object-fit: cover;" />
          <div class="d-none d-sm-block">
            <div class="small fw-semibold"><?= htmlspecialchars($user['nama']); ?></div>
            <div class="text-muted" style="font-size: 0.72rem"><?= strtoupper($user['role']); ?></div>
          </div>
        </div>
      </div>

      <div class="content-area">
        <?php display_flash(); ?>

    <script>
    (function() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');
      const collapseBtn = document.getElementById('sidebarCollapseBtn');
      const toggleBtn = document.getElementById('sidebarToggleBtn');
      const STORAGE_KEY = 'sidebar_collapsed';

      // Restore collapsed state from localStorage (desktop only)
      function isMobile() {
        return window.innerWidth < 992;
      }

      function restoreState() {
        if (!isMobile() && localStorage.getItem(STORAGE_KEY) === '1') {
          sidebar.classList.add('collapsed');
        }
      }

      // Desktop: collapse/expand sidebar
      function toggleCollapse() {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem(STORAGE_KEY, sidebar.classList.contains('collapsed') ? '1' : '0');
      }

      // Mobile: show/hide sidebar overlay
      function toggleMobile() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
      }

      // Close mobile overlay
      function closeMobile() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
      }

      // Collapse button always collapses/expands (works on both mobile/desktop)
      collapseBtn.addEventListener('click', function() {
        if (isMobile()) {
          closeMobile();
        } else {
          toggleCollapse();
        }
      });

      // Topbar hamburger button
      toggleBtn.addEventListener('click', function() {
        if (isMobile()) {
          toggleMobile();
        } else {
          toggleCollapse();
        }
      });

      // Overlay click closes mobile sidebar
      overlay.addEventListener('click', closeMobile);

      // Restore on load
      restoreState();
    })();
    </script>
