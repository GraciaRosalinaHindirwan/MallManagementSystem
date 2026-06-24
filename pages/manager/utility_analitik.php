<?php
/**
 * PBI-M04-01-04
 * Manajer: Analitik konsumsi energi untuk optimasi efisiensi operasional
 */
session_start();
require_once __DIR__ . '/../../config/konek.php';

// ── Query data ───────────────────────────────────────────────────────────────
$per_tipe_q = $conn->query("
    SELECT utility_type,
           COUNT(*) jml_meter,
           SUM(current_reading-previous_reading) total_konsumsi,
           SUM((current_reading-previous_reading)*tarif_per_unit) total_biaya
    FROM 04_utility_meters WHERE status='active'
    GROUP BY utility_type ORDER BY total_biaya DESC
");
$per_tipe = [];
while ($r = $per_tipe_q->fetch_assoc()) $per_tipe[] = $r;

$tren_q = $conn->query("
    SELECT DATE(l.reading_date) AS tgl, m.utility_type, SUM(l.reading_value) AS total_value
    FROM 04_utility_meter_logs l
    JOIN 04_utility_meters m ON l.id_meter=m.id_meter
    WHERE l.reading_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(l.reading_date), m.utility_type ORDER BY tgl ASC
");
$tren_raw = [];
while ($r = $tren_q->fetch_assoc()) $tren_raw[$r['tgl']][$r['utility_type']] = (float)$r['total_value'];
$tren_labels  = array_keys($tren_raw);
$tren_listrik = array_map(fn($d) => $tren_raw[$d]['listrik']  ?? 0, $tren_labels);
$tren_air     = array_map(fn($d) => $tren_raw[$d]['air']      ?? 0, $tren_labels);
$tren_gas     = array_map(fn($d) => $tren_raw[$d]['gas']      ?? 0, $tren_labels);

$top_q = $conn->query("
    SELECT t.tenant_name, u.unit_code, m.utility_type,
           ROUND(m.current_reading-m.previous_reading,2) AS konsumsi,
           ROUND((m.current_reading-m.previous_reading)*m.tarif_per_unit,0) AS biaya
    FROM 04_utility_meters m
    JOIN 01_units u ON m.unit_id=u.id_units
    JOIN 02_tenants t ON u.tenant_id=t.id_tenant
    ORDER BY konsumsi DESC LIMIT 8
");
$inv_stat = $conn->query("
    SELECT SUM(total) total_tagihan,
           SUM(CASE WHEN status='paid'  THEN total ELSE 0 END) total_lunas,
           SUM(CASE WHEN status IN('draft','terbit') THEN total ELSE 0 END) outstanding,
           SUM(CASE WHEN status='overdue' THEN total ELSE 0 END) total_overdue,
           COUNT(*) jml_invoice,
           SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) jml_lunas
    FROM 04_invoice_utilitas
")->fetch_assoc();

$utilColor = ['listrik'=>'#f59e0b','air'=>'#3b82f6','gas'=>'#ef4444','internet'=>'#8b5cf6','ac_central'=>'#06b6d4'];
$utilIcon  = ['listrik'=>'⚡','air'=>'💧','gas'=>'🔥','internet'=>'📶','ac_central'=>'❄️'];

// ── Chart data ───────────────────────────────────────────────────────────────
$chart_labels   = array_map(fn($t) => ucfirst($t['utility_type']), $per_tipe);
$chart_biaya    = array_map(fn($t) => (float)$t['total_biaya'], $per_tipe);
$chart_konsumsi = array_map(fn($t) => (float)$t['total_konsumsi'], $per_tipe);
$chart_tipe_colors = array_map(fn($t) => $utilColor[$t['utility_type']] ?? '#6b7280', $per_tipe);

// ── Variabel template ────────────────────────────────────────────────────────
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

ob_start();
?>

<!-- ── KPI Cards ── -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="background:linear-gradient(135deg,#0B376D,#167E80);border:none;">
        <div class="stat-icon" style="background:rgba(0,212,216,.2);color:var(--accent,#00D4D8);">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div class="stat-info">
            <h3>Rp <?= number_format($inv_stat['total_tagihan'],0,',','.') ?></h3>
            <p>Total Tagihan · <?= $inv_stat['jml_invoice'] ?> invoice</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#064e3b,#065f46);border:none;">
        <div class="stat-icon" style="background:rgba(34,197,94,.2);color:#22c55e;">
            <i class="fa-solid fa-check-double"></i>
        </div>
        <div class="stat-info">
            <h3>Rp <?= number_format($inv_stat['total_lunas'],0,',','.') ?></h3>
            <p>Terlunasi · <?= $inv_stat['jml_lunas'] ?> invoice</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#78350f,#92400e);border:none;">
        <div class="stat-icon" style="background:rgba(245,158,11,.2);color:#f59e0b;">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
        <div class="stat-info">
            <h3>Rp <?= number_format($inv_stat['outstanding'],0,',','.') ?></h3>
            <p>Outstanding</p>
        </div>
    </div>
    <div class="stat-card" style="background:linear-gradient(135deg,#7f1d1d,#991b1b);border:none;">
        <div class="stat-icon" style="background:rgba(239,68,68,.2);color:#ef4444;">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div class="stat-info">
            <h3>Rp <?= number_format($inv_stat['total_overdue'],0,',','.') ?></h3>
            <p>Overdue</p>
        </div>
    </div>
</div>

<!-- ── Charts Row ── -->
<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;margin-bottom:24px;">
    <!-- Donut -->
    <div class="card">
        <h3 style="margin:0 0 16px;font-size:.9rem;font-weight:700;color:var(--text);">
            <i class="fa-solid fa-chart-pie" style="color:var(--accent,#00D4D8);margin-right:6px;"></i>
            Biaya per Tipe Utilitas
        </h3>
        <canvas id="chartDonut" style="max-height:200px;"></canvas>
        <div style="margin-top:14px;display:flex;flex-direction:column;gap:6px;">
            <?php foreach ($per_tipe as $pt): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:.8rem;padding:5px 0;border-bottom:1px solid rgba(255,255,255,.06);">
                <span><?= $utilIcon[$pt['utility_type']] ?? '🔌' ?> <?= ucfirst($pt['utility_type']) ?></span>
                <span style="font-weight:700;color:var(--accent,#00D4D8);">
                    Rp <?= number_format($pt['total_biaya'],0,',','.') ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Line tren -->
    <div class="card">
        <h3 style="margin:0 0 16px;font-size:.9rem;font-weight:700;color:var(--text);">
            <i class="fa-solid fa-chart-line" style="color:var(--accent,#00D4D8);margin-right:6px;"></i>
            Tren Pembacaan Meter (30 Hari Terakhir)
        </h3>
        <?php if (empty($tren_labels)): ?>
        <div style="display:flex;align-items:center;justify-content:center;height:200px;color:rgba(245,247,250,.3);flex-direction:column;gap:8px;font-size:.85rem;">
            <i class="fa-solid fa-chart-line" style="font-size:2.5rem;opacity:.2;"></i>
            Belum ada data log dalam 30 hari terakhir.<br>
            <small>Tambahkan pencatatan meter untuk melihat tren.</small>
        </div>
        <?php else: ?>
        <canvas id="chartTren" style="max-height:230px;"></canvas>
        <?php endif; ?>
    </div>
</div>

<!-- ── Bar Konsumsi per Tipe ── -->
<div class="card" style="margin-bottom:24px;">
    <h3 style="margin:0 0 16px;font-size:.9rem;font-weight:700;color:var(--text);">
        <i class="fa-solid fa-chart-bar" style="color:var(--accent,#00D4D8);margin-right:6px;"></i>
        Perbandingan Total Konsumsi per Tipe Utilitas
    </h3>
    <canvas id="chartBar" style="max-height:220px;"></canvas>
</div>

<!-- ── Ranking Tenant ── -->
<div class="card" style="padding:0;overflow:hidden;">
    <div style="padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.07);">
        <h3 style="margin:0;font-size:.9rem;font-weight:700;color:var(--text);">
            <i class="fa-solid fa-ranking-star" style="color:#f59e0b;margin-right:6px;"></i>
            Ranking Konsumsi Tertinggi per Meter
        </h3>
    </div>
    <div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Rank</th><th>Tenant</th><th>Unit</th><th>Utilitas</th>
            <th style="text-align:right;">Konsumsi</th>
            <th style="text-align:right;">Est. Biaya (Rp)</th>
        </tr>
        </thead>
        <tbody>
        <?php $rank=1; while ($top = $top_q->fetch_assoc()):
            $medal = match($rank){1=>'🥇',2=>'🥈',3=>'🥉',default=>$rank};
        ?>
        <tr style="<?= $rank<=3?'background:rgba(245,158,11,.04);':'' ?>">
            <td style="font-size:1.2rem;font-weight:700;"><?= $medal ?></td>
            <td><strong><?= htmlspecialchars($top['tenant_name']) ?></strong></td>
            <td><?= htmlspecialchars($top['unit_code']) ?></td>
            <td><?= $utilIcon[$top['utility_type']] ?? '🔌' ?> <?= ucfirst($top['utility_type']) ?></td>
            <td style="text-align:right;font-weight:700;"><?= number_format($top['konsumsi'],2) ?></td>
            <td style="text-align:right;font-weight:700;color:var(--accent,#00D4D8);">
                <?= number_format($top['biaya'],0,',','.') ?>
            </td>
        </tr>
        <?php $rank++; endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartDefaults = {
    color: 'rgba(245,247,250,.7)',
    plugins: { legend: { labels: { color:'rgba(245,247,250,.6)', font:{size:11} } } },
    scales: {
        y:{ beginAtZero:true, grid:{color:'rgba(255,255,255,.06)'}, ticks:{color:'rgba(245,247,250,.45)'} },
        x:{ grid:{display:false}, ticks:{color:'rgba(245,247,250,.45)'} }
    }
};

// Donut
new Chart(document.getElementById('chartDonut'),{
    type:'doughnut',
    data:{
        labels:<?= json_encode($chart_labels) ?>,
        datasets:[{
            data:<?= json_encode(array_map('floatval',$chart_biaya)) ?>,
            backgroundColor:<?= json_encode($chart_tipe_colors) ?>,
            borderWidth:3, borderColor:'#082A53'
        }]
    },
    options:{responsive:true,cutout:'65%',
        plugins:{
            legend:{position:'bottom',labels:{color:'rgba(245,247,250,.6)',font:{size:11}}},
            tooltip:{callbacks:{label:c=>' Rp '+c.parsed.toLocaleString('id-ID')}}
        }
    }
});

// Bar
new Chart(document.getElementById('chartBar'),{
    type:'bar',
    data:{
        labels:<?= json_encode($chart_labels) ?>,
        datasets:[{
            label:'Total Konsumsi',
            data:<?= json_encode(array_map('floatval',$chart_konsumsi)) ?>,
            backgroundColor:<?= json_encode(array_map(fn($c)=>$c.'99',$chart_tipe_colors)) ?>,
            borderRadius:8, borderSkipped:false
        }]
    },
    options:{responsive:true,plugins:{legend:{display:false}},...chartDefaults}
});

<?php if(!empty($tren_labels)): ?>
// Line tren
new Chart(document.getElementById('chartTren'),{
    type:'line',
    data:{
        labels:<?= json_encode($tren_labels) ?>,
        datasets:[
            {label:'⚡ Listrik',data:<?= json_encode($tren_listrik) ?>,borderColor:'#f59e0b',backgroundColor:'rgba(245,158,11,.12)',tension:.4,fill:true,pointRadius:3},
            {label:'💧 Air',    data:<?= json_encode($tren_air) ?>,    borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,.12)',tension:.4,fill:true,pointRadius:3},
            {label:'🔥 Gas',    data:<?= json_encode($tren_gas) ?>,    borderColor:'#ef4444',backgroundColor:'rgba(239,68,68,.1)',  tension:.4,fill:true,pointRadius:3}
        ]
    },
    options:{responsive:true,
        plugins:{legend:{position:'top',labels:{color:'rgba(245,247,250,.6)'}}},
        scales:{
            y:{beginAtZero:true,grid:{color:'rgba(255,255,255,.06)'},ticks:{color:'rgba(245,247,250,.45)'}},
            x:{grid:{display:false},ticks:{maxRotation:45,color:'rgba(245,247,250,.45)',font:{size:10}}}
        }
    }
});
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
$conn->close();
require_once dirname(__DIR__, 2) . '/includes/08_nav_template.php';
?>
