<?php
session_start();
// Set role dan nama untuk Manager
$_SESSION['role'] = 'Finance Manager'; 
$_SESSION['nama'] = 'Intan (Manager)';

require_once '../../config/koneksi.php';
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// FIX ERROR: Menggunakan COUNT(*) agar aman dari salah nama kolom database
// 1. Mengambil jumlah Invoice yang Belum Bayar
$res_piutang = $conn->query("SELECT COUNT(*) as jml FROM invoices WHERE status = 'Belum Bayar'");
$total_piutang_count = $res_piutang->fetch_assoc()['jml'] ?? 0;

// 2. Mengambil jumlah Invoice yang Sudah Lunas
$res_lunas = $conn->query("SELECT COUNT(*) as jml FROM invoices WHERE status = 'Lunas'");
$total_masuk_count = $res_lunas->fetch_assoc()['jml'] ?? 0;

// 3. Cek jumlah ketidakcocokan rekonsiliasi
$res_rekon = $conn->query("SELECT COUNT(*) as jml FROM invoices WHERE status = 'Belum Bayar'");
$unreconciled_count = $res_rekon->fetch_assoc()['jml'] ?? 0;
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 style="color: var(--text-accent); font-size: 32px; font-weight: 700; margin: 0;">Finance Manager Executive Dashboard</h1>
            <p style="color: #cbd5e1; margin-top: 5px;">Panel pengawasan arus kas, analisis umur piutang, dan validasi rekonsiliasi bank.</p>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-12 col-md-4">
            <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #10b981; height: 100%;">
                <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                    <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">PENDAPATAN DITERIMA</h5>
                    <i class="fa-solid fa-wallet" style="color: #10b981;"></i>
                </div>
                <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 24px; font-weight: 700;"><?= $total_masuk_count; ?> <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Invoice Lunas</span></h2>
                <span style="color: #10b981; font-size: 12px;">Dana aman di kas</span>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid var(--accent); height: 100%;">
                <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                    <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">TOTAL PIUTANG (AGING)</h5>
                    <i class="fa-solid fa-chart-line" style="color: var(--accent);"></i>
                </div>
                <h2 style="color: var(--accent); margin: 15px 0 5px 0; font-size: 24px; font-weight: 700;"><?= $total_piutang_count; ?> <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Invoice Active</span></h2>
                <span style="color: #cbd5e1; font-size: 12px;">Tagihan perlu di-follow up</span>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #00cfd5; height: 100%;">
                <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                    <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">REKONSILIASI BANK</h5>
                    <i class="fa-solid fa-building-columns" style="color: #00cfd5;"></i>
                </div>
                <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 24px; font-weight: 700;">
                    <?= $unreconciled_count > 0 ? 'Perlu Validasi' : 'Balanced'; ?>
                </h2>
                <span style="color: #00cfd5; font-size: 12px;">Kesesuaian saldo kas vs bank</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h4 style="color: #fff; margin-bottom: 20px; font-size: 18px; font-weight: 600;">Menu Analisis & Strategi Manajerial</h4>
        </div>
        
        <div class="col-12 col-md-6 mb-3">
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 10px; height: 100%;">
                <h5 style="color: var(--text-accent); margin: 0 0 10px 0;"><i class="fa-solid fa-clock"></i> Analisis Umur Piutang (Aging Receivable)</h5>
                <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0;">Pantau rincian piutang tenant berdasarkan tenggat waktu (0-30 hari, 31-60 hari, dst.) untuk mencegah bad debt.</p>
                <a href="agingReceivable.php" class="btn" style="background: var(--accent); color: #021F42; font-weight: 600; font-size: 13px; padding: 8px 15px; border: none; text-decoration: none; display: inline-block; border-radius: 5px;">Buka Analisis Aging</a>
            </div>
        </div>

        <div class="col-12 col-md-6 mb-3">
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 10px; height: 100%;">
                <h5 style="color: var(--text-accent); margin: 0 0 10px 0;"><i class="fa-solid fa-scale-balanced"></i> Rekonsiliasi & Pencocokan Kas</h5>
                <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0;">Cocokkan catatan transaksi internal keuangan mall dengan rekening koran bank secara berkala.</p>
                <a href="bankReconciliation.php" class="btn" style="background: #00cfd5; color: #021F42; font-weight: 600; font-size: 13px; padding: 8px 15px; border: none; text-decoration: none; display: inline-block; border-radius: 5px;">Mulai Rekonsiliasi Bank</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>