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
        $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-2'></i>Komponen biaya sewa berhasil diatur dan disimpan!</div>";
    } else {
        $message = "<div class='alert alert-danger'><i class='fa-solid fa-circle-xmark me-2'></i>Gagal menyimpan data: " . $conn->error . "</div>";
    }
}

// Ambil data kontrak aktif untuk opsi pilihan (Dropdown)
$query_kontrak = "SELECT c.id_contract, c.contract_number, t.brand_name, u.unit_code 
                  FROM `02_contracts` c
                  JOIN `02_tenants` t ON c.id_tenant = t.id_tenant
                  JOIN `01_units` u ON c.id_unit = u.id_units
                  WHERE c.contract_status = 'Active'";
$result_kontrak = $conn->query($query_kontrak);

$department_name = "Tenant & Leasing Management"; 
$page_title = "Atur Komponen Biaya Tenant";
$user_name = "Muhammad Naufal"; 

ob_start();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    :root {
        /* colors */
        --primary: #0B376D;
        --primary-dark: #082A53;

        --secondary: #167E80;
        --secondary-dark: #0D4859;

        --accent: #00D4D8;
        --success: #22C55E;
        --danger: #EF4444;

        /* background */
        --background: #021F42;

        /* text colors */
        --text: #F5F7FA;
        --text-secondary: #B8C7D9;
        --text-accent: #FFB62A;

        /* Typography */
        --font-family: 'Poppins', sans-serif;
        --h1: 32px;
        --h2: 24px;
        --subheading: 20px;
        --body: 16px;
        --label: 14px;
        --caption: 12px;
    }

    body {
        background-color: var(--background);
        color: var(--text);
        font-family: var(--font-family);
        font-size: var(--body);
    }

    .card-custom {
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
        overflow: hidden;
        background-color: var(--primary-dark);
    }

    .card-header-custom {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .card-header-custom h5 {
        font-size: var(--subheading);
        font-weight: 600;
        letter-spacing: 0.5px;
        color: var(--text);
    }

    .card-body-custom {
        padding: 2rem 2.5rem;
        background-color: rgba(11, 55, 109, 0.4); /* Blend smooth dengan primary */
    }

    .form-label-custom {
        font-size: var(--label);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }

    .form-control-custom {
        background-color: var(--background);
        color: var(--text);
        border-radius: 8px;
        padding: 0.6rem 1rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        font-size: var(--body);
        transition: all 0.2s ease-in-out;
    }

    /* Memastikan dropdown bawaan browser di dark mode tidak rusak */
    .form-control-custom option {
        background-color: var(--background);
        color: var(--text);
    }

    .form-control-custom:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 0.25rem rgba(0, 212, 216, 0.15);
        background-color: var(--background);
        color: var(--text);
    }

    .input-group-text-custom {
        background-color: var(--primary);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px 0 0 8px;
        color: var(--text-accent);
        font-weight: 600;
    }

    .input-group .form-control-custom {
        border-radius: 0 8px 8px 0;
    }

    .btn-custom-primary {
        background-color: var(--secondary);
        border-color: var(--secondary);
        color: var(--text);
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: var(--body);
        transition: all 0.2s ease-in-out;
    }

    .btn-custom-primary:hover {
        background-color: var(--secondary-dark);
        border-color: var(--secondary-dark);
        color: var(--text);
    }

    .btn-custom-secondary {
        background-color: rgba(255, 255, 255, 0.08);
        border-color: transparent;
        color: var(--text-secondary);
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: var(--body);
        transition: all 0.2s ease-in-out;
    }

    .btn-custom-secondary:hover {
        background-color: rgba(255, 255, 255, 0.15);
        color: var(--text);
    }

    /* Kustomisasi alert Bootstrap biar menyatu dengan tema */
    .alert-success {
        background-color: rgba(34, 197, 94, 0.15);
        border: 1px solid var(--success);
        color: #aeebd0;
    }

    .alert-danger {
        background-color: rgba(239, 68, 68, 0.15);
        border: 1px solid var(--danger);
        color: #fbcbc4;
    }

    .alert {
        border-radius: 8px;
        font-size: var(--body);
    }

    .small-info {
        font-size: var(--caption);
        color: var(--text-secondary);
        margin-top: 0.25rem;
    }
