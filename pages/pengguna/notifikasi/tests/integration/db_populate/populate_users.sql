-- Clear existing data and reset AUTO_INCREMENT
DELETE FROM 09_users;
DELETE FROM 09_role_pages;

-- TRUNCATE TABLE `09_users`;
-- TRUNCATE TABLE `09_role_pages`;

--
-- Dumping data for table `09_role_pages`
--

INSERT INTO `09_role_pages` (id, `role`, `page_permission`) VALUES
(1, 'Super Admin', 'pages/superadmin/*'),
(2, 'Admin', 'pages/admin/*'),
(3, 'Manager', 'pages/manager/*'),
(4, 'Leasing Manager', 'pages/leasing/*'),
(5, 'Finance Manager', 'pages/finance/*'),
(6, 'Finance Staff', 'pages/finance/staff/*'),
(7, 'Purchasing Manager', 'pages/purchasing/*'),
(8, 'Purchasing Staff', 'pages/purchasing/staff/*'),
(9, 'HR', 'pages/hr/*'),
(10, 'Facility Manager', 'pages/facility/*'),
(11, 'Facility Staff', 'pages/facility/staff/*'),
(12, 'Teknisi', 'pages/technician/*'),
(13, 'Customer Service', 'pages/cs/*'),
(14, 'Pengunjung', 'pages/visitor/*'),
(15, 'Petugas Parkir', 'pages/parking/*'),
(16, 'Event Manager', 'pages/event/*'),
(17, 'Event Organizer', 'pages/event/organizer/*'),
(18, 'Tenant Owner', 'pages/tenant/*'),
(19, 'Tenant Staff', 'pages/tenant/staff/*');

-- --------------------------------------------------------

--
-- Dumping data for table `09_users`
--

INSERT INTO `09_users` (id, `full_name`, `username`, `email`, `password`, `must_change_password`, `role_page_id`, `failed_login_attempts`, `is_blocked`) VALUES
(1, 'Super Admin', 'superadmin', 'superadmin@mall.com', 'admin123', 0, 1, 0, 0),
(2, 'Admin Mall', 'admin', 'admin@mall.com', 'admin123', 1, 2, 0, 0),
(3, 'Manager Mall', 'manager', 'manager@mall.com', 'manager123', 1, 3, 0, 0),
(4, 'Leasing Manager', 'leasing', 'leasing@mall.com', 'leasing123', 1, 4, 0, 0),
(5, 'Finance Manager', 'finance.mgr', 'finance@mall.com', 'finance123', 1, 5, 0, 0),
(6, 'Finance Staff A', 'finance.staff', 'finance.staff@mall.com', 'finance123', 1, 6, 0, 0),
(7, 'Purchasing Manager', 'purchasing.mgr', 'purchasing@mall.com', 'purch123', 1, 7, 0, 0),
(8, 'Purchasing Staff', 'purchasing.staff', 'purchasing.staff@mall.com', 'purch123', 1, 8, 0, 0),
(9, 'HR Staff', 'hr', 'hr@mall.com', 'hr123', 1, 9, 0, 0),
(10, 'Facility Manager', 'facility.mgr', 'facility@mall.com', 'facility123', 1, 10, 0, 0),
(11, 'Facility Staff A', 'facility.staff', 'facility.staff@mall.com', 'facility123', 1, 11, 0, 0),
(12, 'Teknisi A', 'teknisi', 'teknisi@mall.com', 'teknisi123', 1, 12, 0, 0),
(13, 'CS Staff', 'cs', 'cs@mall.com', 'cs123', 1, 13, 0, 0),
(14, 'Petugas Parkir A', 'parkir', 'parkir@mall.com', 'parkir123', 1, 15, 0, 0),
(15, 'Event Manager', 'event.mgr', 'event@mall.com', 'event123', 1, 16, 0, 0),
(16, 'Event Organizer A', 'event.org', 'event.org@mall.com', 'event123', 1, 17, 0, 0);
