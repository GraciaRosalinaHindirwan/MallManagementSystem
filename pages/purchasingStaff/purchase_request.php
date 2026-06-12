<?php
session_start();
$_SESSION['role'] = 'Purchasing Staff';
$_SESSION['nama'] = 'Eva (Purchasing)';

// Panggil file koneksi terpusat
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// =============================================
// PBI-M06-03-01: BUAT PURCHASE REQUEST BARU
// =============================================
if (isset($_POST['tambah_pr'])) {
    $no_pr       = "PR-" . date('Ymd') . "-" . rand(100, 999);
    $nama_item   = $conn->real_escape_string($_POST['nama_item']);
    $jumlah      = (int) $_POST['jumlah'];
    $satuan      = $conn->real_escape_string($_POST['satuan']);
    $estimasi    = (int) $_POST['estimasi_harga'];
    $keperluan   = $conn->real_escape_string($_POST['keperluan']);
    $tgl_butuh   = $conn->real_escape_string($_POST['tgl_dibutuhkan']);
    $dibuat_oleh = $_SESSION['nama'];

    $sql = "INSERT INTO purchase_requests 
                (no_pr, nama_item, jumlah, satuan, estimasi_harga, keperluan, tgl_dibutuhkan, dibuat_oleh, status, tgl_dibuat)
            VALUES 
                ('$no_pr', '$nama_item', '$jumlah', '$satuan', '$estimasi', '$keperluan', '$tgl_butuh', '$dibuat_oleh', 'Menunggu Approval', NOW())";

    if ($conn->query($sql)) {
        echo "<script>alert('Purchase Request $no_pr berhasil dibuat!'); window.location='purchase_request.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan PR: " . $conn->error . "');</script>";
    }
}

// Hapus PR
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    $conn->query("DELETE FROM purchase_requests WHERE id_pr = $id_hapus AND status = 'Menunggu Approval'");
    echo "<script>window.location='purchase_request.php';</script>";
}

// Ambil semua data PR
$data_pr = $conn->query("SELECT * FROM purchase_requests ORDER BY id_pr DESC");
?>

