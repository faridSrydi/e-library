<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/helpers/auth_helper.php';

require_login();

$db = Database::getConnection();
$user = get_user();

// Dukungan slug & id
$slug = sanitize($_GET['slug'] ?? '');
$book_id = (int)($_GET['id'] ?? 0);

if (empty($slug) && $book_id <= 0) {
    set_flash('danger', 'Buku tidak ditemukan.');
    header('Location: ' . BASE_URL . 'user/dashboard');
    exit;
}

// Cek data buku
if (!empty($slug)) {
    $stmt = $db->prepare("SELECT * FROM books WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
} else {
    $stmt = $db->prepare("SELECT * FROM books WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $book_id]);
}
$book = $stmt->fetch();

if (!$book || empty($book['file_ebook'])) {
    set_flash('danger', 'File e-book tidak tersedia.');
    header('Location: ' . BASE_URL . 'user/dashboard');
    exit;
}

// Canonical redirect: jika akses pakai id atau .php, redirect ke slug
if (empty($slug) || strpos($_SERVER['REQUEST_URI'], 'baca-ebook.php') !== false) {
    header('Location: ' . BASE_URL . 'baca-ebook/' . $book['slug'], true, 301);
    exit;
}

// Hak Akses: Admin & Petugas bebas akses, Anggota harus punya peminjaman status 'dipinjam'
if ($user['role'] === 'anggota') {
    $check = $db->prepare("SELECT id FROM borrowings WHERE user_id = :uid AND book_id = :bid AND status = 'dipinjam' LIMIT 1");
    $check->execute([':uid' => $user['id'], ':bid' => $book['id']]);
    if (!$check->fetch()) {
        set_flash('warning', 'Anda harus meminjam buku ini terlebih dahulu untuk memuat pembaca e-book.');
        header('Location: ' . BASE_URL . 'detail-buku/' . $book['slug']);
        exit;
    }
}

// Baca PDF langsung dari server dan encode base64
$ebook_path = STORAGE_PATH . 'ebooks/' . $book['file_ebook'];
if (!file_exists($ebook_path)) {
    $ebook_path = __DIR__ . '/assets/sample.pdf';
}
$pdf_base64 = base64_encode(file_get_contents($ebook_path));
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Membaca: <?= htmlspecialchars($book['judul']); ?> — Gerbang Literasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
      *, *::before, *::after { box-sizing: border-box; }
      body, html {
        margin: 0; padding: 0; height: 100%; overflow: hidden;
        background: #111827;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        user-select: none;
      }

      /* ── Header ── */
      .reader-header {
        height: 50px;
        background: #0a0e1a;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1rem;
        border-bottom: 1px solid #1e2942;
        position: relative;
        z-index: 10;
      }
      .reader-header .btn-sm { font-size: 0.76rem; }

      .mode-toggle {
        display: flex;
        background: #1a2036;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #2d3555;
      }
      .mode-toggle button {
        background: transparent; border: none; color: #6b7aa0;
        padding: 5px 14px; font-size: 0.78rem; cursor: pointer;
        transition: all 0.25s; display: flex; align-items: center; gap: 5px;
      }
      .mode-toggle button.active { background: #4f46e5; color: #fff; }
      .mode-toggle button:hover:not(.active) { color: #a5b4d4; }

      /* ── Loading ── */
      #pdfLoading {
        display: flex; align-items: center; justify-content: center;
        height: calc(100vh - 50px);
        height: calc(100dvh - 50px);
        flex-direction: column; gap: 12px; color: #6b7aa0;
      }

      /* ── VERTICAL MODE ── */
      #verticalView {
        height: calc(100vh - 50px);
        height: calc(100dvh - 50px);
        overflow-y: auto; overflow-x: hidden;
        display: flex; flex-direction: column; align-items: center;
        gap: 16px; padding: 24px 16px;
        scrollbar-width: thin; scrollbar-color: #4f46e544 transparent;
      }
      #verticalView::-webkit-scrollbar { width: 6px; }
      #verticalView::-webkit-scrollbar-thumb { background: #4f46e544; border-radius: 3px; }
      #verticalView .pdf-page {
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        border-radius: 4px; overflow: hidden; flex-shrink: 0;
      }
      #verticalView canvas { display: block; max-width: 100%; height: auto; }

      /* ── HORIZONTAL (SLIDE CAROUSEL LIKE INSTAGRAM) ── */
      #slideView {
        height: calc(100vh - 50px - 56px);
        height: calc(100dvh - 50px - 56px); /* minus header & bottom bar */
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
      }

      #slideViewport {
        width: 100%;
        height: 100%;
        overflow: hidden;
        position: relative;
      }

      #slideWrapper {
        display: flex;
        height: 100%;
        width: 100%;
        will-change: transform;
        transform: translateX(0px);
      }

      .slide-page-container {
        width: 100%;
        height: 100%;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        box-sizing: border-box;
        position: relative;
      }

      .slide-page-container canvas {
        display: block;
        max-height: 100%;
        max-width: 100%;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        border-radius: 4px;
        background: #ffffff;
      }

      /* Slide loading placeholder */
      .slide-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        color: #4b5563;
        width: 100%;
        height: 100%;
      }

      /* Nav buttons (Only on hover / desktop) */
      .slide-nav {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 44px; height: 44px; border-radius: 50%;
        background: rgba(31, 41, 55, 0.7);
        border: 1px solid rgba(75, 85, 99, 0.4);
        color: #f3f4f6; font-size: 1.1rem;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: all 0.2s; z-index: 5;
        backdrop-filter: blur(4px);
      }
      .slide-nav:hover { background: #4f46e5; border-color: #4f46e5; }
      .slide-nav:disabled { opacity: 0; pointer-events: none; }
      .slide-nav.prev { left: 20px; }
      .slide-nav.next { right: 20px; }

      /* Bottom bar */
      .slide-bottom {
        height: 56px;
        background: #0a0e1a;
        border-top: 1px solid #1e2942;
        display: flex; align-items: center; justify-content: center; gap: 20px;
        padding: 0 1.5rem;
        position: relative;
        z-index: 10;
      }
      .page-display {
        font-size: 0.88rem; color: #e2e8f0; font-weight: 600;
        display: flex; align-items: center; gap: 6px;
      }
      .page-display .current { color: #818cf8; font-size: 1.25rem; }
      .page-display .divider { color: #475569; }
      .page-display .total { color: #94a3b8; }

      /* Progress bar */
      .progress-track {
        flex: 1; max-width: 420px; height: 4px;
        background: #1e2942; border-radius: 2px; overflow: hidden;
      }
      .progress-fill {
        height: 100%; background: linear-gradient(90deg, #4f46e5, #818cf8);
        border-radius: 2px; transition: width 0.3s ease;
      }

      @media (max-width: 640px) {
        .slide-nav { display: none; } /* Hide navigation arrows on mobile, use swipe */
        .slide-page-container { padding: 8px; }
      }
    </style>
  </head>
  <body>
    <!-- Header -->
    <div class="reader-header">
      <div class="d-flex align-items-center gap-2 text-truncate">
        <?php if ($user['role'] === 'admin' || $user['role'] === 'petugas'): ?>
          <a href="<?= BASE_URL; ?>admin/kelola-buku" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
        <?php else: ?>
          <a href="<?= BASE_URL; ?>user/dashboard" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i> Rak Buku</a>
        <?php endif; ?>
        <span class="fw-semibold text-truncate d-none d-md-inline" style="font-size: 0.85rem; max-width: 320px; color: #cbd5e1;"><?= htmlspecialchars($book['judul']); ?></span>
      </div>
      <div class="d-flex align-items-center gap-3">
        <div class="mode-toggle">
          <button id="btnVertical" onclick="setMode('vertical')" title="Scroll ke bawah">
            <i class="bi bi-distribute-vertical"></i><span class="d-none d-md-inline">Scroll</span>
          </button>
          <button id="btnSlide" class="active" onclick="setMode('slide')" title="Slide per halaman">
            <i class="bi bi-book"></i><span class="d-none d-md-inline">Slide</span>
          </button>
        </div>
        <span class="badge rounded-pill small d-none d-lg-inline-block" style="padding: 5px 10px; font-size: 0.7rem; background: #1e1b4b; color: #818cf8; border: 1px solid #312e81;">DRM</span>
      </div>
    </div>

    <!-- Loading -->
    <div id="pdfLoading">
      <div class="spinner-border" role="status" style="width:2.5rem; height:2.5rem; color: #4f46e5;">
        <span class="visually-hidden">Loading...</span>
      </div>
      <span style="font-size:0.88rem;">Memuat e-book...</span>
    </div>

    <!-- Vertical Scroll View -->
    <div id="verticalView" style="display:none;"></div>

    <!-- Floating Page Indicator (Vertical Mode) -->
    <div id="verticalPageIndicator" style="position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); background: transparent; border: none; color: rgba(0, 0, 0, 0.45); font-size: 1.25rem; font-weight: 600; z-index: 100; pointer-events: none; transition: opacity 0.3s ease; opacity: 0; display: block;">
      Halaman <span id="vCurrentPage">1</span> / <span id="vTotalPages">—</span>
    </div>

    <!-- Slide View (Instagram Carousel Style) -->
    <div id="slideView" style="display:none;">
      <button class="slide-nav prev" id="btnPrev" onclick="slidePrev()"><i class="bi bi-chevron-left"></i></button>
      
      <div id="slideViewport">
        <div id="slideWrapper"></div>
      </div>

      <button class="slide-nav next" id="btnNext" onclick="slideNext()"><i class="bi bi-chevron-right"></i></button>
    </div>

    <!-- Bottom Bar (slide mode) -->
    <div class="slide-bottom" id="slideBottom" style="display:none;">
      <div class="progress-track">
        <div class="progress-fill" id="progressFill" style="width:0%;"></div>
      </div>
      <div class="page-display">
        <span class="current" id="currentPage">1</span>
        <span class="divider">/</span>
        <span class="total" id="totalPages">—</span>
      </div>
    </div>

    <script>
      pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

      let pdfDoc = null;
      let currentMode = 'slide';
      let currentSlide = 1;
      let totalPages = 0;
      let slideWidth = 0;
      const VERTICAL_SCALE = 1.5;

      // Decode base64 PDF
      const base64Data = '<?= $pdf_base64; ?>';
      const binaryStr = atob(base64Data);
      const pdfBytes = new Uint8Array(binaryStr.length);
      for (let i = 0; i < binaryStr.length; i++) {
        pdfBytes[i] = binaryStr.charCodeAt(i);
      }

      // Load PDF
      pdfjsLib.getDocument({ data: pdfBytes }).promise.then(function(pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        document.getElementById('totalPages').textContent = totalPages;
        document.getElementById('vTotalPages').textContent = totalPages;
        
        // Build empty slides structure
        buildSlidesDOM();
        
        // Hide loading state and set initial mode to slide (renders only 1 page to save memory)
        document.getElementById('pdfLoading').style.display = 'none';
        setMode('slide');
      }).catch(function() {
        document.getElementById('pdfLoading').innerHTML =
          '<i class="bi bi-exclamation-triangle" style="font-size:2rem; color:#f59e0b;"></i>' +
          '<span style="font-size:0.9rem; color:#f59e0b;">Gagal memuat e-book.</span>';
      });

      // Build slide DOM list placeholders
      function buildSlidesDOM() {
        const wrapper = document.getElementById('slideWrapper');
        wrapper.innerHTML = '';
        for (let i = 1; i <= totalPages; i++) {
          const slideDiv = document.createElement('div');
          slideDiv.className = 'slide-page-container';
          slideDiv.id = 'slide-page-' + i;
          
          // Loading placeholder inside each slide
          slideDiv.innerHTML = `
            <div class="slide-placeholder">
              <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
              <span style="font-size: 0.75rem;">Halaman ${i}</span>
            </div>
          `;
          wrapper.appendChild(slideDiv);
        }
      }

      // ── Vertical View: render pages ──
      function renderVerticalPages() {
        const container = document.getElementById('verticalView');
        container.innerHTML = '';
        const promises = [];
        for (let i = 1; i <= totalPages; i++) {
          promises.push(
            pdfDoc.getPage(i).then(function(page) {
              const vp = page.getViewport({ scale: VERTICAL_SCALE });
              const div = document.createElement('div');
              div.className = 'pdf-page';
              const canvas = document.createElement('canvas');
              canvas.width = vp.width;
              canvas.height = vp.height;
              div.appendChild(canvas);
              container.appendChild(div);
              return page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
            })
          );
        }
        Promise.all(promises).then(function() {
          document.getElementById('pdfLoading').style.display = 'none';
          if (currentMode === 'vertical') {
            container.style.display = 'flex';
            document.getElementById('verticalPageIndicator').style.display = 'block';
            showVerticalIndicator();
          }
        });
      }

      // ── Instagram Slide Carousel Logic ──
      const renderedPages = {}; // Track rendered canvases

      function updateSlideWidth() {
        const viewport = document.getElementById('slideViewport');
        if (viewport) {
          slideWidth = viewport.clientWidth;
        }
      }

      function goToSlide(pageNum, smooth = true) {
        currentSlide = pageNum;
        updateSlideWidth();
        const offset = - (currentSlide - 1) * slideWidth;
        const wrapper = document.getElementById('slideWrapper');
        
        wrapper.style.transition = smooth ? 'transform 0.3s cubic-bezier(0.215, 0.61, 0.355, 1)' : 'none';
        wrapper.style.transform = `translateX(${offset}px)`;

        // Update UI
        document.getElementById('currentPage').textContent = currentSlide;
        document.getElementById('progressFill').style.width = ((currentSlide / totalPages) * 100) + '%';
        document.getElementById('btnPrev').disabled = (currentSlide <= 1);
        document.getElementById('btnNext').disabled = (currentSlide >= totalPages);

        // Lazy load adjacent pages
        lazyLoadSlides();
      }

      function lazyLoadSlides() {
        const pagesToLoad = [currentSlide, currentSlide - 1, currentSlide + 1];
        
        // Render current and adjacent pages
        pagesToLoad.forEach(pageNum => {
          if (pageNum >= 1 && pageNum <= totalPages && !renderedPages[pageNum]) {
            renderedPages[pageNum] = true;
            renderCanvasForPage(pageNum);
          }
        });

        // PURGE: Clear/unload canvases that are far away from current view to save memory
        for (let i = 1; i <= totalPages; i++) {
          if (i !== currentSlide && i !== currentSlide - 1 && i !== currentSlide + 1) {
            if (renderedPages[i]) {
              renderedPages[i] = false;
              const container = document.getElementById('slide-page-' + i);
              if (container) {
                container.innerHTML = `
                  <div class="slide-placeholder">
                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    <span style="font-size: 0.75rem;">Halaman ${i}</span>
                  </div>
                `;
              }
            }
          }
        }
      }

      function renderCanvasForPage(pageNum) {
        const container = document.getElementById('slide-page-' + pageNum);
        if (!container) return;

        pdfDoc.getPage(pageNum).then(function(page) {
          const maxW = container.clientWidth - 32;
          const maxH = container.clientHeight - 32;
          const vpBase = page.getViewport({ scale: 1 });
          
          let scale = 1.0;
          if (maxW > 0 && maxH > 0) {
            scale = Math.min(maxW / vpBase.width, maxH / vpBase.height, 2.5);
          } else {
            const view = document.getElementById('slideViewport');
            const vw = (view ? view.clientWidth : window.innerWidth) - 32;
            const vh = (view ? view.clientHeight : window.innerHeight) - 32;
            if (vw > 0 && vh > 0) {
              scale = Math.min(vw / vpBase.width, vh / vpBase.height, 2.5);
            }
          }

          const vp = page.getViewport({ scale: scale });

          const canvas = document.createElement('canvas');
          canvas.width = vp.width;
          canvas.height = vp.height;

          container.innerHTML = ''; // Remove loading spinner
          container.appendChild(canvas);

          return page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
        }).catch(function() {
          renderedPages[pageNum] = false;
        });
      }

      function slideNext() {
        if (currentSlide < totalPages) {
          goToSlide(currentSlide + 1);
        }
      }

      function slidePrev() {
        if (currentSlide > 1) {
          goToSlide(currentSlide - 1);
        }
      }

      // ── Touch & Mouse Drag (Instagram Style) ──
      let touchStartX = 0;
      let isDragging = false;
      let deltaX = 0;
      const slideView = document.getElementById('slideView');
      const slideWrapper = document.getElementById('slideWrapper');

      function dragStart(clientX) {
        updateSlideWidth();
        touchStartX = clientX;
        isDragging = true;
        slideWrapper.style.transition = 'none'; // Follow drag instantly
      }

      function dragMove(clientX) {
        if (!isDragging) return;
        deltaX = clientX - touchStartX;

        // Boundary resistance
        if ((currentSlide === 1 && deltaX > 0) || (currentSlide === totalPages && deltaX < 0)) {
          deltaX = deltaX * 0.3;
        }

        const currentOffset = - (currentSlide - 1) * slideWidth;
        slideWrapper.style.transform = `translateX(${currentOffset + deltaX}px)`;
      }

      function dragEnd() {
        if (!isDragging) return;
        isDragging = false;

        const threshold = slideWidth * 0.2; // 20% width drag threshold

        if (deltaX < -threshold && currentSlide < totalPages) {
          goToSlide(currentSlide + 1);
        } else if (deltaX > threshold && currentSlide > 1) {
          goToSlide(currentSlide - 1);
        } else {
          goToSlide(currentSlide); // snap back
        }
        deltaX = 0;
      }

      // Touch events (iOS Safari optimization)
      slideView.addEventListener('touchstart', function(e) {
        if (e.touches && e.touches.length > 0) {
          dragStart(e.touches[0].clientX);
        }
      }, { passive: true });

      slideView.addEventListener('touchmove', function(e) {
        if (isDragging && e.touches && e.touches.length > 0) {
          if (e.cancelable) {
            e.preventDefault(); // Stop iOS history navigation (swipe back/forward) & body scrolling
          }
          dragMove(e.touches[0].clientX);
        }
      }, { passive: false });

      slideView.addEventListener('touchend', dragEnd);

      // Mouse events
      slideView.addEventListener('mousedown', function(e) {
        dragStart(e.clientX);
      });

      document.addEventListener('mousemove', function(e) {
        dragMove(e.clientX);
      });

      document.addEventListener('mouseup', dragEnd);

      // Window resize listener (Ignore height changes to prevent Safari bar resizing issues)
      let lastWidth = window.innerWidth;
      window.addEventListener('resize', function() {
        if (window.innerWidth === lastWidth) return;
        lastWidth = window.innerWidth;

        if (currentMode === 'slide') {
          // Clear cache on resize to scale canvases correctly
          for (let key in renderedPages) {
            renderedPages[key] = false;
            const container = document.getElementById('slide-page-' + key);
            if (container) {
              container.innerHTML = `
                <div class="slide-placeholder">
                  <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                  <span style="font-size: 0.75rem;">Halaman ${key}</span>
                </div>
              `;
            }
          }
          goToSlide(currentSlide, false);
        }
      });

      // ── Mode Switching ──
      function setMode(mode) {
        currentMode = mode;
        document.getElementById('btnVertical').classList.toggle('active', mode === 'vertical');
        document.getElementById('btnSlide').classList.toggle('active', mode === 'slide');

        const vert = document.getElementById('verticalView');
        const slide = document.getElementById('slideView');
        const bottom = document.getElementById('slideBottom');
        const vIndicator = document.getElementById('verticalPageIndicator');

        if (mode === 'vertical') {
          slide.style.display = 'none';
          bottom.style.display = 'none';
          vert.style.display = 'flex';
          vIndicator.style.display = 'block';
          
          // Re-render vertical pages since they were purged from memory
          document.getElementById('pdfLoading').style.display = 'flex';
          renderVerticalPages();
        } else {
          // Clear vertical pages from DOM to free up memory (Crucial for iOS Safari)
          vert.innerHTML = '';
          
          vert.style.display = 'none';
          vIndicator.style.display = 'none';
          slide.style.display = 'flex';
          bottom.style.display = 'flex';
          goToSlide(currentSlide, false);
        }
      }

      // ── Vertical Indicator Visibility Logic (Auto-Hide) ──
      let vIndicatorTimeout = null;

      function showVerticalIndicator() {
        if (currentMode !== 'vertical') return;
        const vIndicator = document.getElementById('verticalPageIndicator');
        
        vIndicator.style.opacity = '1';

        if (vIndicatorTimeout) {
          clearTimeout(vIndicatorTimeout);
        }

        vIndicatorTimeout = setTimeout(function() {
          vIndicator.style.opacity = '0';
        }, 1500);
      }

      // ── Vertical Scroll Page Tracking ──
      const vertView = document.getElementById('verticalView');
      vertView.addEventListener('scroll', function() {
        if (currentMode !== 'vertical') return;

        const pages = vertView.querySelectorAll('.pdf-page');
        let activePage = 1;
        const viewTop = vertView.scrollTop;
        const viewHeight = vertView.clientHeight;
        const viewCenter = viewTop + (viewHeight / 2);

        for (let i = 0; i < pages.length; i++) {
          const page = pages[i];
          const pageTop = page.offsetTop;
          const pageBottom = pageTop + page.offsetHeight;

          if (viewCenter >= pageTop && viewCenter <= pageBottom) {
            activePage = i + 1;
            break;
          }
        }
        document.getElementById('vCurrentPage').textContent = activePage;
        showVerticalIndicator();
      }, { passive: true });

      // Trigger indicator on touch/mouse actions
      vertView.addEventListener('touchstart', showVerticalIndicator, { passive: true });
      vertView.addEventListener('touchmove', showVerticalIndicator, { passive: true });
      vertView.addEventListener('mousedown', showVerticalIndicator);

      // ── Keyboard ──
      document.addEventListener('keydown', function(e) {
        if (currentMode === 'slide') {
          if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); slideNext(); }
          if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); slidePrev(); }
        }
        if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 's' || e.key === 'u')) {
          e.preventDefault();
        }
      });

      // Proteksi Right Click
      document.addEventListener('contextmenu', e => e.preventDefault());
    </script>
  </body>
</html>