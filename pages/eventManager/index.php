<?php
// pages/eventManager/index.php
// PBI-M04-03 — Dashboard Event Management
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

$page_title = 'Dashboard Event Management';
$page       = 'index';

$semua    = getBookings();
$pending  = array_filter($semua, fn($b) => $b['status'] === 'pending');
$approved = array_filter($semua, fn($b) => $b['status'] === 'approved');
$analytics = getAnalytics();

ob_start();
?>

<style>
/* ── Dashboard Styles ── */
.em-hero {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-dark) 55%, #0a3d4a 100%);
    padding: 2.25rem 2rem 2rem;
    border-radius: 18px;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0,212,216,.12);
}
.em-hero::before {
    content: '';
    position: absolute;
    right: -80px; top: -80px;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0,212,216,.1) 0%, transparent 70%);
    pointer-events: none;
}
.em-hero::after {
    content: '';
    position: absolute;
    left: -40px; bottom: -60px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(22,126,128,.08) 0%, transparent 70%);
    pointer-events: none;
}
.em-kpi-pill {
    background: rgba(255,255,255,.07);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 12px;
    padding: .85rem 1rem;
    text-align: center;
    transition: all .2s;
}
.em-kpi-pill:hover {
    background: rgba(255,255,255,.11);
    border-color: rgba(0,212,216,.25);
    transform: translateY(-2px);
}

/* Feature cards */
.em-feature-card {
    background: var(--primary);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 16px;
    padding: 1.5rem;
    text-decoration: none;
    color: var(--text);
    display: block;
    height: 100%;
    transition: all .22s cubic-bezier(.4,0,.2,1);
    position: relative;
    overflow: hidden;
}
.em-feature-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    opacity: 0;
    transition: opacity .22s;
    pointer-events: none;
}
.em-feature-card:hover {
    color: var(--text);
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,.3);
}
.em-feature-card:hover::before { opacity: 1; }

