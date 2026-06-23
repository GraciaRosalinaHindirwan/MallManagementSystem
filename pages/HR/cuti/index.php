<?php
$page_title = 'Pengajuan Cuti';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

// Approve / Tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $id   = (int)$_POST['id'];
    if (in_array($aksi, ['approve', 'tolak'])) {
        $status_baru = $aksi === 'approve' ? 'disetujui' : 'ditolak';
        $pdo->prepare("UPDATE cuti SET status=? WHERE id=?")->execute([$status_baru, $id]);
        header("Location: index.php?msg=$aksi");
        exit;
    }
}

// Filter & data
$filter = $_GET['filter'] ?? 'semua';
$where  = $filter !== 'semua' ? "WHERE c.status = ?" : '';
$params = $filter !== 'semua' ? [$filter] : [];

$sql = "
    SELECT c.*, p.nama, p.jabatan, p.departemen
    FROM cuti c
    JOIN pegawai p ON c.pegawai_id = p.id
    $where
    ORDER BY 
        CASE c.status WHEN 'pending' THEN 0 ELSE 1 END,
        c.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cuti = $stmt->fetchAll();

// Ringkasan
$summary = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(status='pending')   as pending,
        SUM(status='disetujui') as disetujui,
        SUM(status='ditolak')   as ditolak
    FROM cuti
")->fetch();

// Pegawai + sisa cuti untuk dropdown
$pegawais = $pdo->query("
    SELECT 
        p.id, p.nama, p.departemen,
        GREATEST(0, 12 - COALESCE(SUM(
            CASE 
                WHEN c.status = 'disetujui'
                AND c.jenis_cuti = 'tahunan'
                AND YEAR(c.tgl_mulai) = YEAR(CURDATE())
                THEN (DATEDIFF(c.tgl_selesai, c.tgl_mulai) + 1)
                ELSE 0
            END
        ), 0)) as sisa_cuti
    FROM pegawai p
    LEFT JOIN cuti c ON c.pegawai_id = p.id
    WHERE p.status = 'aktif'
    GROUP BY p.id
    ORDER BY p.nama
")->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
<?php
$msg_map = [
    'tambah'  => ['success', 'circle-check',       'Pengajuan cuti berhasil dikirim!'],
    'approve' => ['success', 'circle-check',       'Cuti berhasil disetujui!'],
    'tolak'   => ['danger',  'circle-exclamation', 'Cuti berhasil ditolak!'],
];
$msg = $msg_map[$_GET['msg']] ?? null;
if ($msg): ?>
<div class="alert alert-<?= $msg[0] ?>">
    <i class="fa-solid fa-<?= $msg[1] ?>"></i> <?= $msg[2] ?>
</div>
<?php endif; endif; ?>

<!-- STAT CARDS -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit, minmax(160px,1fr)); margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-list"></i></div>
        <div class="stat-info"><h3><?= $summary['total'] ?></h3><p>Total Pengajuan</p></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info"><h3><?= $summary['pending'] ?></h3><p>Menunggu</p></div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info"><h3><?= $summary['disetujui'] ?></h3><p>Disetujui</p></div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon"><i class="fa-solid fa-circle-xmark"></i></div>
        <div class="stat-info"><h3><?= $summary['ditolak'] ?></h3><p>Ditolak</p></div>
    </div>
</div>

