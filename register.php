<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/helpers/auth_helper.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'user/dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar — Gerbang Literasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="<?= BASE_URL; ?>assets/css/style.css" rel="stylesheet" />
  </head>
  <body class="login-body">
    <div class="login-page">
      <div class="login-image">
        <div class="login-image-content">
          <h2>Bergabung dengan<br />Gerbang Literasi</h2>
          <p>
            Daftar gratis dan mulai pinjam ribuan buku dari koleksi perpustakaan
            digital kami.
          </p>
          <div class="login-image-stats">
            <div class="stat-item">
              <div class="stat-num">Gratis</div>
              <div class="stat-text">Pendaftaran</div>
            </div>
            <div class="stat-item">
              <div class="stat-num">7 Hari</div>
              <div class="stat-text">Masa Pinjam</div>
            </div>
            <div class="stat-item">
              <div class="stat-num">3 Buku</div>
              <div class="stat-text">Maks. Pinjam</div>
            </div>
          </div>
        </div>
      </div>
      <div class="login-form-side">
        <div class="login-form-inner">
          <div class="mb-3">
            <a href="<?= BASE_URL; ?>" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda</a>
          </div>
          <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 52px; height: 52px; border-radius: 12px; background: var(--primary);">
              <i class="bi bi-person-plus text-white fs-5"></i>
            </div>
            <h4 class="fw-bold mb-1">Buat Akun Baru</h4>
            <p class="text-muted small mb-0">Daftar untuk mulai meminjam buku</p>
          </div>

          <?php display_flash(); ?>

          <form action="<?= BASE_URL; ?>actions/auth_action.php?action=register" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
            <div class="mb-3">
              <label class="form-label" for="regName">Nama Lengkap *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="nama" class="form-control" id="regName" placeholder="Nama lengkap Anda" minlength="3" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label" for="regEmail">Email *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" id="regEmail" placeholder="email@example.com" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label" for="regPhone">No. Telepon / WhatsApp</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                <input type="text" name="no_telepon" class="form-control" id="regPhone" placeholder="081234567890" />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label" for="regPassword">Password *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control border-end-0" id="regPassword" placeholder="Min. 8 karakter" minlength="8" required />
                <button class="btn btn-light border border-start-0 px-3" type="button" onclick="togglePassword('regPassword', 'togglePasswordIcon')" style="border-color: #dee2e6 !important;">
                  <i class="bi bi-eye text-muted" id="togglePasswordIcon"></i>
                </button>
              </div>
            </div>
            <button type="submit" class="btn btn-primary-custom w-100 mb-3">
              <i class="bi bi-person-plus me-2"></i>Daftar Sekarang
            </button>

            <!-- Divider -->
            <div class="d-flex align-items-center my-3">
              <hr class="flex-grow-1 text-muted" style="opacity: 0.15;" />
              <span class="mx-3 text-muted small fw-medium" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">atau</span>
              <hr class="flex-grow-1 text-muted" style="opacity: 0.15;" />
            </div>

            <!-- Google Register Button -->
            <a href="<?= BASE_URL; ?>actions/google_auth_action.php" class="btn btn-outline-dark w-100 mb-3 d-flex align-items-center justify-content-center gap-2 border border-slate-200" style="padding: 10px 16px; font-size: 0.88rem; font-weight: 500; background: #ffffff; color: #334155; border-radius: 12px; transition: all 0.2s ease;">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="18px" height="18px">
                <path fill="#fbc02d" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z" />
                <path fill="#e53935" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z" />
                <path fill="#4caf50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z" />
                <path fill="#1565c0" d="M43.611 20.083 43.5 20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z" />
              </svg>
              <span>Daftar dengan Google</span>
            </a>

            <p class="text-center text-muted small mb-0">
              Sudah punya akun? <a href="<?= BASE_URL; ?>login.php">Masuk di sini</a>
            </p>
          </form>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      if (input && icon) {
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('bi-eye');
          icon.classList.add('bi-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.remove('bi-eye-slash');
          icon.classList.add('bi-eye');
        }
      }
    }
    </script>
  </body>
</html>
