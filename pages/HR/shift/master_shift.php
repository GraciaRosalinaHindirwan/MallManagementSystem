<?php
$page_title = 'Master Shift';
require_once __DIR__ . '/../../../config/database.php';

// Hapus shift
if (isset($_GET['hapus'])) {
    // Cek apakah shift masih dipakai di jadwal
    $cek = $pdo->prepare("SELECT COUNT(*) FROM jadwal_shift WHERE shift_id = ?");
    $cek->execute([(int)$_GET['hapus']]);
    if ($cek->fetchColumn() > 0) {
        header("Location: master_shift.php?msg=terpakai"); exit;
    }
    $pdo->prepare("DELETE FROM shift WHERE id=?")->execute([(int)$_GET['hapus']]);
    header("Location: master_shift.php?msg=hapus"); exit;
}

// Tambah shift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['aksi'] === 'tambah') {
    $nama_shift = trim($_POST['nama_shift'] ?? '');
    $jam_masuk  = $_POST['jam_masuk'] ?? '';
    $jam_keluar = $_POST['jam_keluar'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($nama_shift && $jam_masuk && $jam_keluar) {
        $pdo->prepare("INSERT INTO shift (nama_shift, jam_masuk, jam_keluar, keterangan) VALUES (?,?,?,?)")
            ->execute([$nama_shift, $jam_masuk, $jam_keluar, $keterangan]);
        header("Location: master_shift.php?msg=tambah"); exit;
    }
}

// Edit shift
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['aksi'] === 'edit') {
    $edit_id    = (int)$_POST['edit_id'];
    $nama_shift = trim($_POST['nama_shift'] ?? '');
    $jam_masuk  = $_POST['jam_masuk'] ?? '';
    $jam_keluar = $_POST['jam_keluar'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($edit_id && $nama_shift && $jam_masuk && $jam_keluar) {
        $pdo->prepare("UPDATE shift SET nama_shift=?, jam_masuk=?, jam_keluar=?, keterangan=? WHERE id=?")
            ->execute([$nama_shift, $jam_masuk, $jam_keluar, $keterangan, $edit_id]);
        header("Location: master_shift.php?msg=edit"); exit;
    }
}

$shifts = $pdo->query("SELECT * FROM shift ORDER BY jam_masuk")->fetchAll();
$edit_shift = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM shift WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_shift = $stmt->fetch();
}

require_once __DIR__ . '/../../../includes/hr_header.php';
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert <?= $_GET['msg'] === 'terpakai' ? 'alert-danger' : 'alert-success' ?>">
    <i class="fa-solid <?= $_GET['msg'] === 'terpakai' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i>
    <?= match($_GET['msg']) {
        'tambah'   => 'Shift berhasil ditambahkan!',
        'edit'     => 'Shift berhasil diupdate!',
        'hapus'    => 'Shift berhasil dihapus!',
        'terpakai' => 'Shift tidak bisa dihapus karena masih dipakai di jadwal!',
        default    => ''
    } ?>
</div>
<?php endif; ?>

<!-- FORM TAMBAH / EDIT -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $edit_shift ? 'Edit Shift' : 'Tambah Shift' ?></h2>
        <?php if ($edit_shift): ?>
        <a href="master_shift.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-plus"></i> Tambah Baru
        </a>
        <?php endif; ?>
    </div>
    <form method="POST">
        <input type="hidden" name="aksi" value="<?= $edit_shift ? 'edit' : 'tambah' ?>">
        <?php if ($edit_shift): ?>
        <input type="hidden" name="edit_id" value="<?= $edit_shift['id'] ?>">
        <?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Nama Shift <span style="color:var(--danger)">*</span></label>
                <input type="text" name="nama_shift"
                    value="<?= htmlspecialchars($edit_shift['nama_shift'] ?? '') ?>"
                    placeholder="Pagi, Siang, Malam..." required>
            </div>
            <div class="form-group">
                <label>Jam Masuk <span style="color:var(--danger)">*</span></label>
                <input type="time" name="jam_masuk"
                    value="<?= $edit_shift['jam_masuk'] ?? '' ?>" required>
            </div>
            <div class="form-group">
                <label>Jam Keluar <span style="color:var(--danger)">*</span></label>
                <input type="time" name="jam_keluar"
                    value="<?= $edit_shift['jam_keluar'] ?? '' ?>" required>
            </div>
            <div class="form-group">
                <label>Keterangan</label>
                <input type="text" name="keterangan"
                    value="<?= htmlspecialchars($edit_shift['keterangan'] ?? '') ?>"
                    placeholder="Opsional">
            </div>
        </div>
        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> <?= $edit_shift ? 'Update' : 'Simpan' ?>
            </button>
            <?php if ($edit_shift): ?>
            <a href="master_shift.php" class="btn btn-outline">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- TABEL MASTER SHIFT -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Shift</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Shift</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($shifts): ?>
                    <?php foreach ($shifts as $i => $s): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($s['nama_shift']) ?></span></td>
                        <td><?= substr($s['jam_masuk'],0,5) ?></td>
                        <td><?= substr($s['jam_keluar'],0,5) ?></td>
                        <td><?= htmlspecialchars($s['keterangan'] ?? '-') ?></td>
                        <td style="display:flex; gap:6px;">
                            <a href="master_shift.php?edit=<?= $s['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="master_shift.php?hapus=<?= $s['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Hapus shift <?= htmlspecialchars($s['nama_shift']) ?>?')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <i class="fa-solid fa-clock"></i>
                            <p>Belum ada data shift</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>