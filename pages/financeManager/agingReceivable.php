<?php
session_start();
// Set role sebagai Finance Manager
$_SESSION['role'] = 'Finance Manager'; 
$_SESSION['nama'] = 'Intan (Manager)';

// 1. Panggil file koneksi terpusat
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/koneksi.php';
}

// 2. Panggil navbar dan header khusus manager
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// PBI-M06-01-03: Logika Menghitung Umur Piutang (Aging Receivable)
$query_aging = "SELECT no_invoice, nama_tenant, total_tagihan, sisa_tagihan, tanggal_jatuh_tempo,
                DATEDIFF(CURDATE(), tanggal_jatuh_tempo) as hari_terlambat
                FROM invoices 
                WHERE status = 'Belum Bayar' 
                ORDER BY hari_terlambat DESC";

$aging_data = false;
try {
    $aging_data = $conn->query($query_aging);
} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

// Siapkan variabel untuk total ringkasan dashboard manager
$total_belum_jatuh_tempo = 0;
$total_aging_1_30 = 0;
$total_aging_30_plus = 0;
?>

<div class="content-container">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: var(--h1); margin: 0;">Dashboard Analisis Umur Piutang</h1>
        <p style="color: #cbd5e1; margin: 5px 0 0 0; font-size: 14px;">PBI-M06-01-03 — Pemantauan Aging Piutang Tenant Terpusat (Finance Manager)</p>
    </div>

    <div class="row mb-4" style="display: flex; gap: 20px; margin-top: 20px;">
        <div style="flex: 1; background: #032b5c; padding: 20px; border-radius: 8px; border-left: 5px solid #00cfd5; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 0.8px; font-weight: 600;">BELUM JATUH TEMPO</h5>
            <h3 id="widget-lancar" style="color: #ffffff; margin: 10px 0 0 0; font-size: 28px; font-weight: 700;">Rp 0</h3>
        </div>
        <div style="flex: 1; background: #032b5c; padding: 20px; border-radius: 8px; border-left: 5px solid var(--accent); box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 0.8px; font-weight: 600;">MENUNGGAK 1 - 30 HARI</h5>
            <h3 id="widget-menengah" style="color: var(--accent); margin: 10px 0 0 0; font-size: 28px; font-weight: 700;">Rp 0</h3>
        </div>
        <div style="flex: 1; background: #032b5c; padding: 20px; border-radius: 8px; border-left: 5px solid #ff4d4d; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 0.8px; font-weight: 600;">MENUNGGAK > 30 HARI</h5>
            <h3 id="widget-macet" style="color: #ff4d4d; margin: 10px 0 0 0; font-size: 28px; font-weight: 700;">Rp 0</h3>
        </div>
    </div>

    <table class="table-custom" style="margin-top: 25px;">
        <thead>
            <tr>
                <th>No. Invoice</th>
                <th>Nama Tenant</th>
                <th>Sisa Piutang</th>
                <th>Tanggal Jatuh Tempo</th>
                <th>Status Keterlambatan</th>
                <th>Kategori Umur Piutang</th>
            </tr>
        </thead>
        <tbody>
            <?php if($aging_data && $aging_data->num_rows > 0): ?>
                <?php while($row = $aging_data->fetch_assoc()): 
                    $hari = $row['hari_terlambat'];
                    $sisa = $row['sisa_tagihan'];
                    
                    // Tentukan kategori dan warna teks berdasarkan hari keterlambatan
                    if ($hari <= 0) {
                        $kategori = "Belum Jatuh Tempo";
                        $badge_color = "bg-info text-white";
                        $total_belum_jatuh_tempo += $sisa;
                    } elseif ($hari > 0 && $hari <= 30) {
                        $kategori = "1 - 30 Hari";
                        $badge_color = "bg-warning text-dark";
                        $total_aging_1_30 += $sisa;
                    } else {
                        $kategori = "> 30 Hari (Macet)";
                        $badge_color = "bg-danger text-white";
                        $total_aging_30_plus += $sisa;
                    }
                ?>
                <tr>
                    <td><strong><?= $row['no_invoice']; ?></strong></td>
                    <td><?= $row['nama_tenant']; ?></td>
                    <td style="font-weight: 600; color: #ffffff;">Rp <?= number_format($sisa, 0, ',', '.'); ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
                    <td>
                        <?= $hari <= 0 ? '<span style="color: #10b981; font-weight: 500;">Lancar</span>' : '<span style="color: #f87171; font-weight: 500;">Terlambat ' . $hari . ' Hari</span>'; ?>
                    </td>
                    <td>
                        <span class="badge <?= $badge_color; ?>" style="padding: 6px 12px; font-size: 12px; border-radius: 20px; font-weight: 600;">
                            <?= $kategori; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding: 50px; color: #cbd5e1;">
        
                        <strong>Tidak ada piutang atau tagihan yang tertunggak saat ini.</strong><br>
                        <span style="color: #a0aec0; font-size: 13px;">(Klik tombol <strong>"Sinkronisasi Kontrak"</strong> di halaman Invoice Staff untuk mensimulasikan data tagihan baru)</span>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('widget-lancar').innerText = "Rp <?= number_format($total_belum_jatuh_tempo, 0, ',', '.'); ?>";
document.getElementById('widget-menengah').innerText = "Rp <?= number_format($total_aging_1_30, 0, ',', '.'); ?>";
document.getElementById('widget-macet').innerText = "Rp <?= number_format($total_aging_30_plus, 0, ',', '.'); ?>";
</script>

<?php require_once '../../includes/footer.php'; ?>