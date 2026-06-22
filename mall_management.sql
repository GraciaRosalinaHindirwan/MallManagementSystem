-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 21, 2026 at 07:19 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

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
  `id_buildings` int NOT NULL COMMENT 'ID unik gedung/tower',
  `mall_id` int NOT NULL COMMENT 'ID mall tempat gedung berada (FK ke 01_malls.id_malls)',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nama gedung/tower',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu data dibuat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan data gedung/tower per mall';

--
-- Dumping data for table `01_buildings`
--

INSERT INTO `01_buildings` (`id_buildings`, `mall_id`, `name`, `created_at`) VALUES
(1, 1, 'Tower Utara', '2026-06-20 08:41:02'),
(2, 1, 'Tower Selatan', '2026-06-20 08:41:02'),
(3, 2, 'Tower A', '2026-06-20 08:41:02'),
(4, 3, 'Tower Timur', '2026-06-20 11:50:28'),
(5, 3, 'Tower Barat', '2026-06-20 11:50:28'),
(6, 4, 'Tower A', '2026-06-20 11:50:28'),
(7, 4, 'Tower B', '2026-06-20 11:50:28');

-- --------------------------------------------------------

--
-- Table structure for table `01_floors`
--

CREATE TABLE `01_floors` (
  `id_floors` int NOT NULL COMMENT 'ID unik lantai',
  `building_id` int NOT NULL COMMENT 'ID gedung tempat lantai berada (FK ke 01_buildings.id_buildings)',
  `floor_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nomor lantai. Contoh: LG, 1, 2',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu data dibuat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan data lantai per gedung';

--
-- Dumping data for table `01_floors`
--

INSERT INTO `01_floors` (`id_floors`, `building_id`, `floor_number`, `created_at`) VALUES
(1, 1, 'LG', '2026-06-20 08:41:02'),
(2, 1, '1', '2026-06-20 08:41:02'),
(3, 1, '2', '2026-06-20 08:41:02'),
(4, 2, '1', '2026-06-20 08:41:02'),
(5, 2, '2', '2026-06-20 08:41:02'),
(6, 3, '1', '2026-06-20 08:41:02'),
(7, 4, 'LG', '2026-06-20 11:50:28'),
(8, 4, '1', '2026-06-20 11:50:28'),
(9, 4, '2', '2026-06-20 11:50:28'),
(10, 5, '1', '2026-06-20 11:50:28'),
(11, 5, '2', '2026-06-20 11:50:28'),
(12, 6, 'LG', '2026-06-20 11:50:28'),
(13, 6, '1', '2026-06-20 11:50:28'),
(14, 7, '1', '2026-06-20 11:50:28');

-- --------------------------------------------------------

--
-- Table structure for table `01_malls`
--

CREATE TABLE `01_malls` (
  `id_malls` int NOT NULL COMMENT 'ID unik mall/cabang',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nama mall/cabang',
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Alamat lengkap mall',
  `city` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Kota lokasi mall',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu data dibuat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan data mall atau cabang';

--
-- Dumping data for table `01_malls`
--

INSERT INTO `01_malls` (`id_malls`, `name`, `address`, `city`, `created_at`) VALUES
(1, 'Mall Indonesia', 'Jl. Sudirman No. 1', 'Jakarta', '2026-06-20 08:41:02'),
(2, 'Mall Bintaro', 'Jl. Bintaro Utama No. 10', 'Tangerang', '2026-06-20 08:41:02'),
(3, 'Mall Kelapa Gading', 'Jl. Kelapa Gading Raya No. 5', 'Jakarta', '2026-06-20 11:50:28'),
(4, 'Mall PIK', 'Jl. Pantai Indah Kapuk No. 15', 'Jakarta', '2026-06-20 11:50:28');

-- --------------------------------------------------------

--
-- Table structure for table `01_tenant_categories`
--

CREATE TABLE `01_tenant_categories` (
  `id_tenant_categories` int NOT NULL COMMENT 'ID unik kategori tenant',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nama kategori. Contoh: F&B, Retail, Entertainment'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan daftar kategori usaha tenant';

--
-- Dumping data for table `01_tenant_categories`
--

INSERT INTO `01_tenant_categories` (`id_tenant_categories`, `name`) VALUES
(1, 'F&B'),
(2, 'Retail'),
(3, 'Entertainment'),
(4, 'Service'),
(5, 'Health & Beauty'),
(6, 'Education'),
(7, 'Gaming');

-- --------------------------------------------------------

--
-- Table structure for table `01_units`
--

CREATE TABLE `01_units` (
  `id_units` int NOT NULL COMMENT 'ID unik unit/kios',
  `floor_id` int NOT NULL COMMENT 'ID lantai tempat unit berada (FK ke 01_floors.id_floors)',
  `unit_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Kode unit. Contoh: LG-01',
  `area_size` decimal(10,2) DEFAULT NULL COMMENT 'Luas unit dalam meter persegi',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'available' COMMENT 'Status unit: available, occupied, maintenance, renovation, closed',
  `tenant_id` int DEFAULT NULL COMMENT 'ID tenant yang menempati (FK ke 02_tenants.id_tenants)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu data dibuat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan data unit/kios per lantai';

--
-- Dumping data for table `01_units`
--

INSERT INTO `01_units` (`id_units`, `floor_id`, `unit_code`, `area_size`, `status`, `tenant_id`, `created_at`) VALUES
(1, 1, 'LG-01', 50.00, 'occupied', 11, '2026-06-20 08:41:02'),
(2, 1, 'LG-02', 45.00, 'occupied', 4, '2026-06-20 08:41:02'),
(3, 1, 'LG-03', 30.00, 'available', 5, '2026-06-20 08:41:02'),
(4, 2, 'LT1-01', 60.00, 'occupied', 12, '2026-06-20 08:41:02'),
(5, 2, 'LT1-02', 55.00, 'occupied', 6, '2026-06-20 08:41:02'),
(6, 3, 'LT2-01', 40.00, 'available', 7, '2026-06-20 08:41:02'),
(7, 3, 'LT2-02', 35.00, 'maintenance', 8, '2026-06-20 08:41:02'),
(8, 4, 'LT1-03', 70.00, 'occupied', 13, '2026-06-20 08:41:02'),
(9, 5, 'LT2-03', 25.00, 'available', 9, '2026-06-20 08:41:02'),
(10, 6, 'LT1-04', 80.00, 'occupied', 10, '2026-06-20 08:41:02'),
(11, 7, 'KG-LG-01', 45.00, 'available', NULL, '2026-06-20 11:50:29'),
(12, 7, 'KG-LG-02', 55.00, 'available', NULL, '2026-06-20 11:50:29'),
(13, 8, 'KG-LT1-01', 60.00, 'occupied', 14, '2026-06-20 11:50:29'),
(14, 8, 'KG-LT1-02', 50.00, 'occupied', 15, '2026-06-20 11:50:29'),
(15, 9, 'KG-LT2-01', 40.00, 'available', NULL, '2026-06-20 11:50:29'),
(16, 9, 'KG-LT2-02', 35.00, 'available', NULL, '2026-06-20 11:50:29'),
(17, 10, 'KG-BLT1-01', 70.00, 'occupied', 16, '2026-06-20 11:50:29'),
(18, 11, 'KG-BLT2-01', 30.00, 'occupied', 17, '2026-06-20 11:50:29'),
(19, 12, 'PIK-LG-01', 80.00, 'available', NULL, '2026-06-20 11:50:29'),
(20, 12, 'PIK-LG-02', 65.00, 'available', NULL, '2026-06-20 11:50:29'),
(21, 13, 'PIK-LT1-01', 90.00, 'available', NULL, '2026-06-20 11:50:29'),
(22, 14, 'PIK-LT1-02', 75.00, 'available', NULL, '2026-06-20 11:50:29');

-- --------------------------------------------------------

--
-- Table structure for table `01_unit_types`
--

CREATE TABLE `01_unit_types` (
  `id_unit_types` int NOT NULL COMMENT 'ID unik tipe unit',
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Nama tipe unit. Contoh: Kios, Stand, Food Court'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan daftar tipe unit untuk klasifikasi';

--
-- Dumping data for table `01_unit_types`
--

INSERT INTO `01_unit_types` (`id_unit_types`, `name`) VALUES
(1, 'Kios'),
(2, 'Stand'),
(3, 'Food Court'),
(4, 'Department Store'),
(5, 'Pop-Up Store'),
(6, 'Food Stall'),
(7, 'Co-working Space');

-- --------------------------------------------------------

--
-- Table structure for table `02_contracts`
--

CREATE TABLE `02_contracts` (
  `id_contract` int NOT NULL,
  `contract_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_tenant` int NOT NULL,
  `id_unit` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `contract_status` enum('Draft','Waiting Approval','Active','Amended','Expired','Terminated') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Draft',
  `legal_document_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `02_contracts`
--

INSERT INTO `02_contracts` (`id_contract`, `contract_number`, `id_tenant`, `id_unit`, `start_date`, `end_date`, `contract_status`, `legal_document_url`) VALUES
(1, 'CONT-2026-001', 1, 1, '2026-01-15', '2028-01-14', 'Active', '/documents/contract_kfc.pdf'),
(2, 'CONT-2026-002', 2, 4, '2026-02-10', '2028-02-09', 'Active', '/documents/contract_hm.pdf'),
(3, 'CONT-2026-003', 3, 8, '2026-04-01', '2028-03-31', 'Draft', NULL),
(4, 'CONT-2026-004', 4, 2, '2026-01-20', '2028-01-19', 'Active', '/documents/contract_indomaret.pdf'),
(5, 'CONT-2026-005', 5, 3, '2026-02-15', '2028-02-14', 'Active', '/documents/contract_alfamart.pdf'),
(6, 'CONT-2026-006', 6, 5, '2026-03-01', '2028-02-29', 'Active', '/documents/contract_ace.pdf'),
(7, 'CONT-2026-007', 7, 6, '2026-03-15', '2028-03-14', 'Active', '/documents/contract_guardian.pdf'),
(8, 'CONT-2026-008', 8, 7, '2026-04-01', '2028-03-31', 'Active', '/documents/contract_sogo.pdf'),
(9, 'CONT-2026-009', 9, 9, '2026-04-20', '2028-04-19', 'Draft', NULL),
(10, 'CONT-2026-010', 10, 10, '2026-05-01', '2028-04-30', 'Active', '/documents/contract_matahari.pdf'),
(11, 'CONT-2026-011', 11, 1, '2026-05-15', '2028-05-14', 'Draft', NULL),
(12, 'CONT-2026-012', 12, 4, '2026-06-01', '2028-05-31', 'Active', '/documents/contract_jco.pdf'),
(13, 'CONT-2026-013', 13, 8, '2026-06-10', '2028-06-09', 'Draft', NULL),
(14, 'CONT-2026-014', 14, 13, '2026-06-20', '2028-06-19', 'Draft', NULL),
(15, 'CONT-2026-015', 15, 14, '2026-06-20', '2028-06-19', 'Draft', NULL),
(16, 'CONT-2026-016', 16, 17, '2026-06-20', '2028-06-19', 'Active', '/documents/contract_sushitei.pdf'),
(17, 'CONT-2026-017', 17, 18, '2026-06-20', '2028-06-19', 'Active', '/documents/contract_forecoffee.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `02_contract_cost`
--

CREATE TABLE `02_contract_cost` (
  `id_component` int NOT NULL,
  `id_contract` int NOT NULL,
  `charge_type` enum('Fixed Rent','Revenue Sharing','Service Charge','Utility Charge','Maintenance Fee') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `calculation_basis` enum('Per Sqm','Fixed Monthly','Percentage') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount_or_percentage` decimal(15,2) NOT NULL,
  `billing_cycle` enum('Monthly','Quarterly','Annually') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Monthly'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `02_contract_cost`
--

INSERT INTO `02_contract_cost` (`id_component`, `id_contract`, `charge_type`, `calculation_basis`, `amount_or_percentage`, `billing_cycle`) VALUES
(1, 1, 'Fixed Rent', 'Per Sqm', 150000000.00, 'Monthly'),
(2, 1, 'Service Charge', 'Fixed Monthly', 15000000.00, 'Monthly'),
(3, 2, 'Fixed Rent', 'Per Sqm', 180000000.00, 'Monthly'),
(4, 2, 'Service Charge', 'Fixed Monthly', 18000000.00, 'Monthly'),
(5, 3, 'Fixed Rent', 'Per Sqm', 200000000.00, 'Monthly'),
(6, 4, 'Fixed Rent', 'Per Sqm', 100000000.00, 'Monthly'),
(7, 4, 'Service Charge', 'Fixed Monthly', 10000000.00, 'Monthly'),
(8, 5, 'Fixed Rent', 'Per Sqm', 80000000.00, 'Monthly'),
(9, 5, 'Service Charge', 'Fixed Monthly', 8000000.00, 'Monthly'),
(10, 6, 'Fixed Rent', 'Per Sqm', 120000000.00, 'Monthly'),
(11, 6, 'Service Charge', 'Fixed Monthly', 12000000.00, 'Monthly'),
(12, 7, 'Fixed Rent', 'Per Sqm', 90000000.00, 'Monthly'),
(13, 7, 'Service Charge', 'Fixed Monthly', 9000000.00, 'Monthly'),
(14, 8, 'Fixed Rent', 'Per Sqm', 150000000.00, 'Monthly'),
(15, 8, 'Service Charge', 'Fixed Monthly', 15000000.00, 'Monthly'),
(16, 10, 'Fixed Rent', 'Per Sqm', 180000000.00, 'Monthly'),
(17, 10, 'Service Charge', 'Fixed Monthly', 18000000.00, 'Monthly'),
(18, 12, 'Fixed Rent', 'Per Sqm', 110000000.00, 'Monthly'),
(19, 12, 'Service Charge', 'Fixed Monthly', 11000000.00, 'Monthly'),
(20, 14, 'Fixed Rent', 'Per Sqm', 140000000.00, 'Monthly'),
(21, 14, 'Service Charge', 'Fixed Monthly', 14000000.00, 'Monthly'),
(22, 15, 'Fixed Rent', 'Per Sqm', 120000000.00, 'Monthly'),
(23, 15, 'Service Charge', 'Fixed Monthly', 12000000.00, 'Monthly'),
(24, 16, 'Fixed Rent', 'Per Sqm', 160000000.00, 'Monthly'),
(25, 16, 'Service Charge', 'Fixed Monthly', 16000000.00, 'Monthly'),
(26, 17, 'Fixed Rent', 'Per Sqm', 70000000.00, 'Monthly'),
(27, 17, 'Service Charge', 'Fixed Monthly', 7000000.00, 'Monthly');

-- --------------------------------------------------------

--
-- Table structure for table `02_tenants`
--

CREATE TABLE `02_tenants` (
  `id_tenant` int NOT NULL,
  `id_prospect` int NOT NULL,
  `tenant_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `brand_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_category` int NOT NULL,
  `npwp_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Active','Non-Active','Terminated') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `02_tenants`
--

INSERT INTO `02_tenants` (`id_tenant`, `id_prospect`, `tenant_name`, `brand_name`, `id_category`, `npwp_number`, `status`) VALUES
(1, 1, 'PT KFC Indonesia', 'KFC', 1, '01.234.567.8-901.000', 'Active'),
(2, 2, 'PT H&M Indonesia', 'H&M', 2, '01.234.567.8-902.000', 'Active'),
(3, 4, 'PT Cinema XXI', 'Cinema XXI', 3, '01.234.567.8-904.000', 'Active'),
(4, 6, 'PT Indomaret', 'Indomaret', 2, '01.234.567.8-906.000', 'Active'),
(5, 7, 'PT Sumber Alfaria Trijaya', 'Alfamart', 2, '01.234.567.8-907.000', 'Active'),
(6, 8, 'PT Ace Hardware Indonesia', 'Ace Hardware', 2, '01.234.567.8-908.000', 'Active'),
(7, 9, 'PT Guardian Indonesia', 'Guardian', 2, '01.234.567.8-909.000', 'Active'),
(8, 10, 'PT Sogo Indonesia', 'Sogo', 2, '01.234.567.8-910.000', 'Active'),
(9, 11, 'PT Gramedia Asri Media', 'Gramedia', 2, '01.234.567.8-911.000', 'Active'),
(10, 12, 'PT Matahari Department Store', 'Matahari', 2, '01.234.567.8-912.000', 'Active'),
(11, 13, 'PT Pizza Hut Indonesia', 'Pizza Hut', 1, '01.234.567.8-913.000', 'Active'),
(12, 14, 'PT J.CO Indonesia', 'J.CO Donuts', 1, '01.234.567.8-914.000', 'Active'),
(13, 15, 'PT Erha Clinic', 'Erha Clinic', 4, '01.234.567.8-915.000', 'Active'),
(14, 16, 'PT Samsung Electronics Indonesia', 'Samsung', 2, '01.234.567.8-916.000', 'Active'),
(15, 17, 'PT Zara Indonesia', 'Zara', 2, '01.234.567.8-917.000', 'Active'),
(16, 18, 'PT Sushi Tei Indonesia', 'Sushi Tei', 1, '01.234.567.8-918.000', 'Active'),
(17, 19, 'PT Fore Kopi Indonesia', 'Fore Coffee', 1, '01.234.567.8-919.000', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `02_tenant_complaints`
--

CREATE TABLE `02_tenant_complaints` (
  `id_complaint` int NOT NULL,
  `id_tenant` int NOT NULL,
  `id_unit` int NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `severity_level` enum('Low','Medium','High') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Low',
  `status` enum('Open','In Progress','Resolved','Closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `02_tenant_complaints`
--

INSERT INTO `02_tenant_complaints` (`id_complaint`, `id_tenant`, `id_unit`, `title`, `description`, `severity_level`, `status`, `created_at`) VALUES
(1, 1, 1, 'AC KFC Tidak Dingin', 'AC di unit KFC tidak berfungsi dengan baik', 'High', 'Open', '2026-06-20 08:41:16'),
(2, 2, 4, 'Lampu H&M Rusak', 'Beberapa lampu di toko H&M mati', 'Medium', 'In Progress', '2026-06-20 08:41:16'),
(3, 14, 13, 'Samsung - Masalah Koneksi Internet', 'Internet di unit Samsung sering putus', 'Medium', 'Open', '2026-06-20 11:50:29'),
(4, 16, 17, 'Sushi Tei - Kebocoran Pipa', 'Ada kebocoran pipa di dapur Sushi Tei', 'High', 'In Progress', '2026-06-20 11:50:29'),
(5, 17, 18, 'Fore Coffee - Lampu Taman', 'Lampu di area outdoor fore coffee mati', 'Low', 'Open', '2026-06-20 11:50:29');

-- --------------------------------------------------------

--
-- Table structure for table `02_tenant_deposits`
--

CREATE TABLE `02_tenant_deposits` (
  `id_deposit` int NOT NULL,
  `id_contract` int NOT NULL,
  `deposit_type` enum('Security Deposit','Utility Deposit') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_status` enum('Unpaid','Paid','Refunded','Forfeited') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Unpaid',
  `payment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `02_tenant_deposits`
--

INSERT INTO `02_tenant_deposits` (`id_deposit`, `id_contract`, `deposit_type`, `amount`, `payment_status`, `payment_date`) VALUES
(1, 1, 'Security Deposit', 50000000.00, 'Paid', '2026-01-15'),
(2, 2, 'Security Deposit', 60000000.00, 'Paid', '2026-02-10'),
(3, 3, 'Security Deposit', 70000000.00, 'Unpaid', NULL),
(4, 14, 'Security Deposit', 60000000.00, 'Unpaid', NULL),
(5, 15, 'Security Deposit', 50000000.00, 'Unpaid', NULL),
(6, 16, 'Security Deposit', 70000000.00, 'Paid', '2026-06-20'),
(7, 17, 'Security Deposit', 30000000.00, 'Paid', '2026-06-20');

-- --------------------------------------------------------

--
-- Table structure for table `02_tenant_prospects`
--

CREATE TABLE `02_tenant_prospects` (
  `id_prospect` int NOT NULL,
  `brand_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_category` int NOT NULL,
  `pic_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `interested_unit` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('Prospect','Verified','Rejected','Converted') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Prospect',
  `register_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `02_tenant_prospects`
--

INSERT INTO `02_tenant_prospects` (`id_prospect`, `brand_name`, `id_category`, `pic_name`, `phone`, `email`, `interested_unit`, `notes`, `status`, `register_date`) VALUES
(1, 'KFC', 1, 'Budi Santoso', '081234567890', 'budi@kfc.com', 1, 'Prospek menarik untuk lantai LG', 'Verified', '2026-01-15'),
(2, 'H&M', 2, 'Siti Rahayu', '081234567891', 'siti@hm.com', 4, 'Tertarik unit lantai 1', 'Verified', '2026-02-10'),
(3, 'Starbucks', 1, 'Ahmad Fauzi', '081234567892', 'ahmad@starbucks.com', 3, 'Prospek untuk food court', 'Prospect', '2026-03-05'),
(4, 'Cinema XXI', 3, 'Dewi Lestari', '081234567893', 'dewi@xxi.com', 8, 'Membutuhkan luas > 70m2', 'Verified', '2026-04-01'),
(5, 'Uniqlo', 2, 'Rizky Pratama', '081234567894', 'rizky@uniqlo.com', 10, 'Tertarik untuk lantai 1', 'Prospect', '2026-05-20'),
(6, 'Indomaret', 2, 'Agus Salim', '081234567895', 'agus@indomaret.com', 2, 'Minimarket modern', 'Verified', '2026-01-20'),
(7, 'Alfamart', 2, 'Bambang Susanto', '081234567896', 'bambang@alfamart.com', 3, 'Minimarket modern', 'Prospect', '2026-02-15'),
(8, 'Ace Hardware', 2, 'Cahya Wardhana', '081234567897', 'cahya@ace.com', 5, 'Perlengkapan rumah tangga', 'Verified', '2026-03-01'),
(9, 'Guardian', 2, 'Dewi Sartika', '081234567898', 'dewi@guardian.com', 6, 'Farmasi & kesehatan', 'Verified', '2026-03-15'),
(10, 'Sogo', 2, 'Eko Prasetyo', '081234567899', 'eko@sogo.com', 7, 'Departement store', 'Verified', '2026-04-01'),
(11, 'Gramedia', 2, 'Fitri Handayani', '081234567800', 'fitri@gramedia.com', 9, 'Buku & stationery', 'Prospect', '2026-04-20'),
(12, 'Matahari', 2, 'Gilang Ramadhan', '081234567801', 'gilang@matahari.com', 10, 'Departement store', 'Verified', '2026-05-01'),
(13, 'Pizza Hut', 1, 'Hendra Wijaya', '081234567802', 'hendra@pizzahut.com', 1, 'Restoran pizza', 'Prospect', '2026-05-15'),
(14, 'J.CO Donuts', 1, 'Indah Permata', '081234567803', 'indah@jco.com', 4, 'Donuts & coffee', 'Verified', '2026-06-01'),
(15, 'Erha Clinic', 4, 'Joko Prasetyo', '081234567804', 'joko@erha.com', 8, 'Klinik kecantikan', 'Prospect', '2026-06-10'),
(16, 'Samsung Experience Store', 2, 'Andi Wijaya', '081234567805', 'andi@samsung.com', NULL, 'Tertarik untuk buka flagship store', 'Prospect', '2026-06-20'),
(17, 'Zara', 2, 'Maria Tan', '081234567806', 'maria@zara.com', NULL, 'Mencari lokasi strategis', 'Prospect', '2026-06-20'),
(18, 'Sushi Tei', 1, 'Kevin Hartanto', '081234567807', 'kevin@sushitei.com', NULL, 'Restoran Jepang dengan konsep baru', 'Prospect', '2026-06-20'),
(19, 'Fore Coffee', 1, 'Rina Sari', '081234567808', 'rina@forecoffee.com', NULL, 'Coffee shop dengan konsep modern', 'Verified', '2026-06-20'),
(20, 'Gym & Fitness Center', 7, 'Arief Budiman', '081234567809', 'arief@gym.com', NULL, 'Membutuhkan luas minimal 100m2', 'Prospect', '2026-06-20');

-- --------------------------------------------------------

--
-- Table structure for table `02_tenant_renovations`
--

CREATE TABLE `02_tenant_renovations` (
  `id_renovation` int NOT NULL,
  `id_contract` int NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `proposed_start_date` date NOT NULL,
  `proposed_end_date` date NOT NULL,
  `attachment_plan_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Pending','In Review','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `02_tenant_renovations`
--

INSERT INTO `02_tenant_renovations` (`id_renovation`, `id_contract`, `description`, `proposed_start_date`, `proposed_end_date`, `attachment_plan_url`, `status`) VALUES
(1, 1, 'Renovasi interior KFC', '2026-07-01', '2026-07-15', '/documents/renovasi_kfc.pdf', 'Pending'),
(2, 2, 'Penambahan display H&M', '2026-08-01', '2026-08-10', '/documents/renovasi_hm.pdf', 'Approved'),
(3, 14, 'Instalasi display Samsung Experience Store', '2026-07-01', '2026-07-20', '/documents/renovasi_samsung.pdf', 'Pending'),
(4, 15, 'Renovasi interior Zara', '2026-07-15', '2026-08-05', '/documents/renovasi_zara.pdf', 'In Review');

-- --------------------------------------------------------

--
-- Table structure for table `03_assets`
--

CREATE TABLE `03_assets` (
  `asset_id` int NOT NULL,
  `asset_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'ID Unik / QR Code',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `purchase_value` decimal(15,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `useful_life` int NOT NULL COMMENT 'umur ekonomis (tahun)',
  `depreciation_policy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `current_location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','maintenance','retired','lost') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  `is_vital` tinyint(1) DEFAULT '0' COMMENT 'aset vital (AC, lift, genset, CCTV)',
  `last_mutation_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `mutation_id` int NOT NULL,
  `asset_id` int NOT NULL,
  `old_location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `new_location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mutation_date` datetime NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `recorded_by` int DEFAULT NULL COMMENT 'user_id'
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
  `id` int NOT NULL,
  `schedule_id` int DEFAULT NULL,
  `kondisi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
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
  `report_id` int NOT NULL,
  `ticket_id` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Open','Assigned','In Progress','Resolved','Closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Open',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `asset_id` int DEFAULT NULL
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
  `id` int NOT NULL,
  `asset_id` int DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `frekuensi` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'daily, weekly, monthly, quarterly, annual',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
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
  `technician_id` int NOT NULL,
  `NIK` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Available','On-Duty','Offline') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Available',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL
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
  `skill_id` int NOT NULL,
  `technician_id` int NOT NULL,
  `skill_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `proficiency_level` int DEFAULT '1' COMMENT '1-100'
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
  `work_order_id` int NOT NULL,
  `work_order_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `report_id` int NOT NULL,
  `technician_id` int NOT NULL,
  `required_skill` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `priority` enum('Critical','High','Medium','Low') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sla_target` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `work_status` enum('Assigned','In Progress','Completed','Cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Assigned',
  `assigned_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
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
  `activity_id` int NOT NULL,
  `work_order_id` int NOT NULL,
  `activity_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activity_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `employee_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `attachment_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
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
  `id` int NOT NULL,
  `id_booking` int NOT NULL COMMENT 'FK ke 04_event_booking.id_booking',
  `jml_pengunjung` int DEFAULT NULL,
  `target_pengunjung` int DEFAULT NULL,
  `traffic_before` int DEFAULT NULL,
  `traffic_during` int DEFAULT NULL,
  `traffic_after` int DEFAULT NULL,
  `rating_kepuasan` decimal(2,1) DEFAULT NULL,
  `rating_vendor` decimal(2,1) DEFAULT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Data analitik traffic & kepuasan pasca-event (PBI-M04-03-04)';

-- --------------------------------------------------------

--
-- Table structure for table `04_event_areas`
--

CREATE TABLE `04_event_areas` (
  `id_area` int NOT NULL,
  `nama_area` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `floor_id` int DEFAULT NULL,
  `kapasitas` int DEFAULT NULL,
  `fasilitas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('aktif','nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `04_event_areas`
--

INSERT INTO `04_event_areas` (`id_area`, `nama_area`, `floor_id`, `kapasitas`, `fasilitas`, `status`, `created_at`) VALUES
(1, 'Main Atrium', 2, 500, 'Sound system, LED screen', 'aktif', '2026-06-21 14:58:12'),
(2, 'East Atrium', 3, 300, 'Sound system', 'aktif', '2026-06-21 14:58:12'),
(3, 'Main Atrium', 2, 500, 'Sound system, LED screen', 'aktif', '2026-06-21 14:59:02'),
(4, 'East Atrium', 3, 300, 'Sound system', 'aktif', '2026-06-21 14:59:02'),
(5, 'Main Atrium', 2, 500, 'Sound system, LED screen', 'aktif', '2026-06-21 14:59:50'),
(6, 'East Atrium', 3, 300, 'Sound system', 'aktif', '2026-06-21 14:59:50'),
(7, 'Main Atrium', 2, 500, 'Sound system, LED screen', 'aktif', '2026-06-21 15:00:04'),
(8, 'East Atrium', 3, 300, 'Sound system', 'aktif', '2026-06-21 15:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `04_event_booking`
--

CREATE TABLE `04_event_booking` (
  `id_booking` int NOT NULL COMMENT 'ID unik pengajuan booking event',
  `id_area` int NOT NULL COMMENT 'FK ke 04_event_areas.id_area',
  `id_user` int NOT NULL COMMENT 'FK ke 09_users.id',
  `nama_event` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipe_event` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `estimasi_pengunjung` int DEFAULT NULL COMMENT 'Estimasi pengunjung saat pengajuan',
  `catatan_admin` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Catatan dari admin saat approve/reject/revisi',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending' COMMENT 'pending, approved, rejected, revision'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pengajuan booking area event (PBI-M04-03-01/02)';

-- --------------------------------------------------------

--
-- Table structure for table `04_event_booking_vendor`
--

CREATE TABLE `04_event_booking_vendor` (
  `id` int NOT NULL,
  `id_booking` int NOT NULL COMMENT 'FK ke 04_event_booking.id_booking',
  `nama_vendor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kategori` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kontak` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pivot many-to-many: vendor yang terlibat pada satu event';

-- --------------------------------------------------------

--
-- Table structure for table `04_event_sponsorship`
--

CREATE TABLE `04_event_sponsorship` (
  `id_sponsor` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: SPO-001',
  `id_booking` int NOT NULL COMMENT 'FK ke 04_event_booking.id_booking',
  `sponsor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `paket` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Contoh: Platinum, Gold, Silver',
  `nilai` decimal(15,2) NOT NULL,
  `status_bayar` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'belum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Manajemen sponsor & status settlement per event (PBI-M04-03-03)';

-- --------------------------------------------------------

--
-- Table structure for table `04_event_tiket`
--

CREATE TABLE `04_event_tiket` (
  `id_tiket` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: TKT-001',
  `id_booking` int NOT NULL COMMENT 'FK ke 04_event_booking.id_booking',
  `tipe` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: Gratis, Regular, VIP',
  `kuota` int NOT NULL DEFAULT '0',
  `terjual` int NOT NULL DEFAULT '0',
  `harga` decimal(12,2) DEFAULT '0.00',
  `pendapatan` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Setup tiket digital per event (PBI-M04-03-03)';

-- --------------------------------------------------------

--
-- Table structure for table `04_invoice_utilitas`
--

CREATE TABLE `04_invoice_utilitas` (
  `id_invoice` int NOT NULL COMMENT 'ID unik invoice utilitas',
  `tenant_id` int NOT NULL COMMENT 'FK ke 02_tenants.id_tenant',
  `id_meter` int DEFAULT NULL COMMENT 'FK ke 04_utility_meters.id_meter',
  `billing_period` date DEFAULT NULL COMMENT 'Periode penagihan',
  `total_consumption` decimal(15,2) DEFAULT NULL COMMENT 'Total konsumsi pada periode ini',
  `total` decimal(15,2) NOT NULL COMMENT 'Total tagihan utilitas',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Invoice utilitas per tenant per periode';

-- --------------------------------------------------------

--
-- Table structure for table `04_parking_member`
--

CREATE TABLE `04_parking_member` (
  `id_member` int NOT NULL COMMENT 'ID unik member parkir',
  `tenant_id` int DEFAULT NULL COMMENT 'FK ke 02_tenants.id_tenant, NULL jika member umum',
  `plat_nomor` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipe_kendaraan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `membership_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Contoh: Reguler, Korporat, VIP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Member & subscription parkir, termasuk korporat (PBI-M04-02-03)';

-- --------------------------------------------------------

--
-- Table structure for table `04_parking_tarif`
--

CREATE TABLE `04_parking_tarif` (
  `id_tarif` int NOT NULL,
  `tipe_kendaraan` enum('motor','mobil','truk') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipe_user` enum('umum','member','korporat') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tarif_jam_pertama` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tarif_per_jam` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tarif_harian_max` decimal(10,2) DEFAULT NULL,
  `berlaku_dari` date NOT NULL,
  `berlaku_sampai` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `04_parking_tarif`
--

INSERT INTO `04_parking_tarif` (`id_tarif`, `tipe_kendaraan`, `tipe_user`, `tarif_jam_pertama`, `tarif_per_jam`, `tarif_harian_max`, `berlaku_dari`, `berlaku_sampai`, `created_at`) VALUES
(1, 'mobil', 'umum', 5000.00, 3000.00, 45000.00, '2026-06-01', NULL, '2026-06-21 15:00:04'),
(2, 'motor', 'umum', 2000.00, 1000.00, 15000.00, '2026-06-01', NULL, '2026-06-21 15:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `04_parking_transaksi`
--

CREATE TABLE `04_parking_transaksi` (
  `id_transaksi` int NOT NULL COMMENT 'ID unik transaksi parkir',
  `petugas_id` int DEFAULT NULL COMMENT 'FK ke 09_users.id, petugas yang memproses',
  `plat_nomor` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_member` int DEFAULT NULL COMMENT 'FK ke 04_parking_member.id_member, NULL jika non-member',
  `zona_id` int DEFAULT NULL,
  `tipe_kendaraan` enum('motor','mobil','truk') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `entry_time` datetime NOT NULL,
  `exit_time` datetime DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `payment_method` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parking_slot` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Transaksi entry/exit parkir (PBI-M04-02-01/02)';

--
-- Dumping data for table `04_parking_transaksi`
--

INSERT INTO `04_parking_transaksi` (`id_transaksi`, `petugas_id`, `plat_nomor`, `id_member`, `zona_id`, `tipe_kendaraan`, `entry_time`, `exit_time`, `amount`, `payment_method`, `parking_slot`) VALUES
(1, NULL, 'B 1234 ABC', NULL, 1, 'mobil', '2026-06-20 08:00:00', '2026-06-20 10:00:00', 11000.00, 'cash', 'A-01'),
(2, NULL, 'B 5678 DEF', NULL, 1, 'mobil', '2026-06-20 09:00:00', '2026-06-20 14:00:00', 26000.00, 'cashless', 'A-05'),
(3, NULL, 'B 9101 GHI', NULL, 2, 'motor', '2026-06-20 10:00:00', '2026-06-20 12:00:00', 4000.00, 'cash', 'B-10');

-- --------------------------------------------------------

--
-- Table structure for table `04_parking_zona`
--

CREATE TABLE `04_parking_zona` (
  `id_zona` int NOT NULL COMMENT 'ID unik zona parkir',
  `nama_zona` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: Basement 1, Rooftop',
  `total_slot` int NOT NULL COMMENT 'Total kapasitas slot pada zona ini',
  `occupied_slot` int NOT NULL DEFAULT '0',
  `floor_id` int DEFAULT NULL COMMENT 'FK ke 01_floors.id_floors'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Master zona & kapasitas slot parkir untuk monitoring real-time (PBI-M04-02-04)';

--
-- Dumping data for table `04_parking_zona`
--

INSERT INTO `04_parking_zona` (`id_zona`, `nama_zona`, `total_slot`, `occupied_slot`, `floor_id`) VALUES
(1, 'Basement 1', 150, 45, 1),
(2, 'Basement 2', 200, 78, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `04_utility_meters`
--

CREATE TABLE `04_utility_meters` (
  `id_meter` int NOT NULL COMMENT 'ID unik meter utilitas',
  `unit_id` int NOT NULL COMMENT 'FK ke 01_units.id_units',
  `utility_type` enum('listrik','air','gas','internet','ac_central') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `current_reading` decimal(15,2) DEFAULT NULL COMMENT 'Pembacaan meter terakhir',
  `previous_reading` decimal(15,2) DEFAULT NULL COMMENT 'Pembacaan meter sebelumnya',
  `reading_date` datetime DEFAULT NULL COMMENT 'Waktu pembacaan terakhir',
  `input_method` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'manual atau iot',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  `tarif_per_unit` decimal(12,2) NOT NULL COMMENT 'Tarif per unit konsumsi',
  `threshold_max` decimal(12,2) DEFAULT NULL COMMENT 'Ambang batas deteksi anomali'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Meter utilitas per unit, mendukung pencatatan manual & IoT';

--
-- Dumping data for table `04_utility_meters`
--

INSERT INTO `04_utility_meters` (`id_meter`, `unit_id`, `utility_type`, `current_reading`, `previous_reading`, `reading_date`, `input_method`, `status`, `tarif_per_unit`, `threshold_max`) VALUES
(1, 1, 'listrik', 1500.50, 1400.00, '2026-06-20 15:41:54', 'manual', 'active', 1500.00, 2000.00),
(2, 2, 'air', 500.25, 480.00, '2026-06-20 15:41:54', 'manual', 'active', 5000.00, 1000.00),
(3, 4, 'listrik', 2000.00, 1850.00, '2026-06-20 15:41:54', 'iot', 'active', 1500.00, 2500.00),
(4, 11, 'listrik', 100.00, 50.00, '2026-06-20 18:50:29', 'manual', 'active', 1500.00, 300.00),
(5, 11, 'air', 200.00, 150.00, '2026-06-20 18:50:29', 'manual', 'active', 5000.00, 500.00),
(6, 13, 'listrik', 500.00, 400.00, '2026-06-20 18:50:29', 'iot', 'active', 1500.00, 800.00),
(7, 14, 'listrik', 350.00, 300.00, '2026-06-20 18:50:29', 'iot', 'active', 1500.00, 600.00),
(8, 19, 'listrik', 50.00, 0.00, '2026-06-20 18:50:29', 'manual', 'active', 1500.00, 200.00),
(9, 19, 'gas', 0.00, 0.00, '2026-06-20 18:50:29', 'manual', 'active', 25000.00, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `04_utility_meter_logs`
--

CREATE TABLE `04_utility_meter_logs` (
  `id_log` int NOT NULL COMMENT 'ID unik log pembacaan meter',
  `id_meter` int NOT NULL COMMENT 'FK ke 04_utility_meters.id_meter',
  `reading_value` decimal(15,2) NOT NULL COMMENT 'Nilai pembacaan pada waktu tersebut',
  `reading_date` datetime NOT NULL COMMENT 'Waktu pembacaan',
  `recorded_by` int DEFAULT NULL COMMENT 'FK ke 09_users.id, petugas yang mencatat'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Histori pembacaan meter untuk analitik konsumsi (PBI-M04-01-04)';

-- --------------------------------------------------------

--
-- Table structure for table `05_cs_feedback`
--

CREATE TABLE `05_cs_feedback` (
  `id` int UNSIGNED NOT NULL,
  `nama_pengunjung` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `komentar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kategori` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `05_cs_feedback`
--

INSERT INTO `05_cs_feedback` (`id`, `nama_pengunjung`, `rating`, `komentar`, `kategori`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 5, 'Pelayanan CS sangat memuaskan', 'Pelayanan', '2026-06-20 15:39:36', '2026-06-20 15:39:36'),
(2, 'Jane Smith', 4, 'Mall nya bersih dan nyaman', 'Kebersihan', '2026-06-20 15:39:36', '2026-06-20 15:39:36'),
(3, 'David Brown', 3, 'Parkir agak sempit', 'Fasilitas', '2026-06-20 15:39:36', '2026-06-20 15:39:36');

-- --------------------------------------------------------

--
-- Table structure for table `05_found_items`
--

CREATE TABLE `05_found_items` (
  `id` int NOT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location_found` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('Tersimpan','Dikembalikan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Tersimpan',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `05_found_items`
--

INSERT INTO `05_found_items` (`id`, `photo`, `location_found`, `description`, `status`, `created_at`) VALUES
(1, '/photos/dompet.jpg', 'Food Court', 'Dompet hitam berisi KTP dan uang', 'Tersimpan', '2026-06-20 08:39:36'),
(2, '/photos/hp.jpg', 'Toilet Lantai 1', 'Handphone Samsung', 'Dikembalikan', '2026-06-20 08:39:36');

-- --------------------------------------------------------

--
-- Table structure for table `05_lost_reports`
--

CREATE TABLE `05_lost_reports` (
  `id` int NOT NULL,
  `nama_pelapor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `item_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `contact_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Dicari','Dikembalikan') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Dicari',
  `reported_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `05_lost_reports`
--

INSERT INTO `05_lost_reports` (`id`, `nama_pelapor`, `item_description`, `contact_number`, `status`, `reported_at`) VALUES
(1, 'Maria', 'Jam tangan Rolex', '081234567892', 'Dicari', '2026-06-20 08:39:36'),
(2, 'Rudi', 'Tas kulit coklat', '081234567893', 'Dicari', '2026-06-20 08:39:36');

-- --------------------------------------------------------

--
-- Table structure for table `05_tiket`
--

CREATE TABLE `05_tiket` (
  `id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `report_date` date NOT NULL,
  `pelapor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `no_hp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lokasi` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `floor_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `area_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `asset_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `asset_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kategori` enum('facility','security','cleaning','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `damage_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `priority` enum('Critical','High','Medium','Low') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Medium',
  `severity_level` int DEFAULT '1',
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('open','in_progress','resolved') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'open',
  `dept` enum('Facility','Security','Cleaning','CS') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `foto` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `sla_menit` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `05_tiket`
--

INSERT INTO `05_tiket` (`id`, `report_date`, `pelapor`, `no_hp`, `lokasi`, `floor_name`, `area_name`, `asset_name`, `asset_code`, `kategori`, `damage_type`, `priority`, `severity_level`, `deskripsi`, `status`, `dept`, `foto`, `sla_menit`, `created_at`, `updated_at`) VALUES
('TK-20260608-001', '2026-06-18', 'Unknown', '0', 'Unknown location', NULL, NULL, 'Lift LG 01', 'AST-001', 'facility', 'Unknown damage', 'Medium', 1, 'No description', 'open', 'Facility', NULL, 120, '2026-06-18 15:29:16', '2026-06-18 15:29:16'),
('TK-20260608-003', '2026-06-08', 'Andi', '081234567890', 'Deket mushola', 'LG', 'Area Tengah', 'Lift LG 01', 'AST-001', 'facility', 'Lift mati', 'High', 2, 'Lift di area LG mati total, pengunjung mengeluh', 'open', 'Facility', NULL, 120, '2026-06-20 08:40:26', '2026-06-20 08:40:26'),
('TK-20260609-002', '2026-06-18', 'Unknown', '0', 'Unknown location', NULL, NULL, 'AC Central Lantai 1', 'AST-002', 'facility', 'Unknown damage', 'Medium', 1, 'No description', 'open', 'Facility', NULL, 120, '2026-06-18 15:29:16', '2026-06-18 15:29:16'),
('TK-20260609-004', '2026-06-09', 'Budi', '081234567891', 'Bioskop XXI', '1', 'Deket resto', 'AC Central Lantai 1', 'AST-002', 'facility', 'AC bocor', 'Medium', 1, 'AC di bioskop bocor', 'open', 'Facility', NULL, 120, '2026-06-20 08:40:26', '2026-06-20 08:40:26');

-- --------------------------------------------------------

--
-- Table structure for table `05_tiket_log`
--

CREATE TABLE `05_tiket_log` (
  `id` int NOT NULL,
  `tiket_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status_lama` enum('open','in_progress','resolved') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_baru` enum('open','in_progress','resolved') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `06_ad_contracts`
--

CREATE TABLE `06_ad_contracts` (
  `id` int NOT NULL,
  `advertiser_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tenant_id` int DEFAULT NULL COMMENT 'FK ke M02.tenants jika pengiklan adalah tenant',
  `ad_location` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ad_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Billboard, digital_screen, banner',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_fee` decimal(15,2) NOT NULL,
  `current_period` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Format: YYYY-MM',
  `billing_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'unpaid' COMMENT 'unpaid, paid',
  `last_paid_date` date DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Master data kontrak penempatan iklan sekaligus status tagihan bulanannya';

--
-- Dumping data for table `06_ad_contracts`
--

INSERT INTO `06_ad_contracts` (`id`, `advertiser_name`, `tenant_id`, `ad_location`, `ad_type`, `start_date`, `end_date`, `monthly_fee`, `current_period`, `billing_status`, `last_paid_date`, `status`, `created_at`) VALUES
(1, 'Samsung Electronics', 14, 'Main Atrium', 'digital_screen', '2026-06-01', '2026-12-31', 25000000.00, '2026-06', 'paid', '2026-06-01', 'active', '2026-06-21 15:00:04'),
(2, 'Sushi Tei', 16, 'Food Court', 'banner', '2026-06-01', '2026-12-31', 5000000.00, '2026-06', 'paid', '2026-06-01', 'active', '2026-06-21 15:00:04'),
(3, 'Indosat Ooredoo', NULL, 'Digital Screen', 'digital_screen', '2026-06-01', '2026-08-31', 15000000.00, '2026-06', 'paid', '2026-06-01', 'active', '2026-06-21 15:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `06_approval_log`
--

CREATE TABLE `06_approval_log` (
  `id` int NOT NULL,
  `po_id` int NOT NULL,
  `aksi` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'approved, rejected',
  `komentar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `approver` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tgl_aksi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approval_request_id_fk` int DEFAULT NULL COMMENT 'FK ke M08.approval_requests'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Riwayat keputusan approval PO oleh manager';

-- --------------------------------------------------------

--
-- Table structure for table `06_chart_of_accounts`
--

CREATE TABLE `06_chart_of_accounts` (
  `id` int NOT NULL,
  `account_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: 4-1001',
  `account_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: Pendapatan Sewa',
  `account_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'asset, liability, equity, revenue, expense',
  `depreciation_method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Isinya khusus akun asset: straight_line atau double_declining',
  `useful_life_months` int DEFAULT NULL COMMENT 'Umur ekonomis dalam bulan (Contoh: 48, 60)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Daftar akun akuntansi terpusat + metode depresiasi aset';

--
-- Dumping data for table `06_chart_of_accounts`
--

INSERT INTO `06_chart_of_accounts` (`id`, `account_code`, `account_name`, `account_type`, `depreciation_method`, `useful_life_months`) VALUES
(1, '1-1001', 'Kas', 'asset', NULL, NULL),
(2, '1-1002', 'Bank BCA', 'asset', NULL, NULL),
(3, '1-2001', 'Piutang Sewa', 'asset', NULL, NULL),
(4, '2-1001', 'Utang Usaha', 'liability', NULL, NULL),
(5, '4-1001', 'Pendapatan Sewa', 'revenue', NULL, NULL),
(6, '4-1002', 'Pendapatan Parkir', 'revenue', NULL, NULL),
(7, '4-1003', 'Pendapatan Event', 'revenue', NULL, NULL),
(8, '5-1001', 'Biaya Operasional', 'expense', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `06_daily_parking_summary`
--

CREATE TABLE `06_daily_parking_summary` (
  `id` int NOT NULL,
  `summary_date` date NOT NULL,
  `total_transactions` int DEFAULT '0',
  `total_revenue` decimal(15,2) DEFAULT '0.00',
  `cash_revenue` decimal(15,2) DEFAULT '0.00',
  `cashless_revenue` decimal(15,2) DEFAULT '0.00',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Rekapitulasi harian pos pendapatan parkir mall dari M04';

--
-- Dumping data for table `06_daily_parking_summary`
--

INSERT INTO `06_daily_parking_summary` (`id`, `summary_date`, `total_transactions`, `total_revenue`, `cash_revenue`, `cashless_revenue`, `status`, `created_at`) VALUES
(1, '2026-06-20', 3, 41000.00, 15000.00, 26000.00, 'completed', '2026-06-21 15:00:04');

-- --------------------------------------------------------

--
-- Table structure for table `06_event_revenue`
--

CREATE TABLE `06_event_revenue` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL COMMENT 'ID booking event dari M04',
  `revenue_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Venue_rental, sponsorship, ticketing',
  `amount` decimal(15,2) NOT NULL,
  `received_date` date DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pencatatan kas masuk hasil sewa area event dari M04';

-- --------------------------------------------------------

--
-- Table structure for table `06_invoices`
--

CREATE TABLE `06_invoices` (
  `id` int NOT NULL,
  `invoice_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Contoh: INV-2026-06-001',
  `contract_id` int NOT NULL COMMENT 'FK ke M02.contracts',
  `tenant_id` int NOT NULL COMMENT 'FK ke M02.tenants',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `ppn_rate` decimal(5,2) DEFAULT '11.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL COMMENT 'Total tagihan bruto yang harus dibayar',
  `due_date` date NOT NULL COMMENT 'Jatuh tempo pembayaran',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Belum Bayar' COMMENT 'Status: Belum Bayar, Lunas, Overdue, Cancelled',
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Cash, Bank Transfer, E-Money',
  `amount_paid` decimal(15,2) DEFAULT '0.00' COMMENT 'Nominal uang yang dibayarkan oleh tenant',
  `payment_proof` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Path file bukti transfer',
  `days_overdue` int DEFAULT '0' COMMENT 'Jumlah hari keterlambatan sejak due_date',
  `aging_bucket` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'current' COMMENT 'Kategori: current, 1-30, 31-60, >90 hari',
  `created_by` int DEFAULT NULL COMMENT 'FK ke M09.users',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabel induk tagihan sewa tenant (Sewa + Pembayaran + Piutang)';

--
-- Dumping data for table `06_invoices`
--

INSERT INTO `06_invoices` (`id`, `invoice_number`, `contract_id`, `tenant_id`, `period_start`, `period_end`, `subtotal`, `ppn_rate`, `tax_amount`, `total_amount`, `due_date`, `status`, `payment_date`, `payment_method`, `amount_paid`, `payment_proof`, `days_overdue`, `aging_bucket`, `created_by`, `created_at`) VALUES
(1, 'INV-2026-06-001', 1, 1, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 166500000.00, '2026-07-10', 'Lunas', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:06:54'),
(2, 'INV-2026-06-002', 2, 2, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 199800000.00, '2026-07-10', 'Belum Bayar', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:06:54'),
(3, 'INV-2026-06-003', 3, 3, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 222000000.00, '2026-07-10', 'Lunas', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:06:54'),
(4, 'INV-2026-06-004', 4, 4, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 110000000.00, '2026-07-10', 'Lunas', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:15:07'),
(5, 'INV-2026-06-005', 5, 5, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 88000000.00, '2026-07-10', 'Belum Bayar', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:15:07'),
(6, 'INV-2026-06-006', 6, 6, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 132000000.00, '2026-07-10', 'Lunas', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:15:07'),
(7, 'INV-2026-06-007', 7, 7, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 99000000.00, '2026-07-10', 'Belum Bayar', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:15:07'),
(8, 'INV-2026-06-008', 8, 8, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 165000000.00, '2026-07-10', 'Lunas', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:15:07'),
(9, 'INV-2026-06-009', 10, 10, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 198000000.00, '2026-07-10', 'Lunas', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:15:07'),
(10, 'INV-2026-06-010', 12, 12, '2026-06-01', '2026-06-30', 0.00, 11.00, 0.00, 121000000.00, '2026-07-10', 'Belum Bayar', NULL, NULL, 0.00, NULL, 0, 'current', NULL, '2026-06-20 09:15:07');

-- --------------------------------------------------------

--
-- Table structure for table `06_invoice_items`
--

CREATE TABLE `06_invoice_items` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `charge_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'rent, service_charge, utility, maintenance',
  `amount` decimal(15,2) NOT NULL COMMENT 'Nominal per jenis tagihan',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Detail rincian biaya komponen di dalam lembar invoice';

-- --------------------------------------------------------

--
-- Table structure for table `06_journal_entries`
--

CREATE TABLE `06_journal_entries` (
  `id` int NOT NULL,
  `journal_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `journal_date` date NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `source_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'invoice_payment, vendor_payment, parking, event, ad, manual',
  `source_id` int DEFAULT NULL,
  `total_debit` decimal(15,2) DEFAULT '0.00',
  `total_credit` decimal(15,2) DEFAULT '0.00',
  `is_balanced` tinyint(1) GENERATED ALWAYS AS (if((`total_debit` = `total_credit`),1,0)) STORED,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'draft' COMMENT 'draft, posted, reversed',
  `is_reconciled` tinyint(1) DEFAULT '0' COMMENT '1 jika sudah cocok dengan rekening koran bank',
  `reconciled_at` datetime DEFAULT NULL COMMENT 'Waktu eksekusi rekonsiliasi kas/bank',
  `posted_by` int DEFAULT NULL COMMENT 'FK ke M09.users',
  `posted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Ringkasan pencatatan akuntansi / double-entry accounting book';

-- --------------------------------------------------------

--
-- Table structure for table `06_journal_lines`
--

CREATE TABLE `06_journal_lines` (
  `id` int NOT NULL,
  `journal_entry_id` int NOT NULL,
  `account_id` int NOT NULL,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Detail mutasi debet dan kredit akun pembentuk Buku Besar';

-- --------------------------------------------------------

--
-- Table structure for table `06_mall_budgets`
--

CREATE TABLE `06_mall_budgets` (
  `id` int NOT NULL,
  `account_id` int NOT NULL COMMENT 'FK ke chart_of_accounts untuk menentukan pos biayanya',
  `budget_year` int NOT NULL COMMENT 'Contoh: 2026',
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Plafon total anggaran yang disetujui',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Penyimpanan data batas anggaran tahunan untuk analisis deviasi realisasi biaya operasional';

-- --------------------------------------------------------

--
-- Table structure for table `06_purchase_orders`
--

CREATE TABLE `06_purchase_orders` (
  `id` int NOT NULL,
  `po_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pr_id` int DEFAULT NULL,
  `vendor_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `order_date` date NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Surat pemesanan/order resmi sistem M06 ke pihak ketiga/vendor';

-- --------------------------------------------------------

--
-- Table structure for table `06_purchase_requests`
--

CREATE TABLE `06_purchase_requests` (
  `id` int NOT NULL,
  `pr_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `requested_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Nama pegawai pengaju dari M07',
  `requested_at` datetime NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `estimated_amount` decimal(15,2) DEFAULT '0.00',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'draft' COMMENT 'draft, pending_approval, approved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Form awal pengajuan internal belanja logistik operasional mall';

-- --------------------------------------------------------

--
-- Table structure for table `06_vendor_bill_receipts`
--

CREATE TABLE `06_vendor_bill_receipts` (
  `id` int NOT NULL,
  `po_id` int NOT NULL,
  `gr_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `received_date` date NOT NULL,
  `received_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ticket_ref` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'No tiket penyelesaian maintenance dari M03',
  `vendor_invoice_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `invoice_amount` decimal(15,2) NOT NULL,
  `is_matched` tinyint(1) DEFAULT '0' COMMENT '1 jika PO Amount = Invoice Amount dan Fisik OK',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending_match'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Peleburan tabel fisik gudang & tagihan vendor demi kebutuhan audit 3-Way Matching';

-- --------------------------------------------------------

--
-- Table structure for table `07_absensi`
--

CREATE TABLE `07_absensi` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `status` enum('hadir','izin','sakit','alpha') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'hadir',
  `foto_masuk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lokasi_masuk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `07_absensi`
--

INSERT INTO `07_absensi` (`id`, `pegawai_id`, `tanggal`, `jam_masuk`, `jam_keluar`, `status`, `foto_masuk`, `lokasi_masuk`, `keterangan`, `created_at`) VALUES
(1, 1, '2026-06-18', '07:00:00', '15:00:00', 'hadir', 'foto_1.jpg', 'Mall Indonesia', NULL, '2026-06-20 08:38:09'),
(2, 2, '2026-06-18', '07:00:00', '15:00:00', 'hadir', 'foto_2.jpg', 'Mall Indonesia', NULL, '2026-06-20 08:38:09'),
(3, 3, '2026-06-18', '07:00:00', '15:00:00', 'izin', NULL, NULL, 'Izin ke dokter', '2026-06-20 08:38:09');

-- --------------------------------------------------------

--
-- Table structure for table `07_cuti`
--

CREATE TABLE `07_cuti` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `jenis_cuti` enum('tahunan','sakit','melahirkan','darurat') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `alasan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','disetujui','ditolak') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `07_cuti`
--

INSERT INTO `07_cuti` (`id`, `pegawai_id`, `tgl_mulai`, `tgl_selesai`, `jenis_cuti`, `alasan`, `status`, `approved_by`, `created_at`) VALUES
(1, 1, '2026-07-01', '2026-07-05', 'tahunan', 'Liburan keluarga', 'disetujui', 1, '2026-06-20 08:38:09'),
(2, 2, '2026-08-10', '2026-08-12', 'sakit', 'Sakit', 'pending', NULL, '2026-06-20 08:38:09');

-- --------------------------------------------------------

--
-- Table structure for table `07_jadwal_shift`
--

CREATE TABLE `07_jadwal_shift` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `shift_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `07_kpi`
--

CREATE TABLE `07_kpi` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `periode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `target_kerja` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `realisasi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `nilai` int DEFAULT '0',
  `kategori` enum('sangat_baik','baik','cukup','kurang') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'cukup',
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `07_kpi`
--

INSERT INTO `07_kpi` (`id`, `pegawai_id`, `periode`, `target_kerja`, `realisasi`, `nilai`, `kategori`, `catatan`, `created_at`) VALUES
(1, 1, '2026-06', 'Menyelesaikan 10 rekrutmen', '12 rekrutmen', 90, 'sangat_baik', 'Melebihi target', '2026-06-20 08:38:09'),
(2, 2, '2026-06', 'Menangani 50 keluhan', '45 keluhan', 80, 'baik', 'Mendekati target', '2026-06-20 08:38:09');

-- --------------------------------------------------------

--
-- Table structure for table `07_payroll`
--

CREATE TABLE `07_payroll` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `bulan` int NOT NULL,
  `tahun` int NOT NULL,
  `gaji_pokok` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tunjangan` decimal(12,2) DEFAULT '0.00',
  `potongan` decimal(12,2) DEFAULT '0.00',
  `total` decimal(12,2) GENERATED ALWAYS AS (((`gaji_pokok` + `tunjangan`) - `potongan`)) STORED,
  `status` enum('draft','final') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `07_payroll`
--

INSERT INTO `07_payroll` (`id`, `pegawai_id`, `bulan`, `tahun`, `gaji_pokok`, `tunjangan`, `potongan`, `status`, `created_at`) VALUES
(1, 1, 6, 2026, 10000000.00, 2000000.00, 500000.00, 'final', '2026-06-20 08:38:09'),
(2, 2, 6, 2026, 8000000.00, 1500000.00, 300000.00, 'final', '2026-06-20 08:38:09');

-- --------------------------------------------------------

--
-- Table structure for table `07_pegawai`
--

CREATE TABLE `07_pegawai` (
  `id` int NOT NULL,
  `nik` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jenis_kelamin` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agama` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pendidikan_terakhir` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabatan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `departemen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `no_hp` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tgl_lahir` date DEFAULT NULL,
  `tgl_masuk` date NOT NULL,
  `status` enum('aktif','nonaktif') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'aktif',
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `spesialisasi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sertifikasi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_bank` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_rekening` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kontak_darurat_nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kontak_darurat_hp` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `id` int NOT NULL,
  `nama_shift` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_keluar` time NOT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
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
  `id` int NOT NULL COMMENT 'ID unik log',
  `request_id` int NOT NULL COMMENT 'ID pengajuan (FK ke tabel approval_requests)',
  `level_number` int NOT NULL COMMENT 'Level approval yang diproses (1,2,3...)',
  `decision` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Keputusan: approved, rejected, delegated',
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Komentar approver',
  `approver_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Nama approver',
  `approver_role` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Jabatan approver. Contoh: Finance Manager',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu keputusan diambil'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan setiap keputusan dalam proses approval. Satu pengajuan bisa punya banyak log';

-- --------------------------------------------------------

--
-- Table structure for table `08_approval_requests`
--

CREATE TABLE `08_approval_requests` (
  `approval_id` int NOT NULL,
  `request_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `request_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'contract, renovation, purchase, event, maintenance',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'draft' COMMENT 'draft, pending, approved, rejected',
  `current_level` int DEFAULT '1',
  `submitted_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Nama pengaju',
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Nama approver',
  `approved_at` datetime DEFAULT NULL,
  `reject_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT 'Alasan ditolak',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan pengajuan approval dari berbagai modul';

-- --------------------------------------------------------

--
-- Table structure for table `08_kpi_snapshots`
--

CREATE TABLE `08_kpi_snapshots` (
  `snapshot_id` int NOT NULL,
  `period_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'daily, weekly, monthly, annual',
  `period_date` date NOT NULL,
  `occupancy_rate` decimal(5,2) DEFAULT NULL COMMENT 'Persentase unit terisi',
  `total_revenue` decimal(15,2) DEFAULT NULL COMMENT 'Total pendapatan dalam rupiah',
  `tenant_revenue` decimal(15,2) DEFAULT NULL,
  `event_revenue` decimal(15,2) DEFAULT NULL,
  `parking_revenue` decimal(15,2) DEFAULT NULL,
  `ads_revenue` decimal(15,2) DEFAULT NULL,
  `top_tenants` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci COMMENT '5 tenant teratas berdasarkan revenue',
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_units` int DEFAULT '0',
  `occupied_units` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan snapshot KPI yang sudah dihitung untuk laporan periodik';

--
-- Dumping data for table `08_kpi_snapshots`
--

INSERT INTO `08_kpi_snapshots` (`snapshot_id`, `period_type`, `period_date`, `occupancy_rate`, `total_revenue`, `tenant_revenue`, `event_revenue`, `parking_revenue`, `ads_revenue`, `top_tenants`, `generated_at`, `total_units`, `occupied_units`) VALUES
(1, 'daily', '2026-06-21', 45.45, 1038541000.00, 993500000.00, 0.00, 41000.00, 45000000.00, '1.PT Cinema XXI (Rp 222.000.000), 2.PT Matahari Department Store (Rp 198.000.000), 3.PT KFC Indonesia (Rp 166.500.000), 4.PT Sogo Indonesia (Rp 165.000.000), 5.PT Ace Hardware Indonesia (Rp 132.000.000)', '2026-06-21 15:06:34', 22, 10),
(33, 'daily', '2026-06-20', 77.00, 120000000.00, 91000000.00, 14000000.00, 10000000.00, 5000000.00, 'H&M, Uniqlo, Cinema XXI, Starbucks, Miniso', '2026-06-21 19:06:17', 100, 77),
(34, 'daily', '2026-06-19', 76.50, 118500000.00, 90000000.00, 13500000.00, 10000000.00, 5000000.00, 'H&M, Uniqlo, Cinema XXI, Starbucks, Miniso', '2026-06-21 19:06:17', 100, 76),
(35, 'daily', '2026-06-18', 80.00, 130000000.00, 98000000.00, 16000000.00, 11000000.00, 5000000.00, 'H&M, Uniqlo, Zara, Cinema XXI, Starbucks', '2026-06-21 19:06:17', 100, 80),
(36, 'daily', '2026-06-15', 75.00, 115000000.00, 87000000.00, 13000000.00, 10000000.00, 5000000.00, 'Uniqlo, Cinema XXI, Starbucks, Miniso, KFC', '2026-06-21 19:06:17', 100, 75),
(42, 'weekly', '2026-06-15', 78.20, 870000000.00, 660000000.00, 105000000.00, 70000000.00, 35000000.00, 'H&M, Uniqlo, Zara, Cinema XXI, Starbucks', '2026-06-21 19:06:44', 100, 78),
(43, 'weekly', '2026-06-08', 76.80, 845000000.00, 640000000.00, 100000000.00, 70000000.00, 35000000.00, 'H&M, Uniqlo, Cinema XXI, Starbucks, Ace Hardware', '2026-06-21 19:06:44', 100, 76),
(44, 'weekly', '2026-06-01', 80.50, 910000000.00, 690000000.00, 110000000.00, 75000000.00, 35000000.00, 'H&M, Uniqlo, Zara, Cinema XXI, Starbucks', '2026-06-21 19:06:44', 100, 80),
(45, 'weekly', '2026-05-25', 74.00, 800000000.00, 605000000.00, 95000000.00, 65000000.00, 35000000.00, 'Uniqlo, Cinema XXI, Starbucks, Miniso, KFC', '2026-06-21 19:06:44', 100, 74),
(46, 'annual', '2026-01-01', 78.00, 10500000000.00, 7950000000.00, 1300000000.00, 850000000.00, 400000000.00, 'H&M, Uniqlo, Zara, Cinema XXI, Starbucks', '2026-06-21 19:06:44', 100, 78),
(47, 'annual', '2025-01-01', 82.00, 11200000000.00, 8500000000.00, 1400000000.00, 900000000.00, 400000000.00, 'H&M, Uniqlo, Zara, Cinema XXI, Starbucks', '2026-06-21 19:06:44', 100, 82),
(48, 'annual', '2024-01-01', 75.50, 9800000000.00, 7400000000.00, 1200000000.00, 800000000.00, 400000000.00, 'H&M, Uniqlo, Cinema XXI, Starbucks, Ace Hardware', '2026-06-21 19:06:44', 100, 75),
(49, 'monthly', '2026-06-01', 78.50, 3750000000.00, 2850000000.00, 450000000.00, 300000000.00, 150000000.00, 'H&M, Uniqlo, Zara, Cinema XXI, Starbucks', '2026-06-21 19:12:43', 100, 78),
(50, 'monthly', '2026-05-01', 76.00, 3600000000.00, 2720000000.00, 430000000.00, 300000000.00, 150000000.00, 'H&M, Uniqlo, Cinema XXI, Starbucks, Ace Hardware', '2026-06-21 19:12:43', 100, 76),
(51, 'monthly', '2026-04-01', 80.00, 3900000000.00, 2960000000.00, 470000000.00, 320000000.00, 150000000.00, 'H&M, Uniqlo, Zara, Cinema XXI, Starbucks', '2026-06-21 19:12:43', 100, 80),
(52, 'monthly', '2026-03-01', 74.50, 3400000000.00, 2560000000.00, 410000000.00, 280000000.00, 150000000.00, 'Uniqlo, Cinema XXI, Starbucks, Miniso, KFC', '2026-06-21 19:12:43', 100, 74),
(53, 'monthly', '2026-02-01', 72.00, 3200000000.00, 2400000000.00, 380000000.00, 270000000.00, 150000000.00, 'Uniqlo, Cinema XXI, Starbucks, Miniso, KFC', '2026-06-21 19:12:43', 100, 72),
(54, 'monthly', '2026-01-01', 82.00, 4000000000.00, 3050000000.00, 480000000.00, 320000000.00, 150000000.00, 'H&M, Uniqlo, Zara, Cinema XXI, Starbucks', '2026-06-21 19:12:43', 100, 82);

-- --------------------------------------------------------

--
-- Table structure for table `08_notification_logs`
--

CREATE TABLE `08_notification_logs` (
  `notification_log_id` int NOT NULL,
  `notification_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `recipient_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `recipient_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notification_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'contract_expiry, payment_due, approval_request, approval_result',
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `channel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'email' COMMENT 'email, inapp',
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending' COMMENT 'sent, failed, pending',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL COMMENT 'FK ke M09.users'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Menyimpan semua notifikasi yang dikirim sistem';

-- --------------------------------------------------------

--
-- Table structure for table `08_notification_templates`
--

CREATE TABLE `08_notification_templates` (
  `id` int NOT NULL,
  `notification_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Jenis notifikasi',
  `subject_template` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Template subjek',
  `body_template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Template isi pesan',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Status aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `09_role_pages`
--

CREATE TABLE `09_role_pages` (
  `id` int NOT NULL,
  `role` enum('Super Admin','Admin','Manager','Leasing Manager','Finance Manager','Finance Staff','Purchasing Manager','Purchasing Staff','HR','Facility Manager','Facility Staff','Teknisi','Customer Service','Pengunjung','Petugas Parkir','Event Manager','Event Organizer','Tenant Owner','Tenant Staff') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `page_permission` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
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
  `id` int NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT '1',
  `role_page_id` int NOT NULL,
  `failed_login_attempts` int DEFAULT '0',
  `is_blocked` tinyint(1) DEFAULT '0'
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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ad_contracts_tenant` (`tenant_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_event_rev_booking` (`booking_id`);

--
-- Indexes for table `06_invoices`
--
ALTER TABLE `06_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `fk_invoices_contract` (`contract_id`),
  ADD KEY `fk_invoices_tenant` (`tenant_id`);

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
  ADD KEY `fk_role_pages` (`role_page_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `01_buildings`
--
ALTER TABLE `01_buildings`
  MODIFY `id_buildings` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik gedung/tower', AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `01_floors`
--
ALTER TABLE `01_floors`
  MODIFY `id_floors` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik lantai', AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `01_malls`
--
ALTER TABLE `01_malls`
  MODIFY `id_malls` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik mall/cabang', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `01_tenant_categories`
--
ALTER TABLE `01_tenant_categories`
  MODIFY `id_tenant_categories` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik kategori tenant', AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `01_units`
--
ALTER TABLE `01_units`
  MODIFY `id_units` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik unit/kios', AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `01_unit_types`
--
ALTER TABLE `01_unit_types`
  MODIFY `id_unit_types` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik tipe unit', AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `02_contracts`
--
ALTER TABLE `02_contracts`
  MODIFY `id_contract` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `02_contract_cost`
--
ALTER TABLE `02_contract_cost`
  MODIFY `id_component` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `02_tenants`
--
ALTER TABLE `02_tenants`
  MODIFY `id_tenant` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `02_tenant_complaints`
--
ALTER TABLE `02_tenant_complaints`
  MODIFY `id_complaint` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `02_tenant_deposits`
--
ALTER TABLE `02_tenant_deposits`
  MODIFY `id_deposit` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `02_tenant_prospects`
--
ALTER TABLE `02_tenant_prospects`
  MODIFY `id_prospect` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `02_tenant_renovations`
--
ALTER TABLE `02_tenant_renovations`
  MODIFY `id_renovation` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `03_assets`
--
ALTER TABLE `03_assets`
  MODIFY `asset_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `03_asset_mutations`
--
ALTER TABLE `03_asset_mutations`
  MODIFY `mutation_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `03_checklist`
--
ALTER TABLE `03_checklist`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `03_damage_reports`
--
ALTER TABLE `03_damage_reports`
  MODIFY `report_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `03_maintenance_schedule`
--
ALTER TABLE `03_maintenance_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `03_technicians`
--
ALTER TABLE `03_technicians`
  MODIFY `technician_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `03_technician_skills`
--
ALTER TABLE `03_technician_skills`
  MODIFY `skill_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `03_work_orders`
--
ALTER TABLE `03_work_orders`
  MODIFY `work_order_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `03_work_order_activities`
--
ALTER TABLE `03_work_order_activities`
  MODIFY `activity_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `04_event_analytics`
--
ALTER TABLE `04_event_analytics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `04_event_areas`
--
ALTER TABLE `04_event_areas`
  MODIFY `id_area` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `04_event_booking`
--
ALTER TABLE `04_event_booking`
  MODIFY `id_booking` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik pengajuan booking event', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `04_event_booking_vendor`
--
ALTER TABLE `04_event_booking_vendor`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `04_invoice_utilitas`
--
ALTER TABLE `04_invoice_utilitas`
  MODIFY `id_invoice` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik invoice utilitas';

--
-- AUTO_INCREMENT for table `04_parking_member`
--
ALTER TABLE `04_parking_member`
  MODIFY `id_member` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik member parkir';

--
-- AUTO_INCREMENT for table `04_parking_tarif`
--
ALTER TABLE `04_parking_tarif`
  MODIFY `id_tarif` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `04_parking_transaksi`
--
ALTER TABLE `04_parking_transaksi`
  MODIFY `id_transaksi` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik transaksi parkir', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `04_parking_zona`
--
ALTER TABLE `04_parking_zona`
  MODIFY `id_zona` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik zona parkir', AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `04_utility_meters`
--
ALTER TABLE `04_utility_meters`
  MODIFY `id_meter` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik meter utilitas', AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `04_utility_meter_logs`
--
ALTER TABLE `04_utility_meter_logs`
  MODIFY `id_log` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik log pembacaan meter', AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `05_cs_feedback`
--
ALTER TABLE `05_cs_feedback`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `05_found_items`
--
ALTER TABLE `05_found_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `05_lost_reports`
--
ALTER TABLE `05_lost_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `05_tiket_log`
--
ALTER TABLE `05_tiket_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_ad_contracts`
--
ALTER TABLE `06_ad_contracts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `06_approval_log`
--
ALTER TABLE `06_approval_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_chart_of_accounts`
--
ALTER TABLE `06_chart_of_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `06_daily_parking_summary`
--
ALTER TABLE `06_daily_parking_summary`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `06_event_revenue`
--
ALTER TABLE `06_event_revenue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_invoices`
--
ALTER TABLE `06_invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `06_invoice_items`
--
ALTER TABLE `06_invoice_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_journal_entries`
--
ALTER TABLE `06_journal_entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_journal_lines`
--
ALTER TABLE `06_journal_lines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_mall_budgets`
--
ALTER TABLE `06_mall_budgets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_purchase_orders`
--
ALTER TABLE `06_purchase_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_purchase_requests`
--
ALTER TABLE `06_purchase_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `06_vendor_bill_receipts`
--
ALTER TABLE `06_vendor_bill_receipts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `07_absensi`
--
ALTER TABLE `07_absensi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `07_cuti`
--
ALTER TABLE `07_cuti`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `07_jadwal_shift`
--
ALTER TABLE `07_jadwal_shift`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `07_kpi`
--
ALTER TABLE `07_kpi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `07_payroll`
--
ALTER TABLE `07_payroll`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `07_pegawai`
--
ALTER TABLE `07_pegawai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `07_shift`
--
ALTER TABLE `07_shift`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `08_approval_audit_logs`
--
ALTER TABLE `08_approval_audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT COMMENT 'ID unik log';

--
-- AUTO_INCREMENT for table `08_approval_requests`
--
ALTER TABLE `08_approval_requests`
  MODIFY `approval_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `08_kpi_snapshots`
--
ALTER TABLE `08_kpi_snapshots`
  MODIFY `snapshot_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `08_notification_logs`
--
ALTER TABLE `08_notification_logs`
  MODIFY `notification_log_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `08_notification_templates`
--
ALTER TABLE `08_notification_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `09_role_pages`
--
ALTER TABLE `09_role_pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `09_users`
--
ALTER TABLE `09_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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

--
-- Constraints for table `06_ad_contracts`
--
ALTER TABLE `06_ad_contracts`
  ADD CONSTRAINT `fk_ad_contracts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `02_tenants` (`id_tenant`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `06_approval_log`
--
ALTER TABLE `06_approval_log`
  ADD CONSTRAINT `fk_app_log_po` FOREIGN KEY (`po_id`) REFERENCES `06_purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_app_log_request` FOREIGN KEY (`approval_request_id_fk`) REFERENCES `08_approval_requests` (`approval_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `06_event_revenue`
--
ALTER TABLE `06_event_revenue`
  ADD CONSTRAINT `fk_event_rev_booking` FOREIGN KEY (`booking_id`) REFERENCES `04_event_booking` (`id_booking`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `06_invoices`
--
ALTER TABLE `06_invoices`
  ADD CONSTRAINT `fk_invoices_contract` FOREIGN KEY (`contract_id`) REFERENCES `02_contracts` (`id_contract`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_invoices_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `02_tenants` (`id_tenant`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `06_invoice_items`
--
ALTER TABLE `06_invoice_items`
  ADD CONSTRAINT `fk_inv_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `06_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `06_journal_lines`
--
ALTER TABLE `06_journal_lines`
  ADD CONSTRAINT `fk_jrn_lines_coa` FOREIGN KEY (`account_id`) REFERENCES `06_chart_of_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jrn_lines_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `06_journal_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `06_mall_budgets`
--
ALTER TABLE `06_mall_budgets`
  ADD CONSTRAINT `fk_budgets_coa` FOREIGN KEY (`account_id`) REFERENCES `06_chart_of_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `06_purchase_orders`
--
ALTER TABLE `06_purchase_orders`
  ADD CONSTRAINT `fk_po_purchase_request` FOREIGN KEY (`pr_id`) REFERENCES `06_purchase_requests` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `06_vendor_bill_receipts`
--
ALTER TABLE `06_vendor_bill_receipts`
  ADD CONSTRAINT `fk_bill_receipts_po` FOREIGN KEY (`po_id`) REFERENCES `06_purchase_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `09_users`
--
ALTER TABLE `09_users`
  ADD CONSTRAINT `fk_role_pages` FOREIGN KEY (`role_page_id`) REFERENCES `09_role_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
