<?php
require_once '../../config/konek.php';
require_once __DIR__ . '/../../public/auth/checkSession.php';

// =====================================================
// 1. OCCUPANCY RATE (REAL-TIME)
// =====================================================
$sql_units = "SELECT 
                COUNT(*) as total_units,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_units
              FROM `01_units`";
$result_units = $conn->query($sql_units);
$units = $result_units->fetch_assoc();

$total_units = $units['total_units'] ?? 0;
$occupied_units = $units['occupied_units'] ?? 0;
$occupancy_rate = ($total_units > 0) ? ($occupied_units / $total_units) * 100 : 0;
$occupancy_rate = round($occupancy_rate, 2);

// =====================================================
// 2. REVENUE HARI INI (REAL-TIME)
// =====================================================
$sql_revenue_today = "SELECT COALESCE(SUM(total_amount), 0) as total 
                      FROM `06_invoices` 
                      WHERE status = 'Lunas' 
                      AND DATE(created_at) = CURDATE()";
$result_revenue_today = $conn->query($sql_revenue_today);
$revenue_today = $result_revenue_today->fetch_assoc()['total'];

// =====================================================
// 3. REVENUE BULAN INI (REAL-TIME) - Untuk Breakdown
// =====================================================
$year = date('Y');

// 3a. TENANT REVENUE (Bulan Ini)
$sql_tenant_revenue = "SELECT COALESCE(SUM(total_amount), 0) as total 
                       FROM `06_invoices` 
                       WHERE status = 'Lunas' 
                       AND MONTH(created_at) = MONTH(CURDATE())
                       AND YEAR(created_at) = '$year'";
$result_tenant_revenue = $conn->query($sql_tenant_revenue);
$tenant_revenue = $result_tenant_revenue->fetch_assoc()['total'];

// 3b. EVENT REVENUE (Bulan Ini)
$sql_event_revenue = "SELECT 
                        COALESCE((SELECT SUM(et.pendapatan) 
                                  FROM `04_event_tiket` et
                                  JOIN `04_event_booking` eb ON et.id_booking = eb.id_booking
                                  WHERE MONTH(eb.tanggal_selesai) = MONTH(CURDATE())
                                  AND YEAR(eb.tanggal_selesai) = '$year'), 0) +
                        COALESCE((SELECT SUM(sp.nilai) 
                                  FROM `04_event_sponsorship` sp
                                  JOIN `04_event_booking` eb ON sp.id_booking = eb.id_booking
                                  WHERE sp.status_bayar = 'paid' 
                                  AND MONTH(eb.tanggal_selesai) = MONTH(CURDATE())
                                  AND YEAR(eb.tanggal_selesai) = '$year'), 0) as total";
$result_event_revenue = $conn->query($sql_event_revenue);
$event_revenue = $result_event_revenue->fetch_assoc()['total'];

// 3c. PARKING REVENUE (Bulan Ini)
$sql_parking_revenue = "SELECT COALESCE(SUM(total_revenue), 0) as total 
                        FROM `06_daily_parking_summary`
                        WHERE MONTH(created_at) = MONTH(CURDATE())
                        AND YEAR(created_at) = '$year'";
$result_parking_revenue = $conn->query($sql_parking_revenue);
$parking_revenue = $result_parking_revenue->fetch_assoc()['total'];

// 3d. IKLAN REVENUE (Bulan Ini)
$sql_ads_revenue = "SELECT COALESCE(SUM(monthly_fee), 0) as total 
                    FROM `06_ad_contracts` 
                    WHERE billing_status = 'paid' 
                    AND MONTH(created_at) = MONTH(CURDATE())
                    AND YEAR(created_at) = '$year'";
$result_ads_revenue = $conn->query($sql_ads_revenue);
$ads_revenue = $result_ads_revenue->fetch_assoc()['total'];

// 3e. TOTAL REVENUE BULAN INI
$total_revenue_month = $tenant_revenue + $event_revenue + $parking_revenue + $ads_revenue;

// =====================================================
// 4. EVENT HARI INI (REAL-TIME)
// =====================================================
$sql_events_today = "SELECT 
                        eb.nama_event,
                        eb.tanggal_mulai,
                        eb.tanggal_selesai,
                        COALESCE(SUM(et.pendapatan), 0) as revenue,
                        COALESCE(SUM(et.terjual), 0) as sold
                    FROM `04_event_booking` eb
                    LEFT JOIN `04_event_tiket` et ON eb.id_booking = et.id_booking
                    WHERE (eb.status = 'approved' OR eb.status = 'completed')
                    AND CURDATE() BETWEEN eb.tanggal_mulai AND eb.tanggal_selesai
                    GROUP BY eb.id_booking
                    ORDER BY eb.tanggal_mulai DESC
                    LIMIT 3";

