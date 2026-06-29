# Gerbang Literasi — E-Library & Digital DRM System

<video src="assets/vid/perpus.mp4" width="100%" controls autoplay loop muted></video>

---

### 👨‍🎓 Identitas Pengembang
* **Nama**: Muhamad Faridzqi Suryadi
* **NIM**: 24260045
* **Program Studi**: Teknik Informatika

---

Platform e-library digital modern berbasis web yang mengimplementasikan sistem pembatasan lisensi digital (Digital Rights Management / DRM), antrean lisensi otomatis, serta fitur pembaca e-book langsung di dalam browser tanpa plugin tambahan.

Sistem ini didesain menggunakan **PHP Native** murni dengan arsitektur berstandar tinggi, antarmuka premium, serta efisiensi memori yang optimal untuk rendering dokumen e-book berukuran besar (optimized for iOS Safari & Mobile browsers).

---

## 📸 Preview Tampilan

Klik pada gambar untuk memperbesar (zoom) tampilan.

| [![Landing Page](assets/img/ss/landing_page.png)](assets/img/ss/landing_page.png) | [![Katalog Book](assets/img/ss/katalog_book.png)](assets/img/ss/katalog_book.png) | [![Admin Dashboard](assets/img/ss/admin_dashboard.png)](assets/img/ss/admin_dashboard.png) |
| :---: | :---: | :---: |
| **Landing Page** | **Katalog Book** | **Admin Dashboard** |

---

## 🚀 Fitur Utama

### 📱 1. Responsive & Smooth UI/UX
* **Smooth Hamburger Drawer Menu**: Panel navigasi responsif dengan animasi geser ala laci yang halus (*drawer-like slide animation*).
* **Responsive Staggered Items**: Efek transisi masuk item secara bertahap (*staggered animations*) untuk menu navigasi dan tombol aksi.
* **Dynamic Scroll States**: Efek perubahan tampilan header navbar secara real-time saat halaman di-scroll ke bawah.
* **Mobile Dropdown Inline**: Penyesuaian layout otomatis pada layar perangkat mobile, menjaga menu dropdown pengguna agar tidak terpotong oleh pembatas layar (*viewport clipping*).

### 🛡️ 2. Digital Rights Management (DRM) & Auto-Return
* **Strict License Control**: Pengguna hanya dapat membaca e-book jika memiliki lisensi aktif (status peminjaman `dipinjam`).
* **Auto-Expiration & Auto-Return**: Lisensi e-book secara otomatis dicabut ketika masa berlaku habis tanpa perlu tindakan manual dari pustakawan.
* **Precision Queue System**: Apabila lisensi e-book habis (stok buku 0), pengguna lain dapat masuk ke daftar antrean (*waiting list*). Lisensi akan otomatis dialihkan ke antrean berikutnya sesegera mungkin setelah lisensi aktif sebelumnya dilepaskan/dikembalikan.

### 📄 3. Modern E-Book PDF Reader
* **Dual Reading Mode**: Pengguna dapat memilih mode membaca secara vertikal (*Scroll*) atau per halaman (*Instagram Slide Style*).
* **Instagram-Style Slide View**: Transisi halaman dengan animasi geser berbasis *touch-swipe* (untuk mobile/tablet) dan drag mouse (untuk desktop).
* **Memory Optimization & Lazy Loading**: Kanvas PDF hanya dirender untuk halaman aktif dan halaman terdekat (adjacent pages). Halaman yang jauh akan dihapus otomatis dari memori demi performa responsif di perangkat mobile.
* **Source Protection**: File e-book diubah menjadi representasi base64 dan dilindungi dari klik kanan, pencetakan langsung, serta pintasan keyboard pengunduhan (`Ctrl+P`, `Ctrl+S`, `Ctrl+U`).

---

## 🛠️ Stack Teknologi

