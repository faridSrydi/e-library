<?php
$page_title = "Katalog OPAC — Gerbang Literasi";
$active_nav = "katalog";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/helpers/format_helper.php';
$db = Database::getConnection();
// Helper to preserve URL params when switching filters
function get_filter_url($changes = []) {
    $params = $_GET;
    foreach ($changes as $key => $val) {
        if ($val === 0 || $val === '0' || $val === '' || $val === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $val;
        }
    }
    return 'katalog' . (!empty($params) ? '?' . http_build_query($params) : '');
}
// Parameters Filter
$search = sanitize($_GET['q'] ?? '');
$type = sanitize($_GET['type'] ?? 'all');
$category_id = (int)($_GET['cat'] ?? 0);
$selected_year = (int)($_GET['year'] ?? 0);
// Query Categories
$categories_stmt = $db->query("SELECT c.*, COUNT(b.id) as total_buku FROM categories c LEFT JOIN books b ON c.id = b.category_id GROUP BY c.id ORDER BY c.nama_kategori ASC");
$categories = $categories_stmt->fetchAll();
// Query Years (filtered by active category and search query)
$years_sql = "SELECT tahun_terbit, COUNT(id) as total_buku FROM books b WHERE 1=1";
$years_params = [];
if ($category_id > 0) {
    $years_sql .= " AND b.category_id = :cat";
    $years_params[':cat'] = $category_id;
}
if (!empty($search)) {
    if ($type === 'judul') {
        $years_sql .= " AND b.judul LIKE :q";
        $years_params[':q'] = "%$search%";
    } elseif ($type === 'penulis') {
        $years_sql .= " AND b.pengarang LIKE :q";
        $years_params[':q'] = "%$search%";
    } elseif ($type === 'isbn') {
        $years_sql .= " AND b.isbn LIKE :q";
        $years_params[':q'] = "%$search%";
    } else {
        $years_sql .= " AND (b.judul LIKE :q1 OR b.pengarang LIKE :q2 OR b.isbn LIKE :q3 OR b.penerbit LIKE :q4)";
        $years_params[':q1'] = "%$search%";
        $years_params[':q2'] = "%$search%";
        $years_params[':q3'] = "%$search%";
        $years_params[':q4'] = "%$search%";
    }
}
$years_sql .= " GROUP BY tahun_terbit ORDER BY tahun_terbit DESC";
$years_stmt = $db->prepare($years_sql);
$years_stmt->execute($years_params);
$years = $years_stmt->fetchAll();
// Build Dynamic SQL Query for Books
$sql = "SELECT b.*, c.nama_kategori, COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE book_id = b.id), 0.0) as rating FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE 1=1";
$params = [];
if ($category_id > 0) {
    $sql .= " AND b.category_id = :cat";
    $params[':cat'] = $category_id;
}
if ($selected_year > 0) {
    $sql .= " AND b.tahun_terbit = :year";
    $params[':year'] = $selected_year;
}
if (!empty($search)) {
    if ($type === 'judul') {
        $sql .= " AND b.judul LIKE :q";
        $params[':q'] = "%$search%";
    } elseif ($type === 'penulis') {
        $sql .= " AND b.pengarang LIKE :q";
        $params[':q'] = "%$search%";
    } elseif ($type === 'isbn') {
        $sql .= " AND b.isbn LIKE :q";
        $params[':q'] = "%$search%";
    } else {
        $sql .= " AND (b.judul LIKE :q1 OR b.pengarang LIKE :q2 OR b.isbn LIKE :q3 OR b.penerbit LIKE :q4)";
        $params[':q1'] = "%$search%";
        $params[':q2'] = "%$search%";
        $params[':q3'] = "%$search%";
        $params[':q4'] = "%$search%";
    }
}
$sql .= " ORDER BY b.id DESC";
$books_stmt = $db->prepare($sql);
$books_stmt->execute($params);
$books = $books_stmt->fetchAll();
// Handle AJAX Request
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    ob_start();
    if (empty($books)) {
        ?>
        <div class="text-center py-5 col-12 w-100">
          <i class="bi bi-book-half" style="font-size: 3rem; color: var(--text-muted); opacity: 0.4;"></i>
          <h5 class="mt-3" style="color: var(--text-secondary);">Buku tidak ditemukan</h5>
          <p class="small" style="color: var(--text-muted);">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
        </div>
        <?php
    } else {
        ?>
        <div class="katalog-grid">
          <?php foreach ($books as $i => $book): ?>
            <a href="<?= BASE_URL; ?>detail-buku/<?= $book['slug']; ?>" class="book-card" style="--i:<?= $i; ?>">
              <div class="book-card__cover">
                <img class="book-card__cover-img" src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($book['cover_image']); ?>" alt="<?= htmlspecialchars($book['judul']); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                <div class="book-card__cover-placeholder" style="display:none;background:linear-gradient(135deg,var(--primary),var(--primary-dark));">
                  <span class="book-title-overlay"><?= htmlspecialchars($book['judul']); ?></span>
                  <span class="book-author-overlay"><?= htmlspecialchars($book['pengarang']); ?></span>
                </div>
                <?php if (!empty($book['file_ebook'])): ?>
                  <span class="book-card__badge fiksi"><i class="bi bi-tablet"></i> E-Book</span>
                <?php endif; ?>
              </div>
              <div class="book-card__body">
                <span class="book-card__kategori"><?= htmlspecialchars($book['nama_kategori'] ?? 'Umum'); ?></span>
                <h6 class="book-card__title"><?= htmlspecialchars($book['judul']); ?></h6>
                <p class="book-card__author"><?= htmlspecialchars($book['pengarang']); ?></p>
                <div class="book-card__footer">
                  <span class="book-card__rating"><i class="bi bi-star-fill"></i> <span><?= $book['rating']; ?></span></span>
                  <span class="book-card__stock <?= $book['stok'] > 0 ? 'available' : 'empty'; ?>">
                    <?= $book['stok'] > 0 ? 'Stok: ' . $book['stok'] : 'Habis'; ?>
                  </span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
        <?php
    }
    $html = ob_get_clean();
    echo json_encode(['count' => count($books), 'html' => $html]);
    exit;
}
$page_title = "Katalog OPAC — Gerbang Literasi";
$active_nav = "katalog";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
    <div class="katalog-header">
      <div class="container">
        <h1 class="katalog-header__title">OPAC Katalog Koleksi</h1>
        <p class="katalog-header__desc">Jelajahi ribuan koleksi e-book dan buku digital terproteksi kami secara online.</p>
      </div>
    </div>
    <div class="container py-4">
      <div class="row g-4">
        <!-- Sidebar Kategori & Tahun -->
        <div class="col-lg-3 d-none d-lg-block">
          <div class="katalog-sidebar">
            <div class="katalog-sidebar__header">
              <i class="bi bi-funnel"></i>
              <span>Kategori</span>
            </div>
            <ul class="katalog-sidebar__list" id="categoryList">
              <li class="katalog-sidebar__item <?= $category_id === 0 ? 'active' : ''; ?>">
                <a href="<?= get_filter_url(['cat' => 0]); ?>" class="katalog-sidebar__link">
                  <i class="bi bi-grid-3x3-gap"></i>
                  <span>Semua Kategori</span>
                </a>
              </li>
              <?php foreach ($categories as $cat): ?>
                <li class="katalog-sidebar__item <?= $category_id === (int)$cat['id'] ? 'active' : ''; ?>">
                  <a href="<?= get_filter_url(['cat' => $cat['id']]); ?>" class="katalog-sidebar__link">
                    <i class="bi bi-bookmark"></i>
                    <span><?= htmlspecialchars($cat['nama_kategori']); ?></span>
                    <span class="katalog-sidebar__count"><?= $cat['total_buku']; ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="katalog-sidebar mt-4">
            <div class="katalog-sidebar__header d-flex align-items-center justify-content-between" 
                 data-bs-toggle="collapse" 
                 data-bs-target="#yearListCollapse" 
                 aria-expanded="true" 
                 aria-controls="yearListCollapse" 
                 style="cursor: pointer; user-select: none;">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar-event"></i>
                <span>Tahun Terbit</span>
              </div>
              <i class="bi bi-chevron-down collapse-arrow-icon"></i>
            </div>
            <div class="collapse show" id="yearListCollapse">
              <ul class="katalog-sidebar__list" id="yearList" style="border-top: 1px solid var(--border); margin-top: 0;">
                <li class="katalog-sidebar__item <?= $selected_year === 0 ? 'active' : ''; ?>">
                  <a href="<?= get_filter_url(['year' => 0]); ?>" class="katalog-sidebar__link">
                    <i class="bi bi-calendar-range"></i>
                    <span>Semua Tahun</span>
                  </a>
                </li>
                <?php foreach ($years as $yr): ?>
                  <li class="katalog-sidebar__item <?= $selected_year === (int)$yr['tahun_terbit'] ? 'active' : ''; ?>">
                    <a href="<?= get_filter_url(['year' => $yr['tahun_terbit']]); ?>" class="katalog-sidebar__link">
                      <i class="bi bi-calendar3"></i>
                      <span>Tahun <?= $yr['tahun_terbit']; ?></span>
                      <span class="katalog-sidebar__count"><?= $yr['total_buku']; ?></span>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
        <!-- Main Content -->
        <div class="col-lg-9">
          <!-- Mobile Filter Buttons Trigger -->
          <div class="d-lg-none mb-3">
            <div class="row g-2">
              <div class="col-6">
                <button type="button" class="btn w-100 mobile-category-btn" data-bs-toggle="offcanvas" data-bs-target="#categoryBottomSheet" aria-controls="categoryBottomSheet" style="font-size: 0.82rem; padding: 10px 12px;">
                  <i class="bi bi-funnel-fill me-1 text-primary"></i>
                  Kat: <strong class="text-truncate ms-1" style="max-width: 70px; display: inline-block; vertical-align: bottom;">
                    <?php 
                    $selected_cat_name = 'Semua';
                    foreach ($categories as $cat) {
                        if ($category_id === (int)$cat['id']) {
                            $selected_cat_name = $cat['nama_kategori'];
                            break;
                        }
                    }
                    echo htmlspecialchars($selected_cat_name);
                    ?>
                  </strong>
                </button>
              </div>
              <div class="col-6">
                <button type="button" class="btn w-100 mobile-category-btn" data-bs-toggle="offcanvas" data-bs-target="#yearBottomSheet" aria-controls="yearBottomSheet" style="font-size: 0.82rem; padding: 10px 12px;">
                  <i class="bi bi-calendar-event me-1 text-primary"></i>
                  Thn: <strong class="ms-1"><?= $selected_year > 0 ? $selected_year : 'Semua'; ?></strong>
                </button>
              </div>
            </div>
          </div>
          <!-- Form Search Bar Dynamic -->
          <form action="<?= BASE_URL; ?>katalog" method="GET" class="katalog-searchbar mb-4">
            <?php if ($category_id > 0): ?>
              <input type="hidden" name="cat" value="<?= $category_id; ?>" />
            <?php endif; ?>
            <?php if ($selected_year > 0): ?>
              <input type="hidden" name="year" value="<?= $selected_year; ?>" />
            <?php endif; ?>
            <div class="katalog-searchbar__input-wrap">
              <i class="bi bi-search"></i>
              <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Cari buku, penulis, ISBN..." />
            </div>
            <select name="type" class="katalog-searchbar__select">
              <option value="all" <?= $type === 'all' ? 'selected' : ''; ?>>Semua Kolom</option>
              <option value="judul" <?= $type === 'judul' ? 'selected' : ''; ?>>Judul Buku</option>
              <option value="penulis" <?= $type === 'penulis' ? 'selected' : ''; ?>>Penulis</option>
              <option value="isbn" <?= $type === 'isbn' ? 'selected' : ''; ?>>ISBN</option>
            </select>
            <button type="submit" class="katalog-searchbar__btn">Cari</button>
          </form>
          <!-- Results Info -->
          <div class="katalog-results-info mb-3">
            Menampilkan <strong id="catalogResultsCount"><?= count($books); ?></strong> judul buku dalam koleksi.
          </div>
          <!-- Book Cards Grid Container -->
          <div id="catalogCardsContainer">
            <?php if (empty($books)): ?>
              <div class="text-center py-5 col-12 w-100">
                <i class="bi bi-book-half" style="font-size: 3rem; color: var(--text-muted); opacity: 0.4;"></i>
                <h5 class="mt-3" style="color: var(--text-secondary);">Buku tidak ditemukan</h5>
                <p class="small" style="color: var(--text-muted);">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
              </div>
            <?php else: ?>
              <div class="katalog-grid">
                <?php foreach ($books as $i => $book): ?>
                  <a href="<?= BASE_URL; ?>detail-buku/<?= $book['slug']; ?>" class="book-card" style="--i:<?= $i; ?>">
                    <div class="book-card__cover">
                      <img class="book-card__cover-img" src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($book['cover_image']); ?>" alt="<?= htmlspecialchars($book['judul']); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                      <div class="book-card__cover-placeholder" style="display:none;background:linear-gradient(135deg,var(--primary),var(--primary-dark));">
                        <span class="book-title-overlay"><?= htmlspecialchars($book['judul']); ?></span>
                        <span class="book-author-overlay"><?= htmlspecialchars($book['pengarang']); ?></span>
                      </div>
                      <?php if (!empty($book['file_ebook'])): ?>
                        <span class="book-card__badge fiksi"><i class="bi bi-tablet"></i> E-Book</span>
                      <?php endif; ?>
                    </div>
                    <div class="book-card__body">
                      <span class="book-card__kategori"><?= htmlspecialchars($book['nama_kategori'] ?? 'Umum'); ?></span>
                      <h6 class="book-card__title"><?= htmlspecialchars($book['judul']); ?></h6>
                      <p class="book-card__author"><?= htmlspecialchars($book['pengarang']); ?></p>
                      <div class="book-card__footer">
                        <span class="book-card__rating"><i class="bi bi-star-fill"></i> <span><?= $book['rating']; ?></span></span>
                        <span class="book-card__stock <?= $book['stok'] > 0 ? 'available' : 'empty'; ?>">
                          <?= $book['stok'] > 0 ? 'Stok: ' . $book['stok'] : 'Habis'; ?>
                        </span>
                      </div>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
