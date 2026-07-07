<?php
require_once __DIR__ . '/../../public/auth/checkSession.php';
require_once __DIR__ . '/event_data.php';


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
$page       = 'dashboad_em';

$semua     = getBookings();
$pending   = array_filter($semua, fn($b) => $b['status'] === 'pending');
$approved  = array_filter($semua, fn($b) => $b['status'] === 'approved');
$analytics = getAnalytics();

ob_start();
?>

<style>
.em-card {
    background: var(--primary);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 14px;
    overflow: hidden;
}
.em-card-header {
    padding: .85rem 1.4rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
}
.em-card-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .09em;
    color: var(--accent);
    display: flex;
    align-items: center;
    gap: 6px;
}
.em-card-body { padding: 1.4rem; }

.em-table {
    width: 100%;
    border-collapse: collapse;
    color: var(--text);
    font-size: 13px;
}
.em-table thead tr {
    background: var(--primary-dark);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    opacity: .65;
}
.em-table th { padding: .7rem 1rem; white-space: nowrap; }
.em-table td { padding: .7rem 1rem; border-bottom: 1px solid rgba(255,255,255,.05); vertical-align: middle; }
.em-table tbody tr:last-child td { border-bottom: none; }
.em-table tbody tr:hover { background: rgba(255,255,255,.025); }

.em-btn {
    border: none;
    border-radius: 7px;
    padding: 4px 11px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: opacity .15s, transform .15s;
    text-decoration: none;
}
.em-btn:hover { opacity: .85; transform: translateY(-1px); }
.em-btn-success { background: var(--success); color: #fff; }
.em-btn-danger  { background: rgba(239,68,68,.18); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
.em-btn-warn    { background: var(--secondary); color: #fff; }
.em-btn-primary { background: var(--accent); color: #021F42; font-weight: 700; }

.em-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.em-input,
.em-select {
    background: var(--primary-dark);
    border: 1px solid rgba(255,255,255,.13);
    color: var(--text);
    border-radius: 8px;
    padding: .45rem .75rem;
    font-size: 13px;
    width: 100%;
    transition: border-color .2s;
}
.em-input:focus, .em-select:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0,212,216,.1);
}
.em-label {
    font-size: 12px;
    font-weight: 600;
    opacity: .75;
    margin-bottom: 5px;
    display: block;
}

.em-hero {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-dark, #0d2d35) 55%, #0a3d4a 100%);
    padding: 2rem 2rem 1.75rem;
    border-radius: 16px;
    margin-bottom: 1.75rem;
    border: 1px solid rgba(0,212,216,.1);
    position: relative;
    overflow: hidden;
}
.em-hero::before {
    content: '';
    position: absolute; right: -60px; top: -60px;
    width: 280px; height: 280px; border-radius: 50%;
    background: radial-gradient(circle, rgba(0,212,216,.09) 0%, transparent 70%);
    pointer-events: none;
}
.em-hero-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .07em;
    color: var(--accent);
    background: rgba(0,212,216,.12);
    border: 1px solid rgba(0,212,216,.22);
    border-radius: 20px;
    padding: 3px 13px;
    margin-bottom: .85rem;
}
.em-hero h2 {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0 0 .35rem;
    position: relative;
    z-index: 1;
}
.em-hero p {
    opacity: .5;
    font-size: 13px;
    margin: 0 0 1.4rem;
    position: relative;
    z-index: 1;
}
.em-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .6rem;
    max-width: 520px;
    position: relative;
    z-index: 1;
}
@media (max-width: 576px) {
    .em-kpi-grid { grid-template-columns: repeat(2, 1fr); max-width: 100%; }
    .em-hero { padding: 1.4rem 1.2rem 1.4rem; }
    .em-hero h2 { font-size: 1.2rem; }
}
.em-kpi-pill {
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 11px;
    padding: .75rem .9rem;
    text-align: center;
    transition: background .2s, border-color .2s, transform .2s;
}
.em-kpi-pill:hover {
    background: rgba(255,255,255,.11);
    border-color: rgba(0,212,216,.25);
    transform: translateY(-2px);
}
.em-kpi-val  { font-size: 1.35rem; font-weight: 800; line-height: 1; }
.em-kpi-lbl  { font-size: 10px; opacity: .45; text-transform: uppercase; letter-spacing: .05em; margin-top: 3px; }

