<nav class="navbar navbar-expand-lg main-navbar">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL; ?>">
      <img src="<?= BASE_URL; ?>assets/img/icon.png" alt="Logo" style="height: 52px; width: auto;" />
    </a>
    <button class="navbar-toggler border-0 custom-toggler" type="button" id="drawerToggle" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
      <div class="hamburger-icon">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </button>
    <div class="navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto me-3 gap-1">
        <li class="nav-item">
          <a class="nav-link fw-medium <?= ($active_nav ?? '') === 'beranda' ? 'active' : ''; ?>" href="<?= BASE_URL; ?>">Beranda</a>
        </li>
        <li class="nav-item">
          <a class="nav-link fw-medium <?= ($active_nav ?? '') === 'katalog' ? 'active' : ''; ?>" href="<?= BASE_URL; ?>katalog">Katalog Buku</a>
        </li>
      </ul>
      <div class="d-flex gap-2 align-items-center">
        <?php if (is_logged_in()): ?>
          <?php if ($current_user['role'] === 'admin' || $current_user['role'] === 'petugas'): ?>
            <a href="<?= BASE_URL; ?>admin/dashboard" class="btn btn-outline-custom" style="padding: 8px 16px; font-size: 0.85rem">
              <i class="bi bi-speedometer2 me-1"></i> Dashboard Admin
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL; ?>user/dashboard" class="btn btn-outline-custom" style="padding: 8px 16px; font-size: 0.85rem">
              <i class="bi bi-journal-bookmark me-1"></i> Rak Buku Saya
            </a>
          <?php endif; ?>
          <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
              <?php 
              $avatar_url = !empty($current_user['foto_profil']) ? BASE_URL . 'storage/avatars/' . $current_user['foto_profil'] : '';
              $avatar_path = __DIR__ . '/../storage/avatars/' . ($current_user['foto_profil'] ?? '');
              if (empty($current_user['foto_profil']) || !file_exists($avatar_path)) {
                  $avatar_url = 'https://placehold.co/100?text=' . urlencode(substr($current_user['nama'], 0, 1));
              }
              ?>
              <img src="<?= $avatar_url; ?>" alt="Avatar" class="rounded-circle border" style="width: 26px; height: 26px; object-fit: cover;" />
              <span class="small fw-semibold"><?= htmlspecialchars($current_user['nama']); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              <li><span class="dropdown-item-text text-muted small">Role: <strong><?= strtoupper($current_user['role']); ?></strong></span></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item small" href="<?= BASE_URL; ?>user/settings"><i class="bi bi-gear me-2"></i>Pengaturan Akun</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger small" href="<?= BASE_URL; ?>logout"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= BASE_URL; ?>login" class="btn btn-outline-custom" style="padding: 8px 20px; font-size: 0.85rem">Masuk</a>
          <a href="<?= BASE_URL; ?>register" class="btn btn-primary-custom" style="padding: 8px 20px; font-size: 0.85rem">Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const navbar = document.querySelector('.main-navbar');
  if (!navbar) return;
  
  function checkScroll() {
    if (window.scrollY > 20) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }
  
  window.addEventListener('scroll', checkScroll);
  checkScroll();

  // ── Custom Drawer Toggle ──
  const toggleBtn = document.getElementById('drawerToggle');
  const navMenu = document.getElementById('navMenu');
  if (!toggleBtn || !navMenu) return;

  function openDrawer() {
    navMenu.classList.add('show');
    toggleBtn.setAttribute('aria-expanded', 'true');
  }

  function closeDrawer() {
    navMenu.classList.remove('show');
    toggleBtn.setAttribute('aria-expanded', 'false');
  }

  toggleBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    if (navMenu.classList.contains('show')) {
      closeDrawer();
    } else {
      openDrawer();
    }
  });

  // Close drawer when clicking outside
  document.addEventListener('click', function(e) {
    if (navMenu.classList.contains('show') && !navMenu.contains(e.target) && !toggleBtn.contains(e.target)) {
      closeDrawer();
    }
  });

  // Close drawer on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && navMenu.classList.contains('show')) {
      closeDrawer();
    }
  });

  // Close drawer on window resize to desktop
  window.addEventListener('resize', function() {
    if (window.innerWidth >= 992 && navMenu.classList.contains('show')) {
      closeDrawer();
    }
  });
});
</script>

<?php if (isset($_SESSION['flash'])): ?>
<div class="container mt-3">
  <?php display_flash(); ?>
</div>
<?php endif; ?>
