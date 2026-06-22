<?php
require_once __DIR__ . '/../../config/koneksi.php';
// require_once __DIR__ . '/../../auth/checkSession.php';

$pageTitle   = 'Buat Tiket Baru — Customer Service';
$currentMenu = 'tiket-buat';

$kategori_list = [
    'facility' => 'Facility — Kerusakan Fasilitas',
    'security' => 'Security — Keamanan',
    'cleaning' => 'Cleaning — Kebersihan',
    'other'    => 'Lainnya',
];

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pelapor   = trim($_POST['nama_pelapor'] ?? '');
    $no_hp          = trim($_POST['no_hp'] ?? '') ?: null;
    $lokasi         = trim($_POST['lokasi'] ?? '');
    $floor_name     = trim($_POST['floor_name'] ?? '') ?: null;
    $area_name      = trim($_POST['area_name'] ?? '') ?: null;
    $asset_name     = trim($_POST['asset_name'] ?? '') ?: null;
    $asset_code     = trim($_POST['asset_code'] ?? '') ?: null;
    $kategori       = $_POST['kategori'] ?? '';
    $damage_type    = trim($_POST['damage_type'] ?? '') ?: null;
    $priority       = $_POST['priority'] ?? 'Medium';
    $severity_level = (int) ($_POST['severity_level'] ?? 1);
    $deskripsi      = trim($_POST['deskripsi'] ?? '');

    $allowed_kat      = array_keys($kategori_list);
    $allowed_priority = ['Critical','High','Medium','Low'];
    if (!in_array($priority, $allowed_priority)) $priority = 'Medium';
    if ($severity_level < 1) $severity_level = 1;
    if ($severity_level > 10) $severity_level = 10;

    if (!$nama_pelapor)                  $errors[] = 'Nama pelapor wajib diisi.';
    if (!$lokasi)                        $errors[] = 'Lokasi kejadian wajib diisi.';
    if (!$kategori || !in_array($kategori, $allowed_kat)) $errors[] = 'Kategori tiket wajib dipilih.';
    if (!$deskripsi)                     $errors[] = 'Deskripsi masalah wajib diisi.';

    if (empty($errors)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `05_tiket`");
        $count = (int) $stmt->fetchColumn();
        $new_id = 'TKT-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        $foto_paths = [];
        if (!empty($_FILES['foto']['name'][0])) {
            $upload_dir = __DIR__ . '/uploads/tiket/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            foreach ($_FILES['foto']['tmp_name'] as $i => $tmp) {
                if ($_FILES['foto']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext      = pathinfo($_FILES['foto']['name'][$i], PATHINFO_EXTENSION);
                    $filename = $new_id . '_' . ($i + 1) . '.' . $ext;
                    move_uploaded_file($tmp, $upload_dir . $filename);
                    $foto_paths[] = 'uploads/tiket/' . $filename;
                }
            }
        }

        $foto_json = !empty($foto_paths) ? json_encode($foto_paths) : null;
        $stmt = $pdo->prepare("
            INSERT INTO `05_tiket` (
                id, report_date, pelapor, no_hp, lokasi, floor_name, area_name,
                asset_name, asset_code, kategori, damage_type, priority, severity_level,
                deskripsi, foto
            ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $new_id, $nama_pelapor, $no_hp, $lokasi, $floor_name, $area_name,
            $asset_name, $asset_code, $kategori, $damage_type, $priority, $severity_level,
            $deskripsi, $foto_json
        ]);

        $success = true;
    }
}

ob_start();
?>

