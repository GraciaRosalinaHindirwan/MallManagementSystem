<?php
$page_title = 'Edit Jadwal Shift';
require_once __DIR__ . '/../../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM jadwal_shift WHERE id = ?");
$stmt->execute([$id]);
$jadwal = $stmt->fetch();
if (!$jadwal) { header("Location: index.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = (int)$_POST['pegawai_id'];
    $shift_id   = (int)$_POST['shift_id'];
    $tanggal    = $_POST['tanggal'] ?? '';

    if ($pegawai_id && $shift_id && $tanggal) {
    // Cek bentrok: pegawai sudah punya jadwal di tanggal yang sama (kecuali jadwal yang sedang diedit)
    $cek = $pdo->prepare("SELECT id FROM jadwal_shift WHERE pegawai_id = ? AND tanggal = ? AND id != ?");
    $cek->execute([$pegawai_id, $tanggal, $id]);

    if ($cek->fetch()) {
        header("Location: index.php?msg=bentrok&sumber=edit&tgl=" . urlencode($tanggal));
        exit;
    }

    $pdo->prepare("UPDATE jadwal_shift SET pegawai_id=?, shift_id=?, tanggal=? WHERE id=?")
        ->execute([$pegawai_id, $shift_id, $tanggal, $id]);
    header("Location: index.php?msg=edit"); exit;
}
}

$shifts   = $pdo->query("SELECT * FROM shift")->fetchAll();
$pegawais = $pdo->query("SELECT id, nama FROM pegawai WHERE status='aktif' ORDER BY nama")->fetchAll();

require_once __DIR__ . '/../../../includes/hr_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Edit Jadwal Shift</h2>
        <a href="index.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Pegawai <span style="color:var(--danger)">*</span></label>
                <select name="pegawai_id" required>
                    <option value="">-- Pilih Pegawai --</option>
                    <?php foreach ($pegawais as $pg): ?>
                    <option value="<?= $pg['id'] ?>" <?= $jadwal['pegawai_id'] == $pg['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pg['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Shift <span style="color:var(--danger)">*</span></label>
                <select name="shift_id" required>
                    <option value="">-- Pilih Shift --</option>
                    <?php foreach ($shifts as $sh): ?>
                    <option value="<?= $sh['id'] ?>" <?= $jadwal['shift_id'] == $sh['id'] ? 'selected' : '' ?>>
                        <?= $sh['nama_shift'] ?> (<?= substr($sh['jam_masuk'],0,5) ?>–<?= substr($sh['jam_keluar'],0,5) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tanggal <span style="color:var(--danger)">*</span></label>
                <input type="date" name="tanggal" value="<?= $jadwal['tanggal'] ?>" required>
            </div>
        </div>
        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Update
            </button>
            <a href="index.php" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>