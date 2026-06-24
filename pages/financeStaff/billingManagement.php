<?php
/** @var mysqli $conn */ 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ====================================================================
// [BELUM ADA DATABASE] - HAPUS TANDA KOMEN (/* dan */) JIKA AUTH SIAP
// ====================================================================
/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeManager') {
    header("Location: ../../index.php"); 
    exit();
}
*/
// ====================================================================

// Sesi default sementara tetap dibiarkan di bawahnya agar aman dicoba sekarang
$_SESSION['role'] = 'financeManager';
$_SESSION['nama'] = 'Staff';

// 1. Panggil file koneksi dari folder config
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

// 2. Panggil navbar dan header
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

$id_invoice = $_GET['id'] ?? null;
$invoice = null;

if ($id_invoice) {
    // SINKRONISASI DB BARU: JOIN ke tabel tenant untuk mengambil nama brand tenant
    $query_select = "SELECT i.*, t.brand_name 
                     FROM 06_invoices i
                     LEFT JOIN 02_tenants t ON i.tenant_id = t.id_tenant 
                     WHERE i.id = '$id_invoice'";
    $res_invoice = $conn->query($query_select);
    if ($res_invoice) {
        $invoice = $res_invoice->fetch_assoc();
    }
}

// PBI-M06-01-04 & PBI-M06-04-01: Pelunasan otomatis menjurnal ke Modul 06 baru
if (isset($_POST['proses_pelunasan'])) {
    $id_inv = $_POST['id_inv'];
    $total  = $_POST['total'];
    $metode = $_POST['metode'];
    $no_inv = $_POST['no_inv'];
    $tenant = $_POST['tenant'];

    // 1. Update status di tabel 06_invoices sesuai enum database asli ('Lunas')
    $conn->query("UPDATE 06_invoices SET status = 'Lunas' WHERE id = '$id_inv'");

    // 2. GENERATOR NOMOR JURNAL OTOMATIS (Mencegah Duplicate Entry)
    $prefix = "JV-" . date('Ymd') . "-";
    $query_code = "SELECT journal_number FROM 06_journal_entries WHERE journal_number LIKE '$prefix%' ORDER BY id DESC LIMIT 1";
    $result_code = $conn->query($query_code);

    if ($result_code && $result_code->num_rows > 0) {
        $row_code = $result_code->fetch_assoc();
        $last_code = $row_code['journal_number'];
        $last_num = (int) substr($last_code, -3);
        $next_num = str_pad($last_num + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $next_num = "001";
    }
    $journal_number = $prefix . $next_num;

    // Deteksi jika ada kolom tanggal alternatif pada tabel journal entries
    $kolom_tanggal = 'id';
    $cek_kolom = $conn->query("SHOW COLUMNS FROM 06_journal_entries");
    if ($cek_kolom) {
        while ($k = $cek_kolom->fetch_assoc()) {
            $field = strtolower($k['Field']);
            if (in_array($field, ['entry_date', 'date', 'created_at', 'tanggal'])) {
                $kolom_tanggal = $k['Field'];
                break;
            }
        }
    }

    $keterangan_jurnal = "Pelunasan piutang tenant - " . $conn->real_escape_string($tenant) . " (" . $conn->real_escape_string($no_inv) . ")";
    
    // Simpan entri jurnal beserta journal_number unik & deteksi tanggal otomatis
    if ($kolom_tanggal !== 'id') {
        $conn->query("INSERT INTO 06_journal_entries (journal_number, `$kolom_tanggal`, description) VALUES ('$journal_number', NOW(), '$keterangan_jurnal')");
    } else {
        $conn->query("INSERT INTO 06_journal_entries (journal_number, description) VALUES ('$journal_number', '$keterangan_jurnal')");
    }
    
    $id_jurnal = $conn->insert_id;

    // 3. Tentukan ID Akun (account_id) secara dinamis dari tabel 06_chart_of_accounts agar terhindar dari foreign key error
    $code_debit = ($metode == 'Transfer Bank') ? '1-1002' : '1-1001'; 
    $res_debit  = $conn->query("SELECT id FROM 06_chart_of_accounts WHERE account_code = '$code_debit' LIMIT 1");
    $coa_debit  = ($res_debit && $res_debit->num_rows > 0) ? $res_debit->fetch_assoc()['id'] : 3;

    $res_kredit = $conn->query("SELECT id FROM 06_chart_of_accounts WHERE account_code = '1-1003' LIMIT 1"); 
    $coa_kredit = ($res_kredit && $res_kredit->num_rows > 0) ? $res_kredit->fetch_assoc()['id'] : 2;

    // 4. Posting ke Detail Jurnal (06_journal_lines)
    // Baris Debit (Kas/Bank bertambah)
    $conn->query("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) 
                  VALUES ('$id_jurnal', '$coa_debit', '$total', 0)");
                  
    // Baris Kredit (Piutang berkurang)
    $conn->query("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) 
                  VALUES ('$id_jurnal', '$coa_kredit', 0, '$total')");

    echo "<script>alert('Pembayaran lunas & Jurnal otomatis dengan No: $journal_number berhasil terbentuk!'); window.location='invoiceManagement.php';</script>";
}
?>

<div class="container-fluid" style="margin-top: -15px; min-height: 80vh;">
    <div style="max-width: 580px; margin: 40px auto; background: #011630; border: 1px solid rgba(255,255,255,0.05); padding: 35px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 40px; margin-bottom: 10px;">💸</div>
            <h2 style="color: var(--accent); margin: 0; font-weight: 700; font-size: 24px;">Billing & Collection</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #cbd5e1; opacity: 0.9;">PBI-M06-01-04 — Penerimaan Kas masuk & Otomasi Penjurnalan</p>
        </div>
        
        <?php if($invoice): ?>
        <form method="POST">
            <input type="hidden" name="id_inv" value="<?= $invoice['id']; ?>">
            <input type="hidden" name="no_inv" value="<?= $invoice['invoice_number']; ?>">
            <input type="hidden" name="tenant" value="<?= $invoice['brand_name'] ?? 'Tenant'; ?>">
            <input type="hidden" name="total" value="<?= $invoice['total_amount']; ?>">

            <div style="margin-bottom: 22px; background: rgba(255,255,255,0.02); padding: 12px 16px; border-radius: 6px; border-left: 3px solid rgba(255,255,255,0.2);">
                <label style="color: #a0aec0; font-size: 11px; font-weight: 600; display: block; margin-bottom: 4px; letter-spacing: 0.5px;">NAMA BRAND TENANT / NO INVOICE</label>
                <div style="color: #fff; font-size: 16px; font-weight: 600;"><?= $invoice['brand_name'] ?? 'Tanpa Nama Brand'; ?></div>
                <div style="color: #cbd5e1; font-size: 13px; font-family: monospace; margin-top: 2px;"><?= $invoice['invoice_number']; ?></div>
            </div>

            <div style="margin-bottom: 25px; background: rgba(16, 185, 129, 0.04); padding: 15px 16px; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.15);">
                <label style="color: #a0aec0; font-size: 11px; font-weight: 600; display: block; margin-bottom: 2px; letter-spacing: 0.5px;">TOTAL TAGIHAN YANG HARUS DIBAYAR</label>
                <div style="color: #10b981; font-size: 26px; font-weight: 700;">Rp <?= number_format($invoice['total_amount'], 0, ',', '.'); ?></div>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="color: #fff; display: block; margin-bottom: 8px; font-size: 13px; font-weight: 500;">Pilih Instrumen Likuiditas Pembayaran</label>
                <select name="metode" style="width: 100%; padding: 12px; background: #0f172a; color: #fff; border: 1px solid rgba(255,255,255,0.12); border-radius: 6px; font-size: 14px; outline: none;">
                    <option value="Transfer Bank">Bank Transfer (Rekening Bank Mandiri/BCA Mall)</option>
                    <option value="Kas">Kas Fisik Tunai (Setoran Langsung di Kasir Mall)</option>
                </select>
            </div>

            <button type="submit" name="proses_pelunasan" 
                    style="width: 100%; background: rgba(241, 196, 15, 0.1); color: var(--accent); font-weight: 600; padding: 14px; border: 1px solid rgba(241, 196, 15, 0.3); border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                    onmouseover="this.style.background='var(--accent)'; this.style.color='#021F42';"
                    onmouseout="this.style.background='rgba(241, 196, 15, 0.1)'; this.style.color='var(--accent)';">
                <i class="fa-solid fa-cloud-arrow-up" style="margin-right: 6px;"></i> Eksekusi Terima Uang & Posting Jurnal
            </button>
        </form>
        <?php else: ?>
            <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.25); color: #f59e0b; padding: 15px; border-radius: 6px; font-size: 13px; text-align: center; line-height: 1.5;">
                ⚠️ <strong>Akses Ditolak:</strong> Parameter referensi pelunasan kosong. Silakan tentukan data tagihan tenant pada halaman <a href="invoiceManagement.php" style="color: #fff; font-weight: 600; text-decoration: underline;">Manajemen Invoice</a> sebelum memproses kas masuk.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>