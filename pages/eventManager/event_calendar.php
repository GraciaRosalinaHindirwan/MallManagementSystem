<?php
// pages/eventManager/event_calendar.php
// PBI-M04-03-02 — Kalender Visual per Area + Workflow Approve/Tolak/Revisi
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

$page_title = 'Kalender & Approval Event';
$page       = 'event_calendar';

// ── Handle POST actions ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id      = (int)$_POST['booking_id'];
    $catatan = $_POST['catatan'] ?? '';
    switch ($_POST['action']) {
        case 'approve':  updateBookingStatus($id, 'approved', $catatan); break;
        case 'reject':   updateBookingStatus($id, 'rejected', $catatan); break;
        case 'revision': updateBookingStatus($id, 'revision', $catatan); break;
        case 'delete':   deleteBooking($id); break;
    }
    $redir = 'event_calendar.php' . ($_POST['action'] === 'delete' ? '?msg=deleted' : '?msg=' . $_POST['action']);
    header('Location: ' . $redir);
    exit;
}

$msg      = $_GET['msg'] ?? '';
$semua    = getBookings();
$areas    = getAreas();
$pending  = array_filter($semua, fn($b) => $b['status'] === 'pending');
$approved_count = count(array_filter($semua, fn($b) => $b['status'] === 'approved'));

// Group bookings by area
$byArea = [];
foreach ($areas as $a) $byArea[$a['id_area']] = ['area' => $a, 'events' => []];
foreach ($semua as $b) {
    if ($b['status'] !== 'rejected' && isset($byArea[$b['id_area']]))
        $byArea[$b['id_area']]['events'][] = $b;
}

ob_start();
?>

<!-- Toast messages -->
<?php
$toasts = [
    'approve'  => ['Pengajuan berhasil disetujui.',  'var(--success)'],
    'reject'   => ['Pengajuan berhasil ditolak.',     'var(--danger)'],
    'revision' => ['Pengajuan dikembalikan untuk revisi.', 'var(--secondary)'],
    'deleted'  => ['Pengajuan berhasil dihapus.',     'var(--success)'],
];
if ($msg && isset($toasts[$msg])): ?>
<div id="toastMsg" style="position:fixed;top:1.5rem;right:1.5rem;background:<?= $toasts[$msg][1] ?>;
     color:#fff;padding:.6rem 1.4rem;border-radius:8px;font-size:13px;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.3)">
    <i class="bi bi-check-circle me-2"></i><?= $toasts[$msg][0] ?>
</div>
<script>setTimeout(()=>document.getElementById('toastMsg')?.remove(), 3000)</script>
<?php endif; ?>

<!-- KPI Bar -->
<div class="d-flex gap-3 flex-wrap mb-3">
    <div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:10px;
                padding:.75rem 1.25rem;display:flex;align-items:center;gap:.6rem">
        <i class="bi bi-hourglass-split text-warning"></i>
        <div>
            <div style="font-size:1.1rem;font-weight:700;color:#fde68a"><?= count($pending) ?></div>
            <div style="font-size:11px;opacity:.5">Pending</div>
        </div>
    </div>
    <div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:10px;
                padding:.75rem 1.25rem;display:flex;align-items:center;gap:.6rem">
        <i class="bi bi-calendar-check text-success"></i>
        <div>
            <div style="font-size:1.1rem;font-weight:700;color:#86efac"><?= $approved_count ?></div>
            <div style="font-size:11px;opacity:.5">Approved</div>
        </div>
    </div>
    <div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:10px;
                padding:.75rem 1.25rem;display:flex;align-items:center;gap:.6rem">
        <i class="bi bi-buildings" style="color:var(--accent)"></i>
        <div>
            <div style="font-size:1.1rem;font-weight:700;color:var(--accent)"><?= count($areas) ?></div>
            <div style="font-size:11px;opacity:.5">Area Aktif</div>
        </div>
    </div>
</div>

