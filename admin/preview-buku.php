<?php
$page_title = "Detail E-Book";
$active_admin_nav = "buku";
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/borrow_helper.php';
require_once __DIR__ . '/../helpers/format_helper.php';

require_role(['admin', 'petugas']);

$id = (int)($_GET['id'] ?? 0);
$db = Database::getConnection();

// Fetch Book Details
$stmt = $db->prepare("SELECT b.*, c.nama_kategori FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    set_flash('danger', 'Buku tidak ditemukan.');
    header('Location: ' . BASE_URL . 'admin/kelola-buku.php');
    exit;
}

// Fetch Active Borrowings
$borrowings_stmt = $db->prepare("
    SELECT br.*, u.nama, u.email 
    FROM borrowings br 
    JOIN users u ON br.user_id = u.id 
    WHERE br.book_id = :bid AND br.status = 'dipinjam'
    ORDER BY br.tanggal_pinjam ASC
");
$borrowings_stmt->execute([':bid' => $id]);
$active_borrowings = $borrowings_stmt->fetchAll();

// Fetch Waitlist
$queue_stmt = $db->prepare("
    SELECT br.*, u.nama, u.email 
    FROM borrowings br 
    JOIN users u ON br.user_id = u.id 
    WHERE br.book_id = :bid AND br.status = 'antre'
    ORDER BY br.id ASC
");
$queue_stmt->execute([':bid' => $id]);
$waitlist = $queue_stmt->fetchAll();

// Fetch Reviews
$reviews_stmt = $db->prepare("
    SELECT r.*, u.nama 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.book_id = :bid 
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([':bid' => $id]);
$reviews = $reviews_stmt->fetchAll();
?>

<div class="mb-4">
  <a href="<?= BASE_URL; ?>admin/kelola-buku.php" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-2 fw-semibold" style="border-radius: 8px; padding: 0.45rem 0.75rem;">
    <i class="bi bi-arrow-left text-primary"></i> Kembali ke Kelola Buku
  </a>
</div>

<div class="row g-4">
  <!-- Left Side: Cover & Quick Stats -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
      <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($book['cover_image']); ?>" class="img-fluid rounded-3 shadow mb-3 mx-auto" style="max-height: 320px; object-fit: cover;" onerror="this.src='https://placehold.co/300x400?text=Cover+Buku'" />
      <h5 class="fw-semibold mb-1" style="font-size: 1.05rem; color: var(--text); letter-spacing: -0.1px;"><?= htmlspecialchars($book['judul']); ?></h5>
      <p class="text-muted small mb-3">Oleh <?= htmlspecialchars($book['pengarang']); ?></p>
      
      <div class="d-flex justify-content-center gap-2 mb-3">
        <span class="badge bg-light text-warning border d-inline-flex align-items-center"><i class="bi bi-star-fill me-1"></i><?= number_format($book['rating'], 1); ?></span>
        <span class="badge bg-primary-light text-primary"><?= htmlspecialchars($book['nama_kategori'] ?? 'Umum'); ?></span>
      </div>

      <hr class="my-3" />

      <div class="d-grid gap-2">
        <?php if (!empty($book['file_ebook'])): ?>
          <a href="<?= BASE_URL; ?>baca-ebook/<?= $book['slug']; ?>" target="_blank" class="btn btn-primary-custom d-inline-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-book-half"></i> Baca / Preview E-Book
          </a>
        <?php else: ?>
          <button class="btn btn-secondary d-inline-flex align-items-center justify-content-center gap-2" disabled>
            <i class="bi bi-file-earmark-lock-fill"></i> File E-Book Belum Diupload
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right Side: Details & Lists -->
  <div class="col-lg-8">
    <!-- Info Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
      <h6 class="fw-semibold mb-3 text-primary" style="font-size: 0.88rem;"><i class="bi bi-info-circle me-2"></i>Informasi E-Book</h6>
      <div class="row g-3 small mb-3">
        <div class="col-sm-6">
          <span class="text-muted d-block mb-0.5">Penerbit</span>
          <strong class="text-dark"><?= htmlspecialchars($book['penerbit']); ?></strong>
        </div>
        <div class="col-sm-6">
          <span class="text-muted d-block mb-0.5">Tahun Terbit</span>
          <strong class="text-dark"><?= $book['tahun_terbit']; ?></strong>
        </div>
        <div class="col-sm-6">
          <span class="text-muted d-block mb-0.5">ISBN</span>
          <strong class="text-dark"><?= htmlspecialchars($book['isbn'] ?: '-'); ?></strong>
        </div>
        <div class="col-sm-6">
          <span class="text-muted d-block mb-0.5">Kuota Lisensi (Maks. Bersamaan)</span>
          <strong class="text-dark"><?= $book['stok']; ?> Lisensi</strong>
        </div>
      </div>
      <hr />
      <span class="text-muted small d-block mb-1">Sinopsis / Deskripsi</span>
      <p class="text-secondary mb-0" style="font-size: 0.88rem; line-height: 1.6; text-align: justify;">
        <?= nl2br(htmlspecialchars($book['deskripsi'] ?: 'Tidak ada deskripsi untuk buku ini.')); ?>
      </p>
    </div>

    <!-- Borrowing & Queue Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
      <h6 class="fw-semibold mb-3 text-success" style="font-size: 0.88rem;"><i class="bi bi-arrow-left-right me-2"></i>Status Peminjaman & Antrean</h6>
      
      <!-- Active Borrowers -->
      <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold text-dark small">Sedang Membaca (<?= count($active_borrowings); ?>/<?= $book['stok']; ?>)</span>
        </div>
        <?php if (empty($active_borrowings)): ?>
          <div class="alert alert-light border small text-muted py-2 mb-0">Tidak ada pengguna yang sedang meminjam buku ini.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-borderless align-middle mb-0 small">
              <thead>
                <tr class="text-muted border-bottom" style="font-size: 0.78rem;">
                  <th class="py-2">Nama Pengguna</th>
                  <th class="py-2">Email</th>
                  <th class="py-2">Tanggal Pinjam</th>
                  <th class="py-2">Batas Waktu</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($active_borrowings as $ab): ?>
                  <tr class="border-bottom">
                    <td class="py-2"><strong><?= htmlspecialchars($ab['nama']); ?></strong></td>
                    <td class="py-2 text-muted"><?= htmlspecialchars($ab['email']); ?></td>
                    <td class="py-2"><?= format_tanggal($ab['tanggal_pinjam']); ?></td>
                    <td class="py-2 text-danger fw-semibold"><?= format_tanggal($ab['tanggal_jatuh_tempo']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Waitlist Queue -->
      <div>
        <span class="fw-semibold text-dark small d-block mb-2">Daftar Antrean Waitlist (<?= count($waitlist); ?> Orang)</span>
        <?php if (empty($waitlist)): ?>
          <div class="alert alert-light border small text-muted py-2 mb-0">Antrean kosong.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-borderless align-middle mb-0 small">
              <thead>
                <tr class="text-muted border-bottom" style="font-size: 0.78rem;">
                  <th class="py-2">No. Antrean</th>
                  <th class="py-2">Nama Pengguna</th>
                  <th class="py-2">Email</th>
                  <th class="py-2">Bergabung Antrean</th>
                </tr>
              </thead>
              <tbody>
                <?php $pos = 1; foreach ($waitlist as $wl): ?>
                  <tr class="border-bottom">
                    <td class="py-2"><span class="badge bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center" style="width:20px; height:20px;"><?= $pos++; ?></span></td>
                    <td class="py-2"><strong><?= htmlspecialchars($wl['nama']); ?></strong></td>
                    <td class="py-2 text-muted"><?= htmlspecialchars($wl['email']); ?></td>
                    <td class="py-2"><?= format_tanggal($wl['created_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Reviews Card -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
      <h6 class="fw-semibold mb-3 text-warning" style="font-size: 0.88rem;"><i class="bi bi-chat-left-text me-2"></i>Ulasan & Rating Pembaca</h6>
      <?php if (empty($reviews)): ?>
        <div class="text-center py-3 text-muted small">Belum ada ulasan untuk buku ini.</div>
      <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($reviews as $rev): ?>
            <div class="border-bottom pb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="small text-dark"><?= htmlspecialchars($rev['nama']); ?></strong>
                <span class="text-warning small">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?= $i <= $rev['rating'] ? '-fill' : ''; ?>"></i>
                  <?php endfor; ?>
                </span>
              </div>
              <p class="text-secondary small mb-0"><?= nl2br(htmlspecialchars($rev['ulasan'])); ?></p>
              <small class="text-muted" style="font-size:0.68rem;"><?= format_tanggal($rev['created_at']); ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

      </div> <!-- content-area -->
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