$result_events_today = $conn->query($sql_events_today);
$events_today = [];
while ($row = $result_events_today->fetch_assoc()) {
    $events_today[] = $row;
}

// Total Event Hari Ini
$sql_event_count_today = "SELECT COUNT(*) as total 
                          FROM `04_event_booking` 
                          WHERE (status = 'approved' OR status = 'completed')
                          AND CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai";
$result_event_count = $conn->query($sql_event_count_today);
$total_events_today = $result_event_count->fetch_assoc()['total'] ?? 0;

// =====================================================
// 5. MAINTENANCE (REAL-TIME)
// =====================================================
$sql_maintenance = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN work_status = 'Completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN work_status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                        SUM(CASE WHEN work_status = 'Assigned' THEN 1 ELSE 0 END) as assigned
                    FROM `03_work_orders`";
$result_maintenance = $conn->query($sql_maintenance);
$maintenance_stats = $result_maintenance->fetch_assoc();

// =====================================================
// 6. TOP 5 TENANT (BULAN INI)
// =====================================================
$sql_top_tenant = "SELECT 
                        t.tenant_name,
                        tc.name as category,
                        COALESCE(SUM(i.total_amount), 0) as revenue
                    FROM `02_tenants` t
                    LEFT JOIN `01_tenant_categories` tc ON t.id_category = tc.id_tenant_categories
                    LEFT JOIN `06_invoices` i ON t.id_tenant = i.tenant_id AND i.status = 'Lunas'
                    WHERE t.status = 'Active'
                    AND MONTH(i.created_at) = MONTH(CURDATE())
                    AND YEAR(i.created_at) = '$year'
                    GROUP BY t.id_tenant
                    ORDER BY revenue DESC
                    LIMIT 5";

$result_top_tenant = $conn->query($sql_top_tenant);
$top_tenants = [];
$tenant_labels = [];
$tenant_data = [];
while ($row = $result_top_tenant->fetch_assoc()) {
    $top_tenants[] = $row;
    $tenant_labels[] = $row['tenant_name'];
    $tenant_data[] = $row['revenue'];
}

// =====================================================
// 7. TAMPILKAN DASHBOARD
// =====================================================
$department_name = "BI, Workflow, and Notification";
$page_title = "Dashboard KPI";
$user_name = "Manager";

$menu_items = [
    ['icon' => 'fa-solid fa-gauge', 'label' => 'Dashboard KPI', 'link' => '08_dashboard.php', 'active_page' => '08_dashboard'],
    ['icon' => 'fa-solid fa-chart-line', 'label' => 'Laporan', 'link' => '08_laporan.php', 'active_page' => '08_laporan'],
    ['icon' => 'fa-solid fa-check-circle', 'label' => 'Approval', 'link' => '08_approval.php', 'active_page' => '08_approval'],
    ['icon' => 'fa-solid fa-bell', 'label' => 'Notifikasi', 'link' => '08_notifikasi.php', 'active_page' => '08_notifikasi'],
];

