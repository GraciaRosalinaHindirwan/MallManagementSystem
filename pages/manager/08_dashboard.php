<?php
require_once '../../config/08_conn.php';

// =====================================================
// HITUNG OCCUPANCY RATE (dari dummy_units)
// =====================================================
$sql_units = "SELECT 
                COUNT(*) as total_units,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_units
              FROM dummy_units";
$result_units = $conn->query($sql_units);
$units = $result_units->fetch_assoc();

$total_units = $units['total_units'];
$occupied_units = $units['occupied_units'];
$occupancy_rate = ($total_units > 0) ? ($occupied_units / $total_units) * 100 : 0;
$occupancy_rate = round($occupancy_rate, 2);

// =====================================================
// HITUNG TOTAL REVENUE (dari dummy_transactions)
// =====================================================

// =====================================================
// 1. HITUNG TENANT REVENUE (dari dummy_transactions)
// =====================================================
$sql_tenant = "SELECT COALESCE(SUM(amount), 0) as total FROM dummy_transactions";
$result_tenant = $conn->query($sql_tenant);
$tenant_revenue = $result_tenant->fetch_assoc()['total'];

// =====================================================
// 2. HITUNG EVENT REVENUE (dari dummy_events)
// =====================================================
$sql_event = "SELECT COALESCE(SUM(revenue), 0) as total FROM dummy_events WHERE status = 'completed'";
$result_event = $conn->query($sql_event);
$event_revenue = $result_event->fetch_assoc()['total'];

// =====================================================
// 3. HITUNG PARKING REVENUE (dari dummy_parking)
// =====================================================
$sql_parking = "SELECT COALESCE(SUM(revenue), 0) as total FROM dummy_parking";
$result_parking = $conn->query($sql_parking);
$parking_revenue = $result_parking->fetch_assoc()['total'];

// =====================================================
// 4. HITUNG IKLAN REVENUE (dari dummy_ads)
// =====================================================
$sql_ads = "SELECT COALESCE(SUM(revenue), 0) as total FROM dummy_ads";
$result_ads = $conn->query($sql_ads);
$ads_revenue = $result_ads->fetch_assoc()['total'];

// =====================================================
// 5. HITUNG TOTAL SEMUA REVENUE
// =====================================================
$total_revenue = $tenant_revenue + $event_revenue + $parking_revenue + $ads_revenue;

// =====================================================
// 6. HITUNG PERSENTASE MASING-MASING
// =====================================================
$tenant_percent = ($total_revenue > 0) ? ($tenant_revenue / $total_revenue) * 100 : 0;
$event_percent = ($total_revenue > 0) ? ($event_revenue / $total_revenue) * 100 : 0;
$parking_percent = ($total_revenue > 0) ? ($parking_revenue / $total_revenue) * 100 : 0;
$ads_percent = ($total_revenue > 0) ? ($ads_revenue / $total_revenue) * 100 : 0;

// =====================================================
// HITUNG TOP TENANTS (dari dummy_tenants + dummy_transactions)
// =====================================================
$sql_top = "SELECT 
                t.tenant_name,
                SUM(tr.amount) as total_revenue
            FROM dummy_tenants t
            JOIN dummy_transactions tr ON t.tenant_id = tr.tenant_id
            GROUP BY t.tenant_id
            ORDER BY total_revenue DESC
            LIMIT 5";
$result_top = $conn->query($sql_top);

$top_tenants = [];
while ($row = $result_top->fetch_assoc()) {
    $top_tenants[] = $row['tenant_name'] . ' (Rp ' . number_format($row['total_revenue'], 0, ',', '.') . ')';
}
$top_tenants_str = implode(', ', $top_tenants);

// =====================================================
// SIMPAN KE kpi_snapshots (agar tidak hitung ulang terus)
// =====================================================
$period_type = 'daily';
$period_date = date('Y-m-d');

// Cek apakah sudah ada snapshot untuk hari ini
$check_sql = "SELECT snapshot_id FROM kpi_snapshots WHERE period_type = '$period_type' AND period_date = '$period_date'";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows == 0) {
    // Belum ada, insert baru
    $insert_sql = "INSERT INTO kpi_snapshots 
                   (period_type, period_date, occupancy_rate, total_revenue, top_tenants) 
                   VALUES 
                   ('$period_type', '$period_date', '$occupancy_rate', '$total_revenue', '$top_tenants_str')";
    $conn->query($insert_sql);
    $kpi = [
        'occupancy_rate' => $occupancy_rate,
        'total_revenue' => $total_revenue
    ];
} else {
    // Sudah ada, ambil dari snapshot (biar cepat)
    $sql_kpi = "SELECT occupancy_rate, total_revenue, total_visitors 
                FROM kpi_snapshots 
                WHERE period_type = '$period_type' 
                ORDER BY period_date DESC LIMIT 1";
    $result_kpi = $conn->query($sql_kpi);
    $kpi = $result_kpi->fetch_assoc();
}

