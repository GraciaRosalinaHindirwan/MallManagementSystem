<?php
require_once '../../config/konek.php';
require_once '../../vendor/autoload.php';
require_once __DIR__ . '/../../public/auth/checkSession.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ARRAY NAMA BULAN
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

// FUNGSI FORMAT TANGGAL INDONESIA
function formatTanggalIndo($tanggal, $nama_bulan)
{
    $timestamp = strtotime($tanggal);
    $hari = date('d', $timestamp);
    $bulanInggris = date('F', $timestamp);
    $bulanIndo = $nama_bulan[$bulanInggris] ?? $bulanInggris;
    $tahun = date('Y', $timestamp);
    return "$hari $bulanIndo $tahun";
}

// FUNGSI NAMA FILE PDF
function generateFileNamePDF($period_type, $period_date, $nama_bulan)
{
    $timestamp = strtotime($period_date);

    switch ($period_type) {
        case 'daily':
            $hari = date('d', $timestamp);
            $bulan = $nama_bulan[date('F', $timestamp)] ?? date('F', $timestamp);
            $tahun = date('Y', $timestamp);
            $label = 'Harian';
            $date_part = "$hari $bulan $tahun";
            break;
        case 'weekly':
            $hari = date('d', $timestamp);
            $bulan = $nama_bulan[date('F', $timestamp)] ?? date('F', $timestamp);
            $tahun = date('Y', $timestamp);
            $label = 'Mingguan';
            $date_part = "$hari $bulan $tahun";
            break;
        case 'monthly':
            $bulan = $nama_bulan[date('F', $timestamp)] ?? date('F', $timestamp);
            $tahun = date('Y', $timestamp);
            $label = 'Bulanan';
            $date_part = "$bulan $tahun";
            break;
        case 'annual':
            $tahun = date('Y', $timestamp);
            $label = 'Tahunan';
            $date_part = $tahun;
            break;
        default:
            $label = ucfirst($period_type);
            $date_part = $period_date;
    }

    return "Laporan_{$label}_{$date_part}.pdf";
}

// Ambil parameter dari URL
$period_type = $_GET['period'] ?? 'daily';
$period_date = $_GET['date'] ?? date('Y-m-d');

// Penyesuaian untuk weekly
if ($period_type == 'weekly' && !isset($_GET['date'])) {
    $period_date = date('Y-m-d', strtotime('monday this week'));
}

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
// LOGIKA SNAPSHOT:
// =====================================================

// Tentukan apakah periode ini "masih berjalan" atau "sudah selesai"
function isPeriodActive($period_type, $period_date)
{
    $today = date('Y-m-d');

    switch ($period_type) {
        case 'daily':
            // Hari ini = real-time, selain itu = snapshot
            return $period_date == $today;

        case 'weekly':
            // Minggu ini = real-time
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            return $period_date == $startOfWeek;

        case 'monthly':
            // Bulan ini = real-time
            $firstOfMonth = date('Y-m-01');
            return $period_date == $firstOfMonth;

        case 'annual':
            // Tahun ini = real-time
            $firstOfYear = date('Y-01-01');
            return $period_date == $firstOfYear;

        default:
            return false;
    }
}

// =====================================================
// AMBIL DATA
// =====================================================

$data = null;
$isActive = isPeriodActive($period_type, $period_date);

