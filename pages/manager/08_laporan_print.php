<?php
require_once '../../config/08_conn.php';

$period_type = $_GET['period'] ?? 'daily';
$period_date = $_GET['date'] ?? date('Y-m-d');

if ($period_type == 'weekly' && !isset($_GET['date'])) {
    $period_date = date('Y-m-d', strtotime('monday this week'));
}

// =====================================================
// FUNGSI HITUNG KPI (copy dari 08_laporan_pdf.php)
// =====================================================
function calculateKPIDirect($conn, $period_type, $period_date)
{
    switch ($period_type) {
        case 'daily':
            $start_date = $period_date;
            $end_date = $period_date;
            break;
        case 'weekly':
            $start_date = $period_date;
            $end_date = date('Y-m-d', strtotime($period_date . ' +6 days'));
            break;
        case 'monthly':
            $start_date = date('Y-m-01', strtotime($period_date));
            $end_date = date('Y-m-t', strtotime($period_date));
            break;
        case 'annual':
            $start_date = date('Y-01-01', strtotime($period_date));
            $end_date = date('Y-12-31', strtotime($period_date));
            break;
        default:
            $start_date = $period_date;
            $end_date = $period_date;
    }

    // Tenant Revenue
    $sql_tenant = "SELECT COALESCE(SUM(amount), 0) as total FROM dummy_transactions 
                   WHERE transaction_date BETWEEN '$start_date' AND '$end_date'";
    $result = $conn->query($sql_tenant);
    $tenant_revenue = $result->fetch_assoc()['total'];

    // Event Revenue
    $sql_event = "SELECT COALESCE(SUM(revenue), 0) as total FROM dummy_events 
                  WHERE event_date BETWEEN '$start_date' AND '$end_date' AND status = 'completed'";
    $result = $conn->query($sql_event);
    $event_revenue = $result->fetch_assoc()['total'];

    // Parking Revenue
    $sql_parking = "SELECT COALESCE(SUM(revenue), 0) as total FROM dummy_parking 
                    WHERE transaction_date BETWEEN '$start_date' AND '$end_date'";
    $result = $conn->query($sql_parking);
    $parking_revenue = $result->fetch_assoc()['total'];

    // Iklan Revenue
    $sql_ads = "SELECT COALESCE(SUM(revenue), 0) as total FROM dummy_ads 
                WHERE transaction_date BETWEEN '$start_date' AND '$end_date'";
    $result = $conn->query($sql_ads);
    $ads_revenue = $result->fetch_assoc()['total'];

    // Occupancy
    $sql_occ = "SELECT 
                    COUNT(*) as total_units,
                    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_units
                FROM dummy_units";
    $result = $conn->query($sql_occ);
    $units = $result->fetch_assoc();
    $occupancy_rate = ($units['total_units'] > 0) ? ($units['occupied_units'] / $units['total_units']) * 100 : 0;

    return [
        'period_type' => $period_type,
        'period_date' => $period_date,
        'occupancy_rate' => round($occupancy_rate, 2),
        'total_revenue' => $tenant_revenue + $event_revenue + $parking_revenue + $ads_revenue,
        'tenant_revenue' => $tenant_revenue,
        'event_revenue' => $event_revenue,
        'parking_revenue' => $parking_revenue,
        'ads_revenue' => $ads_revenue,
        'total_units' => $units['total_units'],
        'occupied_units' => $units['occupied_units']
    ];
}

// =====================================================
// AMBIL DATA
// =====================================================
$sql = "SELECT * FROM kpi_snapshots 
        WHERE period_type = '$period_type' 
        AND period_date = '$period_date'";
$result = $conn->query($sql);
$data = $result->fetch_assoc();

if (!$data) {
    $data = calculateKPIDirect($conn, $period_type, $period_date);
}

// =====================================================
// FUNGSI FORMAT JUDUL
// =====================================================
function formatPeriodTitle($period_type, $period_date)
{
    switch ($period_type) {
        case 'daily':
            return date('d F Y', strtotime($period_date));
        case 'weekly':
            $start = date('d M Y', strtotime($period_date));
            $end = date('d M Y', strtotime($period_date . ' +6 days'));
            return "Minggu ke-" . date('W', strtotime($period_date)) . " ($start - $end)";
        case 'monthly':
            return date('F Y', strtotime($period_date));
        case 'annual':
            return 'Tahun ' . $period_date;
        default:
            return $period_date;
    }
}

// =====================================================
// AMBIL TOP TENANT
// =====================================================
$sql_top = "SELECT t.tenant_name, SUM(tr.amount) as total
            FROM dummy_tenants t
            JOIN dummy_transactions tr ON t.tenant_id = tr.tenant_id
            GROUP BY t.tenant_id
            ORDER BY total DESC LIMIT 5";
$result_top = $conn->query($sql_top);
$top_tenants = [];
while ($row = $result_top->fetch_assoc()) {
    $top_tenants[] = $row;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan <?php echo strtoupper($period_type); ?> - Mall Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            padding: 20px;
        }

        .laporan-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .header h2 {
            margin-bottom: 5px;
        }

        .header p {
            margin: 0;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-end {
            text-align: right;
        }

        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="laporan-container">
        <div class="header">
            <h2>MALL MANAGEMENT SYSTEM</h2>
            <h3>LAPORAN <?php echo strtoupper($period_type); ?></h3>
            <p><?php echo formatPeriodTitle($period_type, $period_date); ?></p>
        </div>

        <!-- Revenue Section -->
        <h5>REVENUE BREAKDOWN</h5>
        <table>
            <tr>
                <td width="70%"><strong>Tenant Revenue</strong></td>
                <td class="text-end">Rp <?php echo number_format($data['tenant_revenue'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td><strong>Event Revenue</strong></td>
                <td class="text-end">Rp <?php echo number_format($data['event_revenue'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td><strong>Parking Revenue</strong></td>
                <td class="text-end">Rp <?php echo number_format($data['parking_revenue'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td><strong>Iklan Revenue</strong></td>
                <td class="text-end">Rp <?php echo number_format($data['ads_revenue'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL REVENUE</strong></td>
                <td class="text-end"><strong>Rp <?php echo number_format($data['total_revenue'] ?? 0, 0, ',', '.'); ?></strong></td>
            </tr>
        </table>

        <!-- Operational Section -->
        <h5>OPERASIONAL</h5>
        <table>
            <tr>
                <td width="50%"><strong>Occupancy Rate</strong></td>
                <td><?php echo $data['occupancy_rate'] ?? 0; ?>%</td>
                <td>Terisi <?php echo $data['occupied_units'] ?? 0; ?> dari <?php echo $data['total_units'] ?? 0; ?> unit</td>
            </tr>
        </table>

        <!-- Top Tenant Section -->
        <h5>TOP 5 TENANT</h5>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tenant</th>
                    <th class="text-end">Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                foreach ($top_tenants as $tenant): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $tenant['tenant_name']; ?></td>
                        <td class="text-end">Rp <?php echo number_format($tenant['total'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer">
            <p>Digenerate pada: <?php echo date('d-m-Y H:i:s'); ?></p>
            <p>Mall Management System - Laporan Resmi</p>
        </div>

        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary"><a href="08_laporan_pdf.php?period=<?php echo $period_type; ?>&date=<?php echo $period_date; ?>" target="_blank" class="text-white text-decoration-none">Simpan PDF</a></button>
            <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
        </div>
    </div>
</body>

</html>