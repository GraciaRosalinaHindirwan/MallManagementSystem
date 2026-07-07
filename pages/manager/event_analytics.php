<?php
require_once __DIR__ . '/../../public/auth/checkSession.php';
require_once '../../pages/eventManager/event_data.php';

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
$department_name = "BI, Workflow, and Notification";
$page_title = "Dashboard KPI";
$user_name = "Manager";

$menu_items = [
    ['icon' => 'fa-solid fa-gauge', 'label' => 'Dashboard KPI', 'link' => '08_dashboard.php', 'active_page' => '08_dashboard'],
    ['icon' => 'fa-solid fa-chart-line', 'label' => 'Laporan', 'link' => '08_laporan.php', 'active_page' => '08_laporan'],
    ['icon' => 'fa-solid fa-check-circle', 'label' => 'Approval', 'link' => '08_approval.php', 'active_page' => '08_approval'],
    ['icon' => 'fa-solid fa-bell', 'label' => 'Notifikasi', 'link' => '08_notifikasi.php', 'active_page' => '08_notifikasi'],
    ['icon' => 'fa-solid fa-bell', 'label' => 'Event Analitik', 'link' => 'event_analytics.php', 'active_page' => 'event_analytics'],
    ['icon' => 'fa-solid fa-bell', 'label' => 'Utility Analitik', 'link' => 'utility_analitik.php', 'active_page' => 'utility_analitik']
];

$page_title = 'Post-Event Analytics';
$page       = 'event_analytics';

if (!isset($_SESSION['event_analytics_extended'])) {
    $_SESSION['event_analytics_extended'] = [];
}

$analytics = $_SESSION['event_analytics_extended'];

$totalRevenue    = array_sum(array_map(fn($a) => $a['revenue_sewa']+$a['revenue_tiket']+$a['revenue_sponsor'], $analytics));
$totalPengunjung = array_sum(array_column($analytics, 'jml_pengunjung'));
$avgRating       = count($analytics) > 0 ? round(array_sum(array_column($analytics,'rating_kepuasan'))/count($analytics),1) : 0;
$avgTrafficLift  = count($analytics) > 0
    ? round(array_sum(array_map(fn($a) => (($a['traffic_during']-$a['traffic_before'])/$a['traffic_before'])*100, $analytics))/count($analytics),1)
    : 0;

$chartLabels           = array_column($analytics,'nama_event');
$chartRevenueSewa      = array_column($analytics,'revenue_sewa');
$chartRevenueTiket     = array_column($analytics,'revenue_tiket');
$chartRevenueSponsor   = array_column($analytics,'revenue_sponsor');
$chartVisitors         = array_column($analytics,'jml_pengunjung');
$chartTargets          = array_column($analytics,'target_pengunjung');

ob_start();
?>

