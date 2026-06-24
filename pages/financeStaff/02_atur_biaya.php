<?php
//include '../../public/auth/checkSession.php';
?>

<?php
// 1. KONEKSI DATABASE UTAMA (MALL ERP)
// ==========================================
$db_host = "localhost";
$db_user = "root";     // Default user XAMPP
$db_pass = "";         // Default password XAMPP (kosong)
$db_name = "mall_erp_(22-07)"; // Nama database sesuai file SQL Anda

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Cek apakah koneksi berhasil
if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>

<?php
// =====================================================
// 1. KONEKSI DATABASE
// =====================================================
// Sesuaikan path koneksi database sesuai struktur projek Anda, contoh menggunakan conn dari modul 8 atau config umum

// Proses Handling Form Submit (Aksi Input ke Database)
$message = "";
if (isset($_POST['submit_biaya'])) {
    $id_contract = $_POST['id_contract'];
    $charge_type = $_POST['charge_type'];
    $calculation_basis = $_POST['calculation_basis'];
    $amount_or_percentage = $_POST['amount_or_percentage'];
    $billing_cycle = $_POST['billing_cycle'];

    // Query Insert ke tabel 02_contract_cost
    $query_insert = "INSERT INTO `02_contract_cost` (`id_contract`, `charge_type`, `calculation_basis`, `amount_or_percentage`, `billing_cycle`) 
                     VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query_insert);
    $stmt->bind_param("issds", $id_contract, $charge_type, $calculation_basis, $amount_or_percentage, $billing_cycle);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Komponen biaya sewa berhasil diatur dan disimpan!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal menyimpan data: " . $conn->error . "</div>";
    }
}

// Ambil data kontrak aktif untuk opsi pilihan (Dropdown)
$query_kontrak = "SELECT c.id_contract, c.contract_number, t.brand_name, u.unit_code 
                  FROM `02_contracts` c
                  JOIN `02_tenants` t ON c.id_tenant = t.id_tenant
                  JOIN `01_units` u ON c.id_unit = u.id_units
                  WHERE c.contract_status = 'Active'";
$result_kontrak = $conn->query($query_kontrak);


// =====================================================
// 2. DEFINISIKAN VARIABEL UNTUK TEMPLATE Layout
// =====================================================
$department_name = "Tenant & Leasing Management"; 
$page_title = "Atur Komponen Biaya Tenant";
$user_name = "Muhammad Naufal"; // Menggunakan data profil user

$menu_items = [
    [
        'icon' => 'fa-solid fa-chart-line',
        'label' => 'Dashboard',
        'link' => '08_dashboard.php',
        'active_page' => 'dashboard'
    ],
    [
        'icon' => 'fa-solid fa-money-bill-wave',
        'label' => 'Manajemen Biaya',
        'link' => '02_atur_biaya.php',
        'active_page' => 'atur_biaya'
    ],
    [
        'icon' => 'fa-solid fa-file-alt',
        'label' => 'Laporan',
        'link' => '08_laporan.php',
        'active_page' => 'laporan'
    ]
];


// =====================================================
// 3. KONTEN HALAMAN (Menggunakan Output Buffering)
// =====================================================
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-calculator me-2"></i> Form Input Komponen Biaya Tenant (PBI-M02-03-01)</h5>
                </div>
                <div class="card-body">
                    
                    <?php echo $message; ?>

                    <form action="biaya_tampil.php" method="POST">
                        
                        <div class="mb-3">
                            <label for="id_contract" class="form-label font-weight-bold">Kontrak / Tenant Aktif</label>
                            <select class="form-control" id_contract name="id_contract" required>
                                <option value="">-- Pilih Kontrak Tenant --</option>
                                <?php while($row = $result_kontrak->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id_contract']; ?>">
                                        <?php echo $row['contract_number'] . " - " . $row['brand_name'] . " (Unit: " . $row['unit_code'] . ")"; ?>
                                    </option>
                                <?php endwhile; ?> </select>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="charge_type" class="form-label font-weight-bold">Jenis Komponen Biaya (Charge Type)</label>
                            <select class="form-control" id="charge_type" name="charge_type" required>
                                <option value="">-- Pilih Jenis Biaya --</option>
                                <option value="Fixed Rent">Fixed Rent (Sewa Tetap)</option>
                                <option value="Revenue Sharing">Revenue Sharing (Bagi Hasil)</option>
                                <option value="Service Charge">Service Charge (Biaya Layanan)</option>
                                <option value="Utility Charge">Utility Charge</option>
                                <option value="Maintenance Fee">Maintenance Fee</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="calculation_basis" class="form-label font-weight-bold">Dasar Perhitungan (Calculation Basis)</label>
                            <select class="form-control" id="calculation_basis" name="calculation_basis" required>
                                <option value="">-- Pilih Dasar Hitung --</option>
                                <option value="Per Sqm">Per Meter Persegi (Per Sqm)</option>
                                <option value="Fixed Monthly">Bulanan Tetap (Fixed Monthly)</option>
                                <option value="Percentage">Persentase (Percentage)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="amount_or_percentage" class="form-label font-weight-bold">Nilai Nominal (Rupiah) / Persentase (%)</label>
                            <div class="input-group">
                                <span class="input-group-text">Nilai</span>
                                <input type="number" step="0.01" class="form-control" id="amount_or_percentage" name="amount_or_percentage" placeholder="Contoh: 150000000 atau 10.5" required>
                            </div>
                            <small class="text-muted">*Jangan gunakan titik atau koma untuk ribuan, gunakan desimal langsung jika berupa persentase.</small>
                        </div>

                        <div class="mb-3">
                            <label for="billing_cycle" class="form-label font-weight-bold">Siklus Penagihan (Billing Cycle)</label>
                            <select class="form-control" id="billing_cycle" name="billing_cycle" required>
                                <option value="Monthly">Bulanan (Monthly)</option>
                                <option value="Quarterly">Tiga Bulanan (Quarterly)</option>
                                <option value="Annually">Tahunan (Annually)</option>
                            </select>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="reset" class="btn btn-secondary me-2"><i class="fa-solid fa-undo"></i> Reset</button>
                            <button type="submit" name="submit_biaya" class="btn btn-primary"><i class="fa-solid fa-save"></i> Simpan Komponen Biaya</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
