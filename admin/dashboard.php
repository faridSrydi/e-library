<?php
$page_title = "Admin Dashboard";
$active_admin_nav = "dashboard";
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/format_helper.php';

$db = Database::getConnection();

// Fetch Statistik
$total_buku = $db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$total_anggota = $db->query("SELECT COUNT(*) FROM users WHERE role='anggota'")->fetchColumn();
$total_dipinjam = $db->query("SELECT COUNT(*) FROM borrowings WHERE status='dipinjam'")->fetchColumn();

// Fetch Peminjaman Terbaru (Limit 5)
$recent_borrows_stmt = $db->query("SELECT b.*, u.nama as nama_user, bk.judul as judul_buku FROM borrowings b JOIN users u ON b.user_id = u.id JOIN books bk ON b.book_id = bk.id ORDER BY b.id DESC LIMIT 5");
$recent_borrows = $recent_borrows_stmt->fetchAll();

// Fetch Buku Populer
$popular_books_stmt = $db->query("SELECT *, COALESCE((SELECT ROUND(AVG(rating), 1) FROM reviews WHERE book_id = books.id), 0.0) as rating FROM books ORDER BY rating DESC, id DESC LIMIT 4");
$popular_books = $popular_books_stmt->fetchAll();

// Fetch Kategori Data for Doughnut Chart
$cat_data = $db->query("
  SELECT c.nama_kategori, COUNT(b.id) as total 
  FROM categories c 
  LEFT JOIN books b ON b.category_id = c.id 
  GROUP BY c.id, c.nama_kategori
")->fetchAll();

$cat_labels = [];
$cat_totals = [];
foreach ($cat_data as $cd) {
    if ($cd['total'] > 0) {
        $cat_labels[] = $cd['nama_kategori'];
        $cat_totals[] = (int)$cd['total'];
    }
}

// Fallback if empty
if (empty($cat_labels)) {
    $cat_labels = ['Fiksi', 'Non-Fiksi', 'Teknologi', 'Sains', 'Sejarah'];
    $cat_totals = [12, 8, 5, 3, 6];
}

// Fetch Peminjaman 1 Bulan Terakhir (Default: 30 hari) secara dinamis & akurat
$indo_days_short = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

$days = [];
for ($i = 29; $i >= 0; $i--) {
    $date = new DateTime();
    $date->modify("-$i days");
    $day_label = (int)$date->format('j') . '/' . (int)$date->format('n');
    $days[$date->format('Y-m-d')] = [
        'label' => $day_label,
        'total' => 0
    ];
}

$daily_query = $db->query("
  SELECT DATE(tanggal_pinjam) as tanggal, COUNT(*) as total 
  FROM borrowings 
  WHERE tanggal_pinjam IS NOT NULL AND tanggal_pinjam >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
  GROUP BY tanggal
")->fetchAll();

foreach ($daily_query as $row) {
    if (isset($days[$row['tanggal']])) {
        $days[$row['tanggal']]['total'] = (int)$row['total'];
    }
}

$month_labels = [];
$month_totals = [];
foreach ($days as $d) {
    $month_labels[] = $d['label'];
    $month_totals[] = $d['total'];
}
?>

<div class="mb-4">
  <h5 class="fw-semibold mb-1" style="font-size: 0.98rem; color: var(--text); letter-spacing: -0.1px;">Selamat Datang, <?= htmlspecialchars($user['nama']); ?>! 👋</h5>
  <p class="text-muted small mb-0">Ringkasan operasional perpustakaan digital hari ini.</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-xl-4 col-sm-6">
    <div class="stat-card card-blue">
      <div class="stat-icon blue"><i class="bi bi-book"></i></div>
      <div class="stat-info">
        <h3 class="stat-value"><?= number_format($total_buku); ?></h3>
        <span class="stat-label">Total Judul E-Book</span>
      </div>
      <i class="bi bi-book stat-watermark"></i>
    </div>
  </div>
  <div class="col-xl-4 col-sm-6">
    <div class="stat-card card-green">
      <div class="stat-icon green"><i class="bi bi-people"></i></div>
      <div class="stat-info">
        <h3 class="stat-value"><?= number_format($total_anggota); ?></h3>
        <span class="stat-label">Anggota Terdaftar</span>
      </div>
      <i class="bi bi-people stat-watermark"></i>
    </div>
  </div>
  <div class="col-xl-4 col-sm-6">
    <div class="stat-card card-amber">
      <div class="stat-icon amber"><i class="bi bi-arrow-left-right"></i></div>
      <div class="stat-info">
        <h3 class="stat-value"><?= number_format($total_dipinjam); ?></h3>
        <span class="stat-label">Sedang Dipinjam</span>
      </div>
      <i class="bi bi-arrow-left-right stat-watermark"></i>
    </div>
  </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h6 class="fw-semibold mb-1" style="font-size: 0.88rem; color: var(--text);"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Tren Peminjaman E-Book</h6>
          <p class="text-muted small mb-0" id="borrowingChartSubtitle">Statistik peminjaman buku selama 1 bulan terakhir</p>
        </div>
        <div class="dropdown">
          <button class="btn btn-sm btn-light border dropdown-toggle fw-semibold d-flex align-items-center gap-2" type="button" id="chartFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem; padding: 0.45rem 0.75rem; border-radius: 8px;">
            <i class="bi bi-calendar3 text-primary"></i> <span>1 Bulan Terakhir</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="chartFilterDropdown" style="font-size: 0.78rem; border-radius: 8px; min-width: 150px;">
            <li><a class="dropdown-item py-2" href="#" data-filter="today"><i class="bi bi-clock me-2 text-muted"></i>Hari Ini</a></li>
            <li><a class="dropdown-item py-2" href="#" data-filter="week"><i class="bi bi-calendar-event me-2 text-muted"></i>7 Hari Terakhir</a></li>
            <li><a class="dropdown-item py-2 active" href="#" data-filter="1month"><i class="bi bi-calendar-week me-2 text-muted"></i>1 Bulan Terakhir</a></li>
            <li><a class="dropdown-item py-2" href="#" data-filter="month"><i class="bi bi-calendar-month me-2 text-muted"></i>6 Bulan Terakhir</a></li>
            <li><a class="dropdown-item py-2" href="#" data-filter="year"><i class="bi bi-calendar3 me-2 text-muted"></i>1 Tahun Terakhir</a></li>
          </ul>
        </div>
      </div>
      <div style="height: 260px; position: relative;">
        <canvas id="borrowingChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h6 class="fw-semibold mb-1" style="font-size: 0.88rem; color: var(--text);"><i class="bi bi-pie-chart me-2 text-success"></i>Distribusi Kategori</h6>
          <p class="text-muted small mb-0">Persentase buku berdasarkan kategori</p>
        </div>
      </div>
      <div style="height: 230px; width: 100%; position: relative; display: flex; align-items: center; justify-content: center;">
        <canvas id="categoryChart"></canvas>
        <div class="chart-center-label" style="position: absolute; text-align: center; pointer-events: none; z-index: 10; transform: translateY(-15px);">
          <div class="text-muted" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.7px; font-weight: 500;">Total Buku</div>
          <div class="fw-semibold" style="font-size: 1.35rem; color: var(--text); line-height: 1.1; margin-top: 2px;"><?= number_format($total_buku); ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="d-flex align-items-center justify-content-between p-3 border-bottom bg-white">
        <h6 class="fw-semibold mb-0" style="font-size: 0.88rem; color: var(--primary);"><i class="bi bi-clock-history me-2"></i>Peminjaman Terbaru</h6>
        <a href="<?= BASE_URL; ?>admin/peminjaman.php" class="btn btn-sm btn-outline-custom">Lihat Semua</a>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size: 0.84rem;">
          <thead class="table-light">
            <tr>
              <th>Peminjam</th>
              <th>Buku</th>
              <th>Tanggal</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_borrows)): ?>
              <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada transaksi peminjaman.</td></tr>
            <?php else: ?>
              <?php foreach ($recent_borrows as $rb): ?>
                <tr>
                  <td><strong style="font-weight: 500;"><?= htmlspecialchars($rb['nama_user']); ?></strong></td>
                  <td><?= htmlspecialchars($rb['judul_buku']); ?></td>
                  <td><?= format_tanggal($rb['tanggal_pinjam']); ?></td>
                  <td>
                    <span class="status-badge <?= $rb['status'] === 'dipinjam' ? 'dipinjam' : 'kembali'; ?>">
                      <?= $rb['status'] === 'dipinjam' ? 'Dipinjam' : 'Kembali'; ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
      <div class="p-3 border-bottom bg-white">
        <h6 class="fw-semibold mb-0" style="font-size: 0.88rem; color: var(--warning);"><i class="bi bi-fire me-2"></i>Koleksi Populer</h6>
      </div>
      <div class="p-3">
        <?php foreach ($popular_books as $pb): ?>
          <div class="popular-book-item">
            <img src="<?= BASE_URL; ?>assets/img/<?= htmlspecialchars($pb['cover_image']); ?>" onerror="this.src='https://placehold.co/100x150?text=Cover'" />
            <div class="popular-book-info">
              <h6 class="popular-book-title text-truncate"><?= htmlspecialchars($pb['judul']); ?></h6>
              <span class="popular-book-author"><?= htmlspecialchars($pb['pengarang']); ?></span>
              <div class="popular-book-rating">
                <i class="bi bi-star-fill"></i> <span><?= number_format($pb['rating'], 1); ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

      </div> <!-- content-area -->
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Line/Area Chart - Tren Peminjaman
      const ctxBar = document.getElementById('borrowingChart');
      let borrowingChart = null;

      if (ctxBar) {
        const ctx = ctxBar.getContext('2d');
        
        // Create linear gradient
        const gradient = ctx.createLinearGradient(0, 0, 0, 240);
        gradient.addColorStop(0, 'rgba(42, 143, 225, 0.35)');
        gradient.addColorStop(1, 'rgba(42, 143, 225, 0.00)');

        borrowingChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: <?= json_encode($month_labels); ?>,
            datasets: [{
              label: 'Jumlah Dipinjam',
              data: <?= json_encode($month_totals); ?>,
              backgroundColor: gradient,
              borderColor: '#2a8fe1',
              borderWidth: 3,
              fill: true,
              tension: 0.4,
              pointBackgroundColor: '#ffffff',
              pointBorderColor: '#2a8fe1',
              pointBorderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6,
              pointHoverBackgroundColor: '#2a8fe1',
              pointHoverBorderColor: '#ffffff',
              pointHoverBorderWidth: 2
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: '#0f172a',
                titleFont: { family: 'Inter', size: 12, weight: '600' },
                bodyFont: { family: 'Inter', size: 12 },
                padding: 10,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                  label: function(context) {
                    return `Dipinjam: ${context.raw} E-Book`;
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: { 
                  stepSize: 1,
                  font: { size: 11, family: 'Inter', weight: '500' },
                  color: '#64748b'
                },
                grid: { 
                  color: '#f1f5f9',
                  borderDash: [5, 5]
                }
              },
              x: {
                grid: { display: false },
                ticks: { 
                  font: { size: 11, family: 'Inter', weight: '500' },
                  color: '#64748b'
                }
              }
            }
          }
        });
      }

      // Filter Chart Event Listener
      const filterItems = document.querySelectorAll('[data-filter]');
      const filterDropdownBtn = document.getElementById('chartFilterDropdown');
      const chartSubtitle = document.getElementById('borrowingChartSubtitle');

      const filterLabels = {
        'today': 'Hari Ini',
        'week': '7 Hari Terakhir',
        '1month': '1 Bulan Terakhir',
        'month': '6 Bulan Terakhir',
        'year': '1 Tahun Terakhir'
      };

      const filterSubtitles = {
        'today': 'Statistik peminjaman buku per jam hari ini',
        'week': 'Statistik peminjaman buku selama 7 hari terakhir',
        '1month': 'Statistik peminjaman buku selama 1 bulan terakhir',
        'month': 'Statistik peminjaman buku selama 6 bulan terakhir',
        'year': 'Statistik peminjaman buku selama 1 tahun terakhir'
      };

      filterItems.forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const filter = this.getAttribute('data-filter');
          
          // Remove active class from all items and add to clicked item
          filterItems.forEach(el => el.classList.remove('active'));
          this.classList.add('active');

          // Show loading state
          filterDropdownBtn.disabled = true;
          filterDropdownBtn.innerHTML = `<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span> <span>Loading...</span>`;

          // Fetch data
          fetch(`<?= BASE_URL; ?>admin/get_borrowing_stats.php?filter=${filter}`)
            .then(response => response.json())
            .then(data => {
              if (borrowingChart) {
                // Update Chart Data
                borrowingChart.data.labels = data.labels;
                borrowingChart.data.datasets[0].data = data.totals;
                borrowingChart.update();
              }

              // Update UI
              filterDropdownBtn.innerHTML = `<i class="bi bi-calendar3 text-primary"></i> <span>${filterLabels[filter]}</span>`;
              chartSubtitle.textContent = filterSubtitles[filter];
            })
            .catch(error => {
              console.error('Error fetching chart data:', error);
              filterDropdownBtn.innerHTML = `<i class="bi bi-exclamation-triangle text-danger"></i> <span>Error</span>`;
            })
            .finally(() => {
              filterDropdownBtn.disabled = false;
            });
        });
      });

      // Doughnut Chart - Distribusi Kategori
      const ctxPie = document.getElementById('categoryChart');
      if (ctxPie) {
        new Chart(ctxPie.getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: <?= json_encode($cat_labels); ?>,
            datasets: [{
              data: <?= json_encode($cat_totals); ?>,
              backgroundColor: [
                '#2a8fe1',
                '#22c55e',
                '#f59e0b',
                '#a855f7',
                '#ec4899',
                '#64748b'
              ],
              borderWidth: 2,
              borderColor: '#ffffff',
              hoverOffset: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  boxWidth: 10,
                  boxHeight: 10,
                  padding: 15,
                  font: { size: 11, weight: '500', family: 'Inter' },
                  color: '#475569',
                  usePointStyle: true,
                  pointStyle: 'circle'
                }
              },
              tooltip: {
                backgroundColor: '#0f172a',
                titleFont: { family: 'Inter', size: 12, weight: '600' },
                bodyFont: { family: 'Inter', size: 12 },
                padding: 10,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                  label: function(context) {
                    const value = context.raw;
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = Math.round((value / total) * 100);
                    return ` ${context.label}: ${value} Buku (${percentage}%)`;
                  }
                }
              }
            },
            cutout: '75%'
          }
        });
      }
    });
    </script>
  </body>
</html>
