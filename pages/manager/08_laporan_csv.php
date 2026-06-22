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

// =====================================================
// FUNGSI NAMA FILE CSV
// =====================================================
function generateFileNameCSV($period_type, $period_date, $nama_bulan)
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

    return "Laporan_{$label}_{$date_part}.csv";
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

// Penyesuaian untuk weekly
if ($period_type == 'weekly' && !isset($_GET['date'])) {
    $period_date = date('Y-m-d', strtotime('monday this week'));
}

// =====================================================
// FORMAT TANGGAL INDONESIA UNTUK JUDUL
// =====================================================
$timestamp = strtotime($period_date);
$hari = date('d', $timestamp);
$bulanInggris = date('F', $timestamp);
$bulanIndo = $nama_bulan[$bulanInggris] ?? $bulanInggris;
$tahun = date('Y', $timestamp);
$tanggal_indonesia = "$hari $bulanIndo $tahun";

// Tentukan judul laporan berdasarkan tipe periode
$judul_laporan = [
    'daily' => 'LAPORAN HARIAN',
    'weekly' => 'LAPORAN MINGGUAN',
    'monthly' => 'LAPORAN BULANAN',
    'annual' => 'LAPORAN TAHUNAN'
][$period_type] ?? strtoupper($period_type);

// =====================================================
// AMBIL DATA (HYBRID: REAL-TIME ATAU SNAPSHOT)
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
// GENERATE CSV
// =====================================================
header('Content-Type: text/csv');
$filenameCSV = generateFileNameCSV($period_type, $period_date, $nama_bulan);
header('Content-Disposition: attachment; filename="' . $filenameCSV . '"');

$output = fopen('php://output', 'w');

// =====================================================
// 1. JUDUL & PERIODE
// =====================================================
fputcsv($output, [$judul_laporan]);
fputcsv($output, ['Periode', $tanggal_indonesia]);
fputcsv($output, []);

// =====================================================
// 2. RINCIAN PENDAPATAN
// =====================================================
fputcsv($output, ['RINCIAN PENDAPATAN']);
fputcsv($output, ['Pendapatan Tenant', 'Rp ' . number_format($data['tenant_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, ['Pendapatan Event', 'Rp ' . number_format($data['event_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, ['Pendapatan Parkir', 'Rp ' . number_format($data['parking_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, ['Pendapatan Iklan', 'Rp ' . number_format($data['ads_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, ['TOTAL PENDAPATAN', 'Rp ' . number_format($data['total_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, []);

// =====================================================
// 3. OPERASIONAL
// =====================================================
fputcsv($output, ['OPERASIONAL']);
fputcsv($output, ['Tingkat Hunian', $data['occupancy_rate'] . '%']);
fputcsv($output, ['Unit Terisi', ($data['occupied_units'] ?? 0) . ' dari ' . ($data['total_units'] ?? 0)]);
fputcsv($output, []);

// =====================================================
// 4. AMBIL TOP TENANT (FILTER PER PERIODE)
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

// =====================================================
// 5. TOP 5 TENANT
// =====================================================
fputcsv($output, ['TOP 5 TENANT']);
fputcsv($output, ['#', 'Tenant', 'Total Pendapatan']);

$no = 1;
while ($row = $result_top->fetch_assoc()) {
    fputcsv($output, [
        $no++,
        $row['tenant_name'],
        'Rp ' . number_format($row['total'], 0, ',', '.')
    ]);
}

// =====================================================
// 6. FOOTER
// =====================================================
fputcsv($output, []);
fputcsv($output, ['Dibuat pada: ' . date('d-m-Y H:i:s')]);

fclose($output);
exit;
