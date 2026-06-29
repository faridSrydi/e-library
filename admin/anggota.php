<?php
$page_title = "Manajemen Anggota";
$active_admin_nav = "anggota";
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/format_helper.php';

$db = Database::getConnection();

// Fetch Semua Anggota (role = 'anggota')
$stmt = $db->query("SELECT * FROM users WHERE role = 'anggota' ORDER BY id DESC");
$members = $stmt->fetchAll();
?>

<div class="mb-4">
  <h5 class="fw-semibold mb-1" style="font-size: 1.1rem; color: var(--text); letter-spacing: -0.2px;">Manajemen Anggota Perpustakaan</h5>
  <p class="text-muted small mb-0">Kelola status aktif/nonaktif anggota dan hapus akun anggota terdaftar.</p>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>No</th>
          <th>Nama Anggota</th>
          <th>Email</th>
          <th>No. Telepon</th>
          <th>Tanggal Bergabung</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($members)): ?>
          <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada anggota yang mendaftar.</td></tr>
        <?php else: ?>
          <?php foreach ($members as $i => $m): ?>
            <tr>
              <td><?= $i + 1; ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar bg-light text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle" style="width:32px; height:32px; font-size: 0.85rem;">
                    <?= strtoupper(substr($m['nama'], 0, 1)); ?>
                  </div>
                  <strong><?= htmlspecialchars($m['nama']); ?></strong>
                </div>
              </td>
              <td><?= htmlspecialchars($m['email']); ?></td>
              <td><?= htmlspecialchars($m['no_telepon'] ?: '-'); ?></td>
              <td><?= format_tanggal($m['created_at']); ?></td>
              <td>
                <?php if ($m['status_aktif'] == 1): ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.7rem; padding: 4px 8px;">AKTIF</span>
                <?php else: ?>
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.7rem; padding: 4px 8px;">DIBLOKIR</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex gap-2">
                  <!-- Toggle Status Form -->
                  <form action="<?= BASE_URL; ?>actions/user_action.php?action=toggle_status" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
                    <input type="hidden" name="user_id" value="<?= $m['id']; ?>" />
                    <button type="submit" class="btn btn-sm <?= $m['status_aktif'] == 1 ? 'btn-outline-warning' : 'btn-outline-success'; ?>" style="font-size:0.75rem;">
                      <?= $m['status_aktif'] == 1 ? '<i class="bi bi-slash-circle me-1"></i>Blokir' : '<i class="bi bi-check-circle me-1"></i>Aktifkan'; ?>
                    </button>
                  </form>

                  <!-- Delete Button (triggers modal) -->
                  <button type="button" class="btn btn-sm btn-outline-danger" style="font-size:0.75rem;"
                    data-bs-toggle="modal" data-bs-target="#modalHapusAnggota<?= $m['id']; ?>">
                    <i class="bi bi-trash me-1"></i>Hapus
                  </button>
                </div>
              </td>
            </tr>

            <!-- Modal Hapus Anggota -->
            <div class="modal fade" id="modalHapusAnggota<?= $m['id']; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content rounded-4 border-0 shadow">
                  <div class="modal-header border-bottom pb-3">
                    <h6 class="modal-title fw-semibold text-danger" style="font-size: 0.95rem;">
                      <i class="bi bi-exclamation-triangle-fill me-2"></i>Hapus Anggota?
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body p-4">
                    <p class="mb-2 text-dark" style="font-size: 0.85rem;">Apakah Anda yakin ingin menghapus anggota ini secara permanen?</p>
                    <div class="p-3 bg-light rounded-3 border d-flex align-items-center gap-3">
                      <div class="avatar bg-danger-subtle text-danger fw-bold d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width:40px; height:40px; font-size: 0.95rem;">
                        <?= strtoupper(substr($m['nama'], 0, 1)); ?>
                      </div>
                      <div style="min-width: 0;">
                        <h6 class="fw-semibold text-dark mb-0 text-truncate" style="font-size: 0.88rem;"><?= htmlspecialchars($m['nama']); ?></h6>
                        <small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($m['email']); ?></small>
                      </div>
                    </div>
                    <small class="text-muted d-block mt-3" style="font-size: 0.75rem;">
                      <i class="bi bi-info-circle me-1"></i>Tindakan ini tidak dapat dibatalkan. Semua data anggota akan dihapus permanen.
                    </small>
                  </div>
                  <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal" style="font-size: 0.82rem;">Batal</button>
                    <form action="<?= BASE_URL; ?>actions/user_action.php?action=delete" method="POST" class="m-0">
                      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
                      <input type="hidden" name="user_id" value="<?= $m['id']; ?>" />
                      <button type="submit" class="btn btn-sm btn-danger px-4 fw-semibold" style="font-size: 0.82rem;">Ya, Hapus</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>

          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

      </div> <!-- content-area -->
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
