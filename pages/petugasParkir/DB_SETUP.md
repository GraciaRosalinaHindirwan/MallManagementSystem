# Parking Management - Database Setup Guide

## Overview
Sistem parking ini mendukung dua mode penyimpanan:
1. **Session-based (default)**: Data disimpan dalam `$_SESSION` dan hilang saat restart.
2. **Database-based**: Data persisten di MySQL/MariaDB; otomatis diaktifkan jika `config/db.php` tersedia.

## Prerequisites
- XAMPP (MySQL/MariaDB sudah terinstall)
- Database `mall_db` (atau nama database lainnya)
- User MySQL dengan hak akses DDL/DML

## Setup Langkah-demi-Langkah

### 1. Buat Database
Di phpMyAdmin atau MySQL CLI, jalankan:
```sql
CREATE DATABASE mall_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mall_db;
```

### 2. Buat Tabel
Jalankan SQL berikut untuk membuat skema:

```sql
CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plate VARCHAR(32) NOT NULL UNIQUE,
  type VARCHAR(16) NOT NULL,
  owner_name VARCHAR(255),
  ticket VARCHAR(64),
  entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  email VARCHAR(255) UNIQUE,
  phone VARCHAR(50),
  type VARCHAR(16) DEFAULT 'regular'
) ENGINE=InnoDB;

CREATE TABLE subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) UNIQUE,
  slots INT DEFAULT 0,
  package VARCHAR(32) DEFAULT 'basic',
  discount INT DEFAULT 20
) ENGINE=InnoDB;

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NULL,
  plate VARCHAR(32),
  type VARCHAR(16),
  owner_name VARCHAR(255),
  entry_time TIMESTAMP,
  exit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  duration_minutes INT,
  base_tariff INT,
  discount_percent INT,
  discount_amount INT,
  total INT,
  INDEX (plate),
  INDEX (exit_time),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  total_entry INT DEFAULT 0,
  total_exit INT DEFAULT 0,
  total_revenue INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

### 3. Konfigurasi Koneksi Database
**a) Copy template:**
```bash
cp c:\xampp\htdocs\parking\config\db.example.php c:\xampp\htdocs\parking\config\db.php
```

**b) Edit `c:\xampp\htdocs\parking\config\db.php` dan sesuaikan DSN:**
```php
$dsn = 'mysql:host=127.0.0.1;dbname=mall_db;charset=utf8mb4';
$dbUser = 'root';    // atau user lainnya
$dbPass = '';        // password jika ada
$pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
```

### 4. Test Koneksi
Buka `http://localhost/parking/index.php` di browser. Jika koneksi berhasil, setiap entry/exit akan menyimpan data ke database.

Untuk memverifikasi:
- Buka phpMyAdmin
- Navigasi ke `mall_db` → `vehicles` dan `transactions`
- Lakukan entry/exit dan cek apakah baris muncul di tabel

### 5. Migrasi Data dari Session (Optional)
Jika ingin pindahkan data session lama ke database, jalankan script migrasi:
```bash
php c:\xampp\htdocs\parking\migrate.php
```

(Script ini akan membuat entri di database dari data session yang ada saat itu.)

## Architecture - Fallback Logic
File `parking/parking.php` sekarang memiliki logika fallback:

1. **Startup**: Cek apakah `config/db.php` ada dan bisa di-include
2. **Jika DB tersedia**: Gunakan PDO untuk semua operasi (`processEntry`, `processExit`, `getParkingState`)
3. **Jika DB gagal/tidak ada**: Gunakan session-based storage (backward compatible)

Ini memastikan sistem tetap berjalan meskipun database belum dikonfigurasi.

## Key Features (DB Mode)
- **Transaksi**: Entry/Exit menggunakan `BEGIN TRANSACTION` dan `COMMIT` untuk konsistensi
- **Locking**: `SELECT ... FOR UPDATE` pada `processExit` mencegah race condition (double-exit)
- **Timestamp Server**: `NOW()` dipakai untuk exit_time (sumber kebenaran)
- **Cascade Delete**: Foreign key di transactions.vehicle_id agar auto-cleanup

## Troubleshooting

| Error | Solusi |
|-------|--------|
| "config/db.php not found" | Fallback ke session - ini normal jika DB belum setup |
| "SQLSTATE[HY000]: General error" | Periksa DSN, username, password di `config/db.php` |
| "Access denied for user 'root'@'localhost'" | Edit `config/db.php` dengan user/pass yang benar |
| "Table doesn't exist" | Jalankan SQL schema creation di phpMyAdmin |

## Next Steps
1. Setup database mengikuti langkah 1-3 di atas
2. Test dengan demo: entry beberapa kendaraan, lihat di phpMyAdmin
3. (Opsional) Integrasikan dengan modul MallManagementSystem lainnya
4. (Opsional) Setup WebSocket/SSE untuk realtime multi-client updates

---
**Note**: Semua operasi DB menggunakan prepared statements untuk keamanan. Data session-only tetap aman digunakan untuk testing lokal.