<!-- Category Bottom Sheet for Mobile -->
<div class="offcanvas offcanvas-bottom category-bottom-sheet" tabindex="-1" id="categoryBottomSheet" aria-labelledby="categoryBottomSheetLabel">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold" id="categoryBottomSheetLabel"><i class="bi bi-funnel-fill text-primary me-2"></i>Pilih Kategori</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="list-group list-group-flush">
      <a href="<?= get_filter_url(['cat' => 0]); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 <?= $category_id === 0 ? 'active' : ''; ?>">
        <i class="bi bi-grid-3x3-gap-fill fs-5"></i>
        <span>Semua Kategori</span>
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="<?= get_filter_url(['cat' => $cat['id']]); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3 <?= $category_id === (int)$cat['id'] ? 'active' : ''; ?>">
          <div class="d-flex align-items-center gap-3">
            <i class="bi bi-bookmark-fill fs-5"></i>
            <span><?= htmlspecialchars($cat['nama_kategori']); ?></span>
          </div>
          <span class="badge bg-light text-secondary border rounded-pill px-3 py-2" style="font-size:0.75rem;"><?= $cat['total_buku']; ?> Buku</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<!-- Year Bottom Sheet for Mobile -->
<div class="offcanvas offcanvas-bottom category-bottom-sheet" tabindex="-1" id="yearBottomSheet" aria-labelledby="yearBottomSheetLabel">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold" id="yearBottomSheetLabel"><i class="bi bi-calendar-event-fill text-primary me-2"></i>Pilih Tahun Terbit</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="list-group list-group-flush">
      <a href="<?= get_filter_url(['year' => 0]); ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 <?= $selected_year === 0 ? 'active' : ''; ?>">
        <i class="bi bi-calendar-range-fill fs-5"></i>
        <span>Semua Tahun</span>
      </a>
      <?php foreach ($years as $yr): ?>
        <a href="<?= get_filter_url(['year' => $yr['tahun_terbit']]); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3 <?= $selected_year === (int)$yr['tahun_terbit'] ? 'active' : ''; ?>">
          <div class="d-flex align-items-center gap-3">
            <i class="bi bi-calendar3 fs-5"></i>
            <span>Tahun <?= $yr['tahun_terbit']; ?></span>
          </div>
          <span class="badge bg-light text-secondary border rounded-pill px-3 py-2" style="font-size:0.75rem;"><?= $yr['total_buku']; ?> Buku</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchForm = document.querySelector('.katalog-searchbar');
  const searchInput = document.querySelector('.katalog-searchbar input[name="q"]');
  const searchType = document.querySelector('.katalog-searchbar select[name="type"]');
  const resultsCount = document.getElementById('catalogResultsCount');
  const cardsContainer = document.getElementById('catalogCardsContainer');
  function generateCardSkeletons(count = 5) {
    let html = '<div class="katalog-grid">';
    for (let i = 0; i < count; i++) {
      html += `
        <div class="book-card-skeleton skeleton-pulse">
          <div class="book-card-skeleton__cover"></div>
          <div class="book-card-skeleton__body">
            <div class="skeleton-line w-30"></div>
            <div class="skeleton-line w-100"></div>
            <div class="skeleton-line w-75"></div>
            <div class="d-flex justify-content-between mt-2">
              <div class="skeleton-line w-30"></div>
              <div class="skeleton-line w-30"></div>
            </div>
          </div>
        </div>
      `;
    }
    html += '</div>';
    return html;
  }
  async function performCatalogSearch() {
    const q = searchInput ? searchInput.value : '';
    const type = searchType ? searchType.value : 'all';
    
    // Read current URL params for cat and year
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('q', q);
    urlParams.set('type', type);
    urlParams.set('ajax', '1');
    if (cardsContainer) {
      cardsContainer.innerHTML = generateCardSkeletons(5);
    }
    try {
      await new Promise(resolve => setTimeout(resolve, 300));
      const response = await fetch('katalog?' + urlParams.toString());
      const data = await response.json();
      if (resultsCount) {
        resultsCount.textContent = data.count;
      }
      if (cardsContainer) {
        cardsContainer.innerHTML = data.html;
      }
      // Update URL state without page reload
      urlParams.delete('ajax');
      const newUrl = window.location.pathname + '?' + urlParams.toString();
      window.history.replaceState({}, '', newUrl);
    } catch (err) {
      console.error('Error in catalog search:', err);
    }
  }
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      e.preventDefault();
      performCatalogSearch();
    });
  }
  if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('keyup', function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(performCatalogSearch, 250);
    });
  }
  if (searchType) {
    searchType.addEventListener('change', performCatalogSearch);
  }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
