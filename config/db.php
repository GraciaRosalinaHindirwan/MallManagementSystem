<?php
/**
 * Konfigurasi Koneksi Database - Mall ERP
 *
 * Letakkan file ini di: config/db.php
 * JANGAN upload ke git/public — berisi kredensial!
 */

define('DB_HOST',    '127.0.0.1');
define('DB_NAME',    'mall_erp');       // ← nama database sesuai SQL dump
define('DB_USER',    'root');
define('DB_PASS',    '');               // Kosong = default XAMPP
define('DB_CHARSET', 'utf8mb4');

/**
 * Buat koneksi PDO ke database mall_erp.
 * Mengembalikan PDO jika berhasil, null jika gagal.
 */
function connectDb(): ?PDO
{
    static $instance = null;            // Singleton — satu koneksi per request
    if ($instance !== null) {
        return $instance;
    }

    $dsn = 'mysql:host=' . DB_HOST
         . ';dbname=' . DB_NAME
         . ';charset=' . DB_CHARSET;

    try {
        $instance = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $instance;
    } catch (PDOException $e) {
        error_log('[DB ERROR] Koneksi gagal: ' . $e->getMessage());
        return null;
    }
}

// Koneksi langsung tersedia sebagai $pdo (dipakai oleh parking.php & migrate.php)
$pdo = connectDb();
