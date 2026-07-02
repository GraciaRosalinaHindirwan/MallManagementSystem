<?php
require_once dirname(__DIR__, 2) . '/public/auth/checkSession.php';
require_once dirname(__DIR__, 2) . '/config/konek.php';

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

$page_title = 'Dashboard Event';
$page       = 'dashboard_eo';

$semua      = getBookings();
$tiket_all  = getAllTiket();
$sponsor_all= getAllSponsors();
$areas      = getAreas();

$total      = count($semua);
$pending    = array_filter($semua, fn($b) => $b['status'] === 'pending');
$approved   = array_filter($semua, fn($b) => $b['status'] === 'approved');
$rejected   = array_filter($semua, fn($b) => $b['status'] === 'rejected');
$revision   = array_filter($semua, fn($b) => $b['status'] === 'revision');

$total_rev_tiket   = array_sum(array_column($tiket_all,  'pendapatan'));
$total_rev_sponsor = array_sum(array_map(
    fn($s) => $s['status_bayar'] === 'lunas' ? $s['nilai'] : 0,
    $sponsor_all
));
$total_kuota  = array_sum(array_column($tiket_all, 'kuota'));
$total_terjual= array_sum(array_column($tiket_all, 'terjual'));

$upcoming = array_filter($approved, fn($b) => strtotime($b['tanggal_mulai']) >= strtotime('today'));
usort($upcoming, fn($a, $b) => strtotime($a['tanggal_mulai']) - strtotime($b['tanggal_mulai']));

$recent = array_slice(array_reverse($semua), 0, 5);

ob_start();
?>

<style>
.eo-page { font-family: var(--font-family, 'Poppins', sans-serif); }

