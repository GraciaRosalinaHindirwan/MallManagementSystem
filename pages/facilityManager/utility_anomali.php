<?php
/**
 * PBI-M04-01-02
 * Facility Manager: Deteksi anomali konsumsi utilitas
 */
session_start();
require_once __DIR__ . '/../../config/konek.php';

// ── Ambil data ───────────────────────────────────────────────────────────────
$meters_q = $conn->query("
    SELECT m.*, u.unit_code, t.tenant_name,
           ROUND(m.current_reading - m.previous_reading, 2) AS konsumsi,
           CASE
               WHEN m.threshold_max>0 AND (m.current_reading-m.previous_reading)>m.threshold_max THEN 'anomali'
               WHEN m.threshold_max>0 AND (m.current_reading-m.previous_reading)>(m.threshold_max*0.8) THEN 'waspada'
               ELSE 'normal'
           END AS status_anomali,
           ROUND(CASE WHEN m.threshold_max>0
               THEN ((m.current_reading-m.previous_reading)/m.threshold_max)*100
               ELSE 0 END, 1) AS pct_threshold
    FROM 04_utility_meters m
    JOIN 01_units u ON m.unit_id=u.id_units
    LEFT JOIN 02_tenants t ON u.tenant_id=t.id_tenant
    ORDER BY pct_threshold DESC
");
$all_meters = [];
while ($r = $meters_q->fetch_assoc()) $all_meters[] = $r;

$total   = count($all_meters);
$anomali = count(array_filter($all_meters, fn($m) => $m['status_anomali']==='anomali'));
$waspada = count(array_filter($all_meters, fn($m) => $m['status_anomali']==='waspada'));
$normal  = $total - $anomali - $waspada;

$chart_labels    = array_map(fn($m) => $m['unit_code'].' ('.ucfirst($m['utility_type']).')', $all_meters);
$chart_konsumsi  = array_map(fn($m) => (float)$m['konsumsi'], $all_meters);
$chart_threshold = array_map(fn($m) => (float)$m['threshold_max'], $all_meters);
$chart_colors    = array_map(fn($m) =>
    $m['status_anomali']==='anomali' ? 'rgba(239,68,68,.75)' :
   ($m['status_anomali']==='waspada' ? 'rgba(245,158,11,.75)' : 'rgba(34,197,94,.75)'), $all_meters);

// ── Variabel template ────────────────────────────────────────────────────────
$department_name = 'Utility Management';
$page_title      = 'Deteksi Anomali Konsumsi';
$user_name       = $_SESSION['nama'] ?? ($_SESSION['username'] ?? 'Facility Manager');
$menu_items = [
    ['icon'=>'fa-solid fa-triangle-exclamation','label'=>'Deteksi Anomali',  'link'=>'../../pages/facilityManager/utility_anomali.php', 'active_page'=>'utility_anomali']
];

ob_start();
?>

<!-- ── Stats Grid ── -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left-color:#3b82f6;">
        <div class="stat-icon" style="background:rgba(59,130,246,.15);color:#3b82f6;font-size:1.3rem;">
            <i class="fa-solid fa-gauge-high"></i>
        </div>
        <div class="stat-info">
            <h3><?= $total ?></h3>
            <p>Total Meter</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color:#22c55e;">
        <div class="stat-icon" style="background:rgba(34,197,94,.15);color:#22c55e;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-info">
            <h3><?= $normal ?></h3>
            <p>Normal</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color:#f59e0b;">
        <div class="stat-icon" style="background:rgba(245,158,11,.15);color:#f59e0b;">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div class="stat-info">
            <h3><?= $waspada ?></h3>
            <p>Waspada (>80%)</p>
        </div>
    </div>
    <div class="stat-card" style="border-left-color:#ef4444;">
        <div class="stat-icon" style="background:rgba(239,68,68,.15);color:#ef4444;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
            <h3 style="color:<?= $anomali>0?'#ef4444':'' ?>;"><?= $anomali ?></h3>
            <p>Anomali <?= $anomali>0 ? '⚠ Perlu tindakan!' : '' ?></p>
        </div>
    </div>
</div>

<!-- ── Chart ── -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin:0 0 16px;font-size:.95rem;font-weight:700;color:var(--text);">
        <i class="fa-solid fa-chart-bar" style="color:var(--accent,#00D4D8);margin-right:8px;"></i>
        Grafik Konsumsi vs Threshold
    </h3>
    <canvas id="chartAnomali" style="max-height:300px;"></canvas>
</div>

<!-- ── Tabel Detail ── -->
<h2 style="font-size:1rem;font-weight:700;margin-bottom:14px;color:var(--text);">
    <i class="fa-solid fa-list-check" style="color:var(--accent,#00D4D8);margin-right:8px;"></i>
    Detail Status Semua Meter
</h2>
<div class="card" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Unit</th><th>Tenant</th><th>Utilitas</th>
            <th style="text-align:right;">Konsumsi</th>
            <th style="text-align:right;">Threshold</th>
            <th>% Pemakaian</th>
            <th style="text-align:center;">Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($all_meters as $m):
            $pct = $m['pct_threshold'];
            $barColor = match($m['status_anomali']) { 'anomali'=>'#ef4444','waspada'=>'#f59e0b',default=>'#22c55e' };
            $badgeCls = match($m['status_anomali']) { 'anomali'=>'badge-danger','waspada'=>'badge-warning',default=>'badge-success' };
            $label    = match($m['status_anomali']) { 'anomali'=>'🔴 Anomali','waspada'=>'⚠️ Waspada',default=>'✅ Normal' };
            $utilIcon = ['listrik'=>'fa-bolt','air'=>'fa-droplet','gas'=>'fa-fire-flame-curved','internet'=>'fa-wifi','ac_central'=>'fa-snowflake'];
        ?>
        <tr>
            <td><strong><?= htmlspecialchars($m['unit_code']) ?></strong></td>
            <td><?= htmlspecialchars($m['tenant_name'] ?? '-') ?></td>
            <td>
                <span class="badge badge-info">
                    <i class="fa-solid <?= $utilIcon[$m['utility_type']] ?? 'fa-circle' ?>" style="margin-right:4px;"></i>
                    <?= ucfirst($m['utility_type']) ?>
                </span>
            </td>
            <td style="text-align:right;font-weight:700;color:<?= $barColor ?>;">
                <?= $m['status_anomali']==='anomali' ? '<i class="fa-solid fa-triangle-exclamation"></i> ' : '' ?>
                <?= number_format($m['konsumsi'],2) ?>
            </td>
            <td style="text-align:right;color:rgba(245,247,250,.5);"><?= number_format($m['threshold_max'],2) ?></td>
            <td style="min-width:140px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="progress-wrap" style="flex:1;">
                        <div class="progress-bar" style="width:<?= min($pct,100) ?>%;background:<?= $barColor ?>;"></div>
                    </div>
                    <span style="font-size:.78rem;font-weight:700;color:<?= $barColor ?>;min-width:40px;"><?= $pct ?>%</span>
                </div>
            </td>
            <td style="text-align:center;">
                <span class="badge <?= $badgeCls ?>"><?= $label ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartAnomali').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            { label:'Konsumsi', data:<?= json_encode($chart_konsumsi) ?>,
              backgroundColor:<?= json_encode($chart_colors) ?>, borderRadius:6 },
            { label:'Threshold', data:<?= json_encode($chart_threshold) ?>,
              type:'line', borderColor:'rgba(239,68,68,.9)', backgroundColor:'transparent',
              borderWidth:2, borderDash:[6,3], pointRadius:4 }
        ]
    },
    options:{
        responsive:true,
        plugins:{
            legend:{position:'top',labels:{color:'rgba(245,247,250,.7)'}},
            tooltip:{callbacks:{label:c=>c.dataset.label+': '+c.parsed.y.toLocaleString('id-ID',{minimumFractionDigits:2})}}
        },
        scales:{
            y:{beginAtZero:true,grid:{color:'rgba(255,255,255,.06)'},ticks:{color:'rgba(245,247,250,.5)'}},
            x:{ticks:{maxRotation:45,color:'rgba(245,247,250,.5)',font:{size:11}},grid:{display:false}}
        }
    }
});
</script>

<?php
$content = ob_get_clean();
$conn->close();
require_once __DIR__ . '/../../includes/navbar.php';
?>
