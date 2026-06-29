<?php
$page_title = "Pengaturan Akun";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/format_helper.php';

require_login();
$user = get_user();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
  <?php display_flash(); ?>

  <div class="mb-4">
    <a href="<?= BASE_URL; ?>user/dashboard.php" class="detail-back-btn"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
  </div>

  <div class="row g-4 justify-content-center">
    <!-- Left Column: Avatar Display -->
    <div class="col-md-4 col-lg-3 text-center">
      <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
        <div class="mb-3 position-relative d-inline-block mx-auto">
          <?php 
          $avatar_url = !empty($user['foto_profil']) ? BASE_URL . 'storage/avatars/' . $user['foto_profil'] : '';
          $avatar_path = __DIR__ . '/../storage/avatars/' . ($user['foto_profil'] ?? '');
          if (empty($user['foto_profil']) || !file_exists($avatar_path)) {
              $avatar_url = 'https://placehold.co/150?text=' . urlencode(substr($user['nama'], 0, 1));
          }
          ?>
          <img src="<?= $avatar_url; ?>" alt="Avatar" class="rounded-circle border" style="width: 120px; height: 120px; object-fit: cover;" id="avatarPreview" />
        </div>
        <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['nama']); ?></h5>
        <p class="text-muted small mb-3"><?= htmlspecialchars($user['email']); ?></p>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-semibold" style="font-size:0.75rem;">
          <?= strtoupper($user['role']); ?>
        </span>
      </div>
    </div>

    <!-- Right Column: Settings Form -->
    <div class="col-md-8 col-lg-7">
      <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-header border-bottom bg-white p-4">
          <h5 class="fw-bold mb-0"><i class="bi bi-gear text-primary me-2"></i> Pengaturan Profil</h5>
        </div>
        <div class="card-body p-4">
          <form action="<?= BASE_URL; ?>actions/settings_action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
            
            <h6 class="fw-bold text-muted mb-3 uppercase tracking-wider" style="font-size: 0.75rem;">Informasi Dasar</h6>
            
            <div class="row g-3 mb-4">
              <!-- Name Input -->
              <div class="col-12">
                <label class="form-label small fw-semibold" for="settingsName">Nama Lengkap *</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                  <input type="text" name="nama" class="form-control border-start-0" id="settingsName" value="<?= htmlspecialchars($user['nama']); ?>" required />
                </div>
              </div>

              <!-- Phone Input -->
              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold" for="settingsPhone">No. Telepon / WhatsApp</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-muted"></i></span>
                  <input type="text" name="no_telepon" class="form-control border-start-0" id="settingsPhone" value="<?= htmlspecialchars($user['no_telepon'] ?? ''); ?>" placeholder="081234567890" />
                </div>
              </div>

              <!-- Avatar File Input -->
              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold" for="settingsAvatar">Ganti Foto Profil</label>
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-image text-muted"></i></span>
                  <input type="file" name="foto_profil" class="form-control border-start-0" id="settingsAvatar" accept="image/*" onchange="previewImage(event)" />
                </div>
                <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Format: JPG, PNG, WEBP (Max 2MB)</small>
              </div>

            </div>

            <hr class="my-4 border-slate-100" />
            
            <?php if (($user['oauth_provider'] ?? '') === 'google'): ?>
              <!-- Google OAuth Notice instead of password fields -->
              <div class="alert alert-info border-0 rounded-4 d-flex align-items-center gap-3 p-3.5 mb-4" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0 !important;">
                <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 38px; height: 38px; background: #dcfce7; flex-shrink: 0; color: #15803d;">
                  <i class="bi bi-google fs-5"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0.5" style="font-size: 0.85rem;">Terhubung dengan Google</h6>
                  <p class="mb-0 small" style="opacity: 0.85; font-size: 0.78rem;">Akun Anda terhubung dengan Google. Autentikasi dan keamanan kata sandi Anda dikelola sepenuhnya oleh Google secara aman.</p>
                </div>
              </div>
            <?php else: ?>
              <h6 class="fw-bold text-muted mb-3 uppercase tracking-wider" style="font-size: 0.75rem;">Keamanan Akun (Kosongkan jika tidak ingin mengganti)</h6>

              <div class="row g-3 mb-4">
                <!-- Password Input -->
                <div class="col-12 col-md-6">
                  <label class="form-label small fw-semibold" for="settingsPassword">Password Baru</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 border-end-0" id="settingsPassword" placeholder="Password baru" />
                    <button class="btn btn-light border border-start-0 px-3" type="button" onclick="togglePassword('settingsPassword', 'togglePasswordIcon1')" style="border-color: #dee2e6 !important;">
                      <i class="bi bi-eye text-muted" id="togglePasswordIcon1"></i>
                    </button>
                  </div>
                </div>

                <!-- Password Confirm Input -->
                <div class="col-12 col-md-6">
                  <label class="form-label small fw-semibold" for="settingsConfirm">Konfirmasi Password Baru</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
                    <input type="password" name="confirm_password" class="form-control border-start-0 border-end-0" id="settingsConfirm" placeholder="Ulangi password baru" />
                    <button class="btn btn-light border border-start-0 px-3" type="button" onclick="togglePassword('settingsConfirm', 'togglePasswordIcon2')" style="border-color: #dee2e6 !important;">
                      <i class="bi bi-eye text-muted" id="togglePasswordIcon2"></i>
                    </button>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-primary-custom" style="padding: 10px 24px; font-size: 0.88rem;">
                <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function previewImage(event) {
  const reader = new FileReader();
  reader.onload = function(){
    const output = document.getElementById('avatarPreview');
    output.src = reader.result;
  };
  reader.readAsDataURL(event.target.files[0]);
}

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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
