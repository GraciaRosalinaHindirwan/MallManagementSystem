<?php
/** @var mysqli $conn */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['role'] = 'financeStaff';
$_SESSION['nama'] = 'Finance Staff';

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
            $msg = "<div class='alert danger' style='background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 12px; border-radius: 6px; margin-bottom: 15px;'><i class='fa-solid fa-circle-xmark me-2'></i>Gagal! Pendapatan untuk event '{$nama_event}' sudah pernah dijurnal sebelumnya.</div>";
        } else {
            $conn->begin_transaction();
            try {
                $keterangan = "Pendapatan Rekap Event: {$nama_event} (ID Booking: {$id_booking}) - Gabungan Tiket & Sponsor Lunas";
                $source_type = 'event';
                $status_jurnal = 'posted';
                $jurnal_date = date('Y-m-d', strtotime($tanggal_selesai));
                
                $journal_number = "JV-EV-" . date('YmdHis') . "-" . sprintf("%03d", $id_booking);

                $stmt_jur = $conn->prepare("
                    INSERT INTO 06_journal_entries (journal_number, journal_date, description, source_type, total_debit, total_credit, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_jur->bind_param('ssssdds', $journal_number, $jurnal_date, $keterangan, $source_type, $total_revenue, $total_revenue, $status_jurnal);
                $stmt_jur->execute();

                $journal_entry_id = $conn->insert_id;

                $id_akun_debit = 3; 
                $stmt_debit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, 0)");
                $stmt_debit->bind_param("iid", $journal_entry_id, $id_akun_debit, $total_revenue);
                $stmt_debit->execute();

                $id_akun_kredit = 7;
                $stmt_kredit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, 0, ?)");
                $stmt_kredit->bind_param("iid", $journal_entry_id, $id_akun_kredit, $total_revenue);
                $stmt_kredit->execute();

                $conn->commit();
                $msg = "<div class='alert success' style='background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 12px; border-radius: 6px; margin-bottom: 15px;'><i class='fa-solid fa-circle-check me-2'></i>Berhasil! Total Pendapatan event '{$nama_event}' sebesar Rp " . number_format($total_revenue, 0, ',', '.') . " telah dibukukan otomatis ke Buku Besar.</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='alert danger' style='background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 12px; border-radius: 6px; margin-bottom: 15px;'><i class='fa-solid fa-circle-xmark me-2'></i>Terjadi kesalahan sistem: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// ── 3. QUERY UTAMA REKAP EVENT ──
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
        'link' => $_SERVER['PHP_SELF'],
        'active_page' => 'eventManagement'
    ]
];

// Mulai tangkap konten halaman ke dalam buffer
ob_start();
?>

