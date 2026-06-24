<?php
session_start();
require_once '../../config/konek.php';

$alertMsg  = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'kembalikan') {
        $id = (int) $_POST['id'];
        $stmt = $conn->prepare("UPDATE 05_lost_reports SET status = 'Dikembalikan' WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $alertMsg  = 'Status berhasil diubah menjadi Dikembalikan.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Gagal mengubah status.';
            $alertType = 'danger';
        }
        $stmt->close();
    } else {
        $nama_pelapor     = htmlspecialchars(strip_tags(trim($_POST['nama_pelapor']     ?? '')));
        $item_description = htmlspecialchars(strip_tags(trim($_POST['item_description'] ?? '')));
        $contact_number   = htmlspecialchars(strip_tags(trim($_POST['contact_number']   ?? '')));

        $stmt = $conn->prepare("INSERT INTO 05_lost_reports (nama_pelapor, item_description, contact_number) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $nama_pelapor, $item_description, $contact_number);

        if ($stmt->execute()) {
            $alertMsg  = 'Laporan kehilangan berhasil disimpan.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Gagal menyimpan data.';
            $alertType = 'danger';
        }
        $stmt->close();
    }
}

$result  = $conn->query("SELECT * FROM 05_lost_reports ORDER BY reported_at DESC");
$reports = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$page_title = 'Laporan Kehilangan';

ob_start();
?>

<?php if ($alertMsg): ?>
<div class="card" style="border-left: 4px solid <?= $alertType === 'success' ? '#22C55E' : '#EF4444' ?>; margin-bottom: 16px;">
    <p style="color: <?= $alertType === 'success' ? '#22C55E' : '#EF4444' ?>; margin: 0;">
        <?= $alertMsg ?>
    </p>
</div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">Input Laporan Kehilangan</h2>
    <p style="color: rgba(245,247,250,0.5); font-size: 13px; margin-bottom: 20px;">Catat laporan kehilangan barang dari pengunjung.</p>

    <form method="POST">
        <div class="form-group">
            <label>Nama Pelapor <span style="color:#EF4444">*</span></label>
            <input type="text" name="nama_pelapor" required placeholder="Contoh: Budi Santoso" />
        </div>
        <div class="form-group">
            <label>Deskripsi Barang Hilang <span style="color:#EF4444">*</span></label>
            <textarea name="item_description" rows="4" required placeholder="Contoh: Tas ransel warna biru, berisi laptop..."></textarea>
        </div>
        <div class="form-group">
            <label>Nomor Kontak Pelapor <span style="color:#EF4444">*</span></label>
            <input type="text" name="contact_number" required placeholder="Contoh: 08123456789" />
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-plus-circle"></i> Simpan
        </button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">Daftar Laporan Kehilangan</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pelapor</th>
                    <th>Deskripsi Barang</th>
                    <th>Kontak</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $i => $report): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= $report['nama_pelapor'] ?></td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= $report['item_description'] ?></td>
                    <td><?= $report['contact_number'] ?></td>
                    <td>
                        <span class="badge <?= $report['status'] === 'Dikembalikan' ? 'badge-success' : 'badge-warning' ?>">
                            <?= $report['status'] ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($report['reported_at'])) ?></td>
                    <td>
                        <?php if ($report['status'] !== 'Dikembalikan'): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $report['id'] ?>">
                            <button type="submit" name="action" value="kembalikan" class="btn" style="background:rgba(34,197,94,0.2); color:#22C55E; border:1px solid rgba(34,197,94,0.3); padding:4px 12px; font-size:12px;">
                                <i class="fa-solid fa-check-circle"></i> Dikembalikan
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:rgba(245,247,250,0.3); font-size:12px;">Selesai</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:rgba(245,247,250,0.3); padding:24px;">Belum ada laporan kehilangan.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../../includes/navbarM05.php';
?>