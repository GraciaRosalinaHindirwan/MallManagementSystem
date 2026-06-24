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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
    .page-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
    }
    .card-table-custom {
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(149, 157, 165, 0.08) !important;
        overflow: hidden;
    }
    .table-custom {
        margin-bottom: 0;
    }
    .table-custom thead th {
        background-color: #4e73df !important; /* Disamakan dengan header biru form */
        color: #ffffff !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.82rem;
        letter-spacing: 0.5px;
        padding: 1rem 1.25rem;
        border-bottom: none;
    }
    .table-custom tbody td {
        padding: 1rem 1.25rem;
        font-size: 0.92rem;
        color: #495057;
        border-bottom: 1px solid #eaecf4;
    }
    .table-custom tbody tr:hover {
        background-color: #f1f3f9 !important;
        transition: background-color 0.2s ease-in-out;
    }
    /* Customizing Badge Styles agar terlihat modern (tidak monoton) */
    .badge-charge {
        padding: 0.45em 0.85em;
        font-weight: 600;
        font-size: 0.8rem;
        border-radius: 6px;
    }
    .badge-fixed { background-color: #e8f0fe; color: #1a73e8; }
    .badge-revenue { background-color: #e6f4ea; color: #137333; }
    .badge-service { background-color: #fef7e0; color: #b06000; }
    .badge-default { background-color: #f1f3f4; color: #5f6368; }
    
    .badge-cycle {
        background-color: #eaeaea;
        color: #444444;
        font-weight: 500;
        padding: 0.4em 0.7em;
        border-radius: 4px;
        font-size: 0.8rem;
    }
    .btn-add-custom {
        background-color: #4e73df;
        border-color: #4e73df;
        font-weight: 600;
        padding: 0.55rem 1.25rem;
        border-radius: 8px;
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(78, 115, 223, 0.2);
        transition: all 0.2s ease-in-out;
    }
    .btn-add-custom:hover {
        background-color: #2e59d9;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(78, 115, 223, 0.3);
    }
    .contract-number-text {
        color: #4e73df;
        font-weight: 600;
    }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <h2><i class="fa-solid fa-money-check-dollar me-2 text-primary"></i>Komponen Biaya Kontrak Tenant</h2>
        <a href="02_atur_biaya.php" class="btn btn-primary btn-add-custom"><i class="fa fa-plus me-1"></i> Atur Biaya Baru</a>
    </div>

    <div class="card card-table-custom shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No Kontrak</th>
                            <th>Nama Brand</th>
                            <th>Jenis Biaya</th>
                            <th>Dasar Perhitungan</th>
                            <th class="text-end">Nominal / Persentase</th>
                            <th class="text-center">Siklus Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): 
                                // Logika penentuan warna badge jenis biaya secara dinamis
                                $charge_type = $row['charge_type'];
                                $badge_class = "badge-default";
                                if ($charge_type == 'Fixed Rent') { $badge_class = "badge-fixed"; }
                                elseif ($charge_type == 'Revenue Sharing') { $badge_class = "badge-revenue"; }
                                elseif ($charge_type == 'Service Charge') { $badge_class = "badge-service"; }
                            ?>
                            <tr>
                                <td><span class="contract-number-text"><?= $row['contract_number']; ?></span></td>
                                <td><strong><?= $row['brand_name']; ?></strong></td>
                                <td><span class="badge badge-charge <?= $badge_class; ?>"><?= $charge_type; ?></span></td>
                                <td><i class="fa-solid fa-circle-info text-muted me-1" style="font-size: 0.8rem;"></i> <?= $row['calculation_basis']; ?></td>
                                <td class="text-end font-weight-bold">
                                    <?php if ($row['calculation_basis'] == 'Percentage'): ?>
                                        <span class="text-success fw-bold"><?= $row['amount_or_percentage']; ?> %</span>
                                    <?php else: ?>
                                        <span class="fw-bold">Rp <?= number_format($row['amount_or_percentage'], 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge-cycle"><i class="fa-regular fa-calendar-check me-1"></i> <?= $row['billing_cycle']; ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open d-block mb-2 fa-2x"></i> Belum ada data komponen biaya yang diatur.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>