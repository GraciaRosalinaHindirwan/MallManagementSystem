-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2026 at 02:56 PM
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `08_approval_requests`
--
ALTER TABLE `08_approval_requests`
  ADD PRIMARY KEY (`approval_id`),
  ADD UNIQUE KEY `request_number` (`request_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `08_approval_requests`
--
ALTER TABLE `08_approval_requests`
  MODIFY `approval_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
