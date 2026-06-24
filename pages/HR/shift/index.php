<?php
$page_title = 'Jadwal Shift';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

// Hapus jadwal
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM jadwal_shift WHERE id=?")->execute([(int)$_GET['hapus']]);
    header("Location: index.php?msg=hapus");
    exit;
}

// Filter bulan
$bulan = $_GET['bulan'] ?? date('Y-m');
$sql = "SELECT js.id, p.nama, s.nama_shift, s.jam_masuk, s.jam_keluar, js.tanggal
        FROM jadwal_shift js
        JOIN pegawai p ON js.pegawai_id = p.id
        JOIN shift s   ON js.shift_id   = s.id
        WHERE DATE_FORMAT(js.tanggal, '%Y-%m') = ?
        ORDER BY js.tanggal, p.nama";
$stmt = $pdo->prepare($sql);
$stmt->execute([$bulan]);
$jadwal = $stmt->fetchAll();

$shifts   = $pdo->query("SELECT * FROM shift")->fetchAll();
$pegawais = $pdo->query("SELECT id, nama FROM pegawai WHERE status='aktif' ORDER BY nama")->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert <?= $_GET['msg'] === 'bentrok' ? 'alert-danger' : 'alert-success' ?>">
        <i class="fa-solid <?= $_GET['msg'] === 'bentrok' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
        <?php
        $tgl = isset($_GET['tgl']) ? date('d M Y', strtotime($_GET['tgl'])) : '';
        echo match ($_GET['msg']) {
            'tambah'  => 'Jadwal berhasil ditambahkan!',
            'edit'    => 'Jadwal berhasil diupdate!',
            'hapus'   => 'Jadwal berhasil dihapus!',
            'bentrok' => ($_GET['sumber'] ?? '') === 'edit'
                ? "Jadwal gagal diupdate! Pegawai ini sudah memiliki jadwal pada tanggal $tgl."
                : "Jadwal gagal ditambahkan! Pegawai ini sudah memiliki jadwal pada tanggal $tgl.",
            default   => ''
        };
        ?>
    </div>
<?php endif; ?>

<!-- FORM TAMBAH JADWAL -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Tambah Jadwal Shift</h2>
        <a href="master_shift.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-clock"></i> Kelola Master Shift
        </a>
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
                <label>Shift <span style="color:var(--danger)">*</span></label>
                <select name="shift_id" required>
                    <option value="">-- Pilih Shift --</option>
                    <?php foreach ($shifts as $sh): ?>
                        <option value="<?= $sh['id'] ?>"><?= $sh['nama_shift'] ?> (<?= substr($sh['jam_masuk'], 0, 5) ?>–<?= substr($sh['jam_keluar'], 0, 5) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tanggal <span style="color:var(--danger)">*</span></label>
                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>
        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Jadwal</button>
        </div>
    </form>
</div>

<!-- TABEL JADWAL -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Jadwal Shift</h2>
        <form method="GET" style="display:flex; gap:8px; align-items:center;">
            <input type="month" name="bulan" value="<?= $bulan ?>"
                style="background:var(--primary-dark); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:8px 12px; color:var(--text); font-family:var(--font-family); font-size:var(--label); outline:none;">
            <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pegawai</th>
                    <th>Shift</th>
                    <th>Jam</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($jadwal): ?>
                    <?php foreach ($jadwal as $i => $j): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($j['nama']) ?></td>
                            <td><span class="badge badge-info"><?= $j['nama_shift'] ?></span></td>
                            <td><?= substr($j['jam_masuk'], 0, 5) ?> – <?= substr($j['jam_keluar'], 0, 5) ?></td>
                            <td><?= date('d M Y', strtotime($j['tanggal'])) ?></td>
                            <td style="display:flex; gap:6px;">
                                <a href="edit.php?id=<?= $j['id'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="index.php?hapus=<?= $j['id'] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Hapus jadwal ini?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <p>Tidak ada jadwal untuk bulan ini</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>