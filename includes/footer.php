    <footer class="site-footer">
      <div class="container">
        <div class="row g-4 justify-content-between">
          <!-- Brand Info Column -->
          <div class="col-lg-4 col-md-6">
            <div class="site-footer__brand-title">
              <a href="<?= BASE_URL; ?>">
                <img src="<?= BASE_URL; ?>assets/img/icon.png" alt="Gerbang Literasi Logo" class="site-footer__brand-logo" />
              </a>
            </div>
            <p class="site-footer__brand-desc">
              Mendedikasikan diri untuk penyebaran ilmu pengetahuan melalui teknologi perpustakaan digital terdepan di Indonesia.
            </p>
            <div class="site-footer__socials">
              <a href="https://instagram.com" target="_blank" class="site-footer__social-link instagram" title="Instagram"><i class="bi bi-instagram"></i></a>
              <a href="https://facebook.com" target="_blank" class="site-footer__social-link facebook" title="Facebook"><i class="bi bi-facebook"></i></a>
              <a href="https://twitter.com" target="_blank" class="site-footer__social-link twitter" title="Twitter / X"><i class="bi bi-twitter-x"></i></a>
              <a href="https://youtube.com" target="_blank" class="site-footer__social-link youtube" title="YouTube"><i class="bi bi-youtube"></i></a>
            </div>
          </div>

          <!-- Library Navigation Column -->
          <div class="col-lg-2 col-md-3 col-6">
            <h6 class="site-footer__heading">NAVIGASI</h6>
            <ul class="site-footer__list">
              <li class="site-footer__list-item"><a href="<?= BASE_URL; ?>" class="site-footer__link">Beranda</a></li>
              <li class="site-footer__list-item"><a href="<?= BASE_URL; ?>katalog" class="site-footer__link">Katalog Buku</a></li>
              <li class="site-footer__list-item"><a href="<?= BASE_URL; ?>user/dashboard" class="site-footer__link">Rak Buku Saya</a></li>
            </ul>
          </div>

          <!-- Support Column -->
          <div class="col-lg-2 col-md-3 col-6">
            <h6 class="site-footer__heading">AKUN & AKSES</h6>
            <ul class="site-footer__list">
              <li class="site-footer__list-item"><a href="<?= BASE_URL; ?>login" class="site-footer__link">Masuk ke Akun</a></li>
              <li class="site-footer__list-item"><a href="<?= BASE_URL; ?>register" class="site-footer__link">Pendaftaran Anggota</a></li>
            </ul>
          </div>

          <!-- Contact & Operational Column -->
          <div class="col-lg-3 col-md-6">
            <h6 class="site-footer__heading">KONTAK KAMI</h6>
            <ul class="site-footer__contact-info">
              <li class="site-footer__contact-item">
                <i class="bi bi-geo-alt-fill"></i>
                <div class="site-footer__contact-text">
                  Jl. Perpustakaan Raya No. 45, Jakarta Pusat, DKI Jakarta 10110
                </div>
              </li>
              <li class="site-footer__contact-item">
                <i class="bi bi-envelope-fill"></i>
                <div class="site-footer__contact-text">
                  support@gerbangliterasi.id
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- Copyright Bottom Bar -->
        <div class="site-footer__bottom">
          <div class="site-footer__copyright">
            &copy; <?= date('Y'); ?> <strong style="color: var(--primary)">Gerbang Literasi</strong> — Perpustakaan Digital Indonesia. All rights reserved.
          </div>
          <div class="site-footer__bottom-links">
            <a href="#" class="site-footer__bottom-link">Kebijakan Privasi</a>
            <a href="#" class="site-footer__bottom-link">Syarat & Ketentuan</a>
          </div>
        </div>
      </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($extra_js)) echo $extra_js; ?>
  </body>
</html>
