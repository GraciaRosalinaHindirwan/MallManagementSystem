<?php
session_start();
$_SESSION['role'] = 'Finance Staff'; 
$_SESSION['nama'] = 'Intan (Staff)';

// 1. Cek otomatis file koneksi di folder config agar tidak fatal error
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} elseif (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    die("<div style='color:red; padding:20px;'>⚠️ File koneksi database (.php) tidak ditemukan di folder config!</div>");
}

// 2. Memanggil komponen desain navbar dan header
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// Ambil data jurnal dengan teknik JOIN tiga tabel sekaligus (PBI-M06-04-01)
$query_jurnal = "SELECT j.*, jd.debit, jd.kredit, c.nama_akun, c.kode_akun 
                 FROM jurnal j 
                 JOIN jurnal_detail jd ON j.id_jurnal = jd.id_jurnal 
                 JOIN coa c ON jd.id_coa = c.id_coa 
                 ORDER BY j.id_jurnal DESC";

$jurnals = false;
try {
    $jurnals = $conn->query($query_jurnal);
} catch (Exception $e) {
    $error_msg = $e->getMessage();
}
?>

<div class="content-container">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: var(--h1); margin: 0;">Log Otomasi Jurnal</h1>
        <p class="text-muted m-0">PBI-M06-04-01 — Riwayat Penjurnalan Otomatis (Double-Entry System)</p>
    </div>
    
    <table class="table-custom">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No Bukti / Invoice</th>
                <th>Keterangan Transaksi</th>
                <th>Kode Akun</th>
                <th>Nama Akun Akuntansi</th>
                <th>Debit</th>
                <th>Kredit</th>
            </tr>
        </thead>
        <tbody>
            <?php if($jurnals && $jurnals->num_rows > 0): ?>
                <?php while($row = $jurnals->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($row['tanggal_jurnal'])); ?></td>
                    <td><span class="badge bg-secondary"><?= $row['no_bukti']; ?></span></td>
                    <td><?= $row['keterangan']; ?></td>
                    <td><code><?= $row['kode_akun']; ?></code></td>
                    <td><?= $row['nama_akun']; ?></td>
                    <td class="text-success" style="font-weight: 600;">
                        <?= $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-'; ?>
                    </td>
                    <td class="text-warning" style="font-weight: 600;">
                        <?= $row['kredit'] > 0 ? 'Rp ' . number_format($row['kredit'], 0, ',', '.') : '-'; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding: 50px;">
                        <span style="font-size: 30px;">📭</span> <br><br>
                        <strong>Belum ada log jurnal otomatis yang terbentuk.</strong><br>
                        <span class="text-secondary">Jurnal otomatis akan terisi setelah kamu melakukan simulasi klik 
                        <strong class="text-success">"Pelunasan"</strong> pada invoice di halaman Billing!</span>
                        <?php if(isset($error_msg)): ?>
                            <br><br><span class="badge bg-danger">Info Error: Tabel Jurnal belum kamu masukkan ke phpMyAdmin!</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>