<?php
//include '../../public/auth/checkSession.php';
?>


<?php
// 1. KONEKSI DATABASE UTAMA (MALL ERP)
// ==========================================
$db_host = "localhost";
$db_user = "root";     // Default user XAMPP
$db_pass = "";         // Default password XAMPP (kosong)
$db_name = "mall_erp_(22-07)"; // Nama database sesuai file SQL 

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Cek apakah koneksi berhasil
if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>

<?php
// 1. KONEKSI DATABASE


// 2. DEFINISIKAN VARIABEL TEMPLATE
$department_name = "Tenant & Leasing Management";
$page_title = "Daftar Komponen Biaya Tenant";
$user_name = "Muhammad Naufal";

$menu_items = [
    ['icon' => 'fa-solid fa-money-bill-wave', 'label' => 'Komponen Biaya', 'link' => '02_biaya_tampil.php', 'active_page' => 'biaya'],
    ['icon' => 'fa-solid fa-vault', 'label' => 'Pengelolaan Deposit', 'link' => '02_deposit_tampil.php', 'active_page' => 'deposit'],
    ['icon' => 'fa-solid fa-clock', 'label' => 'Reminder Jatuh Tempo', 'link' => '02_reminder_tampil.php', 'active_page' => 'reminder']
];

// Ambil data komponen biaya dari DB bergabung dengan kontrak dan tenant
$query = "SELECT cc.*, c.contract_number, t.brand_name 
          FROM `02_contract_cost` cc
          JOIN `02_contracts` c ON cc.id_contract = c.id_contract
          JOIN `02_tenants` t ON c.id_tenant = t.id_tenant
          ORDER BY c.contract_number DESC";
$result = mysqli_query($conn, $query);

// 3. KONTEN HALAMAN
ob_start();
?>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>💵 Komponen Biaya Kontrak Tenant</h2>
        <a href="02_atur_biaya.php" class="btn btn-primary"><i class="fa fa-plus"></i> Atur Biaya Baru</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>No Kontrak</th>
                            <th>Nama Brand</th>
                            <th>Jenis Biaya</th>
                            <th>Dasar Perhitungan</th>
                            <th>Nominal / Persentase</th>
                            <th>Siklus Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><strong><?= $row['contract_number']; ?></strong></td>
                            <td><?= $row['brand_name']; ?></td>
                            <td><span class="badge bg-info text-dark"><?= $row['charge_type']; ?></span></td>
                            <td><?= $row['calculation_basis']; ?></td>
                            <td>
                                <?= $row['calculation_basis'] == 'Percentage' ? $row['amount_or_percentage'].' %' : 'Rp '.number_format($row['amount_or_percentage'], 2, ',', '.'); ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= $row['billing_cycle']; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>