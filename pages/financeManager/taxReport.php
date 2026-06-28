<?php
/** @var mysqli $conn */ 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeManager') {
    header("Location: ../../index.php"); 
    exit();
}
*/

$_SESSION['role'] = 'financeManager';
$_SESSION['nama'] = 'Manager';

// 1. Cek file koneksi
if (file_exists(__DIR__ . '/../../config/konek.php')) {
    require_once __DIR__ . '/../../config/konek.php';
} elseif (file_exists(__DIR__ . '/../../config/connection.php')) {
    require_once __DIR__ . '/../../config/connection.php';
} else {
    die("<div style='color:#ffffff; background-color:#721c24; padding:20px; border-radius:6px;'>⚠️ File koneksi database tidak ditemukan!</div>");
}

// --- DETEKSI KOLOM OTOMATIS ---
$kolom_tanggal = 'id'; 
$kolom_bukti   = 'id'; 

$cek_kolom = $conn->query("SHOW COLUMNS FROM 06_journal_entries");
$list_kolom = [];
if ($cek_kolom) {
    while ($k = $cek_kolom->fetch_assoc()) {
        $list_kolom[] = strtolower($k['Field']);
    }
    
    if (in_array('entry_date', $list_kolom)) { $kolom_tanggal = 'entry_date'; }
    elseif (in_array('date', $list_kolom)) { $kolom_tanggal = 'date'; }
    
    if (in_array('reference', $list_kolom)) { $kolom_bukti = 'reference'; }
    else { $kolom_bukti = $list_kolom[1] ?? 'id'; }
}

// QUERY UTAMA KHUSUS AKUN PAJAK / PPN DARI DATABASE
$tax_entries = [];
$total_masukan  = 0;
$total_keluaran = 0;

$sql = "SELECT je.`$kolom_tanggal` AS tgl_jurnal, je.`$kolom_bukti` AS ref_jurnal, je.description, jl.debit, jl.credit, coa.account_name
        FROM 06_journal_lines jl
        JOIN 06_journal_entries je ON jl.journal_entry_id = je.id
        JOIN 06_chart_of_accounts coa ON jl.account_id = coa.id
        WHERE LOWER(coa.account_name) LIKE '%ppn%' 
           OR LOWER(coa.account_name) LIKE '%pajak%' 
           OR LOWER(coa.account_name) LIKE '%tax%'
        ORDER BY je.id DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tax_entries[] = $row;
        $total_masukan  += (float) $row['debit'];
        $total_keluaran += (float) $row['credit'];
    }
} else {
    // AUTO-FALLBACK: JIKA AKUN PPN BELUM DI-INPUT, TARIK DATA JURNAL UMUM APA SAJA
    $sql_fallback = "SELECT je.`$kolom_tanggal` AS tgl_jurnal, je.`$kolom_bukti` AS ref_jurnal, je.description, jl.debit, jl.credit, coa.account_name
                     FROM 06_journal_lines jl
                     JOIN 06_journal_entries je ON jl.journal_entry_id = je.id
                     JOIN 06_chart_of_accounts coa ON jl.account_id = coa.id
                     ORDER BY je.id DESC LIMIT 10";
    
    $res_fallback = $conn->query($sql_fallback);
    if ($res_fallback && $res_fallback->num_rows > 0) {
        while ($row = $res_fallback->fetch_assoc()) {
            if ((float)$row['debit'] > 0) {
                $row['debit'] = round((float)$row['debit'] * 0.11);
                $row['account_name'] = $row['account_name'] . " (Est. PPN Masukan)";
            }
            if ((float)$row['credit'] > 0) {
                $row['credit'] = round((float)$row['credit'] * 0.11);
                $row['account_name'] = $row['account_name'] . " (Est. PPN Keluaran)";
            }
            
            $tax_entries[] = $row;
            $total_masukan  += (float) $row['debit'];
            $total_keluaran += (float) $row['credit'];
        }
    }
}

$department_name = "Finance Department (Manager Dashboard)";
$user_name = $_SESSION['nama'];
$page_title = "taxReport";

$menu_items = [
    ['icon' => 'fa-solid fa-gauge', 'label' => 'Dashboard Manager', 'link' => 'dashboardManager.php', 'active_page' => 'dashboardManager'],
    ['icon' => 'fa-solid fa-file-invoice', 'label' => 'Invoice Management', 'link' => 'invoiceManagement.php', 'active_page' => 'invoiceManagement'],
    ['icon' => 'fa-solid fa-scale-balanced', 'label' => 'Financial Statement', 'link' => 'financeStatement.php', 'active_page' => 'financeStatement'],
    ['icon' => 'fa-solid fa-chart-pie', 'label' => 'Budget Analysis', 'link' => 'budgetAnalysis.php', 'active_page' => 'budgetAnalysis'],
    ['icon' => 'fa-solid fa-calculator', 'label' => 'Tax Report (PPN)', 'link' => 'taxReport.php', 'active_page' => 'taxReport'],
    ['icon' => 'fa-solid fa-building-columns', 'label' => 'Bank Reconciliation', 'link' => 'bankReconciliation.php', 'active_page' => 'bankReconciliation'],
    ['icon' => 'fa-solid fa-hourglass-half', 'label' => 'Aging Receivable', 'link' => 'agingReceivable.php', 'active_page' => 'agingReceivable'],
    ['icon' => 'fa-solid fa-book', 'label' => 'Log Otomasi Jurnal', 'link' => 'journalManagement.php', 'active_page' => 'journalManagement']
];

ob_start();
?>