.em-feature-card.c-teal  { --fc: var(--accent);  }
.em-feature-card.c-gold  { --fc: var(--text-accent); }
.em-feature-card.c-cyan  { --fc: #67e8f9; }
.em-feature-card.c-violet{ --fc: #c4b5fd; }
.em-feature-card.c-green { --fc: #86efac; }

.em-feature-card:hover { border-color: var(--fc, rgba(255,255,255,.2)); }
.em-feature-icon {
    width: 50px; height: 50px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    margin-bottom: 1rem;
}
.em-feature-pbi {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 4px;
}
.em-feature-card h6 { font-size: .9rem; font-weight: 700; margin-bottom: .35rem; }
.em-feature-card p  { font-size: 12px; opacity: .55; margin: 0; line-height: 1.5; }
.em-feature-link { font-size: 12px; margin-top: .75rem; font-weight: 600; display: flex; align-items: center; gap: 4px; }

/* Badge */
.em-count-badge {
    background: var(--danger);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    border-radius: 10px;
    padding: 1px 7px;
    margin-left: 5px;
    vertical-align: middle;
    animation: pulse-badge 1.5s infinite;
}
@keyframes pulse-badge {
    0%,100% { opacity: 1; }
    50%      { opacity: .7; }
}

/* Activity feed */
.em-activity-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .55rem 0;
    border-bottom: 1px solid rgba(255,255,255,.05);
    gap: .5rem;
}
.em-activity-row:last-child { border-bottom: none; }
</style>

<!-- HERO SECTION -->
<div class="em-hero">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span style="font-size:11px;background:rgba(0,212,216,.15);border:1px solid rgba(0,212,216,.25);
                     border-radius:20px;padding:3px 14px;color:var(--accent);font-weight:600">
            <i class="fa-solid fa-calendar-star me-1"></i>SISFO MALL — EVENT
        </span>
    </div>
    <h2 class="fw-bold mb-1" style="font-size:1.55rem;position:relative;z-index:1">Event Management</h2>
    <p style="opacity:.55;font-size:var(--caption);margin-bottom:1.5rem;position:relative;z-index:1">
        Kelola pengajuan, approval, vendor, ticketing, sponsorship &amp; analitik event mall
    </p>
    <div class="row g-2" style="max-width:560px;position:relative;z-index:1">
        <?php $kpis = [
            ['val'=>count($semua),     'lbl'=>'Total Pengajuan', 'clr'=>'var(--text)'],
            ['val'=>count($pending),   'lbl'=>'Pending Review',  'clr'=>'#fde68a'],
            ['val'=>count($approved),  'lbl'=>'Approved',        'clr'=>'#86efac'],
            ['val'=>count($analytics), 'lbl'=>'Event Selesai',   'clr'=>'var(--text-accent)'],
        ]; foreach ($kpis as $k): ?>
        <div class="col-6 col-md-3">
            <div class="em-kpi-pill">
                <div style="font-size:1.4rem;font-weight:700;color:<?= $k['clr'] ?>"><?= $k['val'] ?></div>
                <div style="font-size:10px;opacity:.5;text-transform:uppercase;letter-spacing:.05em;margin-top:2px"><?= $k['lbl'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- FEATURE CARDS GRID -->
<div class="row g-3 mb-4">

    <!-- PBI-01: Form Booking (Event Organizer) -->
    <div class="col-md-6 col-lg-3">
        <a href="../eventOrganizer/event_booking_form.php" class="em-feature-card c-teal">
            <div class="em-feature-icon" style="background:rgba(0,212,216,.15);color:var(--accent)">
                <i class="fa-solid fa-calendar-plus"></i>
            </div>
            <div class="em-feature-pbi" style="color:var(--accent)">PBI-M04-03-01 · Event Organizer</div>
            <h6>
                Form Pengajuan Booking
                <?php if (count($pending) > 0): ?>
                <span class="em-count-badge"><?= count($pending) ?></span>
                <?php endif; ?>
            </h6>
            <p>EO/Tenant ajukan booking area event. Conflict-check otomatis berjalan saat submit.</p>
            <div class="em-feature-link" style="color:var(--accent)">
                <i class="fa-solid fa-arrow-right"></i> Buka Form
            </div>
        </a>
    </div>

    <!-- PBI-01: Status Pengajuan (Event Organizer) -->
    <div class="col-md-6 col-lg-3">
        <a href="../eventOrganizer/event_booking_status.php" class="em-feature-card c-gold">
            <div class="em-feature-icon" style="background:rgba(255,182,42,.12);color:var(--text-accent)">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div class="em-feature-pbi" style="color:var(--text-accent)">PBI-M04-03-01 · Event Organizer</div>
            <h6>Status Pengajuan</h6>
            <p>Pantau progres semua pengajuan dengan timeline visual per-event.</p>
            <div class="em-feature-link" style="color:var(--text-accent)">
                <i class="fa-solid fa-arrow-right"></i> Lihat Status
            </div>
        </a>
    </div>

    <!-- PBI-02: Kalender & Approval (Event Manager) -->
    <div class="col-md-6 col-lg-3">
        <a href="event_calendar.php" class="em-feature-card c-cyan">
            <div class="em-feature-icon" style="background:rgba(22,126,128,.18);color:#67e8f9">
                <i class="fa-solid fa-calendar-week"></i>
            </div>
            <div class="em-feature-pbi" style="color:#67e8f9">PBI-M04-03-02 · Event Manager</div>
            <h6>
                Kalender &amp; Approval
                <?php if (count($pending)): ?>
                <span class="em-count-badge"><?= count($pending) ?></span>
                <?php endif; ?>
            </h6>
            <p>Kalender per area + conflict-check otomatis + workflow approve / tolak / revisi.</p>
            <div class="em-feature-link" style="color:#67e8f9">
                <i class="fa-solid fa-arrow-right"></i> Buka Kalender
            </div>
        </a>
    </div>

    <!-- PBI-03: Vendor & Ticketing (Event Manager) -->
    <div class="col-md-6 col-lg-3">
        <a href="event_vendor_ticketing.php" class="em-feature-card c-violet">
            <div class="em-feature-icon" style="background:rgba(139,92,246,.18);color:#c4b5fd">
                <i class="fa-solid fa-people-group"></i>
            </div>
            <div class="em-feature-pbi" style="color:#c4b5fd">PBI-M04-03-03 · Event Manager</div>
            <h6>Vendor · Tiket · Sponsor</h6>
            <p>Kelola vendor, setup tiket digital per-kuota, dan manajemen sponsorship.</p>
            <div class="em-feature-link" style="color:#c4b5fd">
                <i class="fa-solid fa-arrow-right"></i> Kelola Koordinasi
            </div>
        </a>
    </div>

    <!-- PBI-04: Analytics (Manager - Read Only) -->
    <div class="col-12 col-md-7">
        <a href="event_analytics.php" class="em-feature-card c-green" style="display:flex;gap:1.5rem;align-items:center">
            <div class="em-feature-icon" style="background:rgba(34,197,94,.15);color:#86efac;flex-shrink:0;width:56px;height:56px;font-size:1.6rem">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <div class="em-feature-pbi" style="color:#86efac">PBI-M04-03-04 · Manajer (Read-Only)</div>
                <h6>Post-Event Analytics Dashboard</h6>
                <p>Laporan pengunjung, revenue, traffic impact, rating kepuasan pasca-event.</p>
                <div class="em-feature-link" style="color:#86efac">
                    <i class="fa-solid fa-arrow-right"></i> Buka Analytics
                </div>
            </div>
        </a>
    </div>

    <!-- Aktivitas Terkini -->
    <div class="col-12 col-md-5">
        <div style="background:var(--primary);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:1.5rem;height:100%">
            <div style="font-size:11px;color:var(--accent);font-weight:700;text-transform:uppercase;
                        letter-spacing:.08em;margin-bottom:1rem;display:flex;align-items:center;gap:6px">
                <i class="fa-solid fa-bolt"></i> Aktivitas Terkini
            </div>
            <?php foreach (array_slice(array_reverse($semua), 0, 5) as $b): ?>
            <div class="em-activity-row">
                <div>
                    <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($b['nama_event']) ?></div>
                    <div style="font-size:11px;opacity:.45"><?= htmlspecialchars($b['tipe_event']) ?></div>
                </div>
                <?= statusBadge($b['status']) ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($semua)): ?>
            <div style="text-align:center;opacity:.35;padding:1.5rem;font-size:12px">
                <i class="fa-solid fa-inbox" style="font-size:1.8rem;display:block;margin-bottom:.5rem"></i>
                Belum ada pengajuan.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../../includes/navbar.php';