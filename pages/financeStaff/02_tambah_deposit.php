<?php
//include '../../public/auth/checkSession.php';
?>

<?php
// 1. KONEKSI DATABASE UTAMA (MALL ERP)
// ==========================================
$db_host = "localhost";
$db_user = "root";     
$db_pass = "";         
$db_name = "mall_erp_(22-07)"; // Sudah disesuaikan dengan nama DB Anda

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Proses Handling Form Submit (Aksi Input Deposit ke Database)
$message = "";
if (isset($_POST['submit_deposit'])) {
    $id_contract = $_POST['id_contract'];
    $deposit_type = $_POST['deposit_type'];
    $amount = $_POST['amount'];
    $payment_status = $_POST['payment_status'];
    // Jika statusnya 'Paid', ambil tanggal hari ini, jika 'Unpaid' biarkan NULL
    $payment_date = ($payment_status == 'Paid') ? date('Y-m-d') : null;

    // Query Insert menggunakan Prepared Statement demi keamanan
    $query_insert = "INSERT INTO `02_tenant_deposits` (`id_contract`, `deposit_type`, `amount`, `payment_status`, `payment_date`) 
                     VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query_insert);
    mysqli_stmt_bind_param($stmt, "isdss", $id_contract, $deposit_type, $amount, $payment_status, $payment_date);

    if (mysqli_stmt_execute($stmt)) {
        $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-2'></i>Data deposit jaminan berhasil dicatat ke sistem!</div>";
    } else {
        $message = "<div class='alert alert-danger'><i class='fa-solid fa-circle-xmark me-2'></i>Gagal menyimpan data deposit: " . mysqli_error($conn) . "</div>";
    }
    mysqli_stmt_close($stmt);
}

// Ambil data kontrak aktif untuk opsi dropdown pilihan contract
$query_kontrak = "SELECT c.id_contract, c.contract_number, t.brand_name 
                  FROM `02_contracts` c
                  JOIN `02_tenants` t ON c.id_tenant = t.id_tenant
                  WHERE c.contract_status = 'Active'";
$result_kontrak = mysqli_query($conn, $query_kontrak);

// 2. DEFINISIKAN VARIABEL TEMPLATE
$department_name = "Tenant & Leasing Management";
$page_title = "Input Deposit Jaminan Tenant";
$user_name = "Muhammad Naufal";

$menu_items = [
    ['icon' => 'fa-solid fa-money-bill-wave', 'label' => 'Komponen Biaya', 'link' => '02_biaya_tampil.php', 'active_page' => 'biaya'],
    ['icon' => 'fa-solid fa-vault', 'label' => 'Pengelolaan Deposit', 'link' => 'deposit_tenant.php', 'active_page' => 'deposit'],
    ['icon' => 'fa-solid fa-clock', 'label' => 'Reminder Jatuh Tempo', 'link' => '02_reminder_tampil.php', 'active_page' => 'reminder']
];

// 3. KONTEN HALAMAN (Mulai Tangkap Konten)
ob_start();
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
    .card-custom {
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(149, 157, 165, 0.15) !important;
        overflow: hidden;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #198754 0%, #0f5132 100%) !important; /* Tema Hijau Finansial Premium */
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }
    .card-header-custom h5 {
        font-size: 1.15rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .card-body-custom {
        padding: 2rem 2.5rem;
        background-color: #ffffff;
    }
    .form-label-custom {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    .form-control-custom {
        border-radius: 8px;
        padding: 0.6rem 1rem;
        border: 1px solid #d1d3e2;
        font-size: 0.95rem;
        transition: all 0.2s ease-in-out;
    }
    .form-control-custom:focus {
        border-color: #a3cfbb;
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15);
        background-color: #fff;
    }
    .input-group-text-custom {
        background-color: #eaecf4;
        border: 1px solid #d1d3e2;
        border-radius: 8px 0 0 8px;
        color: #495057;
        font-weight: 600;
    }
    .input-group .form-control-custom {
        border-radius: 0 8px 8px 0;
    }
    .btn-custom-success {
        background-color: #198754;
        border-color: #198754;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s ease-in-out;
    }
    .btn-custom-success:hover {
        background-color: #157347;
        border-color: #146c43;
    }
    .btn-custom-back {
        font-weight: 600;
        font-size: 0.85rem;
        border-radius: 6px;
        padding: 0.4rem 1rem;
        transition: all 0.2s;
    }
    .btn-custom-back:hover {
        transform: translateX(-3px);
    }
    .btn-custom-secondary {
        background-color: #eaecf4;
        border-color: #eaecf4;
        color: #5a5c69;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
    }
    .btn-custom-secondary:hover {
        background-color: #dddfeb;
        color: #5a5c69;
    }
    .alert {
        border-radius: 8px;
        font-size: 0.95rem;
        border: none;
    }
    .small-info {
        font-size: 0.8rem;
        color: #858796;
        margin-top: 0.25rem;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-xl-7 col-lg-9 mx-auto">
            
            <div class="d-flex justify-content-start mb-3">
                <a href="deposit_tenant.php" class="btn btn-outline-secondary btn-custom-back shadow-sm">
                    <i class="fa fa-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>

            <div class="card card-custom">
                <div class="card-header card-header-custom text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-vault me-2"></i> Form Pencatatan Jaminan / Deposit Tenant (PBI-M02-03-02)</h5>
                </div>
                <div class="card-body card-body-custom">
                    
                    <?= $message; ?>

                    <form action="deposit_tenant.php" method="POST">
                        
                        <div class="mb-4">
                            <label for="id_contract" class="form-label form-label-custom font-weight-bold">Kontrak / Tenant Terkait</label>
                            <select class="form-select form-control-custom" id="id_contract" name="id_contract" required>
                                <option value="">-- Pilih Kontrak Tenant --</option>
                                <?php while($row = mysqli_fetch_assoc($result_kontrak)): ?>
                                    <option value="<?= $row['id_contract']; ?>">
                                        <?= $row['contract_number'] . " - " . $row['brand_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="deposit_type" class="form-label form-label-custom font-weight-bold">Jenis Deposit (Deposit Type)</label>
                            <select class="form-select form-control-custom" id="deposit_type" name="deposit_type" required>
                                <option value="">-- Pilih Jenis Jaminan --</option>
                                <option value="Security Deposit">Security Deposit (Uang Jaminan Gedung)</option>
                                <option value="Utility Deposit">Utility Deposit (Uang Jaminan Air/Listrik)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="amount" class="form-label form-label-custom font-weight-bold">Nominal Uang Jaminan (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text input-group-text-custom">Rp</span>
                                <input type="number" step="0.01" class="form-control form-control-custom" id="amount" name="amount" placeholder="Contoh: 50000000" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="payment_status" class="form-label form-label-custom font-weight-bold">Status Pembayaran</label>
                            <select class="form-select form-control-custom" id="payment_status" name="payment_status" required>
                                <option value="Unpaid">Unpaid (Belum Dibayar)</option>
                                <option value="Paid">Paid (Sudah Lunas Dibayar)</option>
                            </select>
                            <div class="small-info"><i class="fa-solid fa-circle-info me-1"></i> Jika dipilih 'Paid', sistem otomatis merekam tanggal pembayaran hari ini.</div>
                        </div>

                        <div class="mt-5 text-end">
                            <button type="reset" class="btn btn-custom-secondary me-2"><i class="fa-solid fa-undo me-1"></i> Reset</button>
                            <button type="submit" name="submit_deposit" class="btn btn-custom-success text-white"><i class="fa-solid fa-save me-1"></i> Simpan Data Deposit</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>