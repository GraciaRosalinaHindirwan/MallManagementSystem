<?php
require_once __DIR__ . '/../../public/auth/checkSession.php';
require_once 'event_data.php';

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

$page_title = 'Management Event';
$page       = 'event_areas';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id      = (int)$_POST['booking_id'];
    $catatan = $_POST['catatan'] ?? '';
    switch ($_POST['action']) {
        case 'approve':  updateBookingStatus($id, 'approved', $catatan); break;
        case 'reject':   updateBookingStatus($id, 'rejected', $catatan); break;
        case 'revision': updateBookingStatus($id, 'revision', $catatan); break;
        case 'delete':   deleteBooking($id); break;
    }
    $redir = 'event_calendar.php' . ($_POST['action'] === 'delete' ? '?msg=deleted' : '?msg='.$_POST['action']);
    header('Location: '.$redir);
    exit;
}

$msg            = $_GET['msg'] ?? '';
$semua          = getBookings();
$areas          = getAreas();
$pending        = array_filter($semua, fn($b) => $b['status'] === 'pending');
$approved_count = count(array_filter($semua, fn($b) => $b['status'] === 'approved'));

$byArea = [];
foreach ($areas as $a) $byArea[$a['id_area']] = ['area' => $a, 'events' => []];
foreach ($semua as $b) {
    if ($b['status'] !== 'rejected' && isset($byArea[$b['id_area']]))
        $byArea[$b['id_area']]['events'][] = $b;
}

ob_start();
?>

<style>
.em-card { background:var(--primary); border:1px solid rgba(255,255,255,.08); border-radius:14px; overflow:hidden; }
.em-card-header { padding:.85rem 1.4rem; border-bottom:1px solid rgba(255,255,255,.07); display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
.em-card-label  { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--accent); display:flex; align-items:center; gap:6px; }
.em-card-body   { padding:1.4rem; }

.em-table { width:100%; border-collapse:collapse; color:var(--text); font-size:13px; }
.em-table thead tr { background:var(--primary-dark); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; opacity:.65; }
.em-table th { padding:.7rem 1rem; white-space:nowrap; }
.em-table td { padding:.7rem 1rem; border-bottom:1px solid rgba(255,255,255,.05); vertical-align:middle; }
.em-table tbody tr:last-child td { border-bottom:none; }
.em-table tbody tr:hover { background:rgba(255,255,255,.025); }