if ($isActive) {
    // Periode aktif (masih berjalan) → hitung real-time
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

        // Simpan ke snapshot untuk periode yang sudah lewat
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
function formatPeriodTitle($period_type, $period_date, $nama_bulan)
{
    $timestamp = strtotime($period_date);

    switch ($period_type) {
        case 'daily':
            $hari = date('d', $timestamp);
            $bulan = $nama_bulan[date('F', $timestamp)] ?? date('F', $timestamp);
            $tahun = date('Y', $timestamp);
            return "$hari $bulan $tahun";

        case 'weekly':
            $start = date('d', $timestamp);
            $bulanStart = $nama_bulan[date('F', $timestamp)] ?? date('F', $timestamp);
            $tahunStart = date('Y', $timestamp);

            $end = date('d', strtotime($period_date . ' +6 days'));
            $bulanEnd = $nama_bulan[date('F', strtotime($period_date . ' +6 days'))] ?? date('F', strtotime($period_date . ' +6 days'));
            $tahunEnd = date('Y', strtotime($period_date . ' +6 days'));

            return "Minggu ke-" . date('W', $timestamp) . " ($start $bulanStart $tahunStart - $end $bulanEnd $tahunEnd)";

        case 'monthly':
            $bulan = $nama_bulan[date('F', $timestamp)] ?? date('F', $timestamp);
            $tahun = date('Y', $timestamp);
            return "$bulan $tahun";

        case 'annual':
            return 'Tahun ' . date('Y', $timestamp);

        default:
            return $period_date;
    }
}

// =====================================================
// AMBIL TOP TENANT (FILTER PER PERIODE)
// =====================================================
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
$top_tenants = [];
while ($row = $result_top->fetch_assoc()) {
    $top_tenants[] = $row;
}

// Kalau tidak ada data pakai payment_date, fallback ke period
if (empty($top_tenants)) {
    $sql_top2 = "SELECT t.tenant_name, COALESCE(SUM(i.total_amount), 0) as total
                 FROM `02_tenants` t
                 LEFT JOIN `06_invoices` i ON t.id_tenant = i.tenant_id 
                 WHERE t.status = 'Active'
                 AND i.period_start <= '$end_date' 
                 AND i.period_end >= '$start_date'
                 GROUP BY t.id_tenant
                 ORDER BY total DESC LIMIT 5";
    $result_top = $conn->query($sql_top2);
    $top_tenants = [];
    while ($row = $result_top->fetch_assoc()) {
        $top_tenants[] = $row;
    }
}

// =====================================================
// BUAT HTML UNTUK PDF
// =====================================================

// Tentukan judul laporan berdasarkan tipe periode
$judul_laporan = [
    'daily' => 'LAPORAN HARIAN',
    'weekly' => 'LAPORAN MINGGUAN',
    'monthly' => 'LAPORAN BULANAN',
    'annual' => 'LAPORAN TAHUNAN'
][$period_type] ?? strtoupper($period_type);

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $judul_laporan . ' - Mall Management System</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .header h2 { margin-bottom: 5px; margin-top: 0; }
        .header p { margin: 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-end { text-align: right; }
        .total-row { background-color: #f8f9fa; font-weight: bold; }
        .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #999; border-top: 1px solid #ddd; padding-top: 15px; }
    </style>
</head>
<body>
<div style="max-width: 1000px; margin: 0 auto;">
    <div class="header">
        <h2>MALL MANAGEMENT SYSTEM</h2>
        <h3>' . $judul_laporan . '</h3>
        <p>' . formatPeriodTitle($period_type, $period_date, $nama_bulan) . '</p>
    </div>

    <h5>RINCIAN PENDAPATAN</h5>
    <table>
        <tr><td width="70%"><strong>Pendapatan Tenant</strong></td><td class="text-end">Rp ' . number_format($data['tenant_revenue'] ?? 0, 0, ',', '.') . '</td></tr>
        <tr><td><strong>Pendapatan Event</strong></td><td class="text-end">Rp ' . number_format($data['event_revenue'] ?? 0, 0, ',', '.') . '</td></tr>
        <tr><td><strong>Pendapatan Parkir</strong></td><td class="text-end">Rp ' . number_format($data['parking_revenue'] ?? 0, 0, ',', '.') . '</td></tr>
        <tr><td><strong>Pendapatan Iklan</strong></td><td class="text-end">Rp ' . number_format($data['ads_revenue'] ?? 0, 0, ',', '.') . '</td></tr>
        <tr class="total-row"><td><strong>TOTAL PENDAPATAN</strong></td><td class="text-end"><strong>Rp ' . number_format($data['total_revenue'] ?? 0, 0, ',', '.') . '</strong></td></tr>
    </table>

    <h5>OPERASIONAL</h5>
    <table>
        <tr>
            <td width="50%"><strong>Tingkat Hunian</strong></td>
            <td>' . ($data['occupancy_rate'] ?? 0) . '%</td>
            <td>Terisi ' . ($data['occupied_units'] ?? 0) . ' dari ' . ($data['total_units'] ?? 0) . ' unit</td>
        </tr>
    </table>

    <h5>TOP 5 TENANT</h5>
    <table>
        <thead><tr><th>#</th><th>Tenant</th><th class="text-end">Total Pendapatan</th></tr></thead>
        <tbody>';

$no = 1;
foreach ($top_tenants as $tenant) {
    $html .= '<tr><td>' . $no++ . '</td><td>' . htmlspecialchars($tenant['tenant_name']) . '</td><td class="text-end">Rp ' . number_format($tenant['total'], 0, ',', '.') . '</td></tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        <p>Dibuat pada: ' . date('d-m-Y H:i:s') . '</p>
        <p>Mall Management System - Laporan Resmi</p>
    </div>
</div>
</body>
</html>';

// =====================================================
// GENERATE PDF
// =====================================================
try {
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filenamePDF = generateFileNamePDF($period_type, $period_date, $nama_bulan);
    $dompdf->stream($filenamePDF, ["Attachment" => true]);
} catch (Exception $e) {
    echo "Error generating PDF: " . $e->getMessage();
}
exit;
