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