.eo-hero {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    padding: 2.5rem 2.25rem 2rem;
    margin-bottom: 1.75rem;
    background: linear-gradient(130deg, #082A53 0%, #0e4a5a 45%, #0a3340 100%);
    border: 1px solid rgba(0,212,216,.15);
    isolation: isolate;
}
.eo-hero::before {
    content: '';
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 55% 70% at 85% 40%, rgba(0,212,216,.11) 0%, transparent 65%),
        radial-gradient(ellipse 40% 50% at 5% 80%,  rgba(255,182,42,.07)  0%, transparent 60%);
    pointer-events: none;
    z-index: 0;
}
.eo-hero-grid {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1.5rem;
    align-items: center;
    position: relative; z-index: 1;
}
@media (max-width: 600px) { .eo-hero-grid { grid-template-columns: 1fr; } }
.eo-hero-eyebrow {
    font-size: 10px; font-weight: 800; letter-spacing: .15em;
    text-transform: uppercase;
    color: var(--accent, #00D4D8);
    background: rgba(0,212,216,.1);
    border: 1px solid rgba(0,212,216,.2);
    border-radius: 20px;
    display: inline-block;
    padding: 3px 13px;
    margin-bottom: .75rem;
}
.eo-hero h1 {
    font-size: clamp(1.2rem, 3.5vw, 1.65rem);
    font-weight: 800;
    margin: 0 0 .35rem;
    color: var(--text, #F5F7FA);
    line-height: 1.2;
}
.eo-hero p {
    font-size: 13px;
    opacity: .55;
    margin: 0 0 1.35rem;
    max-width: 440px;
}
.eo-cta-row { display: flex; gap: .6rem; flex-wrap: wrap; }
.eo-cta-primary {
    display: inline-flex; align-items: center; gap: 7px;
    background: linear-gradient(135deg, var(--accent, #00D4D8), #167E80);
    color: #021F42; font-weight: 700; font-size: 13px;
    border: none; border-radius: 10px; padding: .6rem 1.35rem;
    text-decoration: none; cursor: pointer;
    transition: transform .2s, box-shadow .2s;
    box-shadow: 0 4px 16px rgba(0,212,216,.22);
}
.eo-cta-primary:hover { color: #021F42; transform: translateY(-2px); box-shadow: 0 7px 22px rgba(0,212,216,.32); }
.eo-cta-secondary {
    display: inline-flex; align-items: center; gap: 7px;
    background: rgba(255,255,255,.08); color: var(--text, #F5F7FA);
    font-size: 13px; font-weight: 500;
    border: 1px solid rgba(255,255,255,.15); border-radius: 10px; padding: .6rem 1.35rem;
    text-decoration: none; cursor: pointer;
    transition: background .2s, border-color .2s;
}
.eo-cta-secondary:hover { background: rgba(255,255,255,.14); color: var(--text, #F5F7FA); border-color: rgba(255,255,255,.28); }
.eo-hero-stat {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 14px;
    padding: 1.1rem 1.35rem;
    text-align: center;
    min-width: 120px;
}
.eo-hero-stat-val  { font-size: 2rem; font-weight: 800; line-height: 1; color: var(--text-accent, #FFB62A); }
.eo-hero-stat-lbl  { font-size: 11px; opacity: .45; margin-top: 4px; text-transform: uppercase; letter-spacing: .05em; }

.eo-kpi-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .85rem;
    margin-bottom: 1.75rem;
}
@media (max-width: 900px)  { .eo-kpi-strip { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px)  { .eo-kpi-strip { grid-template-columns: 1fr 1fr; } }

.eo-kpi {
    background: var(--primary, #0B376D);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    position: relative; overflow: hidden;
    transition: transform .2s, border-color .2s;
}
.eo-kpi:hover { transform: translateY(-3px); border-color: rgba(255,255,255,.16); }
.eo-kpi::after {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 100%; height: 3px;
    border-radius: 14px 14px 0 0;
}
.eo-kpi.kpi-pending::after  { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.eo-kpi.kpi-approved::after { background: linear-gradient(90deg,#22c55e,#4ade80); }
.eo-kpi.kpi-tiket::after    { background: linear-gradient(90deg,var(--accent,#00D4D8),#67e8f9); }
.eo-kpi.kpi-sponsor::after  { background: linear-gradient(90deg,#a78bfa,#c4b5fd); }
.eo-kpi-icon {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; margin-bottom: .65rem; flex-shrink: 0;
}
.eo-kpi-val  { font-size: 1.55rem; font-weight: 800; line-height: 1; color: var(--text, #F5F7FA); }
.eo-kpi-lbl  { font-size: 11px; opacity: .45; margin-top: 3px; text-transform: uppercase; letter-spacing: .05em; }
.eo-kpi-sub  { font-size: 11px; margin-top: 5px; }

.eo-content-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.1rem;
    margin-bottom: 1.75rem;
}
@media (max-width: 1000px) { .eo-content-grid { grid-template-columns: 1fr; } }

.eo-card {
    background: var(--primary, #0B376D);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 14px;
    overflow: hidden;
}
.eo-card-head {
    padding: .85rem 1.35rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
    display: flex; justify-content: space-between; align-items: center; gap: .5rem;
}
.eo-card-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .09em; color: var(--accent, #00D4D8);
    display: flex; align-items: center; gap: 6px;
}
.eo-card-body { padding: 1.25rem 1.35rem; }

.eo-table { width: 100%; border-collapse: collapse; font-size: 13px; color: var(--text, #F5F7FA); }
.eo-table th {
    padding: .65rem 1rem; text-align: left;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
    opacity: .5; background: var(--primary-dark, #082A53);
    white-space: nowrap;
}
.eo-table td {
    padding: .7rem 1rem; border-bottom: 1px solid rgba(255,255,255,.05); vertical-align: middle;
}
.eo-table tbody tr:last-child td { border-bottom: none; }
.eo-table tbody tr:hover { background: rgba(255,255,255,.025); }

.eo-upcoming-item {
    display: flex; gap: 1rem; align-items: flex-start;
    padding: .85rem 0;
    border-bottom: 1px solid rgba(255,255,255,.05);
}
.eo-upcoming-item:last-child { border-bottom: none; padding-bottom: 0; }
.eo-date-block {
    flex-shrink: 0; width: 46px; height: 46px;
    border-radius: 10px; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    background: rgba(0,212,216,.12); border: 1px solid rgba(0,212,216,.2);
    line-height: 1;
}
.eo-date-day   { font-size: 1.1rem; font-weight: 800; color: var(--accent, #00D4D8); }
.eo-date-month { font-size: 9px; text-transform: uppercase; opacity: .5; letter-spacing: .05em; }
.eo-upcoming-name  { font-weight: 600; font-size: 13px; }
.eo-upcoming-meta  { font-size: 11px; opacity: .4; margin-top: 2px; }

.eo-badge {
    display: inline-flex; align-items: center; gap: 4px;
    border-radius: 20px; font-size: 11px; font-weight: 600;
    padding: 3px 11px; white-space: nowrap;
}
.eo-badge-pending  { background: rgba(245,158,11,.15); color: #fde68a; border: 1px solid rgba(245,158,11,.25); }
.eo-badge-approved { background: rgba(34,197,94,.15);  color: #86efac; border: 1px solid rgba(34,197,94,.25); }
.eo-badge-rejected { background: rgba(239,68,68,.15);  color: #fca5a5; border: 1px solid rgba(239,68,68,.25); }
.eo-badge-revision { background: rgba(56,189,248,.15); color: #7dd3fc; border: 1px solid rgba(56,189,248,.25); }

.eo-rev-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: .55rem 0; border-bottom: 1px solid rgba(255,255,255,.05);
}
.eo-rev-row:last-child { border-bottom: none; }
.eo-rev-label { font-size: 12px; opacity: .6; }
.eo-rev-val   { font-weight: 700; font-size: 13px; color: var(--text-accent, #FFB62A); }

.eo-ticket-row { padding: .6rem 0; border-bottom: 1px solid rgba(255,255,255,.05); }
.eo-ticket-row:last-child { border-bottom: none; }
.eo-ticket-meta { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; }
.eo-ticket-bar-bg {
    height: 5px; background: rgba(255,255,255,.08); border-radius: 3px; overflow: hidden;
}
.eo-ticket-bar-fill {
    height: 100%; border-radius: 3px;
    background: linear-gradient(90deg, var(--accent, #00D4D8), #167E80);
    transition: width .5s ease;
}

.eo-empty { text-align: center; padding: 2.5rem 1.5rem; opacity: .38; }
.eo-empty i { font-size: 2.5rem; display: block; margin-bottom: .75rem; }
.eo-empty p { font-size: 13px; margin: 0; }

.eo-action-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: .6rem; margin-bottom: 1.75rem;
}
@media (max-width: 500px) { .eo-action-grid { grid-template-columns: 1fr; } }
.eo-action-link {
    display: flex; align-items: center; gap: .85rem;
    background: var(--primary, #0B376D);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 12px; padding: 1rem 1.2rem;
    text-decoration: none; color: var(--text, #F5F7FA);
    transition: transform .2s, border-color .2s, background .2s;
}
.eo-action-link:hover { transform: translateY(-2px); background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.16); color: var(--text,#F5F7FA); }
.eo-action-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.eo-action-name { font-size: 13px; font-weight: 600; }
.eo-action-desc { font-size: 11px; opacity: .4; margin-top: 2px; }

.eo-notif-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--danger, #EF4444);
    display: inline-block; margin-left: 4px;
    animation: eo-blink 1.5s ease-in-out infinite;
    vertical-align: middle;
}
@keyframes eo-blink { 0%,100%{opacity:1} 50%{opacity:.35} }
</style>

<div class="eo-page">
<div class="eo-hero">
    <div class="eo-hero-grid">
        <div>
            <span class="eo-hero-eyebrow">Event </span>
            <h1>Event Submission</h1>
            <p>Pengajuan event dan pantau status pengajuan event.</p>
            <div class="eo-cta-row">
                <a href="<?= BASE_URL ?>/pages/eventOrganizer/event_booking_form.php" class="eo-cta-primary">
                    <i class="fa-solid fa-plus"></i> Ajukan Event Baru
                </a>
                <a href="<?= BASE_URL ?>/pages/eventOrganizer/event_booking_status.php" class="eo-cta-secondary">
                    <i class="fa-solid fa-list-check"></i> Pantau Status
                </a>
            </div>
        </div>
        <div class="eo-hero-stat">
            <div class="eo-hero-stat-val"><?= $total ?></div>
            <div class="eo-hero-stat-lbl">Total Pengajuan</div>
        </div>
    </div>
</div>

<div class="eo-kpi-strip">
    <div class="eo-kpi kpi-pending">
        <div class="eo-kpi-icon" style="background:rgba(245,158,11,.15);color:#f59e0b">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div class="eo-kpi-val"><?= count($pending) ?></div>
        <div class="eo-kpi-lbl">Menunggu Review</div>
        <div class="eo-kpi-sub" style="color:#fde68a">
            <?= count($revision) ?> butuh revisi
        </div>
    </div>

    <div class="eo-kpi kpi-approved">
        <div class="eo-kpi-icon" style="background:rgba(34,197,94,.15);color:#22c55e">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="eo-kpi-val"><?= count($approved) ?></div>
        <div class="eo-kpi-lbl">Disetujui</div>
        <div class="eo-kpi-sub" style="color:#86efac">
            <?= count($upcoming) ?> event mendatang
        </div>
    </div>

    <div class="eo-kpi kpi-tiket">
        <div class="eo-kpi-icon" style="background:rgba(0,212,216,.15);color:var(--accent,#00D4D8)">
            <i class="fa-solid fa-ticket"></i>
        </div>
        <div class="eo-kpi-val"><?= number_format($total_terjual) ?></div>
        <div class="eo-kpi-lbl">Tiket Terjual</div>
        <div class="eo-kpi-sub" style="color:rgba(245,247,250,.45)">
            dari <?= number_format($total_kuota) ?> kuota
        </div>
    </div>
</div>

<div class="eo-action-grid">
    <a href="<?= BASE_URL ?>/pages/eventOrganizer/event_booking_form.php" class="eo-action-link">
        <div class="eo-action-icon" style="background:rgba(0,212,216,.14);color:var(--accent,#00D4D8)">
            <i class="fa-solid fa-calendar-plus"></i>
        </div>
        <div>
            <div class="eo-action-name">Buat Pengajuan Baru</div>
            <div class="eo-action-desc">Booking area event &amp; check ketersediaan</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>/pages/eventOrganizer/event_booking_status.php" class="eo-action-link">
        <div class="eo-action-icon" style="background:rgba(255,182,42,.12);color:var(--text-accent,#FFB62A)">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div>
            <div class="eo-action-name">
                Status Pengajuan
                <?php if (count($pending)): ?><span class="eo-notif-dot"></span><?php endif; ?>
            </div>
            <div class="eo-action-desc">Pantau semua status pengajuan eventmu</div>
        </div>
    </a>
</div>

<div class="eo-content-grid">
    <div class="eo-card">
        <div class="eo-card-head">
            <span class="eo-card-label"><i class="fa-solid fa-clock-rotate-left"></i> Pengajuan Terbaru</span>
            <a href="<?= BASE_URL ?>/pages/eventOrganizer/event_booking_status.php"
               style="font-size:12px;color:var(--accent,#00D4D8);text-decoration:none">
                Lihat semua →
            </a>
        </div>
        <?php if (empty($recent)): ?>
        <div class="eo-empty">
            <i class="fa-solid fa-inbox"></i>
            <p>Belum ada pengajuan. <a href="event_booking_form.php" style="color:var(--accent)">Buat sekarang</a>.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="eo-table">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Nama Event</th>
                        <th>Area</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $b): ?>
                <tr>
                    <td class="ps-4">
                        <span style="color:var(--accent,#00D4D8);font-weight:700">#<?= $b['id_booking'] ?></span>
                    </td>
                    <td>
                        <div style="font-weight:600"><?= htmlspecialchars($b['nama_event']) ?></div>
                        <div style="font-size:11px;opacity:.4"><?= htmlspecialchars($b['tipe_event']) ?></div>
                    </td>
                    <td style="font-size:12px"><?= htmlspecialchars($b['nama_area']) ?></td>
                    <td style="font-size:12px">
                        <?= date('d M Y', strtotime($b['tanggal_mulai'])) ?>
                    </td>
                    <td>
                        <?php
                        $badge_map = [
                            'pending'  => 'eo-badge-pending',
                            'approved' => 'eo-badge-approved',
                            'rejected' => 'eo-badge-rejected',
                            'revision' => 'eo-badge-revision',
                        ];
                        $badge_label = [
                            'pending'  => 'Pending',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            'revision' => 'Revisi',
                        ];
                        $cls = $badge_map[$b['status']] ?? '';
                        $lbl = $badge_label[$b['status']] ?? ucfirst($b['status']);
                        ?>
                        <span class="eo-badge <?= $cls ?>"><?= $lbl ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="eo-card">
            <div class="eo-card-head">
                <span class="eo-card-label"><i class="fa-solid fa-calendar-day"></i> Event Mendatang</span>
                <span style="font-size:11px;background:rgba(34,197,94,.15);color:#86efac;
                              border:1px solid rgba(34,197,94,.25);border-radius:20px;padding: 10px">
                    <?= count($upcoming) ?> event
                </span>
            </div>
            <div class="eo-card-body">
                <?php if (empty($upcoming)): ?>
                <div class="eo-empty" style="padding:1.5rem 1rem">
                    <i class="fa-regular fa-calendar-xmark" style="font-size:1.75rem"></i>
                    <p>Tidak ada event mendatang.</p>
                </div>
                <?php else: ?>
                <?php foreach (array_slice($upcoming, 0, 4) as $ev): ?>
                <div class="eo-upcoming-item">
                    <div class="eo-date-block">
                        <div class="eo-date-day"><?= date('d', strtotime($ev['tanggal_mulai'])) ?></div>
                        <div class="eo-date-month"><?= date('M', strtotime($ev['tanggal_mulai'])) ?></div>
                    </div>
                    <div>
                        <div class="eo-upcoming-name"><?= htmlspecialchars($ev['nama_event']) ?></div>
                        <div class="eo-upcoming-meta">
                            <?= htmlspecialchars($ev['nama_area']) ?> ·
                            <?= number_format($ev['estimasi_pengunjung']) ?> pax
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($tiket_all)): ?>
<div class="eo-card" style="margin-bottom:1.75rem">
    <div class="eo-card-head">
        <span class="eo-card-label"><i class="fa-solid fa-ticket-perforated"></i> Penjualan Tiket per Event</span>
    </div>
    <div class="eo-card-body">
        <?php
        $tiketByEvent = [];
        foreach ($tiket_all as $t) $tiketByEvent[$t['id_booking']][] = $t;
        foreach ($tiketByEvent as $ev_id => $tickets):
            $ev = $tickets[0];
        ?>
        <div style="margin-bottom:1.1rem">
            <div style="font-size:12px;font-weight:600;color:var(--accent,#00D4D8);margin-bottom:.5rem">
                <i class="fa-solid fa-calendar-star" style="margin-right:4px"></i>
                #<?= $ev_id ?> · <?= htmlspecialchars($ev['nama_event'] ?? 'Event') ?>
            </div>
            <?php foreach ($tickets as $t):
                $pct = $t['kuota'] > 0 ? min(100, round($t['terjual'] / $t['kuota'] * 100)) : 0;
            ?>
            <div class="eo-ticket-row">
                <div class="eo-ticket-meta">
                    <span style="font-weight:500"><?= htmlspecialchars($t['tipe']) ?></span>
                    <span style="opacity:.55"><?= number_format($t['terjual']) ?> / <?= number_format($t['kuota']) ?> · <?= $pct ?>%</span>
                </div>
                <div class="eo-ticket-bar-bg">
                    <div class="eo-ticket-bar-fill" style="width:<?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '/includes/navbarM04_EO.php';