<?php
require_once '../../config/konek.php';

// Ambil parameter tab yang aktif
$active_tab = $_GET['tab'] ?? 'daily';

// Definisi variabel untuk navbar
$department_name = "BI, Workflow, and Notification";
$page_title = "Laporan Periodik";
$user_name = "Manager";

$menu_items = [
    ['icon' => 'fa-solid fa-gauge', 'label' => 'Dashboard', 'link' => '08_dashboard.php', 'active_page' => '08_dashboard'],
    ['icon' => 'fa-solid fa-chart-line', 'label' => 'Laporan', 'link' => '08_laporan.php', 'active_page' => '08_laporan'],
    ['icon' => 'fa-solid fa-check-circle', 'label' => 'Approval', 'link' => '08_approval.php', 'active_page' => '08_approval'],
    ['icon' => 'fa-solid fa-bell', 'label' => 'Notifikasi', 'link' => '08_notifikasi.php', 'active_page' => '08_notifikasi'],
];

ob_start();
?>

<div class="laporan-container">
    <!-- Filter Periode -->
    <div class="filter-wrapper">
        <div class="filter-group">
            <a href="?tab=daily" class="btn-filter <?php echo $active_tab == 'daily' ? 'active' : ''; ?>">Harian</a>
            <a href="?tab=weekly" class="btn-filter <?php echo $active_tab == 'weekly' ? 'active' : ''; ?>">Mingguan</a>
            <a href="?tab=monthly" class="btn-filter <?php echo $active_tab == 'monthly' ? 'active' : ''; ?>">Bulanan</a>
            <a href="?tab=annual" class="btn-filter <?php echo $active_tab == 'annual' ? 'active' : ''; ?>">Tahunan</a>
        </div>
    </div>

    <!-- Daftar Laporan -->
    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table-custom">
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
                    $sql = "SELECT * FROM `08_kpi_snapshots` WHERE period_type = '$active_tab' ORDER BY period_date DESC";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
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
                                <td data-label="Periode Laporan"><?php echo $nama_laporan; ?></td>
                                <td data-label="Buka">
                                    <a href="08_laporan_print.php?period=<?php echo $active_tab; ?>&date=<?php echo $row['period_date']; ?>" class="btn-action btn-buka" target="_blank">
                                        <i class="fa-solid fa-eye"></i> Buka
                                    </a>
                                </td>
                                <td data-label="PDF">
                                    <a href="08_laporan_pdf.php?period=<?php echo $active_tab; ?>&date=<?php echo $row['period_date']; ?>" class="btn-action btn-pdf">
                                        <i class="fa-solid fa-file-pdf"></i> PDF
                                    </a>
                                </td>
                                <td data-label="CSV">
                                    <a href="08_laporan_csv.php?period=<?php echo $active_tab; ?>&date=<?php echo $row['period_date']; ?>" class="btn-action btn-csv">
                                        <i class="fa-solid fa-file-csv"></i> CSV
                                    </a>
                                </td>
                            </tr>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Belum ada data laporan untuk periode ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* =============================================
   LAPORAN CONTAINER
   ============================================= */
    .laporan-container {
        padding: 20px 24px;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* =============================================
   FILTER WRAPPER
   ============================================= */
    .filter-wrapper {
        margin-bottom: 32px;
        padding-bottom: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    /* =============================================
   FILTER BUTTONS
   ============================================= */
    .btn-filter {
        background: transparent;
        color: rgba(245, 247, 250, 0.6);
        border: 2px solid rgba(255, 255, 255, 0.12);
        border-radius: 24px;
        padding: 8px 22px;
        font-family: var(--font-family, 'Poppins', sans-serif);
        font-size: var(--label, 14px);
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        min-width: 80px;
        flex-shrink: 0;
    }

    .btn-filter:hover {
        background: rgba(255, 182, 42, 0.12);
        border-color: var(--text-accent, #FFB62A);
        color: var(--text-accent, #FFB62A);
        transform: translateY(-2px);
    }

    .btn-filter.active {
        background: var(--text-accent, #FFB62A);
        border-color: var(--text-accent, #FFB62A);
        color: var(--background, #021F42);
        font-weight: 600;
        box-shadow: 0 4px 14px rgba(255, 182, 42, 0.35);
    }

    .btn-filter.active:hover {
        background: #e6a300;
        border-color: #e6a300;
        color: var(--background, #021F42);
        transform: translateY(-2px);
    }

    /* =============================================
   TABLE WRAPPER
   ============================================= */
    .table-wrapper {
        margin-top: 8px;
    }

    /* =============================================
   TABLE CUSTOM
   ============================================= */
    .table-custom {
        width: 100%;
        border-collapse: collapse;
        background-color: var(--primary, #0B376D);
        border-radius: 12px;
        overflow: hidden;
        font-family: var(--font-family, 'Poppins', sans-serif);
    }

    .table-custom thead th {
        background-color: var(--primary-dark, #082A53);
        color: var(--text, #F5F7FA);
        padding: 14px 18px;
        text-align: left;
        font-weight: 600;
        font-size: var(--label, 14px);
        border-bottom: 2px solid var(--text-accent, #FFB62A);
        white-space: nowrap;
    }

    .table-custom tbody td {
        padding: 12px 18px;
        color: var(--text, #F5F7FA);
        font-size: var(--body, 14px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        vertical-align: middle;
    }

    .table-custom tbody tr:hover {
        background-color: rgba(255, 182, 42, 0.08);
    }

    .table-custom tbody tr:last-child td {
        border-bottom: none;
    }

    /* =============================================
   ACTION BUTTONS
   ============================================= */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: var(--caption, 12px);
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
        font-family: var(--font-family, 'Poppins', sans-serif);
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-action i {
        font-size: 13px;
    }

    .btn-buka {
        background: rgba(0, 212, 216, 0.12);
        color: var(--accent, #00D4D8);
        border: 1px solid rgba(0, 212, 216, 0.25);
    }

    .btn-buka:hover {
        background: var(--accent, #00D4D8);
        color: var(--background, #021F42);
        transform: translateY(-1px);
    }

    .btn-pdf {
        background: rgba(239, 68, 68, 0.12);
        color: var(--danger, #EF4444);
        border: 1px solid rgba(239, 68, 68, 0.25);
    }

    .btn-pdf:hover {
        background: var(--danger, #EF4444);
        color: white;
        transform: translateY(-1px);
    }

    .btn-csv {
        background: rgba(34, 197, 94, 0.12);
        color: var(--success, #22C55E);
        border: 1px solid rgba(34, 197, 94, 0.25);
    }

    .btn-csv:hover {
        background: var(--success, #22C55E);
        color: white;
        transform: translateY(-1px);
    }

    /* =============================================
   MOBILE RESPONSIVE
   ============================================= */
    @media (max-width: 768px) {
        .laporan-container {
            padding: 12px 16px;
        }

        .filter-group {
            gap: 8px;
            justify-content: center;
        }

        .btn-filter {
            padding: 6px 16px;
            font-size: var(--caption, 12px);
            min-width: 60px;
            flex: 1 0 auto;
            max-width: 120px;
        }

        .filter-wrapper {
            margin-bottom: 20px;
            padding-bottom: 12px;
        }

        .table-custom thead {
            display: none;
        }

        .table-custom tbody tr {
            display: block;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 12px 16px;
            background-color: var(--primary, #0B376D);
        }

        .table-custom tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: var(--label, 13px);
            gap: 12px;
        }

        .table-custom tbody td:last-child {
            border-bottom: none;
        }

        .table-custom tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: rgba(245, 247, 250, 0.5);
            font-size: var(--caption, 11px);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
            min-width: 80px;
        }

        .table-custom tbody td[data-label="Periode Laporan"] {
            font-weight: 600;
            font-size: var(--body, 14px);
        }

        .table-custom tbody td[data-label="Periode Laporan"]::before {
            display: none;
        }

        .btn-action {
            padding: 4px 10px;
            font-size: 11px;
            gap: 4px;
        }

        .btn-action i {
            font-size: 11px;
        }
    }

    @media (max-width: 480px) {
        .laporan-container {
            padding: 8px 12px;
        }

        .filter-group {
            gap: 6px;
        }

        .btn-filter {
            padding: 5px 12px;
            font-size: 11px;
            min-width: 50px;
            border-radius: 16px;
        }

        .table-custom tbody tr {
            padding: 10px 12px;
        }

        .table-custom tbody td {
            padding: 6px 0;
            font-size: 12px;
            flex-wrap: wrap;
        }

        .table-custom tbody td::before {
            font-size: 10px;
            min-width: 60px;
        }

        .btn-action {
            padding: 4px 8px;
            font-size: 10px;
            gap: 3px;
            border-radius: 6px;
        }

        .btn-action i {
            font-size: 10px;
        }
    }
</style>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '/includes/08_nav_template.php';
?>