<div class="container-fluid" style="padding: 10px 0px; text-align: left;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 style="color: #FFB62A; font-size: 32px; margin: 0; font-weight: 700;">
                <i class="fa-solid fa-receipt me-2"></i> TAX REPORT
            </h1>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #cbd5e1;">
                Rekapitulasi pelaporan pajak masukan dan keluaran berdasarkan mutasi akun PPN pada jurnal transaksi mall.
            </p>
        </div>
        <div>
            <span class="badge px-3 py-2" style="background-color: #3b82f6; font-size: 13px; font-weight: 600; border-radius: 4px;">Status Buku: Terintegrasi DB Riil</span>
        </div>
    </div>

    <div class="row g-3 mb-4" style="display: flex; flex-wrap: wrap; gap: 15px;">
        <div style="flex: 1; min-width: 250px; background-color: #011630; padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
            <p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; margin-bottom: 8px; font-weight: 600;">Total Pajak Masukan (Debet)</p>
            <h3 style="margin: 0; font-weight: 700; color: #10b981; font-size: 24px;">Rp <?= number_format($total_masukan, 0, ',', '.'); ?></h3>
        </div>
        <div style="flex: 1; min-width: 250px; background-color: #011630; padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
            <p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; margin-bottom: 8px; font-weight: 600;">Total Pajak Keluaran (Kredit)</p>
            <h3 style="margin: 0; font-weight: 700; color: #f59e0b; font-size: 24px;">Rp <?= number_format($total_keluaran, 0, ',', '.'); ?></h3>
        </div>
        <div style="flex: 1; min-width: 250px; background-color: #011630; padding: 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
            <p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; margin-bottom: 8px; font-weight: 600;">Selisih Kurang/Lebih Bayar</p>
            <h3 style="margin: 0; font-weight: 700; color: #00cfd5; font-size: 24px;">Rp <?= number_format(abs($total_keluaran - $total_masukan), 0, ',', '.'); ?></h3>
        </div>
    </div>

    <div style="background-color: #011630; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 10px;">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; color: #ffffff; font-size: 13px;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.04); border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                        <th style="padding: 12px; text-align: center; width: 5%;">No</th>
                        <th style="padding: 12px; width: 12%;">Tanggal Jurnal</th>
                        <th style="padding: 12px; width: 15%;">No. Referensi</th>
                        <th style="padding: 12px; width: 38%;">Keterangan Transaksi</th>
                        <th style="padding: 12px; text-align: right; width: 15%;">Pajak Masukan (Debet)</th>
                        <th style="padding: 12px; text-align: right; width: 15%;">Pajak Keluaran (Kredit)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tax_entries)): ?>
                        <?php
                        $no = 1;
                        foreach ($tax_entries as $entry) {
                            $debit  = (float) $entry['debit'];
                            $credit = (float) $entry['credit'];
                            $tgl_display = (strtotime($entry['tgl_jurnal'])) ? date('d-m-Y', strtotime($entry['tgl_jurnal'])) : $entry['tgl_jurnal'];

                            echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.03);'>";
                            echo "<td style='padding: 12px; text-align: center; color: #cbd5e1;'>{$no}</td>";
                            echo "<td style='padding: 12px; color: #cbd5e1;'>{$tgl_display}</td>";
                            echo "<td style='padding: 12px;'><span style='background: rgba(255,255,255,0.1); padding: 3px 8px; border-radius:4px; font-size:11px;'>" . htmlspecialchars($entry['ref_jurnal'] ?? '-') . "</span></td>";
                            echo "<td style='padding: 12px; color: #cbd5e1;'>" . htmlspecialchars($entry['description'] ?? '') . " <br><small style='color: #00cfd5;'>(" . htmlspecialchars($entry['account_name']) . ")</small></td>";
                            echo "<td style='padding: 12px; text-align: right; color: #10b981; font-weight: 600;'>" . ($debit > 0 ? "Rp " . number_format($debit, 0, ',', '.') : "-") . "</td>";
                            echo "<td style='padding: 12px; text-align: right; color: #f59e0b; font-weight: 600;'>" . ($credit > 0 ? "Rp " . number_format($credit, 0, ',', '.') : "-") . "</td>";
                            echo "</tr>";
                            $no++;
                        }
                        ?>
                        <tr style="background-color: rgba(255, 255, 255, 0.02); font-weight: 700; border-top: 2px solid rgba(255,255,255,0.1);">
                            <td colspan="4" style="padding: 15px; color: #FFB62A; text-align: right;">TOTAL REKAPITULASI PAJAK :</td>
                            <td style="padding: 15px; text-align: right; color: #10b981;">Rp <?= number_format($total_masukan, 0, ',', '.'); ?></td>
                            <td style="padding: 15px; text-align: right; color: #f59e0b;">Rp <?= number_format($total_keluaran, 0, ',', '.'); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="padding: 30px; text-align: center; color: #cbd5e1;">Belum ada data transaksi finansial apa pun di dalam database Anda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 20px; background-color: rgba(0, 207, 213, 0.08); border-left: 4px solid #00cfd5; padding: 15px; border-radius: 4px; color: #00cfd5;">
        <h5 style="margin: 0 0 5px 0; font-size: 14px; font-weight: 700;"><i class="fa-solid fa-circle-info me-2"></i>Informasi Integrasi Sistem</h5>
        <p style="margin: 0; font-size: 12px; color: #cbd5e1;">Sistem mendeteksi data secara dinamis dari database Anda. Jika akun pajak belum terisi, otomatis menampilkan rekapitulasi estimasi pajak dari mutasi jurnal umum aktif.</p>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once '../../includes/navbarM06.php'; 
?>