<!-- ── BAGIAN 1: Status Penggunaan Area ── -->
<div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px;margin-bottom:1.5rem">
    <div style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08)">
        <span style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.08em">
            <i class="bi bi-map me-1"></i>Status Penggunaan Area
        </span>
    </div>
    <div style="padding:1.25rem 1.5rem">
        <?php if (empty($byArea)): ?>
        <div style="text-align:center;opacity:.4;font-size:13px">Tidak ada area aktif.</div>
        <?php endif; ?>
        <?php foreach ($byArea as $row): ?>
        <div style="background:var(--primary-dark);border-radius:8px;padding:1rem;margin-bottom:.75rem">
            <div style="font-weight:600;font-size:13px;margin-bottom:.5rem">
                <i class="bi bi-geo-alt-fill me-1" style="color:var(--accent)"></i>
                <?= htmlspecialchars($row['area']['nama_area']) ?>
                <span style="font-weight:400;opacity:.5;font-size:11px;margin-left:6px">
                    <?= number_format($row['area']['kapasitas']) ?> pax
                    <?php if (!empty($row['area']['floor_number'])): ?>
                    · Lt. <?= $row['area']['floor_number'] ?>
                    <?php endif; ?>
                </span>
            </div>
            <div>
                <?php if (empty($row['events'])): ?>
                <span style="font-size:12px;opacity:.4"><i class="bi bi-calendar-x me-1"></i>Belum ada booking</span>
                <?php else: ?>
                <?php foreach ($row['events'] as $ev):
                    $chip_colors = [
                        'approved' => ['rgba(34,197,94,.2)', '#86efac', 'rgba(34,197,94,.3)'],
                        'pending'  => ['rgba(251,191,36,.2)', '#fde68a', 'rgba(251,191,36,.3)'],
                        'revision' => ['rgba(56,189,248,.2)', '#7dd3fc', 'rgba(56,189,248,.3)'],
                    ];
                    $cc = $chip_colors[$ev['status']] ?? ['rgba(255,255,255,.1)', 'var(--text)', 'rgba(255,255,255,.2)'];
                ?>
                <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;
                             font-size:11px;font-weight:500;margin:2px;
                             background:<?= $cc[0] ?>;color:<?= $cc[1] ?>;border:1px solid <?= $cc[2] ?>">
                    <i class="bi bi-circle-fill" style="font-size:6px"></i>
                    #<?= $ev['id_booking'] ?> · <?= htmlspecialchars($ev['nama_event']) ?>
                    (<?= date('d M', strtotime($ev['tanggal_mulai'])) ?>
                    –<?= date('d M', strtotime($ev['tanggal_selesai'])) ?>)
                </span>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── BAGIAN 2: Antrian Approval ── -->
<div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px;margin-bottom:1.5rem">
    <div style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08);
                display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.08em">
            <i class="bi bi-ui-checks me-1"></i>Antrian Approval
        </span>
        <span style="background:var(--text-accent);color:#021F42;font-size:12px;font-weight:600;
                     padding:2px 10px;border-radius:20px"><?= count($pending) ?> pending</span>
    </div>

    <?php if (empty($pending)): ?>
    <div style="text-align:center;padding:2rem;opacity:.45">
        <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
        Tidak ada yang menunggu review.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table mb-0" style="color:var(--text)">
            <thead style="background:var(--primary-dark)">
                <tr style="font-size:12px;font-weight:600;opacity:.7">
                    <th class="ps-3">ID</th><th>Pemohon</th><th>Event</th><th>Area</th>
                    <th>Tanggal</th><th>Est.</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pending as $p): ?>
            <tr style="border-color:rgba(255,255,255,.06);font-size:13px;vertical-align:middle">
                <td class="ps-3"><strong style="color:var(--accent)">#<?= $p['id_booking'] ?></strong></td>
                <td><?= htmlspecialchars($p['nama_pemohon'] ?? '-') ?></td>
                <td><?= htmlspecialchars($p['nama_event']) ?></td>
                <td><?= htmlspecialchars($p['nama_area']) ?></td>
                <td style="font-size:12px">
                    <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                    <?php if ($p['tanggal_mulai'] !== $p['tanggal_selesai']): ?>
                    <br><small style="opacity:.5">s/d <?= date('d M Y', strtotime($p['tanggal_selesai'])) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= number_format($p['estimasi_pengunjung']) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <button onclick="openAction(<?= $p['id_booking'] ?>,'approve')"
                                style="background:var(--success);color:#fff;border:none;border-radius:6px;
                                       padding:4px 10px;font-size:12px;cursor:pointer" title="Setujui">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button onclick="openAction(<?= $p['id_booking'] ?>,'revision')"
                                style="background:var(--secondary);color:#fff;border:none;border-radius:6px;
                                       padding:4px 10px;font-size:12px;cursor:pointer" title="Minta Revisi">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        <button onclick="openAction(<?= $p['id_booking'] ?>,'reject')"
                                style="background:var(--danger);color:#fff;border:none;border-radius:6px;
                                       padding:4px 10px;font-size:12px;cursor:pointer" title="Tolak">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <button onclick="openAction(<?= $p['id_booking'] ?>,'delete')"
                                style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);
                                       color:#fca5a5;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer" title="Hapus">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── BAGIAN 3: Semua Pengajuan ── -->
