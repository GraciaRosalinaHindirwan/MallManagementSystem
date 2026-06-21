<?php
require_once '../../config/konek.php';

$period_type = $_GET['period'] ?? 'daily';
$period_date = $_GET['date'] ?? date('Y-m-d');

// Ambil data dari kpi_snapshots
$sql = "SELECT * FROM kpi_snapshots 
        WHERE period_type = '$period_type' 
        AND period_date = '$period_date'";
$result = $conn->query($sql);
$data = $result->fetch_assoc();

if (!$data) {
    die("Data tidak ditemukan");
}

// Set header untuk download CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="laporan_' . $period_type . '_' . $period_date . '.csv"');

$output = fopen('php://output', 'w');

// Header CSV
fputcsv($output, ['LAPORAN ' . strtoupper($period_type)]);
fputcsv($output, ['Periode', $period_date]);
fputcsv($output, []);
fputcsv($output, ['REVENUE BREAKDOWN']);
fputcsv($output, ['Tenant Revenue', 'Rp ' . number_format($data['tenant_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, ['Event Revenue', 'Rp ' . number_format($data['event_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, ['Parking Revenue', 'Rp ' . number_format($data['parking_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, ['Iklan Revenue', 'Rp ' . number_format($data['ads_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, ['TOTAL REVENUE', 'Rp ' . number_format($data['total_revenue'] ?? 0, 0, ',', '.')]);
fputcsv($output, []);
fputcsv($output, ['OPERASIONAL']);
fputcsv($output, ['Occupancy Rate', $data['occupancy_rate'] . '%']);
fputcsv($output, ['Unit Terisi', ($data['occupied_units'] ?? 0) . ' dari ' . ($data['total_units'] ?? 0)]);
fputcsv($output, []);
fputcsv($output, ['Digenerate pada: ' . date('d-m-Y H:i:s')]);

fclose($output);
exit;
