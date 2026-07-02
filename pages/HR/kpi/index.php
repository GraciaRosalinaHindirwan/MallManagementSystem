<?php
$page_title = 'KPI Pegawai';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

$pegawais = $pdo->query("SELECT id, nama FROM pegawai WHERE status='aktif' ORDER BY nama")->fetchAll();

$sql = "SELECT k.*, p.nama, p.jabatan FROM kpi k JOIN pegawai p ON k.pegawai_id = p.id ORDER BY k.created_at DESC";
$kpi_data = $pdo->query($sql)->fetchAll();
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'simpan'): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> KPI berhasil dihitung dan disimpan!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'exists'): ?>
    <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> KPI untuk pegawai dan periode ini sudah ada.</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'nodata'): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Tidak ada data absensi pada periode tersebut.</div>
<?php endif; ?>

<!-- FORM HITUNG KPI OTOMATIS -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Hitung KPI Otomatis</h2>
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
                <label>Bulan <span style="color:var(--danger)">*</span></label>
                <select name="bulan" required>
                    <?php
                    $bulan_list = [
                        '01' => 'Januari',
                        '02' => 'Februari',
                        '03' => 'Maret',
                        '04' => 'April',
                        '05' => 'Mei',
                        '06' => 'Juni',
                        '07' => 'Juli',
                        '08' => 'Agustus',
                        '09' => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember'
                    ];
                    foreach ($bulan_list as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $val == date('m') ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tahun <span style="color:var(--danger)">*</span></label>
                <select name="tahun" required>
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Catatan <span style="color:var(--text-muted)">(opsional)</span></label>
                <input type="text" name="catatan" placeholder="Catatan evaluator...">
            </div>
        </div>

        <!-- Info bobot penilaian -->
        <div style="margin-top:12px; padding:12px 16px; background:rgba(255,255,255,0.04); border-radius:8px; border-left:3px solid var(--accent);">
            <p style="font-size:var(--small); color:var(--text-muted); margin:0 0 6px 0;">
                <i class="fa-solid fa-circle-info"></i> <strong style="color:var(--text)">Bobot Penilaian Otomatis:</strong>
            </p>
            <div style="display:flex; gap:16px; flex-wrap:wrap;">
                <span style="font-size:var(--small); color:var(--text-muted)">📊 Kehadiran — 40%</span>
                <span style="font-size:var(--small); color:var(--text-muted)">⏰ Ketepatan Waktu — 30%</span>
                <span style="font-size:var(--small); color:var(--text-muted)">📋 Disiplin Cuti — 30%</span>
            </div>
        </div>

        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-calculator"></i> Hitung & Simpan KPI</button>
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
                <tr>
                    <th>#</th>
                    <th>Pegawai</th>
                    <th>Jabatan</th>
                    <th>Periode</th>
                    <th>Nilai</th>
                    <th>Kategori</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($kpi_data): ?>
                    <?php
                    $kategori_badge = [
                        'sangat_baik' => 'badge-success',
                        'baik'        => 'badge-info',
                        'cukup'       => 'badge-warning',
                        'kurang'      => 'badge-danger',
                    ];
                    $label = ['sangat_baik' => 'Sangat Baik', 'baik' => 'Baik', 'cukup' => 'Cukup', 'kurang' => 'Kurang'];
                    ?>
                    <?php foreach ($kpi_data as $i => $k): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($k['nama']) ?></td>
                            <td><?= htmlspecialchars($k['jabatan']) ?></td>
                            <td><?= htmlspecialchars($k['periode']) ?></td>
                            <td>
                                <?php
                                $n = $k['nilai'];
                                if ($n >= 85)     $bar_color = 'var(--success)';
                                elseif ($n >= 70) $bar_color = 'var(--accent)';
                                elseif ($n >= 55) $bar_color = 'var(--text-accent)';
                                else              $bar_color = 'var(--danger)';
                                ?>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:60px; height:6px; background:rgba(255,255,255,0.1); border-radius:3px;">
                                        <div style="width:<?= $n ?>%; height:100%; background:<?= $bar_color ?>; border-radius:3px;"></div>
                                    </div>
                                    <strong><?= $n ?></strong>
                                </div>
                            </td>
                            <td><span class="badge <?= $kategori_badge[$k['kategori']] ?>"><?= $label[$k['kategori']] ?></span></td>
                            <td><?= htmlspecialchars(substr($k['catatan'] ?? '-', 0, 40)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state"><i class="fa-solid fa-chart-line"></i>
                                <p>Belum ada data KPI</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>