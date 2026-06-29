<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/borrow_helper.php';

$db = Database::getConnection();
process_auto_expiration($db);

// Capture active filters
$search = trim($_GET['q'] ?? '');
$filter_category = (int)($_GET['category'] ?? 0);
$filter_year = (int)($_GET['year'] ?? 0);

// Build dynamic query
$query_sql = "SELECT b.*, c.nama_kategori FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE 1=1";
$query_params = [];

if (!empty($search)) {
    $query_sql .= " AND (b.judul LIKE :q1 OR b.pengarang LIKE :q2 OR b.isbn LIKE :q3 OR b.penerbit LIKE :q4)";
    $query_params[':q1'] = "%$search%";
    $query_params[':q2'] = "%$search%";
    $query_params[':q3'] = "%$search%";
    $query_params[':q4'] = "%$search%";
}

if ($filter_category > 0) {
    $query_sql .= " AND b.category_id = :category";
    $query_params[':category'] = $filter_category;
}

if ($filter_year > 0) {
    $query_sql .= " AND b.tahun_terbit = :year";
    $query_params[':year'] = $filter_year;
}

$query_sql .= " ORDER BY b.id DESC";
$books_stmt = $db->prepare($query_sql);
$books_stmt->execute($query_params);
$books = $books_stmt->fetchAll();

// Handle AJAX Request for Search and Filter (MUST BE RUN BEFORE SIDEBAR OUTPUTS ANY HTML)
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    if (empty($books)) {
        echo '<tr><td colspan="8" class="text-center py-4 text-muted">Belum ada koleksi buku yang cocok.</td></tr>';
    } else {
        foreach ($books as $i => $b) {
            $q_count = get_queue_count($db, $b['id']);
            ?>
            <tr>
              <td class="text-center text-muted fw-semibold small"><?= $i + 1; ?></td>
              <td>
                <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($b['cover_image']); ?>" style="width: 45px; height: 60px; object-fit: cover;" class="rounded shadow-sm" onerror="this.src='https://placehold.co/100x150?text=Cover'" />
              </td>
              <td>
                <strong><?= htmlspecialchars($b['judul']); ?></strong>
                <div class="text-muted small"><?= htmlspecialchars($b['pengarang']); ?></div>
              </td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($b['nama_kategori'] ?? 'Umum'); ?></span></td>
              <td>
                <div class="small">ISBN: <?= htmlspecialchars($b['isbn'] ?: '-'); ?></div>
                <div class="text-muted small"><?= htmlspecialchars($b['penerbit']); ?> (<?= $b['tahun_terbit']; ?>)</div>
              </td>
              <td>
                <span class="badge <?= $b['stok'] > 0 ? 'bg-success' : 'bg-danger'; ?>"><?= $b['stok']; ?> Lisensi Tersedia</span>
              </td>
              <td>
                <span class="badge <?= $q_count > 0 ? 'bg-warning text-dark' : 'bg-light text-dark border'; ?>"><?= $q_count; ?> Pengantre</span>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="<?= BASE_URL; ?>admin/preview-buku.php?id=<?= $b['id']; ?>" class="btn btn-sm btn-outline-info" title="Lihat Detail Buku"><i class="bi bi-eye"></i></a>
                  
                  <button class="btn btn-sm btn-outline-warning edit-book-btn" 
                          data-bs-toggle="modal" 
                          data-bs-target="#modalEditBuku"
                          data-id="<?= $b['id']; ?>"
                          data-judul="<?= htmlspecialchars($b['judul']); ?>"
                          data-category-id="<?= $b['category_id']; ?>"
                          data-pengarang="<?= htmlspecialchars($b['pengarang']); ?>"
                          data-penerbit="<?= htmlspecialchars($b['penerbit']); ?>"
                          data-tahun-terbit="<?= $b['tahun_terbit']; ?>"
                          data-isbn="<?= htmlspecialchars($b['isbn']); ?>"
                          data-stok="<?= $b['stok']; ?>"
                          data-deskripsi="<?= htmlspecialchars($b['deskripsi']); ?>"
                          data-cover="<?= htmlspecialchars($b['cover_image']); ?>"
                          data-ebook="<?= htmlspecialchars($b['file_ebook'] ?? ''); ?>"
                          title="Edit Buku">
                    <i class="bi bi-pencil"></i>
                  </button>

                  <button type="button" 
                          class="btn btn-sm btn-outline-danger delete-book-btn" 
                          data-bs-toggle="modal" 
                          data-bs-target="#modalHapusBuku"
                          data-id="<?= $b['id']; ?>"
                          data-judul="<?= htmlspecialchars($b['judul']); ?>"
                          title="Hapus Buku">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php
        }
    }
    exit;
}

