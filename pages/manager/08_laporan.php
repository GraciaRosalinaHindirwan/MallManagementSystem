<?php
require_once '../../config/08_conn.php';

// Ambil parameter tab yang aktif
$active_tab = $_GET['tab'] ?? 'daily';

// Mulai buffer untuk menangkap konten
ob_start();
?>

<h2 class="mb-3">Laporan Periodik</h2>

<!-- Filter Periode (Harian | Mingguan | Bulanan | Tahunan) -->
<div class="mb-4">
    <a href="?tab=daily" class="btn <?php echo $active_tab == 'daily' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Harian</a>
    <a href="?tab=weekly" class="btn <?php echo $active_tab == 'weekly' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Mingguan</a>
    <a href="?tab=monthly" class="btn <?php echo $active_tab == 'monthly' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Bulanan</a>
    <a href="?tab=annual" class="btn <?php echo $active_tab == 'annual' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Tahunan</a>
</div>

<!-- Daftar Laporan -->
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Periode Laporan</th>
            <th>Buka</th>
            <th>PDF</th>
            <th>CSV</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Ambil data dari kpi_snapshots sesuai tab
        $sql = "SELECT * FROM kpi_snapshots WHERE period_type = '$active_tab' ORDER BY period_date DESC";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
                // Format nama laporan sesuai jenis periode
                switch ($active_tab) {
                    case 'daily':
                        $nama_laporan = "Laporan " . date('d/m/Y', strtotime($row['period_date']));
                        break;
                    case 'weekly':
                        $nama_laporan = "Laporan Minggu ke-" . date('W', strtotime($row['period_date'])) . " (" . date('Y', strtotime($row['period_date'])) . ")";
                        break;
                    case 'monthly':
                        $nama_laporan = "Laporan Bulan " . date('F Y', strtotime($row['period_date']));
                        break;
                    case 'annual':
                        $nama_laporan = "Laporan Tahun " . date('Y', strtotime($row['period_date']));
                        break;
                    default:
                        $nama_laporan = "Laporan " . $row['period_date'];
                }
        ?>
                <tr>
                    <td><?php echo $nama_laporan; ?></td>
                    <td>
                        <a href="08_laporan_print.php?period=<?php echo $active_tab; ?>&date=<?php echo $row['period_date']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">Buka</a>
                    </td>
                    <td>
                        <a href="08_laporan_pdf.php?period=<?php echo $active_tab; ?>&date=<?php echo $row['period_date']; ?>" class="btn btn-sm btn-primary">PDF</a>
                    </td>
                    <td>
                        <a href="08_laporan_csv.php?period=<?php echo $active_tab; ?>&date=<?php echo $row['period_date']; ?>" class="btn btn-sm btn-success">CSV</a>
                    </td>
                </tr>
            <?php
            endwhile;
        else:
            ?>
            <tr>
                <td colspan="4" class="text-center">Belum ada data laporan untuk periode ini.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php
// Simpan konten ke variable $content
$content = ob_get_clean();

// Panggil template
require_once '../../includes/navbar.php';
?>