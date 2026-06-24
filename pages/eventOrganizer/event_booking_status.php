<?php
require_once __DIR__ . '/../../public/auth/checkSession.php';
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
    $id = (int)$id;
    $status = mysqli_real_escape_string($conn, $status);
    $cat = mysqli_real_escape_string($conn, $catatan);
    mysqli_query($conn, "UPDATE 04_event_booking SET status='$status', catatan_admin='$cat' WHERE id_booking=$id");
}

function deleteBooking($id) {
    global $conn;
    $id = (int)$id;
    mysqli_query($conn, "DELETE FROM 04_event_booking WHERE id_booking=$id");
}

function checkConflict($id_area, $tanggal_mulai, $tanggal_selesai, $exclude_id = null) {
    global $conn;
    $id_area = (int)$id_area;
    $mulai = mysqli_real_escape_string($conn, $tanggal_mulai);
    $selesai = mysqli_real_escape_string($conn, $tanggal_selesai);
    $excl = $exclude_id ? "AND id_booking != " . (int)$exclude_id : "";
    $result = mysqli_query($conn, "
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
    $nama = mysqli_real_escape_string($conn, $nama);
    $kategori = mysqli_real_escape_string($conn, $kategori);
    $kontak = mysqli_real_escape_string($conn, $kontak);
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
    $id_tiket = 'TKT-' . str_pad($cnt, 3, '0', STR_PAD_LEFT);
    $id_booking = (int)$id_booking;
    $tipe = mysqli_real_escape_string($conn, $tipe);
    $kuota = (int)$kuota;
    $harga = (float)$harga;
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
    $sponsor = mysqli_real_escape_string($conn, $sponsor);
    $paket = mysqli_real_escape_string($conn, $paket);
    $nilai = (float)str_replace(['.', ',', ' '], '', $nilai);
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
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
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

$department_name = 'Event Management';
$menu_items = [
    ['icon'=>'fa-solid fa-house',         'label'=>'Dashboard',         'link'=>BASE_URL.'/pages/eventManager/dashboard_em.php',                    'active_page'=>'dashboard_em'],
    ['icon'=>'fa-solid fa-calendar-week', 'label'=>'Kalender & Approval','link'=>BASE_URL.'/pages/eventManager/event_calendar.php',          'active_page'=>'event_calendar'],
    ['icon'=>'fa-solid fa-people-group',  'label'=>'Vendor & Tiket',    'link'=>BASE_URL.'/pages/eventManager/event_vendor_ticketing.php',   'active_page'=>'event_vendor_ticketing'],
];

$page_title = 'Status Pengajuan Event';
$page       = 'event_booking_status';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    deleteBooking((int)$_POST['delete_id']);
    header('Location: event_booking_status.php?deleted=1');
    exit;
}

$semua   = getBookings();
$deleted = isset($_GET['deleted']);

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

<?php if (empty($semua)): ?>
<div style="text-align:center;padding:3rem;opacity:.5">
    <i class="bi bi-inbox" style="font-size:3rem;display:block;margin-bottom:.5rem"></i>
    Belum ada pengajuan.
    <a href="event_booking_form.php" style="color:var(--accent)">Buat sekarang</a>.
</div>
<?php endif; ?>

<?php foreach ($semua as $p):
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

        <?php if (!empty($p['catatan_admin'])): ?>
        <div style="background:rgba(255,255,255,.04);border-left:3px solid var(--secondary);
                    border-radius:0 6px 6px 0;padding:.6rem 1rem;font-size:12px;margin-top:.75rem">
            <i class="bi bi-chat-left-text me-1" style="color:var(--accent)"></i>
            <strong>Catatan Admin:</strong> <?= htmlspecialchars($p['catatan_admin']) ?>
        </div>
        <?php endif; ?>

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
require_once dirname(__DIR__, 2) . '../includes\navbarM04_EO.php';