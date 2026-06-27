<?php
/** @var mysqli $conn */ // Memberitahu VS Code kalau $conn itu objek database sah!

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeStaff') {
    // Jika bukan Finance Staff, tendang kembali ke halaman utama login
    header("Location: ../../index.php"); 
    exit();
}
*/

$_SESSION['role'] = 'financeStaff';
$_SESSION['nama'] = 'Finance Staff';

// 1. Cek file koneksi
if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} elseif (file_exists('../../config/connection.php')) {
    require_once '../../config/connection.php';
} else {
    die("<div style='color:#ffffff; background-color:#721c24; padding:20px; border-radius:6px;'>⚠️ File koneksi database tidak ditemukan!</div>");
}

// 2. DETEKSI KOLOM SECARA AGRESIF AGAR AMAN DARI STRUKTUR KEPOTONG
$kolom_tanggal = 'id'; 
$kolom_bukti   = 'id'; 
$kolom_ket     = 'id'; 

$cek_kolom = $conn->query("SHOW COLUMNS FROM 06_journal_entries");
$list_kolom = [];
if ($cek_kolom) {
    while ($k = $cek_kolom->fetch_assoc()) {
        $list_kolom[] = strtolower($k['Field']);
    }
    
    if (in_array('entry_date', $list_kolom)) { $kolom_tanggal = 'entry_date'; }
    elseif (in_array('date', $list_kolom)) { $kolom_tanggal = 'date'; }
    elseif (in_array('created_at', $list_kolom)) { $kolom_tanggal = 'created_at'; }
    elseif (in_array('tanggal', $list_kolom)) { $kolom_tanggal = 'tanggal'; }
    
    if (in_array('reference_number', $list_kolom)) { $kolom_bukti = 'reference_number'; }
    elseif (in_array('invoice_number', $list_kolom)) { $kolom_bukti = 'invoice_number'; }
    elseif (in_array('no_bukti', $list_kolom)) { $kolom_bukti = 'no_bukti'; }
    elseif (in_array('invoice_id', $list_kolom)) { $kolom_bukti = 'invoice_id'; }
    elseif (in_array('invoice_no', $list_kolom)) { $kolom_bukti = 'invoice_no'; }
    elseif (in_array('reference', $list_kolom)) { $kolom_bukti = 'reference'; }
    else {
        $kolom_bukti = $list_kolom[2] ?? $list_kolom[1] ?? 'id';
    }

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

// ==========================================
// CONFIG MASTER UNTUK REQUIRE NAVBAR MENTAHAN
// ==========================================
$department_name = "Finance Department";
$user_name = $_SESSION['nama'] ?? "Finance Staff";
$page_title = "Log Otomasi Jurnal M06";
$menu_items = [
    [
        'icon'        => 'fa-solid fa-gauge',
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
        'icon'        => 'fa-solid fa-file-invoice-dollar',
        'label'       => 'Vendor Bill',
        'link'        => 'vendor_bill.php', 
        'active_page' => 'Vendor Bill'
    ],
    [
        'icon'        => 'fa-solid fa-book',
        'label'       => 'Jurnal Otomatis',
        'link'        => 'journalManagement.php',
        'active_page' => 'Jurnal Otomatis'
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
    :root {
        --bg-primary: #021F42 !important;
        --text-accent: #FFB62A !important;
    }
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #fff !important; }
    .sidebar { background-color: #011630 !important; }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .table-responsive-custom { background: #011630; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 15px; margin-top: 10px; }
    .table-custom { width: 100%; color: #fff; border-collapse: collapse; text-align: left; }
    .table-custom th { color: #FFB62A; border-bottom: 2px solid rgba(255,255,255,0.1); padding: 12px; font-size: 14px; }
    .table-custom td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
</style>

<div class="container-fluid" style="text-align: left;">
    <div style="display: flex; gap: 15px; margin-bottom: 20px; max-width: 500px;">
        <div style="flex: 1; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 12px 16px; border-radius: 6px;">
            <small style="color: #a0aec0; display: block; margin-bottom: 4px; font-size: 10px; font-weight: 600; letter-spacing: 0.5px;">SISTEM JURNAL</small>
            <span style="color: #ffffff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Aktif & Otomatis
            </span>
        </div>
        <div style="flex: 1; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 12px 16px; border-radius: 6px;">
            <small style="color: #a0aec0; display: block; margin-bottom: 4px; font-size: 10px; font-weight: 600; letter-spacing: 0.5px;">BASIS AKUNTANSI</small>
            <span style="color: #ffffff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-scale-balanced" style="color: #3b82f6;"></i> Double-Entry (PSAK)
            </span>
        </div>
    </div>

    <div class="table-responsive-custom">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No Bukti / Invoice</th>
                    <th>Keterangan Transaksi</th>
                    <th>Kode Akun</th>
                    <th>Nama Akun Akuntansi</th>
                    <th style="text-align: right;">Debit</th>
                    <th style="text-align: right;">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <?php if($jurnals && $jurnals->num_rows > 0): ?>
                    <?php while($row = $jurnals->fetch_assoc()): ?>
                    <tr>
                        <td style="color: #cbd5e1;">
                            <?= (strtotime($row['tgl_jurnal'])) ? date('d M Y', strtotime($row['tgl_jurnal'])) : $row['tgl_jurnal']; ?>
                        </td>
                        <td>
                            <span style="background: rgba(255,255,255,0.1); color: #ffffff; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?= $row['bukti_jurnal'] ?? '-'; ?>
                            </span>
                        </td>
                        <td style="color: #cbd5e1; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= $row['ket_jurnal'] ?? '-'; ?>
                        </td>
                        <td>
                            <code style="color: #FFB62A; font-size: 13px; font-family: monospace; background: rgba(241,196,15,0.05); padding: 2px 6px; border-radius: 4px;">
                                <?= $row['account_code']; ?>
                            </code>
                        </td>
                        <td>
                            <span style="<?= $row['credit'] > 0 ? 'padding-left: 20px; color: #cbd5e1;' : 'font-weight: 500; color: #ffffff;'; ?>">
                                <?= $row['account_name']; ?>
                            </span>
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #10b981;">
                            <?= $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-'; ?>
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #f59e0b;">
                            <?= $row['credit'] > 0 ? 'Rp ' . number_format($row['credit'], 0, ',', '.') : '-'; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 50px; text-align: center; color: #ffffff;">
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

<?php 
$content = ob_get_clean();
require_once '../../includes/navbarMO6.php'; 
?>