ob_start();
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="dashboard-container">
    <!-- ROW 1: Cards Utama -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-building"></i></div>
            <div class="stat-info">
                <h3><?php echo $occupancy_rate; ?>%</h3>
                <p>Occupancy Rate</p>
                <small><?php echo $occupied_units; ?> dari <?php echo $total_units; ?> unit terisi</small>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fa-solid fa-money-bill"></i></div>
            <div class="stat-info">
                <h3>Rp <?php echo number_format($revenue_today / 1000000, 1); ?>M</h3>
                <p>Revenue Hari Ini</p>
                <small>Update <?php echo date('H:i'); ?></small>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fa-solid fa-calendar"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_events_today; ?></h3>
                <p>Event Hari Ini</p>
                <small>Event berlangsung</small>
            </div>
        </div>
        <div class="stat-card danger">
            <div class="stat-icon"><i class="fa-solid fa-wrench"></i></div>
            <div class="stat-info">
                <h3><?php echo $maintenance_stats['total']; ?></h3>
                <p>Maintenance</p>
                <small><?php echo $maintenance_stats['completed']; ?> selesai</small>
            </div>
        </div>
    </div>

    <!-- ROW 2: Grafik -->
    <div class="charts-row">
        <div class="chart-box">
            <div class="card-chart">
                <h5 class="card-title">Tenant Performance (Top 5 - Bulan Ini)</h5>
                <div class="chart-wrapper">
                    <canvas id="tenantChart"></canvas>
                </div>
            </div>
        </div>
        <div class="chart-box">
            <div class="card-chart">
                <h5 class="card-title">Revenue Breakdown (Bulan Ini)</h5>
                <div class="chart-wrapper pie-wrapper">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 3: Event & Maintenance -->
    <div class="row mt-4">
        <div class="col-md-6 mb-4">
            <div class="card-table h-100">
                <h5 class="card-title"><i class="fa-solid fa-calendar-check"></i> Event Hari Ini</h5>
                <?php if (count($events_today) > 0): ?>
                    <div class="event-list">
                        <?php foreach ($events_today as $event): ?>
                            <div class="event-item">
                                <div class="event-info">
                                    <strong><?php echo $event['nama_event']; ?></strong>
                                    <span class="event-date"><?php echo date('d M Y', strtotime($event['tanggal_mulai'])); ?></span>
                                </div>
                                <div class="event-stats">
                                    <span class="event-revenue">Rp <?php echo number_format($event['revenue'] / 1000000, 1); ?>M</span>
                                    <span class="event-sold"><?php echo number_format($event['sold']); ?> tiket</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">Tidak ada event hari ini</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card-table h-100">
                <h5 class="card-title"><i class="fa-solid fa-clipboard-list"></i> Maintenance Overview</h5>
                <div class="maintenance-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total Tickets</span>
                        <span class="stat-value"><?php echo $maintenance_stats['total']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Completed</span>
                        <span class="stat-value text-success"><?php echo $maintenance_stats['completed']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">In Progress</span>
                        <span class="stat-value text-warning"><?php echo $maintenance_stats['in_progress']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Assigned</span>
                        <span class="stat-value text-primary"><?php echo $maintenance_stats['assigned']; ?></span>
                    </div>
                </div>
                <div class="progress mt-3">
                    <?php
                    $completion_rate = ($maintenance_stats['total'] > 0)
                        ? round(($maintenance_stats['completed'] / $maintenance_stats['total']) * 100, 1)
                        : 0;
                    ?>
                    <div class="progress-bar bg-success" style="width: <?php echo $completion_rate; ?>%;">
                        <?php echo $completion_rate; ?>%
                    </div>
                </div>
                <small class="text-muted">Completion Rate</small>
            </div>
        </div>
    </div>
</div>

