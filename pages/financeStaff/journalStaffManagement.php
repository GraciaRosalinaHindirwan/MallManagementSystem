<?php
/** @var mysqli $conn */

if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}

$_SESSION['role'] = 'financeStaff';
$_SESSION['nama'] = 'Finance Staff';

// Panggil koneksi database
if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} else {
    require_once '../../config/connection.php';
}

date_default_timezone_set('Asia/Jakarta');

// ── MENANGKAPI PARAMETER LEMPARAN DARI MENU LAIN (AUTO-FILL FORM) ──
$input_keterangan = "";
$input_debit = "";

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'fix_parkir') {
        $input_keterangan = "Jurnal Penyesuaian Selisih Kas Parkir Fisik vs Sistem";
        $input_debit = "150000"; 
    } elseif ($_GET['action'] == 'post_event') {
        $input_keterangan = "Pengakuan Pendapatan Atas Penyelesaian Event Ref #" . ($_GET['ref'] ?? '');
        $input_debit = $_GET['amt'] ?? '';
    } elseif ($_GET['action'] == 'post_iklan') {
        $input_keterangan = "Amortisasi Pendapatan Iklan Periode Ini Ref #" . ($_GET['ref'] ?? '');
        $input_debit = $_GET['amt'] ?? '';
    }
}

// Ambil data Akun (COA) - Menggunakan 'id' sesuai struktur asli database Anda
$query_coa = "SELECT id, account_code, account_name, account_type FROM 06_chart_of_accounts ORDER BY account_code ASC";
$res_coa = $conn->query($query_coa);
$coa_list = $res_coa ? $res_coa->fetch_all(MYSQLI_ASSOC) : [];

// QUERY MENAMPILKAN LOG DATA RIWAYAT JURNAL (30 TERAKHIR)
$query_history = "
    SELECT id, journal_number, journal_date, description, source_type, total_debit, total_credit, status
    FROM 06_journal_entries 
    ORDER BY journal_date DESC, id DESC 
    LIMIT 30
";
$res_history = $conn->query($query_history);
$history_entries = $res_history ? $res_history->fetch_all(MYSQLI_ASSOC) : [];

// CONFIG MASTER UNTUK REQUIRE NAVBAR M06
$department_name = "Finance Department"; 
$user_name = $_SESSION['nama'] ?? "Finance Staff";
$page_title = "Pencatatan & Posting Jurnal";

$menu_items = [
    [
        'icon'        => 'fa-solid fa-chart-pie',
        'label'       => 'Dashboard Staff',
        'link'        => 'dashboardStaff.php',
        'active_page' => 'Dashboard Staff'
    ],
    [
        'icon'        => 'fa-solid fa-file-invoice',
        'label'       => 'Invoice Management',
        'link'        => 'invoiceManagement.php',
        'active_page' => 'Invoice Management'
    ],
    [
        'icon'        => 'fa-solid fa-bolt-lightning', 
        'label'       => 'Invoice Utilitas (Air/Listrik)',
        'link'        => 'utility_invoice.php', 
        'active_page' => 'utility_invoice'
    ],
    [
        'icon'        => 'fa-solid fa-cash-register',
        'label'       => 'Billing System',
        'link'        => 'billingManagement.php',
        'active_page' => 'Billing System'
    ],
    [
        'icon'        => 'fa-solid fa-book',
        'label'       => 'Jurnal Otomatis',
        'link'        => 'journalManagement.php',
        'active_page' => 'Jurnal Otomatis'
    ],
    [
        'icon'        => 'fa-solid fa-book-open',
        'label'       => 'Buku Besar (GL)',
        'link'        => 'bukuBesar.php',
        'active_page' => 'Buku Besar'
    ],
    [
        'icon'        => 'fa-solid fa-folder-open',
        'label'       => 'Dashboard Non Sewa',
        'link'        => 'dashboardNonSewa.php',
        'active_page' => 'Dashboard Non Sewa'
    ]
];

ob_start();
?>

