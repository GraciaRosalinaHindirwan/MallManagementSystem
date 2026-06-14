-- =============================================
-- HR MODULE - Mall Management System
-- =============================================

CREATE DATABASE IF NOT EXISTS mall_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mall_management;

-- Tabel Pegawai (PBI-01)
CREATE TABLE IF NOT EXISTS pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L','P') DEFAULT NULL,
    agama VARCHAR(20),
    pendidikan_terakhir VARCHAR(30),
    jabatan VARCHAR(50) NOT NULL,
    departemen VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    no_hp VARCHAR(15),
    alamat TEXT,
    tgl_lahir DATE,
    tgl_masuk DATE NOT NULL,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    foto VARCHAR(255),
    spesialisasi VARCHAR(100),
    sertifikasi VARCHAR(255),
    nama_bank VARCHAR(50),
    no_rekening VARCHAR(30),
    kontak_darurat_nama VARCHAR(100),
    kontak_darurat_hp VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Shift (PBI-02)
CREATE TABLE IF NOT EXISTS shift (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_shift VARCHAR(50) NOT NULL,
    jam_masuk TIME NOT NULL,
    jam_keluar TIME NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Jadwal Shift (PBI-02)
CREATE TABLE IF NOT EXISTS jadwal_shift (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    shift_id INT NOT NULL,
    tanggal DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shift(id) ON DELETE CASCADE
);

-- Tabel Absensi (PBI-03)
CREATE TABLE IF NOT EXISTS absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_keluar TIME,
    status ENUM('hadir','izin','sakit','alpha') DEFAULT 'hadir',
    foto_masuk VARCHAR(255),
    lokasi_masuk VARCHAR(255),
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE
);

-- Tabel Cuti (PBI-05)
CREATE TABLE IF NOT EXISTS cuti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    tgl_mulai DATE NOT NULL,
    tgl_selesai DATE NOT NULL,
    jenis_cuti ENUM('tahunan','sakit','melahirkan','darurat') NOT NULL,
    alasan TEXT NOT NULL,
    status ENUM('pending','disetujui','ditolak') DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE
);

-- Tabel Payroll (PBI-04)
CREATE TABLE IF NOT EXISTS payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    bulan INT NOT NULL,
    tahun INT NOT NULL,
    gaji_pokok DECIMAL(12,2) NOT NULL DEFAULT 0,
    tunjangan DECIMAL(12,2) DEFAULT 0,
    potongan DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) GENERATED ALWAYS AS (gaji_pokok + tunjangan - potongan) STORED,
    status ENUM('draft','final') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE
);

-- Tabel KPI (PBI-06)
CREATE TABLE IF NOT EXISTS kpi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    periode VARCHAR(20) NOT NULL,
    target_kerja TEXT,
    realisasi TEXT,
    nilai INT DEFAULT 0 CHECK (nilai BETWEEN 0 AND 100),
    kategori ENUM('sangat_baik','baik','cukup','kurang') DEFAULT 'cukup',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE
);

-- =============================================
-- DUMMY DATA
-- =============================================
INSERT INTO shift (nama_shift, jam_masuk, jam_keluar, keterangan) VALUES
('Pagi',  '07:00:00', '15:00:00', 'Shift pagi reguler'),
('Siang', '13:00:00', '21:00:00', 'Shift siang reguler'),
('Malam', '21:00:00', '07:00:00', 'Shift malam');

INSERT INTO pegawai (nik, nama, jenis_kelamin, agama, pendidikan_terakhir, jabatan, departemen, email, no_hp, tgl_masuk, spesialisasi, sertifikasi) VALUES
('EMP001', 'Budi Santoso',   'L', 'Islam',  'S1', 'Staff HR',       'HR', 'budi@mall.com',   '081234567890', '2023-01-10', NULL, NULL),
('EMP002', 'Siti Rahayu',    'P', 'Islam',  'SMA','Kasir',          'CS', 'siti@mall.com',   '081234567891', '2023-02-15', NULL, NULL),
('EMP003', 'Ahmad Fauzi',    'L', 'Islam',  'SMA','Security',       'Security', 'ahmad@mall.com', '081234567892', '2022-11-01', NULL, NULL),
('EMP004', 'Dewi Lestari',   'P', 'Kristen','S1', 'Supervisor',     'Operations', 'dewi@mall.com', '081234567893', '2021-06-20', NULL, NULL),
('EMP005', 'Rizky Pratama',  'L', 'Islam',  'D3', 'Teknisi',        'Facility', 'rizky@mall.com', '081234567894', '2022-03-05', 'Electrical', 'Sertifikat K3 Listrik');
