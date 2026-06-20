<?php
require_once '../../config/08_conn.php';

// =====================================================
// 1. HITUNG OCCUPANCY RATE
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
// 2. HITUNG TOTAL REVENUE
// =====================================================
$sql_revenue = "SELECT 
                    SUM(CASE WHEN status = 'Lunas' THEN total_amount ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'Belum Bayar' THEN total_amount ELSE 0 END) as unpaid_revenue
                FROM `06_invoices`";
$result_revenue = $conn->query($sql_revenue);
$revenue_data = $result_revenue->fetch_assoc();
$total_revenue = $revenue_data['total_revenue'] ?? 0;
$unpaid_revenue = $revenue_data['unpaid_revenue'] ?? 0;

// =====================================================
// 3. TENANT PERFORMANCE (Top 5)
// =====================================================
$sql_tenant = "SELECT 
                    t.tenant_name,
                    tc.name as category,
                    COALESCE(SUM(i.total_amount), 0) as revenue
                FROM `02_tenants` t
                LEFT JOIN `01_tenant_categories` tc ON t.id_category = tc.id_tenant_categories
                LEFT JOIN `06_invoices` i ON t.id_tenant = i.tenant_id AND i.status = 'Lunas'
                WHERE t.status = 'Active'
                GROUP BY t.id_tenant
                ORDER BY revenue DESC
                LIMIT 5";

$result_tenant = $conn->query($sql_tenant);
$top_tenants = [];
$tenant_labels = [];
$tenant_data = [];
while ($row = $result_tenant->fetch_assoc()) {
    $top_tenants[] = $row;
    $tenant_labels[] = $row['tenant_name'];
    $tenant_data[] = $row['revenue'];
}

// =====================================================
// 4. EVENT PERFORMANCE
// =====================================================
$sql_events = "SELECT 
                    eb.nama_event,
                    eb.tanggal_mulai,
                    COALESCE(SUM(et.pendapatan), 0) as revenue,
                    COALESCE(SUM(et.terjual), 0) as sold
                FROM `04_event_booking` eb
                LEFT JOIN `04_event_tiket` et ON eb.id_booking = et.id_booking
                WHERE eb.status = 'approved' OR eb.status = 'completed'
                GROUP BY eb.id_booking
                ORDER BY eb.tanggal_mulai DESC
                LIMIT 3";

$result_events = $conn->query($sql_events);
$events = [];
while ($row = $result_events->fetch_assoc()) {
    $events[] = $row;
}

// Total event stats
$sql_event_stats = "SELECT 
                        COUNT(DISTINCT eb.id_booking) as total_events,
                        COALESCE(SUM(et.pendapatan), 0) as total_revenue,
                        COALESCE(SUM(et.terjual), 0) as total_participants
                    FROM `04_event_booking` eb
                    LEFT JOIN `04_event_tiket` et ON eb.id_booking = et.id_booking
                    WHERE eb.status = 'approved' OR eb.status = 'completed'";

$result_event_stats = $conn->query($sql_event_stats);
$event_stats = $result_event_stats->fetch_assoc();
$total_events = $event_stats['total_events'] ?? 0;
$total_event_revenue = $event_stats['total_revenue'] ?? 0;
$total_participants = $event_stats['total_participants'] ?? 0;

// =====================================================
// 5. MAINTENANCE STATS
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
// 6. SIMPAN KE KPI_SNAPSHOTS
// =====================================================
$period_type = 'daily';
$period_date = date('Y-m-d');
$top_tenants_str = '';
foreach ($top_tenants as $index => $tenant) {
    $top_tenants_str .= ($index + 1) . '.' . $tenant['tenant_name'] . ' (Rp ' . number_format($tenant['revenue'], 0, ',', '.') . '), ';
}
$top_tenants_str = rtrim($top_tenants_str, ', ');

$check_sql = "SELECT snapshot_id FROM `08_kpi_snapshots` WHERE period_type = '$period_type' AND period_date = '$period_date'";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows == 0) {
    $insert_sql = "INSERT INTO `08_kpi_snapshots` 
                   (period_type, period_date, occupancy_rate, total_revenue, top_tenants) 
                   VALUES 
                   ('$period_type', '$period_date', '$occupancy_rate', '$total_revenue', '$top_tenants_str')";
    $conn->query($insert_sql);
}

// =====================================================
// 7. TAMPILKAN DASHBOARD
// =====================================================
$department_name = "BI, Workflow, and Notification";
$page_title = "Dashboard KPI";
$user_name = "Manager";

$menu_items = [
    ['icon' => 'fa-solid fa-gauge', 'label' => 'Dashboard', 'link' => '08_dashboard.php', 'active_page' => '08_dashboard'],
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
                <small><?php echo $occupied_units; ?> dari <?php echo $total_units; ?> unit</small>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="fa-solid fa-money-bill"></i></div>
            <div class="stat-info">
                <h3>Rp <?php echo number_format($total_revenue / 1000000, 1); ?>M</h3>
                <p>Total Revenue</p>
                <small>+<?php echo number_format($unpaid_revenue / 1000000, 1); ?>M belum bayar</small>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fa-solid fa-calendar"></i></div>
            <div class="stat-info">
                <h3><?php echo $total_events; ?></h3>
                <p>Event</p>
                <small><?php echo number_format($total_participants); ?> peserta</small>
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
                <h5 class="card-title">Tenant Performance (Top 5)</h5>
                <div class="chart-wrapper">
                    <canvas id="tenantChart"></canvas>
                </div>
            </div>
        </div>
        <div class="chart-box">
            <div class="card-chart">
                <h5 class="card-title">Revenue Breakdown</h5>
                <div class="chart-wrapper pie-wrapper">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 3: Event & Maintenance (dengan jarak yang jelas) -->
    <div class="row mt-4">
        <div class="col-md-6 mb-4">
            <div class="card-table h-100">
                <h5 class="card-title"><i class="fa-solid fa-calendar-check"></i> Event Performance</h5>
                <?php if (count($events) > 0): ?>
                    <div class="event-list">
                        <?php foreach ($events as $event): ?>
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
                    <p class="text-muted text-center py-3">Belum ada data event</p>
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
    const eventRevenue = <?php echo $total_event_revenue; ?>;
    const tenantRevenue = <?php echo $total_revenue; ?>;

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