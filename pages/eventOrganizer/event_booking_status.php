<?php
// pages/eventOrganizer/event_booking_status.php
// PBI-M04-03-01 — Status & Timeline Pengajuan Booking Event
require_once __DIR__ . '/../../public/auth/checkSession.php';
require_once '../eventManager/event_data.php';

if (!defined('BASE_URL')) {
    $project_root = realpath(__DIR__ . '/../..');
    $doc_root     = realpath($_SERVER['DOCUMENT_ROOT']);
    $base = '';
    if ($doc_root && $project_root && strpos($project_root, $doc_root) === 0) {
        $base = substr($project_root, strlen($doc_root));
    }
    $base = str_replace('\\', '/', $base);
    define('BASE_URL', $base);
}

$department_name = 'Event Management';
$menu_items = [
    [
        'icon'        => 'fa-solid fa-house',
        'label'       => 'Dashboard',
        'link'        => BASE_URL . '/pages/eventManager/index.php',
        'active_page' => 'index',
    ],
    [
        'icon'        => 'fa-solid fa-calendar-plus',
        'label'       => 'Form Booking',
        'link'        => BASE_URL . '/pages/eventOrganizer/event_booking_form.php',
        'active_page' => 'event_booking_form',
    ],
    [
        'icon'        => 'fa-solid fa-list-check',
        'label'       => 'Status Pengajuan',
        'link'        => BASE_URL . '/pages/eventOrganizer/event_booking_status.php',
        'active_page' => 'event_booking_status',
    ],
    [
        'icon'        => 'fa-solid fa-calendar-week',
        'label'       => 'Kalender & Approval',
        'link'        => BASE_URL . '/pages/eventManager/event_calendar.php',
        'active_page' => 'event_calendar',
    ],
    [
        'icon'        => 'fa-solid fa-people-group',
        'label'       => 'Vendor & Tiket',
        'link'        => BASE_URL . '/pages/eventManager/event_vendor_ticketing.php',
        'active_page' => 'event_vendor_ticketing',
    ],
    [
        'icon'        => 'fa-solid fa-chart-line',
        'label'       => 'Analytics',
        'link'        => BASE_URL . '/pages/eventManager/event_analytics.php',
        'active_page' => 'event_analytics',
    ],
];

$page_title = 'Status Pengajuan Event';
$page       = 'event_booking_status';

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    deleteBooking((int)$_POST['delete_id']);
    header('Location: event_booking_status.php?deleted=1');
    exit;
}

$semua   = getBookings();
$deleted = isset($_GET['deleted']);

// Hitung counts
$counts = ['pending'=>0, 'approved'=>0, 'revision'=>0, 'rejected'=>0];
foreach ($semua as $p) if (isset($counts[$p['status']])) $counts[$p['status']]++;

ob_start();
?>

<?php if ($deleted): ?>
<div id="toastDeleted" style="position:fixed;top:1.5rem;right:1.5rem;background:var(--success);
     color:#fff;padding:.6rem 1.2rem;border-radius:8px;font-size:13px;z-index:9999">
    <i class="bi bi-check-circle me-2"></i>Pengajuan berhasil dihapus.
</div>
<script>setTimeout(()=>document.getElementById('toastDeleted').remove(),3000)</script>
<?php endif; ?>

<!-- Header actions -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <button class="filter-btn active" onclick="filterStatus('all',this)"
                style="background:var(--accent);color:#021F42;border:none;border-radius:20px;
                       padding:4px 14px;font-size:12px;font-weight:600;cursor:pointer">
            Semua (<?= count($semua) ?>)
        </button>
        <?php
        $lbls = ['pending'=>'Pending','approved'=>'Approved','revision'=>'Perlu Revisi','rejected'=>'Ditolak'];
        foreach ($lbls as $k => $v): ?>
        <button class="filter-btn" onclick="filterStatus('<?= $k ?>',this)"
                style="background:var(--primary);border:1px solid rgba(255,255,255,.15);color:var(--text);
                       border-radius:20px;padding:4px 14px;font-size:12px;cursor:pointer">
            <?= $v ?> (<?= $counts[$k] ?>)
        </button>
        <?php endforeach; ?>
    </div>
    <a href="event_booking_form.php"
       style="background:var(--accent);color:#021F42;font-weight:600;border:none;
              padding:.45rem 1.1rem;border-radius:8px;text-decoration:none;font-size:13px">
        <i class="bi bi-plus-lg me-1"></i>Ajukan Baru
    </a>
</div>

<!-- Empty state -->
<?php if (empty($semua)): ?>
<div style="text-align:center;padding:3rem;opacity:.5">
    <i class="bi bi-inbox" style="font-size:3rem;display:block;margin-bottom:.5rem"></i>
    Belum ada pengajuan.
    <a href="event_booking_form.php" style="color:var(--accent)">Buat sekarang</a>.
</div>
<?php endif; ?>

<!-- Cards -->
<?php foreach ($semua as $p):
    // Build timeline steps
    $steps = [
        ['label' => 'Diajukan',     'state' => 'done'],
        ['label' => 'Review Admin', 'state' => in_array($p['status'], ['approved','rejected','revision']) ? 'done' : 'active'],
        ['label' => 'Persetujuan',
         'state' => $p['status'] === 'approved' ? 'done' : ($p['status'] === 'rejected' ? 'fail' : ($p['status'] === 'revision' ? 'active' : ''))],
        ['label' => 'Kontrak & DP', 'state' => $p['status'] === 'approved' ? 'active' : ''],
    ];
    $border_colors = [
        'pending'  => '#f59e0b',
        'approved' => 'var(--success)',
        'rejected' => 'var(--danger)',
        'revision' => 'var(--secondary)',
    ];
    $bc = $border_colors[$p['status']] ?? 'rgba(255,255,255,.2)';
