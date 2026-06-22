-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2026 at 01:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mall_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `01_buildings`
--

CREATE TABLE `01_buildings` (
  `id_buildings` int(11) NOT NULL COMMENT 'ID unik gedung/tower',
  `mall_id` int(11) NOT NULL COMMENT 'ID mall tempat gedung berada (FK ke 01_malls.id_malls)',
  `name` varchar(50) NOT NULL COMMENT 'Nama gedung/tower',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Waktu data dibuat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan data gedung/tower per mall';

-- --------------------------------------------------------

--
-- Table structure for table `01_floors`
--

CREATE TABLE `01_floors` (
  `id_floors` int(11) NOT NULL COMMENT 'ID unik lantai',
  `building_id` int(11) NOT NULL COMMENT 'ID gedung tempat lantai berada (FK ke 01_buildings.id_buildings)',
  `floor_number` varchar(10) NOT NULL COMMENT 'Nomor lantai. Contoh: LG, 1, 2',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Waktu data dibuat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan data lantai per gedung';

-- --------------------------------------------------------

--
-- Table structure for table `01_malls`
--

CREATE TABLE `01_malls` (
  `id_malls` int(11) NOT NULL COMMENT 'ID unik mall/cabang',
  `name` varchar(100) NOT NULL COMMENT 'Nama mall/cabang',
  `address` text DEFAULT NULL COMMENT 'Alamat lengkap mall',
  `city` varchar(50) DEFAULT NULL COMMENT 'Kota lokasi mall',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Waktu data dibuat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan data mall atau cabang';

-- --------------------------------------------------------

--
-- Table structure for table `01_tenant_categories`
--

CREATE TABLE `01_tenant_categories` (
  `id_tenant_categories` int(11) NOT NULL COMMENT 'ID unik kategori tenant',
  `name` varchar(50) NOT NULL COMMENT 'Nama kategori. Contoh: F&B, Retail, Entertainment'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan daftar kategori usaha tenant';

-- --------------------------------------------------------

--
-- Table structure for table `01_units`
--

CREATE TABLE `01_units` (
  `id_units` int(11) NOT NULL COMMENT 'ID unik unit/kios',
  `floor_id` int(11) NOT NULL COMMENT 'ID lantai tempat unit berada (FK ke 01_floors.id_floors)',
  `unit_code` varchar(20) NOT NULL COMMENT 'Kode unit. Contoh: LG-01',
  `area_size` decimal(10,2) DEFAULT NULL COMMENT 'Luas unit dalam meter persegi',
  `status` varchar(20) DEFAULT 'available' COMMENT 'Status unit: available, occupied, maintenance, renovation, closed',
  `tenant_id` int(11) DEFAULT NULL COMMENT 'ID tenant yang menempati (FK ke 02_tenants.id_tenants)',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Waktu data dibuat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan data unit/kios per lantai';

-- --------------------------------------------------------

--
-- Table structure for table `01_unit_types`
--

CREATE TABLE `01_unit_types` (
  `id_unit_types` int(11) NOT NULL COMMENT 'ID unik tipe unit',
  `name` varchar(50) NOT NULL COMMENT 'Nama tipe unit. Contoh: Kios, Stand, Food Court'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan daftar tipe unit untuk klasifikasi';

-- --------------------------------------------------------

--
-- Table structure for table `02_contracts`
--

CREATE TABLE `02_contracts` (
  `id_contract` int(11) NOT NULL,
  `contract_number` varchar(50) NOT NULL,
  `id_tenant` int(11) NOT NULL,
  `id_unit` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `contract_status` enum('Draft','Waiting Approval','Active','Amended','Expired','Terminated') NOT NULL DEFAULT 'Draft',
  `legal_document_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `02_contract_cost`
--

CREATE TABLE `02_contract_cost` (
  `id_component` int(11) NOT NULL,
  `id_contract` int(11) NOT NULL,
  `charge_type` enum('Fixed Rent','Revenue Sharing','Service Charge','Utility Charge','Maintenance Fee') NOT NULL,
  `calculation_basis` enum('Per Sqm','Fixed Monthly','Percentage') NOT NULL,
  `amount_or_percentage` decimal(15,2) NOT NULL,
  `billing_cycle` enum('Monthly','Quarterly','Annually') NOT NULL DEFAULT 'Monthly'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `02_tenants`
--

CREATE TABLE `02_tenants` (
  `id_tenant` int(11) NOT NULL,
  `id_prospect` int(11) NOT NULL,
  `tenant_name` varchar(100) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `id_category` int(11) NOT NULL,
  `npwp_number` varchar(30) DEFAULT NULL,
  `status` enum('Active','Non-Active','Terminated') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `02_tenant_complaints`
--

CREATE TABLE `02_tenant_complaints` (
  `id_complaint` int(11) NOT NULL,
  `id_tenant` int(11) NOT NULL,
  `id_unit` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `severity_level` enum('Low','Medium','High') NOT NULL DEFAULT 'Low',
  `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `02_tenant_deposits`
--

CREATE TABLE `02_tenant_deposits` (
  `id_deposit` int(11) NOT NULL,
  `id_contract` int(11) NOT NULL,
  `deposit_type` enum('Security Deposit','Utility Deposit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_status` enum('Unpaid','Paid','Refunded','Forfeited') NOT NULL DEFAULT 'Unpaid',
  `payment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `02_tenant_prospects`
--

CREATE TABLE `02_tenant_prospects` (
  `id_prospect` int(11) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `id_category` int(11) NOT NULL,
  `pic_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `interested_unit` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Prospect','Verified','Rejected','Converted') NOT NULL DEFAULT 'Prospect',
  `register_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `02_tenant_renovations`
--

CREATE TABLE `02_tenant_renovations` (
  `id_renovation` int(11) NOT NULL,
  `id_contract` int(11) NOT NULL,
  `description` text NOT NULL,
  `proposed_start_date` date NOT NULL,
  `proposed_end_date` date NOT NULL,
  `attachment_plan_url` varchar(255) DEFAULT NULL,
  `status` enum('Pending','In Review','Approved','Rejected') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `03_assets`
--

CREATE TABLE `03_assets` (
  `asset_id` int(11) NOT NULL,
  `asset_code` varchar(50) NOT NULL COMMENT 'ID Unik / QR Code',
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `purchase_value` decimal(15,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `useful_life` int(11) NOT NULL COMMENT 'umur ekonomis (tahun)',
  `depreciation_policy` varchar(255) NOT NULL,
  `current_location` varchar(100) DEFAULT NULL,
  `status` enum('active','maintenance','retired','lost') DEFAULT 'active',
  `is_vital` tinyint(1) DEFAULT 0 COMMENT 'aset vital (AC, lift, genset, CCTV)',
  `last_mutation_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `03_assets`
--

INSERT INTO `03_assets` (`asset_id`, `asset_code`, `name`, `category`, `purchase_value`, `purchase_date`, `useful_life`, `depreciation_policy`, `current_location`, `status`, `is_vital`, `last_mutation_date`, `created_at`, `updated_at`) VALUES
(1, 'AST-001', 'Lift LG 01', 'HVAC', 1500000000.00, '2020-01-15', 10, '0', 'Lantai LG', 'active', 1, NULL, '2026-06-18 11:55:23', '2026-06-18 11:55:23'),
(2, 'AST-002', 'AC Central Lantai 1', 'HVAC', 750000000.00, '2021-03-10', 8, '0', 'Lantai 1', 'maintenance', 1, NULL, '2026-06-18 11:55:23', '2026-06-18 11:55:23'),
(3, 'AST-003', 'Genset Utama', 'Electrical', 1200000000.00, '2019-11-20', 12, '0', 'Ruang Genset', 'active', 1, NULL, '2026-06-18 11:55:23', '2026-06-18 11:55:23'),
(4, 'AST-004', 'CCTV Camera 01', 'Security', 15000000.00, '2022-06-01', 5, '0', 'Lantai LG', 'active', 0, NULL, '2026-06-18 11:55:23', '2026-06-18 11:55:23');

-- --------------------------------------------------------

--
-- Table structure for table `03_asset_mutations`
--

CREATE TABLE `03_asset_mutations` (
  `mutation_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `old_location` varchar(100) NOT NULL,
  `new_location` varchar(100) NOT NULL,
  `mutation_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL COMMENT 'user_id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `03_asset_mutations`
--

INSERT INTO `03_asset_mutations` (`mutation_id`, `asset_id`, `old_location`, `new_location`, `mutation_date`, `notes`, `recorded_by`) VALUES
(1, 1, 'Lantai LG', 'Lantai 3', '2026-06-01 08:00:00', 'Pemindahan lift untuk maintenance', 1);

-- --------------------------------------------------------

--
-- Table structure for table `03_checklist`
--

CREATE TABLE `03_checklist` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `kondisi` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tanggal_inspeksi` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `03_checklist`
--

INSERT INTO `03_checklist` (`id`, `schedule_id`, `kondisi`, `catatan`, `foto`, `tanggal_inspeksi`) VALUES
(1, 1, 'baik', 'Semua berfungsi normal', 'foto_cek1.jpg', '2026-06-15'),
(2, 2, 'perlu perbaikan', 'Suara berisik', 'foto_cek2.jpg', '2026-06-20');

-- --------------------------------------------------------

--
-- Table structure for table `03_damage_reports`
--

CREATE TABLE `03_damage_reports` (
  `report_id` int(11) NOT NULL,
  `ticket_id` varchar(30) NOT NULL,
  `status` enum('Open','Assigned','In Progress','Resolved','Closed') DEFAULT 'Open',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `asset_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `03_damage_reports`
--

INSERT INTO `03_damage_reports` (`report_id`, `ticket_id`, `status`, `created_by`, `created_at`, `asset_id`) VALUES
(1, 'TK-20260608-001', 'Assigned', 1, '2026-06-18 11:57:20', 1),
(2, 'TK-20260609-002', 'Assigned', 2, '2026-06-18 11:57:20', 2);

-- --------------------------------------------------------

--
-- Table structure for table `03_maintenance_schedule`
--

CREATE TABLE `03_maintenance_schedule` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `frekuensi` varchar(20) DEFAULT NULL COMMENT 'daily, weekly, monthly, quarterly, annual',
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `03_maintenance_schedule`
--

INSERT INTO `03_maintenance_schedule` (`id`, `asset_id`, `tanggal`, `frekuensi`, `status`) VALUES
(1, 1, '2026-06-15', 'monthly', 'pending'),
(2, 2, '2026-06-20', 'weekly', 'scheduled'),
(3, 3, '2026-06-01', 'quarterly', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `03_technicians`
--

CREATE TABLE `03_technicians` (
  `technician_id` int(11) NOT NULL,
  `NIK` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('Available','On-Duty','Offline') DEFAULT 'Available',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `03_technicians`
--

INSERT INTO `03_technicians` (`technician_id`, `NIK`, `photo`, `status`, `is_active`, `created_at`, `user_id`) VALUES
(1, 'EMP005', 'foto_teknisi_1.jpg', 'On-Duty', 1, '2026-06-18 11:57:20', 5),
(2, 'EMP006', 'foto_teknisi_2.jpg', 'Available', 1, '2026-06-18 11:57:20', 7),
(3, 'EMP007', 'foto_teknisi_3.jpg', 'Offline', 1, '2026-06-18 11:57:20', 6);

-- --------------------------------------------------------

--
-- Table structure for table `03_technician_skills`
--

CREATE TABLE `03_technician_skills` (
  `skill_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `skill_name` varchar(100) DEFAULT NULL,
  `proficiency_level` int(11) DEFAULT 1 COMMENT '1-100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `03_technician_skills`
--

INSERT INTO `03_technician_skills` (`skill_id`, `technician_id`, `skill_name`, `proficiency_level`) VALUES
(1, 1, 'HVAC', 95),
(2, 1, 'Electrical', 70),
(3, 2, 'Electrical', 98),
(4, 2, 'Fire Safety', 60);

-- --------------------------------------------------------

--
-- Table structure for table `03_work_orders`
--

CREATE TABLE `03_work_orders` (
  `work_order_id` int(11) NOT NULL,
  `work_order_number` varchar(30) DEFAULT NULL,
  `report_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `required_skill` varchar(100) DEFAULT NULL,
  `priority` enum('Critical','High','Medium','Low') DEFAULT NULL,
  `sla_target` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `work_status` enum('Assigned','In Progress','Completed','Cancelled') DEFAULT 'Assigned',
  `assigned_by` varchar(255) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `03_work_orders`
--

INSERT INTO `03_work_orders` (`work_order_id`, `work_order_number`, `report_id`, `technician_id`, `required_skill`, `priority`, `sla_target`, `due_date`, `work_status`, `assigned_by`, `assigned_at`, `notes`) VALUES
(1, 'WO-20260609-001', 1, 1, 'HVAC', 'Critical', '2026-06-18 08:50:00', '2026-06-25', 'Assigned', '1', '2026-06-18 11:57:20', 'Perbaikan lift segera'),
(2, 'WO-20260609-002', 2, 2, 'Electrical', 'High', '2026-06-09 17:57:00', '2026-06-13', 'Completed', '1', '2026-06-18 11:57:20', 'Perbaikan AC central'),
(3, 'WO-20260618-1166', 1, 1, 'HVAC', 'High', '2026-06-18 22:32:00', '2026-06-20', 'Assigned', '1', '2026-06-18 15:32:58', NULL),
(5, 'WO-20260618-0001-201', 1, 1, 'HVAC', 'High', '2026-06-18 22:32:00', '2026-06-20', 'Assigned', '1', '2026-06-18 15:35:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `03_work_order_activities`
--

CREATE TABLE `03_work_order_activities` (
  `activity_id` int(11) NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `activity_note` text DEFAULT NULL,
  `employee_code` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `attachment_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `03_work_order_activities`
--

INSERT INTO `03_work_order_activities` (`activity_id`, `work_order_id`, `activity_type`, `activity_note`, `employee_code`, `created_by`, `created_at`, `attachment_file`) VALUES
(1, 1, 'start', 'Teknisi mulai perbaikan', '0', 1, '2026-06-18 11:57:20', 'foto_perbaikan.jpg'),
(2, 1, 'update', 'Selesai mengganti spare part', '0', 1, '2026-06-18 11:57:20', NULL),
(3, 5, 'Assigned', 'Assigned to Rizky Pratama', '0', NULL, '2026-06-18 15:35:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `04_event_analytics`
--

CREATE TABLE `04_event_analytics` (
  `id` int(11) NOT NULL,
  `id_booking` int(11) NOT NULL COMMENT 'FK ke 04_event_booking.id_booking',
  `jml_pengunjung` int(11) DEFAULT NULL,
  `target_pengunjung` int(11) DEFAULT NULL,
  `traffic_before` int(11) DEFAULT NULL,
  `traffic_during` int(11) DEFAULT NULL,
  `traffic_after` int(11) DEFAULT NULL,
  `rating_kepuasan` decimal(2,1) DEFAULT NULL,
  `rating_vendor` decimal(2,1) DEFAULT NULL,
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Data analitik traffic & kepuasan pasca-event (PBI-M04-03-04)';

-- --------------------------------------------------------

--
-- Table structure for table `04_event_areas`
--

CREATE TABLE `04_event_areas` (
  `id_area` int(11) NOT NULL,
  `nama_area` varchar(100) NOT NULL,
  `floor_id` int(11) DEFAULT NULL,
  `kapasitas` int(11) DEFAULT NULL,
  `fasilitas` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `04_event_booking`
--

CREATE TABLE `04_event_booking` (
  `id_booking` int(11) NOT NULL COMMENT 'ID unik pengajuan booking event',
  `id_area` int(11) NOT NULL COMMENT 'FK ke 04_event_areas.id_area',
  `id_user` int(11) NOT NULL COMMENT 'FK ke 09_users.id',
  `nama_event` varchar(150) NOT NULL,
  `tipe_event` varchar(50) NOT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `estimasi_pengunjung` int(11) DEFAULT NULL COMMENT 'Estimasi pengunjung saat pengajuan',
  `catatan_admin` text DEFAULT NULL COMMENT 'Catatan dari admin saat approve/reject/revisi',
  `status` varchar(20) DEFAULT 'pending' COMMENT 'pending, approved, rejected, revision'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pengajuan booking area event (PBI-M04-03-01/02)';

-- --------------------------------------------------------

--
-- Table structure for table `04_event_booking_vendor`
--

CREATE TABLE `04_event_booking_vendor` (
  `id` int(11) NOT NULL,
  `id_booking` int(11) NOT NULL COMMENT 'FK ke 04_event_booking.id_booking',
  `nama_vendor` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `kontak` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pivot many-to-many: vendor yang terlibat pada satu event';

-- --------------------------------------------------------

--
-- Table structure for table `04_event_sponsorship`
--

CREATE TABLE `04_event_sponsorship` (
  `id_sponsor` varchar(20) NOT NULL COMMENT 'Contoh: SPO-001',
  `id_booking` int(11) NOT NULL COMMENT 'FK ke 04_event_booking.id_booking',
  `sponsor` varchar(100) NOT NULL,
  `paket` varchar(30) DEFAULT NULL COMMENT 'Contoh: Platinum, Gold, Silver',
  `nilai` decimal(15,2) NOT NULL,
  `status_bayar` varchar(20) DEFAULT 'belum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Manajemen sponsor & status settlement per event (PBI-M04-03-03)';

-- --------------------------------------------------------

--
-- Table structure for table `04_event_tiket`
--

CREATE TABLE `04_event_tiket` (
  `id_tiket` varchar(20) NOT NULL COMMENT 'Contoh: TKT-001',
  `id_booking` int(11) NOT NULL COMMENT 'FK ke 04_event_booking.id_booking',
  `tipe` varchar(30) NOT NULL COMMENT 'Contoh: Gratis, Regular, VIP',
  `kuota` int(11) NOT NULL DEFAULT 0,
  `terjual` int(11) NOT NULL DEFAULT 0,
  `harga` decimal(12,2) DEFAULT 0.00,
  `pendapatan` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Setup tiket digital per event (PBI-M04-03-03)';

-- --------------------------------------------------------

--
-- Table structure for table `04_invoice_utilitas`
--

CREATE TABLE `04_invoice_utilitas` (
  `id_invoice` int(11) NOT NULL COMMENT 'ID unik invoice utilitas',
  `tenant_id` int(11) NOT NULL COMMENT 'FK ke 02_tenants.id_tenant',
  `id_meter` int(11) DEFAULT NULL COMMENT 'FK ke 04_utility_meters.id_meter',
  `billing_period` date DEFAULT NULL COMMENT 'Periode penagihan',
  `total_consumption` decimal(15,2) DEFAULT NULL COMMENT 'Total konsumsi pada periode ini',
  `total` decimal(15,2) NOT NULL COMMENT 'Total tagihan utilitas',
  `status` varchar(20) DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Invoice utilitas per tenant per periode';

-- --------------------------------------------------------

--
-- Table structure for table `04_parking_member`
--

CREATE TABLE `04_parking_member` (
  `id_member` int(11) NOT NULL COMMENT 'ID unik member parkir',
  `tenant_id` int(11) DEFAULT NULL COMMENT 'FK ke 02_tenants.id_tenant, NULL jika member umum',
  `plat_nomor` varchar(15) NOT NULL,
  `tipe_kendaraan` varchar(20) NOT NULL,
  `membership_type` varchar(30) DEFAULT NULL COMMENT 'Contoh: Reguler, Korporat, VIP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Member & subscription parkir, termasuk korporat (PBI-M04-02-03)';

-- --------------------------------------------------------

--
-- Table structure for table `04_parking_tarif`
--

CREATE TABLE `04_parking_tarif` (
  `id_tarif` int(11) NOT NULL,
  `tipe_kendaraan` enum('motor','mobil','truk') NOT NULL,
  `tipe_user` enum('umum','member','korporat') NOT NULL,
  `tarif_jam_pertama` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tarif_per_jam` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tarif_harian_max` decimal(10,2) DEFAULT NULL,
  `berlaku_dari` date NOT NULL,
  `berlaku_sampai` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `04_parking_transaksi`
--

CREATE TABLE `04_parking_transaksi` (
  `id_transaksi` int(11) NOT NULL COMMENT 'ID unik transaksi parkir',
  `petugas_id` int(11) DEFAULT NULL COMMENT 'FK ke 09_users.id, petugas yang memproses',
  `plat_nomor` varchar(15) NOT NULL,
  `id_member` int(11) DEFAULT NULL COMMENT 'FK ke 04_parking_member.id_member, NULL jika non-member',
  `zona_id` int(11) DEFAULT NULL,
  `tipe_kendaraan` enum('motor','mobil','truk') DEFAULT NULL,
  `entry_time` datetime NOT NULL,
  `exit_time` datetime DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(15) DEFAULT NULL,
  `parking_slot` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Transaksi entry/exit parkir (PBI-M04-02-01/02)';

-- --------------------------------------------------------

--
-- Table structure for table `04_parking_zona`
--

CREATE TABLE `04_parking_zona` (
  `id_zona` int(11) NOT NULL COMMENT 'ID unik zona parkir',
  `nama_zona` varchar(50) NOT NULL COMMENT 'Contoh: Basement 1, Rooftop',
  `total_slot` int(11) NOT NULL COMMENT 'Total kapasitas slot pada zona ini',
  `occupied_slot` int(11) NOT NULL DEFAULT 0,
  `floor_id` int(11) DEFAULT NULL COMMENT 'FK ke 01_floors.id_floors'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Master zona & kapasitas slot parkir untuk monitoring real-time (PBI-M04-02-04)';

-- --------------------------------------------------------

--
-- Table structure for table `04_utility_meters`
--

CREATE TABLE `04_utility_meters` (
  `id_meter` int(11) NOT NULL COMMENT 'ID unik meter utilitas',
  `unit_id` int(11) NOT NULL COMMENT 'FK ke 01_units.id_units',
  `utility_type` enum('listrik','air','gas','internet','ac_central') NOT NULL,
  `current_reading` decimal(15,2) DEFAULT NULL COMMENT 'Pembacaan meter terakhir',
  `previous_reading` decimal(15,2) DEFAULT NULL COMMENT 'Pembacaan meter sebelumnya',
  `reading_date` datetime DEFAULT NULL COMMENT 'Waktu pembacaan terakhir',
  `input_method` varchar(20) DEFAULT NULL COMMENT 'manual atau iot',
  `status` varchar(20) DEFAULT 'active',
  `tarif_per_unit` decimal(12,2) NOT NULL COMMENT 'Tarif per unit konsumsi',
  `threshold_max` decimal(12,2) DEFAULT NULL COMMENT 'Ambang batas deteksi anomali'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Meter utilitas per unit, mendukung pencatatan manual & IoT';

-- --------------------------------------------------------

--
-- Table structure for table `04_utility_meter_logs`
--

CREATE TABLE `04_utility_meter_logs` (
  `id_log` int(11) NOT NULL COMMENT 'ID unik log pembacaan meter',
  `id_meter` int(11) NOT NULL COMMENT 'FK ke 04_utility_meters.id_meter',
  `reading_value` decimal(15,2) NOT NULL COMMENT 'Nilai pembacaan pada waktu tersebut',
  `reading_date` datetime NOT NULL COMMENT 'Waktu pembacaan',
  `recorded_by` int(11) DEFAULT NULL COMMENT 'FK ke 09_users.id, petugas yang mencatat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Histori pembacaan meter untuk analitik konsumsi (PBI-M04-01-04)';

-- --------------------------------------------------------

--
-- Table structure for table `05_cs_feedback`
--

CREATE TABLE `05_cs_feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_pengunjung` varchar(100) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `komentar` text NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `05_found_items`
--

CREATE TABLE `05_found_items` (
  `id` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `location_found` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Tersimpan','Dikembalikan') DEFAULT 'Tersimpan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `05_lost_reports`
--

CREATE TABLE `05_lost_reports` (
  `id` int(11) NOT NULL,
  `nama_pelapor` varchar(100) DEFAULT NULL,
  `item_description` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('Dicari','Dikembalikan') DEFAULT 'Dicari',
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `05_tiket`
--

CREATE TABLE `05_tiket` (
  `id` varchar(20) NOT NULL,
  `report_date` date NOT NULL,
  `pelapor` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `lokasi` varchar(200) NOT NULL,
  `floor_name` varchar(50) DEFAULT NULL,
  `area_name` varchar(100) DEFAULT NULL,
  `asset_name` varchar(100) DEFAULT NULL,
  `asset_code` varchar(50) DEFAULT NULL,
  `kategori` enum('facility','security','cleaning','other') NOT NULL,
  `damage_type` varchar(100) DEFAULT NULL,
  `priority` enum('Critical','High','Medium','Low') DEFAULT 'Medium',
  `severity_level` int(11) DEFAULT 1,
  `deskripsi` text NOT NULL,
  `status` enum('open','in_progress','resolved') DEFAULT 'open',
  `dept` enum('Facility','Security','Cleaning','CS') NOT NULL,
  `foto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`foto`)),
  `sla_menit` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `05_tiket`
--

INSERT INTO `05_tiket` (`id`, `report_date`, `pelapor`, `no_hp`, `lokasi`, `floor_name`, `area_name`, `asset_name`, `asset_code`, `kategori`, `damage_type`, `priority`, `severity_level`, `deskripsi`, `status`, `dept`, `foto`, `sla_menit`, `created_at`, `updated_at`) VALUES
('TK-20260608-001', '2026-06-18', 'Unknown', '0', 'Unknown location', NULL, NULL, 'Lift LG 01', 'AST-001', 'facility', 'Unknown damage', 'Medium', 1, 'No description', 'open', 'Facility', NULL, 120, '2026-06-18 15:29:16', '2026-06-18 15:29:16'),
('TK-20260609-002', '2026-06-18', 'Unknown', '0', 'Unknown location', NULL, NULL, 'AC Central Lantai 1', 'AST-002', 'facility', 'Unknown damage', 'Medium', 1, 'No description', 'open', 'Facility', NULL, 120, '2026-06-18 15:29:16', '2026-06-18 15:29:16');

-- --------------------------------------------------------

--
-- Table structure for table `05_tiket_log`
--

CREATE TABLE `05_tiket_log` (
  `id` int(11) NOT NULL,
  `tiket_id` varchar(20) NOT NULL,
  `status_lama` enum('open','in_progress','resolved') DEFAULT NULL,
  `status_baru` enum('open','in_progress','resolved') NOT NULL,
  `catatan` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `06_ad_contracts`
--

CREATE TABLE `06_ad_contracts` (
  `id` int(11) NOT NULL,
  `advertiser_name` varchar(100) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL COMMENT 'FK ke M02.tenants jika pengiklan adalah tenant',
  `ad_location` varchar(100) DEFAULT NULL,
  `ad_type` varchar(50) DEFAULT NULL COMMENT 'Billboard, digital_screen, banner',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_fee` decimal(15,2) NOT NULL,
  `current_period` varchar(20) DEFAULT NULL COMMENT 'Format: YYYY-MM',
  `billing_status` varchar(20) DEFAULT 'unpaid' COMMENT 'unpaid, paid',
  `last_paid_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Master data kontrak penempatan iklan sekaligus status tagihan bulanannya';

-- --------------------------------------------------------

--
-- Table structure for table `06_approval_log`
--

CREATE TABLE `06_approval_log` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `aksi` varchar(20) NOT NULL COMMENT 'approved, rejected',
  `komentar` text DEFAULT NULL,
  `approver` varchar(100) NOT NULL,
  `tgl_aksi` datetime NOT NULL DEFAULT current_timestamp(),
  `approval_request_id_fk` int(11) DEFAULT NULL COMMENT 'FK ke M08.approval_requests'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Riwayat keputusan approval PO oleh manager';

-- --------------------------------------------------------

--
-- Table structure for table `06_chart_of_accounts`
--

CREATE TABLE `06_chart_of_accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL COMMENT 'Contoh: 4-1001',
  `account_name` varchar(100) NOT NULL COMMENT 'Contoh: Pendapatan Sewa',
  `account_type` varchar(30) DEFAULT NULL COMMENT 'asset, liability, equity, revenue, expense',
  `depreciation_method` varchar(30) DEFAULT NULL COMMENT 'Isinya khusus akun asset: straight_line atau double_declining',
  `useful_life_months` int(11) DEFAULT NULL COMMENT 'Umur ekonomis dalam bulan (Contoh: 48, 60)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Daftar akun akuntansi terpusat + metode depresiasi aset';

-- --------------------------------------------------------

--
-- Table structure for table `06_daily_parking_summary`
--

CREATE TABLE `06_daily_parking_summary` (
  `id` int(11) NOT NULL,
  `summary_date` date NOT NULL,
  `total_transactions` int(11) DEFAULT 0,
  `total_revenue` decimal(15,2) DEFAULT 0.00,
  `cash_revenue` decimal(15,2) DEFAULT 0.00,
  `cashless_revenue` decimal(15,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Rekapitulasi harian pos pendapatan parkir mall dari M04';

-- --------------------------------------------------------

--
-- Table structure for table `06_event_revenue`
--

CREATE TABLE `06_event_revenue` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL COMMENT 'ID booking event dari M04',
  `revenue_type` varchar(30) NOT NULL COMMENT 'Venue_rental, sponsorship, ticketing',
  `amount` decimal(15,2) NOT NULL,
  `received_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pencatatan kas masuk hasil sewa area event dari M04';

-- --------------------------------------------------------

--
-- Table structure for table `06_invoices`
--

CREATE TABLE `06_invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL COMMENT 'Contoh: INV-2026-06-001',
  `contract_id` int(11) NOT NULL COMMENT 'FK ke M02.contracts',
  `tenant_id` int(11) NOT NULL COMMENT 'FK ke M02.tenants',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `ppn_rate` decimal(5,2) DEFAULT 11.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL COMMENT 'Total tagihan bruto yang harus dibayar',
  `due_date` date NOT NULL COMMENT 'Jatuh tempo pembayaran',
  `status` varchar(20) DEFAULT 'Belum Bayar' COMMENT 'Status: Belum Bayar, Lunas, Overdue, Cancelled',
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(30) DEFAULT NULL COMMENT 'Cash, Bank Transfer, E-Money',
  `amount_paid` decimal(15,2) DEFAULT 0.00 COMMENT 'Nominal uang yang dibayarkan oleh tenant',
  `payment_proof` varchar(255) DEFAULT NULL COMMENT 'Path file bukti transfer',
  `days_overdue` int(11) DEFAULT 0 COMMENT 'Jumlah hari keterlambatan sejak due_date',
  `aging_bucket` varchar(20) DEFAULT 'current' COMMENT 'Kategori: current, 1-30, 31-60, >90 hari',
  `created_by` int(11) DEFAULT NULL COMMENT 'FK ke M09.users',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel induk tagihan sewa tenant (Sewa + Pembayaran + Piutang)';

-- --------------------------------------------------------

--
-- Table structure for table `06_invoice_items`
--

CREATE TABLE `06_invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `charge_type` varchar(50) DEFAULT NULL COMMENT 'rent, service_charge, utility, maintenance',
  `amount` decimal(15,2) NOT NULL COMMENT 'Nominal per jenis tagihan',
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Detail rincian biaya komponen di dalam lembar invoice';

-- --------------------------------------------------------

--
-- Table structure for table `06_journal_entries`
--

CREATE TABLE `06_journal_entries` (
  `id` int(11) NOT NULL,
  `journal_number` varchar(50) NOT NULL,
  `journal_date` date NOT NULL,
  `description` text NOT NULL,
  `source_type` varchar(30) DEFAULT NULL COMMENT 'invoice_payment, vendor_payment, parking, event, ad, manual',
  `source_id` int(11) DEFAULT NULL,
  `total_debit` decimal(15,2) DEFAULT 0.00,
  `total_credit` decimal(15,2) DEFAULT 0.00,
  `is_balanced` tinyint(1) GENERATED ALWAYS AS (if(`total_debit` = `total_credit`,1,0)) STORED,
  `status` varchar(20) DEFAULT 'draft' COMMENT 'draft, posted, reversed',
  `is_reconciled` tinyint(1) DEFAULT 0 COMMENT '1 jika sudah cocok dengan rekening koran bank',
  `reconciled_at` datetime DEFAULT NULL COMMENT 'Waktu eksekusi rekonsiliasi kas/bank',
  `posted_by` int(11) DEFAULT NULL COMMENT 'FK ke M09.users',
  `posted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Ringkasan pencatatan akuntansi / double-entry accounting book';

-- --------------------------------------------------------

--
-- Table structure for table `06_journal_lines`
--

CREATE TABLE `06_journal_lines` (
  `id` int(11) NOT NULL,
  `journal_entry_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Detail mutasi debet dan kredit akun pembentuk Buku Besar';

-- --------------------------------------------------------

--
-- Table structure for table `06_mall_budgets`
--

CREATE TABLE `06_mall_budgets` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL COMMENT 'FK ke chart_of_accounts untuk menentukan pos biayanya',
  `budget_year` int(11) NOT NULL COMMENT 'Contoh: 2026',
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Plafon total anggaran yang disetujui',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Penyimpanan data batas anggaran tahunan untuk analisis deviasi realisasi biaya operasional';

-- --------------------------------------------------------

--
-- Table structure for table `06_purchase_orders`
--

CREATE TABLE `06_purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `pr_id` int(11) DEFAULT NULL,
  `vendor_name` varchar(100) NOT NULL,
  `order_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Surat pemesanan/order resmi sistem M06 ke pihak ketiga/vendor';

-- --------------------------------------------------------

--
-- Table structure for table `06_purchase_requests`
--

CREATE TABLE `06_purchase_requests` (
  `id` int(11) NOT NULL,
  `pr_number` varchar(50) NOT NULL,
  `requested_by` varchar(100) DEFAULT NULL COMMENT 'Nama pegawai pengaju dari M07',
  `requested_at` datetime NOT NULL,
  `description` text DEFAULT NULL,
  `estimated_amount` decimal(15,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'draft' COMMENT 'draft, pending_approval, approved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Form awal pengajuan internal belanja logistik operasional mall';

-- --------------------------------------------------------

--
-- Table structure for table `06_vendor_bill_receipts`
--

CREATE TABLE `06_vendor_bill_receipts` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `gr_number` varchar(50) NOT NULL,
  `received_date` date NOT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `ticket_ref` varchar(50) DEFAULT NULL COMMENT 'No tiket penyelesaian maintenance dari M03',
  `vendor_invoice_number` varchar(50) NOT NULL,
  `invoice_amount` decimal(15,2) NOT NULL,
  `is_matched` tinyint(1) DEFAULT 0 COMMENT '1 jika PO Amount = Invoice Amount dan Fisik OK',
  `status` varchar(20) DEFAULT 'pending_match'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Peleburan tabel fisik gudang & tagihan vendor demi kebutuhan audit 3-Way Matching';

-- --------------------------------------------------------

--
-- Table structure for table `07_absensi`
--

CREATE TABLE `07_absensi` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `status` enum('hadir','izin','sakit','alpha') DEFAULT 'hadir',
  `foto_masuk` varchar(255) DEFAULT NULL,
  `lokasi_masuk` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `07_cuti`
--

CREATE TABLE `07_cuti` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `jenis_cuti` enum('tahunan','sakit','melahirkan','darurat') NOT NULL,
  `alasan` text NOT NULL,
  `status` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `07_jadwal_shift`
--

CREATE TABLE `07_jadwal_shift` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `07_kpi`
--

CREATE TABLE `07_kpi` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `periode` varchar(20) NOT NULL,
  `target_kerja` text DEFAULT NULL,
  `realisasi` text DEFAULT NULL,
  `nilai` int(11) DEFAULT 0,
  `kategori` enum('sangat_baik','baik','cukup','kurang') DEFAULT 'cukup',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `07_payroll`
--

CREATE TABLE `07_payroll` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `bulan` int(11) NOT NULL,
  `tahun` int(11) NOT NULL,
  `gaji_pokok` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tunjangan` decimal(12,2) DEFAULT 0.00,
  `potongan` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) GENERATED ALWAYS AS (`gaji_pokok` + `tunjangan` - `potongan`) STORED,
  `status` enum('draft','final') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `07_pegawai`
--

CREATE TABLE `07_pegawai` (
  `id` int(11) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `agama` varchar(20) DEFAULT NULL,
  `pendidikan_terakhir` varchar(30) DEFAULT NULL,
  `jabatan` varchar(50) NOT NULL,
  `departemen` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `tgl_masuk` date NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `foto` varchar(255) DEFAULT NULL,
  `spesialisasi` varchar(100) DEFAULT NULL,
  `sertifikasi` varchar(255) DEFAULT NULL,
  `nama_bank` varchar(50) DEFAULT NULL,
  `no_rekening` varchar(30) DEFAULT NULL,
  `kontak_darurat_nama` varchar(100) DEFAULT NULL,
  `kontak_darurat_hp` varchar(15) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `07_pegawai`
--

INSERT INTO `07_pegawai` (`id`, `nik`, `nama`, `jenis_kelamin`, `agama`, `pendidikan_terakhir`, `jabatan`, `departemen`, `email`, `no_hp`, `alamat`, `tgl_lahir`, `tgl_masuk`, `status`, `foto`, `spesialisasi`, `sertifikasi`, `nama_bank`, `no_rekening`, `kontak_darurat_nama`, `kontak_darurat_hp`, `created_at`, `updated_at`) VALUES
(1, 'EMP001', 'Budi Santoso', 'L', 'Islam', 'S1', 'Staff HR', 'HR', 'budi@mall.com', '081234567890', NULL, NULL, '2023-01-10', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-14 10:18:41', '2026-06-14 10:18:41'),
(2, 'EMP002', 'Siti Rahayu', 'P', 'Islam', 'SMA', 'Kasir', 'CS', 'siti@mall.com', '081234567891', NULL, NULL, '2023-02-15', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-14 10:18:41', '2026-06-14 10:18:41'),
(3, 'EMP003', 'Ahmad Fauzi', 'L', 'Islam', 'SMA', 'Security', 'Security', 'ahmad@mall.com', '081234567892', NULL, NULL, '2022-11-01', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-14 10:18:41', '2026-06-14 10:18:41'),
(4, 'EMP004', 'Dewi Lestari', 'P', 'Kristen', 'S1', 'Supervisor', 'Operations', 'dewi@mall.com', '081234567893', NULL, NULL, '2021-06-20', 'aktif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-14 10:18:41', '2026-06-14 10:18:41'),
(5, 'EMP005', 'Rizky Pratama', 'L', 'Islam', 'D3', 'Teknisi', 'Facility', 'rizky@mall.com', '081234567894', NULL, NULL, '2022-03-05', 'aktif', NULL, 'Electrical', 'Sertifikat K3 Listrik', NULL, NULL, NULL, NULL, '2026-06-14 10:18:41', '2026-06-14 10:18:41');

-- --------------------------------------------------------

--
-- Table structure for table `07_shift`
--

CREATE TABLE `07_shift` (
  `id` int(11) NOT NULL,
  `nama_shift` varchar(50) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_keluar` time NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `07_shift`
--

INSERT INTO `07_shift` (`id`, `nama_shift`, `jam_masuk`, `jam_keluar`, `keterangan`, `created_at`) VALUES
(1, 'Pagi', '07:00:00', '15:00:00', 'Shift pagi reguler', '2026-06-14 10:18:41'),
(2, 'Siang', '13:00:00', '21:00:00', 'Shift siang reguler', '2026-06-14 10:18:41'),
(3, 'Malam', '21:00:00', '07:00:00', 'Shift malam', '2026-06-14 10:18:41');

-- --------------------------------------------------------

--
-- Table structure for table `08_approval_audit_logs`
--

CREATE TABLE `08_approval_audit_logs` (
  `id` int(11) NOT NULL COMMENT 'ID unik log',
  `request_id` int(11) NOT NULL COMMENT 'ID pengajuan (FK ke tabel approval_requests)',
  `level_number` int(11) NOT NULL COMMENT 'Level approval yang diproses (1,2,3...)',
  `decision` varchar(20) NOT NULL COMMENT 'Keputusan: approved, rejected, delegated',
  `comment` text DEFAULT NULL COMMENT 'Komentar approver',
  `approver_name` varchar(100) DEFAULT NULL COMMENT 'Nama approver',
  `approver_role` varchar(100) DEFAULT NULL COMMENT 'Jabatan approver. Contoh: Finance Manager',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Waktu keputusan diambil'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan setiap keputusan dalam proses approval. Satu pengajuan bisa punya banyak log';

-- --------------------------------------------------------

--
-- Table structure for table `08_approval_requests`
--

CREATE TABLE `08_approval_requests` (
  `approval_id` int(11) NOT NULL,
  `request_number` varchar(30) NOT NULL,
  `request_type` varchar(30) NOT NULL COMMENT 'contract, renovation, purchase, event, maintenance',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft' COMMENT 'draft, pending, approved, rejected',
  `current_level` int(11) DEFAULT 1,
  `submitted_by` varchar(100) DEFAULT NULL COMMENT 'Nama pengaju',
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL COMMENT 'Nama approver',
  `approved_at` datetime DEFAULT NULL,
  `reject_reason` text DEFAULT NULL COMMENT 'Alasan ditolak',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan pengajuan approval dari berbagai modul';

-- --------------------------------------------------------

--
-- Table structure for table `08_kpi_snapshots`
--

CREATE TABLE `08_kpi_snapshots` (
  `snapshot_id` int(11) NOT NULL,
  `period_type` varchar(20) NOT NULL COMMENT 'daily, weekly, monthly, annual',
  `period_date` date NOT NULL,
  `occupancy_rate` decimal(5,2) DEFAULT NULL COMMENT 'Persentase unit terisi',
  `total_revenue` decimal(15,2) DEFAULT NULL COMMENT 'Total pendapatan dalam rupiah',
  `total_visitors` int(11) DEFAULT NULL COMMENT 'Total pengunjung',
  `top_tenants` text DEFAULT NULL COMMENT '5 tenant teratas berdasarkan revenue',
  `generated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan snapshot KPI yang sudah dihitung untuk laporan periodik';

-- --------------------------------------------------------

--
-- Table structure for table `08_notification_logs`
--

CREATE TABLE `08_notification_logs` (
  `notification_log_id` int(11) NOT NULL,
  `notification_id` varchar(50) NOT NULL,
  `recipient_email` varchar(100) NOT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `notification_type` varchar(30) NOT NULL COMMENT 'contract_expiry, payment_due, approval_request, approval_result',
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `channel` varchar(20) DEFAULT 'email' COMMENT 'email, inapp',
  `status` varchar(20) DEFAULT 'pending' COMMENT 'sent, failed, pending',
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL COMMENT 'FK ke M09.users'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan semua notifikasi yang dikirim sistem';

-- --------------------------------------------------------

--
-- Table structure for table `08_notification_templates`
--

CREATE TABLE `08_notification_templates` (
  `id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL COMMENT 'Jenis notifikasi',
  `subject_template` varchar(255) NOT NULL COMMENT 'Template subjek',
  `body_template` text NOT NULL COMMENT 'Template isi pesan',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Status aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `09_role_pages`
--

CREATE TABLE `09_role_pages` (
  `id` int(11) NOT NULL,
  `role` enum('Super Admin','Mall Director','Leasing Manager','Finance Manager','Finance Staff','HR Manager','HR Admin','Facility Manager','Teknisi','Customer Service','Security','Petugas Parkir','Tenant','Event Manager','Event Organizer') NOT NULL,
  `page_permission` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `09_role_pages`
--

INSERT INTO `09_role_pages` (`id`, `role`, `page_permission`) VALUES
(1, 'Event Manager', 'pages/eventManager/*'),
(2, 'Event Organizer', 'pages/eventOrganizer/*'),
(3, 'Event Manager', 'pages/eventManager/*'),
(4, 'Event Organizer', 'pages/eventOrganizer/*'),
(5, 'Event Manager', 'pages/eventManager/*'),
(6, 'Event Organizer', 'pages/eventOrganizer/*'),
(7, 'Event Manager', 'pages/eventManager/*'),
(8, 'Event Organizer', 'pages/eventOrganizer/*'),
(9, 'Event Manager', 'pages/eventManager/*'),
(10, 'Event Organizer', 'pages/eventOrganizer/*'),
(11, 'Event Manager', 'pages/eventManager/*'),
(12, 'Event Organizer', 'pages/eventOrganizer/*'),
(13, 'Event Manager', 'pages/eventManager/*'),
(14, 'Event Organizer', 'pages/eventOrganizer/*'),
(15, 'Event Manager', 'pages/eventManager/*'),
(16, 'Event Organizer', 'pages/eventOrganizer/*'),
(17, 'Event Manager', 'pages/eventManager/*'),
(18, 'Event Organizer', 'pages/eventOrganizer/*'),
(19, 'Event Manager', 'pages/eventManager/*'),
(20, 'Event Organizer', 'pages/eventOrganizer/*'),
(21, 'Event Manager', 'pages/eventManager/*'),
(22, 'Event Organizer', 'pages/eventOrganizer/*'),
(23, 'Event Manager', 'pages/eventManager/*'),
(24, 'Event Organizer', 'pages/eventOrganizer/*');

-- --------------------------------------------------------

--
-- Table structure for table `09_users`
--

CREATE TABLE `09_users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT 1,
  `role_page_id` int(11) DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `is_blocked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `01_buildings`
--
ALTER TABLE `01_buildings`
  ADD PRIMARY KEY (`id_buildings`),
  ADD KEY `fk_buildings_malls` (`mall_id`);

--
-- Indexes for table `01_floors`
--
ALTER TABLE `01_floors`
  ADD PRIMARY KEY (`id_floors`),
  ADD KEY `fk_floors_buildings` (`building_id`);

--
-- Indexes for table `01_malls`
--
ALTER TABLE `01_malls`
  ADD PRIMARY KEY (`id_malls`);

--
-- Indexes for table `01_tenant_categories`
--
ALTER TABLE `01_tenant_categories`
  ADD PRIMARY KEY (`id_tenant_categories`);

--
-- Indexes for table `01_units`
--
ALTER TABLE `01_units`
  ADD PRIMARY KEY (`id_units`),
  ADD KEY `fk_units_floors` (`floor_id`),
  ADD KEY `fk_units_tenants` (`tenant_id`);

--
-- Indexes for table `01_unit_types`
--
ALTER TABLE `01_unit_types`
  ADD PRIMARY KEY (`id_unit_types`);

--
-- Indexes for table `02_contracts`
--
ALTER TABLE `02_contracts`
  ADD PRIMARY KEY (`id_contract`),
  ADD UNIQUE KEY `contract_number` (`contract_number`),
  ADD KEY `fk_contract_tenant` (`id_tenant`),
  ADD KEY `fk_contract_unit` (`id_unit`);

--
-- Indexes for table `02_contract_cost`
--
ALTER TABLE `02_contract_cost`
  ADD PRIMARY KEY (`id_component`),
  ADD KEY `fk_cost_contract` (`id_contract`);

--
-- Indexes for table `02_tenants`
--
ALTER TABLE `02_tenants`
  ADD PRIMARY KEY (`id_tenant`),
  ADD KEY `fk_tenant_prospect` (`id_prospect`),
  ADD KEY `fk_tenant_categories` (`id_category`);

--
-- Indexes for table `02_tenant_complaints`
--
ALTER TABLE `02_tenant_complaints`
  ADD PRIMARY KEY (`id_complaint`),
  ADD KEY `fk_complaint_tenant` (`id_tenant`),
  ADD KEY `fk_complaint_unit` (`id_unit`);

--
-- Indexes for table `02_tenant_deposits`
--
ALTER TABLE `02_tenant_deposits`
  ADD PRIMARY KEY (`id_deposit`),
  ADD KEY `fk_deposit_contract` (`id_contract`);

--
-- Indexes for table `02_tenant_prospects`
--
ALTER TABLE `02_tenant_prospects`
  ADD PRIMARY KEY (`id_prospect`),
  ADD KEY `fk_prospect_unit` (`interested_unit`),
  ADD KEY `fk_prospect_categories` (`id_category`);

--
-- Indexes for table `02_tenant_renovations`
--
ALTER TABLE `02_tenant_renovations`
  ADD PRIMARY KEY (`id_renovation`),
  ADD KEY `fk_renovation_contract` (`id_contract`);

--
-- Indexes for table `03_assets`
--
ALTER TABLE `03_assets`
  ADD PRIMARY KEY (`asset_id`),
  ADD UNIQUE KEY `asset_code` (`asset_code`),
  ADD KEY `fk_depreciation_method` (`depreciation_policy`);

--
-- Indexes for table `03_asset_mutations`
--
ALTER TABLE `03_asset_mutations`
  ADD PRIMARY KEY (`mutation_id`),
  ADD KEY `fk_mutation_asset` (`asset_id`);

--
-- Indexes for table `03_checklist`
--
ALTER TABLE `03_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `03_damage_reports`
--
ALTER TABLE `03_damage_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_report_creator` (`created_by`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `fk_damage_reports_tiket` (`ticket_id`);

--
-- Indexes for table `03_maintenance_schedule`
--
ALTER TABLE `03_maintenance_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `03_technicians`
--
ALTER TABLE `03_technicians`
  ADD PRIMARY KEY (`technician_id`),
  ADD UNIQUE KEY `employee_code` (`NIK`),
  ADD KEY `fk_technician_user` (`user_id`);

--
-- Indexes for table `03_technician_skills`
--
ALTER TABLE `03_technician_skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD KEY `fk_skill_technician` (`technician_id`);

--
-- Indexes for table `03_work_orders`
--
ALTER TABLE `03_work_orders`
  ADD PRIMARY KEY (`work_order_id`),
  ADD UNIQUE KEY `work_order_number` (`work_order_number`),
  ADD KEY `fk_workorder_report` (`report_id`),
  ADD KEY `fk_workorder_technician` (`technician_id`);

--
-- Indexes for table `03_work_order_activities`
--
ALTER TABLE `03_work_order_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `fk_activity_workorder` (`work_order_id`);

--
-- Indexes for table `04_event_analytics`
--
ALTER TABLE `04_event_analytics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_eventanalytics_booking` (`id_booking`),
  ADD KEY `fk_analytics_booking` (`id_booking`);

--
-- Indexes for table `04_event_areas`
--
ALTER TABLE `04_event_areas`
  ADD PRIMARY KEY (`id_area`),
  ADD KEY `fk_eventarea_floor` (`floor_id`);

--
-- Indexes for table `04_event_booking`
--
ALTER TABLE `04_event_booking`
  ADD PRIMARY KEY (`id_booking`),
  ADD KEY `fk_booking_area` (`id_area`),
  ADD KEY `fk_booking_user` (`id_user`),
  ADD KEY `idx_booking_tanggal` (`id_area`,`tanggal_mulai`,`tanggal_selesai`);

--
-- Indexes for table `04_event_booking_vendor`
--
ALTER TABLE `04_event_booking_vendor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bookingvendor` (`id_booking`,`nama_vendor`),
  ADD KEY `fk_bookingvendor_booking` (`id_booking`);

--
-- Indexes for table `04_event_sponsorship`
--
ALTER TABLE `04_event_sponsorship`
  ADD PRIMARY KEY (`id_sponsor`),
  ADD KEY `fk_sponsorship_booking` (`id_booking`);

--
-- Indexes for table `04_event_tiket`
--
ALTER TABLE `04_event_tiket`
  ADD PRIMARY KEY (`id_tiket`),
  ADD KEY `fk_tiket_booking` (`id_booking`);

--
-- Indexes for table `04_invoice_utilitas`
--
ALTER TABLE `04_invoice_utilitas`
  ADD PRIMARY KEY (`id_invoice`),
  ADD KEY `fk_invoiceutil_tenant` (`tenant_id`),
  ADD KEY `fk_invoiceutil_meter` (`id_meter`);

--
-- Indexes for table `04_parking_member`
--
ALTER TABLE `04_parking_member`
  ADD PRIMARY KEY (`id_member`),
  ADD UNIQUE KEY `uq_parkingmember_plat` (`plat_nomor`),
  ADD KEY `fk_parkingmember_tenant` (`tenant_id`);

--
-- Indexes for table `04_parking_tarif`
--
ALTER TABLE `04_parking_tarif`
  ADD PRIMARY KEY (`id_tarif`),
  ADD KEY `idx_tarif_lookup` (`tipe_kendaraan`,`tipe_user`,`berlaku_dari`);

--
-- Indexes for table `04_parking_transaksi`
--
ALTER TABLE `04_parking_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `fk_parkingtrx_petugas` (`petugas_id`),
  ADD KEY `fk_parkingtrx_member` (`id_member`),
  ADD KEY `fk_parkingtrx_zona` (`zona_id`);

--
-- Indexes for table `04_parking_zona`
--
ALTER TABLE `04_parking_zona`
  ADD PRIMARY KEY (`id_zona`),
  ADD KEY `fk_parkingzona_floor` (`floor_id`);

--
-- Indexes for table `04_utility_meters`
--
ALTER TABLE `04_utility_meters`
  ADD PRIMARY KEY (`id_meter`),
  ADD KEY `fk_utilitymeter_unit` (`unit_id`);

--
-- Indexes for table `04_utility_meter_logs`
--
ALTER TABLE `04_utility_meter_logs`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `fk_meterlog_user` (`recorded_by`),
  ADD KEY `fk_meterlog_meter` (`id_meter`);

--
-- Indexes for table `05_cs_feedback`
--
ALTER TABLE `05_cs_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `05_found_items`
--
ALTER TABLE `05_found_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `05_lost_reports`
--
ALTER TABLE `05_lost_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `05_tiket`
--
ALTER TABLE `05_tiket`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `05_tiket_log`
--
ALTER TABLE `05_tiket_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tiket_id` (`tiket_id`);

--
-- Indexes for table `06_ad_contracts`
--
ALTER TABLE `06_ad_contracts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `06_approval_log`
--
ALTER TABLE `06_approval_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_po` (`po_id`),
  ADD KEY `fk_approval_log_request` (`approval_request_id_fk`);

--
-- Indexes for table `06_chart_of_accounts`
--
ALTER TABLE `06_chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`),
  ADD UNIQUE KEY `idx_depretiation_method` (`depreciation_method`);

--
-- Indexes for table `06_daily_parking_summary`
--
ALTER TABLE `06_daily_parking_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `summary_date` (`summary_date`);

--
-- Indexes for table `06_event_revenue`
--
ALTER TABLE `06_event_revenue`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `06_invoices`
--
ALTER TABLE `06_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `06_invoice_items`
--
ALTER TABLE `06_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_invoice` (`invoice_id`);

--
-- Indexes for table `06_journal_entries`
--
ALTER TABLE `06_journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `journal_number` (`journal_number`);

--
-- Indexes for table `06_journal_lines`
--
ALTER TABLE `06_journal_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_line_entry` (`journal_entry_id`),
  ADD KEY `fk_line_account` (`account_id`);

--
-- Indexes for table `06_mall_budgets`
--
ALTER TABLE `06_mall_budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_budget_coa` (`account_id`);

--
-- Indexes for table `06_purchase_orders`
--
ALTER TABLE `06_purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `fk_po_pr` (`pr_id`);

--
-- Indexes for table `06_purchase_requests`
--
ALTER TABLE `06_purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pr_number` (`pr_number`);

--
-- Indexes for table `06_vendor_bill_receipts`
--
ALTER TABLE `06_vendor_bill_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gr_number` (`gr_number`),
  ADD KEY `fk_vbr_po` (`po_id`);

--
-- Indexes for table `07_absensi`
--
ALTER TABLE `07_absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `07_cuti`
--
ALTER TABLE `07_cuti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `07_jadwal_shift`
--
ALTER TABLE `07_jadwal_shift`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `shift_id` (`shift_id`);

--
-- Indexes for table `07_kpi`
--
ALTER TABLE `07_kpi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `07_payroll`
--
ALTER TABLE `07_payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `07_pegawai`
--
ALTER TABLE `07_pegawai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `07_shift`
--
ALTER TABLE `07_shift`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `08_approval_audit_logs`
--
ALTER TABLE `08_approval_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_audit_requests` (`request_id`);

--
-- Indexes for table `08_approval_requests`
--
ALTER TABLE `08_approval_requests`
  ADD PRIMARY KEY (`approval_id`),
  ADD UNIQUE KEY `request_number` (`request_number`);

--
-- Indexes for table `08_kpi_snapshots`
--
ALTER TABLE `08_kpi_snapshots`
  ADD PRIMARY KEY (`snapshot_id`);

--
-- Indexes for table `08_notification_logs`
--
ALTER TABLE `08_notification_logs`
  ADD PRIMARY KEY (`notification_log_id`),
  ADD UNIQUE KEY `notification_id` (`notification_id`),
  ADD KEY `fk_notif_user_m08` (`user_id`);

--
-- Indexes for table `08_notification_templates`
--
ALTER TABLE `08_notification_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `09_role_pages`
--
ALTER TABLE `09_role_pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `09_users`
--
ALTER TABLE `09_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_page_id` (`role_page_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `01_buildings`
--
ALTER TABLE `01_buildings`
  MODIFY `id_buildings` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik gedung/tower';

--
-- AUTO_INCREMENT for table `01_floors`
--
ALTER TABLE `01_floors`
  MODIFY `id_floors` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik lantai';

--
-- AUTO_INCREMENT for table `01_malls`
--
ALTER TABLE `01_malls`
  MODIFY `id_malls` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik mall/cabang';

--
-- AUTO_INCREMENT for table `01_tenant_categories`
--
ALTER TABLE `01_tenant_categories`
  MODIFY `id_tenant_categories` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik kategori tenant';

--
-- AUTO_INCREMENT for table `01_units`
--
ALTER TABLE `01_units`
  MODIFY `id_units` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik unit/kios';

--
-- AUTO_INCREMENT for table `01_unit_types`
--
ALTER TABLE `01_unit_types`
  MODIFY `id_unit_types` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik tipe unit';

--
-- AUTO_INCREMENT for table `02_contracts`
--
ALTER TABLE `02_contracts`
  MODIFY `id_contract` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `02_contract_cost`
--
ALTER TABLE `02_contract_cost`
  MODIFY `id_component` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `02_tenants`
--
ALTER TABLE `02_tenants`
  MODIFY `id_tenant` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `02_tenant_complaints`
--
ALTER TABLE `02_tenant_complaints`
  MODIFY `id_complaint` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `02_tenant_deposits`
--
ALTER TABLE `02_tenant_deposits`
  MODIFY `id_deposit` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `02_tenant_prospects`
--
ALTER TABLE `02_tenant_prospects`
  MODIFY `id_prospect` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `02_tenant_renovations`
--
ALTER TABLE `02_tenant_renovations`
  MODIFY `id_renovation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `03_assets`
--
ALTER TABLE `03_assets`
  MODIFY `asset_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `03_asset_mutations`
--
ALTER TABLE `03_asset_mutations`
  MODIFY `mutation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `03_checklist`
--
ALTER TABLE `03_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `03_damage_reports`
--
ALTER TABLE `03_damage_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `03_maintenance_schedule`
--
ALTER TABLE `03_maintenance_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `03_technicians`
--
ALTER TABLE `03_technicians`
  MODIFY `technician_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `03_technician_skills`
--
ALTER TABLE `03_technician_skills`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `03_work_orders`
--
ALTER TABLE `03_work_orders`
  MODIFY `work_order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `03_work_order_activities`
--
ALTER TABLE `03_work_order_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `04_event_analytics`
--
ALTER TABLE `04_event_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `04_event_areas`
--
ALTER TABLE `04_event_areas`
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `04_event_booking`
--
ALTER TABLE `04_event_booking`
  MODIFY `id_booking` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik pengajuan booking event';

--
-- AUTO_INCREMENT for table `04_event_booking_vendor`
--
ALTER TABLE `04_event_booking_vendor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `04_invoice_utilitas`
--
ALTER TABLE `04_invoice_utilitas`
  MODIFY `id_invoice` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik invoice utilitas';

--
-- AUTO_INCREMENT for table `04_parking_member`
--
ALTER TABLE `04_parking_member`
  MODIFY `id_member` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik member parkir';

--
-- AUTO_INCREMENT for table `04_parking_tarif`
--
ALTER TABLE `04_parking_tarif`
  MODIFY `id_tarif` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `04_parking_transaksi`
--
ALTER TABLE `04_parking_transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik transaksi parkir';

--
-- AUTO_INCREMENT for table `04_parking_zona`
--
ALTER TABLE `04_parking_zona`
  MODIFY `id_zona` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik zona parkir';

--
-- AUTO_INCREMENT for table `04_utility_meters`
--
ALTER TABLE `04_utility_meters`
  MODIFY `id_meter` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik meter utilitas';

--
-- AUTO_INCREMENT for table `04_utility_meter_logs`
--
ALTER TABLE `04_utility_meter_logs`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik log pembacaan meter';

--
-- AUTO_INCREMENT for table `05_cs_feedback`
--
ALTER TABLE `05_cs_feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `05_found_items`
--
ALTER TABLE `05_found_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `05_lost_reports`
--
ALTER TABLE `05_lost_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `05_tiket_log`
--
ALTER TABLE `05_tiket_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_ad_contracts`
--
ALTER TABLE `06_ad_contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_approval_log`
--
ALTER TABLE `06_approval_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_chart_of_accounts`
--
ALTER TABLE `06_chart_of_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_daily_parking_summary`
--
ALTER TABLE `06_daily_parking_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_event_revenue`
--
ALTER TABLE `06_event_revenue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_invoices`
--
ALTER TABLE `06_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_invoice_items`
--
ALTER TABLE `06_invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_journal_entries`
--
ALTER TABLE `06_journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_journal_lines`
--
ALTER TABLE `06_journal_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_mall_budgets`
--
ALTER TABLE `06_mall_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_purchase_orders`
--
ALTER TABLE `06_purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_purchase_requests`
--
ALTER TABLE `06_purchase_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_vendor_bill_receipts`
--
ALTER TABLE `06_vendor_bill_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `07_absensi`
--
ALTER TABLE `07_absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `07_cuti`
--
ALTER TABLE `07_cuti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `07_jadwal_shift`
--
ALTER TABLE `07_jadwal_shift`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `07_kpi`
--
ALTER TABLE `07_kpi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `07_payroll`
--
ALTER TABLE `07_payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `07_pegawai`
--
ALTER TABLE `07_pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `07_shift`
--
ALTER TABLE `07_shift`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `08_approval_audit_logs`
--
ALTER TABLE `08_approval_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID unik log';

--
-- AUTO_INCREMENT for table `08_approval_requests`
--
ALTER TABLE `08_approval_requests`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `08_kpi_snapshots`
--
ALTER TABLE `08_kpi_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `08_notification_logs`
--
ALTER TABLE `08_notification_logs`
  MODIFY `notification_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `08_notification_templates`
--
ALTER TABLE `08_notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `09_role_pages`
--
ALTER TABLE `09_role_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `09_users`
--
ALTER TABLE `09_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `01_buildings`
--
ALTER TABLE `01_buildings`
  ADD CONSTRAINT `fk_buildings_malls` FOREIGN KEY (`mall_id`) REFERENCES `01_malls` (`id_malls`) ON DELETE CASCADE;

--
-- Constraints for table `01_floors`
--
ALTER TABLE `01_floors`
  ADD CONSTRAINT `fk_floors_buildings` FOREIGN KEY (`building_id`) REFERENCES `01_buildings` (`id_buildings`) ON DELETE CASCADE;

--
-- Constraints for table `01_units`
--
ALTER TABLE `01_units`
  ADD CONSTRAINT `fk_units_floors` FOREIGN KEY (`floor_id`) REFERENCES `01_floors` (`id_floors`),
  ADD CONSTRAINT `fk_units_tenants` FOREIGN KEY (`tenant_id`) REFERENCES `02_tenants` (`id_tenant`) ON DELETE SET NULL;

--
-- Constraints for table `02_contracts`
--
ALTER TABLE `02_contracts`
  ADD CONSTRAINT `fk_contract_tenant` FOREIGN KEY (`id_tenant`) REFERENCES `02_tenants` (`id_tenant`),
  ADD CONSTRAINT `fk_contract_unit` FOREIGN KEY (`id_unit`) REFERENCES `01_units` (`id_units`);

--
-- Constraints for table `02_contract_cost`
--
ALTER TABLE `02_contract_cost`
  ADD CONSTRAINT `fk_cost_contract` FOREIGN KEY (`id_contract`) REFERENCES `02_contracts` (`id_contract`) ON DELETE CASCADE;

--
-- Constraints for table `02_tenants`
--
ALTER TABLE `02_tenants`
  ADD CONSTRAINT `fk_tenant_categories` FOREIGN KEY (`id_category`) REFERENCES `01_tenant_categories` (`id_tenant_categories`),
  ADD CONSTRAINT `fk_tenant_prospect` FOREIGN KEY (`id_prospect`) REFERENCES `02_tenant_prospects` (`id_prospect`);

--
-- Constraints for table `02_tenant_complaints`
--
ALTER TABLE `02_tenant_complaints`
  ADD CONSTRAINT `fk_complaint_tenant` FOREIGN KEY (`id_tenant`) REFERENCES `02_tenants` (`id_tenant`),
  ADD CONSTRAINT `fk_complaint_unit` FOREIGN KEY (`id_unit`) REFERENCES `01_units` (`id_units`);

--
-- Constraints for table `02_tenant_deposits`
--
ALTER TABLE `02_tenant_deposits`
  ADD CONSTRAINT `fk_deposit_contract` FOREIGN KEY (`id_contract`) REFERENCES `02_contracts` (`id_contract`);

--
-- Constraints for table `02_tenant_prospects`
--
ALTER TABLE `02_tenant_prospects`
  ADD CONSTRAINT `fk_prospect_categories` FOREIGN KEY (`id_category`) REFERENCES `01_tenant_categories` (`id_tenant_categories`),
  ADD CONSTRAINT `fk_prospect_unit` FOREIGN KEY (`interested_unit`) REFERENCES `01_units` (`id_units`) ON DELETE SET NULL;

--
-- Constraints for table `02_tenant_renovations`
--
ALTER TABLE `02_tenant_renovations`
  ADD CONSTRAINT `fk_renovation_contract` FOREIGN KEY (`id_contract`) REFERENCES `02_contracts` (`id_contract`);

--
-- Constraints for table `03_asset_mutations`
--
ALTER TABLE `03_asset_mutations`
  ADD CONSTRAINT `fk_mutation_asset` FOREIGN KEY (`asset_id`) REFERENCES `03_assets` (`asset_id`) ON DELETE CASCADE;

--
-- Constraints for table `04_event_analytics`
--
ALTER TABLE `04_event_analytics`
  ADD CONSTRAINT `fk_analytics_booking` FOREIGN KEY (`id_booking`) REFERENCES `04_event_booking` (`id_booking`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `04_event_areas`
--
ALTER TABLE `04_event_areas`
  ADD CONSTRAINT `fk_eventarea_floor` FOREIGN KEY (`floor_id`) REFERENCES `01_floors` (`id_floors`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `04_event_booking`
--
ALTER TABLE `04_event_booking`
  ADD CONSTRAINT `fk_booking_area` FOREIGN KEY (`id_area`) REFERENCES `04_event_areas` (`id_area`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`id_user`) REFERENCES `09_users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `04_event_booking_vendor`
--
ALTER TABLE `04_event_booking_vendor`
  ADD CONSTRAINT `fk_bookingvendor_booking` FOREIGN KEY (`id_booking`) REFERENCES `04_event_booking` (`id_booking`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `04_event_sponsorship`
--
ALTER TABLE `04_event_sponsorship`
  ADD CONSTRAINT `fk_sponsorship_booking` FOREIGN KEY (`id_booking`) REFERENCES `04_event_booking` (`id_booking`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `04_event_tiket`
--
ALTER TABLE `04_event_tiket`
  ADD CONSTRAINT `fk_tiket_booking` FOREIGN KEY (`id_booking`) REFERENCES `04_event_booking` (`id_booking`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `04_invoice_utilitas`
--
ALTER TABLE `04_invoice_utilitas`
  ADD CONSTRAINT `fk_invoiceutil_meter` FOREIGN KEY (`id_meter`) REFERENCES `04_utility_meters` (`id_meter`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_invoiceutil_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `02_tenants` (`id_tenant`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `04_parking_member`
--
ALTER TABLE `04_parking_member`
  ADD CONSTRAINT `fk_parkingmember_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `02_tenants` (`id_tenant`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `04_parking_transaksi`
--
ALTER TABLE `04_parking_transaksi`
  ADD CONSTRAINT `fk_parkingtrx_member` FOREIGN KEY (`id_member`) REFERENCES `04_parking_member` (`id_member`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_parkingtrx_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `09_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_parkingtrx_zona` FOREIGN KEY (`zona_id`) REFERENCES `04_parking_zona` (`id_zona`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `04_parking_zona`
--
ALTER TABLE `04_parking_zona`
  ADD CONSTRAINT `fk_parkingzona_floor` FOREIGN KEY (`floor_id`) REFERENCES `01_floors` (`id_floors`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `04_utility_meters`
--
ALTER TABLE `04_utility_meters`
  ADD CONSTRAINT `fk_utilitymeter_unit` FOREIGN KEY (`unit_id`) REFERENCES `01_units` (`id_units`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `04_utility_meter_logs`
--
ALTER TABLE `04_utility_meter_logs`
  ADD CONSTRAINT `fk_meterlog_meter` FOREIGN KEY (`id_meter`) REFERENCES `04_utility_meters` (`id_meter`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_meterlog_user` FOREIGN KEY (`recorded_by`) REFERENCES `09_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
