<?php
/** @var mysqli $conn */ // Memberitahu VS Code kalau $conn itu objek database sah!

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeStaff') {
    // Jika bukan Finance Staff, tendang kembali ke halaman utama login
    header("Location: ../../index.php"); 
    exit();
}
*/

// Sesi default sementara dibiarkan aktif agar aman dicoba langsung sekarang
$_SESSION['role'] = 'financeStaff';
$_SESSION['nama'] = 'Finance Staff';

// HAPUS BARIS INI (Sudah dihapus): session_start();
require_once __DIR__ . '/../../config/konek.php';

$success_msg = $error_msg = '';


$success_msg = $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'terbitkan') {
        $id = (int)$_POST['id_invoice'];
        $conn->query("UPDATE 04_invoice_utilitas SET status='terbit' WHERE id_invoice=$id AND status='draft'")
            ? $success_msg = 'Invoice berhasil diterbitkan!'
            : $error_msg = 'Gagal menerbitkan: '.$conn->error;
    }
    if ($_POST['action'] === 'bayar') {
        $id = (int)$_POST['id_invoice'];
        $conn->query("UPDATE 04_invoice_utilitas SET status='paid' WHERE id_invoice=$id AND status='terbit'")
            ? $success_msg = 'Invoice ditandai Lunas!'
            : $error_msg = 'Gagal memperbarui: '.$conn->error;
    }
    if ($_POST['action'] === 'buat_invoice') {
        $id_meter  = (int)$_POST['id_meter'];
        $tenant_id = (int)$_POST['tenant_id'];
        $period    = $conn->real_escape_string($_POST['billing_period']);
        $meter = $conn->query("SELECT * FROM 04_utility_meters WHERE id_meter=$id_meter")->fetch_assoc();
        if ($meter) {
            $konsumsi = $meter['current_reading'] - $meter['previous_reading'];
            $total    = $konsumsi * $meter['tarif_per_unit'];
            $conn->query("INSERT INTO 04_invoice_utilitas (tenant_id,id_meter,billing_period,total_consumption,total,status)
                          VALUES ($tenant_id,$id_meter,'$period',$konsumsi,$total,'draft')")
                ? $success_msg = 'Invoice dibuat! Total: Rp '.number_format($total,0,',','.')
                : $error_msg = 'Gagal: '.$conn->error;
        }
    }
}

