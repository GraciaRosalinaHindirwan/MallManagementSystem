<?php
require_once '../../config/konek.php';

$period_type = $_GET['period'] ?? 'daily';
$period_date = $_GET['date'] ?? date('Y-m-d');

if ($period_type == 'weekly' && !isset($_GET['date'])) {
    $period_date = date('Y-m-d', strtotime('monday this week'));
}

function formatPeriodTitlePrint($period_type, $period_date)
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

// PERBAIKAN: Ganti kpi_snapshots menjadi 08_kpi_snapshots
$sql = "SELECT * FROM `08_kpi_snapshots` WHERE period_type = '$period_type' AND period_date = '$period_date'";
$result = $conn->query($sql);
$data = $result->fetch_assoc();

// Jika data tidak ditemukan, hitung ulang
if (!$data) {
    // Hitung occupancy
    $sql_units = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied FROM `01_units`";
    $result_units = $conn->query($sql_units);
    $units = $result_units->fetch_assoc();
    $occupancy_rate = ($units['total'] > 0) ? round(($units['occupied'] / $units['total']) * 100, 2) : 0;

    // Hitung revenue
    $sql_rev = "SELECT SUM(total_amount) as total FROM `06_invoices` WHERE status = 'Lunas'";
    $result_rev = $conn->query($sql_rev);
    $rev = $result_rev->fetch_assoc();
    $total_revenue = $rev['total'] ?? 0;

    $data = [
        'occupancy_rate' => $occupancy_rate,
        'total_revenue' => $total_revenue,
        'tenant_revenue' => $total_revenue,
        'event_revenue' => 0,
        'parking_revenue' => 0,
        'ads_revenue' => 0,
        'total_units' => $units['total'],
        'occupied_units' => $units['occupied']
    ];
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
            <p><?php echo formatPeriodTitlePrint($period_type, $period_date); ?></p>
        </div>

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

        <h5>OPERASIONAL</h5>
        <table>
            <tr>
                <td width="50%"><strong>Occupancy Rate</strong></td>
                <td><?php echo $data['occupancy_rate'] ?? 0; ?>%</td>
                <td>Terisi <?php echo $data['occupied_units'] ?? 0; ?> dari <?php echo $data['total_units'] ?? 0; ?> unit</td>
            </tr>
        </table>

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
                <?php
                $sql_top = "SELECT t.tenant_name, COALESCE(SUM(i.total_amount), 0) as total
                        FROM `02_tenants` t
                        LEFT JOIN `06_invoices` i ON t.id_tenant = i.tenant_id AND i.status = 'Lunas'
                        WHERE t.status = 'Active'
                        GROUP BY t.id_tenant
                        ORDER BY total DESC LIMIT 5";
                $result_top = $conn->query($sql_top);
                $no = 1;
                while ($row = $result_top->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $row['tenant_name']; ?></td>
                        <td class="text-end">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="footer">
            <p>Digenerate pada: <?php echo date('d-m-Y H:i:s'); ?></p>
        </div>
        <div class="text-center mt-4 no-print">
            <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
        </div>
    </div>
</body>

</html>