<style>
    /* =============================================
   DASHBOARD STYLES - DESIGN SYSTEM
   ============================================= */

    .dashboard-container {
        padding: 0 5px;
    }

    /* ---- STATS GRID ---- */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: var(--primary, #0B376D);
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        border-left: 4px solid var(--text-accent, #FFB62A);
        transition: transform 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-4px);
    }

    .stat-card.success {
        border-left-color: var(--success, #22C55E);
    }

    .stat-card.warning {
        border-left-color: var(--text-accent, #FFB62A);
    }

    .stat-card.danger {
        border-left-color: var(--danger, #EF4444);
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: rgba(255, 182, 42, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: var(--text-accent, #FFB62A);
        flex-shrink: 0;
    }

    .stat-card.success .stat-icon {
        background: rgba(34, 197, 94, 0.15);
        color: var(--success, #22C55E);
    }

    .stat-card.warning .stat-icon {
        background: rgba(255, 182, 42, 0.15);
        color: var(--text-accent, #FFB62A);
    }

    .stat-card.danger .stat-icon {
        background: rgba(239, 68, 68, 0.15);
        color: var(--danger, #EF4444);
    }

    .stat-info h3 {
        font-size: var(--h2, 24px);
        font-weight: 700;
        margin: 0;
        color: var(--text, #F5F7FA);
    }

    .stat-info p {
        font-size: var(--label, 14px);
        margin: 0;
        color: rgba(245, 247, 250, 0.6);
    }

    .stat-info small {
        font-size: var(--caption, 12px);
        color: rgba(245, 247, 250, 0.4);
    }

    /* ---- CHARTS ROW ---- */
    .charts-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    .chart-box {
        min-width: 0;
    }

    .card-chart {
        background: var(--primary, #0B376D);
        border-radius: 12px;
        padding: 16px 20px;
        height: 100%;
    }

    .card-title {
        font-size: var(--label, 14px);
        font-weight: 600;
        color: var(--text, #F5F7FA);
        margin-bottom: 12px;
    }

    .card-title i {
        margin-right: 6px;
        color: var(--text-accent, #FFB62A);
    }

    .chart-wrapper {
        position: relative;
        width: 100%;
        max-height: 180px;
    }

    .chart-wrapper canvas {
        width: 100% !important;
        height: 100% !important;
        max-height: 180px;
    }

    .pie-wrapper {
        max-height: 150px;
    }

    .pie-wrapper canvas {
        max-height: 150px;
    }

    /* ---- CARD TABLE ---- */
    .card-table {
        background: var(--primary, #0B376D);
        border-radius: 12px;
        padding: 16px 20px;
        height: 100%;
    }

    /* ---- EVENT LIST ---- */
    .event-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .event-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        transition: background 0.2s;
    }

    .event-item:hover {
        background: rgba(255, 255, 255, 0.08);
    }

    .event-info strong {
        color: var(--text, #F5F7FA);
        font-size: var(--label, 14px);
    }

    .event-date {
        font-size: var(--caption, 12px);
        color: rgba(245, 247, 250, 0.5);
    }

    .event-revenue {
        font-size: var(--label, 14px);
        font-weight: 600;
        color: var(--text-accent, #FFB62A);
        display: block;
    }

    .event-sold {
        font-size: var(--caption, 12px);
        color: rgba(245, 247, 250, 0.5);
    }

    /* ---- MAINTENANCE STATS ---- */
    .maintenance-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 12px;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        padding: 6px 12px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 6px;
    }

    .stat-label {
        font-size: var(--caption, 12px);
        color: rgba(245, 247, 250, 0.6);
    }

    .stat-value {
        font-size: var(--label, 14px);
        font-weight: 600;
        color: var(--text, #F5F7FA);
    }

    .stat-value.text-success {
        color: var(--success, #22C55E);
    }

    .stat-value.text-warning {
        color: var(--text-accent, #FFB62A);
    }

    .stat-value.text-primary {
        color: var(--accent, #00D4D8);
    }

    /* ---- RESPONSIVE ---- */
    @media (max-width: 992px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .charts-row {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 768px) {
        .charts-row {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .chart-wrapper {
            max-height: 200px;
        }

        .pie-wrapper {
            max-height: 180px;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
    }

    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .stat-card {
            padding: 12px 16px;
        }

        .stat-icon {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }

        .stat-info h3 {
            font-size: var(--subheading, 20px);
        }

        .card-chart,
        .card-table {
            padding: 12px 16px;
        }

        .event-item {
            flex-direction: column;
            text-align: center;
            gap: 4px;
        }

        .event-stats {
            text-align: center;
        }

        .chart-wrapper {
            max-height: 150px;
        }

        .pie-wrapper {
            max-height: 140px;
        }

        .maintenance-stats {
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
    }

    @media (max-width: 400px) {
        .maintenance-stats {
            grid-template-columns: 1fr;
        }

        .stat-card {
            flex-direction: column;
            text-align: center;
            padding: 12px;
        }

        .stat-info small {
            display: none;
        }
    }
</style>

<script>
    // =============================================
    // CHART: TENANT PERFORMANCE (Bar Chart)
    // =============================================
    const ctx1 = document.getElementById('tenantChart').getContext('2d');
    const tenantLabels = <?php echo json_encode($tenant_labels); ?>;
    const tenantData = <?php echo json_encode($tenant_data); ?>;

    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: tenantLabels,
            datasets: [{
                label: 'Revenue (Rp)',
                data: tenantData,
                backgroundColor: [
                    '#FFB62A', // text-accent
                    '#22C55E', // success
                    '#00D4D8', // accent
                    '#167E80', // secondary
                    '#EF4444' // danger
                ],
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'rgba(245,247,250,0.5)',
                        font: {
                            size: 10
                        },
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp' + (value / 1000000).toFixed(1) + 'M';
                            return 'Rp' + value;
                        }
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.05)'
                    }
                },
                x: {
                    ticks: {
                        color: 'rgba(245,247,250,0.5)',
                        font: {
                            size: 10
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // =============================================
    // CHART: REVENUE BREAKDOWN (Doughnut)
    // =============================================
    const ctx2 = document.getElementById('revenueChart').getContext('2d');
    const tenantRevenue = <?php echo $tenant_revenue; ?>;
    const eventRevenue = <?php echo $event_revenue; ?>;

    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Tenant Revenue', 'Event Revenue'],
            datasets: [{
                data: [tenantRevenue, eventRevenue],
                backgroundColor: [
                    '#FFB62A', // text-accent
                    '#22C55E' // success
                ],
                borderColor: ['#021F42', '#021F42'], // background
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: 'rgba(245,247,250,0.6)',
                        font: {
                            size: 10
                        },
                        padding: 12,
                        usePointStyle: true,
                        pointStyle: 'circle',
                    }
                }
            },
            cutout: '65%',
        }
    });
</script>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '/includes/08_nav_template.php';
?>