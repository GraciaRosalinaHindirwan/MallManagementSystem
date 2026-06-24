<?php
session_start();

// Panggil file koneksi terpusat
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

require_once '../../includes/header.php';
?>

<!-- FORCE SIDEBAR CUSTOM UNTUK PURCHASING STAFF -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebarNav = document.querySelector('#sidebarMenu .mt-3');
        if (sidebarNav) {
            sidebarNav.innerHTML = `
                <a href="purchase_requests.php" class="nav-sidebar-item">
                    <i class="fa-solid fa-file-invoice"></i> Purchase Requests
                </a>
                <a href="purchase_orders.php" class="nav-sidebar-item">
                    <i class="fa-solid fa-cart-shopping"></i> Purchase Orders
                </a>
            `;
        }

        // Ubah teks info instansi di navbar top
        const brandText = document.querySelector('.navbar-brand style + span, .navbar-brand span');
        if (brandText) brandText.textContent = "— M06 - Purchasing Staff";
    });
</script>

<?php require_once '../../includes/navbar.php'; ?>

<!-- content-wrapper menjamin konten turun 75px dan bergeser otomatis ke kanan saat sidebar buka -->
<div class="content-wrapper">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 32px; font-weight: 700; margin: 0;">Purchasing Staff Workspace</h1>
        <p style="color: #cbd5e1; margin-top: 5px;">Pusat kendali pengadaan barang, permintaan logistik, dan order ke vendor mall.</p>
    </div>

    <!-- Kotak Ringkasan Statistik Logistik -->
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

        <div style="background: #032b5c; padding: 25px; border-radius: 15px; border-left: 5px solid #ef4444; box-shadow: 0 10px 15px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center; color: #a0aec0;">
                <h5 style="font-size: 12px; margin: 0; letter-spacing: 1px;">PO MENUNGGU APPROVAL</h5>
                <i class="fa-solid fa-clock" style="color: #ef4444;"></i>
            </div>
            <h2 style="color: #fff; margin: 15px 0 5px 0; font-size: 28px; font-weight: 700;">0 <span style="font-size: 14px; font-weight: 400; color: #cbd5e1;">Pending</span></h2>
            <span style="color: #ef4444; font-size: 12px;">Menunggu tindakan Manager</span>
        </div>
    </div>

    <!-- Akses Menu Utama -->
    <h4 style="color: #fff; margin-bottom: 20px; font-size: 18px; font-weight: 600;">Akses Cepat Fitur Operasional Staff</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 10px;">
            <h5 style="color: var(--text-accent); margin: 0 0 10px 0;"><i class="fa-solid fa-file-lines"></i> Purchase Requests</h5>
            <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0;">Kelola dan buat berkas pengajuan pengadaan barang/jasa operasional tenant & manajemen mall.</p>
            <a href="purchase_requests.php" class="btn" style="background: #00cfd5; color: #021F42; font-weight: 600; font-size: 13px; padding: 8px 15px; border: none; text-decoration: none; display: inline-block; border-radius: 5px;">Buka Purchase Requests</a>
        </div>

        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 20px; border-radius: 10px;">
            <h5 style="color: var(--text-accent); margin: 0 0 10px 0;"><i class="fa-solid fa-truck-ramp-box"></i> Purchase Orders</h5>
            <p style="color: #cbd5e1; font-size: 13px; margin: 0 0 15px 0;">Terbitkan pesanan resmi barang ke vendor eksternal, tracking pengiriman logistik, dan cetak invoice.</p>
            <a href="purchase_orders.php" class="btn" style="background: var(--accent); color: #021F42; font-weight: 600; font-size: 13px; padding: 8px 15px; border: none; text-decoration: none; display: inline-block; border-radius: 5px;">Buka Purchase Orders</a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