<?php if ($success): ?>
<div class="flex items-center gap-3 bg-success/10 border border-success/30 text-success rounded-lg px-5 py-3">
    <i class="bi bi-check-circle-fill text-lg"></i>
    <div>
        <p class="text-label font-semibold">Tiket berhasil dibuat!</p>
        <p class="text-caption text-text/60">Tiket telah otomatis diteruskan ke departemen terkait.</p>
    </div>
    <a href="tiket.php" class="ml-auto cs-btn bg-success/20 text-success hover:bg-success/30 text-caption px-3 py-1">
        Lihat Semua Tiket
    </a>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="flex items-start gap-3 bg-danger/10 border border-danger/30 text-danger rounded-lg px-5 py-3">
    <i class="bi bi-exclamation-circle-fill text-lg mt-0.5"></i>
    <ul class="text-label space-y-0.5">
        <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 cs-card space-y-5">
        <div class="flex items-center gap-3 pb-4 border-b border-border">
            <div class="w-9 h-9 rounded-md bg-accent/15 flex items-center justify-center">
                <i class="bi bi-plus-circle text-accent"></i>
            </div>
            <div>
                <h2 class="text-label font-semibold">Form Tiket Keluhan</h2>
                <p class="text-caption text-text/50">Isi data keluhan pengunjung secara lengkap</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">

            <div class="space-y-1">
                <p class="text-caption text-text/50 font-semibold uppercase tracking-widest">Identitas Pelapor</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Nama Pelapor <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pelapor" class="cs-input" placeholder="Masukkan nama pengunjung"
                        value="<?= htmlspecialchars($_POST['nama_pelapor'] ?? '') ?>" required />
                </div>
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Nomor HP</label>
                    <input type="tel" name="no_hp" class="cs-input" placeholder="08xx-xxxx-xxxx (opsional)"
                        value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" />
                </div>
            </div>

            <div class="space-y-1 pt-2">
                <p class="text-caption text-text/50 font-semibold uppercase tracking-widest">Detail Keluhan</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Lokasi Kejadian <span class="text-danger">*</span></label>
                    <input type="text" name="lokasi" class="cs-input" placeholder="Contoh: Lantai 2, dekat ATM BCA"
                        value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>" required />
                </div>
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Kategori Tiket <span class="text-danger">*</span></label>
                    <select name="kategori" class="cs-input" style="background-color:#0B376D;color:#F5F7FA;" required>
                        <option value="" disabled <?= empty($_POST['kategori']) ? 'selected' : '' ?>>-- Pilih kategori --</option>
                        <?php foreach ($kategori_list as $val => $label): ?>
                            <option value="<?= $val ?>" <?= (($_POST['kategori'] ?? '') === $val) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Lantai</label>
                    <input type="text" name="floor_name" class="cs-input" placeholder="Contoh: 2"
                        value="<?= htmlspecialchars($_POST['floor_name'] ?? '') ?>" />
                </div>
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Area</label>
                    <input type="text" name="area_name" class="cs-input" placeholder="Contoh: ATM Area"
                        value="<?= htmlspecialchars($_POST['area_name'] ?? '') ?>" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Nama Aset</label>
                    <input type="text" name="asset_name" class="cs-input" placeholder="Contoh: AC, Lift, Toilet"
                        value="<?= htmlspecialchars($_POST['asset_name'] ?? '') ?>" />
                </div>
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Kode Aset</label>
                    <input type="text" name="asset_code" class="cs-input" placeholder="Contoh: AC-L2-01"
                        value="<?= htmlspecialchars($_POST['asset_code'] ?? '') ?>" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Jenis Kerusakan</label>
                    <input type="text" name="damage_type" class="cs-input" placeholder="Contoh: Mechanical Failure"
                        value="<?= htmlspecialchars($_POST['damage_type'] ?? '') ?>" />
                </div>
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Prioritas</label>
                    <select name="priority" class="cs-input" style="background-color:#0B376D;color:#F5F7FA;">
                        <?php foreach (['Critical','High','Medium','Low'] as $p): ?>
                            <option value="<?= $p ?>" <?= (($_POST['priority'] ?? 'Medium') === $p) ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-label font-medium">Severity (1-10)</label>
                    <input type="number" name="severity_level" min="1" max="10" class="cs-input"
                        value="<?= htmlspecialchars($_POST['severity_level'] ?? '1') ?>" />
                </div>
            </div>

            <div id="assign-info" class="hidden flex items-center gap-2 bg-accent/10 border border-accent/20 rounded-md px-4 py-2.5">
                <i class="bi bi-arrow-right-circle text-accent text-sm"></i>
                <p class="text-caption text-text/70">Tiket akan otomatis diteruskan ke departemen: <span id="assign-dept" class="text-accent font-semibold"></span></p>
            </div>

            <div class="space-y-1.5">
                <label class="text-label font-medium">Deskripsi Masalah <span class="text-danger">*</span></label>
                <textarea name="deskripsi" class="cs-input resize-none" rows="4"
                    placeholder="Jelaskan masalah secara detail..." required><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
            </div>

            <div class="space-y-1.5">
                <label class="text-label font-medium">Foto Lampiran <span class="text-caption text-text/40">(opsional, maks. 3 foto)</span></label>
                <div class="border-2 border-dashed border-border rounded-lg p-6 text-center hover:border-accent/40 transition-colors cursor-pointer"
                    onclick="document.getElementById('foto-input').click()">
                    <i class="bi bi-cloud-arrow-up text-2xl text-text/30 mb-2 block"></i>
                    <p class="text-label text-text/50">Klik untuk upload foto</p>
                    <p class="text-caption text-text/30 mt-1">JPG, PNG — maks. 5MB per file</p>
                </div>
                <input type="file" id="foto-input" name="foto[]" multiple accept="image/*" class="hidden" />
                <div id="foto-preview" class="flex gap-3 mt-2 flex-wrap"></div>
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-border">
                <button type="submit" class="cs-btn bg-accent text-background hover:bg-accent/90 px-6 py-2.5">
                    <i class="bi bi-send"></i> Kirim Tiket
                </button>
                <a href="tiket.php" class="cs-btn bg-white/5 text-text/70 hover:bg-white/10 border-border px-6 py-2.5">
                    Batal
                </a>
                <span class="ml-auto text-caption text-text/30"><span class="text-danger">*</span> wajib diisi</span>
            </div>

        </form>
    </div>

    <div class="space-y-4">
        <div class="cs-card space-y-3">
            <div class="flex items-center gap-2 pb-3 border-b border-border">
                <i class="bi bi-clock-history text-warning"></i>
                <h3 class="text-label font-semibold">Batas Waktu SLA</h3>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-danger inline-block"></span><span class="text-label">Facility</span></div>
                    <span class="text-label font-semibold text-warning">2 Jam</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-warning inline-block"></span><span class="text-label">Security</span></div>
                    <span class="text-label font-semibold text-warning">30 Menit</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-success inline-block"></span><span class="text-label">Cleaning</span></div>
                    <span class="text-label font-semibold text-warning">1 Jam</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-text/30 inline-block"></span><span class="text-label">Lainnya</span></div>
                    <span class="text-label font-semibold text-warning">4 Jam</span>
                </div>
            </div>
        </div>

        <div class="cs-card space-y-3">
            <div class="flex items-center gap-2 pb-3 border-b border-border">
                <i class="bi bi-info-circle text-accent"></i>
                <h3 class="text-label font-semibold">Panduan Kategori</h3>
            </div>
            <ul class="space-y-2.5 text-caption text-text/70">
                <li class="flex gap-2"><i class="bi bi-tools text-accent/70 mt-0.5 flex-shrink-0"></i><span><strong class="text-text/90">Facility</strong> — AC rusak, lift bermasalah, lampu mati, kebocoran</span></li>
                <li class="flex gap-2"><i class="bi bi-shield text-warning/70 mt-0.5 flex-shrink-0"></i><span><strong class="text-text/90">Security</strong> — Kehilangan, pencurian, keributan, kedaruratan</span></li>
                <li class="flex gap-2"><i class="bi bi-trash text-success/70 mt-0.5 flex-shrink-0"></i><span><strong class="text-text/90">Cleaning</strong> — Lantai licin, tumpahan, toilet kotor, sampah</span></li>
                <li class="flex gap-2"><i class="bi bi-three-dots text-text/40 mt-0.5 flex-shrink-0"></i><span><strong class="text-text/90">Lainnya</strong> — Keluhan yang tidak masuk kategori di atas</span></li>
            </ul>
        </div>
    </div>
</div>

<script>
const deptMap = {
    facility: 'Departemen Facility',
    security: 'Departemen Security',
    cleaning: 'Departemen Cleaning',
    other:    'Tim CS (Manual Review)',
};
document.querySelector('[name="kategori"]').addEventListener('change', function () {
    const info = document.getElementById('assign-info');
    const dept = document.getElementById('assign-dept');
    if (this.value && deptMap[this.value]) {
        dept.textContent = deptMap[this.value];
        info.classList.remove('hidden');
        info.classList.add('flex');
    } else {
        info.classList.add('hidden');
        info.classList.remove('flex');
    }
});

document.getElementById('foto-input').addEventListener('change', function () {
    const preview = document.getElementById('foto-preview');
    preview.innerHTML = '';
    Array.from(this.files).slice(0, 3).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'relative w-20 h-20 rounded-md overflow-hidden border border-border';
            div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover" />`;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layout_cs.php';
?>