<div class="content-container">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 32px; font-weight: 700; margin: 0;">
            Purchase Request <span style="font-size: 16px; color: #64748b;">(PBI-M06-03-01)</span>
        </h1>
        <p style="color: #cbd5e1; margin-top: 5px;">Buat dan kelola pengajuan kebutuhan pengadaan barang/jasa agar terdokumentasi.</p>
    </div>

    <!-- STATISTIK RINGKAS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <?php
            $total_pr    = $conn->query("SELECT COUNT(*) as jml FROM purchase_requests")->fetch_assoc()['jml'] ?? 0;
            $menunggu    = $conn->query("SELECT COUNT(*) as jml FROM purchase_requests WHERE status='Menunggu Approval'")->fetch_assoc()['jml'] ?? 0;
            $disetujui   = $conn->query("SELECT COUNT(*) as jml FROM purchase_requests WHERE status='Disetujui'")->fetch_assoc()['jml'] ?? 0;
            $ditolak     = $conn->query("SELECT COUNT(*) as jml FROM purchase_requests WHERE status='Ditolak'")->fetch_assoc()['jml'] ?? 0;
        ?>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #00cfd5;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">TOTAL PR</p>
            <h2 style="color:#fff; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $total_pr ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #FFB62A;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">MENUNGGU APPROVAL</p>
            <h2 style="color:#FFB62A; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $menunggu ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #22c55e;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">DISETUJUI</p>
            <h2 style="color:#22c55e; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $disetujui ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #ef4444;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">DITOLAK</p>
            <h2 style="color:#ef4444; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $ditolak ?></h2>
        </div>
    </div>

    <!-- FORM BUAT PR BARU -->
    <div style="background:#032b5c; padding:25px; border-radius:12px; margin-bottom:30px; border:1px solid rgba(255,255,255,0.06);">
        <h4 style="color:#FFB62A; margin-bottom:20px; font-size:16px;">
            <i class="fa-solid fa-plus-circle"></i> Buat Purchase Request Baru
        </h4>
        <form method="POST">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Nama Item / Jasa</label>
                    <input type="text" name="nama_item" required placeholder="contoh: Cat Tembok, Jasa Cleaning..."
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Keperluan / Keterangan</label>
                    <input type="text" name="keperluan" required placeholder="contoh: Renovasi Lantai 2, Operasional Harian..."
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Jumlah</label>
                    <input type="number" name="jumlah" required min="1" placeholder="contoh: 10"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Satuan</label>
                    <select name="satuan" style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                        <option value="Unit">Unit</option>
                        <option value="Pcs">Pcs</option>
                        <option value="Kg">Kg</option>
                        <option value="Liter">Liter</option>
                        <option value="Meter">Meter</option>
                        <option value="Paket">Paket</option>
                        <option value="Set">Set</option>
                    </select>
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Estimasi Harga (Rp)</label>
                    <input type="number" name="estimasi_harga" required min="0" placeholder="contoh: 5000000"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Tanggal Dibutuhkan</label>
                    <input type="date" name="tgl_dibutuhkan" required
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

            </div>

            <div style="margin-top:20px;">
                <button type="submit" name="tambah_pr"
                    style="background:#00cfd5; color:#021F42; font-weight:700; padding:10px 24px; border:none; border-radius:8px; font-size:14px; cursor:pointer;">
                    <i class="fa-solid fa-paper-plane"></i> Ajukan Purchase Request
                </button>
            </div>
        </form>
    </div>

    <!-- TABEL DAFTAR PR -->
    <h4 style="color:#fff; margin-bottom:15px; font-size:16px; font-weight:600;">
        <i class="fa-solid fa-list"></i> Daftar Purchase Request
    </h4>

    <table class="table-custom">
        <thead>
            <tr>
                <th>No. PR</th>
                <th>Nama Item</th>
                <th>Keperluan</th>
                <th>Jumlah</th>
                <th>Estimasi Harga</th>
                <th>Tgl Dibutuhkan</th>
                <th>Dibuat Oleh</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($data_pr && $data_pr->num_rows > 0): ?>
                <?php while ($row = $data_pr->fetch_assoc()): ?>
                <tr>
                    <td><strong style="color:#00cfd5;"><?= $row['no_pr'] ?></strong></td>
                    <td><?= htmlspecialchars($row['nama_item']) ?></td>
                    <td style="color:#cbd5e1; font-size:13px;"><?= htmlspecialchars($row['keperluan']) ?></td>
                    <td><?= $row['jumlah'] ?> <?= $row['satuan'] ?></td>
                    <td>Rp <?= number_format($row['estimasi_harga'], 0, ',', '.') ?></td>
                    <td><?= date('d M Y', strtotime($row['tgl_dibutuhkan'])) ?></td>
                    <td style="font-size:13px; color:#cbd5e1;"><?= $row['dibuat_oleh'] ?></td>
                    <td>
                        <?php
                            $status = $row['status'];
                            $warna  = match($status) {
                                'Disetujui'         => '#22c55e',
                                'Ditolak'           => '#ef4444',
                                'Menunggu Approval' => '#FFB62A',
                                default             => '#94a3b8',
                            };
                        ?>
                        <span style="background:<?= $warna ?>22; color:<?= $warna ?>; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; border:1px solid <?= $warna ?>44;">
                            <?= $status ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Menunggu Approval'): ?>
                            <a href="?hapus=<?= $row['id_pr'] ?>"
                               onclick="return confirm('Hapus PR ini?')"
                               style="background:#ef4444; color:#fff; padding:5px 12px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">
                               <i class="fa-solid fa-trash"></i> Hapus
                            </a>
                        <?php else: ?>
                            <span style="color:#64748b; font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:30px; color:#64748b;">
                        Belum ada Purchase Request. Silakan buat di atas!
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>
