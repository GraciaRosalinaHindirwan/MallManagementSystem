<?php
$page_title = 'Edit Pegawai';
require_once __DIR__ . '/../../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
$pegawai = $pdo->prepare("SELECT * FROM pegawai WHERE id = ?");
$pegawai->execute([$id]);
$p = $pegawai->fetch();
if (!$p) {
    header("Location: index.php");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik        = trim($_POST['nik'] ?? '');
    $nama       = trim($_POST['nama'] ?? '');
    $jabatan    = trim($_POST['jabatan'] ?? '');
    $departemen = trim($_POST['departemen'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $no_hp      = trim($_POST['no_hp'] ?? '');
    $alamat     = trim($_POST['alamat'] ?? '');
    $tgl_lahir  = $_POST['tgl_lahir'] ?? null;
    $tgl_masuk  = $_POST['tgl_masuk'] ?? '';
    $status     = $_POST['status'] ?? 'aktif';

    if (!$nik || !$nama || !$jabatan || !$departemen || !$email || !$tgl_masuk) {
        $errors[] = 'Field wajib tidak boleh kosong!';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE pegawai SET nik=?,nama=?,jabatan=?,departemen=?,email=?,no_hp=?,alamat=?,tgl_lahir=?,tgl_masuk=?,status=? WHERE id=?");
        $stmt->execute([$nik, $nama, $jabatan, $departemen, $email, $no_hp, $alamat, $tgl_lahir ?: null, $tgl_masuk, $status, $id]);
        header("Location: index.php?msg=edit");
        exit;
    }
    $p = array_merge($p, $_POST);
}

require_once __DIR__ . '/../../../includes/hr_header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= implode('<br>', $errors) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Edit Data Pegawai</h2>
        <a href="index.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>NIK <span style="color:var(--danger)">*</span></label>
                <input type="text" name="nik" value="<?= htmlspecialchars($p['nik']) ?>" required>
            </div>
            <div class="form-group">
                <label>Nama Lengkap <span style="color:var(--danger)">*</span></label>
                <input type="text" name="nama" value="<?= htmlspecialchars($p['nama']) ?>" required>
            </div>
            <div class="form-group">
                <label>Jabatan <span style="color:var(--danger)">*</span></label>
                <input type="text" name="jabatan" value="<?= htmlspecialchars($p['jabatan']) ?>" required>
            </div>
            <div class="form-group">
                <label>Departemen <span style="color:var(--danger)">*</span></label>
                <select name="departemen" required>
                    <?php foreach (['HR', 'CS', 'Security', 'Operations', 'Facility', 'Finance', 'IT'] as $dep): ?>
                        <option value="<?= $dep ?>" <?= $p['departemen'] === $dep ? 'selected' : '' ?>><?= $dep ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Email <span style="color:var(--danger)">*</span></label>
                <input type="email" name="email" value="<?= htmlspecialchars($p['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>No. HP</label>
                <input type="text" name="no_hp" value="<?= htmlspecialchars($p['no_hp'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tgl_lahir" value="<?= $p['tgl_lahir'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Tanggal Masuk <span style="color:var(--danger)">*</span></label>
                <input type="date" name="tgl_masuk" value="<?= $p['tgl_masuk'] ?>" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="aktif" <?= $p['status'] === 'aktif'    ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $p['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>Alamat</label>
                <textarea name="alamat" rows="3"><?= htmlspecialchars($p['alamat'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions" style="margin-top:24px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Update
            </button>
            <a href="index.php" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>