$page_title = "Kelola Koleksi & Lisensi E-Book";
$active_admin_nav = "buku";
require_once __DIR__ . '/../includes/sidebar.php';

// Auto-fix any categories that were saved with HTML entities
$db->query("UPDATE categories SET nama_kategori = REPLACE(nama_kategori, '&amp;', '&') WHERE nama_kategori LIKE '%&amp;%'");

// Fetch Kategori untuk Modal Option & Filter
$categories_stmt = $db->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
$categories = $categories_stmt->fetchAll();

// Fetch list of distinct publication years
$years_stmt = $db->query("SELECT DISTINCT tahun_terbit FROM books WHERE tahun_terbit IS NOT NULL AND tahun_terbit > 0 ORDER BY tahun_terbit DESC");
$years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);

// Also count TOTAL ALL books (unfiltered)
$total_books_stmt = $db->query("SELECT COUNT(*) FROM books");
$total_books = (int)$total_books_stmt->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <h5 class="fw-semibold mb-0" style="font-size: 1.1rem; color: var(--text); letter-spacing: -0.2px;">Kelola Koleksi & Kuota Lisensi E-Book</h5>
      <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.72rem;">Total: <?= $total_books; ?> Buku</span>
    </div>
    <p class="text-muted small mb-0">Atur batas kuota lisensi bersamaan dan upload file e-book DRM terproteksi.</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-custom fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahKategori" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
      <i class="bi bi-tags me-1"></i> Tambah Kategori
    </button>
    <button class="btn btn-primary-custom fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahBuku" style="font-size: 0.85rem; padding: 0.5rem 1rem;">
      <i class="bi bi-plus-circle me-1"></i> Tambah Buku Baru
    </button>
  </div>
</div>

