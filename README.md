# 🏬 Mall Management System

Sistem informasi manajemen mall yang dikembangkan untuk mempermudah pengelolaan tenant, karyawan, fasilitas, keamanan, dan kegiatan operasional mall secara efektif dan efisien.

![PHP](https://img.shields.io/badge/PHP-8+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
![Git](https://img.shields.io/badge/Git-F05032?logo=git&logoColor=white)
![GitHub](https://img.shields.io/badge/GitHub-Repository-181717?logo=github&logoColor=white)
![Status](https://img.shields.io/badge/Status-Development-success)

## Tech Stack

| Category | Technology |
|----------|------------|
| Backend | PHP Native |
| Database | MySQL |
| Frontend | HTML5, CSS3, JavaScript |
| UI Framework | Bootstrap 5, tailwind CSS |
| Web Server | Apache (XAMPP/Laragon) |
| Version Control | Git & GitHub |

## 🚀 Fitur Utama

| Modul | Deskripsi |
|---------|-----------|
| 🏢 Master Data & Unit Management | Mengelola data mall, gedung, lantai, zona, unit, tenant, vendor, serta analisis tingkat hunian. |
| 🤝 Tenant & Leasing Management | Mengelola siklus hidup tenant, kontrak sewa, komponen biaya tenant, dan portal tenant. |
| 🛠️ Facility & Maintenance Management | Mengelola preventive maintenance, ticketing kerusakan, serta manajemen aset mall. |
| ⚡ Utility, Parking & Event Management | Mengelola utilitas, parkir, dan penyelenggaraan event dalam mall. |
| 🎧 Customer Service | Menangani informasi pengunjung, keluhan, helpdesk, serta barang hilang dan temuan. |
| 💰 Finance & Accounting | Mengelola penagihan, piutang, pengadaan, akuntansi, dan pelaporan keuangan. |
| 👥 Human Resource | Mengelola data pegawai, absensi, payroll, cuti, jadwal kerja, dan KPI karyawan. |
| 📊 Business Intelligence, Workflow & Notifikasi | Menyediakan dashboard eksekutif, monitoring KPI, approval workflow, dan notifikasi sistem. |
| 🔐 Security Access & Web Applications | Mengelola hak akses pengguna, autentikasi, audit log, serta portal tenant, pengunjung, dan staff. |

---

## 📂 Struktur Project

```plaintext
MALL/
│
├── config/
│   └── koneksi.php
│
├── dto/
│   ├── UserDto.php
│   ├── LoginDto.php
│   └── ChangePasswordDto.php
│
├── repositories/
│   ├── UserRepositoryInterface.php
│   └── UserRepository.php
│
├── services/
│   └── AuthService.php
│
├── testing/
│   ├── dashboardTest.php
│   └── 
│
├── includes/
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
│
├── pages/
│   ├── admin/
│   ├── approver/
│   ├── CS/
│   ├── eventManager/
│   ├── eventOrganizer/
│   ├── facilityManager/
│   ├── facilityStaff/
│   ├── financeManager/
│   ├── financeStaff/
│   ├── hr/
│   ├── leasingManager/
│   ├── manager/
│   ├── member/
│   ├── operationalManager/
│   ├── pegawai/
│   ├── pengguna/
│   ├── pengunjung/
│   ├── technician/
│   ├── keamanan/
│   ├── petugasParkir/
│   ├── pusrchasingStaff/
│   ├── staff/
│   ├── teknisi/
│   └── tenant/
│
├── public/
│   │
│   ├── asset/
│   │   ├── css/
│   │   ├── images/
│   │   └── js/
│   │
│   ├── auth/
│   │   ├── loginProcess.php
│   │   ├── registerProcess.php
│   │   ├── changePasswordProcess.php
│   │   ├── checkSession.php
│   │   ├── captcha.php
│   │   └── logout.php
│   │
│   ├── index.php
│   ├── changePassword.php
│   └── register.php
│
└── README.md
```

---
## Requirements

- PHP 8.0+
- MySQL 8.0+
- Apache / XAMPP / Laragon

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/username/mall-management.git
```

### 2. Move Project

Simpan project ke folder web server:

```plaintext
htdocs/mall-management
```

atau

```plaintext
www/mall-management
```

### 3. Create Database

Buat database baru:

```sql
CREATE DATABASE mall_management;
```

### 4. Import Database

Import file SQL yang tersedia ke database:

```plaintext
database/mall_management.sql
```

### 5. Configure Database

Edit file:

```plaintext
config/database.php
```

```php
<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "mall_management"
);
```

### 6. Run Application

Buka browser:

```plaintext
http://localhost/mall-management/public
```

## 👤 Peran Pengguna

- Super Admin
- Mall Director
- Leasing Manager
- Finance Staff & Manager
- HR Admin & Manager
- Facility Manager
- Teknisi
- Customer Service
- Security
- Petugas Parkir
- Tenant
- Pengunjung
## Development Notes

Proyek ini dikembangkan menggunakan PHP Native tanpa framework. Komponen yang digunakan bersama seperti header, footer, navbar, serta fungsi-fungsi pendukung disimpan dalam folder `includes` dan digunakan kembali pada berbagai halaman menggunakan `include` atau `require`.

Example:

```php
<?php
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<h1>Dashboard</h1>

<?php
require_once '../../includes/footer.php';
?>
```

## License

Proyek ini dikembangkan untuk keperluan akademik dan pembelajaran.