<!-- TABEL DAFTAR CUTI -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Pengajuan Cuti</h2>
        <div class="filter-tabs">
            <?php foreach (['semua','pending','disetujui','ditolak'] as $f): ?>
            <a href="?filter=<?= $f ?>" class="<?= $filter === $f ? 'active' : '' ?>">
                <?= ucfirst($f) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pegawai</th>
                    <th>Departemen</th>
                    <th>Jenis</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Durasi</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($cuti): ?>
                    <?php foreach ($cuti as $i => $c): ?>
                    <?php
                        $durasi    = (new DateTime($c['tgl_mulai']))->diff(new DateTime($c['tgl_selesai']))->days + 1;
                        $badge_map = ['pending'=>'badge-warning','disetujui'=>'badge-success','ditolak'=>'badge-danger'];
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <?= htmlspecialchars($c['nama']) ?>
                            <div style="font-size:var(--caption); color:rgba(245,247,250,0.45); margin-top:2px;">
                                <?= htmlspecialchars($c['jabatan']) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($c['departemen']) ?></td>
                        <td><span class="badge badge-info"><?= ucfirst($c['jenis_cuti']) ?></span></td>
                        <td><?= date('d M Y', strtotime($c['tgl_mulai'])) ?></td>
                        <td><?= date('d M Y', strtotime($c['tgl_selesai'])) ?></td>
                        <td><?= $durasi ?> hari</td>
                        <td><?= htmlspecialchars(substr($c['alasan'], 0, 40)) . (strlen($c['alasan']) > 40 ? '...' : '') ?></td>
                        <td><span class="badge <?= $badge_map[$c['status']] ?>"><?= ucfirst($c['status']) ?></span></td>
                        <td>
                            <?php if ($c['status'] === 'pending'): ?>
                            <div style="display:flex; gap:6px;">
                                <form method="POST" onsubmit="return confirm('Setujui cuti <?= htmlspecialchars(addslashes($c['nama'])) ?>?')">
                                    <input type="hidden" name="aksi" value="approve">
                                    <input type="hidden" name="id"   value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn-aksi edit" title="Setujui">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Tolak cuti <?= htmlspecialchars(addslashes($c['nama'])) ?>?')">
                                    <input type="hidden" name="aksi" value="tolak">
                                    <input type="hidden" name="id"   value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn-aksi hapus" title="Tolak">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span style="color:rgba(255,255,255,0.3);">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10">
                        <div class="empty-state">
                            <i class="fa-solid fa-umbrella-beach"></i>
                            <p>Tidak ada pengajuan cuti<?= $filter !== 'semua' ? " dengan status \"$filter\"" : '' ?></p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- FORM PENGAJUAN CUTI (di bawah tabel) -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Ajukan Cuti</h2>
    </div>
    <form method="POST" action="tambah.php">
        <div class="form-grid">
            <div class="form-group">
                <label>Pegawai <span style="color:var(--danger)">*</span></label>
                <select name="pegawai_id" required onchange="updateSisaCuti(this)">
                    <option value="">-- Pilih Pegawai --</option>
                    <?php foreach ($pegawais as $pg): ?>
                    <option value="<?= $pg['id'] ?>" data-sisa="<?= $pg['sisa_cuti'] ?>">
                        <?= htmlspecialchars($pg['nama']) ?> — <?= htmlspecialchars($pg['departemen']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div id="info-sisa-cuti" style="display:none; margin-top:6px; font-size:var(--caption); color:rgba(245,247,250,0.6);">
                    Sisa cuti tahunan: <strong id="sisa-cuti-val"></strong> hari
                </div>
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
        </div>

        <!-- Tanggal kanan kiri di desktop -->
        <div class="form-grid-2" style="margin-top:20px;">
            <div class="form-group">
                <label>Tanggal Mulai <span style="color:var(--danger)">*</span></label>
                <input type="date" name="tgl_mulai" required>
            </div>
            <div class="form-group">
                <label>Tanggal Selesai <span style="color:var(--danger)">*</span></label>
                <input type="date" name="tgl_selesai" required>
            </div>
        </div>

        <div class="form-grid" style="margin-top:20px;">
            <div class="form-group" style="grid-column:1/-1;">
                <label>Alasan <span style="color:var(--danger)">*</span></label>
                <textarea name="alasan" rows="3"
                    placeholder="Tuliskan alasan pengajuan cuti..." required></textarea>
            </div>
        </div>

        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Kirim Pengajuan
            </button>
        </div>
    </form>
</div>

<script>
function updateSisaCuti(select) {
    const opt  = select.options[select.selectedIndex];
    const sisa = opt.dataset.sisa;
    const info = document.getElementById('info-sisa-cuti');
    const val  = document.getElementById('sisa-cuti-val');
    if (select.value && sisa !== undefined) {
        val.textContent = sisa;
        val.style.color = sisa <= 3 ? 'var(--danger)' : sisa <= 6 ? 'var(--text-accent)' : 'var(--success)';
        info.style.display = 'block';
    } else {
        info.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>