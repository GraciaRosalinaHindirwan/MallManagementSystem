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

if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} else {
    require_once '../../config/connection.php';
}

$id_invoice = $_GET['id'] ?? null;
$invoice = null;

if ($id_invoice) {
    $query_select = "SELECT i.*, t.brand_name FROM 06_invoices i LEFT JOIN 02_tenants t ON i.tenant_id = t.id_tenant WHERE i.id = '$id_invoice'";
    $res_invoice = $conn->query($query_select);
    if ($res_invoice) { $invoice = $res_invoice->fetch_assoc(); }
}

if (isset($_POST['proses_pelunasan'])) {
    $id_inv = $_POST['id_inv'];
    $total  = $_POST['total'];
    $metode = $_POST['metode'];
    $no_inv = $_POST['no_inv'];
    $tenant = $_POST['tenant'];

    $conn->query("UPDATE 06_invoices SET status = 'Lunas' WHERE id = '$id_inv'");

    $prefix = "JV-" . date('Ymd') . "-";
    $query_code = "SELECT journal_number FROM 06_journal_entries WHERE journal_number LIKE '$prefix%' ORDER BY id DESC LIMIT 1";
    $result_code = $conn->query($query_code);

    if ($result_code && $result_code->num_rows > 0) {
        $row_code = $result_code->fetch_assoc();
        $next_num = str_pad(((int) substr($row_code['journal_number'], -3)) + 1, 3, '0', STR_PAD_LEFT);
    } else { $next_num = "001"; }
    $journal_number = $prefix . $next_num;

    $keterangan_jurnal = "Pelunasan piutang tenant - " . $conn->real_escape_string($tenant) . " (" . $conn->real_escape_string($no_inv) . ")";
    $conn->query("INSERT INTO 06_journal_entries (journal_number, description) VALUES ('$journal_number', '$keterangan_jurnal')");
    $id_jurnal = $conn->insert_id;

    $code_debit = ($metode == 'Transfer Bank') ? '1-1002' : '1-1001'; 
    $res_debit  = $conn->query("SELECT id FROM 06_chart_of_accounts WHERE account_code = '$code_debit' LIMIT 1");
    $coa_debit  = ($res_debit && $res_debit->num_rows > 0) ? $res_debit->fetch_assoc()['id'] : 3;

    $res_kredit = $conn->query("SELECT id FROM 06_chart_of_accounts WHERE account_code = '1-1003' LIMIT 1"); 
    $coa_kredit = ($res_kredit && $res_kredit->num_rows > 0) ? $res_kredit->fetch_assoc()['id'] : 2;

    $conn->query("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES ('$id_jurnal', '$coa_debit', '$total', 0)");
    $conn->query("INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES ('$id_jurnal', '$coa_kredit', 0, '$total')");

    echo "<script>alert('Pembayaran berhasil & Jurnal No: $journal_number otomatis terbit!'); window.location='invoiceManagement.php';</script>";
}

// Config Master Navbar Mentahan
$department_name = "Finance Department";
$user_name = $_SESSION['nama'] ?? "Finance Staff";
$page_title = "Billing & Collection";
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
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #fff !important; }
    .sidebar { background-color: #011630 !important; }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .billing-card { max-width: 500px; margin: 40px auto; background: #011630; border: 1px solid rgba(255,255,255,0.05); padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); text-align: left;}
</style>

<div class="container-fluid">
    <div class="billing-card">
        <h3 style="color: #FFB62A; font-weight: 700; margin-bottom: 20px; text-align: center;">Proses Penerimaan Kas</h3>
        
        <?php if($invoice): ?>
        <form method="POST">
            <input type="hidden" name="id_inv" value="<?= $invoice['id']; ?>">
            <input type="hidden" name="no_inv" value="<?= $invoice['invoice_number']; ?>">
            <input type="hidden" name="tenant" value="<?= $invoice['brand_name'] ?? 'Tenant'; ?>">
            <input type="hidden" name="total" value="<?= $invoice['total_amount']; ?>">

            <div style="background: rgba(255,255,255,0.02); padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                <span style="color: #a0aec0; font-size: 11px; display: block;">BRAND TENANT</span>
                <strong style="font-size: 16px; color: #fff;"><?= htmlspecialchars($invoice['brand_name'] ?? 'Tanpa Nama Brand'); ?></strong>
                <small style="display: block; color: #cbd5e1; font-family: monospace;"><?= $invoice['invoice_number']; ?></small>
            </div>

            <div style="background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.2); margin-bottom: 20px;">
                <span style="color: #cbd5e1; font-size: 11px; display: block;">JUMLAH YANG HARUS DIBAYAR</span>
                <span style="color: #10b981; font-size: 24px; font-weight: 700;">Rp <?= number_format($invoice['total_amount'], 0, ',', '.'); ?></span>
            </div>

            <div class="mb-4">
                <label style="color: #fff; font-size: 13px; display: block; margin-bottom: 8px;">Pilih Instrumen Likuiditas Pembayaran</label>
                <select name="metode" style="background: #0f172a; color: #fff; border: 1px solid rgba(255,255,255,0.15); width:100%; padding: 10px; border-radius: 5px;">
                    <option value="Transfer Bank">Transfer Bank (Mandiri/BCA Mall)</option>
                    <option value="Kas">Kas Tunai (Kasir Utama Mall)</option>
                </select>
            </div>

            <button type="submit" name="proses_pelunasan" style="background: #FFB62A; color: #021F42; font-weight: 600; width: 100%; padding: 12px; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fa-solid fa-check"></i> Lunasi & Posting Jurnal Otomatis
            </button>
        </form>
        <?php else: ?>
            <div style="color: #f59e0b; text-align: center;">⚠️ Data Invoice tidak valid atau belum ditentukan.</div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once '../../includes/navbarM06.php'; 
?>
