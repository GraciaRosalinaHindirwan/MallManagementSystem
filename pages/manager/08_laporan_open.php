<?php
require_once '../../config/konek.php';
require_once __DIR__ . '/../../public/auth/checkSession.php';

$nama_bulan = [
    'January' => 'Januari',
    'February' => 'Februari',
    'March' => 'Maret',
    'April' => 'April',
    'May' => 'Mei',
    'June' => 'Juni',
    'July' => 'Juli',
    'August' => 'Agustus',
    'September' => 'September',
    'October' => 'Oktober',
    'November' => 'November',
    'December' => 'Desember'
];

function calculateKPIDirect($conn, $period_type, $period_date)
{
    // Tentukan rentang tanggal
    switch ($period_type) {
        case 'daily':
            $start_date = $period_date;
            $end_date = $period_date;
            break;
        case 'weekly':
            $start_date = date('Y-m-d', strtotime('monday this week', strtotime($period_date)));
            $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($period_date)));
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

    // =====================================================
    // 1. TENANT REVENUE - Pakai payment_date atau period
    // =====================================================
    // Opsi A: Pakai payment_date (kalau ada)
    $sql_tenant = "SELECT COALESCE(SUM(total_amount), 0) as total 
                   FROM `06_invoices` 
                   WHERE status = 'Lunas' 
                   AND payment_date BETWEEN '$start_date' AND '$end_date 23:59:59'";
    $result = $conn->query($sql_tenant);
    $tenant_revenue = $result->fetch_assoc()['total'];

    // Opsi B: Kalau payment_date NULL, pakai period (untuk yang sudah jatuh tempo)
    if ($tenant_revenue == 0) {
        $sql_tenant2 = "SELECT COALESCE(SUM(total_amount), 0) as total 
                        FROM `06_invoices` 
                        WHERE status IN ('Lunas', 'Belum Bayar')
                        AND period_start <= '$end_date' 
                        AND period_end >= '$start_date'";
        $result = $conn->query($sql_tenant2);
        $tenant_revenue = $result->fetch_assoc()['total'];
    }

    // =====================================================
    // 2. EVENT REVENUE - Untuk periode yang sedang berjalan
    // =====================================================
    // Ambil dari tiket yang terjual (pakai tanggal event)
    $sql_event = "SELECT COALESCE(SUM(et.pendapatan), 0) as total 
                  FROM `04_event_tiket` et
                  JOIN `04_event_booking` eb ON et.id_booking = eb.id_booking
                  WHERE eb.tanggal_mulai <= '$end_date' 
                  AND eb.tanggal_selesai >= '$start_date'
                  AND eb.status IN ('approved', 'completed')";
    $result = $conn->query($sql_event);
    $event_from_ticket = $result->fetch_assoc()['total'];

    // Tambah dari sponsorship yang sudah paid
    $sql_sponsor = "SELECT COALESCE(SUM(sp.nilai), 0) as total 
                    FROM `04_event_sponsorship` sp
                    JOIN `04_event_booking` eb ON sp.id_booking = eb.id_booking
                    WHERE sp.status_bayar = 'lunas'
                    AND eb.tanggal_mulai <= '$end_date' 
                    AND eb.tanggal_selesai >= '$start_date'";
    $result = $conn->query($sql_sponsor);
    $event_from_sponsor = $result->fetch_assoc()['total'];

    $event_revenue = $event_from_ticket + $event_from_sponsor;

    // =====================================================
    // 3. PARKING REVENUE
    // =====================================================
    // Ambil dari transaksi parkir langsung (04_parking_transaksi)
    $sql_parking = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM `04_parking_transaksi` 
                    WHERE exit_time BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'
                    AND amount > 0";
    $result = $conn->query($sql_parking);
    $parking_revenue = $result->fetch_assoc()['total'];

    // Kalau tidak ada, coba dari summary
    if ($parking_revenue == 0) {
        $sql_parking2 = "SELECT COALESCE(SUM(total_revenue), 0) as total 
                         FROM `06_daily_parking_summary`
                         WHERE summary_date BETWEEN '$start_date' AND '$end_date'";
        $result = $conn->query($sql_parking2);
        $parking_revenue = $result->fetch_assoc()['total'];
    }

    // =====================================================
    // 4. IKLAN REVENUE
    // =====================================================
    // Ambil dari kontrak iklan yang active dan paid
    $sql_ads = "SELECT COALESCE(SUM(monthly_fee), 0) as total 
                FROM `06_ad_contracts` 
                WHERE billing_status = 'paid' 
                AND start_date <= '$end_date' 
                AND end_date >= '$start_date'";
    $result = $conn->query($sql_ads);
    $ads_revenue = $result->fetch_assoc()['total'];

    // Kalau tidak ada, ambil dari kontrak yang active (meski belum paid)
    if ($ads_revenue == 0) {
        $sql_ads2 = "SELECT COALESCE(SUM(monthly_fee), 0) as total 
                     FROM `06_ad_contracts` 
                     WHERE status = 'active'
                     AND start_date <= '$end_date' 
                     AND end_date >= '$start_date'";
        $result = $conn->query($sql_ads2);
        $ads_revenue = $result->fetch_assoc()['total'];
    }

    // =====================================================
    // 5. OCCUPANCY
    // =====================================================
    $sql_occ = "SELECT 
                    COUNT(*) as total_units,
                    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_units
                FROM `01_units`";
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
// LOGIKA SNAPSHOT
// =====================================================
function isPeriodActive($period_type, $period_date)
{
    $today = date('Y-m-d');

    switch ($period_type) {
        case 'daily':
            return $period_date == $today;
        case 'weekly':
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            return $period_date == $startOfWeek;
        case 'monthly':
            $firstOfMonth = date('Y-m-01');
            return $period_date == $firstOfMonth;
        case 'annual':
            $firstOfYear = date('Y-01-01');
            return $period_date == $firstOfYear;
        default:
            return false;
    }
}

