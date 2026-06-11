<?php
$page_title = 'Pengajuan Cuti';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

// Approve / Tolak
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $status_baru = $_GET['aksi'] === 'approve' ? 'disetujui' : 'ditolak';
    $pdo->prepare("UPDATE cuti SET status=? WHERE id=?")->execute([$status_baru, (int)$_GET['id']]);
    header("Location: index.php?msg={$_GET['aksi']}"); exit;
}

$filter = $_GET['filter'] ?? 'semua';
$where  = $filter !== 'semua' ? "WHERE c.status = '$filter'" : '';
$sql = "SELECT c.*, p.nama, p.jabatan FROM cuti c JOIN pegawai p ON c.pegawai_id = p.id $where ORDER BY c.created_at DESC";
$cuti = $pdo->query($sql)->fetchAll();

$pegawais = $pdo->query("SELECT id, nama FROM pegawai WHERE status='aktif' ORDER BY nama")->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i>
    <?= $_GET['msg']==='tambah'?'Pengajuan cuti berhasil dikirim!':($_GET['msg']==='approve'?'Cuti disetujui!':'Cuti ditolak!') ?>
</div>
<?php endif; ?>

<!-- FORM PENGAJUAN CUTI -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Ajukan Cuti</h2>
    </div>
    <form method="POST" action="tambah.php">
        <div class="form-grid">
            <div class="form-group">
                <label>Pegawai <span style="color:var(--danger)">*</span></label>
                <select name="pegawai_id" required>
                    <option value="">-- Pilih Pegawai --</option>
                    <?php foreach ($pegawais as $pg): ?>
                    <option value="<?= $pg['id'] ?>"><?= htmlspecialchars($pg['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jenis Cuti <span style="color:var(--danger)">*</span></label>
                <select name="jenis_cuti" required>
                    <option value="tahunan">Tahunan</option>
                    <option value="sakit">Sakit</option>
                    <option value="melahirkan">Melahirkan</option>
                    <option value="darurat">Darurat</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tanggal Mulai <span style="color:var(--danger)">*</span></label>
                <input type="date" name="tgl_mulai" required>
            </div>
            <div class="form-group">
                <label>Tanggal Selesai <span style="color:var(--danger)">*</span></label>
                <input type="date" name="tgl_selesai" required>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>Alasan <span style="color:var(--danger)">*</span></label>
                <textarea name="alasan" rows="3" placeholder="Tuliskan alasan pengajuan cuti..." required></textarea>
            </div>
        </div>
        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Kirim Pengajuan</button>
        </div>
    </form>
</div>

<!-- TABEL CUTI -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Pengajuan Cuti</h2>
        <div style="display:flex; gap:8px;">
            <?php foreach (['semua','pending','disetujui','ditolak'] as $f): ?>
            <a href="?filter=<?= $f ?>" class="btn btn-sm <?= $filter===$f?'btn-primary':'btn-outline' ?>">
                <?= ucfirst($f) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Pegawai</th><th>Jenis</th><th>Mulai</th><th>Selesai</th><th>Durasi</th><th>Alasan</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if ($cuti): ?>
                    <?php foreach ($cuti as $i => $c): ?>
                    <?php
                        $durasi = (new DateTime($c['tgl_mulai']))->diff(new DateTime($c['tgl_selesai']))->days + 1;
                        $badge_map = ['pending'=>'badge-warning','disetujui'=>'badge-success','ditolak'=>'badge-danger'];
                    ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($c['nama']) ?></td>
                        <td><span class="badge badge-info"><?= ucfirst($c['jenis_cuti']) ?></span></td>
                        <td><?= date('d M Y', strtotime($c['tgl_mulai'])) ?></td>
                        <td><?= date('d M Y', strtotime($c['tgl_selesai'])) ?></td>
                        <td><?= $durasi ?> hari</td>
                        <td><?= htmlspecialchars(substr($c['alasan'],0,40)) . (strlen($c['alasan'])>40?'...':'') ?></td>
                        <td><span class="badge <?= $badge_map[$c['status']] ?>"><?= ucfirst($c['status']) ?></span></td>
                        <td>
                            <?php if ($c['status'] === 'pending'): ?>
                            <a href="?aksi=approve&id=<?= $c['id'] ?>" class="btn btn-sm btn-primary" onclick="return confirm('Setujui cuti ini?')">
                                <i class="fa-solid fa-check"></i>
                            </a>
                            <a href="?aksi=tolak&id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tolak cuti ini?')">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                            <?php else: ?>
                            <span style="color:rgba(255,255,255,0.3); font-size:var(--caption);">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9">
                        <div class="empty-state"><i class="fa-solid fa-umbrella-beach"></i><p>Tidak ada pengajuan cuti</p></div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>
