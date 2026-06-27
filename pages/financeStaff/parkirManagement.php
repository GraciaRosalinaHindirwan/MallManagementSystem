<?php
/** @var mysqli $conn */

if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}

$_SESSION['role'] = 'financeStaff';
$_SESSION['nama'] = 'Finance Staff';

// 1. Panggil koneksi database
if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} else {
    require_once '../../config/connection.php';
}

date_default_timezone_set('Asia/Jakarta');

// Mengatur filter bulan aktif dinamis
if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $periode_aktif = $_GET['filter_bulan']; 
} else {
    $periode_aktif = date('Y-m'); 
}

// ── 2. PROSES POSTING JURNAL OTOMATIS (REAL DOUBLE-ENTRY) ──
$msg = '';
if (isset($_POST['action']) && $_POST['action'] === 'post_to_journal') {
    $tanggal_rekap = $_POST['summary_date'];
    $total_pendapatan = (float)$_POST['total_revenue'];

    if ($total_pendapatan > 0) {
        // Cek apakah hari ini sudah pernah dijurnal untuk menghindari duplikasi
        $cek_jurnal = $conn->prepare("SELECT id FROM 06_journal_entries WHERE journal_date = ? AND source_type = 'parking'");
        $cek_jurnal->bind_param('s', $tanggal_rekap);
        $cek_jurnal->execute();
        $res_cek = $cek_jurnal->get_result();

        if ($res_cek->num_rows > 0) {
            $msg = "<div class='alert danger' style='background: rgba(239,68,68,0.15); color: #EF4444; border: 1px solid rgba(239,68,68,0.3); padding: 12px; border-radius: 8px; margin-bottom: 1rem;'><i class='fa-solid fa-circle-xmark me-2'></i>Gagal! Pendapatan parkir tanggal ".date('d M Y', strtotime($tanggal_rekap))." sudah pernah dijurnal sebelumnya.</div>";
        } else {
            $conn->begin_transaction();
            try {
                // A. Masukkan ringkasan ke tabel pembantu 06_daily_parking_summary
                $stmt_sum = $conn->prepare("
                    INSERT INTO 06_daily_parking_summary (summary_date, total_revenue, status) 
                    VALUES (?, ?, 'completed')
                    ON DUPLICATE KEY UPDATE total_revenue = ?, status = 'completed'
                ");
                $stmt_sum->bind_param('sdd', $tanggal_rekap, $total_pendapatan, $total_pendapatan);
                $stmt_sum->execute();

                // B. Generate Nomor Jurnal Unik & Keterangan Akuntansi
                $journal_number = "JV-PK-" . date('YmdHis') . "-" . rand(100, 999);
                $keterangan = "Automated Rekap Pendapatan Parkir Tanggal " . date('d/m/Y', strtotime($tanggal_rekap));
                $source_type = 'parking';
                $status_jurnal = 'posted';

                // C. INSERT KE TABEL HEADER (06_journal_entries)
                $stmt_jur = $conn->prepare("
                    INSERT INTO 06_journal_entries (journal_number, journal_date, description, source_type, total_debit, total_credit, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_jur->bind_param('ssssdds', $journal_number, $tanggal_rekap, $keterangan, $source_type, $total_pendapatan, $total_pendapatan, $status_jurnal);
                $stmt_jur->execute();

                $journal_entry_id = $conn->insert_id;

                // D. INSERT DETAIL DEBIT (ID 2: Bank BCA)
                $id_akun_debit = 2; 
                $stmt_debit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, 0)");
                $stmt_debit->bind_param("iid", $journal_entry_id, $id_akun_debit, $total_pendapatan);
                $stmt_debit->execute();

                // E. INSERT DETAIL KREDIT (ID 6: Pendapatan Parkir)
                $id_akun_kredit = 6; 
                $stmt_kredit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, 0, ?)");
                $stmt_kredit->bind_param("iid", $journal_entry_id, $id_akun_kredit, $total_pendapatan);
                $stmt_kredit->execute();

                $conn->commit();
                $msg = "<div class='alert success' style='background: rgba(34,197,94,0.15); color: #22C55E; border: 1px solid rgba(34,197,94,0.3); padding: 12px; border-radius: 8px; margin-bottom: 1rem;'><i class='fa-solid fa-circle-check me-2'></i>Berhasil! Rekap tanggal ".date('d M Y', strtotime($tanggal_rekap))." sebesar Rp ".number_format($total_pendapatan,0,',','.')." telah otomatis masuk ke Buku Besar.</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='alert danger' style='background: rgba(239,68,68,0.15); color: #EF4444; border: 1px solid rgba(239,68,68,0.3); padding: 12px; border-radius: 8px; margin-bottom: 1rem;'>Terjadi kesalahan sistem: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// ── 3. QUERY MENAMPILKAN DATA TRANSAKSI PARKIR M04 (DENGAN FILTER BULAN) ──
$query_parkir = "
    SELECT 
        DATE(p.exit_time) AS tanggal,
        COUNT(p.id_transaksi) AS total_kendaraan,
        SUM(p.amount) AS total_revenue,
        s.status AS status_jurnal
    FROM 04_parking_transaksi p
    LEFT JOIN 06_daily_parking_summary s ON DATE(p.exit_time) = s.summary_date
    WHERE DATE_FORMAT(p.exit_time, '%Y-%m') = ?
    GROUP BY DATE(p.exit_time)
    ORDER BY DATE(p.exit_time) DESC
";

$stmt = $conn->prepare($query_parkir);
$stmt->bind_param('s', $periode_aktif);
$stmt->execute();
$list_parkir = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ==========================================
// CONFIG MASTER UNTUK REQUIRE NAVBAR M06
// ==========================================
$department_name = "Finance Department"; 
$user_name = $_SESSION['nama'] ?? "Finance Staff";
$page_title = "Sinkronisasi Jurnal Pendapatan Parkir";

$menu_items = [
    [
        'icon' => 'fa-solid fa-square-parking',
        'label' => 'Pembukuan Parkir',
        'link' => $_SERVER['PHP_SELF'],
        'active_page' => 'parkirManagement'
    ]
];

// Mulai mengunci output buffer untuk ditangkap navbar
ob_start();
?>

<style>
    :root {
        --primary-color: #021F42 !important;
        --bg-dark: #021F42 !important;
    }
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #fff !important; }
    .sidebar { background-color: #011630 !important; border-right: 1px solid rgba(255,255,255,0.05); }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff !important; }
    .page-title { color: #FFB62A !important; font-weight: 700; }

    .container-box { background: #011630; border: 1px solid rgba(255,255,255,.05); border-radius: 12px; padding: 1.5rem; margin-top: 20px; }
    .title-pbi { font-size: 18px; font-weight: 600; color: #FFB62A; margin-bottom: 4px; }
    .sub-pbi { font-size: 13px; color: #64748B; margin-bottom: 1.5rem; }
    .tbl-parkir { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
    .tbl-parkir th { color: #FFB62A; font-size: 13px; font-weight: 600; padding: 12px; border-bottom: 2px solid rgba(255,255,255,.1); text-align: left; }
    .tbl-parkir td { padding: 12px; color: #fff; border-bottom: 1px solid rgba(255,255,255,.05); }
    .badge-status { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 500; padding: 3px 10px; border-radius: 99px; }
    .badge-status.completed { background: rgba(34,197,94,.12); color: #22C55E; border: 1px solid rgba(34,197,94,0.2); }
    .badge-status.pending { background: rgba(255,182,42,.12); color: #FFB62A; border: 1px solid rgba(255,182,42,0.2); }
    .btn-post { background: #167E80; color: #fff; border: none; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.15s; }
    .btn-post:hover { background: #0E5E60; }
</style>

<div class="container-fluid" style="text-align: left; padding-top: 10px;">
    <div class="container-box">
        <div class="title-pbi"><i class="fa-solid fa-square-parking text-info me-2"></i> Sinkronisasi Jurnal Pendapatan Parkir (M04)</div>
        <div class="sub-pbi">PBI-M06-02-01: Memastikan data transaksi gerbang parkir terintegrasi otomatis ke dalam pembukuan harian.</div>

        <?= $msg; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
            <a href="dashboardNonSewa.php" style="display: inline-flex; align-items: center; gap: 8px; color: #94A3B8; text-decoration: none; font-size: 13px; transition: 0.2s;" onmouseover="this.style.color='#00D4D8'" onmouseout="this.style.color='#94A3B8'">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
            </a>

            <form method="GET" style="display: flex; gap: 10px; align-items: center; background: rgba(255,255,255,0.02); padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                <input type="month" name="filter_bulan" value="<?= $periode_aktif ?>" style="background: #104A8F; color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 6px; font-size: 13px;">
                <button type="submit" style="background: #167E80; color: #fff; border: none; padding: 5px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Cari</button>
            </form>
        </div>

        <div style="overflow-x:auto;">
            <table class="tbl-parkir">
                <thead>
                    <tr>
                        <th>Tanggal Transaksi</th>
                        <th>Volume Kendaraan</th>
                        <th>Total Pendapatan Ter-capture</th>
                        <th>Status Sinkronisasi Jurnal</th>
                        <th style="text-align: center;">Aksi Pembukuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list_parkir)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#cbd5e1; padding:40px;">📂 Tidak ada data transaksi parkir pada periode bulan ini di sistem M04.</td></tr>
                    <?php else: ?>
                        <?php foreach ($list_parkir as $row): 
                            $is_completed = ($row['status_jurnal'] === 'completed');
                        ?>
                            <tr>
                                <td><strong><?= date('d F Y', strtotime($row['tanggal'])) ?></strong></td>
                                <td><?= number_format($row['total_kendaraan'], 0, ',', '.') ?> Kendaraan</td>
                                <td style="font-weight: 600; color: #00D4D8;">Rp <?= number_format($row['total_revenue'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($is_completed): ?>
                                        <span class="badge-status completed"><i class="fa-solid fa-circle-check"></i> Terjurnal (Auto)</span>
                                    <?php else: ?>
                                        <span class="badge-status pending"><i class="fa-solid fa-clock"></i> Belum Masuk Jurnal</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($is_completed): ?>
                                        <button class="btn-post" style="background:rgba(255,255,255,0.02); color:#4a5568; cursor:not-allowed; border: 1px solid rgba(255,255,255,0.05);" disabled><i class="fa-solid fa-check-double"></i> Synced</button>
                                    <?php else: ?>
                                        <form method="POST" style="margin:0;" onsubmit="return confirm('Apakah Anda yakin ingin melakukan posting jurnal pendapatan parkir untuk tanggal <?= date('d/m/Y', strtotime($row['tanggal'])) ?>?')">
                                            <input type="hidden" name="action" value="post_to_journal">
                                            <input type="hidden" name="summary_date" value="<?= $row['tanggal'] ?>">
                                            <input type="hidden" name="total_revenue" value="<?= $row['total_revenue'] ?>">
                                            <button type="submit" class="btn-post"><i class="fa-solid fa-share-from-square me-1"></i> Post ke Jurnal</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once '../../includes/navbarMO6.php'; 
?>
