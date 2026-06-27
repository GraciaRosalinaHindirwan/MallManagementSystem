<?php
/** @var mysqli $conn */

if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}

// ── PROTEKSI AUTHENTICATION (SIAP PAKAI) ──
/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeStaff') {
    header("Location: ../../index.php"); 
    exit();
}
*/

$_SESSION['role'] = 'financeStaff'; 
$_SESSION['nama'] = 'Staff Finance'; 

// Parameter konfigurasi untuk di-render oleh navbarMO6.php
$department_name = "M06 FINANCE";
$page_title = "Buku Besar (General Ledger)";
$user_name = $_SESSION['nama'];

$menu_items = [
    [
        'icon'        => 'fa-solid fa-chart-line',
        'label'       => 'Dashboard Non-Sewa',
        'link'        => 'dashboardNonSewa.php',
        'active_page' => 'dashboard_non_sewa'
    ],
    [
        'icon'        => 'fa-solid fa-book',
        'label'       => 'Buku Besar (GL)',
        'link'        => 'bukuBesar.php',
        'active_page' => 'buku_besar'
    ]
];

if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} else {
    require_once '../../config/connection.php';
}

date_default_timezone_set('Asia/Jakarta');

// Mengambil filter bulan, default ke bulan berjalan saat ini
if (isset($_GET['filter_bulan']) && !empty($_GET['filter_bulan'])) {
    $periode_aktif = $_GET['filter_bulan']; 
} else {
    $periode_aktif = date('Y-m'); 
}

// ── QUERY UTAMA: Menggabungkan ke 06_chart_of_accounts untuk mengambil account_code asli ──
$query_ledger = "
    SELECT 
        je.journal_number,
        je.journal_date AS tgl_jurnal,
        je.description,
        coa.account_code AS kode_tampil,
        jl.debit,
        jl.credit
    FROM 06_journal_lines jl
    INNER JOIN 06_journal_entries je ON jl.journal_entry_id = je.id
    LEFT JOIN 06_chart_of_accounts coa ON jl.account_id = coa.id
    WHERE DATE_FORMAT(je.journal_date, '%Y-%m') = ?
    ORDER BY je.journal_date ASC, je.id ASC, jl.id ASC
";

$stmt = $conn->prepare($query_ledger);
$stmt->bind_param('s', $periode_aktif);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_debit_all = 0;
$total_kredit_all = 0;

// Mulai mengunci output buffer agar ditangkap rapi oleh navbar
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

    .ledger-container { background: #011630; border: 1px solid rgba(255,255,255,.05); border-radius: 12px; padding: 1.5rem; margin-top: 10px; text-align: left; }
    .ledger-title { font-size: 18px; font-weight: 600; color: #FFB62A; margin-bottom: 4px; }
    .ledger-sub { font-size: 13px; color: #94A3B8; margin-bottom: 1.5rem; }
    
    .tbl-ledger { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
    .tbl-ledger th { background: rgba(255,255,255,.03); font-size: 11px; font-weight: 600; text-transform: uppercase; color: #FFB62A; padding: 12px; border-bottom: 2px solid rgba(255,255,255,.1); text-align: left; }
    .tbl-ledger td { padding: 12px; color: #fff; border-bottom: 1px solid rgba(255,255,255,.04); }
    
    .text-right { text-align: right; }
    .total-row { background: rgba(0, 212, 216, 0.05); font-weight: 600; color: #00D4D8 !important; }
    .total-row td { border-top: 2px solid #00D4D8; color: #00D4D8 !important; }
    .box-akun { background: #104A8F; padding: 4px 10px; border-radius: 4px; color: #fff; font-weight: 500; font-family: monospace; display: inline-block; border: 1px solid rgba(255,255,255,0.1); }
    
    .btn-m06 { background: #167E80; color: #fff; border: none; padding: 6px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.15s; }
    .btn-m06:hover { background: #0E5E60; }
    .input-month-m06 { background: #104A8F; color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 6px; font-size: 13px; }
    .input-month-m06:focus { outline: none; border-color: #00D4D8; }
</style>

<div class="container-fluid" style="padding-top: 10px;">
    <div class="ledger-container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <div class="ledger-title"><i class="fa-solid fa-folder-open text-info me-2"></i> Buku Besar (General Ledger)</div>
                <div class="ledger-sub">Ringkasan mutasi riil posting Debit dan Kredit seluruh akun keuangan Mall ERP.</div>
            </div>
            <div>
                <a href="dashboardNonSewa.php" style="display: inline-flex; align-items: center; gap: 8px; color: #94A3B8; text-decoration: none; font-size: 13px;" class="btn btn-outline-light btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>

        <form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: rgba(255,255,255,0.02); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
            <label for="filter_bulan" style="color: #cbd5e1; font-size: 13px; margin: 0;">Periode Pembukuan:</label>
            <input type="month" id="filter_bulan" name="filter_bulan" value="<?= $periode_aktif ?>" class="input-month-m06">
            <button type="submit" class="btn-m06"><i class="fa-solid fa-filter me-1"></i> Saring Data</button>
        </form>

        <div style="overflow-x:auto;">
            <table class="tbl-ledger">
                <thead>
                    <tr>
                        <th style="width: 110px;">Tanggal</th>
                        <th style="width: 160px;">No. Jurnal</th>
                        <th>Keterangan Transaksi</th>
                        <th style="width: 130px; text-align: center;">Kode Akun</th>
                        <th style="width: 150px; text-align: right;">Debit</th>
                        <th style="width: 150px; text-align: right;">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:#94a3b8; padding:50px;">
                                <i class="fa-solid fa-inbox d-block mb-3" style="font-size: 28px; color: #64748B;"></i>
                                Tidak ditemukan catatan mutasi jurnal yang diposting pada periode ini (<?= date('F Y', strtotime($periode_aktif)) ?>).
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $row): 
                            $total_debit_all += (float)$row['debit'];
                            $total_kredit_all += (float)$row['credit'];
                        ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['tgl_jurnal'])) ?></td>
                                <td><span style="font-family: monospace; color: #00D4D8; font-weight: 500;"><?= htmlspecialchars($row['journal_number']) ?></span></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td style="text-align: center;">
                                    <div class="box-akun"><?= htmlspecialchars($row['kode_tampil'] ?? '-') ?></div>
                                </td>
                                <td class="text-right" style="color: #22C55E; font-weight: 500;">
                                    <?= $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-' ?>
                                </td>
                                <td class="text-right" style="color: #EF4444; font-weight: 500;">
                                    <?= $row['credit'] > 0 ? 'Rp ' . number_format($row['credit'], 0, ',', '.') : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="total-row">
                            <td colspan="4" style="text-align: right; padding-right: 20px; font-weight: 700;">TOTAL MUTASI:</td>
                            <td class="text-right">Rp <?= number_format($total_debit_all, 0, ',', '.') ?></td>
                            <td class="text-right">Rp <?= number_format($total_kredit_all, 0, ',', '.') ?></td>
                        </tr>
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