<style>
    :root { --primary-color: #021F42 !important; --bg-dark: #021F42 !important; }
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #fff !important; }
    .sidebar { background-color: #011630 !important; border-right: 1px solid rgba(255,255,255,0.05); }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff !important; }
    .page-title { color: #FFB62A !important; font-weight: 700; font-size: 22px; margin: 0; }
    .page-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #00D4D8; margin-bottom: 4px; }
    .page-sub { font-size: 13px; color: #94A3B8; margin-top: 4px; margin-bottom: 1.5rem; }
    .form-card { background: #011630; border: 1px solid rgba(255,255,255,.05); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
    .form-group { margin-bottom: 1.25rem; }
    .form-label { display: block; font-size: 13px; font-weight: 500; color: #94A3B8; margin-bottom: 6px; }
    .form-control-m06 { width: 100%; background: #104A8F; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 10px 14px; color: #fff; font-size: 13px; transition: border-color 0.15s; }
    .form-control-m06:focus { outline: none; border-color: #00D4D8; background: #0c3d75; }
    .btn-submit { background: #167E80; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: background 0.15s; }
    .btn-submit:hover { background: #0e5e60; }
    .grid-cols-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media(max-width:600px){ .grid-cols-2 { grid-template-columns: 1fr; } }
    .tbl-jurnal { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
    .tbl-jurnal th { color: #FFB62A; font-size: 13px; font-weight: 600; padding: 12px; border-bottom: 2px solid rgba(255,255,255,.1); text-align: left; }
    .tbl-jurnal td { padding: 12px; color: #fff; border-bottom: 1px solid rgba(255,255,255,.05); }
    .badge-source { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; text-transform: uppercase; }
    .badge-source.manual { background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.3); }
    .badge-source.parking { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
    .badge-source.event { background: rgba(251, 146, 60, 0.15); color: #fb923c; border: 1px solid rgba(251, 146, 60, 0.3); }
    .badge-source.ad { background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
</style>

<div class="container-fluid" style="text-align: left; padding-top: 10px;">

    <?php 
    if (isset($_SESSION['success_msg'])) { echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); }
    if (isset($_SESSION['error_msg'])) { echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); }
    ?>

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="page-eyebrow">General Ledger — Akuntansi Mall ERP</div>
            <h1 class="page-title"><i class="fa-solid fa-book-bookmark text-info me-2"></i>Pencatatan &amp; Posting Jurnal</h1>
            <p class="page-sub">Formulir penjurnalan harian pendapatan non-sewa dan penyesuaian akun pembukuan keuangan.</p>
        </div>
        <div>
            <a href="dashboardNonSewa.php" class="btn-submit" style="background:transparent; color:#94A3B8; border:1px solid rgba(255,255,255,.1); padding:8px 16px; border-radius:6px; font-size:13px; text-decoration:none; display: inline-block;">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="form-card">
        <h3 style="color:#FFB62A; font-size:15px; margin-top:0; margin-bottom:1.25rem;"><i class="fa-solid fa-pen-to-square me-2"></i>Input Entri Jurnal Baru</h3>
        
        <form action="processJournal.php" method="POST">
            <div class="grid-cols-2">
                <div class="form-group">
                    <label class="form-label">Tanggal Transaksi / Jurnal</label>
                    <input type="date" class="form-control-m06" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor Dokumen Sumber / Referensi</label>
                    <input type="text" class="form-control-m06" name="reference_no" placeholder="Otomatis jika dikosongkan" value="<?= isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : '' ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Keterangan Transaksi</label>
                <input type="text" class="form-control-m06" name="keterangan" placeholder="Tulis deskripsi detail transaksi..." value="<?= htmlspecialchars($input_keterangan) ?>" required>
            </div>

            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 1.25rem; border-radius: 8px; margin-bottom: 1.25rem;">
                <p style="color: #00D4D8; font-size: 12px; margin-top:0; font-weight:600;"><i class="fa-solid fa-scale-balanced me-1"></i> ALOKASI AKUN (DOUBLE ENTRY)</p>
                
                <div class="grid-cols-2">
                    <div class="form-group">
                        <label class="form-label" style="color: #00D4D8;">Akun Sisi DEBIT</label>
                        <select class="form-control-m06" name="akun_debit" required>
                            <option value="">-- Pilih Akun Debit --</option>
                            <?php foreach($coa_list as $coa): ?>
                                <option value="<?= $coa['id'] ?>" <?= (isset($_GET['action']) && $_GET['action'] == 'fix_parkir' && $coa['account_code'] == '5-1004') || (isset($_GET['action']) && $_GET['action'] == 'post_event' && $coa['account_code'] == '1-2001') || (isset($_GET['action']) && $_GET['action'] == 'post_iklan' && $coa['account_code'] == '1-1002') ? 'selected' : '' ?>>
                                    <?= $coa['account_code'] ?> - <?= htmlspecialchars($coa['account_name']) ?> (<?= $coa['account_type'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nominal Debit (Rp)</label>
                        <input type="number" class="form-control-m06" id="nominal_debit" name="nominal_debit" step="0.01" min="1" placeholder="0" value="<?= htmlspecialchars($input_debit) ?>" required oninput="autoMatchKredit()">
                    </div>
                </div>

                <div class="grid-cols-2" style="margin-top:0.5rem;">
                    <div class="form-group">
                        <label class="form-label" style="color: #FFB62A;">Akun Sisi KREDIT</label>
                        <select class="form-control-m06" name="akun_kredit" required>
                            <option value="">-- Pilih Akun Kredit --</option>
                            <?php foreach($coa_list as $coa): ?>
                                <option value="<?= $coa['id'] ?>" <?= (isset($_GET['action']) && $_GET['action'] == 'post_event' && $coa['account_code'] == '4-1003') || (isset($_GET['action']) && $_GET['action'] == 'post_iklan' && $coa['account_code'] == '4-1001') || (isset($_GET['action']) && $_GET['action'] == 'fix_parkir' && $coa['account_code'] == '1-1001') ? 'selected' : '' ?>>
                                    <?= $coa['account_code'] ?> - <?= htmlspecialchars($coa['account_name']) ?> (<?= $coa['account_type'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nominal Kredit (Rp)</label>
                        <input type="number" class="form-control-m06" id="nominal_kredit" name="nominal_kredit" step="0.01" min="1" placeholder="0" value="<?= htmlspecialchars($input_debit) ?>" required readonly>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span style="color: #94A3B8; font-size: 12px;"><i class="fa-solid fa-shield-halved text-success me-1"></i> Balance check otomatis aktif sebelum submit</span>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Simpan &amp; Posting ke Jurnal
                </button>
            </div>
        </form>
    </div>

    <div class="form-card">
        <h3 style="color:#FFB62A; font-size:15px; margin-top:0; margin-bottom:1.25rem;"><i class="fa-solid fa-clock-rotate-left me-2"></i>Log Riwayat Transaksi Jurnal</h3>
        <div style="overflow-x:auto;">
            <table class="tbl-jurnal">
                <thead>
                    <tr>
                        <th>Tanggal Jurnal</th>
                        <th>No. Jurnal (#Ref)</th>
                        <th>Keterangan / Deskripsi Transaksi</th>
                        <th>Tipe</th>
                        <th>Total Debit</th>
                        <th>Total Kredit</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history_entries)): ?>
                        <tr><td colspan="7" style="text-align:center; color:#64748B; padding:30px;">📂 Belum ada data jurnal yang tercatat.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history_entries as $row): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['journal_date'])) ?></td>
                                <td><span class="font-monospace text-info"><?= htmlspecialchars($row['journal_number']) ?></span></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><span class="badge-source <?= $row['source_type'] ?>"><?= $row['source_type'] ?></span></td>
                                <td style="color: #00D4D8; font-weight: 500;">Rp <?= number_format($row['total_debit'], 0, ',', '.') ?></td>
                                <td style="color: #FFB62A; font-weight: 500;">Rp <?= number_format($row['total_credit'], 0, ',', '.') ?></td>
                                <td style="text-align: center;">
                                    <span style="color: #10b981; font-size: 11px; background: rgba(16, 185, 129, 0.1); padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.2);">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function autoMatchKredit() {
    document.getElementById('nominal_kredit').value = document.getElementById('nominal_debit').value;
}
</script>

<?php 
$content = ob_get_clean();
require_once '../../includes/navbarM06.php'; 
?>