-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: elibrary_db
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `elibrary_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `elibrary_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `elibrary_db`;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `pengarang` varchar(150) NOT NULL,
  `penerbit` varchar(150) NOT NULL,
  `tahun_terbit` int NOT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `stok` int NOT NULL DEFAULT '2',
  `deskripsi` text,
  `cover_image` varchar(255) DEFAULT 'default_cover.jpg',
  `file_ebook` varchar(255) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT '0.0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `books_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (4,2,'Pada Senja Yang Membawamu Pergi','pada-senja-yang-membawamu-pergi','Boy Candra','Gagas Media',2016,'978-979-780-864-8',10,'Novel Pada Senja yang Membawamu Pergi menceritakan perjalanan cinta Gie yang harus menghadapi perpisahan dengan Kaila dan penantian bersama Aira. Dari pengalaman tersebut, Gie belajar tentang perjuangan, kehilangan, persahabatan, serta arti mencintai dan merelakan.','cover_1782735246_997.jpg','ebook_1782735246_458.pdf',0.0,'2026-06-29 12:14:06'),(5,2,'Bulan Terbelah di Langit Amerika','bulan-terbelah-di-langit-amerika','Hanum Salsabiela Rais','PT Gramedia Pustaka Utama',2014,'978-602-03-0545-5',14,'Bulan Terbelah di Langit Amerika mengisahkan pasangan suami istri, Hanum dan Rangga, yang pergi ke New York saat peringatan tragedi 11 September. Hanum bertugas menyelidiki apakah dunia lebih baik tanpa Islam, sedangkan Rangga harus menemui seorang profesor demi kelulusannya. Di tengah kekacauan unjuk rasa yang memisahkan mereka, keduanya bertemu Azima Hussein, mualaf yang suaminya dituduh sebagai teroris. Melalui pertemuan ini, mereka berhasil mengungkap sisi humanis dan membuktikan bahwa Islam adalah agama yang membawa kedamaian.','cover_1782735784_592.jpg','ebook_1782735784_921.pdf',0.0,'2026-06-29 12:23:04'),(6,2,'The Seven Spirits of God','the-seven-spirits-of-god','Chris Oyakhilome','Love World',2026,'978-37865-0-4',10,'The Seven Spirits of God karya Pastor Chris Oyakhilome mengupas rahasia ilahi untuk hidup dalam mukjizat melalui kepenuhan Roh Kudus. Buku ini menjelaskan bahwa istilah &amp;quot;tujuh roh&amp;quot; bukanlah entitas yang terpisah, melainkan tujuh manifestasi operasional dari Roh Kudus yang satu untuk memperlengkapi orang percaya. Penulis membimbing pembaca memahami dimensi roh tersebutΓÇömulai dari hikmat, pengertian, hingga keperkasaanΓÇöagar dapat berjalan dalam otoritas supranatural dan memancarkan keagungan Tuhan dalam kehidupan sehari-hari.','cover_1782736028_609.png','ebook_1782736028_494.pdf',0.0,'2026-06-29 12:27:08'),(7,2,'Syahadat Cinta','syahadat-cinta','Taufiqurrahman al-Azizy','DIVA Press',2006,'979-963-307-9',5,'Iqbal, seorang pemuda kota yang kaya, egois, dan buta agama, dikirim orang tuanya ke Pondok Pesantren Tegal Jadin milik Kiai Sabran. Di sana, ia tidak betah dan melarikan diri, hingga akhirnya terdampar di sebuah desa terpencil. Dalam pelariannya, Iqbal mengalami benturan realitas yang mengubah hidupnya. Ia belajar arti ketulusan, kesederhanaan, dan cinta sejati melalui interaksinya dengan warga desa dan dua wanita yang memikat hatinya, Aisyah dan Khaura. Perjalanan ini menuntun Iqbal menemukan kembali esensi iman dan &quot;syahadat&quot; yang sesungguhnya di dalam hatinya.','cover_1782736291_948.png','ebook_1782736291_400.pdf',0.0,'2026-06-29 12:31:31'),(8,2,'Yang Fana adalah Waktu','yang-fana-adalah-waktu','Sapardi Djoko Damono','PT Gramedia Pustaka Utama',2018,'978-602-03-8305-7',20,'Buku ini melanjutkan dan mengakhiri kisah cinta pelik antara Sarwono dan Pingkan. Hubungan mereka berdua terus diuji oleh jarak yang membentang saat Pingkan harus melanjutkan studi ke Jepang, sementara Sarwono tetap berada di Indonesia. Tidak hanya masalah jarak geografis (LDR), cinta mereka juga dihadang oleh tembok besar berupa perbedaan budaya dan keyakinan agama yang tak kunjung menemui titik temu di mata keluarga mereka','cover_1782736579_451.png','ebook_1782736503_628.pdf',0.0,'2026-06-29 12:35:03'),(9,1,'Intrusion Detection Systems with Snort','intrusion-detection-systems-with-snort','Rafeeq Ur Rehman','Prentice Hall PTR',2003,'0-13-140733-3',3,'&amp;quot;Intrusion Detection Systems with Snort&amp;quot; karya Rafeeq Ur Rehman adalah panduan praktis komprehensif untuk membangun sistem deteksi intrusi open-source berbasis Snort. Buku ini mencakup instalasi, konfigurasi, penulisan aturan, dan integrasi Snort dengan MySQL, Apache, dan ACID untuk pemantauan keamanan jaringan.','cover_1782736984_949.jpg','ebook_1782736984_554.pdf',0.0,'2026-06-29 12:43:04'),(10,1,'Fundamentals of Computanional Intelligence','fundamentals-of-computanional-intelligence','James M. Keller','IEEE Press',2016,'978-1-110-21434-2',2,'Buku ini menyajikan pengantar mendalam mengenai Kecerdasan Komputasional (Computational Intelligence/CI) dengan berfokus pada sistem komputer yang terinspirasi dari alam. Pendekatannya menggabungkan aspek teori, perancangan (design), dan implementasi praktis untuk menyelesaikan berbagai masalah kompleks di dunia nyata.','cover_1782737389_964.jpg','ebook_1782737389_476.pdf',0.0,'2026-06-29 12:49:49'),(11,1,'Computer Networks and Internets (Fifth Edition)','computer-networks-and-internets-fifth-edition','Douglas E. Comer','Pearson',2008,'978-0-13-606127-4',2,'Buku ini menyajikan panduan komprehensif mengenai konsep, prinsip, dan teknologi yang mendasari jaringan komputer serta internet global. Pendekatannya dirancang secara mandiri (self-contained), sehingga dapat dipelajari oleh mahasiswa maupun profesional tanpa memerlukan latar belakang mendalam di bidang sistem operasi atau matematika tingkat lanjut. Comer menggunakan banyak gambar, diagram, dan analogi alih-alih pembuktian matematis yang rumit.','cover_1782737576_414.jpg','ebook_1782737576_956.pdf',0.0,'2026-06-29 12:52:56'),(12,3,'Big Book Matematika (SMA Kelas 1,2&amp;3)','big-book-matematika-sma-kelas-1-2-amp-3','Tim BBM','Cmedia Imprint Kawan Pustaka',2015,'978-602-1609-77-4',7,'Buku ini adalah buku panduan belajar dan kumpulan soal penunjang super lengkap yang dirancang sebagai solusi satu wadah (one-stop solution) untuk siswa SMA/MA. Buku ini merangkum seluruh materi matematika esensial selama tiga tahun masa sekolah (Kelas X, XI, dan XII) ke dalam satu buku praktis. Tujuannya adalah membantu siswa meraih nilai tinggi dalam Ulangan Harian, Ujian Sekolah, hingga seleksi masuk Perguruan Tinggi Negeri (PTN) seperti UTBK.','cover_1782737890_535.png','ebook_1782737890_335.pdf',0.0,'2026-06-29 12:58:10'),(13,3,'Integral Calculus','integral-calculus','Hari Kishan','Atlantic',2005,'978-81-269-0559-1',10,'Buku ini membahas berbagai teknik integrasi, termasuk metode substitusi, integrasi parsial, substitusi trigonometri, dan pecahan parsial.','cover_1782738177_827.png','ebook_1782738177_132.pdf',0.0,'2026-06-29 13:02:57');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `borrowings`