<!-- Filter Bar Card -->
<div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-white">
  <form action="" method="GET" class="row g-2 align-items-center">
    <div class="col-12 col-md-5">
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" name="q" class="form-control border-start-0" placeholder="Cari judul, penulis, penerbit, atau ISBN..." value="<?= htmlspecialchars($search); ?>" />
      </div>
    </div>
    <div class="col-6 col-md-3">
      <select name="category" class="form-select form-select-sm">
        <option value="">Semua Kategori</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id']; ?>" <?= $filter_category === (int)$cat['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($cat['nama_kategori']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select name="year" class="form-select form-select-sm">
        <option value="">Semua Tahun</option>
        <?php foreach ($years as $yr): ?>
          <option value="<?= $yr; ?>" <?= $filter_year === (int)$yr ? 'selected' : ''; ?>><?= $yr; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-sm btn-primary-custom w-100 fw-semibold" style="padding: 0.45rem;"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
      <?php if (!empty($search) || $filter_category > 0 || $filter_year > 0): ?>
        <a href="kelola-buku.php" class="btn btn-sm btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center fw-semibold" style="padding: 0.45rem;"><i class="bi bi-arrow-counterclockwise"></i></a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 60px;" class="text-center">No</th>
          <th style="width: 80px;">Cover</th>
          <th>Judul & Pengarang</th>
          <th>Kategori</th>
          <th>ISBN & Penerbit</th>
          <th>Sisa Lisensi Bebas</th>
          <th>Antrean Waitlist</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($books)): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada koleksi buku yang cocok.</td></tr>
        <?php else: ?>
          <?php foreach ($books as $i => $b): ?>
            <?php $q_count = get_queue_count($db, $b['id']); ?>
            <tr>
              <td class="text-center text-muted fw-semibold small"><?= $i + 1; ?></td>
              <td>
                <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($b['cover_image']); ?>" style="width: 45px; height: 60px; object-fit: cover;" class="rounded shadow-sm" onerror="this.src='https://placehold.co/100x150?text=Cover'" />
              </td>
              <td>
                <strong><?= htmlspecialchars($b['judul']); ?></strong>
                <div class="text-muted small"><?= htmlspecialchars($b['pengarang']); ?></div>
              </td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($b['nama_kategori'] ?? 'Umum'); ?></span></td>
              <td>
                <div class="small">ISBN: <?= htmlspecialchars($b['isbn'] ?: '-'); ?></div>
                <div class="text-muted small"><?= htmlspecialchars($b['penerbit']); ?> (<?= $b['tahun_terbit']; ?>)</div>
              </td>
              <td>
                <span class="badge <?= $b['stok'] > 0 ? 'bg-success' : 'bg-danger'; ?>"><?= $b['stok']; ?> Lisensi Tersedia</span>
              </td>
              <td>
                <span class="badge <?= $q_count > 0 ? 'bg-warning text-dark' : 'bg-light text-dark border'; ?>"><?= $q_count; ?> Pengantre</span>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="<?= BASE_URL; ?>admin/preview-buku.php?id=<?= $b['id']; ?>" class="btn btn-sm btn-outline-info" title="Lihat Detail Buku"><i class="bi bi-eye"></i></a>
                  
                  <button class="btn btn-sm btn-outline-warning edit-book-btn" 
                          data-bs-toggle="modal" 
                          data-bs-target="#modalEditBuku"
                          data-id="<?= $b['id']; ?>"
                          data-judul="<?= htmlspecialchars($b['judul']); ?>"
                          data-category-id="<?= $b['category_id']; ?>"
                          data-pengarang="<?= htmlspecialchars($b['pengarang']); ?>"
                          data-penerbit="<?= htmlspecialchars($b['penerbit']); ?>"
                          data-tahun-terbit="<?= $b['tahun_terbit']; ?>"
                          data-isbn="<?= htmlspecialchars($b['isbn']); ?>"
                          data-stok="<?= $b['stok']; ?>"
                          data-deskripsi="<?= htmlspecialchars($b['deskripsi']); ?>"
                          data-cover="<?= htmlspecialchars($b['cover_image']); ?>"
                          data-ebook="<?= htmlspecialchars($b['file_ebook'] ?? ''); ?>"
                          title="Edit Buku">
                    <i class="bi bi-pencil"></i>
                  </button>

                  <button type="button" 
                          class="btn btn-sm btn-outline-danger delete-book-btn" 
                          data-bs-toggle="modal" 
                          data-bs-target="#modalHapusBuku"
                          data-id="<?= $b['id']; ?>"
                          data-judul="<?= htmlspecialchars($b['judul']); ?>"
                          title="Hapus Buku">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Tambah Buku -->