// =====================================================
// EVENT PERFORMANCE (dari tabel dummy_events)
// =====================================================
$sql_events = "SELECT 
                event_id,
                event_name,
                revenue,
                participant_count,
                event_date,
                status
               FROM dummy_events 
               WHERE status = 'completed'
               ORDER BY event_date DESC";

$result_events = $conn->query($sql_events);

// Hitung total pendapatan event
$sql_total = "SELECT 
                SUM(revenue) as total_revenue, 
                SUM(participant_count) as total_participants,
                AVG(participant_count) as avg_participants,
                COUNT(*) as total_events
              FROM dummy_events 
              WHERE status = 'completed'";
$result_total = $conn->query($sql_total);
$total_data = $result_total->fetch_assoc();

// =====================================================
// TENANT DETAIL (setelah join_date dan unit.tenant_id ditambah)
// =====================================================
$sql_tenant_detail = "SELECT 
    t.tenant_id,
    t.tenant_name,
    t.category,
    t.join_date,
    DATEDIFF(NOW(), t.join_date) as days_as_tenant,
    COALESCE(SUM(tr.amount), 0) as total_revenue
FROM dummy_tenants t
LEFT JOIN dummy_transactions tr ON t.tenant_id = tr.tenant_id
GROUP BY t.tenant_id, t.tenant_name, t.category, t.join_date
ORDER BY total_revenue DESC";

$result_tenant_detail = $conn->query($sql_tenant_detail);