--

DROP TABLE IF EXISTS `borrowings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `borrowings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `tanggal_pinjam` datetime DEFAULT NULL,
  `tanggal_jatuh_tempo` datetime DEFAULT NULL,
  `tanggal_kembali` datetime DEFAULT NULL,
  `status` enum('dipinjam','dikembalikan','antre','ditolak') NOT NULL DEFAULT 'dipinjam',
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `borrowings`
--

LOCK TABLES `borrowings` WRITE;
/*!40000 ALTER TABLE `borrowings` DISABLE KEYS */;
INSERT INTO `borrowings` VALUES (5,1,4,'2026-06-29 12:17:27','2026-07-06 12:17:27','2026-06-29 13:26:03','dikembalikan',NULL,'2026-06-29 12:17:27'),(6,1,5,'2026-06-29 13:25:59','2026-07-06 13:25:59',NULL,'dipinjam',NULL,'2026-06-29 13:25:59');
/*!40000 ALTER TABLE `borrowings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `deskripsi` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Teknologi & Pemrograman','teknologi-pemrograman','Buku software engineering, web development, dan AI'),(2,'Fiksi & Novel','fiksi-novel','Novel, cerita pendek, dan karya sastra klasik'),(3,'Sains & Matematika','sains-matematika','Buku fisika, kimia, biologi, dan matematika');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `rating` int NOT NULL,
  `ulasan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_book_unique` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas','anggota') NOT NULL DEFAULT 'anggota',
  `no_telepon` varchar(20) DEFAULT NULL,
  `status_aktif` tinyint(1) DEFAULT '1',
  `foto_profil` varchar(255) DEFAULT 'default_avatar.png',
  `oauth_provider` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','admin@gerbangliterasi.id','$2y$10$Uq88dBT3YN8pTFAZLHaoLuIQ8xDCZENuk1Ud09i2V9zBCVV.8r0L.','admin','081234567890',1,'default_avatar.png',NULL,'2026-06-29 05:24:59'),(2,'Anggota Demo','user@gerbangliterasi.id','$2y$10$ETxNEs3Yb3dJ6amvWr4CEOX8FKwD55iBIDR42z8xgWHBuEX8UNubG','anggota','089876543210',1,'default_avatar.png',NULL,'2026-06-29 05:24:59'),(3,'farid suryadi','farid@gmaiil.com','$2y$10$q2Um8rgzExoHUG7w84tRH.UdrWkdLvjbhwGF.hXNiP4YtWrNiR3Jy','anggota','0812345678',1,'default_avatar.png',NULL,'2026-06-29 05:25:30');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-29 20:40:58