<div class="modal fade" id="modalTambahBuku" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-semibold" style="font-size: 1rem;">Tambah Koleksi E-Book Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?= BASE_URL; ?>actions/book_action.php?action=tambah" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Judul Buku *</label>
              <input type="text" name="judul" class="form-control" placeholder="Masukkan judul buku" required />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Kategori *</label>
              <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['nama_kategori']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Pengarang / Penulis *</label>
              <input type="text" name="pengarang" class="form-control" placeholder="Nama penulis" required />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Penerbit *</label>
              <input type="text" name="penerbit" class="form-control" placeholder="Nama penerbit" required />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Tahun Terbit</label>
              <input type="number" name="tahun_terbit" class="form-control" value="<?= date('Y'); ?>" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">ISBN</label>
              <input type="text" name="isbn" class="form-control" placeholder="978-xxx-xxx" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Kuota Lisensi Digital (Stok)</label>
              <input type="number" name="stok" class="form-control" value="2" min="1" />
              <small class="text-muted" style="font-size:0.72rem;">Jumlah batas dibaca bersamaan.</small>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Deskripsi / Sinopsis</label>
              <textarea name="deskripsi" class="form-control" rows="3" placeholder="Tuliskan gambaran ringkas buku"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Upload Cover (JPG/PNG)</label>
              <input type="file" name="cover_image" class="form-control" accept="image/*" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Upload File PDF (E-Book DRM)</label>
              <input type="file" name="file_ebook" class="form-control" accept=".pdf" />
            </div>
          </div>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">Simpan Buku</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-semibold" style="font-size: 1rem;">Tambah Kategori Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?= BASE_URL; ?>actions/category_action.php?action=tambah" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Kategori *</label>
            <p class="text-muted small mb-2">Kamu bisa memasukkan lebih dari 1 kategori. Nanti kategori akan digabungkan menggunakan tanda <strong>&</strong> secara otomatis.</p>
            
            <div id="kategori-input-container">
              <div class="d-flex gap-2 mb-2 align-items-center">
                <input type="text" name="kategori[]" class="form-control" placeholder="Contoh: Teknologi" required />
              </div>
            </div>
            
            <button type="button" class="btn btn-sm btn-outline-secondary mt-1 d-inline-flex align-items-center gap-1" id="btnTambahInputKategori">
              <i class="bi bi-plus-circle"></i> Tambah Kategori Lain
            </button>
          </div>

          <hr class="my-4" />
          
          <h6 class="fw-semibold mb-2" style="font-size: 0.88rem; color: var(--text);"><i class="bi bi-list-ul me-1 text-primary"></i> Daftar Kategori Saat Ini</h6>
          <div class="list-group list-group-flush border rounded-3 overflow-hidden" style="max-height: 220px; overflow-y: auto;">
            <?php if (empty($categories)): ?>
              <div class="list-group-item text-center py-3 text-muted small">Belum ada kategori.</div>
            <?php else: ?>
              <?php foreach ($categories as $cat): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 small">
                  <span class="fw-medium text-dark"><?= htmlspecialchars($cat['nama_kategori']); ?></span>
                  
                  <div class="d-flex gap-1">
                    <button type="button" 
                            class="btn btn-sm btn-outline-warning btn-edit-category-trigger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalEditKategori"
                            data-id="<?= $cat['id']; ?>" 
                            data-name="<?= htmlspecialchars($cat['nama_kategori']); ?>" 
                            style="padding: 2px 6px; font-size: 0.75rem;" 
                            title="Edit Kategori">
                      <i class="bi bi-pencil"></i>
                    </button>
                    
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger delete-category-btn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalHapusKategori"
                            data-id="<?= $cat['id']; ?>" 
                            data-name="<?= htmlspecialchars($cat['nama_kategori']); ?>" 
                            style="padding: 2px 6px; font-size: 0.75rem;" 
                            title="Hapus Kategori">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">Simpan Kategori</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Hapus Buku -->
<div class="modal fade" id="modalHapusBuku" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-body p-4 text-center">
        <div class="text-danger mb-3" style="font-size: 2.5rem;">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h5 class="fw-semibold mb-2" style="font-size: 1.05rem; color: var(--text);">Hapus Buku ini?</h5>
        <p class="text-muted small mb-4">Tindakan ini tidak dapat dibatalkan. Buku <strong id="hapus-nama-buku" class="text-dark"></strong> akan dihapus secara permanen.</p>
        
        <form action="<?= BASE_URL; ?>actions/book_action.php?action=hapus" method="POST">
          <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
          <input type="hidden" name="id" id="hapus-id" value="" />
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-light border w-100 fw-semibold" data-bs-dismiss="modal" style="font-size: 0.85rem; border-radius: 8px; padding: 0.55rem;">Batal</button>
            <button type="submit" class="btn btn-danger w-100 fw-semibold" style="font-size: 0.85rem; border-radius: 8px; padding: 0.55rem;">Ya, Hapus</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Hapus Kategori -->