/* Feature grid */
.em-features { display: grid; grid-template-columns: repeat(5, 1fr); gap: .85rem; margin-bottom: 1.5rem; }
@media (max-width: 1111px) { .em-features { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .em-features { grid-template-columns: 1fr; } }

.em-feat-card {
    background: var(--primary);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 14px;
    padding: 1.35rem;
    text-decoration: none;
    color: var(--text);
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: transform .2s cubic-bezier(.4,0,.2,1), box-shadow .2s, border-color .2s;
}
.em-feat-card:hover {
    color: var(--text);
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(0,0,0,.28);
    border-color: var(--fc, rgba(255,255,255,.15));
}
.em-feat-card.c-teal   { --fc: var(--accent); }
.em-feat-card.c-gold   { --fc: var(--text-accent); }
.em-feat-card.c-cyan   { --fc: #67e8f9; }
.em-feat-card.c-violet { --fc: #c4b5fd; }
.em-feat-card.c-green  { --fc: #86efac; }

.em-feat-icon {
    width: 46px; height: 46px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem;
    margin-bottom: .9rem;
    flex-shrink: 0;
}
.em-feat-pbi { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px; opacity: .9; }
.em-feat-card h6 { font-size: .88rem; font-weight: 700; margin: 0 0 .3rem; }
.em-feat-card p  { font-size: 12px; opacity: .5; margin: 0; line-height: 1.55; flex: 1; }
.em-feat-link {
    font-size: 12px; font-weight: 700;
    margin-top: .85rem;
    display: flex; align-items: center; gap: 4px;
}

.em-count-badge {
    background: var(--danger);
    color: #fff;
    font-size: 10px; font-weight: 700;
    border-radius: 10px;
    padding: 1px 7px;
    margin-left: 4px;
    vertical-align: middle;
    animation: em-pulse 1.6s ease-in-out infinite;
}
@keyframes em-pulse {
    0%,100% { opacity: 1; }
    50%      { opacity: .6; }
}

.em-bottom-row { display: grid; grid-template-columns: 1fr 0px;}
@media (max-width: 992px) { .em-bottom-row { grid-template-columns: 1fr; } }

.em-activity-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .55rem 0;
    border-bottom: 1px solid rgba(255,255,255,.05);
}
</style>

<div class="em-hero">
    <div class="em-hero-tag">
        <i class="fa-solid fa-calendar-star"></i> SISFO MALL — EVENT
    </div>
    <h2>Event Management</h2>
    <p>Kelola approval, vendor, ticketing, &amp; sponsorship</p>

    <div class="em-kpi-grid">
        <?php
        $kpis = [
            ['val' => count($semua),     'lbl' => 'Total',    'clr' => 'var(--text)'],
            ['val' => count($pending),   'lbl' => 'Pending',  'clr' => '#fde68a'],
            ['val' => count($approved),  'lbl' => 'Approved', 'clr' => '#86efac'],
            ['val' => count($analytics), 'lbl' => 'Selesai',  'clr' => 'var(--text-accent)'],
        ];
        foreach ($kpis as $k): ?>
        <div class="em-kpi-pill">
            <div class="em-kpi-val" style="color:<?= $k['clr'] ?>"><?= $k['val'] ?></div>
            <div class="em-kpi-lbl"><?= $k['lbl'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="em-features">

    <a href="event_areas.php" class="em-feat-card c-gold">
        <div class="em-feat-icon" style="background:rgba(255,182,42,.12);color:var(--text-accent)">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="em-feat-pbi" style="color:var(--text-accent)">Event Area</div>
        <h6>Status Area Event</h6>
        <p>Pantau progres semua pengajuan dengan timeline visual per-event.</p>
        <div class="em-feat-link" style="color:var(--text-accent)">
            <i class="fa-solid fa-arrow-right"></i> Lihat Status
        </div>
    </a>

    <a href="event_approval.php" class="em-feat-card c-cyan">
        <div class="em-feat-icon" style="background:rgba(22,126,128,.18);color:#67e8f9">
            <i class="fa-solid fa-calendar-week"></i>
        </div>
        <div class="em-feat-pbi" style="color:#67e8f9">Event Approval</div>
        <h6>Kelola Persetujuan
            <?php if (count($pending)): ?><span class="em-count-badge"><?= count($pending) ?></span><?php endif; ?>
        </h6>
        <p>Kalender per area + conflict-check otomatis + workflow approve / tolak / revisi.</p>
        <div class="em-feat-link" style="color:#67e8f9">
            <i class="fa-solid fa-arrow-right"></i> Lihat Detail
        </div>
    </a>

    <a href="event_vendor.php" class="em-feat-card c-violet">
        <div class="em-feat-icon" style="background:rgba(139,92,246,.18);color:#c4b5fd">
            <i class="fa-solid fa-people-group"></i>
        </div>
        <div class="em-feat-pbi" style="color:#c4b5fd">Event Vendor</div>
        <h6>Kelola Vendor</h6>
        <p>Kelola database vendor yang digunakan pada seluruh event.</p>
        <div class="em-feat-link" style="color:#c4b5fd">
            <i class="fa-solid fa-arrow-right"></i> Lihat Vendor
        </div>
    </a>

    <a href="event_ticketing.php" class="em-feat-card c-violet">
        <div class="em-feat-icon" style="background:rgba(139,92,246,.18);color:#c4b5fd">
            <i class="fa-solid fa-people-group"></i>
        </div>
        <div class="em-feat-pbi" style="color:#c4b5fd">Event Ticketing</div>
        <h6>Kelola Tiket</h6>
        <p>Kelola ticketing yang digunakan pada seluruh event.</p>
        <div class="em-feat-link" style="color:#c4b5fd">
            <i class="fa-solid fa-arrow-right"></i> Lihat Ticket
        </div>
    </a>

    <a href="event_sponsorship.php" class="em-feat-card c-violet">
        <div class="em-feat-icon" style="background:rgba(139,92,246,.18);color:#c4b5fd">
            <i class="fa-solid fa-people-group"></i>
        </div>
        <div class="em-feat-pbi" style="color:#c4b5fd">Event Sponsorship</div>
        <h6>Kelola Sponsor</h6>
        <p>Kelola database sponsor yang digunakan pada seluruh event.</p>
        <div class="em-feat-link" style="color:#c4b5fd">
            <i class="fa-solid fa-arrow-right"></i> Lihat Sponsor
        </div>
    </a>
</div>

<div class="em-bottom-row">
    <div class="em-card">
        <div class="em-card-header">
            <span class="em-card-label"><i class="fa-solid fa-bolt"></i> Aktivitas Terkini</span>
        </div>
        <div class="em-card-body" style="padding:1rem 1.4rem">
            <?php foreach (array_slice(array_reverse($semua), 0, 6) as $b): ?>
            <div class="em-activity-row">
                <div>
                    <div class="em-activity-name"><?= htmlspecialchars($b['nama_event']) ?></div>
                    <div class="em-activity-type"><?= htmlspecialchars($b['tipe_event']) ?></div>
                </div>
                <?= statusBadge($b['status']) ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($semua)): ?>
            <div style="text-align:center;opacity:.3;padding:1.5rem;font-size:12px">
                <i class="fa-solid fa-inbox" style="font-size:1.6rem;display:block;margin-bottom:.4rem"></i>
                Belum ada pengajuan.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '/includes/navbarM04_EM.php';