<div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px">
    <div style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08)">
        <span style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.08em">
            <i class="bi bi-list-ul me-1"></i>Semua Pengajuan
        </span>
    </div>
    <div class="table-responsive">
        <table class="table mb-0" style="color:var(--text)">
            <thead style="background:var(--primary-dark)">
                <tr style="font-size:12px;font-weight:600;opacity:.7">
                    <th class="ps-3">ID</th><th>Event</th><th>Area</th><th>Tanggal</th>
                    <th>Status</th><th>Catatan</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($semua as $p): ?>
            <tr style="border-color:rgba(255,255,255,.06);font-size:13px;vertical-align:middle">
                <td class="ps-3"><strong style="color:var(--accent)">#<?= $p['id_booking'] ?></strong></td>
                <td>
                    <div><?= htmlspecialchars($p['nama_event']) ?></div>
                    <small style="opacity:.5"><?= htmlspecialchars($p['tipe_event']) ?></small>
                </td>
                <td><?= htmlspecialchars($p['nama_area']) ?></td>
                <td style="font-size:12px">
                    <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                    <?php if ($p['tanggal_mulai'] !== $p['tanggal_selesai']): ?>
                    – <?= date('d M Y', strtotime($p['tanggal_selesai'])) ?>
                    <?php endif; ?>
                </td>
                <td><?= statusBadge($p['status']) ?></td>
                <td style="font-size:12px;opacity:.6;max-width:180px">
                    <?= $p['catatan_admin'] ? htmlspecialchars($p['catatan_admin']) : '—' ?>
                </td>
                <td>
                    <button onclick="openAction(<?= $p['id_booking'] ?>,'delete')"
                            style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);
                                   color:#fca5a5;border-radius:6px;padding:3px 10px;font-size:11px;cursor:pointer">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ACTION MODAL -->
<div class="modal fade" id="actionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--primary);color:var(--text);border:1px solid rgba(255,255,255,.1)">
      <div class="modal-header" style="border-color:rgba(255,255,255,.1)">
        <h5 class="modal-title" id="modalTitle"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
            <input type="hidden" name="booking_id" id="modalBookingId">
            <input type="hidden" name="action"     id="modalAction">
            <p id="modalDesc" style="font-size:13px"></p>
            <div id="catatanWrap">
                <label style="font-size:13px;font-weight:500">Catatan (opsional)</label>
                <textarea name="catatan" rows="3" class="form-control mt-1"
                          style="background:var(--primary-dark);color:var(--text);
                                 border:1px solid rgba(255,255,255,.15);border-radius:8px"
                          placeholder="Tulis catatan untuk pemohon..."></textarea>
            </div>
        </div>
        <div class="modal-footer" style="border-color:rgba(255,255,255,.1)">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-sm" id="modalSubmitBtn"></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extra_scripts = <<<JS
<script>
function openAction(id, action) {
    document.getElementById('modalBookingId').value = id;
    document.getElementById('modalAction').value    = action;
    const cfg = {
        approve:  { title:'Setujui Pengajuan',  desc:'Setujui pengajuan <strong>#'+id+'</strong>?',           btn:'Setujui',    style:'background:var(--success);color:#fff', notes:true  },
        reject:   { title:'Tolak Pengajuan',    desc:'Tolak pengajuan <strong>#'+id+'</strong>?',             btn:'Tolak',      style:'background:var(--danger);color:#fff',  notes:true  },
        revision: { title:'Minta Revisi',       desc:'Kembalikan <strong>#'+id+'</strong> untuk direvisi.',   btn:'Kirim',      style:'background:var(--secondary);color:#fff',notes:true },
        delete:   { title:'Hapus Pengajuan',    desc:'Hapus <strong>#'+id+'</strong> secara permanen?',       btn:'Ya, Hapus',  style:'background:var(--danger);color:#fff',  notes:false },
    };
    const c = cfg[action];
    document.getElementById('modalTitle').textContent     = c.title;
    document.getElementById('modalDesc').innerHTML        = c.desc;
    document.getElementById('catatanWrap').style.display  = c.notes ? '' : 'none';
    const btn = document.getElementById('modalSubmitBtn');
    btn.textContent  = c.btn;
    btn.style.cssText = c.style;
    new bootstrap.Modal(document.getElementById('actionModal')).show();
}
</script>
JS;

$content = ob_get_clean();
require_once '../../includes/navbar.php';