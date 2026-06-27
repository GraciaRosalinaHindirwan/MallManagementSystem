<?php
/** @var mysqli $conn */ // Memberitahu VS Code kalau $conn itu objek database sah!

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeStaff') {
    // Jika bukan Finance Staff, tendang kembali ke halaman utama login
    header("Location: ../../index.php"); 
    exit();
}
*/

$_SESSION['role'] = 'financeStaff';
$_SESSION['nama'] = 'Finance Staff';


// 1. Panggil koneksi database
if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} else {
    require_once '../../config/connection.php';
}

// PROSES PBI-M06-01-01: Sinkronisasi Kontrak Aktif (M02) Menjadi Invoice (M06)
if (isset($_POST['sync_m02'])) {
    $cek_kontrak = $conn->query("SELECT * FROM 02_contracts WHERE contract_status = 'Active'");
    if ($cek_kontrak && $cek_kontrak->num_rows > 0) {
        $sukses = 0;
        while ($kontrak = $cek_kontrak->fetch_assoc()) {
            $id_contract = $kontrak['id_contract'];        
            $id_tenant   = $kontrak['id_tenant']; 
            $total_sewa  = 5000000; 
            
            $cek_double = $conn->query("SELECT id FROM 06_invoices WHERE contract_id = '$id_contract'");
            if ($cek_double && $cek_double->num_rows == 0) {
                $no_inv = "INV-2026-" . rand(1000, 9999);
                $jatuh_tempo = date('Y-m-d', strtotime('+14 days'));

                $insert = $conn->query("INSERT INTO 06_invoices (invoice_number, contract_id, tenant_id, due_date, total_amount, status) 
                                        VALUES ('$no_inv', '$id_contract', '$id_tenant', '$jatuh_tempo', '$total_sewa', 'Belum Bayar')");
                if ($insert) { $sukses++; }
            }
        }
        if ($sukses > 0) {
            echo "<script>alert('Sukses! $sukses Data invoice baru berhasil dibuat.'); window.location='invoiceManagement.php';</script>";
        } else {
            echo "<script>alert('Perhatian: Semua kontrak aktif sudah memiliki invoice.'); window.location='invoiceManagement.php';</script>";
        }
    } else {
        $no_inv = "INV-2026-999";
        $conn->query("INSERT INTO 06_invoices (invoice_number, contract_id, tenant_id, due_date, total_amount, status) 
                      VALUES ('$no_inv', 1, 1, '2026-07-20', 12500000, 'Belum Bayar')");
        echo "<script>alert('Demo Mode: Kontrak M02 kosong, invoice simulasi berhasil diterbitkan!'); window.location='invoiceManagement.php';</script>";
    }
}

// Ambil data dari database untuk tabel workspace
$query_invoices = "SELECT i.*, t.brand_name FROM 06_invoices i LEFT JOIN 02_tenants t ON i.tenant_id = t.id_tenant ORDER BY i.id DESC";
$invoices = $conn->query($query_invoices);

// ==========================================
// CONFIG MASTER UNTUK REQUIRE NAVBAR MENTAHAN
// ==========================================
$department_name = "Finance Department"; 
$user_name = $_SESSION['nama'] ?? "Finance Staff";
$page_title = "Invoice Management M06";

$menu_items = [
    [
        'icon'        => 'fa-solid fa-gauge',
        'label'       => 'Dashboard Staff',
        'link'        => 'dashboardStaff.php',
        'active_page' => 'Dashboard Staff'
    ],
    [
        'icon'        => 'fa-solid fa-file-invoice',
        'label'       => 'Invoice Management',
        'link'        => 'invoiceManagement.php',
        'active_page' => 'Invoice Management'
    ],
    [
        'icon'        => 'fa-solid fa-bolt-lightning', 
        'label'       => 'Invoice Utilitas (Air/Listrik)',
        'link'        => 'utility_invoice.php', 
        'active_page' => 'utility_invoice'
    ],
    [
        'icon'        => 'fa-solid fa-cash-register',
        'label'       => 'Billing System',
        'link'        => 'billingManagement.php',
        'active_page' => 'Billing System'
    ],
    [
        'icon'        => 'fa-solid fa-file-invoice-dollar',
        'label'       => 'Vendor Bill',
        'link'        => 'vendor_bill.php', // Sesuaikan nama file tujuanmu jika berbeda
        'active_page' => 'Vendor Bill'
    ],
    [
        'icon'        => 'fa-solid fa-book',
        'label'       => 'Jurnal Otomatis',
        'link'        => 'journalManagement.php',
        'active_page' => 'Jurnal Otomatis'
    ],
    [
        'icon'        => 'fa-solid fa-folder-open',
        'label'       => 'Dashboard Non Sewa',
        'link'        => 'dashboardNonSewa.php',
        'active_page' => 'Dashboard Non Sewa'
    ]
];

// Mulai tangkap konten halaman M06 agar masuk ke dalam variabel $content template mentahan kamu
ob_start();
?>

<style>
    :root {
        --primary-color: #021F42 !important;
        --bg-dark: #021F42 !important;
    }
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #fff !important; }
    .sidebar { background-color: #011630 !important; border-right: 1px solid rgba(255,255,255,0.05); }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff !important; }
    .page-title { color: #FFB62A !important; font-weight: 700; }
    .topbar-user { color: #fff !important; }
    .table-responsive-custom { background: #011630; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 15px; margin-top: 20px; }
    .table-m06 { width: 100%; color: #fff; border-collapse: collapse; }
    .table-m06 th { color: #FFB62A; border-bottom: 2px solid rgba(255,255,255,0.1); padding: 12px; font-size: 14px; text-align: left; }
    .table-m06 td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
    .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .btn-action { padding: 5px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; display: inline-block; border: none; cursor: pointer; }
</style>

<div class="container-fluid" style="text-align: left;">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <form method="POST">
            <button type="submit" name="sync_m02" class="btn-action" style="background-color: #FFB62A; color: #021F42; font-weight: 600; padding: 10px 18px;">
                <i class="fa-solid fa-sync-alt"></i> Tarik & Sinkronisasi Kontrak Aktif (M02)
            </button>
        </form>
    </div>

    <div class="table-responsive-custom">
        <table class="table-m06">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Nama Tenant / Brand</th>
                    <th style="text-align: center;">ID Kontrak</th>
                    <th>Total Tagihan</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($invoices && $invoices->num_rows > 0): ?>
                    <?php while ($row = $invoices->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['invoice_number']); ?></strong></td>
                        <td style="color: #cbd5e1;"><?= htmlspecialchars($row['brand_name'] ?? 'Tenant Tidak Teridentifikasi'); ?></td>
                        <td style="text-align: center;">
                            <span style="background: rgba(255,255,255,0.08); color: #cbd5e1; padding: 3px 8px; border-radius: 4px; font-family: monospace;">
                                #CT-<?= $row['contract_id']; ?>
                            </span>
                        </td>
                        <td style="font-weight: 600;">Rp <?= number_format($row['total_amount'], 0, ',', '.'); ?></td>
                        <td style="color: #cbd5e1;"><?= date('d M Y', strtotime($row['due_date'])); ?></td>
                        <td>
                            <?php if ($row['status'] == 'Lunas'): ?>
                                <span class="badge-status" style="background-color: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Lunas</span>
                            <?php else: ?>
                                <span class="badge-status" style="background-color: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Belum Bayar</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button class="btn-action" style="background: rgba(255,255,255,0.05); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); margin-right: 5px;" onclick="alert('Notifikasi terkirim!')">
                                <i class="fa-regular fa-paper-plane"></i> Kirim
                            </button>
                            <?php if ($row['status'] !== 'Lunas'): ?>
                                <a href="billingManagement.php?id=<?= $row['id']; ?>" class="btn-action" style="background: rgba(255,182,42,0.1); color: #FFB62A; border: 1px solid #FFB62A;">
                                    <i class="fa-solid fa-receipt"></i> Bayar
                                </a>
                            <?php else: ?>
                                <button class="btn-action" style="background: rgba(255,255,255,0.02); color: #4a5568;" disabled><i class="fa-solid fa-check-double"></i> Selesai</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: #cbd5e1;">📂 Belum ada data invoice tercatat dalam sistem.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once '../../includes/navbarMO6.php'; 
?>
