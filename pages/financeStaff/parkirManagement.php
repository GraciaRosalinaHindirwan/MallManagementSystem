<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once '../../config/koneksi.php';

date_default_timezone_set('Asia/Jakarta');

// Mengambil filter bulan berjalan otomatis
$periode_aktif = date('Y-m');

// ── 1. PROSES POSTING JURNAL OTOMATIS (REAL DOUBLE-ENTRY) ──
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
            $msg = "<div class='alert danger'>Gagal! Pendapatan parkir tanggal ".date('d M Y', strtotime($tanggal_rekap))." sudah pernah dijurnal sebelumnya.</div>";
        } else {
            $conn->begin_transaction();
            try {
                // 1. Masukkan ringkasan ke tabel pembantu 06_daily_parking_summary
                $stmt_sum = $conn->prepare("
                    INSERT INTO 06_daily_parking_summary (summary_date, total_revenue, status) 
                    VALUES (?, ?, 'completed')
                    ON DUPLICATE KEY UPDATE total_revenue = ?, status = 'completed'
                ");
                $stmt_sum->bind_param('sdd', $tanggal_rekap, $total_pendapatan, $total_pendapatan);
                $stmt_sum->execute();

                // 2. Generate Nomor Jurnal Unik & Keterangan Akuntansi
                $journal_number = "JV-PK-" . date('YmdHis') . "-" . rand(100, 999);
                $keterangan = "Automated Rekap Pendapatan Parkir Tanggal " . date('d/m/Y', strtotime($tanggal_rekap));
                $source_type = 'parking';
                $status_jurnal = 'posted';

                // 3. INSERT KE TABEL HEADER (06_journal_entries)
                $stmt_jur = $conn->prepare("
                    INSERT INTO 06_journal_entries (journal_number, journal_date, description, source_type, total_debit, total_credit, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_jur->bind_param('ssssdds', $journal_number, $tanggal_rekap, $keterangan, $source_type, $total_pendapatan, $total_pendapatan, $status_jurnal);
                $stmt_jur->execute();

                // Ambil ID Header yang baru dimasukkan
                $journal_entry_id = $conn->insert_id;

                // 4. INSERT DETAIL DEBIT (ID 2: Bank BCA)
                $id_akun_debit = 2; // Sesuai ID di COA untuk Bank BCA
                $stmt_debit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, 0)");
                $stmt_debit->bind_param("iid", $journal_entry_id, $id_akun_debit, $total_pendapatan);
                $stmt_debit->execute();

                // 5. INSERT DETAIL KREDIT (ID 6: Pendapatan Parkir)
                $id_akun_kredit = 6; // Sesuai ID di COA untuk Pendapatan Parkir
                $stmt_kredit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, 0, ?)");
                $stmt_kredit->bind_param("iid", $journal_entry_id, $id_akun_kredit, $total_pendapatan);
                $stmt_kredit->execute();

                $conn->commit();
                $msg = "<div class='alert success'>Berhasil! Rekap tanggal ".date('d M Y', strtotime($tanggal_rekap))." sebesar Rp ".number_format($total_pendapatan,0,',','.')." telah otomatis masuk ke Buku Besar.</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='alert danger'>Terjadi kesalahan sistem: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// ── 2. QUERY MENAMPILKAN DATA TRANSAKSI PARKIR M04 ────────────────────────
$query_parkir = "
    SELECT 
        DATE(p.exit_time) AS tanggal,
        COUNT(p.id_transaksi) AS total_kendaraan,
        SUM(p.amount) AS total_revenue,
        s.status AS status_jurnal
    FROM 04_parking_transaksi p
    LEFT JOIN 06_daily_parking_summary s ON DATE(p.exit_time) = s.summary_date

    GROUP BY DATE(p.exit_time)
    ORDER BY DATE(p.exit_time) DESC
";

$stmt = $conn->prepare($query_parkir);

$stmt->execute();
$list_parkir = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<style>
.container-box { background: #0B376D; border: 1px solid rgba(255,255,255,.08); border-radius: 12px; padding: 1.5rem; margin-top: 1.5rem; }
.title-pbi { font-size: 18px; font-weight: 600; color: #fff; margin-bottom: 4px; }
.sub-pbi { font-size: 13px; color: #94A3B8; margin-bottom: 1.5rem; }
.tbl-parkir { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
.tbl-parkir th { background: rgba(255,255,255,.03); font-size: 11px; font-weight: 500; text-transform: uppercase; color: #64748B; padding: 12px; border-bottom: 1px solid rgba(255,255,255,.06); text-align: left; }
.tbl-parkir td { padding: 12px; color: #fff; border-bottom: 1px solid rgba(255,255,255,.04); }
.badge-status { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 500; padding: 3px 10px; border-radius: 99px; }
.badge-status.completed { background: rgba(34,197,94,.12); color: #22C55E; }
.badge-status.pending { background: rgba(255,182,42,.12); color: #FFB62A; }
.btn-post { background: #167E80; color: #fff; border: none; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; transition: 0.15s; }
.btn-post:hover { background: #0E5E60; }
.alert { padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 1rem; }
.alert.success { background: rgba(34,197,94,0.15); color: #22C55E; border: 1px solid rgba(34,197,94,0.3); }
.alert.danger { background: rgba(239,68,68,0.15); color: #EF4444; border: 1px solid rgba(239,68,68,0.3); }
</style>

<div class="container-box">
    <div class="title-pbi"><i class="fa-solid fa-square-parking text-info me-2"></i> Sinkronisasi Jurnal Pendapatan Parkir (M04)</div>
    <div class="sub-pbi">PBI-M06-02-01: Memastikan data transaksi gerbang parkir terintegrasi otomatis ke dalam pembukuan harian.</div>

    <?= $msg; ?>

    <a href="dashboardNonSewa.php" style="display: inline-flex; align-items: center; gap: 8px; color: #94A3B8; text-decoration: none; font-size: 13px; margin-bottom: 15px; transition: 0.2s;" onmouseover="this.style.color='#00D4D8'" onmouseout="this.style.color='#94A3B8'">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
    </a>

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
                    <tr><td colspan="5" style="text-align:center; color:#64748B; padding:30px;">Tidak ada data transaksi parkir bulan ini di tabel M04.</td></tr>
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
                                    <button class="btn-post" style="background:rgba(255,255,255,0.05); color:#64748B; cursor:not-allowed;" disabled>Synced</button>
                                <?php else: ?>
                                    <form method="POST" style="margin:0;">
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