* **Backend**: PHP Native (version >= 7.4)
* **Database**: MySQL / MariaDB (PDO Engine)
* **Frontend / Styling**: Vanilla CSS & Bootstrap 5 (CSS-driven Transitions & Layouts)
* **Icons**: Bootstrap Icons (CDN)
* **Library PDF**: PDF.js (v3.11.174 via Cloudflare CDN)

---

## 📂 Struktur Project

```text
e-library/
├── actions/             # File aksi pemrosesan data (login, pinjam, dll)
├── admin/               # Panel admin untuk kelola buku, anggota & lisensi
├── assets/              # Asset statis (CSS, Javascript, Gambar, SVG)
│   ├── css/
│   │   └── style.css    # Custom styling e-library
│   └── img/             # Logo & sampul buku
├── config/              # Konfigurasi database & security
├── helpers/             # Helper fungsi (format tanggal, auth, borrow)
├── includes/            # Layout re-usable (header, footer, navbar, sidebar)
├── storage/             # Tempat penyimpanan file privat
│   ├── avatars/         # Foto profil pengguna
│   └── ebooks/          # File e-book PDF terproteksi
├── user/                # Halaman dashboard & settings anggota
├── elibrary_db.sql      # Skema & data lengkap database ter-export
├── index.php            # Halaman landing page / katalog publik
└── README.md            # Dokumentasi proyek
```

---

## ⚙️ Langkah Instalasi & Menjalankan Project

### Prasyarat
* **Web Server**: Apache (Laragon / XAMPP / MAMP)
* **PHP**: Versi 7.4 ke atas
* **Database**: MySQL / MariaDB

### Langkah-langkah

1. **Copy/Clone Project**
   Letakkan direktori project ini ke dalam folder server lokal Anda (misal `C:\laragon\www\e-library` atau `htdocs/e-library`).

2. **Setup Database**
   * Nyalakan server MySQL.
   * Import file [elibrary_db.sql](file:///c:/laragon/www/perpustakaan/elibrary_db.sql) ke dalam server MySQL Anda (melalui phpMyAdmin / Adminer / CLI).
     > [!NOTE]
     > Anda tidak perlu membuat database kosong terlebih dahulu karena perintah `CREATE DATABASE IF NOT EXISTS elibrary_db` dan `USE elibrary_db` sudah tertulis otomatis di bagian atas file SQL tersebut.

3. **Konfigurasi Server & Database**
   Sesuaikan konfigurasi koneksi database dan domain web Anda pada file berikut:
   * **Database**: Buka [database.php](file:///c:/laragon/www/perpustakaan/config/database.php) lalu atur `host`, `db_name` (`elibrary_db`), `username`, dan `password`.
   * **URL Utama**: Buka [config.php](file:///c:/laragon/www/perpustakaan/config/config.php) lalu sesuaikan konstanta `BASE_URL` (misal: `http://localhost/e-library/`).

4. **Jalankan Aplikasi**
   Buka browser Anda lalu ketik alamat berikut:
   ```text
   http://localhost/e-library/
   ```

---

## 🔐 Kredensial Default Pengujian

Gunakan akun di bawah ini untuk menguji fitur dengan peran (role) yang berbeda:

| Peran (Role) | Email | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@gerbangliterasi.id` | `admin123` | Manajemen buku, kelola lisensi & anggota |
| **Anggota Demo** | `user@gerbangliterasi.id` | `user123` | Meminjam buku, antre lisensi & membaca e-book |

---

## 📝 Catatan Penggunaan

> [!IMPORTANT]
> Sistem pengembalian lisensi otomatis (*auto-expiration*) berjalan secara trigger-based (setiap kali ada user mengakses dashboard atau membuka e-book). Hal ini menghindari kebutuhan setup background daemon/cronjob pada server hosting sederhana.

* File dokumen PDF disimpan secara privat di dalam direktori `storage/ebooks/`.
* Seluruh foto profil pengguna disimpan di direktori `storage/avatars/`.
* Seluruh rute aset web menggunakan absolute path berbasis konstanta `BASE_URL` untuk mencegah broken links.
