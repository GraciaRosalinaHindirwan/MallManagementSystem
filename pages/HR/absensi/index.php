<?php
$page_title = 'Absensi Pegawai';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

$tanggal   = $_GET['tanggal'] ?? date('Y-m-d');
$pegawais  = $pdo->query("SELECT id, nama FROM pegawai WHERE status='aktif' ORDER BY nama")->fetchAll();

$sql = "SELECT a.*, p.nama FROM absensi a JOIN pegawai p ON a.pegawai_id = p.id WHERE a.tanggal = ? ORDER BY p.nama";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal]);
$absensi = $stmt->fetchAll();

// Rekap hari ini
$rekap = ['hadir'=>0,'izin'=>0,'sakit'=>0,'alpha'=>0];
foreach ($absensi as $a) { $rekap[$a['status']]++; }
?>

<!-- REKAP MINI -->
<div class="stats-grid" style="grid-template-columns: repeat(4,1fr);">
    <div class="stat-card success">
        <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
        <div class="stat-info"><h3><?= $rekap['hadir'] ?></h3><p>Hadir</p></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="fa-solid fa-user-clock"></i></div>
        <div class="stat-info"><h3><?= $rekap['izin'] ?></h3><p>Izin</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-user-injured"></i></div>
        <div class="stat-info"><h3><?= $rekap['sakit'] ?></h3><p>Sakit</p></div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon"><i class="fa-solid fa-user-xmark"></i></div>
        <div class="stat-info"><h3><?= $rekap['alpha'] ?></h3><p>Alpha</p></div>
    </div>
</div>

<!-- FORM CATAT ABSENSI -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Catat Absensi</h2>
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
                <label>Tanggal <span style="color:var(--danger)">*</span></label>
                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Jam Masuk</label>
                <input type="time" name="jam_masuk" value="<?= date('H:i') ?>">
            </div>
            <div class="form-group">
                <label>Jam Keluar</label>
                <input type="time" name="jam_keluar">
            </div>
            <div class="form-group">
                <label>Status <span style="color:var(--danger)">*</span></label>
                <select name="status" required>
                    <option value="hadir">Hadir</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="alpha">Alpha</option>
                </select>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <input type="text" name="keterangan" placeholder="Opsional">
            </div>
        </div>
        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Catat</button>
        </div>
    </form>
</div>

<!-- TABEL ABSENSI -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Data Absensi</h2>
        <form method="GET" style="display:flex; gap:8px;">
            <input type="date" name="tanggal" value="<?= $tanggal ?>"
                style="background:var(--primary-dark); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:8px 12px; color:var(--text); font-family:var(--font-family); font-size:var(--label); outline:none;">
            <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-filter"></i></button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Pegawai</th><th>Tanggal</th><th>Jam Masuk</th><th>Jam Keluar</th><th>Status</th><th>Keterangan</th></tr>
            </thead>
            <tbody>
                <?php if ($absensi): ?>
                    <?php foreach ($absensi as $i => $a): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($a['nama']) ?></td>
                        <td><?= date('d M Y', strtotime($a['tanggal'])) ?></td>
                        <td><?= $a['jam_masuk'] ? substr($a['jam_masuk'],0,5) : '-' ?></td>
                        <td><?= $a['jam_keluar'] ? substr($a['jam_keluar'],0,5) : '-' ?></td>
                        <td>
                            <?php $badge=['hadir'=>'badge-success','izin'=>'badge-warning','sakit'=>'badge-info','alpha'=>'badge-danger']; ?>
                            <span class="badge <?= $badge[$a['status']] ?>"><?= ucfirst($a['status']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($a['keterangan'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">
                        <div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><p>Belum ada data absensi untuk tanggal ini</p></div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>