<style>
.an-page-header {
    background: linear-gradient(135deg, #082A53 0%, #0D4859 60%, #0a3d4a 100%);
    padding: 1.75rem 2rem;
    border-radius: 16px;
    margin-bottom: 1.75rem;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(0,212,216,.15);
}
.an-page-header::before {
    content: '';
    position: absolute;
    right: -80px; top: -80px;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0,212,216,.10) 0%, transparent 70%);
}
.an-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15);
    color: var(--text);
    border-radius: 20px;
    padding: .35rem 1rem;
    font-size: var(--caption);
    font-family: var(--font-family);
    font-weight: 500;
    text-decoration: none;
    transition: all .2s;
    cursor: pointer;
}
.an-back-btn:hover {
    background: rgba(255,255,255,.16);
    color: var(--accent);
    border-color: rgba(0,212,216,.35);
    transform: translateX(-2px);
}
.an-badge-readonly {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,182,42,.12);
    border: 1px solid rgba(255,182,42,.25);
    color: var(--text-accent);
    border-radius: 20px;
    font-size: 11px;
    padding: 3px 12px;
    font-weight: 600;
}
.an-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.75rem;
}
@media (max-width: 768px) {
    .an-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
.an-kpi-card {
    background: var(--primary);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    position: relative;
    overflow: hidden;
    transition: transform .2s, border-color .2s;
}
.an-kpi-card:hover {
    transform: translateY(-2px);
    border-color: rgba(0,212,216,.25);
}
.an-kpi-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 3px;
    border-radius: 14px 14px 0 0;
}
.an-kpi-card.revenue::after  { background: linear-gradient(90deg, #00D4D8, #FFB62A); }
.an-kpi-card.visitors::after { background: linear-gradient(90deg, #22C55E, #00D4D8); }
.an-kpi-card.rating::after   { background: linear-gradient(90deg, #f59e0b, #f97316); }
.an-kpi-card.traffic::after  { background: linear-gradient(90deg, #8b5cf6, #00D4D8); }
.an-kpi-val    { font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
.an-kpi-label  { font-size: 11px; opacity: .55; text-transform: uppercase; letter-spacing: .06em; margin-top: 4px; }
.an-kpi-sub    { font-size: 11px; margin-top: 6px; }
.an-kpi-icon   { font-size: 2.2rem; opacity: .12; position: absolute; right: 1.25rem; top: 50%; transform: translateY(-50%); }

.an-chart-card {
    background: var(--primary);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 14px;
    padding: 1.4rem 1.5rem;
    height: 100%;
}
.an-chart-title {
    font-size: 11px;
    font-weight: 700;
    color: var(--accent);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 1.1rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.an-event-card {
    background: var(--primary);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 14px;
    margin-bottom: 1rem;
    overflow: hidden;
    transition: border-color .2s;
}
.an-event-card:hover {
    border-color: rgba(0,212,216,.2);
}
.an-event-head {
    background: linear-gradient(90deg, var(--primary-dark) 0%, rgba(8,42,83,.6) 100%);
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: .5rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.an-event-body { padding: 1.25rem 1.5rem; }
.an-metric-box {
    background: var(--primary-dark);
    border-radius: 10px;
    padding: .85rem 1rem;
    border: 1px solid rgba(255,255,255,.06);
}
.an-metric-box .val { font-size: 1.05rem; font-weight: 700; color: var(--text-accent); }
.an-metric-box .lbl { font-size: 10px; opacity: .5; text-transform: uppercase; margin-top: 2px; }
.an-rev-chip {
    flex: 1;
    min-width: 100px;
    border-radius: 8px;
    padding: .6rem .85rem;
    font-size: var(--caption);
}
.an-rev-sewa    { background: rgba(22,126,128,.18); border: 1px solid rgba(22,126,128,.3); }
.an-rev-tiket   { background: rgba(0,212,216,.13);  border: 1px solid rgba(0,212,216,.3); }
.an-rev-sponsor { background: rgba(255,182,42,.13); border: 1px solid rgba(255,182,42,.3); }
.an-rev-lbl { opacity: .6; margin-bottom: 2px; font-size: 10px; }
.an-rev-val { font-weight: 700; font-size: var(--label); }
.an-star     { color: #f59e0b; }
.an-empty {
    text-align: center;
    padding: 3.5rem 2rem;
    opacity: .45;
}
.an-note {
    background: rgba(255,255,255,.04);
    border-left: 3px solid var(--secondary);
    border-radius: 0 8px 8px 0;
    padding: .65rem 1rem;
    font-size: var(--caption);
    margin-top: .85rem;
}
</style>

<div class="an-page-header">
    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
        <div>
            <div style="font-size:11px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.4rem">
                <i class="fa-solid fa-chart-line me-1"></i>PBI-M04-03-04
            </div>
            <h2 class="fw-bold mb-1" style="font-size:1.4rem">Post-Event Analytics Dashboard</h2>
            <small style="opacity:.65">Laporan pengunjung, revenue, traffic impact & kepuasan pasca-event</small>
        </div>
        <div style="font-size:var(--caption);opacity:.6;text-align:right">
            <i class="fa-regular fa-calendar me-1"></i>
            <strong style="color:var(--text-accent)"><?= count($analytics) ?></strong> event selesai
        </div>
    </div>
</div>

<?php if (empty($analytics)): ?>
<div class="an-chart-card an-empty">
    <i class="fa-solid fa-chart-bar" style="font-size:3rem;display:block;margin-bottom:1rem;color:var(--accent);opacity:.3"></i>
    <div style="font-size:1rem;font-weight:600;margin-bottom:.5rem">Belum Ada Data Analytics</div>
    <p style="font-size:var(--caption);max-width:320px;margin:0 auto">
        Data akan muncul otomatis setelah event selesai dilaksanakan dan tim input laporan pasca-event.
    </p>
</div>

<?php else: ?>

<div class="an-kpi-grid">
    <div class="an-kpi-card revenue">
        <i class="fa-solid fa-sack-dollar an-kpi-icon"></i>
        <div class="an-kpi-val" style="color:var(--text-accent)">Rp <?= number_format($totalRevenue/1000000,1) ?>jt</div>
        <div class="an-kpi-label">Total Revenue Event</div>
        <div class="an-kpi-sub" style="color:var(--success)">
            <i class="fa-solid fa-arrow-up-short-wide"></i> Sewa + Tiket + Sponsor
        </div>
    </div>
    <div class="an-kpi-card visitors">
        <i class="fa-solid fa-users an-kpi-icon"></i>
        <div class="an-kpi-val" style="color:#86efac"><?= number_format($totalPengunjung) ?></div>
        <div class="an-kpi-label">Total Pengunjung</div>
        <div class="an-kpi-sub" style="opacity:.55">Akumulasi semua event</div>
    </div>
    <div class="an-kpi-card rating">
        <i class="fa-solid fa-star an-kpi-icon"></i>
        <div class="an-kpi-val" style="color:#f59e0b"><?= $avgRating ?> <span style="font-size:.9rem">★</span></div>
        <div class="an-kpi-label">Avg. Kepuasan</div>
        <div class="an-kpi-sub" style="opacity:.55">Dari semua event</div>
    </div>
    <div class="an-kpi-card traffic">
        <i class="fa-solid fa-arrow-trend-up an-kpi-icon"></i>
        <div class="an-kpi-val" style="color:#c4b5fd">+<?= $avgTrafficLift ?>%</div>
        <div class="an-kpi-label">Avg. Traffic Lift</div>
        <div class="an-kpi-sub" style="color:var(--accent)">
            <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:9px"></i> vs hari normal
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="an-chart-card">
            <div class="an-chart-title">
                <i class="fa-solid fa-chart-bar"></i> Revenue per Event (Juta Rp)
            </div>
            <canvas id="chartRevenue" height="180"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="an-chart-card">
            <div class="an-chart-title">
                <i class="fa-solid fa-people-group"></i> Pengunjung: Target vs Aktual
            </div>
            <canvas id="chartVisitors" height="180"></canvas>
        </div>
    </div>
</div>

<div style="font-size:11px;color:var(--accent);font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:1rem">
    <i class="fa-solid fa-list-ul me-1"></i> Detail Per Event
</div>

<?php foreach ($analytics as $a):
    $totalRev   = $a['revenue_sewa'] + $a['revenue_tiket'] + $a['revenue_sponsor'];
    $pctTarget  = $a['target_pengunjung']>0 ? round($a['jml_pengunjung']/$a['target_pengunjung']*100) : 0;
    $trafficLift= $a['traffic_before']>0 ? round(($a['traffic_during']-$a['traffic_before'])/$a['traffic_before']*100,1) : 0;
    $maxTraf    = max($a['traffic_before'],$a['traffic_during'],$a['traffic_after'],1);
    $hB = round($a['traffic_before']/$maxTraf*100);
    $hD = round($a['traffic_during']/$maxTraf*100);
    $hA = round($a['traffic_after']/$maxTraf*100);
?>
<div class="an-event-card">
    <div class="an-event-head">
        <div>
            <span style="font-size:11px;color:var(--accent);font-weight:600"><?= htmlspecialchars($a['id_event']) ?></span>
            <h6 class="mb-0 mt-1 fw-bold"><?= htmlspecialchars($a['nama_event']) ?></h6>
            <small style="opacity:.55">
                <?= htmlspecialchars($a['tipe']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($a['area']) ?>
                &nbsp;·&nbsp; <?= htmlspecialchars($a['tanggal']) ?>
            </small>
        </div>
        <div style="text-align:right">
            <div style="font-size:1.2rem;font-weight:700;color:var(--text-accent)">
                Rp <?= number_format($totalRev/1000000,1) ?>jt
            </div>
            <small style="opacity:.45">Total Revenue</small>
        </div>
    </div>
    <div class="an-event-body">
        <div class="row g-3">

            <div class="col-md-3">
                <div class="an-metric-box">
                    <div class="lbl">Pengunjung Aktual</div>
                    <div class="val"><?= number_format($a['jml_pengunjung']) ?></div>
                    <div style="font-size:10px;margin-top:5px">
                        <?php if ($pctTarget >= 100): ?>
                        <span style="color:var(--success)">
                            <i class="fa-solid fa-circle-check"></i> <?= $pctTarget ?>% dari target
                        </span>
                        <?php else: ?>
                        <span style="color:var(--danger)">
                            <i class="fa-solid fa-arrow-down"></i> <?= $pctTarget ?>% dari target
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div style="font-size:10px;opacity:.45;text-transform:uppercase;margin-bottom:.5rem">Revenue Breakdown</div>
                <div class="d-flex gap-2 flex-wrap">
                    <div class="an-rev-chip an-rev-sewa">
                        <div class="an-rev-lbl">Sewa Area</div>
                        <div class="an-rev-val">Rp <?= number_format($a['revenue_sewa']/1000000,1) ?>jt</div>
                    </div>
                    <div class="an-rev-chip an-rev-tiket">
                        <div class="an-rev-lbl">Tiket</div>
                        <div class="an-rev-val">Rp <?= number_format($a['revenue_tiket']/1000000,1) ?>jt</div>
                    </div>
                    <div class="an-rev-chip an-rev-sponsor">
                        <div class="an-rev-lbl">Sponsor</div>
                        <div class="an-rev-val">Rp <?= number_format($a['revenue_sponsor']/1000000,1) ?>jt</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div style="font-size:10px;opacity:.45;text-transform:uppercase;margin-bottom:.5rem">Traffic Impact</div>
                <div style="display:flex;gap:6px;align-items:flex-end;height:56px">
                    <?php foreach ([['Sblm',$hB,'rgba(255,255,255,.15)'],['Event',$hD,'var(--accent)'],['Stlh',$hA,'rgba(255,255,255,.28)']] as [$lbl,$h,$clr]): ?>
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%">
                        <div style="width:100%;height:<?= $h ?>%;border-radius:3px 3px 0 0;background:<?= $clr ?>"></div>
                        <div style="font-size:9px;opacity:.45;margin-top:2px"><?= $lbl ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="font-size:11px;color:var(--accent);margin-top:4px;font-weight:600">
                    <i class="fa-solid fa-arrow-trend-up" style="font-size:9px"></i> +<?= $trafficLift ?>% lift
                </div>
            </div>
            <div class="col-md-2">
                <div style="font-size:10px;opacity:.45;text-transform:uppercase;margin-bottom:.5rem">Kepuasan</div>
                <div class="an-star" style="font-size:1.05rem">
                    <?= str_repeat('★',round($a['rating_kepuasan'])).str_repeat('☆',5-round($a['rating_kepuasan'])) ?>
                    <span style="font-size:1rem;color:var(--text-accent);margin-left:3px"><?= $a['rating_kepuasan'] ?></span>
                </div>
                <div style="font-size:10px;margin-top:4px;opacity:.55">
                    Vendor: <span style="color:#f59e0b"><?= str_repeat('★',round($a['rating_vendor'])) ?></span> <?= $a['rating_vendor'] ?>
                </div>
            </div>
        </div>
        <?php if (!empty($a['catatan'])): ?>
        <div class="an-note">
            <i class="fa-solid fa-journal-whills me-1" style="color:var(--secondary)"></i>
            <strong>Catatan:</strong> <?= htmlspecialchars($a['catatan']) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php
$extra_scripts = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const anChartDefaults = {
    responsive: true,
    plugins: { legend: { labels: { color:'#F5F7FA', font:{family:'Poppins',size:11}, boxWidth:12 } } },
    scales: {
        x: { ticks:{color:'rgba(245,247,250,.45)',font:{family:'Poppins',size:10}}, grid:{color:'rgba(255,255,255,.04)'} },
        y: { ticks:{color:'rgba(245,247,250,.45)',font:{family:'Poppins',size:10}}, grid:{color:'rgba(255,255,255,.04)'} }
    }
};
JS;

if (!empty($analytics)) {
    $extra_scripts .= "
new Chart(document.getElementById('chartRevenue'), {
    type: 'bar',
    data: {
        labels: " . json_encode($chartLabels) . ",
        datasets: [
            { label:'Sewa',    data:" . json_encode(array_map(fn($v)=>round($v/1000000,1),$chartRevenueSewa)) . ",    backgroundColor:'rgba(22,126,128,.75)',  borderRadius:5 },
            { label:'Tiket',   data:" . json_encode(array_map(fn($v)=>round($v/1000000,1),$chartRevenueTiket)) . ",   backgroundColor:'rgba(0,212,216,.65)',   borderRadius:5 },
            { label:'Sponsor', data:" . json_encode(array_map(fn($v)=>round($v/1000000,1),$chartRevenueSponsor)) . ", backgroundColor:'rgba(255,182,42,.75)',  borderRadius:5 },
        ]
    },
    options: anChartDefaults
});
new Chart(document.getElementById('chartVisitors'), {
    type: 'bar',
    data: {
        labels: " . json_encode($chartLabels) . ",
        datasets: [
            { label:'Target', data:" . json_encode($chartTargets) . ",  backgroundColor:'rgba(255,255,255,.1)', borderRadius:5 },
            { label:'Aktual', data:" . json_encode($chartVisitors) . ", backgroundColor:'rgba(0,212,216,.75)', borderRadius:5 },
        ]
    },
    options: anChartDefaults
});";
}

$extra_scripts .= "</script>";

$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '/includes/08_nav_template.php';