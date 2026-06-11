<?php
$page_title = 'KPI Pegawai';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

$periode = $_GET['periode'] ?? date('Y-Q' . ceil(date('n')/3));
$pegawais = $pdo->query("SELECT id, nama FROM pegawai WHERE status='aktif' ORDER BY nama")->fetchAll();

$sql = "SELECT k.*, p.nama, p.jabatan FROM kpi k JOIN pegawai p ON k.pegawai_id = p.id ORDER BY k.created_at DESC";
$kpi_data = $pdo->query($sql)->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Data KPI berhasil disimpan!</div>
<?php endif; ?>

<!-- FORM INPUT KPI -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Input KPI</h2>
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
                <label>Periode <span style="color:var(--danger)">*</span></label>
                <input type="text" name="periode" placeholder="Q1-2025, Jan 2025, dll" required>
            </div>
            <div class="form-group">
                <label>Nilai (0-100) <span style="color:var(--danger)">*</span></label>
                <input type="number" name="nilai" min="0" max="100" placeholder="85" required
                    oninput="updateKategori(this.value)">
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori" id="kategori">
                    <option value="sangat_baik">Sangat Baik (≥85)</option>
                    <option value="baik">Baik (70–84)</option>
                    <option value="cukup" selected>Cukup (55–69)</option>
                    <option value="kurang">Kurang (&lt;55)</option>
                </select>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>Target Kerja</label>
                <textarea name="target_kerja" rows="2" placeholder="Target yang harus dicapai..."></textarea>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>Realisasi</label>
                <textarea name="realisasi" rows="2" placeholder="Hasil yang dicapai..."></textarea>
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>Catatan</label>
                <textarea name="catatan" rows="2" placeholder="Catatan evaluator..."></textarea>
            </div>
        </div>
        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan KPI</button>
        </div>
    </form>
</div>

<!-- TABEL KPI -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Data KPI</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Pegawai</th><th>Jabatan</th><th>Periode</th><th>Nilai</th><th>Kategori</th><th>Catatan</th></tr>
            </thead>
            <tbody>
                <?php if ($kpi_data): ?>
                    <?php foreach ($kpi_data as $i => $k): ?>
                    <?php
                        $kategori_badge = [
                            'sangat_baik' => 'badge-success',
                            'baik'        => 'badge-info',
                            'cukup'       => 'badge-warning',
                            'kurang'      => 'badge-danger',
                        ];
                        $label = ['sangat_baik'=>'Sangat Baik','baik'=>'Baik','cukup'=>'Cukup','kurang'=>'Kurang'];
                    ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($k['nama']) ?></td>
                        <td><?= htmlspecialchars($k['jabatan']) ?></td>
                        <td><?= htmlspecialchars($k['periode']) ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:60px; height:6px; background:rgba(255,255,255,0.1); border-radius:3px;">
                                    <div style="width:<?= $k['nilai'] ?>%; height:100%; background:<?= $k['nilai']>=85?'var(--success)':($k['nilai']>=70?'var(--accent)':($k['nilai']>=55?'var(--text-accent)':'var(--danger)')) ?>; border-radius:3px;"></div>
                                </div>
                                <strong><?= $k['nilai'] ?></strong>
                            </div>
                        </td>
                        <td><span class="badge <?= $kategori_badge[$k['kategori']] ?>"><?= $label[$k['kategori']] ?></span></td>
                        <td><?= htmlspecialchars(substr($k['catatan'] ?? '-', 0, 40)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">
                        <div class="empty-state"><i class="fa-solid fa-chart-line"></i><p>Belum ada data KPI</p></div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateKategori(val) {
    const sel = document.getElementById('kategori');
    val = parseInt(val);
    if (val >= 85) sel.value = 'sangat_baik';
    else if (val >= 70) sel.value = 'baik';
    else if (val >= 55) sel.value = 'cukup';
    else sel.value = 'kurang';
}
</script>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>
