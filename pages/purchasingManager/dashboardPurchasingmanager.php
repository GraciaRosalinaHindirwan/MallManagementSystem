<?php
session_start();

if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

require_once '../../includes/header.php';
?>

<!-- FORCE SIDEBAR CUSTOM UNTUK PURCHASING MANAGER -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebarNav = document.querySelector('#sidebarMenu .mt-3');
        if (sidebarNav) {
            sidebarNav.innerHTML = `
                <a href="approval_po.php" class="nav-sidebar-item">
                    <i class="fa-solid fa-user-check"></i> Approval PO
                </a>
            `;
        }

        // Ubah teks info instansi di navbar top
        const brandText = document.querySelector('.navbar-brand style + span, .navbar-brand span');
        if (brandText) brandText.textContent = "— M06 - Purchasing Manager";
    });
</script>

<?php require_once '../../includes/navbar.php'; ?>

<!-- content-wrapper menjamin konten turun 75px dan bergeser otomatis ke kanan saat sidebar buka -->
<div class="content-wrapper">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 32px; font-weight: 700; margin: 0;">Purchasing Manager Command Center</h1>
        <p style="color: #cbd5e1; margin-top: 5px;">Panel otorisasi keuangan pengadaan, penandatanganan dokumen belanja digital mall.</p>
    </div>

    <!-- Kotak Ringkasan Statistik Manager -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 35px;">
        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #FFB62A; box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">MENUNGGU KEPUTUSAN</h5>
                <i class="fa-solid fa-hourglass-start" style="color: #FFB62A;"></i>
            </div>
            <h2 style="color: #FFB62A; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">0 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Berkas</span></h2>
            <span style="color: #cbd5e1; font-size: 12px;">Segera tinjau pengajuan anggaran</span>
        </div>

        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #22c55e; box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">TELAH DISETUJUI</h5>
                <i class="fa-solid fa-circle-check" style="color: #22c55e;"></i>
            </div>
            <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">3 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">PO Selesai</span></h2>
            <span style="color: #22c55e; font-size: 12px;">Diteruskan ke departemen Finance</span>
        </div>

        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #ef4444; box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">DITOLAK / REJECTED</h5>
                <i class="fa-solid fa-ban" style="color: #ef4444;"></i>
            </div>
            <h2 style="color: #ef4444; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">0 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Berkas</span></h2>
            <span style="color: #cbd5e1; font-size: 12px;">Pengajuan tidak lolos kriteria</span>
        </div>
    </div>

    <!-- Akses Utama Fitur Manager -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,182,42,0.2); padding: 25px; border-radius: 12px;">
            <h5 style="color: #FFB62A; margin: 0 0 10px 0; font-size: 16px;"><i class="fa-solid fa-user-shield"></i> Validasi Dokumen & Approval PO</h5>
            <p style="color: #cbd5e1; font-size: 14px; margin: 0 0 20px 0;">Menu utama untuk melakukan peninjauan nominal harga belanja, pengisian komentar umpan balik, serta eksekusi status disetujui/ditolak.</p>
            <a href="approval_po.php" class="btn" style="background: #FFB62A; color: #021F42; font-weight: 700; font-size: 13px; padding: 10px 20px; border: none; text-decoration: none; display: inline-block; border-radius: 6px;">Buka Sistem Otorisasi & Approval</a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?><?php
                                                    /** @var mysqli $conn */

                                                    if (session_status() == PHP_SESSION_NONE) {
                                                        session_start();
                                                    }

                                                    // ====================================================================
                                                    // SECURE AUTH CHECK - JIKA BELUM LOGIN / ROLE BUKAN PURCHASING MANAGER
                                                    // ====================================================================
                                                    // Hilangkan tanda komentar (/* dan */) di bawah ini jika database & sistem login Anda sudah siap.
                                                    /*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Purchasing Manager') {
    header("Location: ../../index.php"); 
    exit();
}
*/
                                                    // ====================================================================

                                                    // Sesi default sementara agar aman dicoba dan tidak merusak sidebar
                                                    $_SESSION['role'] = 'Purchasing Manager';
                                                    $_SESSION['nama'] = 'Eva (Manager)';


                                                    // -------------------------------------------------------------------------
                                                    // LANJUTAN KODE ASLI DASHBOARD (DISESUAIKAN UNTUK ROLE MANAGER)
                                                    // -------------------------------------------------------------------------
                                                    // Panggil file koneksi terpusat
                                                    if (file_exists('../../config/koneksi.php')) {
                                                        require_once '../../config/koneksi.php';
                                                    } else {
                                                        require_once '../../config/connection.php';
                                                    }

                                                    require_once '../../includes/header.php';
                                                    ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebarNav = document.querySelector('#sidebarMenu .mt-3');
        if (sidebarNav) {
            sidebarNav.innerHTML = `
                <a href="../purchasingStaff/purchase_requests.php" class="nav-sidebar-item">
                    <i class="fa-solid fa-file-invoice"></i> Purchase Requests
                </a>
                <a href="approval_po.php" class="nav-sidebar-item">
                    <i class="fa-solid fa-user-check"></i> Approval PO
                </a>
            `;
        }

        // Ubah teks info instansi di navbar top menjadi Manager
        const brandText = document.querySelector('.navbar-brand style + span, .navbar-brand span');
        if (brandText) brandText.textContent = "— M06 - Purchasing Manager";
    });