<div class="modal fade" id="modalHapusKategori" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-body p-4 text-center">
        <div class="text-danger mb-3" style="font-size: 2.5rem;">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h5 class="fw-semibold mb-2" style="font-size: 1.05rem; color: var(--text);">Hapus Kategori ini?</h5>
        <p class="text-muted small mb-4">Tindakan ini tidak dapat dibatalkan. Kategori <strong id="hapus-nama-kategori" class="text-dark"></strong> akan dihapus. Buku di kategori ini akan dipindah ke kategori <strong>Umum</strong>.</p>
        
        <form action="<?= BASE_URL; ?>actions/category_action.php?action=hapus" method="POST">
          <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
          <input type="hidden" name="id" id="hapus-category-id" value="" />
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-light border w-100 fw-semibold" data-bs-dismiss="modal" style="font-size: 0.85rem; border-radius: 8px; padding: 0.55rem;">Batal</button>
            <button type="submit" class="btn btn-danger w-100 fw-semibold" style="font-size: 0.85rem; border-radius: 8px; padding: 0.55rem;">Ya, Hapus</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-semibold" style="font-size: 1rem;">Edit Nama Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?= BASE_URL; ?>actions/category_action.php?action=edit" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
        <input type="hidden" name="id" id="edit-category-id" value="" />
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Kategori *</label>
            <input type="text" name="nama_kategori" id="edit-category-name" class="form-control" required />
          </div>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-size: 0.85rem;">Batal</button>
          <button type="submit" class="btn btn-primary-custom" style="font-size: 0.85rem;">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit Buku -->