// =====================================================
// AMBIL PARAMETER
// =====================================================
$period_type = $_GET['period'] ?? 'daily';
$period_date = $_GET['date'] ?? date('Y-m-d');

if ($period_type == 'weekly' && !isset($_GET['date'])) {
    $period_date = date('Y-m-d', strtotime('monday this week'));
}

// =====================================================
// AMBIL DATA (HYBRID: REAL-TIME ATAU SNAPSHOT)
// =====================================================
$data = null;
$isActive = isPeriodActive($period_type, $period_date);

if ($isActive) {
    // Periode aktif → hitung real-time
    $data = calculateKPIDirect($conn, $period_type, $period_date);
} else {
    // Periode sudah lewat → cari di snapshot
    $sql = "SELECT * FROM `08_kpi_snapshots` 
            WHERE period_type = '$period_type' 
            AND period_date = '$period_date'";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();

    // Kalau belum ada di snapshot → hitung dan simpan
    if (!$data) {
        $data = calculateKPIDirect($conn, $period_type, $period_date);

        $sql_insert = "INSERT INTO `08_kpi_snapshots` 
                       (period_type, period_date, occupancy_rate, total_revenue, 
                        tenant_revenue, event_revenue, parking_revenue, ads_revenue,
                        total_units, occupied_units, created_at)
                       VALUES (
                           '$period_type', 
                           '$period_date', 
                           '{$data['occupancy_rate']}', 
                           '{$data['total_revenue']}', 
                           '{$data['tenant_revenue']}', 
                           '{$data['event_revenue']}', 
                           '{$data['parking_revenue']}', 
                           '{$data['ads_revenue']}', 
                           '{$data['total_units']}', 
                           '{$data['occupied_units']}', 
                           NOW()
                       )";
        $conn->query($sql_insert);
    }
}

// =====================================================
// FORMAT JUDUL (BAHASA INDONESIA)
// =====================================================
function formatPeriodTitlePrint($period_type, $period_date, $nama_bulan)
{
    switch ($period_type) {
        case 'daily':
            $hari = date('d', strtotime($period_date));
            $bulanInggris = date('F', strtotime($period_date));
            $bulanIndo = $nama_bulan[$bulanInggris] ?? $bulanInggris;
            $tahun = date('Y', strtotime($period_date));
            return "$hari $bulanIndo $tahun";
        case 'weekly':
            $start = date('d', strtotime($period_date));
            $bulanStart = $nama_bulan[date('F', strtotime($period_date))] ?? date('F', strtotime($period_date));
            $tahunStart = date('Y', strtotime($period_date));

            $end = date('d', strtotime($period_date . ' +6 days'));
            $bulanEnd = $nama_bulan[date('F', strtotime($period_date . ' +6 days'))] ?? date('F', strtotime($period_date . ' +6 days'));
            $tahunEnd = date('Y', strtotime($period_date . ' +6 days'));

            return "Minggu ke-" . date('W', strtotime($period_date)) . " ($start $bulanStart $tahunStart - $end $bulanEnd $tahunEnd)";
        case 'monthly':
            $bulanInggris = date('F', strtotime($period_date));
            $bulanIndo = $nama_bulan[$bulanInggris] ?? $bulanInggris;
            $tahun = date('Y', strtotime($period_date));
            return "$bulanIndo $tahun";
        case 'annual':
            return 'Tahun ' . $period_date;
        default:
            return $period_date;
    }
}