</script>

<?php require_once '../../includes/navbar.php'; ?>

<div class="content-wrapper">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 32px; font-weight: 700; margin: 0;">Purchasing Manager Workspace</h1>
        <p style="color: #cbd5e1; margin-top: 5px;">Pusat kendali persetujuan pengadaan barang, pengawasan logistik, dan validasi order belanja perusahaan.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 35px;">
        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #00cfd5; box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">PURCHASE REQUESTS</h5>
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">4 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Pengajuan</span></h2>
            <span style="color: #00cfd5; font-size: 12px;">Permintaan logistik masuk</span>
        </div>

        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid var(--accent); box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">TOTAL PURCHASE ORDER</h5>
                <i class="fa-solid fa-cart-shopping" style="color: var(--accent);"></i>
            </div>
            <h2 style="color: var(--accent); margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">3 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Berkas</span></h2>
            <span style="color: #cbd5e1; font-size: 12px;">Dokumen PO belanja diterbitkan</span>
        </div>

        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #FFB62A; box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">PO MENUNGGU APPROVAL</h5>
                <i class="fa-solid fa-clock" style="color: #FFB62A;"></i>
            </div>
            <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">0 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Pending</span></h2>
            <span style="color: #FFB62A; font-size: 12px;">Memerlukan tindakan Manager</span>
        </div>
    </div>

    <h4 style="color: #fff; margin-bottom: 20px; font-size: 18px; font-weight: 600;">Akses Cepat Fitur Manajemen</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 10px;">
            <h5 style="color: var(--text-accent); margin: 0 0 10px 0;"><i class="fa-solid fa-file-lines"></i> Purchase Requests (Staff View)</h5>
            <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0;">Tinjau rekam berkas pengajuan pengadaan barang/jasa operasional tenant & manajemen mall.</p>
            <a href="../purchasingStaff/purchase_requests.php" class="btn" style="background: #00cfd5; color: #021F42; font-weight: 600; font-size: 13px; padding: 8px 15px; border: none; text-decoration: none; display: inline-block; border-radius: 5px;">Buka Purchase Requests</a>
        </div>

        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 10px;">
            <h5 style="color: var(--text-accent); margin: 0 0 10px 0;"><i class="fa-solid fa-user-check"></i> Approval Purchase Orders</h5>
            <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0;">Lakukan validasi persetujuan (Approve) atau penolakan (Reject) dokumen pesanan belanja logistik bernilai tinggi.</p>
            <a href="approval_po.php" class="btn" style="background: #FFB62A; color: #021F42; font-weight: 600; font-size: 13px; padding: 8px 15px; border: none; text-decoration: none; display: inline-block; border-radius: 5px;">Buka Approval PO</a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>