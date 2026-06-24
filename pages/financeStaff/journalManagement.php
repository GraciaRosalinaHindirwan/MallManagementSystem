<?php

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// ── MENANGKAPI PARAMETER LEMPARAN DARI MENU LAIN (AUTO-FILL FORM) ──
$input_keterangan = "";
$input_debit = "";
$input_kredit = "";

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'fix_parkir') {
        $input_keterangan = "Jurnal Penyesuaian Selisih Kas Parkir Fisik vs Sistem 10 Juni";
        $input_debit = "150000"; // Beban Selisih Kas
    } elseif ($_GET['action'] == 'post_event') {
        $input_keterangan = "Pengakuan Pendapatan Atas Penyelesaian Event Ref #" . ($_GET['ref'] ?? '');
        $input_debit = $_GET['amt'] ?? '';
    } elseif ($_GET['action'] == 'post_iklan') {
        $input_keterangan = "Amortisasi Pendapatan Iklan Periode Juni Ref #" . ($_GET['ref'] ?? '');
        $input_debit = $_GET['amt'] ?? '';
    }
}

include '../../includes/header.php';
include '../../includes/navbar.php';
?>

<style>
.page-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #00D4D8; margin-bottom: 4px; }
.page-title   { font-size: 22px; font-weight: 700; color: #fff; margin: 0; }
.page-sub     { font-size: 13px; color: #94A3B8; margin-top: 4px; }

.form-card { background: #0B376D; border: 1px solid rgba(255,255,255,.08); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
.form-group { margin-bottom: 1.25rem; }
.form-label { display: block; font-size: 13px; font-weight: 500; color: #94A3B8; margin-bottom: 6px; }
.form-control { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 14px; transition: border-color 0.15s; }
.form-control:focus { outline: none; border-color: #00D4D8; background: rgba(255,255,255,0.07); }

.btn-submit { background: #00D4D8; color: #021F42; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.15s; }
.btn-submit:hover { background: #167E80; color: #fff; }
.grid-cols-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media(max-width:600px){ .grid-cols-2 { grid-template-columns: 1fr; } }
</style>

<div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <div class="page-eyebrow">General Ledger — Akuntansi Mall ERP</div>
        <h1 class="page-title">Pencatatan &amp; Posting Jurnal</h1>
        <p class="page-sub">Formulir penjurnalan ganda untuk pendapatan non-sewa dan penyesuaian akun pembukuan keuangan.</p>
    </div>

    <div>
        <a href="dashboard.php" class="btn btn-sm" style="background:transparent;color:#94A3B8;border:1px solid rgba(255,255,255,.1);padding:8px 16px;border-radius:8px;font-size:13px; text-decoration:none;">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="form-card">
    <h3 style="color:#fff; font-size:16px; margin-top:0; margin-bottom:1.25rem;"><i class="fa-solid fa-book-bookmark text-info me-2"></i>Input Entri Jurnal Baru</h3>
    
    <form action="processJournal.php" method="POST">
        
        <div class="grid-cols-2">
            <div class="form-group">
                <label class="form-label">Tanggal Transaksi / Jurnal</label>
                <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nomor Dokumen Sumber / Referensi</label>
                <input type="text" class="form-control" name="reference_no" placeholder="Contoh: JV-202606-003" value="<?= isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : '' ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Keterangan Transaksi</label>
            <input type="text" class="form-control" name="keterangan" placeholder="Tulis deskripsi detail akun di sini..." value="<?= htmlspecialchars($input_keterangan) ?>" required>
        </div>

        <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; margin-bottom: 1.25rem;">
            <p style="color: #00D4D8; font-size: 12px; margin-top:0; font-weight:600;">ALOKASI AKUN (DOUBLE ENTRY)</p>
            
            <div class="grid-cols-2">
                <div class="form-group">
                    <label class="form-label">Akun Sisi DEBIT</label>
                    <select class="form-control" name="akun_debit">
                        <option value="6" <?= ($_GET['action'] ?? '') == 'fix_parkir' ? 'selected' : '' ?>>5-1004 - Beban Selisih Kas Operasional</option>
                        <option value="3" <?= ($_GET['action'] ?? '') == 'post_event' ? 'selected' : '' ?>>1-2001 - Piutang Usaha Sewa Event</option>
                        <option value="2" <?= ($_GET['action'] ?? '') == 'post_iklan' ? 'selected' : '' ?>>1-1002 - Piutang Usaha Iklan & Billboard / Bank BCA</option>
                        <option value="1" <?= ($_GET['action'] ?? '') == '' ? 'selected' : '' ?>>1-1001 - Kas Utama Mall</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nominal Debit (Rp)</label>
                    <input type="number" class="form-control" name="nominal_debit" placeholder="0" value="<?= htmlspecialchars($input_debit) ?>" required>
                </div>
            </div>

            <div class="grid-cols-2" style="margin-top:0.5rem;">
                <div class="form-group">
                    <label class="form-label">Akun Sisi KREDIT</label>
                    <select class="form-control" name="akun_kredit">
                        <option value="4" <?= ($_GET['action'] ?? '') == 'post_event' ? 'selected' : '' ?>>4-1003 - Pendapatan Sewa Space Event</option>
                        <option value="5" <?= ($_GET['action'] ?? '') == 'post_iklan' ? 'selected' : '' ?>>4-1001 - Pendapatan Iklan Berkala</option>
                        <option value="1" <?= ($_GET['action'] ?? '') == 'fix_parkir' ? 'selected' : '' ?>>1-1001 - Kas di Tangan (Kasir Parkir)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nominal Kredit (Rp)</label>
                    <input type="number" class="form-control" name="nominal_kredit" placeholder="0" value="<?= htmlspecialchars($input_debit) ?>" required>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted" style="font-size: 12px;"><i class="fa-solid fa-shield-halved me-1"></i> Balance check otomatis aktif sebelum submit</span>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Simpan &amp; Posting ke Jurnal
            </button>
        </div>

    </form>
</div>

<?php include '../../includes/footer.php'; ?>

/** @var mysqli $conn */ 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeStaff') {
    header("Location: ../../index.php"); 
    exit();
}
*/

// Sesi default sementara tetap dibiarkan di bawahnya agar aman dicoba sekarang
$_SESSION['role'] = 'financeStaff';
$_SESSION['nama'] = 'Staff';

// 1. Cek file koneksi
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} elseif (file_exists('../../config/connection.php')) {
    require_once '../../config/connection.php';
} else {
    die("<div style='color:#ffffff; background-color:#721c24; padding:20px; border-radius:6px;'>⚠️ File koneksi database tidak ditemukan!</div>");
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// 2. DETEKSI KOLOM SECARA AGRESIF AGAR AMAN DARI STRUKTUR KEPOTONG
$kolom_tanggal = 'id'; // Fallback paling aman jika kolom tanggal tidak ada
$kolom_bukti   = 'id'; // Fallback jika tidak ada kolom teks bukti
$kolom_ket     = 'id'; 

$cek_kolom = $conn->query("SHOW COLUMNS FROM 06_journal_entries");
$list_kolom = [];
if ($cek_kolom) {
    while ($k = $cek_kolom->fetch_assoc()) {
        $list_kolom[] = strtolower($k['Field']);
    }
    
    // Deteksi Kolom Tanggal
    if (in_array('entry_date', $list_kolom)) { $kolom_tanggal = 'entry_date'; }
    elseif (in_array('date', $list_kolom)) { $kolom_tanggal = 'date'; }
    elseif (in_array('created_at', $list_kolom)) { $kolom_tanggal = 'created_at'; }
    elseif (in_array('tanggal', $list_kolom)) { $kolom_tanggal = 'tanggal'; }
    
    // Deteksi Kolom No Bukti / Invoice Reference
    if (in_array('reference_number', $list_kolom)) { $kolom_bukti = 'reference_number'; }
    elseif (in_array('invoice_number', $list_kolom)) { $kolom_bukti = 'invoice_number'; }
    elseif (in_array('no_bukti', $list_kolom)) { $kolom_bukti = 'no_bukti'; }
    elseif (in_array('invoice_id', $list_kolom)) { $kolom_bukti = 'invoice_id'; }
    elseif (in_array('invoice_no', $list_kolom)) { $kolom_bukti = 'invoice_no'; }
    elseif (in_array('reference', $list_kolom)) { $kolom_bukti = 'reference'; }
    else {
        $kolom_bukti = $list_kolom[2] ?? $list_kolom[1] ?? 'id';
    }

    // Deteksi Kolom Keterangan / Deskripsi
    if (in_array('description', $list_kolom)) { $kolom_ket = 'description'; }
    elseif (in_array('keterangan', $list_kolom)) { $kolom_ket = 'keterangan'; }
    elseif (in_array('notes', $list_kolom)) { $kolom_ket = 'notes'; }
    else {
        $kolom_ket = $list_kolom[3] ?? $list_kolom[1] ?? 'id';
    }
}

// 3. JALANKAN QUERY DENGAN HASIL DETEKSI AMAN
$jurnals = false;
$error_msg = null;

// Cek apakah tabel journal_lines ada, jika tidak ada, bypass error
$cek_lines = $conn->query("SHOW TABLES LIKE '06_journal_lines'");
if ($cek_lines && $cek_lines->num_rows > 0) {
    $query_jurnal = "SELECT 
                        j.id,
                        j.`$kolom_tanggal` AS tgl_jurnal, 
                        j.`$kolom_bukti` AS bukti_jurnal, 
                        j.`$kolom_ket` AS ket_jurnal, 
                        c.account_code, 
                        c.account_name, 
                        jl.debit, 
                        jl.credit 
                     FROM 06_journal_entries j 
                     JOIN 06_journal_lines jl ON j.id = jl.journal_entry_id 
                     JOIN 06_chart_of_accounts c ON jl.account_id = c.id 
                     ORDER BY j.id DESC, jl.debit DESC";
    try {
        $jurnals = $conn->query($query_jurnal);
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
    }
} else {
    $error_msg = "Tabel `06_journal_lines` tidak ditemukan di database Anda (kemungkinan besar skrip SQL terpotong saat import).";
}
?>

<div class="content-container" style="padding: 20px; background: var(--bg-primary); min-height: 80vh; color: #ffffff;">
    
    <div class="mb-4" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
        <h1 style="color: var(--text-accent); font-size: var(--h1); margin: 0; font-weight: 700;">Log Otomasi Jurnal</h1>
        <p style="margin: 5px 0 0 0; font-size: 14px; color: #cbd5e1;">
            Sistem Pencatatan Akuntansi Berpasangan Otomatis *(Double-Entry Ledger System)*
        </p>
    </div>

    <div style="display: flex; gap: 15px; margin-bottom: 25px; max-width: 500px;">
        <div class="card-simple" style="flex: 1; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 12px 16px; border-radius: 6px;">
            <small style="color: #a0aec0; display: block; margin-bottom: 4px; font-size: 10px; font-weight: 600; letter-spacing: 0.5px;">SISTEM JURNAL</small>
            <span style="color: #ffffff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Aktif & Otomatis
            </span>
        </div>
        <div class="card-simple" style="flex: 1; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 12px 16px; border-radius: 6px;">
            <small style="color: #a0aec0; display: block; margin-bottom: 4px; font-size: 10px; font-weight: 600; letter-spacing: 0.5px;">BASIS AKUNTANSI</small>
            <span style="color: #ffffff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-scale-balanced" style="color: #3b82f6;"></i> Double-Entry (PSAK)
            </span>
        </div>
    </div>

    <div class="table-responsive" style="background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
        <table class="table-custom" style="width: 100%; border-collapse: collapse; margin: 0; color: #ffffff;">
            <thead>
                <tr style="background: rgba(255,255,255,0.04); text-align: left; border-bottom: 2px solid rgba(255,255,255,0.1);">
                    <th style="padding: 15px 12px; color: var(--text-accent); font-weight: 600; font-size: 14px;">Tanggal</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-weight: 600; font-size: 14px;">No Bukti / Invoice</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-weight: 600; font-size: 14px;">Keterangan Transaksi</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-weight: 600; font-size: 14px;">Kode Akun</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-weight: 600; font-size: 14px;">Nama Akun Akuntansi</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-weight: 600; font-size: 14px; text-align: right;">Debit</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-weight: 600; font-size: 14px; text-align: right;">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <?php if($jurnals && $jurnals->num_rows > 0): ?>
                    <?php while($row = $jurnals->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        
                        <td style="padding: 12px; font-size: 13px; color: #cbd5e1;">
                            <?= (strtotime($row['tgl_jurnal'])) ? date('d M Y', strtotime($row['tgl_jurnal'])) : $row['tgl_jurnal']; ?>
                        </td>
                        
                        <td style="padding: 12px;">
                            <span class="badge" style="background: rgba(255,255,255,0.1); color: #ffffff; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?= $row['bukti_jurnal'] ?? '-'; ?>
                            </span>
                        </td>
                        
                        <td style="padding: 12px; font-size: 13px; color: #cbd5e1; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= $row['ket_jurnal'] ?? '-'; ?>
                        </td>
                        
                        <td style="padding: 12px;">
                            <code style="color: var(--accent); font-size: 13px; font-family: monospace; background: rgba(241,196,15,0.05); padding: 2px 6px; border-radius: 4px;">
                                <?= $row['account_code']; ?>
                            </code>
                        </td>
                        
                        <td style="padding: 12px; font-size: 13px; color: #ffffff;">
                            <span style="<?= $row['credit'] > 0 ? 'padding-left: 20px; color: #cbd5e1;' : 'font-weight: 500; color: #ffffff;'; ?>">
                                <?= $row['account_name']; ?>
                            </span>
                        </td>
                        
                        <td class="text-success" style="padding: 12px; text-align: right; font-weight: 600; font-size: 13px; color: #10b981;">
                            <?= $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-'; ?>
                        </td>
                        
                        <td class="text-warning" style="padding: 12px; text-align: right; font-weight: 600; font-size: 13px; color: #f59e0b;">
                            <?= $row['credit'] > 0 ? 'Rp ' . number_format($row['credit'], 0, ',', '.') : '-'; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 50px; text-align: center; color: #ffffff;">
                            <span style="font-size: 40px;">📭</span> <br><br>
                            <strong style="color: #ffffff; font-size: 16px; display: block; margin-bottom: 5px;">Belum ada data log jurnal otomatis yang terbentuk di database.</strong>
                            <span style="color: #cbd5e1; display: block; margin-bottom: 20px;">Data akan terisi otomatis setelah Anda melakukan simulasi pelunasan tagihan.</span>
                            
                            <?php if($error_msg !== null): ?>
                                <div style="background-color: #721c24; color: #f8d7da; padding: 12px; border-radius: 6px; display: inline-block; max-width: 80%; border: 1px solid #f5c6cb; font-family: monospace; font-size: 13px; text-align: left;">
                                    <strong>⚠️ Pesan Sistem (Info Kolom Database):</strong><br>
                                    <?= $error_msg; ?>
                                    <?php if(!empty($list_kolom)): ?>
                                        <br><br><strong>Kolom yang terdeteksi di tabel kamu:</strong> [<?= implode(', ', $list_kolom); ?>]
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
>>>>>>> 8ae22b10bc0f61c3f5d2b110cae64285153316e7
