<?php
require_once 'event_data.php';

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISFO MALL - Dashboard Analisis Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../public/asset/css/designSystem.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { background: var(--background); color: var(--text); font-family: var(--font-family); }
        .page-header { background: linear-gradient(135deg, #082A53, var(--secondary-dark)); padding: 1.75rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .btn-back { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); color: var(--text); border-radius: 8px; padding: .4rem 1rem; font-size: var(--label); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background .2s; }
        .btn-back:hover { background: rgba(255,255,255,.15); color: var(--text); }
        .kpi-card { background: var(--primary); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; padding: 1.25rem 1.5rem; }
        .kpi-value { font-size: 1.55rem; font-weight: 700; line-height: 1.1; }
        .kpi-label { font-size: var(--caption); opacity: .6; text-transform: uppercase; letter-spacing: .06em; margin-top: 4px; }
        .kpi-delta { font-size: var(--caption); margin-top: 6px; }
        .kpi-icon { font-size: 2rem; opacity: .18; }
        .chart-card { background: var(--primary); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
        .chart-title { font-size: var(--label); font-weight: 600; color: var(--accent); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 1rem; }
        .event-detail-card { background: var(--primary); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; margin-bottom: 1rem; overflow: hidden; }
        .event-detail-header { background: var(--primary-dark); padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; }
        .event-detail-body { padding: 1.25rem 1.5rem; }
        .metric-box { flex: 1; min-width: 120px; background: var(--primary-dark); border-radius: 8px; padding: .75rem 1rem; }
        .metric-box .val { font-size: 1.1rem; font-weight: 700; color: var(--text-accent); }
        .metric-box .lbl { font-size: 10px; opacity: .55; text-transform: uppercase; margin-top: 2px; }
        .rev-item { flex: 1; min-width: 100px; border-radius: 8px; padding: .6rem .75rem; font-size: var(--caption); }
        .rev-sewa    { background: rgba(22,126,128,.2);  border: 1px solid rgba(22,126,128,.3); }
        .rev-tiket   { background: rgba(0,212,216,.15);  border: 1px solid rgba(0,212,216,.3); }
        .rev-sponsor { background: rgba(255,182,42,.15); border: 1px solid rgba(255,182,42,.3); }
        .rev-lbl { opacity: .65; margin-bottom: 2px; }
        .rev-val { font-weight: 700; font-size: var(--label); }
        .star-row { color: #f59e0b; font-size: 1.1rem; }
        .empty-state { text-align: center; padding: 3rem; opacity: .5; }
    </style>
</head>
<body>
<div class="container-fluid py-4 px-4">

    <div class="mb-3">
        <a href="index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Dashboard Event</a>
    </div>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-bar-chart-line me-2"></i>Post-Event Analytics</h4>
            <small style="opacity:.8">SISFO MALL</small>
        </div>
        <div style="font-size:var(--caption);opacity:.6">
            <i class="bi bi-calendar3 me-1"></i>Data dari <?= count($analytics) ?> event selesai
        </div>
    </div>

    <?php if (empty($analytics)): ?>
    <div class="empty-state">
        <i class="bi bi-bar-chart fs-1 d-block mb-2"></i>
        Belum ada data analytics event. Data akan muncul setelah event selesai dilaksanakan.
    </div>
    <?php else: ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="kpi-value" style="color:var(--text-accent)">Rp <?= number_format($totalRevenue/1000000,1) ?>jt</div>
                        <div class="kpi-label">Total Revenue Event</div>
                        <div class="kpi-delta" style="color:var(--success)"><i class="bi bi-arrow-up-short"></i>Sewa + Tiket + Sponsor</div>
                    </div>
                    <i class="bi bi-cash-coin kpi-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="kpi-value" style="color:var(--accent)"><?= number_format($totalPengunjung) ?></div>
                        <div class="kpi-label">Total Pengunjung</div>
                        <div class="kpi-delta" style="opacity:.6">Akumulasi semua event</div>
                    </div>
                    <i class="bi bi-people kpi-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="kpi-value" style="color:#86efac"><?= $avgRating ?> <span style="font-size:1rem;color:#f59e0b">★</span></div>
                        <div class="kpi-label">Avg. Kepuasan</div>
                        <div class="kpi-delta" style="opacity:.6">Dari semua event</div>
                    </div>
                    <i class="bi bi-star kpi-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="kpi-value" style="color:var(--secondary)">+<?= $avgTrafficLift ?>%</div>
                        <div class="kpi-label">Avg. Traffic Lift</div>
                        <div class="kpi-delta" style="color:var(--accent)"><i class="bi bi-arrow-up-right"></i>vs hari normal</div>
                    </div>
                    <i class="bi bi-graph-up-arrow kpi-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="chart-card">
                <div class="chart-title"><i class="bi bi-bar-chart me-1"></i>Revenue per Event (juta Rp)</div>
                <canvas id="chartRevenue" height="170"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-card">
                <div class="chart-title"><i class="bi bi-people me-1"></i>Pengunjung: Target vs Aktual</div>
                <canvas id="chartVisitors" height="170"></canvas>
            </div>
        </div>
    </div>

    <?php foreach ($analytics as $a):
        $totalRev   = $a['revenue_sewa'] + $a['revenue_tiket'] + $a['revenue_sponsor'];
        $pctTarget  = $a['target_pengunjung']>0 ? round($a['jml_pengunjung']/$a['target_pengunjung']*100) : 0;
        $trafficLift= $a['traffic_before']>0 ? round(($a['traffic_during']-$a['traffic_before'])/$a['traffic_before']*100,1) : 0;
        $maxTraf    = max($a['traffic_before'],$a['traffic_during'],$a['traffic_after']);
        $hB = round($a['traffic_before']/$maxTraf*100);
        $hD = round($a['traffic_during']/$maxTraf*100);
        $hA = round($a['traffic_after']/$maxTraf*100);
    ?>
    <div class="event-detail-card">
        <div class="event-detail-header">
            <div>
                <span style="font-size:var(--caption);color:var(--accent);font-weight:600"><?= $a['id_event'] ?></span>
                <h6 class="mb-0 mt-1 fw-bold"><?= $a['nama_event'] ?></h6>
                <small style="opacity:.6"><?= $a['tipe'] ?> · <?= $a['area'] ?> · <?= $a['tanggal'] ?></small>
            </div>
            <div style="text-align:right">
                <div style="font-size:1.15rem;font-weight:700;color:var(--text-accent)">Rp <?= number_format($totalRev/1000000,1) ?>jt</div>
                <small style="opacity:.5">Total Revenue</small>
            </div>
        </div>
        <div class="event-detail-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="metric-box">
                        <div class="lbl">Pengunjung Aktual</div>
                        <div class="val"><?= number_format($a['jml_pengunjung']) ?></div>
                        <div style="font-size:10px;margin-top:4px">
                            <?php if ($pctTarget>=100): ?>
                            <span style="color:var(--success)"><i class="bi bi-check-circle"></i> <?= $pctTarget ?>% dari target</span>
                            <?php else: ?>
                            <span style="color:var(--danger)"><i class="bi bi-arrow-down"></i> <?= $pctTarget ?>% dari target</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="lbl" style="font-size:10px;opacity:.5;text-transform:uppercase;margin-bottom:.5rem">Revenue Breakdown</div>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="rev-item rev-sewa"><div class="rev-lbl">Sewa Area</div><div class="rev-val">Rp <?= number_format($a['revenue_sewa']/1000000,1) ?>jt</div></div>
                        <div class="rev-item rev-tiket"><div class="rev-lbl">Tiket</div><div class="rev-val">Rp <?= number_format($a['revenue_tiket']/1000000,1) ?>jt</div></div>
                        <div class="rev-item rev-sponsor"><div class="rev-lbl">Sponsor</div><div class="rev-val">Rp <?= number_format($a['revenue_sponsor']/1000000,1) ?>jt</div></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="lbl" style="font-size:10px;opacity:.5;text-transform:uppercase;margin-bottom:.4rem">Traffic Impact</div>
                    <div style="display:flex;gap:6px;align-items:flex-end;height:60px">
                        <?php foreach ([['Sblm',$hB,'rgba(255,255,255,.15)'],['Event',$hD,'var(--accent)'],['Stlh',$hA,'rgba(255,255,255,.28)']] as [$lbl,$h,$clr]): ?>
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%">
                            <div style="width:100%;height:<?= $h ?>%;border-radius:3px 3px 0 0;background:<?= $clr ?>"></div>
                            <div style="font-size:9px;opacity:.5;margin-top:2px"><?= $lbl ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="font-size:10px;color:var(--accent);margin-top:4px;font-weight:600">+<?= $trafficLift ?>% lift</div>
                </div>
                <div class="col-md-2">
                    <div class="lbl" style="font-size:10px;opacity:.5;text-transform:uppercase;margin-bottom:.4rem">Kepuasan</div>
                    <div class="star-row">
                        <?= str_repeat('★',round($a['rating_kepuasan'])).str_repeat('☆',5-round($a['rating_kepuasan'])) ?>
                        <span style="font-size:1.05rem;color:var(--text-accent);margin-left:4px"><?= $a['rating_kepuasan'] ?></span>
                    </div>
                    <div style="font-size:10px;margin-top:4px;opacity:.6">Vendor: <span style="color:#f59e0b"><?= str_repeat('★',round($a['rating_vendor'])) ?></span> <?= $a['rating_vendor'] ?></div>
                </div>
            </div>
            <?php if (!empty($a['catatan'])): ?>
            <div class="mt-3 p-2 rounded" style="background:rgba(255,255,255,.04);font-size:var(--caption);border-left:3px solid var(--secondary)">
                <i class="bi bi-journal-text me-1" style="color:var(--secondary)"></i>
                <strong>Catatan:</strong> <?= $a['catatan'] ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>
<script>
const chartOpts = {
    responsive: true,
    plugins: { legend: { labels: { color:'#F5F7FA', font:{family:'Poppins',size:11} } } },
    scales: {
        x: { ticks:{color:'rgba(245,247,250,.5)',font:{family:'Poppins',size:10}}, grid:{color:'rgba(255,255,255,.05)'} },
        y: { ticks:{color:'rgba(245,247,250,.5)',font:{family:'Poppins',size:10}}, grid:{color:'rgba(255,255,255,.05)'} }
    }
};
<?php if (!empty($analytics)): ?>
new Chart(document.getElementById('chartRevenue'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            { label:'Sewa',   data:<?= json_encode(array_map(fn($v)=>round($v/1000000,1),$chartRevenueSewa)) ?>,   backgroundColor:'rgba(22,126,128,.7)', borderRadius:4 },
            { label:'Tiket',  data:<?= json_encode(array_map(fn($v)=>round($v/1000000,1),$chartRevenueTiket)) ?>,  backgroundColor:'rgba(0,212,216,.6)',  borderRadius:4 },
            { label:'Sponsor',data:<?= json_encode(array_map(fn($v)=>round($v/1000000,1),$chartRevenueSponsor)) ?>,backgroundColor:'rgba(255,182,42,.7)', borderRadius:4 },
        ]
    },
    options: chartOpts
});
new Chart(document.getElementById('chartVisitors'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            { label:'Target', data:<?= json_encode($chartTargets) ?>,   backgroundColor:'rgba(255,255,255,.12)', borderRadius:4 },
            { label:'Aktual', data:<?= json_encode($chartVisitors) ?>,  backgroundColor:'rgba(0,212,216,.7)',    borderRadius:4 },
        ]
    },
    options: chartOpts
});
<?php endif; ?>
</script>
</body>
</html>