<?php
$page_title = "Manajemen Lisensi Digital & Antrean";
$active_admin_nav = "peminjaman";
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/format_helper.php';
require_once __DIR__ . '/../helpers/borrow_helper.php';

$db = Database::getConnection();
process_auto_expiration($db);

// Fetch Semua Peminjaman & Antrean
$stmt = $db->query("SELECT b.*, u.nama as nama_user, u.email as email_user, bk.judul as judul_buku FROM borrowings b JOIN users u ON b.user_id = u.id JOIN books bk ON b.book_id = bk.id ORDER BY b.id DESC");
$borrows = $stmt->fetchAll();
?>

<div class="mb-4">
  <h5 class="fw-semibold mb-1" style="font-size: 1.1rem; color: var(--text); letter-spacing: -0.2px;">Manajemen Lisensi Digital & Antrean</h5>
  <p class="text-muted small mb-0">Pantau peminjaman aktif, batas waktu otomatis kembali, dan daftar antrean lisensi pengguna.</p>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Anggota / Pemohon</th>
          <th>Buku Digital</th>
          <th>Tgl Pinjam / Pengajuan</th>
          <th>Batas Waktu Otomatis Kembali</th>
          <th>Status Lisensi</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($borrows)): ?>
          <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi peminjaman atau antrean.</td></tr>
        <?php else: ?>
          <?php foreach ($borrows as $b): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($b['nama_user']); ?></strong>
                <div class="text-muted small"><?= htmlspecialchars($b['email_user']); ?></div>
              </td>
              <td><strong><?= htmlspecialchars($b['judul_buku']); ?></strong></td>
              <td><?= format_tanggal($b['tanggal_pinjam'] ?: $b['created_at']); ?></td>
              <td>
                <?php if ($b['status'] === 'dipinjam'): ?>
                  <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i><?= hitung_sisa_waktu($b['tanggal_jatuh_tempo']); ?></span>
                  <div class="text-muted small"><?= format_tanggal($b['tanggal_jatuh_tempo']); ?></div>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($b['status'] === 'dipinjam'): ?>
                  <span class="badge bg-success"><i class="bi bi-play-circle me-1"></i>LISENSI AKTIF</span>
                <?php elseif ($b['status'] === 'antre'): ?>
                  <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>DALAM ANTREAN</span>
                <?php elseif ($b['status'] === 'dikembalikan'): ?>
                  <span class="badge bg-secondary">OTOMATIS KEMBALI</span>
                <?php else: ?>
                  <span class="badge bg-danger"><?= strtoupper($b['status']); ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($b['status'] === 'dipinjam'): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalResetLisensi<?= $b['id']; ?>">
                    Reset Lisensi
                  </button>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
            </tr>

            <?php if ($b['status'] === 'dipinjam'): ?>
            <!-- Modal Reset Lisensi -->
            <div class="modal fade" id="modalResetLisensi<?= $b['id']; ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content rounded-4 border-0 shadow">
                  <div class="modal-header border-bottom pb-3">
                    <h6 class="modal-title fw-semibold text-danger" style="font-size: 0.95rem;">
                      <i class="bi bi-exclamation-triangle-fill me-2"></i>Reset Lisensi?
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body p-4">
                    <p class="mb-2 text-dark" style="font-size: 0.85rem;">Apakah Anda yakin ingin mencabut lisensi / memulihkan stok buku ini?</p>
                    <div class="p-3 bg-light rounded-3 border">
                      <div class="fw-semibold text-dark text-truncate" style="font-size: 0.88rem;"><?= htmlspecialchars($b['judul_buku']); ?></div>
                      <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Peminjam: <?= htmlspecialchars($b['nama_user']); ?> (<?= htmlspecialchars($b['email_user']); ?>)</small>
                    </div>
                    <small class="text-muted d-block mt-3" style="font-size: 0.75rem;">
                      <i class="bi bi-info-circle me-1"></i>Tindakan ini akan menghentikan akses membaca user tersebut dan mengembalikan kuota lisensi buku.
                    </small>
                  </div>
                  <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal" style="font-size: 0.82rem;">Batal</button>
                    <form action="<?= BASE_URL; ?>actions/borrow_action.php?action=kembalikan" method="POST" class="m-0">
                      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
                      <input type="hidden" name="borrow_id" value="<?= $b['id']; ?>" />
                      <button type="submit" class="btn btn-sm btn-danger px-4 fw-semibold" style="font-size: 0.82rem;">Ya, Reset</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>
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