// =====================================================
// AMBIL TOP TENANT (FILTER PER PERIODE)
// =====================================================
// Tentukan rentang tanggal untuk top tenant
switch ($period_type) {
    case 'daily':
        $start_date = $period_date;
        $end_date = $period_date;
        break;
    case 'weekly':
        $start_date = date('Y-m-d', strtotime('monday this week', strtotime($period_date)));
        $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($period_date)));
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

// PAKAI PAYMENT_DATE, BUKAN CREATED_AT
$sql_top = "SELECT t.tenant_name, COALESCE(SUM(i.total_amount), 0) as total
            FROM `02_tenants` t
            LEFT JOIN `06_invoices` i ON t.id_tenant = i.tenant_id AND i.status = 'Lunas'
            WHERE t.status = 'Active'
            AND i.payment_date BETWEEN '$start_date' AND '$end_date'
            GROUP BY t.id_tenant
            ORDER BY total DESC LIMIT 5";
$result_top = $conn->query($sql_top);

// Kalau tidak ada data pakai payment_date, fallback ke period
if ($result_top->num_rows == 0) {
    $sql_top2 = "SELECT t.tenant_name, COALESCE(SUM(i.total_amount), 0) as total
                 FROM `02_tenants` t
                 LEFT JOIN `06_invoices` i ON t.id_tenant = i.tenant_id 
                 WHERE t.status = 'Active'
                 AND i.period_start <= '$end_date' 
                 AND i.period_end >= '$start_date'
                 GROUP BY t.id_tenant
                 ORDER BY total DESC LIMIT 5";
    $result_top = $conn->query($sql_top2);
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
            <h3>
                <?php
                $judul_laporan = [
                    'daily' => 'LAPORAN HARIAN',
                    'weekly' => 'LAPORAN MINGGUAN',
                    'monthly' => 'LAPORAN BULANAN',
                    'annual' => 'LAPORAN TAHUNAN'
                ][$period_type] ?? strtoupper($period_type);
                echo $judul_laporan;
                ?>
            </h3>
            <p><?php echo formatPeriodTitlePrint($period_type, $period_date, $nama_bulan); ?></p>
        </div>

        <h5>RINCIAN PENDAPATAN</h5>
        <table>
            <tr>
                <td width="70%"><strong>Pendapatan Tenant</strong></td>
                <td class="text-end">Rp <?php echo number_format($data['tenant_revenue'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td><strong>Pendapatan Event</strong></td>
                <td class="text-end">Rp <?php echo number_format($data['event_revenue'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td><strong>Pendapatan Parkir</strong></td>
                <td class="text-end">Rp <?php echo number_format($data['parking_revenue'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td><strong>Pendapatan Iklan</strong></td>
                <td class="text-end">Rp <?php echo number_format($data['ads_revenue'] ?? 0, 0, ',', '.'); ?></td>
            </tr>
            <tr class="total-row">
                <td><strong>TOTAL PENDAPATAN</strong></td>
                <td class="text-end"><strong>Rp <?php echo number_format($data['total_revenue'] ?? 0, 0, ',', '.'); ?></strong></td>
            </tr>
        </table>

        <h5>OPERASIONAL</h5>
        <table>
            <tr>
                <td width="50%"><strong>Tingkat Hunian</strong></td>
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
                    <th class="text-end">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                <?php
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
            <p>Dibuat pada: <?php echo date('d-m-Y H:i:s'); ?></p>
        </div>
        <div class="text-center mt-4 no-print">
            <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
        </div>
    </div>
</body>

</html>