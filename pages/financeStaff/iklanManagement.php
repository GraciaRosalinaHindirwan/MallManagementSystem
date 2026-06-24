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

// ── 1. PROSES POSTING PENDAPATAN IKLAN KE JURNAL (DOUBLE-ENTRY FIX) ──
if (isset($_POST['action']) && $_POST['action'] === 'post_ad_journal') {
    $id_kontrak = (int)$_POST['id_kontrak'];
    $advertiser_name = $_POST['advertiser_name'];
    $monthly_fee = (float)$_POST['monthly_fee'];
    $periode_tagihan = $_POST['periode_tagihan'];

    if ($monthly_fee > 0) {
        // Proteksi double-entry di jurnal harian berdasarkan kontrak dan periode
        $cek_jurnal = $conn->prepare("SELECT id FROM 06_journal_entries WHERE description LIKE ? AND source_type = 'ad'");
        $search_desc = "%ID Kontrak: " . $id_kontrak . " (Periode: " . $periode_tagihan . ")%";
        $cek_jurnal->bind_param('s', $search_desc);
        $cek_jurnal->execute();
        $res_cek = $cek_jurnal->get_result();

        if ($res_cek->num_rows > 0) {
            $msg = "<div class='alert danger'>Gagal! Pendapatan iklan '{$advertiser_name}' untuk periode {$periode_tagihan} sudah pernah dijurnal.</div>";
        } else {
            $conn->begin_transaction();
            try {
                $keterangan = "Pendapatan Iklan: {$advertiser_name} (ID Kontrak: {$id_kontrak}) (Periode: {$periode_tagihan})";
                $source_type = 'ad';
                $status_jurnal = 'posted';
                $jurnal_date = date('Y-m-d'); 

                // Generate otomatis nomor jurnal unik
                $journal_number = "JV-AD-" . date('YmdHis') . "-" . sprintf("%03d", $id_kontrak);

                // A. INSERT KE TABEL HEADER (06_journal_entries)
                $stmt_jur = $conn->prepare("
                    INSERT INTO 06_journal_entries (journal_number, journal_date, description, source_type, total_debit, total_credit, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_jur->bind_param('ssssdds', $journal_number, $jurnal_date, $keterangan, $source_type, $monthly_fee, $monthly_fee, $status_jurnal);
                $stmt_jur->execute();

                // Ambil ID Header yang baru dimasukkan
                $journal_entry_id = $conn->insert_id;

                // B. INSERT DETAIL DEBIT (ID 2: Bank BCA)
                $id_akun_debit = 2; 
                $stmt_debit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, 0)");
                $stmt_debit->bind_param("iid", $journal_entry_id, $id_akun_debit, $monthly_fee);
                $stmt_debit->execute();

                // C. INSERT DETAIL KREDIT (ID 5: Pendapatan Sewa / Iklan)
                $id_akun_kredit = 5;
                $stmt_kredit = $conn->prepare("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, 0, ?)");
                $stmt_kredit->bind_param("iid", $journal_entry_id, $id_akun_kredit, $monthly_fee);
                $stmt_kredit->execute();

                $conn->commit();
                $msg = "<div class='alert success'>Berhasil! Pendapatan sewa iklan '{$advertiser_name}' sebesar Rp " . number_format($monthly_fee, 0, ',', '.') . " telah otomatis masuk ke Buku Besar (No: {$journal_number}).</div>";
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "<div class='alert danger'>Terjadi kesalahan sistem: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// ── 2. QUERY UTAMA MENGAMBIL KONTRAK IKLAN YANG AKTIF & PAID (FIXED MATCHING STATUS) ──
$query_ad = "
    SELECT 
        a.id,
        a.advertiser_name,
        a.ad_location,
        a.ad_type,
        a.monthly_fee,
        a.current_period,
        a.billing_status,
        (SELECT COUNT(*) FROM 06_journal_entries j WHERE j.description LIKE CONCAT('%(ID Kontrak: ', a.id, ')%Periode: ', a.current_period, '%') AND j.source_type = 'ad') AS sudah_dijurnal
    FROM 06_ad_contracts a
    WHERE a.status = 'active' 
      AND a.billing_status = 'paid'
      AND a.current_period = ?
    ORDER BY a.id ASC
";

$stmt = $conn->prepare($query_ad);
$stmt->bind_param('s', $periode_aktif);
$stmt->execute();
$list_ad = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<style>
.container-box { background: #0B376D; border: 1px solid rgba(255,255,255,.08); border-radius: 12px; padding: 1.5rem; margin-top: 1.5rem; }
.title-pbi { font-size: 18px; font-weight: 600; color: #fff; margin-bottom: 4px; }
.sub-pbi { font-size: 13px; color: #94A3B8; margin-bottom: 1.5rem; }
.tbl-ad { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
.tbl-ad th { background: rgba(255,255,255,.03); font-size: 11px; font-weight: 500; text-transform: uppercase; color: #64748B; padding: 12px; border-bottom: 1px solid rgba(255,255,255,.06); text-align: left; }
.tbl-ad td { padding: 12px; color: #fff; border-bottom: 1px solid rgba(255,255,255,.04); }
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
    <div class="title-pbi"><i class="fa-solid fa-rectangle-ad text-info me-2"></i> Pembukuan Pendapatan Kontrak Iklan & Billboard</div>
    <div class="sub-pbi">PBI-M06-02-03: Sinkronisasi berkala omset sewa space iklan komersial mall yang sudah lunas terbayar.</div>

    <?= $msg; ?>

    <a href="dashboardNonSewa.php" style="display: inline-flex; align-items: center; gap: 8px; color: #94A3B8; text-decoration: none; font-size: 13px; margin-bottom: 20px; transition: 0.2s;" onmouseover="this.style.color='#00D4D8'" onmouseout="this.style.color='#94A3B8'">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
    </a>

    <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: rgba(255,255,255,0.03); padding: 10px; border-radius: 8px;">
        <label for="filter_bulan" style="color: #fff; font-size: 13px;">Pilih Periode Tagihan Bulanan:</label>
        <input type="month" id="filter_bulan" name="filter_bulan" value="<?= $periode_aktif ?>" style="background: #104A8F; color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 6px; font-size: 13px;">
        <button type="submit" style="background: #167E80; color: #fff; border: none; padding: 6px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Cari</button>
    </form>

    <div style="overflow-x:auto;">
        <table class="tbl-ad">
            <thead>
                <tr>
                    <th>Nama Pengiklan (Advertiser)</th>
                    <th>Lokasi & Jenis Iklan</th>
                    <th>Periode Tagihan</th>
                    <th>Fee Bulanan (Omset)</th>
                    <th>Status Jurnal</th>
                    <th style="text-align: center;">Aksi Pembukuan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_ad)): ?>
                    <tr><td colspan="6" style="text-align:center; color:#64748B; padding:30px;">Tidak ada tagihan iklan yang berstatus PAID pada periode bulan ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($list_ad as $row): 
                        $is_synced = ($row['sudah_dijurnal'] > 0);
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['advertiser_name']) ?></strong></td>
                            <td>
                                <span><?= htmlspecialchars($row['ad_location']) ?></span><br>
                                <small style="color:#64748B; text-transform: capitalize;"><?= htmlspecialchars($row['ad_type']) ?></small>
                            </td>
                            <td><span style="font-family: monospace; color: #FFB62A;"><?= htmlspecialchars($row['current_period']) ?></span></td>
                            <td style="font-weight: 600; color: #00D4D8;">Rp <?= number_format($row['monthly_fee'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ($is_synced): ?>
                                    <span class="badge-status completed"><i class="fa-solid fa-circle-check"></i> Terjurnal</span>
                                <?php else: ?>
                                    <span class="badge-status pending"><i class="fa-solid fa-clock"></i> Siap Post</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($is_synced): ?>
                                    <button class="btn-post" style="background:rgba(255,255,255,0.05); color:#64748B; cursor:not-allowed;" disabled>Synced</button>
                                <?php else: ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="action" value="post_ad_journal">
                                        <input type="hidden" name="id_kontrak" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="advertiser_name" value="<?= htmlspecialchars($row['advertiser_name']) ?>">
                                        <input type="hidden" name="monthly_fee" value="<?= $row['monthly_fee'] ?>">
                                        <input type="hidden" name="periode_tagihan" value="<?= $row['current_period'] ?>">
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

<?php include '../../includes/footer.php'; ?>