<style>
    :root {
        --primary-color: #021F42 !important;
        --bg-dark: #021F42 !important;
    }
    /* Mencegah tabrakan layout dan tumpang tindih */
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #fff !important; }
    .sidebar { background-color: #011630 !important; border-right: 1px solid rgba(255,255,255,0.05); }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff !important; }
    .page-title { color: #FFB62A !important; font-weight: 700; }
    
    .table-responsive-custom { background: #011630; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 15px; margin-top: 20px; }
    .table-m06 { width: 100%; color: #fff; border-collapse: collapse; }
    .table-m06 th { color: #FFB62A; border-bottom: 2px solid rgba(255,255,255,0.1); padding: 12px; font-size: 14px; text-align: left; }
    .table-m06 td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
    .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; }
    .btn-action { padding: 5px 12px; border-radius: 4px; font-size: 12px; text-decoration: none; display: inline-block; border: none; cursor: pointer; }
</style>

<div class="container-fluid" style="text-align: left; padding-top: 20px;">
    <div class="title-pbi" style="font-size: 18px; font-weight: 600; color: #FFB62A; margin-bottom: 5px;">
        <i class="fa-solid fa-calendar-check text-info me-2"></i> Pembukuan Pendapatan Event & Tiket
    </div>
    <div class="sub-pbi" style="font-size: 13px; color: #64748B; margin-bottom: 20px;">
        PBI-M06-02-02: Sinkronisasi data omset riil (Sponsorship Lunas + Penjualan Tiket) setelah event selesai.
    </div>

    <?= $msg; ?>

    <div class="mb-3">
        <a href="dashboardNonSewa.php" style="display: inline-flex; align-items: center; gap: 8px; color: #94A3B8; text-decoration: none; font-size: 13px; transition: 0.2s;">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: #011630; padding: 10px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); width: max-content;">
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
                        <td colspan="7" style="text-align: center; padding: 40px; color: #cbd5e1;">📂 Tidak ada data event pada periode transaksi ini.</td>
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
                                <button type="button" class="btn-action" style="background: rgba(255,255,255,0.05); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.1); margin-right: 5px;"
                                        data-bs-toggle="modal" data-bs-target="#modalDetailEvent"
                                        data-id="<?= $row['id_booking'] ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_event']) ?>"
                                        data-sponsor="<?= $row['total_sponsorship'] ?>"
                                        data-tiket="<?= $row['total_tiket'] ?>"
                                        data-total="<?= $total_gabungan ?>"
                                        data-synced="<?= $is_synced ? 'true' : 'false' ?>">
                                    <i class="fa-solid fa-eye"></i> Audit
                                </button>

                                <?php if ($is_synced): ?>
                                    <button class="btn-action" disabled style="background: rgba(255,255,255,0.02); color: #4a5568;"><i class="fa-solid fa-check-double"></i> Synced</button>
                                <?php else: ?>
                                    <form method="POST" id="form-post-<?= $row['id_booking'] ?>" style="margin:0; display: inline-block;">
                                        <input type="hidden" name="action" value="post_event_journal">
                                        <input type="hidden" name="id_booking" value="<?= $row['id_booking'] ?>">
                                        <input type="hidden" name="nama_event" value="<?= htmlspecialchars($row['nama_event']) ?>">
                                        <input type="hidden" name="total_revenue" value="<?= $total_gabungan ?>">
                                        <input type="hidden" name="tanggal_selesai" value="<?= $row['tanggal_selesai'] ?>">
                                        <button type="submit" class="btn-action" style="background: #167E80; color: #fff;" onclick="return confirm('Apakah Anda ingin membukukan pendapatan ke Buku Besar?')">
                                            <i class="fa-solid fa-book"></i> Jurnal
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

    <div class="modal fade" id="modalDetailEvent" tabindex="-1" aria-labelledby="modalDetailEventLabel" aria-hidden="true" style="color: #000;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="background: #011630; border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <h5 class="modal-title" id="modalDetailEventLabel" style="color: #FFB62A; font-weight: 600;">
                        <i class="fa-solid fa-magnifying-glass-dollar me-2"></i> Rincian Audit Omset Event
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="text-muted d-block font-monospace" style="font-size: 11px;">NAMA EVENT</label>
                        <span id="md_nama_event" class="fw-bold fs-5 text-info">-</span>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div style="background: rgba(255,255,255,0.02); padding: 12px; border-radius: 6px; border-left: 4px solid #167E80;">
                                <span class="text-muted d-block" style="font-size: 11px;">OMSET SPONSORSHIP (LUNAS)</span>
                                <strong id="md_sponsor" class="fs-5 text-white">Rp 0</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div style="background: rgba(255,255,255,0.02); padding: 12px; border-radius: 6px; border-left: 4px solid #00D4D8;">
                                <span class="text-muted d-block" style="font-size: 11px;">OMSET PENJUALAN TIKET</span>
                                <strong id="md_tiket" class="fs-5 text-white">Rp 0</strong>
                            </div>
                        </div>
                    </div>

                    <div style="background: rgba(255,182,42,0.05); padding: 15px; border-radius: 8px; border: 1px dashed rgba(255,182,42,0.3); text-align: center;">
                        <span class="text-muted d-block" style="font-size: 12px; font-weight: 600;">TOTAL AKUMULASI REVENUE</span>
                        <h3 id="md_total" style="color: #FFB62A; font-weight: 700; margin: 5px 0 0 0;">Rp 0</h3>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1);">
                    <button type="button" class="btn-action" style="background: rgba(255,255,255,0.1); color: #fff;" data-bs-dismiss="modal">Tutup</button>
                    <div id="md_action_wrapper"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const modalDetail = document.getElementById('modalDetailEvent');
    if (modalDetail) {
        modalDetail.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            
            const id = button.getAttribute('data-id');
            const nama = button.getAttribute('data-nama');
            const sponsor = parseFloat(button.getAttribute('data-sponsor'));
            const tiket = parseFloat(button.getAttribute('data-tiket'));
            const total = parseFloat(button.getAttribute('data-total'));
            const synced = button.getAttribute('data-synced') === 'true';
            
            const formatIDR = (num) => 'Rp ' + num.toLocaleString('id-ID');

            document.getElementById('md_nama_event').textContent = nama;
            document.getElementById('md_sponsor').textContent = formatIDR(sponsor);
            document.getElementById('md_tiket').textContent = formatIDR(tiket);
            document.getElementById('md_total').textContent = formatIDR(total);

            const actionWrapper = document.getElementById('md_action_wrapper');
            if (synced) {
                actionWrapper.innerHTML = `<button disabled class="btn-action" style="background:gray; color:#fff;"><i class="fa-solid fa-check-double"></i> Telah Terposting</button>`;
            } else {
                actionWrapper.innerHTML = `
                    <button type="button" class="btn-action" style="background: #167E80; color: #fff; font-weight:600;" onclick="triggerFormSubmit('${id}')">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Validasi & Posting Jurnal
                    </button>`;
            }
        });
    }
});

function triggerFormSubmit(bookingId) {
    const targetForm = document.getElementById('form-post-' + bookingId);
    if (targetForm) {
        if(confirm("Apakah data omset gabungan ini sudah divalidasi dengan benar?")) {
            targetForm.submit();
        }
    }
}
</script>

<?php 
$content = ob_get_clean();
require_once '../../includes/navbarMO6.php'; 
<<<<<<< Updated upstream
?>
=======
?>
>>>>>>> Stashed changes
