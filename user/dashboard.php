<?php
$page_title = "Rak Buku Saya & Antrean";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/format_helper.php';
require_once __DIR__ . '/../helpers/borrow_helper.php';

require_login();
$user = get_user();
$db = Database::getConnection();

// Run auto expiration check
process_auto_expiration($db);

// Fetch Active Borrowings (Rak Buku Saya)
$active_stmt = $db->prepare("SELECT b.*, bk.judul, bk.cover_image, bk.file_ebook, bk.pengarang, bk.slug FROM borrowings b JOIN books bk ON b.book_id = bk.id WHERE b.user_id = :uid AND b.status = 'dipinjam' ORDER BY b.id DESC");
$active_stmt->execute([':uid' => $user['id']]);
$active_borrowings = $active_stmt->fetchAll();

// Fetch Queued Borrowings (Antrean Saya)
$queue_stmt = $db->prepare("SELECT b.*, bk.judul, bk.cover_image, bk.pengarang, bk.id as book_id FROM borrowings b JOIN books bk ON b.book_id = bk.id WHERE b.user_id = :uid AND b.status = 'antre' ORDER BY b.id DESC");
$queue_stmt->execute([':uid' => $user['id']]);
$queued_borrowings = $queue_stmt->fetchAll();

// Fetch History
$history_stmt = $db->prepare("
  SELECT b.*, bk.id as book_id, bk.judul, bk.pengarang, bk.cover_image, bk.slug,
         r.rating as user_rating,
         (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE book_id = bk.id) as avg_rating
  FROM borrowings b 
  JOIN books bk ON b.book_id = bk.id 
  LEFT JOIN reviews r ON r.book_id = bk.id AND r.user_id = b.user_id
  WHERE b.user_id = :uid AND b.status IN ('dikembalikan', 'ditolak') 
  ORDER BY b.id DESC
");
$history_stmt->execute([':uid' => $user['id']]);
$history_borrowings = $history_stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-4">
  <?php display_flash(); ?>

  <!-- Welcome Header Banner -->
  <div class="card border-0 rounded-4 shadow-sm mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="card-body p-4 text-white position-relative">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 position-relative" style="z-index: 2;">
        <div>
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-primary-subtle text-primary fw-semibold px-2.5 py-1" style="font-size: 0.75rem; border-radius: 6px;">
              <i class="bi bi-shield-check me-1"></i>Anggota Digital
            </span>
          </div>
          <h4 class="fw-bold mb-1" style="font-size: 1.35rem; color: #ffffff;">Selamat datang kembali, <?= htmlspecialchars($user['nama']); ?>! 👋</h4>
          <p class="text-slate-300 small mb-0" style="color: #94a3b8; font-size: 0.85rem;">Kelola koleksi e-book aktif, pantau lisensi digital, dan baca kapan saja tanpa khawatir denda.</p>
        </div>
        <a href="<?= BASE_URL; ?>katalog" class="btn btn-primary-custom d-inline-flex align-items-center gap-2" style="font-size: 0.85rem; padding: 0.55rem 1.25rem;">
          <i class="bi bi-search"></i> Jelajahi Katalog
        </a>
      </div>
    </div>
  </div>

  <!-- Main Grid Layout -->
  <div class="row g-4">
    <!-- Left Column: Active Books & Queue -->
    <div class="col-lg-8">
      <!-- Stat Cards -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-sm-4">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
              <div class="stat-icon blue" style="width: 44px; height: 44px; border-radius: 12px; background: #deeefb; color: #2a8fe1; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                <i class="bi bi-book"></i>
              </div>
              <div class="min-w-0">
                <h3 class="fw-bold mb-0" style="font-size: 1.35rem; color: var(--text); line-height: 1.2;"><?= count($active_borrowings); ?></h3>
                <span class="text-muted text-truncate d-block" style="font-size: 0.75rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;">Buku Aktif</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-4">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
              <div class="stat-icon amber" style="width: 44px; height: 44px; border-radius: 12px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                <i class="bi bi-clock-history"></i>
              </div>
              <div class="min-w-0">
                <h3 class="fw-bold mb-0" style="font-size: 1.35rem; color: var(--text); line-height: 1.2;"><?= count($queued_borrowings); ?></h3>
                <span class="text-muted text-truncate d-block" style="font-size: 0.75rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;">Dalam Antrean</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-4">
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center gap-3">
              <div class="stat-icon green" style="width: 44px; height: 44px; border-radius: 12px; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                <i class="bi bi-check-circle"></i>
              </div>
              <div class="min-w-0">
                <h3 class="fw-bold mb-0" style="font-size: 1.35rem; color: var(--text); line-height: 1.2;"><?= count($history_borrowings); ?></h3>
                <span class="text-muted text-truncate d-block" style="font-size: 0.75rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px;">Selesai Dibaca</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 1. Rak Buku Aktif Saya -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-white p-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
          <h6 class="fw-semibold mb-0" style="font-size: 0.95rem; color: var(--text);">
            <i class="bi bi-collection-play me-2 text-primary"></i>Rak Buku Aktif Saya
          </h6>
          <span class="text-muted small" style="font-size: 0.75rem;">
            <i class="bi bi-info-circle me-1 text-primary"></i>Pengembalian otomatis saat masa berlaku habis
          </span>
        </div>
        <div class="card-body p-3 p-sm-4">
          <?php if (empty($active_borrowings)): ?>
            <div class="text-center py-5">
              <div class="mb-3 text-muted opacity-50" style="font-size: 2.8rem;">
                <i class="bi bi-journal-x"></i>
              </div>
              <h6 class="fw-semibold mb-1" style="font-size: 0.95rem; color: var(--text);">Rak Buku Anda Sedang Kosong</h6>
              <p class="text-muted small mb-3" style="max-width: 380px; margin: 0 auto; font-size: 0.8rem;">Anda belum meminjam e-book saat ini. Temukan ribuan koleksi e-book menarik di katalog digital kami!</p>
              <a href="<?= BASE_URL; ?>katalog" class="btn btn-sm btn-primary-custom" style="padding: 0.45rem 1rem; font-size: 0.82rem;">
                <i class="bi bi-search me-1"></i>Cari E-Book Sekarang
              </a>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($active_borrowings as $b): ?>
                <?php 
                  $iso_expire = date('c', strtotime($b['tanggal_jatuh_tempo'])); 
                  $sisa_waktu = hitung_sisa_waktu($b['tanggal_jatuh_tempo']);
                ?>
                <div class="col-12 col-md-6">
                  <div class="card h-100 border p-3 rounded-4 shadow-sm bg-white hover-shadow transition-all">
                    <div class="d-flex gap-3">
                      <div class="d-flex align-items-center justify-content-center bg-light border rounded flex-shrink-0" style="width: 75px; height: 105px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                        <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($b['cover_image']); ?>" alt="Cover" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                        <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8; font-size:1.5rem;">
                          <i class="bi bi-journal-text"></i>
                        </div>
                      </div>
                      <div class="flex-grow-1" style="min-width: 0;">
                        <h6 class="fw-semibold mb-1 text-dark" style="font-size: 0.9rem; line-height: 1.35; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($b['judul']); ?></h6>
                        <p class="text-muted small mb-2 text-truncate" style="font-size: 0.78rem;"><?= htmlspecialchars($b['pengarang']); ?></p>
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle mb-1.5 live-countdown d-inline-flex align-items-center gap-1" data-expire="<?= $iso_expire; ?>" style="font-size: 0.72rem; padding: 0.35rem 0.6rem; border-radius: 6px;">
                          <i class="bi bi-clock"></i><?= $sisa_waktu; ?>
                        </span>
                        <div class="text-muted" style="font-size: 0.72rem;">Batas: <?= format_tanggal($b['tanggal_jatuh_tempo']); ?></div>
                      </div>
                    </div>
                    <div class="mt-3 pt-2 border-top d-flex align-items-center gap-2">
                      <a href="<?= BASE_URL; ?>baca-ebook/<?= $b['slug']; ?>" class="btn btn-sm btn-success flex-grow-1 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.8rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                        <i class="bi bi-book-half"></i> Membaca
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalKembalikan<?= $b['id']; ?>" style="font-size: 0.8rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
                        <i class="bi bi-box-arrow-right"></i> Kembalikan
                      </button>
                    </div>

                    <!-- Modal Konfirmasi Kembalikan -->
                    <div class="modal fade" id="modalKembalikan<?= $b['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
                        <div class="modal-content rounded-4 border-0 shadow">
                          <div class="modal-header border-bottom pb-3">
                            <h6 class="modal-title fw-semibold text-danger" style="font-size: 0.95rem;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Pengembalian E-Book</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body p-4">
                            <p class="mb-2 text-dark" style="font-size: 0.85rem;">Apakah Anda yakin ingin mengembalikan e-book ini lebih awal?</p>
                            <div class="p-3 bg-light rounded-3 border d-flex align-items-center gap-3">
                              <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($b['cover_image']); ?>" style="width: 45px; height: 62px; object-fit: cover;" class="rounded shadow-sm" onerror="this.src='https://placehold.co/45x62?text=📖'" />
                              <div style="min-width: 0;">
                                <h6 class="fw-semibold text-dark mb-1 text-truncate" style="font-size: 0.88rem;"><?= htmlspecialchars($b['judul']); ?></h6>
                                <p class="text-muted small mb-0 text-truncate" style="font-size: 0.75rem;"><?= htmlspecialchars($b['pengarang']); ?></p>
                              </div>
                            </div>
                            <small class="text-muted d-block mt-3" style="font-size: 0.75rem;"><i class="bi bi-info-circle me-1"></i>Lisensi digital akan langsung dilepaskan agar pengguna dalam antrean dapat membaca.</small>
                          </div>
                          <div class="modal-footer border-top bg-light">
                            <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal" style="font-size: 0.82rem;">Batal</button>
                            <form action="<?= BASE_URL; ?>actions/borrow_action.php?action=kembalikan" method="POST" class="m-0">
                              <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
                              <input type="hidden" name="borrow_id" value="<?= $b['id']; ?>" />
                              <button type="submit" class="btn btn-sm btn-danger px-4 fw-semibold" style="font-size: 0.82rem;">Ya, Kembalikan</button>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- 2. Daftar Antrean Saya -->
      <?php if (!empty($queued_borrowings)): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
          <div class="card-header bg-white p-3 px-4 border-bottom">
            <h6 class="fw-semibold mb-0 text-warning" style="font-size: 0.95rem;"><i class="bi bi-hourglass-split me-2"></i>Daftar Antrean Lisensi Saya</h6>
          </div>
          <div class="card-body p-3 p-sm-4">
            <div class="row g-3">
              <?php foreach ($queued_borrowings as $q): ?>
                <?php $pos = get_user_queue_position($db, $user['id'], $q['book_id']); ?>
                <div class="col-12 col-md-6">
                  <div class="card h-100 border p-3 rounded-4 bg-light">
                    <div class="d-flex gap-3">
                      <div class="d-flex align-items-center justify-content-center bg-light border rounded flex-shrink-0" style="width: 65px; height: 90px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                        <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($q['cover_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                        <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8; font-size:1.3rem;">
                          <i class="bi bi-journal-text"></i>
                        </div>
                      </div>
                      <div class="flex-grow-1" style="min-width: 0;">
                        <h6 class="fw-semibold mb-1 text-dark text-truncate" style="font-size: 0.88rem;"><?= htmlspecialchars($q['judul']); ?></h6>
                        <span class="badge bg-warning text-dark mb-1.5" style="font-size: 0.72rem;">Posisi: Antrean ke-<?= $pos; ?></span>
                        <div class="text-muted" style="font-size: 0.72rem;">Otomatis aktif saat lisensi tersedia.</div>
                      </div>
                    </div>
                    <div class="mt-3 pt-2 border-top text-end">
                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalBatalAntrean<?= $q['id']; ?>" style="font-size: 0.78rem; padding: 0.35rem 0.75rem; border-radius: 6px;">
                        Batalkan Antrean
                      </button>
                    </div>

                    <!-- Modal Konfirmasi Batal Antrean -->
                    <div class="modal fade" id="modalBatalAntrean<?= $q['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
                        <div class="modal-content rounded-4 border-0 shadow">
                          <div class="modal-header border-bottom pb-3">
                            <h6 class="modal-title fw-semibold text-danger" style="font-size: 0.95rem;"><i class="bi bi-exclamation-triangle me-2"></i>Batalkan Antrean?</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body p-4">
                            <p class="mb-0 text-dark" style="font-size: 0.85rem;">Apakah Anda yakin ingin mengundurkan diri dari antrean buku <strong><?= htmlspecialchars($q['judul']); ?></strong>?</p>
                          </div>
                          <div class="modal-footer border-top bg-light">
                            <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal" style="font-size: 0.82rem;">Batal</button>
                            <form action="<?= BASE_URL; ?>actions/borrow_action.php?action=batal_antrean" method="POST" class="m-0">
                              <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
                              <input type="hidden" name="borrow_id" value="<?= $q['id']; ?>" />
                              <button type="submit" class="btn btn-sm btn-danger px-4 fw-semibold" style="font-size: 0.82rem;">Ya, Batalkan</button>
                            </form>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Right Column: Reading History (col-lg-4) -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 90px; z-index: 10;">
        <div class="card-header bg-white p-3 px-4 border-bottom d-flex align-items-center justify-content-between">
          <h6 class="fw-semibold mb-0" style="font-size: 0.95rem; color: var(--text);">
            <i class="bi bi-clock-history me-2 text-secondary"></i>Riwayat Membaca
          </h6>
          <span class="badge bg-light text-muted border"><?= count($history_borrowings); ?> Buku</span>
        </div>
        <div class="card-body p-0">
          <?php if (empty($history_borrowings)): ?>
            <div class="text-center py-4 px-3">
              <i class="bi bi-journal-check" style="font-size: 2rem; color: var(--text-muted);"></i>
              <p class="text-muted small mb-0 mt-2" style="font-size: 0.8rem;">Belum ada riwayat peminjaman selesai.</p>
            </div>
          <?php else: ?>
            <ul class="list-unstyled mb-0">
              <?php foreach (array_slice($history_borrowings, 0, 5) as $i => $h): ?>
                <?php $disp_r = !empty($h['user_rating']) ? $h['user_rating'] : (!empty($h['avg_rating']) ? $h['avg_rating'] : 0); ?>
                <li>
                  <a href="<?= BASE_URL; ?>detail-buku/<?= $h['slug']; ?>" class="text-decoration-none text-dark d-flex align-items-center gap-3 p-3 <?= $i < min(count($history_borrowings), 5) - 1 ? 'border-bottom' : ''; ?>" style="transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <div class="d-flex align-items-center justify-content-center bg-light border rounded flex-shrink-0" style="width: 42px; height: 60px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                      <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($h['cover_image']); ?>" alt="Cover"
                           style="width: 100%; height: 100%; object-fit: cover;"
                           onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                      <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8; font-size:1.1rem;">
                        <i class="bi bi-journal-text"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1" style="min-width: 0;">
                      <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                        <h6 class="mb-0 fw-semibold text-truncate text-dark" style="font-size: 0.84rem; line-height: 1.3;"><?= htmlspecialchars($h['judul']); ?></h6>
                        <?php if ($disp_r > 0): ?>
                          <span class="text-warning flex-shrink-0 d-inline-flex align-items-center gap-1" style="font-size: 0.76rem; font-weight: 600; background: #fff8e6; padding: 2px 6px; border-radius: 4px;" title="<?= !empty($h['user_rating']) ? 'Ulasan Anda' : 'Rating Rata-rata'; ?>">
                            <i class="bi bi-star-fill"></i><?= number_format($disp_r, 1); ?>
                          </span>
                        <?php endif; ?>
                      </div>
                      <small class="text-muted d-block" style="font-size: 0.72rem;">Selesai: <?= format_tanggal($h['tanggal_kembali'] ?? $h['tanggal_jatuh_tempo']); ?></small>
                    </div>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
            <?php if (count($history_borrowings) > 5): ?>
              <div class="text-center py-2.5 bg-light border-top">
                <a href="#fullHistory" class="text-decoration-none small fw-semibold text-primary" style="font-size: 0.8rem;" data-bs-toggle="collapse">
                  Lihat Selengkapnya (<?= count($history_borrowings); ?> buku) <i class="bi bi-chevron-down ms-1"></i>
                </a>
              </div>
              <div class="collapse" id="fullHistory">
                <ul class="list-unstyled mb-0">
                  <?php foreach (array_slice($history_borrowings, 5) as $h): ?>
                    <?php $disp_r = !empty($h['user_rating']) ? $h['user_rating'] : (!empty($h['avg_rating']) ? $h['avg_rating'] : 0); ?>
                    <li>
                      <a href="<?= BASE_URL; ?>detail-buku/<?= $h['slug']; ?>" class="text-decoration-none text-dark d-flex align-items-center gap-3 p-3 border-top" style="transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <div class="d-flex align-items-center justify-content-center bg-light border rounded flex-shrink-0" style="width: 42px; height: 60px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
                          <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($h['cover_image']); ?>" alt="Cover"
                               style="width: 100%; height: 100%; object-fit: cover;"
                               onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                          <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8; font-size:1.1rem;">
                            <i class="bi bi-journal-text"></i>
                          </div>
                        </div>
                        <div class="flex-grow-1" style="min-width: 0;">
                          <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                            <h6 class="mb-0 fw-semibold text-truncate text-dark" style="font-size: 0.84rem; line-height: 1.3;"><?= htmlspecialchars($h['judul']); ?></h6>
                            <?php if ($disp_r > 0): ?>
                              <span class="text-warning flex-shrink-0 d-inline-flex align-items-center gap-1" style="font-size: 0.76rem; font-weight: 600; background: #fff8e6; padding: 2px 6px; border-radius: 4px;" title="<?= !empty($h['user_rating']) ? 'Ulasan Anda' : 'Rating Rata-rata'; ?>">
                                <i class="bi bi-star-fill"></i><?= number_format($disp_r, 1); ?>
                              </span>
                            <?php endif; ?>
                          </div>
                          <small class="text-muted d-block" style="font-size: 0.72rem;">Selesai: <?= format_tanggal($h['tanggal_kembali'] ?? $h['tanggal_jatuh_tempo']); ?></small>
                        </div>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Live Real-Time Ticking Countdown Timer Script
function startLiveCountdown() {
  const timers = document.querySelectorAll('.live-countdown');
  
  function updateTimers() {
    const now = new Date().getTime();
    
    timers.forEach(timer => {
      const expireStr = timer.getAttribute('data-expire');
      if (!expireStr) return;
      
      const expireTime = new Date(expireStr).getTime();
      const diff = expireTime - now;
      
      if (diff <= 0) {
        timer.className = "badge bg-danger mb-1";
        timer.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i>Masa Baca Habis';
        setTimeout(() => { location.reload(); }, 2000);
      } else {
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        let displayStr = 'Sisa ';
        if (days > 0) {
          displayStr += days + 'h ' + hours + 'jam ' + minutes + 'm ' + seconds + 's';
        } else {
          displayStr += hours + ' jam ' + minutes + ' menit ' + seconds + ' detik';
        }
        
        timer.innerHTML = '<i class="bi bi-clock me-1"></i>' + displayStr;
      }
    });
  }
  
  updateTimers();
  setInterval(updateTimers, 1000);
}

document.addEventListener('DOMContentLoaded', startLiveCountdown);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
