<?php
$page_title = "Gerbang Literasi — Perpustakaan Digital";
$active_nav = "beranda";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getConnection();

// Fetch Statistik Ringkas
$total_buku_stmt = $db->query("SELECT COUNT(*) as total FROM books");
$total_buku = $total_buku_stmt->fetch()['total'] ?? 0;

$total_user_stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'anggota'");
$total_user = $total_user_stmt->fetch()['total'] ?? 0;

$satisfaction_stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END), 0) as positive FROM reviews");
$satisfaction_data = $satisfaction_stmt->fetch();
$total_review = $satisfaction_data['total'];
$positive_review = $satisfaction_data['positive'];
$kepuasan = $total_review > 0 ? round(($positive_review / $total_review) * 100) : 100;

// Fetch Buku Populer / Terbaru (Limit 6)
$popular_stmt = $db->query("SELECT b.*, c.nama_kategori, COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE book_id = b.id), 0.0) as rating FROM books b LEFT JOIN categories c ON b.category_id = c.id ORDER BY rating DESC, b.id DESC LIMIT 6");
$popular_books = $popular_stmt->fetchAll();
?>

    <section class="hero-section">
      <div class="hero-bg-shape hero-bg-shape--1"></div>
      <div class="hero-bg-shape hero-bg-shape--2"></div>
      <div class="hero-bg-shape hero-bg-shape--3"></div>

      <div class="container" style="position: relative; z-index: 2">
        <div class="row align-items-center g-5">
          <div class="col-lg-6">
            <div class="hero-badge">
              <span class="hero-badge__dot"></span>
              E-Library Platform Production
            </div>
            <h1 class="hero-title">
              Pintu Masuk Menuju<br />Dunia
              <span class="hero-title__highlight">Pengetahuan</span>
            </h1>
            <p class="hero-desc">
              Akses ribuan koleksi e-book & buku digital, baca secara online dengan aman, dan nikmati membaca buku favorit Anda — semua dalam satu platform.
            </p>
            <div class="d-flex gap-3 flex-wrap mb-4">
              <a href="<?= BASE_URL; ?>katalog" class="btn btn-primary-custom hero-btn">
                <i class="bi bi-search me-2"></i>Jelajahi Katalog
              </a>
              <?php if (!is_logged_in()): ?>
                <a href="<?= BASE_URL; ?>register" class="btn btn-outline-custom hero-btn">
                  <i class="bi bi-person-plus me-2"></i>Daftar Gratis
                </a>
              <?php endif; ?>
            </div>
            <div class="hero-stats">
              <div class="hero-stats__item">
                <span class="hero-stats__num"><?= number_format($total_buku); ?>+</span>
                <span class="hero-stats__label">Koleksi Buku</span>
              </div>
              <div class="hero-stats__divider"></div>
              <div class="hero-stats__item">
                <span class="hero-stats__num"><?= number_format($total_user); ?>+</span>
                <span class="hero-stats__label">Anggota</span>
              </div>
              <div class="hero-stats__divider"></div>
              <div class="hero-stats__item">
                <span class="hero-stats__num" style="color: var(--success)"><?= $kepuasan; ?>%</span>
                <span class="hero-stats__label">Kepuasan</span>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-image-wrapper">
              <div class="hero-image-card">
                <img src="<?= BASE_URL; ?>assets/img/hero_banner.png" alt="Gerbang Literasi" class="hero-image" />
                <video src="<?= BASE_URL; ?>assets/vid/perpus.mp4" class="hero-video" loop muted playsinline></video>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section style="background: var(--bg); padding: 4rem 0">
      <div class="container">
        <div class="text-center mb-4">
          <h3 class="fw-bold">Buku Populer & Rekomendasi</h3>
          <p class="text-muted">Koleksi buku dengan rating tertinggi di Gerbang Literasi</p>
        </div>
        <div class="row g-4">
          <?php foreach ($popular_books as $index => $book): ?>
            <div class="col-6 col-md-4 col-lg-2">
              <div class="book-card h-100 d-flex flex-column" onclick="location.href='<?= BASE_URL; ?>detail-buku/<?= $book['slug']; ?>'" style="cursor: pointer; --i:<?= $index; ?>;">
                <div class="book-card__cover">
                  <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($book['cover_image']); ?>" alt="<?= htmlspecialchars($book['judul']); ?>" onerror="this.src='https://placehold.co/300x400?text=Cover+Buku'" />
                  <?php if (!empty($book['file_ebook'])): ?>
                    <span class="badge bg-primary position-absolute top-0 end-0 m-2"><i class="bi bi-tablet me-1"></i>E-Book</span>
                  <?php endif; ?>
                </div>
                <div class="book-card__body d-flex flex-column flex-grow-1 p-3">
                  <span class="badge bg-light text-dark mb-2 text-truncate" style="width: fit-content; max-width: 100%;"><?= htmlspecialchars($book['nama_kategori'] ?? 'Umum'); ?></span>
                  <h6 class="book-card__title text-truncate-2 mb-1 fw-bold"><?= htmlspecialchars($book['judul']); ?></h6>
                  <p class="book-card__author text-muted small mb-2"><?= htmlspecialchars($book['pengarang']); ?></p>
                  <div class="mt-auto d-flex justify-content-between align-items-center">
                    <span class="small text-warning"><i class="bi bi-star-fill me-1"></i><?= $book['rating']; ?></span>
                    <span class="badge <?= $book['stok'] > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                      <?= $book['stok'] > 0 ? 'Stok: ' . $book['stok'] : 'Habis'; ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
          <a href="<?= BASE_URL; ?>katalog" class="btn btn-primary-custom" style="padding: 10px 28px; font-size: 0.9rem">
            Lihat Semua Koleksi <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </section>

    <!-- Section: Cara Meminjam Buku -->
    <section class="cara-meminjam-section">
      <div class="section-bg-shape"></div>
      <div class="container">
        <div class="text-center mb-5 section-header">
          <span class="badge-pill-custom">PROSES MUDAH</span>
          <h2 class="fw-extrabold mt-2 text-dark section-title">Cara Meminjam Buku</h2>
          <p class="text-muted section-subtitle">Baca e-book terproteksi secara instan dan aman dalam 3 langkah mudah & praktis</p>
        </div>
        
        <div class="steps-flow-container">
          <div class="steps-connector-line"></div>
          
          <div class="row g-4 position-relative">
            <!-- Step 1 -->
            <div class="col-lg-4">
              <div class="step-card step-card--primary h-100">
                <div class="step-badge-number">01</div>
                <div class="step-card-header">
                  <div class="step-icon-wrapper">
                    <i class="bi bi-search"></i>
                  </div>
                  <span class="step-pill">Langkah 01</span>
                </div>
                <h5 class="step-card-title">Cari Buku</h5>
                <p class="step-card-desc">
                  Jelajahi OPAC katalog dan temukan buku berdasarkan judul, penulis, ISBN, atau kategori pilihan Anda.
                </p>
              </div>
            </div>
            
            <!-- Step 2 -->
            <div class="col-lg-4">
              <div class="step-card step-card--success h-100">
                <div class="step-badge-number">02</div>
                <div class="step-card-header">
                  <div class="step-icon-wrapper">
                    <i class="bi bi-person-check"></i>
                  </div>
                  <span class="step-pill">Langkah 02</span>
                </div>
                <h5 class="step-card-title">Daftar &amp; Login</h5>
                <p class="step-card-desc">
                  Buat akun anggota gratis atau login untuk mulai meminjam e-book dan mengecek kuota lisensi yang tersedia.
                </p>
              </div>
            </div>
            
            <!-- Step 3 -->
            <div class="col-lg-4">
              <div class="step-card step-card--warning h-100">
                <div class="step-badge-number">03</div>
                <div class="step-card-header">
                  <div class="step-icon-wrapper">
                    <i class="bi bi-book-half"></i>
                  </div>
                  <span class="step-pill">Langkah 03</span>
                </div>
                <h5 class="step-card-title">Pinjam &amp; Baca DRM</h5>
                <p class="step-card-desc">
                  Simpan buku ke rak digital atau nikmati membaca e-book DRM secara online kapan saja dan di mana saja.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const heroCard = document.querySelector('.hero-image-card');
  const heroVideo = document.querySelector('.hero-video');
  
  if (heroCard && heroVideo) {
    heroCard.addEventListener('mouseenter', function() {
      heroVideo.play().catch(function(err) {
        console.log('Video autoplay prevented:', err);
      });
    });
    heroCard.addEventListener('mouseleave', function() {
      heroVideo.pause();
      heroVideo.currentTime = 0;
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
