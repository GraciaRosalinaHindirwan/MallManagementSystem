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
        $message = "<div class='alert alert-success'>Data deposit jaminan berhasil dicatat ke sistem!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal menyimpan data deposit: " . mysqli_error($conn) . "</div>";
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

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="02_deposit_tampil.php" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Kembali ke Daftar</a>
            </div>

            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-vault me-2"></i> Form Pencatatan Jaminan / Deposit Tenant (PBI-M02-03-02)</h5>
                </div>
                <div class="card-body">
                    
                    <?= $message; ?>

                    <form action="deposit_tenant.php" method="POST">
                        
                        <div class="mb-3">
                            <label for="id_contract" class="form-label fw-bold">Kontrak / Tenant Terkait</label>
                            <select class="form-control" id="id_contract" name="id_contract" required>
                                <option value="">-- Pilih Kontrak Tenant --</option>
                                <?php while($row = mysqli_fetch_assoc($result_kontrak)): ?>
                                    <option value="<?= $row['id_contract']; ?>">
                                        <?= $row['contract_number'] . " - " . $row['brand_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="deposit_type" class="form-label fw-bold">Jenis Deposit (Deposit Type)</label>
                            <select class="form-control" id="deposit_type" name="deposit_type" required>
                                <option value="">-- Pilih Jenis Jaminan --</option>
                                <option value="Security Deposit">Security Deposit (Uang Jaminan Gedung)</option>
                                <option value="Utility Deposit">Utility Deposit (Uang Jaminan Air/Listrik)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label fw-bold">Nominal Uang Jaminan (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" placeholder="Contoh: 50000000" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="payment_status" class="form-label fw-bold">Status Pembayaran</label>
                            <select class="form-control" id="payment_status" name="payment_status" required>
                                <option value="Unpaid">Unpaid (Belum Dibayar)</option>
                                <option value="Paid">Paid (Sudah Lunas Dibayar)</option>
                            </select>
                            <small class="text-muted">*Jika dipilih 'Paid', sistem otomatis merekam tanggal pembayaran hari ini.</small>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="reset" class="btn btn-light border me-2"><i class="fa-solid fa-undo"></i> Reset</button>
                            <button type="submit" name="submit_deposit" class="btn btn-success"><i class="fa-solid fa-save"></i> Simpan Data Deposit</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