// ── Ambil data ───────────────────────────────────────────────────────────────
$inv_q = $conn->query("
    SELECT i.*, t.tenant_name, m.utility_type, m.tarif_per_unit, u.unit_code
    FROM 04_invoice_utilitas i
    JOIN 02_tenants t ON i.tenant_id=t.id_tenant
    LEFT JOIN 04_utility_meters m ON i.id_meter=m.id_meter
    LEFT JOIN 01_units u ON m.unit_id=u.id_units
    ORDER BY i.billing_period DESC, i.id_invoice DESC
");
$stat = $conn->query("
    SELECT COUNT(*) AS total,
           SUM(CASE WHEN status='draft'   THEN 1 ELSE 0 END) jml_draft,
           SUM(CASE WHEN status='terbit'  THEN 1 ELSE 0 END) jml_terbit,
           SUM(CASE WHEN status='paid'    THEN 1 ELSE 0 END) jml_paid,
           SUM(CASE WHEN status='overdue' THEN 1 ELSE 0 END) jml_overdue,
           SUM(total) total_tagihan,
           SUM(CASE WHEN status='paid'    THEN total ELSE 0 END) total_lunas,
           SUM(CASE WHEN status='overdue' THEN total ELSE 0 END) total_overdue
    FROM 04_invoice_utilitas
")->fetch_assoc();
$meters_dd = $conn->query("
    SELECT m.id_meter, m.utility_type, u.unit_code, t.id_tenant, t.tenant_name,
           ROUND((m.current_reading-m.previous_reading)*m.tarif_per_unit,2) AS estimasi_total
    FROM 04_utility_meters m
    JOIN 01_units u ON m.unit_id=u.id_units
    JOIN 02_tenants t ON u.tenant_id=t.id_tenant
    ORDER BY u.unit_code
");

$department_name = 'Utility Management';
$page_title      = 'Invoice Utilitas';
$user_name       = $_SESSION['nama'] ?? ($_SESSION['username'] ?? 'Finance Staff');
$menu_items = [
    [
        'icon'        => 'fa-solid fa-gauge',
        'label'       => 'Dashboard Staff',
        'link'        => 'dashboardStaff.php',
        'active_page' => 'Dashboard Staff'
    ],
    [
        'icon'        => 'fa-solid fa-file-invoice',
        'label'       => 'Invoice Management',
        'link'        => 'invoiceManagement.php',
        'active_page' => 'Invoice Management'
    ],
    [
        'icon'        => 'fa-solid fa-bolt-lightning', 
        'label'       => 'Invoice Utilitas (Air/Listrik)',
        'link'        => 'utility_invoice.php', 
        'active_page' => 'utility_invoice'
    ],
    [
        'icon'        => 'fa-solid fa-cash-register',
        'label'       => 'Billing System',
        'link'        => 'billingManagement.php',
        'active_page' => 'Billing System'
    ],
    [
        'icon'        => 'fa-solid fa-file-invoice-dollar',
        'label'       => 'Vendor Bill',
        'link'        => 'vendor_bill.php', 
        'active_page' => 'Vendor Bill'
    ],
    [
        'icon'        => 'fa-solid fa-book',
        'label'       => 'Jurnal Otomatis',
        'link'        => 'journalManagement.php',
        'active_page' => 'Jurnal Otomatis'
    ],
    [
        'icon'        => 'fa-solid fa-folder-open',
        'label'       => 'Dashboard Non Sewa',
        'link'        => 'dashboardNonSewa.php',
        'active_page' => 'Dashboard Non Sewa'
    ]
];


$statusBadge = ['draft'=>'badge-secondary','terbit'=>'badge-info','paid'=>'badge-success','overdue'=>'badge-danger'];
$statusLabel = ['draft'=>'📝 Draft','terbit'=>'📄 Terbit','paid'=>'✅ Lunas','overdue'=>'🔴 Overdue'];
$utilIcon    = ['listrik'=>'⚡','air'=>'💧','gas'=>'🔥','internet'=>'📶','ac_central'=>'❄️'];

ob_start();
?>

<?php if ($success_msg): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
<div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<!-- ── Stats ── -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left-color:var(--accent,#00D4D8);">
        <div class="stat-icon" style="background:rgba(0,212,216,.12);color:var(--accent,#00D4D8);">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div class="stat-info">
            <h3>Rp <?= number_format($stat['total_tagihan'],0,',','.') ?></h3>
            <p>Total Tagihan (<?= $stat['total'] ?> invoice)</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color:#f59e0b;">
        <div class="stat-icon" style="background:rgba(245,158,11,.15);color:#f59e0b;">
            <i class="fa-solid fa-file-lines"></i>
        </div>
        <div class="stat-info">
            <h3><?= $stat['jml_draft'] ?> Draft</h3>
            <p><?= $stat['jml_terbit'] ?> Terbit · <?= $stat['jml_paid'] ?> Lunas</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color:#22c55e;">
        <div class="stat-icon" style="background:rgba(34,197,94,.15);color:#22c55e;">
            <i class="fa-solid fa-check-double"></i>
        </div>
        <div class="stat-info">
            <h3>Rp <?= number_format($stat['total_lunas'],0,',','.') ?></h3>
            <p>Total Terlunasi</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color:#ef4444;">
        <div class="stat-icon" style="background:rgba(239,68,68,.15);color:#ef4444;">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div class="stat-info">
            <h3 style="color:<?= $stat['jml_overdue']>0?'#ef4444':'' ?>;">
                Rp <?= number_format($stat['total_overdue'],0,',','.') ?>
            </h3>
            <p><?= $stat['jml_overdue'] ?> invoice overdue</p>
        </div>
    </div>
</div>

<!-- ── Header + Tombol ── -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h2 style="margin:0;font-size:1rem;font-weight:700;color:var(--text);">
        <i class="fa-solid fa-file-invoice-dollar" style="color:var(--accent,#00D4D8);margin-right:8px;"></i>
        Daftar Invoice Utilitas
    </h2>
    <button onclick="document.getElementById('modal-buat').classList.add('open')" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Buat Invoice
    </button>
</div>

<!-- ── Tabel ── -->
<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>#</th><th>Tenant</th><th>Unit</th><th>Utilitas</th>
            <th>Periode</th>
            <th style="text-align:right;">Konsumsi</th>
            <th style="text-align:right;">Total (Rp)</th>
            <th style="text-align:center;">Status</th>
            <th style="text-align:center;">Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($inv = $inv_q->fetch_assoc()): $st = $inv['status']; ?>
        <tr>
            <td style="color:rgba(245,247,250,.35);font-size:.78rem;">#<?= $inv['id_invoice'] ?></td>
            <td><strong><?= htmlspecialchars($inv['tenant_name']) ?></strong></td>
            <td><?= htmlspecialchars($inv['unit_code'] ?? '-') ?></td>
            <td><?= $utilIcon[$inv['utility_type']] ?? '🔌' ?> <?= ucfirst($inv['utility_type'] ?? '-') ?></td>
            <td style="color:rgba(245,247,250,.55);"><?= date('M Y', strtotime($inv['billing_period'])) ?></td>
            <td style="text-align:right;"><?= number_format($inv['total_consumption'],2) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--accent,#00D4D8);">
                <?= number_format($inv['total'],0,',','.') ?>
            </td>
            <td style="text-align:center;">
                <span class="badge <?= $statusBadge[$st] ?? '' ?>"><?= $statusLabel[$st] ?? $st ?></span>
            </td>
            <td style="text-align:center;">
                <?php if ($st==='draft'): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="terbitkan">
                    <input type="hidden" name="id_invoice" value="<?= $inv['id_invoice'] ?>">
                    <button type="submit" class="btn btn-secondary" style="padding:4px 12px;font-size:.78rem;">
                        <i class="fa-solid fa-paper-plane"></i> Terbitkan
                    </button>
                </form>
                <?php elseif ($st==='terbit'): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="bayar">
                    <input type="hidden" name="id_invoice" value="<?= $inv['id_invoice'] ?>">
                    <button type="submit" class="btn btn-success" style="padding:4px 12px;font-size:.78rem;">
                        <i class="fa-solid fa-check"></i> Lunas
                    </button>
                </form>
                <?php else: ?>
                <span style="color:rgba(245,247,250,.25);font-size:.8rem;">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ── Modal: Buat Invoice ── -->
<div id="modal-buat" class="modal-overlay">
    <div class="modal-box">
        <h3 class="modal-title">
            <i class="fa-solid fa-file-invoice-dollar" style="color:var(--accent,#00D4D8);margin-right:8px;"></i>
            Buat Invoice Baru
        </h3>
        <form method="POST" id="form-buat">
            <input type="hidden" name="action" value="buat_invoice">
            <input type="hidden" name="tenant_id" id="inp-tenant-id">

            <div class="form-group">
                <label class="form-label">Pilih Meter / Unit <span style="color:#ef4444">*</span></label>
                <select name="id_meter" id="sel-meter" required class="form-control" onchange="updateEstimasi(this)">
                    <option value="">-- Pilih Meter --</option>
                    <?php while ($md = $meters_dd->fetch_assoc()): ?>
                    <option value="<?= $md['id_meter'] ?>"
                            data-tenant="<?= $md['id_tenant'] ?>"
                            data-est="<?= number_format($md['estimasi_total'],0,',','.') ?>">
                        <?= htmlspecialchars($md['unit_code'].' · '.$md['tenant_name'].' — '.ucfirst($md['utility_type'])) ?>
                        (Est. Rp <?= number_format($md['estimasi_total'],0,',','.') ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div id="box-est" style="display:none;" class="form-group">
                <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);border-radius:8px;padding:12px 14px;">
                    <div style="font-size:.78rem;color:rgba(245,247,250,.5);font-weight:600;">Estimasi Total Invoice</div>
                    <div id="lbl-est" style="font-size:1.3rem;font-weight:800;color:#86efac;"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Periode Tagihan <span style="color:#ef4444">*</span></label>
                <input type="month" name="billing_period" required value="<?= date('Y-m') ?>" class="form-control">
            </div>

            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="button" onclick="document.getElementById('modal-buat').classList.remove('open')"
                        class="btn btn-secondary" style="flex:1;">Batal</button>
                <button type="submit" class="btn btn-primary" style="flex:2;">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Buat Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateEstimasi(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('inp-tenant-id').value = opt.dataset.tenant || '';
    const box = document.getElementById('box-est');
    if (sel.value && opt.dataset.est) {
        document.getElementById('lbl-est').textContent = 'Rp ' + opt.dataset.est;
        box.style.display = 'block';
    } else { box.style.display = 'none'; }
}
document.querySelectorAll('.modal-overlay').forEach(el =>
    el.addEventListener('click', function(e){ if(e.target===this) this.classList.remove('open'); }));
</script>

<?php
$content = ob_get_clean();
$conn->close();
require_once __DIR__ . '/../../includes/navbarMO6.php';
?>
