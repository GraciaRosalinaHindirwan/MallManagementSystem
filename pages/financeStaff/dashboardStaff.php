<?php
/** @var mysqli $conn */ 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeStaff') {
    header("Location: ../../index.php"); 
    exit();
}

// Sesi default sementara tetap dibiarkan di bawahnya agar aman dicoba sekarang
$_SESSION['role'] = 'financeStaff';
$_SESSION['nama'] = 'Staff';

// Panggil file koneksi terpusat
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// --- SINKRONISASI QUERY DASHBOARD DENGAN DB BARU MODUL 06 ---
// 1. Hitung total invoice yang sudah diterbitkan dari tabel 06_invoices
$res_total = $conn->query("SELECT COUNT(*) as jml FROM 06_invoices");
$total_invoice = ($res_total) ? $res_total->fetch_assoc()['jml'] : 0;

// 2. Hitung invoice yang Belum Bayar ('Unpaid') dari tabel 06_invoices
$res_pending = $conn->query("SELECT COUNT(*) as jml FROM 06_invoices WHERE status = 'Unpaid'");
$pending_invoice = ($res_pending) ? $res_pending->fetch_assoc()['jml'] : 0;

// 3. Hitung total entri jurnal dari tabel 06_journal_entries
$res_jurnal = $conn->query("SELECT COUNT(*) as jml FROM 06_journal_entries");
$total_jurnal = ($res_jurnal) ? $res_jurnal->fetch_assoc()['jml'] : 0;
?>

<div class="content-container">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 32px; font-weight: 700; margin: 0;">Finance Staff Workspace</h1>
        <p style="color: #cbd5e1; margin-top: 5px;">Pusat kendali operasional, invoicing, dan pembukuan harian mall.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 35px;">
        
        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #00cfd5; box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">TOTAL INVOICE</h5>
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;"><?= $total_invoice; ?> <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Data</span></h2>
            <span style="color: #00cfd5; font-size: 12px;">Telah diterbitkan di sistem M06</span>
        </div>

        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid var(--accent); box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">PERLU DI-FOLLOW UP</h5>
                <i class="fa-solid fa-hourglass-half" style="color: var(--accent);"></i>
            </div>
            <h2 style="color: var(--accent); margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;"><?= $pending_invoice; ?> <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Belum Bayar</span></h2>
            <span style="color: #cbd5e1; font-size: 12px;">Menunggu tindakan penagihan (Unpaid)</span>
        </div>

        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #10b981; box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">OTOMASI JURNAL</h5>
                <i class="fa-solid fa-book" style="color: #10b981;"></i>
            </div>
            <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;"><?= $total_jurnal; ?> <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Log</span></h2>
            <span style="color: #10b981; font-size: 12px;">Pembukuan otomatis sukses</span>
        </div>

    </div>

    <h4 style="color: #fff; margin-bottom: 20px; font-size: 18px; font-weight: 600;">Akses Cepat Fitur Operasional</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 10px;">
            <h5 style="color: var(--text-accent); margin: 0 0 10px 0;"><i class="fa-solid fa-file-circle-plus"></i> Invoicing & Kontrak</h5>
            <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0;">Sinkronisasi data sewa dari modul M02 dan cetak invoice baru tenant.</p>
            <a href="invoiceManagement.php" class="btn" style="background: #00cfd5; color: #021F42; font-weight: 600; font-size: 13px; padding: 8px 15px; border: none; text-decoration: none; display: inline-block; border-radius: 5px;">Buka Invoice Management</a>
        </div>

        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 10px;">
            <h5 style="color: var(--text-accent); margin: 0 0 10px 0;"><i class="fa-solid fa-cash-register"></i> Billing & Pelunasan</h5>
            <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0;">Proses pencatatan pembayaran cicilan atau pelunasan tagihan masuk.</p>
            <a href="billingManagement.php" class="btn" style="background: var(--accent); color: #021F42; font-weight: 600; font-size: 13px; padding: 8px 15px; border: none; text-decoration: none; display: inline-block; border-radius: 5px;">Buka Billing System</a>
        </div>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
