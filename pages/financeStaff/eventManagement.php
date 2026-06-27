<<<<<<< HEAD
<?php
/** @var mysqli $conn */ // Memberitahu VS Code kalau $conn itu objek database sah!

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

if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $periode_aktif = $_GET['filter_bulan']; 
} else {
    $periode_aktif = date('Y-m'); 
}

$msg = '';
if (isset($_POST['action']) && $_POST['action'] === 'post_event_journal') {
    $id_booking = (int)$_POST['id_booking'];
    $nama_event = $_POST['nama_event'];
    $total_revenue = (float)$_POST['total_revenue'];
    $tanggal_selesai = $_POST['tanggal_selesai'];

    if ($total_revenue > 0) {
        $cek_jurnal = $conn->prepare("SELECT id FROM 06_journal_entries WHERE description LIKE ? AND source_type = 'event'");
        $search_desc = "%ID Booking: " . $id_booking . "%";
        $cek_jurnal->bind_param('s', $search_desc);
        $cek_jurnal->execute();
        $res_cek = $cek_jurnal->get_result();

        if ($res_cek->num_rows > 0) {
            $msg = "<div class='alert danger' style='background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px;'>Gagal! Pendapatan untuk event '{$nama_event}' sudah pernah dijurnal sebelumnya.</div>";
        } else {
            $conn->begin_transaction();
            try {
                $keterangan = "Pendapatan Rekap Event: {$nama_event} (ID Booking: {$id_booking}) - Gabungan Tiket & Sponsor Lunas";
                $source_type = 'event';
                $status_jurnal = 'posted';
                $jurnal_date = date('Y-m-d', strtotime($tanggal_selesai));
                
                $journal_number = "JV-EV-" . date('YmdHis') . "-" . sprintf("%03d", $id_booking);

                // A. INSERT KE TABEL HEADER
                $stmt_jur = $conn->prepare("
                    INSERT INTO 06_journal_entries (journal_number, journal_date, description, source_type, total_debit, total_credit, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_jur->bind_param('ssssdds', $journal_number, $jurnal_date, $keterangan, $source_type, $total_revenue, $total_revenue, $status_jurnal);
                $stmt_jur->execute();

                $journal_entry_id = $conn->insert_id;

                // B. INSERT DETAIL - DEBIT (ID 3: Piutang Sewa)
                $id_akun_debit = 3; 
                $stmt_debit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, 0)");
                $stmt_debit->bind_param("iid", $journal_entry_id, $id_akun_debit, $total_revenue);
                $stmt_debit->execute();

                // C. INSERT DETAIL - KREDIT (ID 7: Pendapatan Event)
                $id_akun_kredit = 7;
                $stmt_kredit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, 0, ?)");
                $stmt_kredit->bind_param("iid", $journal_entry_id, $id_akun_kredit, $total_revenue);
                $stmt_kredit->execute();

                $conn->commit();
                $msg = "<div class='alert success' style='background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px;'>Berhasil! Total Pendapatan event '{$nama_event}' sebesar Rp " . number_format($total_revenue, 0, ',', '.') . " telah dibukukan otomatis ke Buku Besar.</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='alert danger' style='background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 10px; border-radius: 6px; margin-bottom: 15px;'>Terjadi kesalahan sistem: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// ── 2. QUERY UTAMA REKAP EVENT ──
$query_event = "
    SELECT 
        b.id_booking,
        b.nama_event,
        b.tipe_event,
        b.tanggal_selesai,
        IFNULL((SELECT SUM(s.nilai) FROM 04_event_sponsorship s WHERE s.id_booking = b.id_booking AND s.status_bayar = 'lunas'), 0) AS total_sponsorship,
        IFNULL((SELECT SUM(t.pendapatan) FROM 04_event_tiket t WHERE t.id_booking = b.id_booking), 0) AS total_tiket,
        (SELECT COUNT(*) FROM 06_journal_entries j WHERE j.description LIKE CONCAT('%ID Booking: ', b.id_booking, '%') AND j.source_type = 'event') AS sudah_dijurnal
    FROM 04_event_booking b
    WHERE b.status = 'approved' 
      AND DATE_FORMAT(b.tanggal_selesai, '%Y-%m') = ?
    ORDER BY b.tanggal_selesai DESC
";

$stmt = $conn->prepare($query_event);
$stmt->bind_param('s', $periode_aktif);
$stmt->execute();
$list_event = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$department_name = "Finance Department"; 
$user_name = $_SESSION['nama'] ?? "Finance Staff";
$page_title = "Pembukuan Pendapatan Event & Tiket";

$menu_items = [
    [
        'icon' => 'fa-solid fa-calendar-check',
        'label' => 'Pembukuan Event',
        'link' => 'eventManagement.php',
        'active_page' => 'eventManagement'
    ]
];

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
    .topbar-user { color: #fff !important; }
    .table-responsive-custom { background: #011630; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 15px; margin-top: 20px; }
    .table-m06 { width: 100%; color: #fff; border-collapse: collapse; }
    .table-m06 th { color: #FFB62A; border-bottom: 2px solid rgba(255,255,255,0.1); padding: 12px; font-size: 14px; text-align: left; }
    .table-m06 td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
    .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .btn-action { padding: 5px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; display: inline-block; border: none; cursor: pointer; }
</style>

<div class="container-fluid" style="text-align: left;">
    <div class="title-pbi" style="font-size: 18px; font-weight: 600; color: #FFB62A; margin-bottom: 5px;">
        <i class="fa-solid fa-calendar-check text-info me-2"></i> Pembukuan Pendapatan Event & Tiket
    </div>
    <div class="sub-pbi" style="font-size: 13px; color: #64748B; margin-bottom: 20px;">
        PBI-M06-02-02: Sinkronisasi data omset riil (Sponsorship Lunas + Penjualan Tiket) setelah event selesai.
    </div>

    <?= $msg; ?>

    <a href="dashboardNonSewa.php" style="display: inline-flex; align-items: center; gap: 8px; color: #94A3B8; text-decoration: none; font-size: 13px; margin-bottom: 20px; transition: 0.2s;">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
    </a>

    <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: #011630; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
        <input type="month" name="filter_bulan" value="<?= $periode_aktif ?>" style="background: #104A8F; color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 6px; font-size: 13px;">
        <button type="submit" style="background: #167E80; color: #fff; border: none; padding: 6px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Cari</button>
    </form>

    <div class="table-responsive-custom">
        <table class="table-m06">
            <thead>
                <tr>
                    <th>Nama Event</th>
                    <th>Tanggal Selesai</th>
                    <th>Omset Sponsor</th>
                    <th>Omset Tiket</th>
                    <th>Total Gabungan</th>
                    <th>Status Jurnal</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_event)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #cbd5e1;">📂 Tidak ada data event tercatat dalam sistem.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($list_event as $row): 
                        $total_gabungan = (float)$row['total_sponsorship'] + (float)$row['total_tiket'];
                        $is_synced = ($row['sudah_dijurnal'] > 0);
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['nama_event']) ?></strong></td>
                            <td style="color: #cbd5e1;"><?= date('d M Y', strtotime($row['tanggal_selesai'])) ?></td>
                            <td>Rp <?= number_format($row['total_sponsorship'], 0, ',', '.') ?></td>
                            <td>Rp <?= number_format($row['total_tiket'], 0, ',', '.') ?></td>
                            <td style="font-weight: 600; color: #00D4D8;">Rp <?= number_format($total_gabungan, 0, ',', '.') ?></td>
                            <td>
                                <?php if ($is_synced): ?>
                                    <span class="badge-status" style="background-color: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);">Terjurnal</span>
                                <?php else: ?>
                                    <span class="badge-status" style="background-color: rgba(255, 182, 42, 0.15); color: #FFB62A; border: 1px solid rgba(255, 182, 42, 0.3);">Siap</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <?php if ($is_synced): ?>
                                    <button class="btn-action" disabled style="background: rgba(255,255,255,0.02); color: #4a5568;"><i class="fa-solid fa-check-double"></i> Synced</button>
                                <?php else: ?>
                                    <form method="POST" style="margin:0; display: inline-block;">
                                        <input type="hidden" name="action" value="post_event_journal">
                                        <input type="hidden" name="id_booking" value="<?= $row['id_booking'] ?>">
                                        <input type="hidden" name="nama_event" value="<?= htmlspecialchars($row['nama_event']) ?>">
                                        <input type="hidden" name="total_revenue" value="<?= $total_gabungan ?>">
                                        <input type="hidden" name="tanggal_selesai" value="<?= $row['tanggal_selesai'] ?>">
                                        <button type="submit" class="btn-action" style="background: #167E80; color: #fff;">
                                            <i class="fa-solid fa-book"></i> Bukukan Jurnal
                                        </button>
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

<?php 
$content = ob_get_clean();
require_once '../../includes/navbarMO6.php'; 
?>
=======
<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once '../../config/koneksi.php';

date_default_timezone_set('Asia/Jakarta');

if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $periode_aktif = $_GET['filter_bulan']; 
} else {
    $periode_aktif = date('Y-m'); 
}

$msg = '';

// ── 1. PROSES POSTING PENDAPATAN EVENT KE JURNAL (OTOMATIS - MENGGUNAKAN ID ASLI) ──
if (isset($_POST['action']) && $_POST['action'] === 'post_event_journal') {
    $id_booking = (int)$_POST['id_booking'];
    $nama_event = $_POST['nama_event'];
    $total_revenue = (float)$_POST['total_revenue'];
    $tanggal_selesai = $_POST['tanggal_selesai'];

    if ($total_revenue > 0) {
        $cek_jurnal = $conn->prepare("SELECT id FROM 06_journal_entries WHERE description LIKE ? AND source_type = 'event'");
        $search_desc = "%ID Booking: " . $id_booking . "%";
        $cek_jurnal->bind_param('s', $search_desc);
        $cek_jurnal->execute();
        $res_cek = $cek_jurnal->get_result();

        if ($res_cek->num_rows > 0) {
            $msg = "<div class='alert danger'>Gagal! Pendapatan untuk event '{$nama_event}' sudah pernah dijurnal sebelumnya.</div>";
        } else {
            $conn->begin_transaction();
            try {
                $keterangan = "Pendapatan Rekap Event: {$nama_event} (ID Booking: {$id_booking}) - Gabungan Tiket & Sponsor Lunas";
                $source_type = 'event';
                $status_jurnal = 'posted';
                $jurnal_date = date('Y-m-d', strtotime($tanggal_selesai));
                
                $journal_number = "JV-EV-" . date('YmdHis') . "-" . sprintf("%03d", $id_booking);

                // A. INSERT KE TABEL HEADER
                $stmt_jur = $conn->prepare("
                    INSERT INTO 06_journal_entries (journal_number, journal_date, description, source_type, total_debit, total_credit, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_jur->bind_param('ssssdds', $journal_number, $jurnal_date, $keterangan, $source_type, $total_revenue, $total_revenue, $status_jurnal);
                $stmt_jur->execute();

                $journal_entry_id = $conn->insert_id;

                // B. INSERT DETAIL - DEBIT (ID 3: Piutang Sewa)
                $id_akun_debit = 3; 
                $stmt_debit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, 0)");
                $stmt_debit->bind_param("iid", $journal_entry_id, $id_akun_debit, $total_revenue); // Menggunakan "iid"
                $stmt_debit->execute();

                // C. INSERT DETAIL - KREDIT (ID 7: Pendapatan Event)
                $id_akun_kredit = 7;
                $stmt_kredit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, 0, ?)");
                $stmt_kredit->bind_param("iid", $journal_entry_id, $id_akun_kredit, $total_revenue); // Menggunakan "iid"
                $stmt_kredit->execute();

                $conn->commit();
                $msg = "<div class='alert success'>Berhasil! Total Pendapatan event '{$nama_event}' sebesar Rp " . number_format($total_revenue, 0, ',', '.') . " telah dibukukan otomatis ke Buku Besar.</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='alert danger'>Terjadi kesalahan sistem: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// ── 2. QUERY UTAMA REKAP EVENT ──
$query_event = "
    SELECT 
        b.id_booking,
        b.nama_event,
        b.tipe_event,
        b.tanggal_selesai,
        IFNULL((SELECT SUM(s.nilai) FROM 04_event_sponsorship s WHERE s.id_booking = b.id_booking AND s.status_bayar = 'lunas'), 0) AS total_sponsorship,
        IFNULL((SELECT SUM(t.pendapatan) FROM 04_event_tiket t WHERE t.id_booking = b.id_booking), 0) AS total_tiket,
        (SELECT COUNT(*) FROM 06_journal_entries j WHERE j.description LIKE CONCAT('%ID Booking: ', b.id_booking, '%') AND j.source_type = 'event') AS sudah_dijurnal
    FROM 04_event_booking b
    WHERE b.status = 'approved' 
      AND DATE_FORMAT(b.tanggal_selesai, '%Y-%m') = ?
    ORDER BY b.tanggal_selesai DESC
";

$stmt = $conn->prepare($query_event);
$stmt->bind_param('s', $periode_aktif);
$stmt->execute();
$list_event = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<!-- HTML Bagian Event Sesuai Kode Kamu -->
<div class="container-box">
    <div class="title-pbi"><i class="fa-solid fa-calendar-check text-info me-2"></i> Pembukuan Pendapatan Event & Tiket</div>
    <div class="sub-pbi">PBI-M06-02-02: Sinkronisasi data omset riil (Sponsorship Lunas + Penjualan Tiket) setelah event selesai.</div>

    <?= $msg; ?>

    <a href="dashboardNonSewa.php" style="display: inline-flex; align-items: center; gap: 8px; color: #94A3B8; text-decoration: none; font-size: 13px; margin-bottom: 20px; transition: 0.2s;">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
    </a>

    <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: rgba(255,255,255,0.03); padding: 10px; border-radius: 8px;">
        <input type="month" name="filter_bulan" value="<?= $periode_aktif ?>" style="background: #104A8F; color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 6px; font-size: 13px;">
        <button type="submit" style="background: #167E80; color: #fff; border: none; padding: 6px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Cari</button>
    </form>

    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse: collapse; font-size:13px; color:#fff;">
            <thead>
                <tr style="background: rgba(255,255,255,.03); text-align:left; color:#64748B;">
                    <th style="padding:12px;">Nama Event</th>
                    <th style="padding:12px;">Tanggal Selesai</th>
                    <th style="padding:12px;">Omset Sponsor</th>
                    <th style="padding:12px;">Omset Tiket</th>
                    <th style="padding:12px;">Total Gabungan</th>
                    <th style="padding:12px;">Status Jurnal</th>
                    <th style="padding:12px; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_event)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:30px; color:#64748B;">Tidak ada data event.</td></tr>
                <?php else: ?>
                    <?php foreach ($list_event as $row): 
                        $total_gabungan = (float)$row['total_sponsorship'] + (float)$row['total_tiket'];
                        $is_synced = ($row['sudah_dijurnal'] > 0);
                    ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
                            <td style="padding:12px;"><strong><?= htmlspecialchars($row['nama_event']) ?></strong></td>
                            <td style="padding:12px;"><?= date('d M Y', strtotime($row['tanggal_selesai'])) ?></td>
                            <td style="padding:12px;">Rp <?= number_format($row['total_sponsorship'], 0, ',', '.') ?></td>
                            <td style="padding:12px;">Rp <?= number_format($row['total_tiket'], 0, ',', '.') ?></td>
                            <td style="padding:12px; color:#00D4D8;">Rp <?= number_format($total_gabungan, 0, ',', '.') ?></td>
                            <td style="padding:12px;"><?= $is_synced ? 'Terjurnal' : 'Siap' ?></td>
                            <td style="padding:12px; text-align:center;">
                                <?php if ($is_synced): ?>
                                    <button disabled style="background:gray; color:#fff; padding:5px 10px; border:none; border-radius:4px;">Synced</button>
                                <?php else: ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="post_event_journal">
                                        <input type="hidden" name="id_booking" value="<?= $row['id_booking'] ?>">
                                        <input type="hidden" name="nama_event" value="<?= htmlspecialchars($row['nama_event']) ?>">
                                        <input type="hidden" name="total_revenue" value="<?= $total_gabungan ?>">
                                        <input type="hidden" name="tanggal_selesai" value="<?= $row['tanggal_selesai'] ?>">
                                        <button type="submit" style="background:#167E80; color:#fff; padding:5px 10px; border:none; border-radius:4px; cursor:pointer;">Bukukan Jurnal</button>
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

<?php include '../../includes/footer.php'; ?>
>>>>>>> main