<div class="modal fade" id="modalEditBuku" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4 border-0">
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-semibold" style="font-size: 1rem;">Edit Koleksi E-Book</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?= BASE_URL; ?>actions/book_action.php?action=edit" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>" />
        <input type="hidden" name="id" id="edit-id" value="" />
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Judul Buku *</label>
              <input type="text" name="judul" id="edit-judul" class="form-control" placeholder="Masukkan judul buku" required />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Kategori *</label>
              <select name="category_id" id="edit-category_id" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['nama_kategori']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Pengarang / Penulis *</label>
              <input type="text" name="pengarang" id="edit-pengarang" class="form-control" placeholder="Nama penulis" required />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Penerbit *</label>
              <input type="text" name="penerbit" id="edit-penerbit" class="form-control" placeholder="Nama penerbit" required />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Tahun Terbit</label>
              <input type="number" name="tahun_terbit" id="edit-tahun_terbit" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">ISBN</label>
              <input type="text" name="isbn" id="edit-isbn" class="form-control" placeholder="978-xxx-xxx" />
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Kuota Lisensi Digital (Stok)</label>
              <input type="number" name="stok" id="edit-stok" class="form-control" min="0" />
              <small class="text-muted" style="font-size:0.72rem;">Jumlah batas dibaca bersamaan.</small>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Deskripsi / Sinopsis</label>
              <textarea name="deskripsi" id="edit-deskripsi" class="form-control" rows="3" placeholder="Tuliskan gambaran ringkas buku"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ganti Cover <span class="text-muted fw-normal" style="font-size: 0.75rem;">(Biarkan kosong jika tidak ingin diubah)</span></label>
              <div class="d-flex align-items-center gap-3 mb-2 p-2 border rounded-3 bg-light">
                <img id="edit-preview-cover" src="" style="width: 45px; height: 60px; object-fit: cover;" class="rounded shadow-sm" onerror="this.src='https://placehold.co/100x150?text=Cover'" />
                <div>
                  <div class="small fw-semibold text-dark" style="font-size: 0.8rem;">Cover Saat Ini:</div>
                  <div class="text-muted small text-break" id="edit-cover-name" style="font-size: 0.75rem;">-</div>
                </div>
              </div>
              <input type="file" name="cover_image" class="form-control" accept="image/*" />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Ganti File PDF <span class="text-muted fw-normal" style="font-size: 0.75rem;">(Biarkan kosong jika tidak ingin diubah)</span></label>
              <div class="d-flex align-items-center gap-2 mb-2 p-2 border rounded-3 bg-light" style="min-height: 76px;">
                <i class="bi bi-file-earmark-pdf-fill text-danger fs-3 ms-1"></i>
                <div class="overflow-hidden">
                  <div class="small fw-semibold text-dark" style="font-size: 0.8rem;">File PDF Saat Ini:</div>
                  <div class="text-muted small text-truncate" id="edit-ebook-name" style="font-size: 0.75rem;">Belum ada file PDF</div>
                </div>
              </div>
              <input type="file" name="file_ebook" class="form-control" accept=".pdf" />
            </div>
          </div>
        </div>
        <div class="modal-footer border-top bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

      </div> <!-- content-area -->
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      const filterForm = document.querySelector('.card form');
      const searchInput = document.querySelector('input[name="q"]');
      const categorySelect = document.querySelector('select[name="category"]');
      const yearSelect = document.querySelector('select[name="year"]');
      const bookTableBody = document.querySelector('table tbody');

      function generateSkeletons(count = 3) {
        let html = '';
        for (let i = 0; i < count; i++) {
          html += `
            <tr class="skeleton-pulse">
              <td class="text-center"><div class="skeleton-line w-30"></div></td>
              <td><div class="skeleton-cover"></div></td>
              <td>
                <div class="skeleton-line w-75 mb-1"></div>
                <div class="skeleton-line w-50"></div>
              </td>
              <td><div class="skeleton-line w-50"></div></td>
              <td>
                <div class="skeleton-line w-100 mb-1"></div>
                <div class="skeleton-line w-75"></div>
              </td>
              <td><div class="skeleton-line w-75"></div></td>
              <td><div class="skeleton-line w-50"></div></td>
              <td><div class="skeleton-line w-75"></div></td>
            </tr>
          `;
        }
        return html;
      }

      async function performFilter(e) {
        if (e) e.preventDefault();

        const q = searchInput ? searchInput.value : '';
        const category = categorySelect ? categorySelect.value : '';
        const year = yearSelect ? yearSelect.value : '';

        const params = new URLSearchParams({
          ajax: 1,
          q: q,
          category: category,
          year: year
        });

        // Show skeleton rows first
        bookTableBody.innerHTML = generateSkeletons(4);

        try {
          // Add a tiny artificial delay (350ms) to let the skeleton shine!
          await new Promise(resolve => setTimeout(resolve, 350));

          const response = await fetch('kelola-buku.php?' + params.toString());
          const html = await response.text();

          // Render response HTML
          bookTableBody.innerHTML = html;

          // Re-wire edit and delete listeners because elements were replaced!
          wireEventHandlers();
        } catch (err) {
          console.error('Error fetching filtered books:', err);
          bookTableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Gagal memuat data buku. Silakan coba lagi.</td></tr>';
        }
      }

      function wireEventHandlers() {
        const editButtons = document.querySelectorAll('.edit-book-btn');
        editButtons.forEach(btn => {
          btn.addEventListener('click', function () {
            document.getElementById('edit-id').value = this.getAttribute('data-id');
            document.getElementById('edit-judul').value = this.getAttribute('data-judul');
            document.getElementById('edit-category_id').value = this.getAttribute('data-category-id');
            document.getElementById('edit-pengarang').value = this.getAttribute('data-pengarang');
            document.getElementById('edit-penerbit').value = this.getAttribute('data-penerbit');
            document.getElementById('edit-tahun_terbit').value = this.getAttribute('data-tahun-terbit');
            document.getElementById('edit-isbn').value = this.getAttribute('data-isbn');
            document.getElementById('edit-stok').value = this.getAttribute('data-stok');
            document.getElementById('edit-deskripsi').value = this.getAttribute('data-deskripsi');

            const cover = this.getAttribute('data-cover');
            const ebook = this.getAttribute('data-ebook');

            const previewCoverImg = document.getElementById('edit-preview-cover');
            const previewCoverName = document.getElementById('edit-cover-name');
            if (cover) {
              previewCoverImg.src = '<?= BASE_URL; ?>assets/img/' + cover;
              previewCoverName.textContent = cover;
            } else {
              previewCoverImg.src = 'https://placehold.co/100x150?text=Cover';
              previewCoverName.textContent = 'default_cover.jpg';
            }

            const previewEbookName = document.getElementById('edit-ebook-name');
            if (ebook && ebook.trim() !== '') {
              previewEbookName.textContent = ebook;
            } else {
              previewEbookName.textContent = 'Belum ada file PDF';
            }
          });
        });

        // Delete Book Modal Handler
        const deleteButtons = document.querySelectorAll('.delete-book-btn');
        deleteButtons.forEach(btn => {
          btn.addEventListener('click', function () {
            document.getElementById('hapus-id').value = this.getAttribute('data-id');
            document.getElementById('hapus-nama-buku').textContent = this.getAttribute('data-judul');
          });
        });
      }

      // Attach AJAX triggers
      if (filterForm) {
        filterForm.addEventListener('submit', performFilter);
      }
      if (searchInput) {
        // Debounce typing keyup to prevent heavy loading
        let debounceTimer;
        searchInput.addEventListener('keyup', () => {
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(performFilter, 250);
        });
      }
      if (categorySelect) {
        categorySelect.addEventListener('change', performFilter);
      }
      if (yearSelect) {
        yearSelect.addEventListener('change', performFilter);
      }

      // Initial wiring on page load
      wireEventHandlers();

      // Dynamic Category Inputs
      const btnTambahInput = document.getElementById('btnTambahInputKategori');
      const containerKategori = document.getElementById('kategori-input-container');

      if (btnTambahInput && containerKategori) {
        btnTambahInput.addEventListener('click', function () {
          const div = document.createElement('div');
          div.className = 'd-flex gap-2 mb-2 align-items-center';
          div.innerHTML = `
            <input type="text" name="kategori[]" class="form-control" placeholder="Contoh: Pemrograman" required />
            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-input" style="padding: 0.375rem 0.6rem;"><i class="bi bi-trash"></i></button>
          `;
          containerKategori.appendChild(div);

          // Add delete listener to the new button
          div.querySelector('.btn-hapus-input').addEventListener('click', function () {
            div.remove();
          });
        });
      }

      // Edit Category Modal Handler
      const editCategoryTriggers = document.querySelectorAll('.btn-edit-category-trigger');
      editCategoryTriggers.forEach(btn => {
        btn.addEventListener('click', function () {
          const id = this.getAttribute('data-id');
          const name = this.getAttribute('data-name');
          document.getElementById('edit-category-id').value = id;
          document.getElementById('edit-category-name').value = name;
        });
      });

      // Delete Category Modal Handler
      const deleteCategoryButtons = document.querySelectorAll('.delete-category-btn');
      deleteCategoryButtons.forEach(btn => {
        btn.addEventListener('click', function () {
          document.getElementById('hapus-category-id').value = this.getAttribute('data-id');
          document.getElementById('hapus-nama-kategori').textContent = this.getAttribute('data-name');
        });
      });
    });
    </script>
  </body>
</html>
