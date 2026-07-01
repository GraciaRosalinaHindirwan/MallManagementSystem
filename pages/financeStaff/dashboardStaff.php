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

// 2. Hubungkan ke Database
if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} else {
    require_once '../../config/connection.php';
}

// 4. Ambil Angka Statistik dari Database M06
$res_total = $conn->query("SELECT COUNT(*) as jml FROM 06_invoices");
$total_invoice = ($res_total) ? $res_total->fetch_assoc()['jml'] : 0;

$res_pending = $conn->query("SELECT COUNT(*) as jml FROM 06_invoices WHERE status = 'Belum Bayar' OR status = 'Unpaid'");
$pending_invoice = ($res_pending) ? $res_pending->fetch_assoc()['jml'] : 0;

$res_jurnal = $conn->query("SELECT COUNT(*) as jml FROM 06_journal_entries");
$total_jurnal = ($res_jurnal) ? $res_jurnal->fetch_assoc()['jml'] : 0;

$department_name = "Finance Department (Staff Dashboard)";
$user_name = $_SESSION['nama'];
$page_title = "Dashboard Staff";

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


ob_start();
?>

<style>
    :root { --accent: #FFB62A !important; }
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #fff !important; }
    .sidebar { background-color: #011630 !important; }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); }
    
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 10px; text-align: left; }
    .kpi-card { background: #011630; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 20px; transition: transform 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); border-color: #FFB62A; }
    
    .menu-panel { background: #011630; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 25px; margin-top: 25px; text-align: left; }
    .grid-control { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-top: 15px; }
    .btn-panel { display: flex; align-items: center; justify-content: space-between; padding: 18px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 6px; text-decoration: none; color: #fff; transition: all 0.2s; }
    .btn-panel:hover { background: rgba(255,255,255,0.07); border-color: #FFB62A; transform: translateX(3px); text-decoration: none; }
</style>

<div class="container-fluid" style="padding-top: 5px;">
    <div class="d-flex justify-content-between align-items-center mb-4" style="text-align: left;">
        <div>
            <p class="small mb-0" style="color: #cbd5e1 !important; opacity: 0.9;">
                Grup Otoritas Operasional: <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong>. Panel kendali harian pembukuan & invoice.
            </p>
        </div>
        <div style="background-color: #FFB62A; color: #021F42; font-weight: bold; padding: 6px 15px; border-radius: 4px; font-size: 13px;">
            <i class="fa-solid fa-calendar-days me-1"></i> Tahun Buku: <?php echo date('Y'); ?>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="kpi-card" style="border-left: 4px solid #00cfd5;">
            <small style="color: #cbd5e1; font-size: 11px; display: block; letter-spacing: 0.5px;">TOTAL INVOICE (YTD)</small>
            <h3 style="margin: 8px 0; font-size: 20px; font-weight: 700; color: #fff;"><?= $total_invoice; ?> <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Data</span></h3>
            <small style="color: #00cfd5; font-size: 11px;"><i class="fa-solid fa-file-invoice me-1"></i> Terbit di Sistem M06</small>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #FFB62A;">
            <small style="color: #cbd5e1; font-size: 11px; display: block; letter-spacing: 0.5px;">PERLU DI-FOLLOW UP</small>
            <h3 style="margin: 8px 0; font-size: 20px; font-weight: 700; color: #FFB62A;"><?= $pending_invoice; ?> <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Invoice</span></h3>
            <small style="color: #cbd5e1; opacity: 0.6; font-size: 11px;">Menunggu Pembayaran Tenant</small>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #10b981;">
            <small style="color: #cbd5e1; font-size: 11px; display: block; letter-spacing: 0.5px;">OTOMASI JURNAL BERHASIL</small>
            <h3 style="margin: 8px 0; font-size: 20px; font-weight: 700; color: #10b981;"><?= $total_jurnal; ?> <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Log</span></h3>
            <small style="color: #cbd5e1; opacity: 0.6; font-size: 11px;"><i class="fa-solid fa-book me-1"></i> Pembukuan Sukses</small>
        </div>
    </div>

    <div class="menu-panel">
        <h5 style="color: #FFB62A; font-weight: 600; font-size: 15px; margin-top: 0; margin-bottom: 5px;">
            <i class="fa-solid fa-folder-open me-2"></i> Konsol Kendali Fitur Operasional Staff (M06)
        </h5>
        <p style="color: #cbd5e1; font-size: 12px; margin-bottom: 20px;">Akses cepat entri data keuangan harian:</p>
        
        <div class="grid-control">
            <a href="invoiceManagement.php" class="btn-panel">
                <div>
                    <strong style="color: #00cfd5; display: block; font-size: 14px;"><i class="fa-solid fa-file-circle-plus me-2"></i> Invoicing & Kontrak</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Sinkronisasi data sewa dari modul M02</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #00cfd5; opacity: 0.7;"></i>
            </a>

            <a href="billingManagement.php" class="btn-panel">
                <div>
                    <strong style="color: #eab308; display: block; font-size: 14px;"><i class="fa-solid fa-cash-register me-2"></i> Billing & Pelunasan</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Pencatatan pembayaran cicilan & tagihan masuk</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #eab308; opacity: 0.7;"></i>
            </a>
            
            <a href="journalManagement.php" class="btn-panel">
                <div>
                    <strong style="color: #10b981; display: block; font-size: 14px;"><i class="fa-solid fa-book me-2"></i> Jurnal Otomatis</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Pemeriksaan mutasi entri jurnal harian</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #10b981; opacity: 0.7;"></i>
            </a>

            <a href="dashboardNonSewa.php" class="btn-panel">
                <div>
                    <strong style="color: #a855f7; display: block; font-size: 14px;"><i class="fa-solid fa-folder me-2"></i> Dashboard Non Sewa</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Manajemen pendapatan sekunder & utilitas mall</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #a855f7; opacity: 0.7;"></i>
            </a>
              <a href="vendor_bill.php" class="btn-panel">
                <div>
                    <strong style="color: #a855f7; display: block; font-size: 14px;"><i class="fa-solid fa-folder me-2"></i> Vendor Bill</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Vendor Bill</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #a855f7; opacity: 0.7;"></i>
            </a>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarM06.php'; 
?>
