<?php
$page_title = 'Tambah Pegawai';
require_once __DIR__ . '/../../../config/database.php';

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
    $gaji_pokok = (int)str_replace(['.', ','], '', $_POST['gaji_pokok'] ?? '0');

    if (!$nik || !$nama || !$jabatan || !$departemen || !$email || !$tgl_masuk) {
        $errors[] = 'Field wajib tidak boleh kosong!';
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid!';
    }

    if (!$errors) {
        $cek_nik = $pdo->prepare("SELECT id FROM pegawai WHERE nik = ?");
        $cek_nik->execute([$nik]);
        if ($cek_nik->fetch()) $errors[] = "NIK <strong>$nik</strong> sudah terdaftar!";

        $cek_email = $pdo->prepare("SELECT id FROM pegawai WHERE email = ?");
        $cek_email->execute([$email]);
        if ($cek_email->fetch()) $errors[] = "Email <strong>$email</strong> sudah terdaftar!";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO pegawai 
            (nik, nama, jabatan, departemen, email, no_hp, alamat, tgl_lahir, tgl_masuk) 
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $nik, $nama, $jabatan, $departemen, $email,
            $no_hp, $alamat, $tgl_lahir ?: null, $tgl_masuk
        ]);
        $pegawai_id = $pdo->lastInsertId();

        if ($gaji_pokok > 0) {
            $pdo->prepare("INSERT INTO payroll (pegawai_id, bulan, tahun, gaji_pokok, tunjangan, potongan, total, status) VALUES (?,?,?,?,0,0,?,?)")
                ->execute([$pegawai_id, date('n'), date('Y'), $gaji_pokok, $gaji_pokok, 'draft']);
        }

        header("Location: index.php?msg=tambah");
        exit;
    }
}

require_once __DIR__ . '/../../../includes/hr_header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger" style="flex-direction:column; align-items:flex-start; gap:6px;">
    <div style="display:flex; align-items:center; gap:8px; font-weight:600;">
        <i class="fa-solid fa-circle-exclamation"></i> Terdapat kesalahan:
    </div>
    <ul style="margin:0; padding-left:20px;">
        <?php foreach ($errors as $err): ?>
        <li><?= $err ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Form Tambah Pegawai</h2>
        <a href="index.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>NIK <span style="color:var(--danger)">*</span></label>
                <input type="text" name="nik"
                    value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>"
                    placeholder="EMP001" required>
            </div>
            <div class="form-group">
                <label>Nama Lengkap <span style="color:var(--danger)">*</span></label>
                <input type="text" name="nama"
                    value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                    placeholder="Nama pegawai" required>
            </div>
            <div class="form-group">
                <label>Jabatan <span style="color:var(--danger)">*</span></label>
                <input type="text" name="jabatan"
                    value="<?= htmlspecialchars($_POST['jabatan'] ?? '') ?>"
                    placeholder="Staff HR, Kasir, dll" required>
            </div>
            <div class="form-group">
                <label>Departemen <span style="color:var(--danger)">*</span></label>
                <select name="departemen" required>
                    <option value="">-- Pilih Departemen --</option>
                    <?php foreach (['HR','CS','Security','Operations','Facility','Finance','IT'] as $dep): ?>
                    <option value="<?= $dep ?>" <?= ($_POST['departemen'] ?? '') === $dep ? 'selected' : '' ?>><?= $dep ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Email <span style="color:var(--danger)">*</span></label>
                <input type="email" name="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    placeholder="email@mall.com" required>
            </div>
            <div class="form-group">
                <label>No. HP</label>
                <input type="text" name="no_hp"
                    value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>"
                    placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tgl_lahir"
                    value="<?= $_POST['tgl_lahir'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Tanggal Masuk <span style="color:var(--danger)">*</span></label>
                <input type="date" name="tgl_masuk"
                    value="<?= $_POST['tgl_masuk'] ?? date('Y-m-d') ?>" required>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>Alamat</label>
                <textarea name="alamat" rows="3"
                    placeholder="Alamat lengkap"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="section-divider"><span>Informasi Gaji</span></div>
        <div class="form-grid">
            <div class="form-group">
                <label>Gaji Pokok</label>
                <input type="number" name="gaji_pokok" min="0" step="1000"
                    value="<?= $_POST['gaji_pokok'] ?? '' ?>"
                    placeholder="0 (kosongkan jika belum ditentukan)">
                <small style="color:rgba(245,247,250,0.4); font-size:12px;">
                    Akan otomatis tersimpan ke data payroll bulan ini
                </small>
            </div>
        </div>

        <div class="form-actions" style="margin-top:24px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Simpan
            </button>
            <a href="index.php" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>