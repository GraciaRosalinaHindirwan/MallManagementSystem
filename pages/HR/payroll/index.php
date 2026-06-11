<?php
$page_title = 'Payroll';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

$bulan = (int)($_GET['bulan'] ?? date('m'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$sql = "SELECT py.*, p.nama, p.jabatan,
        (SELECT COUNT(*) FROM absensi a WHERE a.pegawai_id=py.pegawai_id AND a.status='hadir' AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=?) AS hari_hadir
        FROM payroll py
        JOIN pegawai p ON py.pegawai_id = p.id
        WHERE py.bulan=? AND py.tahun=?
        ORDER BY p.nama";
$stmt = $pdo->prepare($sql);
$stmt->execute([$bulan,$tahun,$bulan,$tahun]);
$payroll = $stmt->fetchAll();

$pegawais = $pdo->query("SELECT id, nama FROM pegawai WHERE status='aktif' ORDER BY nama")->fetchAll();
$bulan_list = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Payroll berhasil disimpan!</div>
<?php endif; ?>

<!-- FORM GENERATE PAYROLL -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Input Payroll</h2>
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
                <label>Bulan <span style="color:var(--danger)">*</span></label>
                <select name="bulan" required>
                    <?php foreach ($bulan_list as $i => $bl): ?>
                    <option value="<?= $i+1 ?>" <?= ($i+1)==$bulan?'selected':'' ?>><?= $bl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tahun <span style="color:var(--danger)">*</span></label>
                <input type="number" name="tahun" value="<?= $tahun ?>" min="2020" max="2030" required>
            </div>
            <div class="form-group">
                <label>Gaji Pokok (Rp) <span style="color:var(--danger)">*</span></label>
                <input type="number" name="gaji_pokok" placeholder="4000000" min="0" required>
            </div>
            <div class="form-group">
                <label>Tunjangan (Rp)</label>
                <input type="number" name="tunjangan" placeholder="500000" min="0" value="0">
            </div>
            <div class="form-group">
                <label>Potongan (Rp)</label>
                <input type="number" name="potongan" placeholder="0" min="0" value="0">
            </div>
        </div>
        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        </div>
    </form>
</div>

<!-- TABEL PAYROLL -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Data Payroll — <?= $bulan_list[$bulan-1] ?> <?= $tahun ?></h2>
        <form method="GET" style="display:flex; gap:8px;">
            <select name="bulan" style="background:var(--primary-dark); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:8px 12px; color:var(--text); font-family:var(--font-family); font-size:var(--label);">
                <?php foreach ($bulan_list as $i => $bl): ?>
                <option value="<?= $i+1 ?>" <?= ($i+1)==$bulan?'selected':'' ?>><?= $bl ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="tahun" value="<?= $tahun ?>" min="2020" max="2030"
                style="background:var(--primary-dark); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:8px 12px; color:var(--text); font-family:var(--font-family); font-size:var(--label); width:90px;">
            <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-filter"></i></button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Nama</th><th>Jabatan</th><th>Hari Hadir</th><th>Gaji Pokok</th><th>Tunjangan</th><th>Potongan</th><th>Total</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php if ($payroll): ?>
                    <?php foreach ($payroll as $i => $py): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($py['nama']) ?></td>
                        <td><?= htmlspecialchars($py['jabatan']) ?></td>
                        <td><?= $py['hari_hadir'] ?> hari</td>
                        <td>Rp <?= number_format($py['gaji_pokok'],0,',','.') ?></td>
                        <td>Rp <?= number_format($py['tunjangan'],0,',','.') ?></td>
                        <td>Rp <?= number_format($py['potongan'],0,',','.') ?></td>
                        <td style="color:var(--success); font-weight:700;">Rp <?= number_format($py['total'],0,',','.') ?></td>
                        <td><span class="badge <?= $py['status']==='final'?'badge-success':'badge-warning' ?>"><?= ucfirst($py['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9">
                        <div class="empty-state"><i class="fa-solid fa-money-bill-slash"></i><p>Belum ada data payroll bulan ini</p></div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>