// =====================================================
// TAMPILKAN DI DASHBOARD
// =====================================================
ob_start();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card text-center p-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-muted">Occupancy Rate</h5>
                    <h2 class="display-4"><?php echo $kpi['occupancy_rate']; ?>%</h2>
                    <p class="text-muted">Terisi: <?php echo $occupied_units; ?> dari <?php echo $total_units; ?> unit</p>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card text-center p-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Revenue</h5>
                    <h2 class="display-4 text-accent">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></h2>
                    <p class="text-muted">Total pendapatan dari semua sumber</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Total Revenue Breakdown -->
    <div class="card mt-4 p-3 shadow-sm">
        <h5 class="mb-3 heading-breakdown-revenue">TOTAL REVENUE BREAKDOWN</h5>

        <div class="revenue-breakdown">
            <!-- Tenant Revenue -->
            <div class="revenue-item mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="total-revenue-detail"><strong>Tenant Revenue</strong></span>
                    <span class="total-revenue-digit">Rp <?php echo number_format($tenant_revenue, 0, ',', '.'); ?></span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill tenant-fill" style="width: <?php echo $tenant_percent; ?>%"></div>
                </div>
                <div class="d-flex justify-content-end mt-1">
                    <small class="text-muted"><?php echo round($tenant_percent, 1); ?>% dari total</small>
                </div>
            </div>

            <!-- Event Revenue -->
            <div class="revenue-item mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="total-revenue-detail"><strong>Event Revenue</strong></span>
                    <span class="total-revenue-digit">Rp <?php echo number_format($event_revenue, 0, ',', '.'); ?></span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill event-fill" style="width: <?php echo $event_percent; ?>%"></div>
                </div>
                <div class="d-flex justify-content-end mt-1">
                    <small class="text-muted"><?php echo round($event_percent, 1); ?>% dari total</small>
                </div>
            </div>

            <!-- Parking Revenue -->
            <div class="revenue-item mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="total-revenue-detail"><strong>Parking Revenue</strong></span>
                    <span class="total-revenue-digit">Rp <?php echo number_format($parking_revenue, 0, ',', '.'); ?></span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill parking-fill" style="width: <?php echo $parking_percent; ?>%"></div>
                </div>
                <div class="d-flex justify-content-end mt-1">
                    <small class="text-muted"><?php echo round($parking_percent, 1); ?>% dari total</small>
                </div>
            </div>

            <!-- Iklan Revenue -->
            <div class="revenue-item mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="total-revenue-detail"><strong>Iklan Revenue</strong></span>
                    <span class="total-revenue-digit">Rp <?php echo number_format($ads_revenue, 0, ',', '.'); ?></span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill ads-fill" style="width: <?php echo $ads_percent; ?>%"></div>
                </div>
                <div class="d-flex justify-content-end mt-1">
                    <small class="text-muted"><?php echo round($ads_percent, 1); ?>% dari total</small>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="total-revenue mt-3 pt-3 border-top">
                <div class="d-flex justify-content-between">
                    <span class="total-revenue-detail"><strong>TOTAL</strong></span>
                    <span class="total-revenue-digit"><strong>Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4 p-3 shadow-sm">
        <h5 class="card-title">Tenant Performance</h5>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tenant</th>
                    <th>Kategori</th>
                    <th>Lama Jadi Tenant (hari)</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                while ($row = $result_tenant_detail->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $row['tenant_name']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><?php echo $row['days_as_tenant']; ?> hari</td>
                        <td>Rp <?php echo number_format($row['total_revenue'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card p-3 shadow-sm">
                <h5 class="card-title">Top 5 Tenant Berdasarkan Revenue</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $sql_top_detail = "SELECT 
                                                t.tenant_name,
                                                SUM(tr.amount) as total_revenue
                                            FROM dummy_tenants t
                                            JOIN dummy_transactions tr ON t.tenant_id = tr.tenant_id
                                            GROUP BY t.tenant_id
                                            ORDER BY total_revenue DESC
                                            LIMIT 5";
                        $result_top_detail = $conn->query($sql_top_detail);
                        while ($row = $result_top_detail->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $row['tenant_name']; ?></td>
                                <td>Rp <?php echo number_format($row['total_revenue'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4 mb-4 p-3 shadow-sm">
        <h5 class="mb-3 card-title">Event Performance</h5>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Event</th>
                        <th>Tanggal</th>
                        <th>Pendapatan</th>
                        <th>Peserta</th>
                        <th>Rata-rata per Peserta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if ($result_events->num_rows > 0):
                        while ($event = $result_events->fetch_assoc()):
                            $avg_per_participant = ($event['participant_count'] > 0)
                                ? $event['revenue'] / $event['participant_count']
                                : 0;
                    ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><strong><?php echo htmlspecialchars($event['event_name']); ?></strong></td>
                                <td><?php echo date('d M Y', strtotime($event['event_date'])); ?></td>
                                <td>Rp <?php echo number_format($event['revenue'], 0, ',', '.'); ?></td>
                                <td><?php echo number_format($event['participant_count']); ?> orang</td>
                                <td>Rp <?php echo number_format($avg_per_participant, 0, ',', '.'); ?></td>
                            </tr>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada data event</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Ringkasan Event -->
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="bg-light p-2 rounded text-center">
                    <small class="text-muted">Total Event</small>
                    <h5 class="mb-0"><?php echo $total_data['total_events']; ?> event</h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-light p-2 rounded text-center">
                    <small class="text-muted">Total Pendapatan</small>
                    <h5 class="mb-0">Rp <?php echo number_format($total_data['total_revenue'] / 1000000, 1); ?> M</h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-light p-2 rounded text-center">
                    <small class="text-muted">Total Peserta</small>
                    <h5 class="mb-0"><?php echo number_format($total_data['total_participants'] / 1000, 1); ?> rb</h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-light p-2 rounded text-center">
                    <small class="text-muted">Rata-rata Peserta</small>
                    <h5 class="mb-0"><?php echo number_format($total_data['avg_participants'], 0); ?> org</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .display-4 {
        font-size: 2.5rem;
        font-weight: 600;
        color: var(--text-accent, #FFB62A);
    }

    .card {
        background-color: var(--background, #021F42);
        border-radius: 12px;
        border: none;
    }

    .card-title {
        color: var(--text, #F5F7FA);
    }

    .text-muted {
        color: #a0a0a0 !important;
    }

    .table {
        color: var(--text, #F5F7FA);
    }

    .table-striped>tbody>tr:nth-of-type(odd)>* {
        background-color: var(--text);
    }

    .card-title {
        color: var(--text);
        margin-bottom: 20px;
    }

    .revenue-breakdown {
        padding: 0 5px;
    }

    .progress-bar-container {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        height: 30px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 10px;
        color: white;
        font-size: 12px;
        font-weight: bold;
        transition: width 0.5s ease;
    }

    .tenant-fill {
        background: linear-gradient(90deg, #FFB62A, #FFD966);
    }

    .event-fill {
        background: linear-gradient(90deg, #22C55E, #4ADE80);
    }

    .parking-fill {
        background: linear-gradient(90deg, #0B376D, #167E80);
    }

    .ads-fill {
        background: linear-gradient(90deg, #EF4444, #F87171);
    }

    .total-revenue {
        border-top-color: rgba(255, 255, 255, 0.2) !important;
    }

    .text-accent {
        color: var(--text-accent, #FFB62A);
    }

    .revenue-item {
        animation: fadeInUp 0.4s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .heading-breakdown-revenue {
        color: var(--text-accent);
        margin-bottom: 16px;
        font-size: var(--h2);
        font-weight: 600;
    }

    .total-revenue-detail {
        color: var(--text);
        font-size: var(--h3);
    }

    .total-revenue-digit {
        color: var(--text-accent);
        font-size: var(--h3);
    }
</style>

<?php
$content = ob_get_clean();
require_once '../../includes/08_nav_template.php';
?>