</style>

<div class="container-fluid py-5">
    <div class="row">
        <div class="col-xl-7 col-lg-9 mx-auto">
            <div class="card card-custom">
                <div class="card-header card-header-custom text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-calculator me-2"></i> Form Input Komponen Biaya Tenant (PBI-M02-03-01)</h5>
                </div>
                <div class="card-body card-body-custom">
                    
                    <?php echo $message; ?>

                    <form action="" method="POST">
                        
                        <div class="mb-4">
                            <label for="id_contract" class="form-label form-label-custom">Kontrak / Tenant Aktif</label>
                            <select class="form-select form-control-custom" id="id_contract" name="id_contract" required>
                                <option value="">-- Pilih Kontrak Tenant --</option>
                                <?php while($row = $result_kontrak->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id_contract']; ?>">
                                        <?php echo $row['contract_number'] . " - " . $row['brand_name'] . " (Unit: " . $row['unit_code'] . ")"; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="charge_type" class="form-label form-label-custom">Jenis Komponen Biaya (Charge Type)</label>
                            <select class="form-select form-control-custom" id="charge_type" name="charge_type" required>
                                <option value="">-- Pilih Jenis Biaya --</option>
                                <option value="Fixed Rent">Fixed Rent (Sewa Tetap)</option>
                                <option value="Revenue Sharing">Revenue Sharing (Bagi Hasil)</option>
                                <option value="Service Charge">Service Charge (Biaya Layanan)</option>
                                <option value="Utility Charge">Utility Charge</option>
                                <option value="Maintenance Fee">Maintenance Fee</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="calculation_basis" class="form-label form-label-custom">Dasar Perhitungan (Calculation Basis)</label>
                            <select class="form-select form-control-custom" id="calculation_basis" name="calculation_basis" required>
                                <option value="">-- Pilih Dasar Hitung --</option>
                                <option value="Per Sqm">Per Meter Persegi (Per Sqm)</option>
                                <option value="Fixed Monthly">Bulanan Tetap (Fixed Monthly)</option>
                                <option value="Percentage">Persentase (Percentage)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="amount_or_percentage" class="form-label form-label-custom">Nilai Nominal (Rupiah) / Persentase (%)</label>
                            <div class="input-group">
                                <span class="input-group-text input-group-text-custom">Rp / %</span>
                                <input type="number" step="0.01" class="form-control form-control-custom" id="amount_or_percentage" name="amount_or_percentage" placeholder="Contoh: 150000000 atau 10.5" required>
                            </div>
                            <div class="small-info">*Jangan gunakan titik atau koma untuk ribuan, gunakan desimal langsung jika berupa persentase.</div>
                        </div>

                        <div class="mb-4">
                            <label for="billing_cycle" class="form-label form-label-custom">Siklus Penagihan (Billing Cycle)</label>
                            <select class="form-select form-control-custom" id="billing_cycle" name="billing_cycle" required>
                                <option value="Monthly">Bulanan (Monthly)</option>
                                <option value="Quarterly">Tiga Bulanan (Quarterly)</option>
                                <option value="Annually">Tahunan (Annually)</option>
                            </select>
                        </div>

                        <div class="mt-5 text-end">
                            <button type="reset" class="btn btn-custom-secondary me-2"><i class="fa-solid fa-undo me-1"></i> Reset</button>
                            <button type="submit" name="submit_biaya" class="btn btn-custom-primary text-white"><i class="fa-solid fa-save me-1"></i> Simpan Komponen Biaya</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>