?>
<div class="status-card" data-status="<?= $p['status'] ?>"
     style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-left:4px solid <?= $bc ?>;
            border-radius:12px;margin-bottom:1rem;transition:border-color .2s">
    <div style="padding:1.25rem 1.5rem">

        <!-- Header row -->
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <span style="font-size:12px;color:var(--accent);font-weight:600">#<?= $p['id_booking'] ?></span>
                <h6 class="mb-0 mt-1 fw-bold"><?= htmlspecialchars($p['nama_event']) ?></h6>
                <small style="opacity:.5"><?= htmlspecialchars($p['tipe_event']) ?> · <?= htmlspecialchars($p['nama_pemohon'] ?? '') ?></small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?= statusBadge($p['status']) ?>
                <button onclick="confirmDel(<?= $p['id_booking'] ?>,'<?= addslashes(htmlspecialchars($p['nama_event'])) ?>')"
                        style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);
                               color:#fca5a5;border-radius:6px;padding:3px 10px;font-size:11px;cursor:pointer">
                    <i class="bi bi-trash3 me-1"></i>Hapus
                </button>
            </div>
        </div>

        <!-- Meta -->
        <div class="row g-3 mt-1">
            <div class="col-6 col-md-3">
                <div style="font-size:11px;opacity:.55;text-transform:uppercase">Area</div>
                <div style="font-size:13px;font-weight:500"><?= htmlspecialchars($p['nama_area']) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div style="font-size:11px;opacity:.55;text-transform:uppercase">Tanggal</div>
                <div style="font-size:13px">
                    <?= date('d M Y H:i', strtotime($p['tanggal_mulai'])) ?>
                    <?php if ($p['tanggal_mulai'] !== $p['tanggal_selesai']): ?>
                    <br><small style="opacity:.5">s/d <?= date('d M Y H:i', strtotime($p['tanggal_selesai'])) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div style="font-size:11px;opacity:.55;text-transform:uppercase">Est. Pengunjung</div>
                <div style="font-size:13px;font-weight:500"><?= number_format($p['estimasi_pengunjung']) ?> pax</div>
            </div>
        </div>

        <!-- Catatan admin -->
        <?php if (!empty($p['catatan_admin'])): ?>
        <div style="background:rgba(255,255,255,.04);border-left:3px solid var(--secondary);
                    border-radius:0 6px 6px 0;padding:.6rem 1rem;font-size:12px;margin-top:.75rem">
            <i class="bi bi-chat-left-text me-1" style="color:var(--accent)"></i>
            <strong>Catatan Admin:</strong> <?= htmlspecialchars($p['catatan_admin']) ?>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div style="display:flex;margin-top:1rem">
            <?php foreach ($steps as $i => $st):
                $dot_bg = match($st['state']) {
                    'done'   => 'var(--success)',
                    'fail'   => 'var(--danger)',
                    'active' => '#f59e0b',
                    default  => 'var(--primary-dark)',
                };
                $icon = match($st['state']) {
                    'done'   => 'bi-check-lg',
                    'fail'   => 'bi-x-lg',
                    'active' => 'bi-clock',
                    default  => 'bi-circle',
                };
            ?>
            <div style="flex:1;text-align:center;position:relative">
                <?php if ($i < count($steps)-1): ?>
                <div style="position:absolute;top:14px;left:50%;right:-50%;height:2px;
                            background:rgba(255,255,255,.1);z-index:0"></div>
                <?php endif; ?>
                <div style="width:28px;height:28px;border-radius:50%;background:<?= $dot_bg ?>;
                            display:flex;align-items:center;justify-content:center;margin:0 auto;
                            position:relative;z-index:1;font-size:12px;color:#fff">
                    <i class="bi <?= $icon ?>"></i>
                </div>
                <div style="font-size:10px;opacity:.6;margin-top:4px"><?= $st['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>
<?php endforeach; ?>

<!-- Delete form & modal -->
<form method="POST" id="delForm" style="display:none">
    <input type="hidden" name="delete_id" id="delId">
</form>
<div class="modal fade" id="delModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="background:var(--primary);color:var(--text);border:1px solid rgba(255,255,255,.1)">
      <div class="modal-body text-center py-4">
        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
        <p class="fw-bold mb-1">Hapus Pengajuan?</p>
        <p id="delDesc" style="font-size:12px;opacity:.7" class="mb-3"></p>
        <div class="d-flex gap-2 justify-content-center">
            <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            <button class="btn btn-sm" style="background:var(--danger);color:#fff"
                    onclick="document.getElementById('delForm').submit()">Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extra_scripts = <<<JS
<script>
function filterStatus(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.style.background  = 'var(--primary)';
        b.style.color       = 'var(--text)';
        b.style.fontWeight  = 'normal';
    });
    btn.style.background = 'var(--accent)';
    btn.style.color      = '#021F42';
    btn.style.fontWeight = '600';
    document.querySelectorAll('.status-card').forEach(c => {
        c.style.display = (status === 'all' || c.dataset.status === status) ? '' : 'none';
    });
}
function confirmDel(id, nama) {
    document.getElementById('delId').value = id;
    document.getElementById('delDesc').textContent = '"' + nama + '" (#' + id + ') akan dihapus permanen.';
    new bootstrap.Modal(document.getElementById('delModal')).show();
}
</script>
JS;

$content = ob_get_clean();
require_once '../../includes/navbar.php';