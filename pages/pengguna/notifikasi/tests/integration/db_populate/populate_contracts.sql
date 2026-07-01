-- Disable FK checks so we can clear tables regardless of external references
SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing data to avoid duplicate PK conflicts
DELETE FROM `02_tenant_deposits`;
DELETE FROM `02_contract_cost`;
DELETE FROM `02_contracts`;
DELETE FROM `01_units`;
DELETE FROM `02_tenants`;
DELETE FROM `02_tenant_prospects`;
DELETE FROM `01_floors`;
DELETE FROM `01_buildings`;
DELETE FROM `01_malls`;
DELETE FROM `01_tenant_categories`;

--
-- Dumping data for table `01_malls`
--

INSERT INTO `01_malls` (`id_malls`, `name`, `address`, `city`, `created_at`) VALUES
(1, 'Test Mall A', 'Jl. Test No. 1', 'Jakarta', '2026-06-01 00:00:00'),
(2, 'Test Mall B', 'Jl. Test No. 2', 'Bandung', '2026-06-01 00:00:00');

--
-- Dumping data for table `01_buildings`
--

INSERT INTO `01_buildings` (`id_buildings`, `mall_id`, `name`, `created_at`) VALUES
(1, 1, 'Tower Test A', '2026-06-01 00:00:00'),
(2, 2, 'Tower Test B', '2026-06-01 00:00:00');

--
-- Dumping data for table `01_floors`
--

INSERT INTO `01_floors` (`id_floors`, `building_id`, `floor_number`, `created_at`) VALUES
(1, 1, '1', '2026-06-01 00:00:00'),
(2, 1, '2', '2026-06-01 00:00:00'),
(3, 2, '1', '2026-06-01 00:00:00');

--
-- Dumping data for table `01_tenant_categories`
--

INSERT INTO `01_tenant_categories` (`id_tenant_categories`, `name`) VALUES
(1, 'F&B'),
(2, 'Retail'),
(3, 'Service');

--
-- Dumping data for table `02_tenant_prospects`
--

INSERT INTO `02_tenant_prospects` (`id_prospect`, `brand_name`, `id_category`, `pic_name`, `phone`, `email`, `interested_unit`, `notes`, `status`, `register_date`) VALUES
(1, 'Test Cafe', 1, 'John Doe', '081111111111', 'john@testcafe.com', NULL, 'Test prospect for integration', 'Verified', '2026-06-01'),
(2, 'Test Store', 2, 'Jane Doe', '082222222222', 'jane@teststore.com', NULL, 'Test prospect for integration', 'Verified', '2026-06-01');

--
-- Dumping data for table `02_tenants`
--

INSERT INTO `02_tenants` (`id_tenant`, `id_prospect`, `tenant_name`, `brand_name`, `id_category`, `npwp_number`, `status`) VALUES
(1, 1, 'PT Test Cafe Indonesia', 'Test Cafe', 1, '99.999.999.9-999.001', 'Active'),
(2, 2, 'PT Test Store Indonesia', 'Test Store', 2, '99.999.999.9-999.002', 'Active');

--
-- Dumping data for table `01_units`
--

INSERT INTO `01_units` (`id_units`, `floor_id`, `unit_code`, `area_size`, `status`, `tenant_id`, `created_at`) VALUES
(1, 1, 'TST-1-01', 50.00, 'occupied', 1, '2026-06-01 00:00:00'),
(2, 2, 'TST-2-01', 60.00, 'occupied', 2, '2026-06-01 00:00:00'),
(3, 3, 'TST-1-02', 40.00, 'available', NULL, '2026-06-01 00:00:00');

--
-- Dumping data for table `02_contracts`
--

INSERT INTO `02_contracts` (`id_contract`, `contract_number`, `id_tenant`, `id_unit`, `start_date`, `end_date`, `contract_status`, `legal_document_url`) VALUES
(1, 'TEST-CONT-2026-001', 1, 1, '2026-01-01', '2028-12-31', 'Active', '/documents/test_contract_1.pdf'),
(2, 'TEST-CONT-2026-002', 2, 2, '2026-03-01', '2028-02-29', 'Active', '/documents/test_contract_2.pdf');

--
-- Dumping data for table `02_contract_cost`
--

INSERT INTO `02_contract_cost` (`id_component`, `id_contract`, `charge_type`, `calculation_basis`, `amount_or_percentage`, `billing_cycle`) VALUES
(1, 1, 'Fixed Rent', 'Per Sqm', 100000000.00, 'Monthly'),
(2, 1, 'Service Charge', 'Fixed Monthly', 10000000.00, 'Monthly'),
(3, 2, 'Fixed Rent', 'Per Sqm', 120000000.00, 'Monthly'),
(4, 2, 'Service Charge', 'Fixed Monthly', 12000000.00, 'Monthly');

--
-- Dumping data for table `02_tenant_deposits`
--

INSERT INTO `02_tenant_deposits` (`id_deposit`, `id_contract`, `deposit_type`, `amount`, `payment_status`, `payment_date`) VALUES
(1, 1, 'Security Deposit', 50000000.00, 'Paid', '2026-01-01'),
(2, 2, 'Security Deposit', 60000000.00, 'Unpaid', NULL);

-- Re-enable FK checks now that all data is inserted in order
SET FOREIGN_KEY_CHECKS = 1;

--
-- Update AUTO_INCREMENT
--

ALTER TABLE `01_malls`
  MODIFY `id_malls` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `01_buildings`
  MODIFY `id_buildings` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `01_floors`
  MODIFY `id_floors` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `01_tenant_categories`
  MODIFY `id_tenant_categories` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `02_tenant_prospects`
  MODIFY `id_prospect` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `02_tenants`
  MODIFY `id_tenant` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `01_units`
  MODIFY `id_units` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `02_contracts`
  MODIFY `id_contract` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `02_contract_cost`
  MODIFY `id_component` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `02_tenant_deposits`
  MODIFY `id_deposit` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
