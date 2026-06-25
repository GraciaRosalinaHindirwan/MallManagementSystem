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
    }

    body {
        background-color: var(--background);
        font-family: var(--font-family);
        color: var(--text);
    }
    
    .page-header h2 {
        font-size: 24px; /* --h2 */
        font-weight: 700;
        color: var(--text);
    }

    .card-table-custom {
        background-color: var(--primary-dark);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2) !important;
        overflow: hidden;
    }

    .table-custom {
        margin-bottom: 0;
        color: var(--text-secondary);
    }

    .table-custom thead th {
        background-color: var(--primary) !important;
        color: var(--text) !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 14px; /* --label */
        letter-spacing: 0.5px;
        padding: 1.1rem 1.25rem;
        border-bottom: 2px solid rgba(255, 255, 255, 0.05);
    }

    .table-custom tbody td {
        padding: 1.1rem 1.25rem;
        font-size: 16px; /* --body */
        color: var(--text-secondary);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        background-color: transparent !important;
    }

    .table-custom tbody tr {
        background-color: transparent !important;
    }

    .table-custom tbody tr:hover td {
        background-color: rgba(255, 255, 255, 0.03) !important;
        color: var(--text) !important;
        transition: all 0.2s ease-in-out;
    }

    /* Customizing Badge Styles - Neomorphic Dark/Glow style */
    .badge-charge {
        padding: 0.5em 0.9em;
        font-weight: 600;
        font-size: 12px; /* --caption */
        border-radius: 6px;
    }
    .badge-fixed { 
        background-color: rgba(0, 212, 216, 0.15); 
        color: var(--accent); 
        border: 1px solid rgba(0, 212, 216, 0.3);
    }
    .badge-revenue { 
        background-color: rgba(34, 197, 94, 0.15); 
        color: var(--success); 
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    .badge-service { 
        background-color: rgba(255, 182, 42, 0.15); 
        color: var(--text-accent); 
        border: 1px solid rgba(255, 182, 42, 0.3);
    }
    .badge-default { 
        background-color: rgba(255, 255, 255, 0.1); 
        color: var(--text-secondary); 
    }
    
    .badge-cycle {
        background-color: rgba(22, 126, 128, 0.2);
        color: #167E80; /* --secondary */
        border: 1px solid rgba(22, 126, 128, 0.4);
        font-weight: 500;
        padding: 0.4em 0.8em;
        border-radius: 6px;
        font-size: 12px; /* --caption */
    }

    .btn-add-custom {
        background-color: var(--secondary);
        border-color: var(--secondary);
        color: var(--text);
        font-weight: 600;
        padding: 0.6rem 1.4rem;
        border-radius: 8px;
        font-size: 14px; /* --label */
        box-shadow: 0 4px 12px rgba(22, 126, 128, 0.3);
        transition: all 0.2s ease-in-out;
    }

    .btn-add-custom:hover {
        background-color: var(--secondary-dark);
        border-color: var(--secondary-dark);
        color: var(--text);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(22, 126, 128, 0.4);
    }

    .contract-number-text {
        color: var(--accent);
        font-weight: 600;
    }

    .text-muted-custom {
        color: var(--text-secondary) !important;
    }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <h2><i class="fa-solid fa-money-check-dollar me-2" style="color: var(--accent);"></i>Komponen Biaya Kontrak Tenant</h2>
        <a href="02_atur_biaya.php" class="btn btn-add-custom"><i class="fa fa-plus me-1"></i> Atur Biaya Baru</a>
    </div>

    <div class="card card-table-custom">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
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
                                <td><strong style="color: var(--text);"><?= $row['brand_name']; ?></strong></td>
                                <td><span class="badge badge-charge <?= $badge_class; ?>"><?= $charge_type; ?></span></td>
                                <td><i class="fa-solid fa-circle-info text-muted-custom me-1" style="font-size: 0.8rem;"></i> <?= $row['calculation_basis']; ?></td>
                                <td class="text-end">
                                    <?php if ($row['calculation_basis'] == 'Percentage'): ?>
                                        <span class="fw-bold" style="color: var(--success);"><?= $row['amount_or_percentage']; ?> %</span>
                                    <?php else: ?>
                                        <span class="fw-bold" style="color: var(--text);">Rp <?= number_format($row['amount_or_percentage'], 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge-cycle"><i class="fa-regular fa-calendar-check me-1"></i> <?= $row['billing_cycle']; ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted-custom">
                                    <i class="fa-solid fa-folder-open d-block mb-3 fa-2x" style="color: var(--text-secondary);"></i> Belum ada data komponen biaya yang diatur.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>