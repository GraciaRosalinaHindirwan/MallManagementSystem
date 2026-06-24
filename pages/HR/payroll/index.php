<?php
$page_title = 'Payroll';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

$bulan = (int)($_GET['bulan'] ?? date('m'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$sql = "SELECT py.*, p.nama, p.jabatan,
        (SELECT COUNT(*) FROM absensi a
            WHERE a.pegawai_id=py.pegawai_id
              AND a.status IN ('hadir','terlambat')
              AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=?) AS hari_hadir,
        (SELECT COUNT(*) FROM absensi a
            WHERE a.pegawai_id=py.pegawai_id
              AND a.status='terlambat'
              AND MONTH(a.tanggal)=? AND YEAR(a.tanggal)=?) AS hari_terlambat
        FROM payroll py
        JOIN pegawai p ON py.pegawai_id=p.id
        WHERE py.bulan=? AND py.tahun=?
        ORDER BY p.nama";
$stmt = $pdo->prepare($sql);
$stmt->execute([$bulan,$tahun,$bulan,$tahun,$bulan,$tahun]);
$payroll = $stmt->fetchAll();

$pegawais = $pdo->query("SELECT id, nama, jabatan FROM pegawai WHERE status='aktif' ORDER BY nama")->fetchAll();
$bulan_list = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$total_pengeluaran = array_sum(array_column($payroll, 'total'));
$jumlah_final      = count(array_filter($payroll, fn($r) => $r['status'] === 'final'));
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Payroll berhasil disimpan!</div>
<?php endif; ?>
<?php if (isset($_GET['msg_status'])): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Status payroll berhasil diperbarui!</div>
<?php endif; ?>
<?php if (isset($_GET['msg_locked'])): ?>
<div class="alert alert-danger"><i class="fa-solid fa-lock"></i> Payroll yang sudah Approved/Final tidak dapat diubah.</div>
<?php endif; ?>
<?php if (isset($_GET['msg_nogaji'])): ?>
<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> Pegawai ini belum memiliki data gaji pokok. Silakan isi gaji pokok terlebih dahulu melalui menu <strong>Edit Pegawai</strong>.</div>
<?php endif; ?>

<!-- FORM GENERATE PAYROLL -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Generate Payroll Otomatis</h2>
    </div>
    <form method="POST" action="tambah.php" id="formPayroll">
        <div class="form-grid">
            <div class="form-group">
                <label>Pegawai <span style="color:var(--danger)">*</span></label>
                <select name="pegawai_id" id="pegawaiSelect" required onchange="loadPreview()">
                    <option value="">-- Pilih Pegawai --</option>
                    <?php foreach ($pegawais as $pg): ?>
                    <option value="<?= $pg['id'] ?>">
                        <?= htmlspecialchars($pg['nama']) ?> — <?= htmlspecialchars($pg['jabatan']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Bulan <span style="color:var(--danger)">*</span></label>
                <select name="bulan" id="bulanSelect" required onchange="loadPreview()">
                    <?php foreach ($bulan_list as $i => $bl): ?>
                    <option value="<?= $i+1 ?>" <?= ($i+1)==$bulan?'selected':'' ?>><?= $bl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tahun <span style="color:var(--danger)">*</span></label>
                <input type="number" name="tahun" id="tahunInput" value="<?= $tahun ?>" min="2020" max="2030" required onchange="loadPreview()">
            </div>
        </div>

        <!-- PREVIEW KALKULASI -->
        <div id="previewKalkulasi" style="display:none; margin-top:16px; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); border-radius:10px; padding:16px;">
            <div style="font-size:13px; font-weight:600; color:var(--text-muted); margin-bottom:12px; text-transform:uppercase; letter-spacing:0.5px;">
                <i class="fa-solid fa-calculator"></i> Preview Kalkulasi Otomatis
            </div>
            <div id="loadingState" style="color:var(--text-muted); font-size:13px; display:none;">
                <i class="fa-solid fa-spinner fa-spin"></i> Mengambil data...
            </div>
            <div id="previewContent" style="display:flex; flex-direction:column; gap:8px; font-size:14px;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-muted)">Gaji Pokok</span>
                    <span id="prev_gaji">—</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-muted)">+ Tunjangan</span>
                    <span id="prev_tunjangan" style="color:#4ade80;">Rp 500.000</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-muted)">− Potongan Terlambat</span>
                    <span id="prev_potongan" style="color:#f87171;">—</span>
                </div>
                <div id="prev_absensi_info" style="font-size:12px; color:var(--text-muted); padding:4px 0 4px 16px; border-left:2px solid rgba(255,255,255,0.1); display:none;"></div>
                <div style="border-top:1px solid rgba(255,255,255,0.15); padding-top:10px; margin-top:4px; display:flex; justify-content:space-between; font-weight:700; font-size:16px;">
                    <span>Total Gaji Bersih</span>
                    <span id="prev_total" style="color:var(--success);">—</span>
                </div>
            </div>
            <!-- Peringatan jika belum ada gaji pokok -->
            <div id="noGajiWarning" style="display:none; margin-top:10px; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.4); border-radius:8px; padding:10px; font-size:13px; color:#f87171;">
                <i class="fa-solid fa-circle-exclamation"></i> Pegawai ini belum memiliki data gaji pokok. Silakan isi terlebih dahulu melalui menu <strong>Edit Pegawai</strong>.
            </div>
        </div>

        <!-- Warning duplikat -->
        <div id="duplikatWarning" style="display:none; margin-top:12px; background:rgba(251,191,36,0.15); border:1px solid rgba(251,191,36,0.4); border-radius:8px; padding:12px; font-size:13px; color:#fbbf24;">
            <i class="fa-solid fa-triangle-exclamation"></i> <strong>Perhatian:</strong> Data payroll pegawai ini di bulan yang dipilih <strong>sudah ada</strong>. Menyimpan akan menimpa data lama (hanya jika masih berstatus Draft).
        </div>

        <div class="form-actions" style="margin-top:16px; display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" id="btnSimpan">
                <i class="fa-solid fa-calculator"></i> Generate & Simpan
            </button>
            <button type="button" class="btn btn-outline" onclick="resetForm()">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </button>
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

    <?php if ($payroll): ?>
    <div style="display:flex; gap:12px; padding:0 4px 16px; flex-wrap:wrap;">
        <div style="flex:1; min-width:130px; background:rgba(0,0,0,0.2); border-radius:10px; padding:14px 16px; border:1px solid rgba(255,255,255,0.08);">
            <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Total Pegawai</div>
            <div style="font-size:22px; font-weight:700;"><?= count($payroll) ?></div>
        </div>
        <div style="flex:1; min-width:130px; background:rgba(0,0,0,0.2); border-radius:10px; padding:14px 16px; border:1px solid rgba(255,255,255,0.08);">
            <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Status Final</div>
            <div style="font-size:22px; font-weight:700; color:var(--success);"><?= $jumlah_final ?></div>
        </div>
        <div style="flex:2; min-width:200px; background:rgba(0,0,0,0.2); border-radius:10px; padding:14px 16px; border:1px solid rgba(255,255,255,0.08);">
            <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Total Pengeluaran Gaji</div>
            <div style="font-size:22px; font-weight:700; color:var(--success);">Rp <?= number_format($total_pengeluaran,0,',','.') ?></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Hari Hadir</th>
                    <th>Terlambat</th>
                    <th>Gaji Pokok</th>
                    <th>Tunjangan</th>
                    <th>Potongan</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payroll): ?>
                    <?php foreach ($payroll as $i => $py): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($py['nama']) ?></td>
                        <td><?= htmlspecialchars($py['jabatan']) ?></td>
                        <td><?= $py['hari_hadir'] ?> hari</td>
                        <td>
                            <?php if ($py['hari_terlambat'] > 0): ?>
                                <span style="color:#fbbf24;"><?= $py['hari_terlambat'] ?> hari</span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">0 hari</span>
                            <?php endif; ?>
                        </td>
                        <td>Rp <?= number_format($py['gaji_pokok'],0,',','.') ?></td>
                        <td>Rp <?= number_format($py['tunjangan'],0,',','.') ?></td>
                        <td style="color:#f87171;">Rp <?= number_format($py['potongan'],0,',','.') ?></td>
                        <td style="color:var(--success); font-weight:700;">Rp <?= number_format($py['total'],0,',','.') ?></td>
                        <td>
                            <?php if ($py['status'] === 'final'): ?>
                                <span class="badge badge-success">Final</span>
                            <?php elseif ($py['status'] === 'approved'): ?>
                                <span class="badge" style="background:rgba(99,102,241,0.2);color:#818cf8;">Approved</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($py['status'] === 'draft'): ?>
                            <form method="POST" action="tambah.php" style="margin:0;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= $py['id'] ?>">
                                <input type="hidden" name="status_baru" value="approved">
                                <input type="hidden" name="bulan" value="<?= $bulan ?>">
                                <input type="hidden" name="tahun" value="<?= $tahun ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="font-size:11px; padding:4px 8px;">
                                    <i class="fa-solid fa-check"></i> Approve
                                </button>
                            </form>
                            <?php elseif ($py['status'] === 'approved'): ?>
                            <form method="POST" action="tambah.php" style="margin:0;" onsubmit="return confirm('Tandai sebagai Final (sudah dibayar)?')">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?= $py['id'] ?>">
                                <input type="hidden" name="status_baru" value="final">
                                <input type="hidden" name="bulan" value="<?= $bulan ?>">
                                <input type="hidden" name="tahun" value="<?= $tahun ?>">
                                <button type="submit" class="btn btn-primary btn-sm" style="font-size:11px; padding:4px 8px;">
                                    <i class="fa-solid fa-circle-check"></i> Final
                                </button>
                            </form>
                            <?php else: ?>
                            <span style="font-size:11px; color:var(--text-muted);"><i class="fa-solid fa-lock"></i> Terkunci</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11">
                        <div class="empty-state"><i class="fa-solid fa-money-bill-slash"></i><p>Belum ada data payroll bulan ini</p></div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Payroll yang sudah ada bulan ini (untuk warning duplikat)
