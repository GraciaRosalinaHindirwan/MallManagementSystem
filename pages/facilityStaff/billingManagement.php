<?php
session_start();
$_SESSION['role'] = 'Finance Staff'; 
$_SESSION['nama'] = 'Intan (Staff)';

// 1. Panggil file koneksi dari folder config (mundur 2 folder dulu)
require_once '../../config/koneksi.php'; 

// 2. Panggil navbar dan header
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// --- DI BAWAH SINI KODINGAN PHP NYA UDAH LANGSUNG BISA PAKAI $conn ---

$conn = new mysqli("localhost", "root", "", "mall_management");
$id_invoice = $_GET['id'] ?? null;
$invoice = $id_invoice ? $conn->query("SELECT * FROM invoices WHERE id_invoice = '$id_invoice'")->fetch_assoc() : null;

// PBI-M06-01-04: Pelunasan otomatis menjurnal
if (isset($_POST['proses_pelunasan'])) {
    $id_inv = $_POST['id_inv'];
    $total = $_POST['total'];
    $metode = $_POST['metode'];
    $no_inv = $_POST['no_inv'];
    $tenant = $_POST['tenant'];
    $tgl = date('Y-m-d');

    $conn->query("INSERT INTO payments (id_invoice, jumlah_bayar, tanggal_bayar, metode_pembayaran) VALUES ('$id_inv', '$total', '$tgl', '$metode')");
    $conn->query("UPDATE invoices SET sisa_tagihan = 0, status = 'Lunas' WHERE id_invoice = '$id_inv'");

    // Posting Jurnal Otomatis (PBI-M06-04-01)
    $conn->query("INSERT INTO jurnal (tanggal_jurnal, no_bukti, keterangan) VALUES ('$tgl', '$no_inv', 'Pelunasan piutang tenant $tenant')");
    $id_jurnal = $conn->insert_id;
    $coa = ($metode == 'Transfer Bank') ? 2 : 1;

    $conn->query("INSERT INTO jurnal_detail (id_jurnal, id_coa, debit, kredit) VALUES ('$id_jurnal', '$coa', '$total', 0)");
    $conn->query("INSERT INTO jurnal_detail (id_jurnal, id_coa, debit, kredit) VALUES ('$id_jurnal', 3, 0, '$total')");

    echo "<script>alert('Pembayaran lunas & Jurnal otomatis berhasil terbentuk!'); window.location='invoiceManagement.php';</script>";
}
?>

<div class="content-container" style="max-width: 600px; margin: auto;">
    <h1 style="color: var(--text-accent); font-size: var(--h2);">Billing & Collection</h1>
    
    <?php if($invoice): ?>
    <form method="POST" class="p-4" style="background: var(--primary-dark); border-radius: 8px;">
        <input type="hidden" name="id_inv" value="<?= $invoice['id_invoice']; ?>">
        <input type="hidden" name="no_inv" value="<?= $invoice['no_invoice']; ?>">
        <input type="hidden" name="tenant" value="<?= $invoice['nama_tenant']; ?>">
        <input type="hidden" name="total" value="<?= $invoice['sisa_tagihan']; ?>">

        <div class="mb-3">
            <label class="text-muted">Tenant / No. Invoice</label>
            <h4><?= $invoice['nama_tenant']; ?> (<?= $invoice['no_invoice']; ?>)</h4>
        </div>
        <div class="mb-3">
            <label class="text-muted">Jumlah Pelunasan</label>
            <h2 style="color: var(--success);">Rp <?= number_format($invoice['sisa_tagihan'],0,',','.'); ?></h2>
        </div>
        <div class="mb-3">
            <label class="form-label">Metode Pembayaran</label>
            <select name="metode" class="form-control-custom">
                <option value="Transfer Bank">Transfer Bank</option>
                <option value="Kas">Kas Tunai</option>
            </select>
        </div>
        <button type="submit" name="proses_pelunasan" class="btn w-100" style="background: var(--success); color: white; font-weight: bold;">Proses & Posting Jurnal</button>
    </form>
    <?php else: ?>
        <p class="alert alert-warning text-dark">Silakan pilih invoice melalui halaman Invoice Management terlebih dahulu.</p>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>