.em-btn { border:none; border-radius:7px; padding:4px 11px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:4px; transition:opacity .15s,transform .15s; }
.em-btn:hover { opacity:.85; transform:translateY(-1px); }
.em-btn-success { background:var(--success); color:#fff; }
.em-btn-danger  { background:rgba(239,68,68,.18); border:1px solid rgba(239,68,68,.3); color:#fca5a5; }
.em-btn-warn    { background:var(--secondary); color:#fff; }

.ec-kpi-bar { display:flex; gap:.7rem; flex-wrap:wrap; margin-bottom:1.25rem; }
.ec-kpi-item {
    background: var(--primary);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 11px;
    padding: .7rem 1.1rem;
    display: flex;
    align-items: center;
    gap: .6rem;
    min-width: 110px;
}
.ec-kpi-item i { font-size: 1.05rem; }
.ec-kpi-val  { font-size: 1.1rem; font-weight: 800; line-height: 1; }
.ec-kpi-lbl  { font-size: 11px; opacity: .45; margin-top: 2px; }

.ec-area-row {
    background: var(--primary-dark);
    border-radius: 10px;
    padding: .9rem 1.1rem;
    margin-bottom: .6rem;
}
.ec-area-name { font-weight: 700; font-size: 13px; margin-bottom: .45rem; display:flex; align-items:center; gap:6px; }
.ec-area-meta { font-weight: 400; opacity: .45; font-size: 11px; }
.ec-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    margin: 2px;
}

.ec-toast {
    position: fixed;
    top: 1.5rem; right: 1.5rem;
    color: #fff;
    padding: .6rem 1.35rem;
    border-radius: 9px;
    font-size: 13px;
    z-index: 9999;
    box-shadow: 0 6px 24px rgba(0,0,0,.35);
    display: flex;
    align-items: center;
    gap: 8px;
}

.ec-action-group { display:flex; gap:.35rem; flex-wrap:wrap; }

.em-textarea {
    background: var(--primary-dark);
    border: 1px solid rgba(255,255,255,.13);
    color: var(--text);
    border-radius: 8px;
    width: 100%;
    padding: .5rem .75rem;
    font-size: 13px;
    resize: vertical;
    transition: border-color .2s;
}
.em-textarea:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(0,212,216,.1); }

@media (max-width:768px) {
    .em-table th, .em-table td { padding:.55rem .65rem; }
    .ec-kpi-bar { gap:.45rem; }
}
</style>

<?php
$toasts = [
    'approve'  => ['Pengajuan berhasil disetujui.',           'var(--success)'],
    'reject'   => ['Pengajuan berhasil ditolak.',             'var(--danger)'],
    'revision' => ['Pengajuan dikembalikan untuk revisi.',    'var(--secondary)'],
    'deleted'  => ['Pengajuan berhasil dihapus.',             'var(--success)'],
];
if ($msg && isset($toasts[$msg])): ?>
<div id="toastMsg" class="ec-toast" style="background:<?= $toasts[$msg][1] ?>">
    <i class="bi bi-check-circle"></i> <?= $toasts[$msg][0] ?>
</div>
<script>setTimeout(()=>document.getElementById('toastMsg')?.remove(), 3000)</script>
<?php endif; ?>

<div class="ec-kpi-bar">
    <div class="ec-kpi-item">
        <i class="bi bi-hourglass-split" style="color:#fde68a"></i>
        <div>
            <div class="ec-kpi-val" style="color:#fde68a"><?= count($pending) ?></div>
            <div class="ec-kpi-lbl">Pending</div>
        </div>
    </div>
    <div class="ec-kpi-item">
        <i class="bi bi-calendar-check" style="color:#86efac"></i>
        <div>
            <div class="ec-kpi-val" style="color:#86efac"><?= $approved_count ?></div>
            <div class="ec-kpi-lbl">Approved</div>
        </div>
    </div>
    <div class="ec-kpi-item">
        <i class="bi bi-buildings" style="color:var(--accent)"></i>
        <div>
            <div class="ec-kpi-val" style="color:var(--accent)"><?= count($areas) ?></div>
            <div class="ec-kpi-lbl">Area Aktif</div>
        </div>
    </div>
</div>

<div class="em-card mb-3">
    <div class="em-card-header">
        <span class="em-card-label"><i class="bi bi-map"></i> Status Penggunaan Area</span>
    </div>
    <div class="em-card-body">
        <?php if (empty($byArea)): ?>
        <div style="text-align:center;opacity:.35;font-size:13px;padding:.5rem">Tidak ada area aktif.</div>
        <?php endif; ?>
        <?php foreach ($byArea as $row): ?>
        <div class="ec-area-row">
            <div class="ec-area-name">
                <i class="bi bi-geo-alt-fill" style="color:var(--accent)"></i>
                <?= htmlspecialchars($row['area']['nama_area']) ?>
                <span class="ec-area-meta">
                    <?= number_format($row['area']['kapasitas']) ?> pax
                    <?php if (!empty($row['area']['floor_number'])): ?> · Lt. <?= $row['area']['floor_number'] ?><?php endif; ?>
                </span>
            </div>
            <div>
                <?php if (empty($row['events'])): ?>
                <span style="font-size:12px;opacity:.35"><i class="bi bi-calendar-x me-1"></i>Belum ada booking</span>
                <?php else: ?>
                <?php foreach ($row['events'] as $ev):
                    $cc = [
                        'approved' => ['rgba(34,197,94,.18)','#86efac','rgba(34,197,94,.3)'],
                        'pending'  => ['rgba(251,191,36,.18)','#fde68a','rgba(251,191,36,.3)'],
                        'revision' => ['rgba(56,189,248,.18)','#7dd3fc','rgba(56,189,248,.3)'],
                    ][$ev['status']] ?? ['rgba(255,255,255,.08)','var(--text)','rgba(255,255,255,.18)'];
                ?>
                <span class="ec-chip" style="background:<?= $cc[0] ?>;color:<?= $cc[1] ?>;border:1px solid <?= $cc[2] ?>">
                    <i class="bi bi-circle-fill" style="font-size:5px"></i>
                    #<?= $ev['id_booking'] ?> · <?= htmlspecialchars($ev['nama_event']) ?>
                    (<?= date('d M', strtotime($ev['tanggal_mulai'])) ?>–<?= date('d M', strtotime($ev['tanggal_selesai'])) ?>)
                </span>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$extra_scripts = <<<JS
<script>
function openAction(id, action) {
    document.getElementById('modalBookingId').value = id;
    document.getElementById('modalAction').value    = action;
    const cfg = {
        approve:  { title:'Setujui Pengajuan',  desc:'Setujui pengajuan <strong>#'+id+'</strong>?',         btn:'Setujui',   style:'background:var(--success);color:#fff;border:none',   notes:true  },
        reject:   { title:'Tolak Pengajuan',    desc:'Tolak pengajuan <strong>#'+id+'</strong>?',           btn:'Tolak',     style:'background:var(--danger);color:#fff;border:none',    notes:true  },
        revision: { title:'Minta Revisi',       desc:'Kembalikan <strong>#'+id+'</strong> untuk direvisi.', btn:'Kirim',     style:'background:var(--secondary);color:#fff;border:none', notes:true  },
        delete:   { title:'Hapus Pengajuan',    desc:'Hapus <strong>#'+id+'</strong> secara permanen?',     btn:'Ya, Hapus', style:'background:var(--danger);color:#fff;border:none',    notes:false },
    };
    const c = cfg[action];
    document.getElementById('modalTitle').textContent    = c.title;
    document.getElementById('modalDesc').innerHTML       = c.desc;
    document.getElementById('catatanWrap').style.display = c.notes ? '' : 'none';
    const btn = document.getElementById('modalSubmitBtn');
    btn.textContent  = c.btn;
    btn.style.cssText = c.style + ';border-radius:7px;padding:5px 14px;font-size:13px;font-weight:600;cursor:pointer';
    new bootstrap.Modal(document.getElementById('actionModal')).show();
}
</script>
JS;

$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '../includes/navbarM04_EM.php';