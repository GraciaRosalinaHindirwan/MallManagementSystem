<?php
/** @var mysqli $conn */ 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Purchasing Manager') {
    header("Location: ../../index.php"); 
    exit();
}
*/

// Sesi default sementara agar aman dicoba dan tidak merusak sidebar
$_SESSION['role'] = 'Purchasing Manager';
$_SESSION['nama'] = 'Eva (Manager)';

// Panggil file koneksi terpusat menggunakan __DIR__ agar mutlak dan aman
if (file_exists(__DIR__ . '/../../config/konek.php')) {
    require_once __DIR__ . '/../../config/konek.php';
} elseif (file_exists(__DIR__ . '/../../config/connection.php')) {
    require_once __DIR__ . '/../../config/connection.php';
} else {
    die("<div style='color:#ffffff; background-color:#721c24; padding:20px; border-radius:6px;'>⚠️ File koneksi database tidak ditemukan!</div>");
}

$department_name = "Purchasing Department (Manager Workspace)";
$user_name = $_SESSION['nama'];

$menu_items = [
    [
        'icon'        => 'fa-solid fa-chart-line',
        'label'       => 'Dashboard Manager',
        'link'        => 'dashboardPurchasingmanager.php',
        'active_page' => 'dashboardPurchasingmanager' // Menyalakan menu ini jika file ini diakses
    ],
    [
        'icon'        => 'fa-solid fa-stamp',
        'label'       => 'Approval  Po',
        'link'        => 'approval_po.php',
        'active_page' => 'approval_po'
    ]
];

// Menggunakan 'approval_po' agar menyala pada item menu sidebar di navbarM06.php
$page_title = "approval_po"; 

// Mulai menangkap output visual komponen tengah halaman
ob_start();
?>

<div class="container-fluid" style="padding: 10px 0px; text-align: left;">
    <div class="mb-4">
        <h1 style="color: #FFB62A; font-size: 32px; font-weight: 700; margin: 0;">Purchasing Manager Workspace</h1>
        <p style="color: #cbd5e1; margin-top: 5px; font-size: 14px;">Pusat kendali persetujuan pengadaan barang, pengawasan logistik, dan validasi order belanja perusahaan.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 35px;">
        <div style="background: #011630; padding: 25px; border-radius: 8px; border-left: 5px solid #00cfd5; border: 1px solid rgba(255,255,255,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">PURCHASE REQUESTS</h5>
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">4 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Pengajuan</span></h2>
            <span style="color: #00cfd5; font-size: 12px;">Permintaan logistik masuk</span>
        </div>

        <div style="background: #011630; padding: 25px; border-radius: 8px; border-left: 5px solid #FFB62A; border: 1px solid rgba(255,255,255,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">TOTAL PURCHASE ORDER</h5>
                <i class="fa-solid fa-cart-shopping" style="color: #FFB62A;"></i>
            </div>
            <h2 style="color: #FFB62A; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">3 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Berkas</span></h2>
            <span style="color: #cbd5e1; font-size: 12px;">Dokumen PO belanja diterbitkan</span>
        </div>

        <div style="background: #011630; padding: 25px; border-radius: 8px; border-left: 5px solid #ef4444; border: 1px solid rgba(255,255,255,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">PO MENUNGGU APPROVAL</h5>
                <i class="fa-solid fa-clock" style="color: #ef4444;"></i>
            </div>
            <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">0 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Pending</span></h2>
            <span style="color: #ef4444; font-size: 12px;">Memerlukan tindakan Manager</span>
        </div>
    </div>

    <h4 style="color: #fff; margin-bottom: 20px; font-size: 16px; font-weight: 600;">Akses Cepat Fitur Manajemen</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: #011630; border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 8px;">
            <h5 style="color: #00cfd5; margin: 0 0 10px 0; font-weight: 600;"><i class="fa-solid fa-file-lines me-2"></i> Purchase Requests (Staff View)</h5>
            <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0; line-height: 1.5;">Tinjau rekam berkas pengajuan pengadaan barang/jasa operasional tenant & manajemen mall.</p>
            <a href="../purchasingStaff/purchase_requests.php" style="background: #00cfd5; color: #021F42; font-weight: 700; font-size: 13px; padding: 10px 16px; border: none; text-decoration: none; display: inline-block; border-radius: 6px;">Buka Purchase Requests</a>
        </div>

        <div style="background: #011630; border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 8px;">
            <h5 style="color: #FFB62A; margin: 0 0 10px 0; font-weight: 600;"><i class="fa-solid fa-user-check me-2"></i> Approval Purchase Orders</h5>
            <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0; line-height: 1.5;">Lakukan validasi persetujuan (Approve) atau penolakan (Reject) dokumen pesanan belanja logistik bernilai tinggi.</p>
            <a href="approval_po.php" style="background: #FFB62A; color: #021F42; font-weight: 700; font-size: 13px; padding: 10px 16px; border: none; text-decoration: none; display: inline-block; border-radius: 6px;">Buka Approval PO</a>
        </div>
    </div>
</div>

<?php 
// Melempar buffer isi konten ke komponen master template navbarM06.php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarM06.php'; 
?>