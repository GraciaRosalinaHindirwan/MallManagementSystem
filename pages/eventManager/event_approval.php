<?php
require_once '../../config/konek.php';

function getAreas() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT ea.*, f.floor_number, b.name AS building_name
        FROM 04_event_areas ea
        LEFT JOIN 01_floors f ON ea.floor_id = f.id_floors
        LEFT JOIN 01_buildings b ON f.building_id = b.id_buildings
        WHERE ea.status = 'aktif'
        ORDER BY ea.nama_area
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getAreaById($id) {
    global $conn;
    $id = (int)$id;
    $result = mysqli_query($conn, "SELECT * FROM 04_event_areas WHERE id_area = $id");
    return mysqli_fetch_assoc($result);
}

function getBookings($filter_status = null) {
    global $conn;
    $where = $filter_status ? "WHERE b.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'" : "";
    $result = mysqli_query($conn, "
        SELECT b.*, a.nama_area, a.kapasitas,
               u.full_name AS nama_pemohon
        FROM 04_event_booking b
        LEFT JOIN 04_event_areas a ON b.id_area = a.id_area
        LEFT JOIN 09_users u ON b.id_user = u.id
        $where
        ORDER BY b.id_booking DESC
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getBookingById($id) {
    global $conn;
    $id = (int)$id;
    $result = mysqli_query($conn, "
        SELECT b.*, a.nama_area, a.kapasitas, a.fasilitas,
               u.full_name AS nama_pemohon, u.email AS email_pemohon
        FROM 04_event_booking b
        LEFT JOIN 04_event_areas a ON b.id_area = a.id_area
        LEFT JOIN 09_users u ON b.id_user = u.id
        WHERE b.id_booking = $id
    ");
    return mysqli_fetch_assoc($result);
}

function addBooking($data) {
    global $conn;
    $id_area   = (int)$data['id_area'];
    $id_user   = (int)($data['id_user'] ?? 1);
    $nama      = mysqli_real_escape_string($conn, $data['nama_event']);
    $tipe      = mysqli_real_escape_string($conn, $data['tipe_event']);
    $mulai     = mysqli_real_escape_string($conn, $data['tanggal_mulai']);
    $selesai   = mysqli_real_escape_string($conn, $data['tanggal_selesai']);
    $estimasi  = (int)$data['estimasi_pengunjung'];

    $sql = "INSERT INTO 04_event_booking
            (id_area, id_user, nama_event, tipe_event, tanggal_mulai, tanggal_selesai, estimasi_pengunjung, status)
            VALUES ($id_area, $id_user, '$nama', '$tipe', '$mulai', '$selesai', $estimasi, 'pending')";
    mysqli_query($conn, $sql);
    return mysqli_insert_id($conn);
}

function updateBookingStatus($id, $status, $catatan = '') {
    global $conn;
    $id     = (int)$id;
    $status = mysqli_real_escape_string($conn, $status);
    $cat    = mysqli_real_escape_string($conn, $catatan);
    mysqli_query($conn, "UPDATE 04_event_booking SET status='$status', catatan_admin='$cat' WHERE id_booking=$id");
}

function deleteBooking($id) {
    global $conn;
    $id = (int)$id;
    mysqli_query($conn, "DELETE FROM 04_event_booking WHERE id_booking=$id");
}

function checkConflict($id_area, $tanggal_mulai, $tanggal_selesai, $exclude_id = null) {
    global $conn;
    $id_area  = (int)$id_area;
    $mulai    = mysqli_real_escape_string($conn, $tanggal_mulai);
    $selesai  = mysqli_real_escape_string($conn, $tanggal_selesai);
    $excl     = $exclude_id ? "AND id_booking != " . (int)$exclude_id : "";
    $result   = mysqli_query($conn, "
        SELECT * FROM 04_event_booking
        WHERE id_area = $id_area
          AND status NOT IN ('rejected')
          AND tanggal_mulai <= '$selesai'
          AND tanggal_selesai >= '$mulai'
          $excl
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getVendorsByBooking($id_booking) {
    global $conn;
    $id = (int)$id_booking;
    $result = mysqli_query($conn, "SELECT * FROM 04_event_booking_vendor WHERE id_booking=$id");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getAllVendors() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT v.*, b.nama_event
        FROM 04_event_booking_vendor v
        LEFT JOIN 04_event_booking b ON v.id_booking = b.id_booking
        ORDER BY v.id DESC
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function addVendor($id_booking, $nama, $kategori, $kontak) {
    global $conn;
    $id_booking = (int)$id_booking;
    $nama       = mysqli_real_escape_string($conn, $nama);
    $kategori   = mysqli_real_escape_string($conn, $kategori);
    $kontak     = mysqli_real_escape_string($conn, $kontak);
    mysqli_query($conn, "INSERT INTO 04_event_booking_vendor (id_booking, nama_vendor, kategori, kontak)
                         VALUES ($id_booking, '$nama', '$kategori', '$kontak')");
}

function deleteVendor($id) {
    global $conn;
    mysqli_query($conn, "DELETE FROM 04_event_booking_vendor WHERE id=" . (int)$id);
}

function getTiketByBooking($id_booking) {
    global $conn;
    $id = (int)$id_booking;
    $result = mysqli_query($conn, "SELECT * FROM 04_event_tiket WHERE id_booking=$id ORDER BY id_tiket");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getAllTiket() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT t.*, b.nama_event, b.tipe_event
        FROM 04_event_tiket t
        LEFT JOIN 04_event_booking b ON t.id_booking = b.id_booking
        ORDER BY t.id_tiket
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function addTiket($id_booking, $tipe, $kuota, $harga) {
    global $conn;
    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM 04_event_tiket");
    $cnt = mysqli_fetch_assoc($res)['c'] + 1;
    $id_tiket   = 'TKT-' . str_pad($cnt, 3, '0', STR_PAD_LEFT);
    $id_booking = (int)$id_booking;
    $tipe       = mysqli_real_escape_string($conn, $tipe);
    $kuota      = (int)$kuota;
    $harga      = (float)$harga;
    mysqli_query($conn, "INSERT INTO 04_event_tiket (id_tiket, id_booking, tipe, kuota, terjual, harga, pendapatan)
                         VALUES ('$id_tiket', $id_booking, '$tipe', $kuota, 0, $harga, 0)");
}

function deleteTiket($id_tiket) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id_tiket);
    mysqli_query($conn, "DELETE FROM 04_event_tiket WHERE id_tiket='$id'");
}

function getSponsorByBooking($id_booking) {
    global $conn;
    $id = (int)$id_booking;
    $result = mysqli_query($conn, "SELECT * FROM 04_event_sponsorship WHERE id_booking=$id");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function getAllSponsors() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT s.*, b.nama_event
        FROM 04_event_sponsorship s
        LEFT JOIN 04_event_booking b ON s.id_booking = b.id_booking
        ORDER BY s.id_sponsor
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function addSponsor($id_booking, $sponsor, $paket, $nilai) {
    global $conn;
    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM 04_event_sponsorship");
    $cnt = mysqli_fetch_assoc($res)['c'] + 1;
    $id_sponsor = 'SPO-' . str_pad($cnt, 3, '0', STR_PAD_LEFT);
    $id_booking = (int)$id_booking;
    $sponsor    = mysqli_real_escape_string($conn, $sponsor);
    $paket      = mysqli_real_escape_string($conn, $paket);
    $nilai      = (float)str_replace(['.', ',', ' '], '', $nilai);
    mysqli_query($conn, "INSERT INTO 04_event_sponsorship (id_sponsor, id_booking, sponsor, paket, nilai, status_bayar)
                         VALUES ('$id_sponsor', $id_booking, '$sponsor', '$paket', $nilai, 'belum')");
}

function settleSponsor($id_sponsor) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id_sponsor);
    mysqli_query($conn, "UPDATE 04_event_sponsorship SET status_bayar='lunas' WHERE id_sponsor='$id'");
}

function deleteSponsor($id_sponsor) {
    global $conn;
    $id = mysqli_real_escape_string($conn, $id_sponsor);
    mysqli_query($conn, "DELETE FROM 04_event_sponsorship WHERE id_sponsor='$id'");
}

function getAnalytics() {
    global $conn;
    $result = mysqli_query($conn, "
        SELECT an.*, b.nama_event, b.tipe_event, b.tanggal_mulai, b.tanggal_selesai,
               a.nama_area
        FROM 04_event_analytics an
        LEFT JOIN 04_event_booking b ON an.id_booking = b.id_booking
        LEFT JOIN 04_event_areas a ON b.id_area = a.id_area
        ORDER BY an.id DESC
    ");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}

function statusBadge($status) {
    $map = [
        'pending'  => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'revision' => '<span class="badge bg-info text-dark">Revisi</span>',
    ];
    return $map[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

function payBadge($status) {
    return $status === 'lunas'
        ? '<span class="badge bg-success">Lunas</span>'
        : '<span class="badge bg-danger">Belum</span>';
}

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

$page_title = 'Approval Event';
$page       = 'event_approval';

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
        <span class="em-card-label"><i class="bi bi-ui-checks"></i> Antrian Approval</span>
        <span style="background:var(--text-accent);color:#021F42;font-size:11px;font-weight:700;padding:2px 11px;border-radius:20px">
            <?= count($pending) ?> pending
        </span>
    </div>

    <?php if (empty($pending)): ?>
    <div style="text-align:center;padding:2.25rem;opacity:.4;font-size:13px">
        <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
        Tidak ada yang menunggu review.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="em-table">
            <thead>
                <tr><th class="ps-4">ID</th><th>Pemohon</th><th>Event</th><th>Area</th><th>Tanggal</th><th>Est.</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($pending as $p): ?>
            <tr>
                <td class="ps-4"><strong style="color:var(--accent)">#<?= $p['id_booking'] ?></strong></td>
                <td><?= htmlspecialchars($p['nama_pemohon'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['nama_event']) ?></td>
                <td><?= htmlspecialchars($p['nama_area']) ?></td>
                <td style="font-size:12px">
                    <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                    <?php if ($p['tanggal_mulai'] !== $p['tanggal_selesai']): ?>
                    <br><span style="opacity:.45">s/d <?= date('d M Y', strtotime($p['tanggal_selesai'])) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= number_format($p['estimasi_pengunjung']) ?></td>
                <td>
                    <div class="ec-action-group">
                        <button onclick="openAction(<?= $p['id_booking'] ?>,'approve')"  class="em-btn em-btn-success" title="Setujui"><i class="bi bi-check-lg"></i></button>
                        <button onclick="openAction(<?= $p['id_booking'] ?>,'revision')" class="em-btn em-btn-warn"    title="Minta Revisi"><i class="bi bi-arrow-repeat"></i></button>
                        <button onclick="openAction(<?= $p['id_booking'] ?>,'reject')"   class="em-btn em-btn-danger"  title="Tolak"><i class="bi bi-x-lg"></i></button>
                        <button onclick="openAction(<?= $p['id_booking'] ?>,'delete')"   class="em-btn em-btn-danger"  title="Hapus"><i class="bi bi-trash3"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="em-card">
    <div class="em-card-header">
        <span class="em-card-label"><i class="bi bi-list-ul"></i> Semua Pengajuan</span>
    </div>
    <div class="table-responsive">
        <table class="em-table">
            <thead>
                <tr><th class="ps-4">ID</th><th>Event</th><th>Area</th><th>Tanggal</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($semua as $p): ?>
            <tr>
                <td class="ps-4"><strong style="color:var(--accent)">#<?= $p['id_booking'] ?></strong></td>
                <td>
                    <div style="font-weight:600"><?= htmlspecialchars($p['nama_event']) ?></div>
                    <div style="font-size:11px;opacity:.45"><?= htmlspecialchars($p['tipe_event']) ?></div>
                </td>
                <td><?= htmlspecialchars($p['nama_area']) ?></td>
                <td style="font-size:12px">
                    <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                    <?php if ($p['tanggal_mulai'] !== $p['tanggal_selesai']): ?>
                    – <?= date('d M Y', strtotime($p['tanggal_selesai'])) ?>
                    <?php endif; ?>
                </td>
                <td><?= statusBadge($p['status']) ?></td>
                <td style="font-size:12px;opacity:.55;max-width:170px"><?= $p['catatan_admin'] ? htmlspecialchars($p['catatan_admin']) : '—' ?></td>
                <td>
                    <button onclick="openAction(<?= $p['id_booking'] ?>,'delete')" class="em-btn em-btn-danger" title="Hapus">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--primary);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:14px">
      <div class="modal-header" style="border-color:rgba(255,255,255,.08);padding:1rem 1.4rem">
        <h6 class="modal-title fw-bold" id="modalTitle"></h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body" style="padding:1.25rem 1.4rem">
            <input type="hidden" name="booking_id" id="modalBookingId">
            <input type="hidden" name="action"     id="modalAction">
            <p id="modalDesc" style="font-size:13px;opacity:.8;margin-bottom:1rem"></p>
            <div id="catatanWrap">
                <label class="em-label">Catatan (opsional)</label>
                <textarea name="catatan" rows="3" class="em-textarea" placeholder="Tulis catatan untuk pemohon..."></textarea>
            </div>
        </div>
        <div class="modal-footer" style="border-color:rgba(255,255,255,.08);padding:.85rem 1.4rem;gap:.5rem">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="em-btn" id="modalSubmitBtn"></button>
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