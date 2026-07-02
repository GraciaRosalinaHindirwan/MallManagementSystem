<?php
/**
 * PBI-M04-01-01
 * Facility Staff: Pencatatan data meter listrik/air secara manual maupun sensor IoT
 */
session_start();
require_once __DIR__ . '/../../config/konek.php';

// ── Proses POST ──────────────────────────────────────────────────────────────
$success_msg = $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_meter') {
        $unit_id   = (int)$_POST['unit_id'];
        $util_type = $conn->real_escape_string($_POST['utility_type']);
        $current   = (float)$_POST['current_reading'];
        $previous  = (float)$_POST['previous_reading'];
        $method    = $conn->real_escape_string($_POST['input_method']);
        $tarif     = (float)$_POST['tarif_per_unit'];
        $threshold = (float)$_POST['threshold_max'];
        $now       = date('Y-m-d H:i:s');

        $sql = "INSERT INTO 04_utility_meters
                    (unit_id, utility_type, current_reading, previous_reading,
                     reading_date, input_method, tarif_per_unit, threshold_max, status)
                VALUES ($unit_id,'$util_type',$current,$previous,'$now','$method',$tarif,$threshold,'active')";

        if ($conn->query($sql)) {
            $id_meter = $conn->insert_id;
            $uid = $_SESSION['user_id'] ?? 'NULL';
            $conn->query("INSERT INTO 04_utility_meter_logs (id_meter,reading_value,reading_date,recorded_by)
                          VALUES ($id_meter,$current,'$now',$uid)");
            $success_msg = 'Meter berhasil ditambahkan!';
        } else {
            $error_msg = 'Gagal menyimpan: ' . $conn->error;
        }
    }

    if ($_POST['action'] === 'catat_reading') {
        $id_meter  = (int)$_POST['id_meter'];
        $new_value = (float)$_POST['reading_value'];
        $now       = date('Y-m-d H:i:s');
        $uid       = $_SESSION['user_id'] ?? 'NULL';

        $conn->query("UPDATE 04_utility_meters
                      SET previous_reading=current_reading, current_reading=$new_value, reading_date='$now'
                      WHERE id_meter=$id_meter");
        $conn->query("INSERT INTO 04_utility_meter_logs (id_meter,reading_value,reading_date,recorded_by)
                      VALUES ($id_meter,$new_value,'$now',$uid)");
        $success_msg = 'Reading berhasil dicatat!';
    }
}

// ── Ambil data ───────────────────────────────────────────────────────────────
$units_q = $conn->query("
    SELECT u.id_units, u.unit_code, t.tenant_name
    FROM 01_units u JOIN 02_tenants t ON u.tenant_id=t.id_tenant
    ORDER BY u.unit_code
");

$meters_q = $conn->query("
    SELECT m.*, u.unit_code, t.tenant_name,
           ROUND(m.current_reading - m.previous_reading, 2) AS konsumsi
    FROM 04_utility_meters m
    JOIN 01_units u ON m.unit_id=u.id_units
    LEFT JOIN 02_tenants t ON u.tenant_id=t.id_tenant
    ORDER BY m.reading_date DESC
");

$logs_q = $conn->query("
    SELECT l.*, m.utility_type, u.unit_code, t.tenant_name
    FROM 04_utility_meter_logs l
    JOIN 04_utility_meters m ON l.id_meter=m.id_meter
    JOIN 01_units u ON m.unit_id=u.id_units
    LEFT JOIN 02_tenants t ON u.tenant_id=t.id_tenant
    ORDER BY l.reading_date DESC LIMIT 15
");

// ── Definisi variabel template ───────────────────────────────────────────────
$department_name = 'Utility Management';
$page_title      = 'Pencatatan Meter';
$user_name       = $_SESSION['nama'] ?? ($_SESSION['username'] ?? 'Facility Staff');
$menu_items = [
    ['icon'=>'fa-solid fa-gauge-high', 'label'=>'Pencatatan Meter', 'link'=>'../../pages/facilityStaff/utility_meter.php', 'active_page'=>'utility_meter']
];

// ── Konten halaman ───────────────────────────────────────────────────────────
$badgeColor = ['listrik'=>'#f59e0b','air'=>'#3b82f6','gas'=>'#ef4444','internet'=>'#8b5cf6','ac_central'=>'#06b6d4'];
$icon       = ['listrik'=>'fa-bolt','air'=>'fa-droplet','gas'=>'fa-fire-flame-curved','internet'=>'fa-wifi','ac_central'=>'fa-snowflake'];

ob_start();
?>

<?php if ($success_msg): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
<div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<!-- ── Header Section ── -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <div>
        <h2 style="margin:0;font-size:1.25rem;font-weight:700;color:var(--text);">
            <i class="fa-solid fa-gauge-high" style="color:var(--accent,#00D4D8);margin-right:8px;"></i>
            Daftar Meter Aktif
        </h2>
        <p style="margin:4px 0 0;font-size:.82rem;color:rgba(245,247,250,.5);">
            Pencatatan angka meter listrik, air, dan utilitas lainnya
        </p>
    </div>
    <button onclick="document.getElementById('modal-add-meter').classList.add('open')"
            class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Tambah Meter
    </button>
</div>

<!-- ── Tabel Meter Aktif ── -->
<div class="card" style="padding:0;overflow:hidden;margin-bottom:28px;">
    <div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Unit</th><th>Tenant</th><th>Tipe Utilitas</th>
            <th style="text-align:right;">Sebelumnya</th>
            <th style="text-align:right;">Saat Ini</th>
            <th style="text-align:right;">Konsumsi</th>
            <th>Metode</th><th style="text-align:center;">Status</th>
            <th style="text-align:center;">Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($m = $meters_q->fetch_assoc()):
            $anomali = $m['threshold_max'] > 0 && $m['konsumsi'] > $m['threshold_max'];
        ?>
        <tr style="<?= $anomali ? 'background:rgba(239,68,68,.06);' : '' ?>">
            <td><strong><?= htmlspecialchars($m['unit_code']) ?></strong></td>
            <td><?= htmlspecialchars($m['tenant_name'] ?? '-') ?></td>
            <td>
                <span class="badge <?= $m['utility_type']==='listrik'?'badge-warning':($m['utility_type']==='air'?'badge-info':'') ?>">
                    <i class="fa-solid <?= $icon[$m['utility_type']] ?? 'fa-circle' ?>" style="margin-right:4px;"></i>
                    <?= ucfirst($m['utility_type']) ?>
                </span>
            </td>
            <td style="text-align:right;color:rgba(245,247,250,.55);"><?= number_format($m['previous_reading'],2) ?></td>
            <td style="text-align:right;font-weight:700;"><?= number_format($m['current_reading'],2) ?></td>
            <td style="text-align:right;">
                <span style="font-weight:700;color:<?= $anomali?'#ef4444':'#22c55e' ?>;">
                    <?= $anomali ? '<i class="fa-solid fa-triangle-exclamation"></i> ' : '' ?>
                    <?= number_format($m['konsumsi'],2) ?>
                </span>
            </td>
            <td>
                <span class="badge badge-secondary">
                    <i class="fa-solid <?= $m['input_method']==='iot'?'fa-microchip':'fa-pen' ?>" style="margin-right:4px;"></i>
                    <?= ucfirst($m['input_method']) ?>
                </span>
            </td>
            <td style="text-align:center;">
                <?php if ($anomali): ?>
                    <span class="badge badge-danger">⚠ Anomali</span>
                <?php else: ?>
                    <span class="badge badge-success">✓ Normal</span>
                <?php endif; ?>
            </td>
            <td style="text-align:center;">
                <button onclick="bukaModalReading(<?= $m['id_meter'] ?>,'<?= $m['unit_code'] ?>','<?= $m['utility_type'] ?>',<?= $m['current_reading'] ?>)"
                        class="btn btn-secondary" style="padding:5px 12px;font-size:.78rem;">
                    <i class="fa-solid fa-pen-to-square"></i> Catat
                </button>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ── Riwayat Log ── -->
<h2 style="font-size:1rem;font-weight:700;margin-bottom:14px;color:var(--text);">
    <i class="fa-solid fa-clock-rotate-left" style="color:var(--accent,#00D4D8);margin-right:8px;"></i>
    Riwayat 15 Pencatatan Terakhir
</h2>
<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Tanggal</th><th>Unit</th><th>Tenant</th><th>Utilitas</th>
            <th style="text-align:right;">Angka Meter</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($log = $logs_q->fetch_assoc()): ?>
        <tr>
            <td style="color:rgba(245,247,250,.55);"><?= date('d/m/Y H:i', strtotime($log['reading_date'])) ?></td>
            <td><strong><?= htmlspecialchars($log['unit_code']) ?></strong></td>
            <td><?= htmlspecialchars($log['tenant_name'] ?? '-') ?></td>
            <td>
                <span class="badge badge-info" style="font-size:.72rem;">
                    <i class="fa-solid <?= $icon[$log['utility_type']] ?? 'fa-circle' ?>" style="margin-right:4px;"></i>
                    <?= ucfirst($log['utility_type']) ?>
                </span>
            </td>
            <td style="text-align:right;font-weight:700;"><?= number_format($log['reading_value'],2) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ── Modal: Catat Reading ── -->
<div id="modal-reading" class="modal-overlay">
    <div class="modal-box">
        <h3 class="modal-title" id="modal-reading-title">
            <i class="fa-solid fa-gauge-high" style="color:var(--accent,#00D4D8);margin-right:8px;"></i>
            Catat Reading Meter
        </h3>
        <form method="POST">
            <input type="hidden" name="action" value="catat_reading">
            <input type="hidden" name="id_meter" id="inp-id-meter">

            <div class="form-group">
                <label class="form-label">Unit & Utilitas</label>
                <div id="inp-meter-info" class="form-control" style="pointer-events:none;opacity:.7;"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Angka Meter Sebelumnya</label>
                <div id="inp-prev-info" class="form-control" style="pointer-events:none;opacity:.7;"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Angka Meter Baru <span style="color:#ef4444">*</span></label>
                <input type="number" name="reading_value" id="inp-reading-value"
                       step="0.01" min="0" required class="form-control"
                       style="font-size:1.1rem;font-weight:700;">
            </div>
            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="button" onclick="document.getElementById('modal-reading').classList.remove('open')"
                        class="btn btn-secondary" style="flex:1;">Batal</button>
                <button type="submit" class="btn btn-primary" style="flex:2;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── Modal: Tambah Meter Baru ── -->
<div id="modal-add-meter" class="modal-overlay">
    <div class="modal-box" style="width:520px;">
        <h3 class="modal-title">
            <i class="fa-solid fa-plus-circle" style="color:var(--accent,#00D4D8);margin-right:8px;"></i>
            Tambah Meter Baru
        </h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_meter">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="form-group">
                    <label class="form-label">Unit <span style="color:#ef4444">*</span></label>
                    <select name="unit_id" required class="form-control">
                        <option value="">-- Pilih Unit --</option>
                        <?php $units_q->data_seek(0); while ($u = $units_q->fetch_assoc()): ?>
                        <option value="<?= $u['id_units'] ?>"><?= htmlspecialchars($u['unit_code'].' — '.$u['tenant_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Utilitas <span style="color:#ef4444">*</span></label>
                    <select name="utility_type" required class="form-control">
                        <option value="listrik">⚡ Listrik</option>
                        <option value="air">💧 Air</option>
                        <option value="gas">🔥 Gas</option>
                        <option value="internet">📶 Internet</option>
                        <option value="ac_central">❄️ AC Central</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Angka Meter Saat Ini</label>
                    <input type="number" name="current_reading" step="0.01" value="0" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Angka Meter Sebelumnya</label>
                    <input type="number" name="previous_reading" step="0.01" value="0" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Metode Input</label>
                    <select name="input_method" class="form-control">
                        <option value="manual">Manual</option>
                        <option value="iot">IoT/Sensor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tarif/Unit (Rp)</label>
                    <input type="number" name="tarif_per_unit" step="0.01" value="1500" class="form-control">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Threshold Maksimum Konsumsi</label>
                    <input type="number" name="threshold_max" step="0.01" value="500" class="form-control">
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="button" onclick="document.getElementById('modal-add-meter').classList.remove('open')"
                        class="btn btn-secondary" style="flex:1;">Batal</button>
                <button type="submit" class="btn btn-primary" style="flex:2;">
                    <i class="fa-solid fa-plus"></i> Tambah Meter
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalReading(id, unit, tipe, prev) {
    document.getElementById('inp-id-meter').value = id;
    document.getElementById('modal-reading-title').innerHTML =
        '<i class="fa-solid fa-gauge-high" style="color:var(--accent,#00D4D8);margin-right:8px;"></i>Catat Reading — ' + unit;
    document.getElementById('inp-meter-info').textContent = unit + ' · ' + tipe.charAt(0).toUpperCase() + tipe.slice(1);
    document.getElementById('inp-prev-info').textContent = parseFloat(prev).toLocaleString('id-ID',{minimumFractionDigits:2});
    document.getElementById('inp-reading-value').min = prev;
    document.getElementById('inp-reading-value').value = '';
    document.getElementById('modal-reading').classList.add('open');
}
// Tutup modal kalau klik overlay
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); });
});
</script>

<?php
$content = ob_get_clean();
$conn->close();
require_once __DIR__ . '/../../includes/navbar.php';
?>
