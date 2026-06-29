<?php
/**
 * Database Connection Helper (PDO Singleton Pattern)
 * Siap Produksi dengan Exception Handling & Prepared Statements
 */

class Database {
    private static $host = 'localhost';
    private static $db_name = 'elibrary_db';
    private static $username = 'root';
    private static $password = ''; // UBAH SESUAI PASSWORD MYSQL PRODUCTION ANDA
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$conn = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // Jangan tampilkan detail error database di mode produksi
                error_log("Database Connection Error: " . $e->getMessage());
                die("<div style='padding:20px; font-family:sans-serif; text-align:center;'>
                        <h2>Koneksi Database Gagal</h2>
                        <p>Pastikan MySQL telah berjalan dan database <code>perpustakaan_db</code> telah di-import.</p>
                     </div>");
            }
        }
        return self::$conn;
    }
}
