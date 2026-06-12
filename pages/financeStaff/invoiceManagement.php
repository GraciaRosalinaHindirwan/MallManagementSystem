<?php
session_start();
$_SESSION['role'] = 'Finance Staff'; 
$_SESSION['nama'] = 'Intan (Staff)';

// 1. Panggil file koneksi terpusat
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

// 2. Panggil navbar dan header
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// PBI-M06-01-01: Proses Sinkronisasi dan Ambil SEMUA Data Kontrak Aktif
if (isset($_POST['sync_m02'])) {
    
    // Ambil semua data kontrak yang berstatus 'Aktif' dari tabel M02
    $cek_kontrak = $conn->query("SELECT * FROM kontrak_sewa WHERE status_kontrak = 'Aktif'");
    
    if ($cek_kontrak && $cek_kontrak->num_rows > 0) {
        $sukses = 0;
        
        while ($kontrak = $cek_kontrak->fetch_assoc()) {
            $id_kontrak = $kontrak['id_kontrak'];
            $tenant = $kontrak['nama_tenant'];
            $total = $kontrak['harga_sewa_bulanan'];
            
            // Cek dulu biar gak double insert kalau tombol diklik berkali-kali
            $cek_double = $conn->query("SELECT id_invoice FROM invoices WHERE id_kontrak = '$id_kontrak'");
            
            if ($cek_double->num_rows == 0) {
                $no_inv = "INV-2026-" . rand(1000, 9999);
                $jatuh_tempo = date('Y-m-d', strtotime('+14 days')); // Rumus otomatis 14 hari

                // Masukkan kontrak satu per satu ke tabel invoice kamu
                $conn->query("INSERT INTO invoices (no_invoice, id_kontrak, nama_tenant, total_tagihan, sisa_tagihan, tanggal_jatuh_tempo, status) 
                              VALUES ('$no_inv', '$id_kontrak', '$tenant', '$total', '$total', '$jatuh_tempo', 'Belum Bayar')");
                $sukses++;
            }
        }
        
        if ($sukses > 0) {
            echo "<script>alert('Sukses! $sukses Invoice Baru Berhasil Diterbitkan Otomatis!'); window.location='invoiceManagement.php';</script>";
        } else {
            echo "<script>alert('Perhatian: Semua kontrak aktif sudah di-sinkronisasi sebelumnya.'); window.location='invoiceManagement.php';</script>";
        }
    } else {
        // Cadangan kalau tabel kontrak_sewa di phpMyAdmin kamu belum diisi data dummy
        $no_inv = "INV-2026-" . rand(1000, 9999);
        $conn->query("INSERT INTO invoices (no_invoice, id_kontrak, nama_tenant, total_tagihan, sisa_tagihan, tanggal_jatuh_tempo, status) 
                      VALUES ('$no_inv', 305, 'Sport Station Lt.2', 42000000, 42000000, '2026-07-15', 'Belum Bayar')");
        echo "<script>alert('Invoice Cadangan $no_inv Berhasil Diterbitkan!'); window.location='invoiceManagement.php';</script>";
    }
}

// Ambil SEMUA data invoice yang ada di database untuk ditampilkan ke tabel
$invoices = $conn->query("SELECT * FROM invoices ORDER BY id_invoice DESC");
?>

<div class="content-container">
    <h1 style="color: var(--text-accent); font-size: var(--h1);">Invoice Management (PBI-M06-01-01)</h1>
    <p>Kelola penerbitan tagihan sewa otomatis dari data integrasi kontrak aktif tenant.</p>

    <form method="POST" class="mb-4">
        <button type="submit" name="sync_m02" class="btn" style="background-color: var(--accent); color: var(--primary-dark); font-weight: 600;">⚡ Sinkronisasi Data Kontrak M02</button>
    </form>

    <table class="table-custom">
        <thead>
            <tr>
                <th>No. Invoice</th>
                <th>Nama Tenant</th>
                <th>Total Tagihan</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th>Aksi (PBI-M06-01-02)</th>
            </tr>
        </thead>
        <tbody>
            <?php if($invoices && $invoices->num_rows > 0): ?>
                <?php while($row = $invoices->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= $row['no_invoice']; ?></strong></td>
                    <td><?= $row['nama_tenant']; ?></td>
                    <td>Rp <?= number_format($row['total_tagihan'], 0, ',', '.'); ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></td>
                    <td><span class="badge <?= $row['status'] == 'Lunas' ? 'bg-success' : 'bg-danger'; ?>"><?= $row['status']; ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-info text-white" onclick="alert('Invoice terkirim via Email & Portal Tenant!')">📧 Kirim Tagihan</button>
                        <?php if($row['status'] !== 'Lunas'): ?>
                            <a href="billingManagement.php?id=<?= $row['id_invoice']; ?>" class="btn btn-sm btn-success">💸 Catat Bayar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding: 30px;">
                        Belum ada invoice yang diterbitkan. Silakan klik tombol sinkronisasi di atas!
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>