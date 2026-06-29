<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/helpers/auth_helper.php';
require_once __DIR__ . '/helpers/borrow_helper.php';

$db = Database::getConnection();

// Run auto expiration check
process_auto_expiration($db);

$slug = sanitize($_GET['slug'] ?? '');
$book_id = (int)($_GET['id'] ?? 0);

if (empty($slug) && $book_id <= 0) {
    header('Location: ' . BASE_URL . 'katalog');
    exit;
}

if (!empty($slug)) {
    $stmt = $db->prepare("SELECT b.*, c.nama_kategori FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
} else {
    $stmt = $db->prepare("SELECT b.*, c.nama_kategori FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = :id LIMIT 1");
    $stmt->execute([':id' => $book_id]);
}
$book = $stmt->fetch();

if (!$book) {
    header('Location: ' . BASE_URL . 'katalog');
    exit;
}

// Canonical URL redirection for clean URL detail-buku/slug
if (strpos($_SERVER['REQUEST_URI'], 'detail-buku.php') !== false) {
    header('Location: ' . BASE_URL . 'detail-buku/' . $book['slug'], true, 301);
    exit;
}

$book_id = (int)$book['id'];

$current_user = get_user();

// Cek status peminjaman / antrean user login
$borrow_status = null;
$user_borrow_data = null;
if (is_logged_in()) {
    $check_borrow = $db->prepare("SELECT * FROM borrowings WHERE user_id = :uid AND book_id = :bid AND status IN ('dipinjam', 'antre') LIMIT 1");
    $check_borrow->execute([':uid' => $current_user['id'], ':bid' => $book_id]);
    $user_borrow_data = $check_borrow->fetch();
    if ($user_borrow_data) {
        $borrow_status = $user_borrow_data['status'];
    }
}

// Cek posisi antrean jika user sedang antre
$queue_position = 0;
if ($borrow_status === 'antre') {
    $queue_position = get_user_queue_position($db, $current_user['id'], $book_id);
}

// Total antrean buku ini secara umum
$total_queue_stmt = $db->prepare("SELECT COUNT(*) FROM borrowings WHERE book_id = :bid AND status = 'antre'");
$total_queue_stmt->execute([':bid' => $book_id]);
$total_queue = (int)$total_queue_stmt->fetchColumn();

// Ambil Daftar Ulasan / Review Buku
$reviews_stmt = $db->prepare("SELECT r.*, u.nama, u.foto_profil FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.book_id = :bid ORDER BY r.created_at DESC");
$reviews_stmt->execute([':bid' => $book_id]);
$reviews = $reviews_stmt->fetchAll();

// Hitung Statistik Ulasan
$total_reviews = count($reviews);
$rating_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$sum_ratings = 0;
foreach ($reviews as $rev) {
    $r = (int)$rev['rating'];
    if ($r >= 1 && $r <= 5) {
        $rating_counts[$r]++;
        $sum_ratings += $r;
    }
}
$avg_rating = $total_reviews > 0 ? round($sum_ratings / $total_reviews, 1) : (float)$book['rating'];

// Update rating buku di DB jika perlu
if ($book['rating'] != $avg_rating && $total_reviews > 0) {
    $up_rat = $db->prepare("UPDATE books SET rating = :rat WHERE id = :id");
    $up_rat->execute([':rat' => $avg_rating, ':id' => $book_id]);
    $book['rating'] = $avg_rating;
}

// Ambil Buku Terkait (Kategori sama)
$related_stmt = $db->prepare("SELECT b.*, c.nama_kategori, COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE book_id = b.id), 0.0) as rating FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.category_id = :cat AND b.id != :bid ORDER BY RAND() LIMIT 4");
$related_stmt->execute([':cat' => $book['category_id'], ':bid' => $book_id]);
$related_books = $related_stmt->fetchAll();

$page_title = htmlspecialchars($book['judul']) . " — Gerbang Literasi";
$active_nav = "katalog";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4 pt-4 pt-md-5">
  <!-- Top Link Kembali ke Katalog -->
  <div class="mb-4 mt-2">
    <a href="<?= BASE_URL; ?>katalog" class="btn btn-light border rounded-pill px-3 py-1.5 text-secondary small fw-semibold shadow-sm d-inline-flex align-items-center gap-2" style="font-size: 0.82rem; background: #ffffff;">
      <i class="bi bi-arrow-left"></i> Kembali ke Katalog
    </a>
  </div>

  <!-- Bagian Utama Detail Buku (Polos Tanpa Card Besar Outer) -->
  <div class="row g-4 g-lg-5 mb-5 align-items-start">
    <!-- Cover Buku Sebelah Kiri -->
    <div class="col-12 col-md-4 col-lg-custom-cover text-center">
      <div class="w-100">
        <div class="detail-cover-wrapper">
          <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($book['cover_image']); ?>" alt="<?= htmlspecialchars($book['judul']); ?>" class="detail-cover-img rounded-4 shadow-sm" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
          <div class="rounded-4 shadow-sm align-items-center justify-content-center flex-column text-white p-4 text-center" style="display:none; aspect-ratio:3/4; background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
            <i class="bi bi-book display-3 mb-2 opacity-50"></i>
            <h6 class="fw-bold mb-1"><?= htmlspecialchars($book['judul']); ?></h6>
            <span class="small opacity-75"><?= htmlspecialchars($book['pengarang']); ?></span>
          </div>


        </div>
      </div>
    </div>

    <!-- Informasi Detail Sebelah Kanan -->
    <div class="col-12 col-md-8 col-lg-custom-info">
      <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold" style="font-size: 0.8rem;">
          <?= htmlspecialchars($book['nama_kategori'] ?? 'Umum'); ?>
        </span>
        <span class="text-warning fw-bold small"><i class="bi bi-star-fill me-1"></i><?= number_format($book['rating'], 1); ?> / 5.0</span>
      </div>

      <h2 class="fw-bold mb-1 text-dark" style="font-size: calc(1.3rem + 0.8vw); letter-spacing: -0.3px;"><?= htmlspecialchars($book['judul']); ?></h2>
      <p class="text-muted mb-4" style="font-size: 0.95rem;">Oleh <strong class="text-secondary"><?= htmlspecialchars($book['pengarang']); ?></strong></p>

      <!-- Row 3 Kotak Informasi (Penerbit, ISBN, Lisensi) -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-sm-4">
          <div class="p-3 bg-white border rounded-4 shadow-sm d-flex align-items-center gap-3 h-100 detail-info-card">
            <div class="p-2.5 rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
              <i class="bi bi-building fs-5"></i>
            </div>
            <div class="min-w-0">
              <span class="text-uppercase text-muted d-block fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">PENERBIT</span>
              <strong class="text-dark small text-truncate d-block" title="<?= htmlspecialchars($book['penerbit'] ?: '-'); ?>"><?= htmlspecialchars($book['penerbit'] ?: '-'); ?> (<?= htmlspecialchars($book['tahun_terbit']); ?>)</strong>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-4">
          <div class="p-3 bg-white border rounded-4 shadow-sm d-flex align-items-center gap-3 h-100 detail-info-card">
            <div class="p-2.5 rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
              <i class="bi bi-upc-scan fs-5"></i>
            </div>
            <div class="min-w-0">
              <span class="text-uppercase text-muted d-block fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">ISBN</span>
              <strong class="text-dark small text-truncate d-block" title="<?= htmlspecialchars($book['isbn'] ?: '-'); ?>"><?= htmlspecialchars($book['isbn'] ?: '-'); ?></strong>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-4">
          <div class="p-3 bg-white border rounded-4 shadow-sm d-flex align-items-center gap-3 h-100 detail-info-card">
            <div class="p-2.5 rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
              <i class="bi bi-shield-check fs-5"></i>
            </div>
            <div class="min-w-0">
              <span class="text-uppercase text-muted d-block fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">LISENSI E-PERPUS DRM</span>
              <strong class="<?= $book['stok'] > 0 ? 'text-success' : 'text-danger'; ?> small text-truncate d-block">
                <i class="bi bi-check-circle-fill me-1"></i><?= $book['stok'] > 0 ? 'Tersedia (' . $book['stok'] . ' Lisensi)' : 'Lisensi Penuh'; ?>
              </strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Sinopsis / Deskripsi Buku -->
      <h6 class="fw-bold text-dark mb-2" style="font-size: 0.95rem;">Sinopsis / Deskripsi Buku</h6>
      <div class="p-4 bg-light border rounded-4 text-secondary lh-lg mb-4" style="font-size: 0.92rem; background: #f8fafc !important;">
        <?= nl2br(htmlspecialchars($book['deskripsi'] ?: 'Belum ada deskripsi untuk buku ini.')); ?>
      </div>

      <!-- Banner Aksi Peminjaman / Login (Kuning Amber Persis Screenshot) -->
      <?php if (is_logged_in()): ?>
        <?php if ($borrow_status === 'dipinjam'): ?>
          <div class="p-3 rounded-4 border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 shadow-sm" style="background: #ecfdf5; border-color: #a7f3d0 !important;">
            <div class="d-flex align-items-center gap-2 text-success fw-semibold small">
              <i class="bi bi-check-circle-fill fs-5"></i> Anda sedang meminjam buku ini secara aktif.
            </div>
            <div class="d-flex gap-2">
              <a href="<?= BASE_URL; ?>baca-ebook/<?= $book['slug']; ?>" class="btn btn-success rounded-3 px-4 py-2 fw-semibold shadow-sm text-nowrap" style="font-size: 0.88rem;">
                <i class="bi bi-book-half me-1"></i> Baca Sekarang
              </a>
              <a href="<?= BASE_URL; ?>user/dashboard" class="btn btn-outline-secondary rounded-3 px-3 py-2 text-nowrap" style="font-size: 0.88rem;">Rak Saya</a>
            </div>
          </div>
        <?php elseif ($borrow_status === 'antre'): ?>
          <div class="p-3 rounded-4 border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 shadow-sm" style="background: #fffbeb; border-color: #fde68a !important;">
            <div class="d-flex align-items-center gap-2 text-warning-emphasis fw-semibold small">
              <i class="bi bi-clock-history fs-5"></i> Anda dalam antrean lisensi (Posisi ke-<?= $queue_position; ?>).
            </div>
            <form action="<?= BASE_URL; ?>actions/borrow_action.php?action=batal_antrean" method="POST" class="m-0">
              <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
              <input type="hidden" name="borrow_id" value="<?= $user_borrow_data['id']; ?>" />
              <button type="submit" class="btn btn-outline-danger rounded-3 px-3 py-1.5 text-nowrap" style="font-size: 0.82rem;">Batal Antrean</button>
            </form>
          </div>
        <?php else: ?>
          <?php if ($book['stok'] > 0): ?>
            <div class="p-3 rounded-4 border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 shadow-sm" style="background: #f0f9ff; border-color: #bae6fd !important;">
              <div class="d-flex align-items-center gap-2 text-primary fw-semibold small">
                <i class="bi bi-info-circle-fill fs-5"></i> Lisensi digital tersedia untuk dipinjam secara langsung.
              </div>
              <button type="button" class="btn btn-primary-custom rounded-3 px-4 py-2 fw-semibold shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalPinjam" style="font-size: 0.88rem;">
                <i class="bi bi-journal-plus me-1"></i> Pinjam E-Book Digital
              </button>
            </div>
          <?php else: ?>
            <div class="p-3 rounded-4 border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 shadow-sm" style="background: #fffbeb; border-color: #fde68a !important;">
              <div class="d-flex align-items-center gap-2 text-warning-emphasis fw-semibold small">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i> Lisensi penuh saat ini. Masuk antrean untuk peminjaman otomatis.
              </div>
              <form action="<?= BASE_URL; ?>actions/borrow_action.php?action=pinjam" method="POST" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
                <input type="hidden" name="book_id" value="<?= $book['id']; ?>" />
                <button type="submit" class="btn btn-warning text-dark rounded-3 px-4 py-2 fw-semibold shadow-sm text-nowrap" style="font-size: 0.88rem;">
                  Masuk Antrean
                </button>
              </form>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php else: ?>
        <!-- Amber Login Banner (Sleek & Kompak!) -->
        <div class="p-2.5 rounded-4 border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 shadow-sm" style="background: #fef3c7; border-color: #fde68a !important; padding: 0.6rem 1rem !important; max-width: 640px;">
          <div class="d-flex align-items-center gap-2 text-dark small fw-medium" style="font-size: 0.84rem;">
            <i class="bi bi-exclamation-triangle text-warning fs-5"></i> Silakan masuk ke akun Anda untuk meminjam atau mengantre lisensi buku digital.
          </div>
          <a href="<?= BASE_URL; ?>login.php" class="btn btn-primary-custom rounded-3 px-3.5 py-1.5 fw-semibold text-nowrap shadow-sm" style="font-size: 0.82rem;">
            Masuk Sekarang
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Garis Pembatas Abu & Spacing Gap Antara Detail dan Ulasan -->
  <hr class="my-5" style="border-color: #e2e8f0; opacity: 0.8;" />

  <!-- Section Ulasan & Rating Pembaca (Layout 2 Kolom Kompak & Rapi) -->
  <div class="mb-5">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
      <div>
        <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2" style="font-size: 1.15rem;">
          <i class="bi bi-chat-square-text text-primary"></i> Ulasan & Rating Pembaca
        </h5>
        <p class="text-muted small mb-0" style="font-size: 0.84rem;">Pendapat dan pengalaman nyata dari pembaca buku ini.</p>
      </div>
      <?php if (!is_logged_in()): ?>
        <a href="<?= BASE_URL; ?>login.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1.5 small fw-semibold" style="font-size: 0.8rem; background: #fff;">
          <i class="bi bi-box-arrow-in-right me-1"></i> Masuk untuk Memberi Ulasan
        </a>
      <?php endif; ?>
    </div>

    <div class="row g-4 align-items-start">
      <!-- Kolom Kiri: Statistik Rating & Form Input Ulasan -->
      <div class="col-12 col-lg-5">
        <!-- Card Ringkasan Rating (Warna Dark Navy & Tinggi Pas) -->
        <div class="card border rounded-4 p-3 mb-4 shadow-sm bg-white overflow-hidden" style="min-height: 180px;">
          <div class="row align-items-center g-3 h-100">
            <!-- Box Kiri Warna Dark Navy (Persis Gambar 2) -->
            <div class="col-5 text-center">
              <div class="p-3 rounded-4 text-white d-flex flex-column align-items-center justify-content-center h-100 shadow-sm" style="background: #0b132b; border-radius: 16px !important; min-height: 155px;">
                <div class="text-uppercase small fw-bold mb-1 opacity-75" style="letter-spacing: 0.8px; font-size: 0.62rem; color: #94a3b8;">RATA-RATA RATING</div>
                <div class="fw-bold text-white mb-1" style="font-size: 2.8rem; font-weight: 800; line-height: 1;"><?= number_format($avg_rating, 1); ?></div>
                <div class="small mb-1.5" style="color: #f59e0b; font-size: 0.9rem;">
                  <?php
                  for ($i = 1; $i <= 5; $i++) {
                      echo $i <= round($avg_rating) ? '<i class="bi bi-star-fill me-0.5"></i>' : '<i class="bi bi-star me-0.5" style="color: #475569;"></i>';
                  }
                  ?>
                </div>
                <div class="extra-small opacity-75" style="color: #cbd5e1; font-size: 0.72rem;"><?= $total_reviews; ?> Ulasan Terverifikasi</div>
              </div>
            </div>

            <!-- Box Kanan Breakdown Progress Bar -->
            <div class="col-7 ps-2 pe-3">
              <div class="d-flex flex-column gap-2 py-1">
                <?php for ($star = 5; $star >= 1; $star--): ?>
                  <?php 
                  $cnt = $rating_counts[$star];
                  $pct = $total_reviews > 0 ? round(($cnt / $total_reviews) * 100) : 0;
                  ?>
                  <div class="d-flex align-items-center gap-2" style="font-size: 0.78rem;">
                    <span class="text-muted fw-semibold flex-shrink-0 d-flex align-items-center gap-1" style="width: 32px; font-size: 0.78rem;"><?= $star; ?> <i class="bi bi-star-fill text-warning" style="font-size: 0.7rem;"></i></span>
                    <div class="progress flex-grow-1" style="height: 7px; background: #f1f5f9; border-radius: 10px;">
                      <div class="progress-bar bg-warning rounded-pill" style="width: <?= $pct; ?>%;"></div>
                    </div>
                    <span class="text-muted extra-small flex-shrink-0" style="width: 22px; text-align: right; font-weight: 600; font-size: 0.78rem;"><?= $cnt; ?></span>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Ulasan jika Logged In -->
        <?php if (is_logged_in()): ?>
          <?php
          $my_rev = null;
          foreach ($reviews as $rv) {
              if ((int)$rv['user_id'] === (int)$current_user['id']) {
                  $my_rev = $rv;
                  break;
              }
          }
          ?>
          <div class="card border rounded-4 p-4 shadow-sm bg-white">
            <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2" style="font-size: 0.95rem;">
              <i class="bi bi-pencil-square text-primary"></i> <?= $my_rev ? 'Perbarui Ulasan Anda' : 'Tulis Ulasan Anda'; ?>
            </h6>
            <form action="<?= BASE_URL; ?>actions/review_action.php" method="POST">
              <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
              <input type="hidden" name="book_id" value="<?= $book['id']; ?>" />
              <input type="hidden" name="redirect_to" value="<?= BASE_URL; ?>detail-buku/<?= $book['slug']; ?>" />
              
              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Pilih Rating *</label>
                <div class="star-rating-selector d-flex gap-2 text-warning fs-4" id="starSelector" style="cursor: pointer;">
                  <?php $cur_r = $my_rev ? (int)$my_rev['rating'] : 5; ?>
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="bi bi-star-fill star-item <?= $s <= $cur_r ? 'selected' : 'text-muted opacity-25'; ?>" data-value="<?= $s; ?>"></i>
                  <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="<?= $cur_r; ?>" required />
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary" for="ulasanText">Ulasan / Pendapat (Opsional)</label>
                <textarea name="ulasan" id="ulasanText" rows="3" class="form-control rounded-3 small" placeholder="Tulis ulasan singkat Anda mengenai buku ini..."><?= htmlspecialchars($my_rev['ulasan'] ?? ''); ?></textarea>
              </div>

              <button type="submit" class="btn btn-primary-custom px-4 py-2 fw-semibold rounded-3 w-100" style="font-size: 0.85rem;">
                <i class="bi <?= $my_rev ? 'bi-pencil-square' : 'bi-send'; ?> me-1"></i> <?= $my_rev ? 'Edit Ulasan' : 'Kirim Ulasan'; ?>
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <!-- Kolom Kanan: Kumpulan Ulasan Pembaca -->
      <div class="col-12 col-lg-7">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
          <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2" style="font-size: 0.95rem;">
            <i class="bi bi-chat-left-text text-primary"></i>Ulasan Pembaca <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-1" style="font-size: 0.72rem;"><?= count($reviews); ?></span>
          </h6>
        </div>

        <div class="d-flex flex-column gap-3">
          <?php if (empty($reviews)): ?>
            <div class="p-4 p-md-5 rounded-4 border text-center bg-white shadow-sm d-flex flex-column align-items-center justify-content-center" style="min-height: 200px;">
              <div class="mb-2"><i class="bi bi-chat-left-dots text-muted opacity-50" style="font-size: 2.2rem;"></i></div>
              <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">Belum Ada Ulasan</h6>
              <p class="text-muted small mb-0" style="font-size: 0.84rem;">Jadilah pembaca pertama yang membagikan ulasan dan penilaian untuk buku ini!</p>
            </div>
          <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
              <div class="p-3.5 rounded-4 border bg-white shadow-sm" style="padding: 1rem 1.2rem;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="d-flex align-items-center gap-2.5">
                    <?php 
                    $av = !empty($rev['foto_profil']) ? BASE_URL . 'storage/avatars/' . $rev['foto_profil'] : 'https://placehold.co/100?text=' . urlencode(substr($rev['nama'], 0, 1));
                    ?>
                    <img src="<?= $av; ?>" alt="Avatar" class="rounded-circle border" style="width: 36px; height: 36px; object-fit: cover;" />
                    <div>
                      <h6 class="fw-semibold mb-0 text-dark" style="font-size: 0.88rem;"><?= htmlspecialchars($rev['nama']); ?></h6>
                      <span class="text-muted" style="font-size: 0.72rem;"><?= date('d M Y, H:i', strtotime($rev['created_at'])); ?> WIB</span>
                    </div>
                  </div>
                  <div class="text-warning small" style="font-size: 0.8rem;">
                    <?php for ($st = 1; $st <= 5; $st++): ?>
                      <i class="bi bi-star<?= $st <= (int)$rev['rating'] ? '-fill' : ' text-muted opacity-25'; ?>"></i>
                    <?php endfor; ?>
                  </div>
                </div>
                <?php if (!empty($rev['ulasan'])): ?>
                  <p class="text-secondary small mb-0 lh-base" style="font-size: 0.85rem; color: #475569 !important;"><?= nl2br(htmlspecialchars($rev['ulasan'])); ?></p>
                <?php else: ?>
                  <span class="text-muted fst-italic small" style="font-size: 0.78rem;">(Memberikan rating tanpa ulasan tertulis)</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Section Bawah: Buku Lainnya dalam Kategori (Dengan Jarak/Gap Jelas) -->
  <?php if (!empty($related_books)): ?>
  <div class="mt-5 pt-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div>
        <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2" style="font-size: 1.1rem;">
          <i class="bi bi-journal-album text-primary"></i> Buku Lainnya dalam Kategori <span class="text-primary"><?= htmlspecialchars($book['nama_kategori'] ?? 'Koleksi'); ?></span>
        </h5>
      </div>
      <a href="<?= BASE_URL; ?>katalog?cat=<?= $book['category_id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 small fw-semibold" style="font-size: 0.8rem; background: #fff;">Lihat Semua</a>
    </div>

    <div class="row g-4">
      <?php foreach ($related_books as $index => $book_item): ?>
        <div class="col-6 col-md-4 col-lg-2">
          <div class="book-card h-100 d-flex flex-column" onclick="location.href='<?= BASE_URL; ?>detail-buku/<?= $book_item['slug']; ?>'" style="cursor: pointer; --i:<?= $index; ?>;">
            <div class="book-card__cover">
              <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($book_item['cover_image']); ?>" alt="<?= htmlspecialchars($book_item['judul']); ?>" onerror="this.src='https://placehold.co/300x400?text=Cover+Buku'" />
              <?php if (!empty($book_item['file_ebook'])): ?>
                <span class="badge bg-primary position-absolute top-0 end-0 m-2"><i class="bi bi-tablet me-1"></i>E-Book</span>
              <?php endif; ?>
            </div>
            <div class="book-card__body d-flex flex-column flex-grow-1 p-3">
              <span class="badge bg-light text-dark mb-2 text-truncate" style="width: fit-content; max-width: 100%;"><?= htmlspecialchars($book_item['nama_kategori'] ?? 'Umum'); ?></span>
              <h6 class="book-card__title text-truncate-2 mb-1 fw-bold"><?= htmlspecialchars($book_item['judul']); ?></h6>
              <p class="book-card__author text-muted small mb-2"><?= htmlspecialchars($book_item['pengarang']); ?></p>
              <div class="mt-auto d-flex justify-content-between align-items-center">
                <span class="small text-warning"><i class="bi bi-star-fill me-1"></i><?= $book_item['rating']; ?></span>
                <span class="badge <?= $book_item['stok'] > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                  <?= $book_item['stok'] > 0 ? 'Stok: ' . $book_item['stok'] : 'Habis'; ?>
                </span>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Dialog Opsi Pinjam Buku -->
<?php if (is_logged_in() && $book['stok'] > 0): ?>
<div class="modal fade" id="modalPinjam" tabindex="-1" aria-labelledby="modalPinjamLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-dark" id="modalPinjamLabel"><i class="bi bi-journal-plus text-primary me-2"></i>Konfirmasi Peminjaman E-Book</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?= BASE_URL; ?>actions/borrow_action.php?action=pinjam" method="POST">
        <div class="modal-body py-4">
          <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
          <input type="hidden" name="book_id" value="<?= $book['id']; ?>" />

          <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-4 border">
            <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($book['cover_image']); ?>" style="width: 50px; height: 70px; object-fit: cover;" class="rounded shadow-sm" />
            <div>
              <h6 class="fw-bold mb-1 text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($book['judul']); ?></h6>
              <span class="text-muted small">Stok Lisensi: <strong class="text-success"><?= $book['stok']; ?> Tersedia</strong></span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary" for="durasiSelect">Pilih Durasi Masa Pinjam *</label>
            <select name="durasi" id="durasiSelect" class="form-select rounded-3 py-2" required>
              <?php foreach ([1, 3, 7, 14] as $d): ?>
                <option value="<?= $d; ?>" <?= $d === 7 ? 'selected' : ''; ?>>
                  <?= $d; ?> Hari <?= $d === 7 ? '(Standar Rekomendasi)' : ''; ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text text-muted" style="font-size: 0.78rem;">
              <i class="bi bi-info-circle me-1"></i>Buku akan otomatis dikembalikan oleh sistem begitu masa pinjam berakhir tanpa denda.
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom rounded-3 px-4 fw-semibold">Konfirmasi & Pinjam</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const starItems = document.querySelectorAll('#starSelector .star-item');
  const ratingInput = document.getElementById('ratingInput');

  if (starItems.length > 0 && ratingInput) {
    starItems.forEach(star => {
      star.addEventListener('click', function() {
        const val = parseInt(this.getAttribute('data-value'));
        ratingInput.value = val;
        
        starItems.forEach(s => {
          const sVal = parseInt(s.getAttribute('data-value'));
          if (sVal <= val) {
            s.classList.add('selected');
            s.classList.remove('text-muted', 'opacity-25');
          } else {
            s.classList.remove('selected');
            s.classList.add('text-muted', 'opacity-25');
          }
        });
      });
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