const existingPayroll = <?= json_encode(array_column($payroll, 'status', 'pegawai_id')) ?>;
const TUNJANGAN = 500000;
const POTONGAN_PER_TERLAMBAT = 50000;

function loadPreview() {
    const pegawaiId = document.getElementById('pegawaiSelect').value;
    const bulan     = document.getElementById('bulanSelect').value;
    const tahun     = document.getElementById('tahunInput').value;

    if (!pegawaiId) {
        document.getElementById('previewKalkulasi').style.display = 'none';
        document.getElementById('duplikatWarning').style.display  = 'none';
        return;
    }

    // Warning duplikat
    document.getElementById('duplikatWarning').style.display =
        existingPayroll[pegawaiId] ? 'block' : 'none';

    // Tampilkan box preview & loading
    document.getElementById('previewKalkulasi').style.display = 'block';
    document.getElementById('loadingState').style.display     = 'block';
    document.getElementById('previewContent').style.opacity   = '0.4';
    document.getElementById('noGajiWarning').style.display    = 'none';
    document.getElementById('btnSimpan').disabled             = true;

    // Fetch data absensi + gaji pokok dari tambah.php via AJAX
    fetch(`tambah.php?action=get_absensi&pegawai_id=${pegawaiId}&bulan=${bulan}&tahun=${tahun}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingState').style.display   = 'none';
            document.getElementById('previewContent').style.opacity = '1';

            const gajiPokok  = data.gaji_pokok  ?? 0;
            const terlambat  = data.terlambat   ?? 0;
            const hadir      = data.hadir        ?? 0;
            const potongan   = terlambat * POTONGAN_PER_TERLAMBAT;
            const total      = gajiPokok + TUNJANGAN - potongan;

            if (gajiPokok <= 0) {
                // Belum ada gaji pokok → tampilkan peringatan, nonaktifkan tombol
                document.getElementById('prev_gaji').textContent     = 'Belum diset';
                document.getElementById('prev_potongan').textContent = '—';
                document.getElementById('prev_total').textContent    = '—';
                document.getElementById('prev_absensi_info').style.display = 'none';
                document.getElementById('noGajiWarning').style.display    = 'block';
                document.getElementById('btnSimpan').disabled              = true;
            } else {
                document.getElementById('prev_gaji').textContent     = 'Rp ' + gajiPokok.toLocaleString('id-ID');
                document.getElementById('prev_potongan').textContent = 'Rp ' + potongan.toLocaleString('id-ID');
                document.getElementById('prev_total').textContent    = 'Rp ' + total.toLocaleString('id-ID');

                const infoEl = document.getElementById('prev_absensi_info');
                infoEl.textContent = `${hadir} hari hadir + ${terlambat} hari terlambat | Potongan: ${terlambat} × Rp 50.000`;
                infoEl.style.display = 'block';

                document.getElementById('noGajiWarning').style.display = 'none';
                document.getElementById('btnSimpan').disabled           = false;
            }
        })
        .catch(() => {
            document.getElementById('loadingState').style.display   = 'none';
            document.getElementById('previewContent').style.opacity = '1';
            document.getElementById('btnSimpan').disabled           = false;
        });
}

function resetForm() {
    document.getElementById('formPayroll').reset();
    document.getElementById('previewKalkulasi').style.display = 'none';
    document.getElementById('duplikatWarning').style.display  = 'none';
    document.getElementById('btnSimpan').disabled             = false;
}
</script>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>
