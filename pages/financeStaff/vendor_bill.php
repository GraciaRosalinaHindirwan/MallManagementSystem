<?php

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

$_SESSION['role'] = 'financeStaff';
$_SESSION['nama'] = 'Finance Staff';

if (file_exists(__DIR__ . '/../../config/konek.php')) {
    require_once __DIR__ . '/../../config/konek.php';
} elseif (file_exists(__DIR__ . '/../../config/connection.php')) {
    require_once __DIR__ . '/../../config/connection.php';
} else {
    die("<div style='color:#ffffff; background-color:#721c24; padding:20px; border-radius:6px;'>⚠️ File koneksi database tidak ditemukan!</div>");
}

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

if (isset($_POST['buat_dummy'])) {
    $po_number_dummy = "PO-" . date('Ymd') . "-" . rand(100, 999);
    $tgl_dummy       = date('Y-m-d');

    // Nilai PO dibuat acak antara 5 Juta sampai 50 Juta agar kebutuhan tidak selalu Rp 7.500.000
    $nominal_dummy   = rand(50, 500) * 100000;
    $status_dummy    = 'pending';
    $vendor_dummy    = 'Vendor Logistik Utama';

    $stmt_dummy = $conn->prepare(
        "INSERT INTO `06_purchase_orders` (po_number, vendor_name, order_date, total_amount, status)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt_dummy->bind_param("sssds", $po_number_dummy, $vendor_dummy, $tgl_dummy, $nominal_dummy, $status_dummy);
    if ($stmt_dummy->execute()) {
        echo "<script>alert('Dummy PO Berhasil Dibuat Dinamis! Nominal: Rp " . number_format($nominal_dummy, 0, ',', '.') . "'); window.location='vendor_bill.php';</script>";
    } else {
        echo "<script>alert('Gagal membuat dummy PO: " . $conn->error . "');</script>";
    }
    $stmt_dummy->close();
    exit();
}

if (isset($_POST['input_vbr'])) {
    $po_id                 = (int) $_POST['po_id'];
    $received_date         = $_POST['received_date'];
    $ticket_ref            = trim($_POST['ticket_ref'] ?? '');
    $vendor_invoice_number = trim($_POST['vendor_invoice_number']);
    $invoice_amount        = (float) $_POST['invoice_amount'];
    $received_by           = $_SESSION['nama'] ?? 'Finance Staff';

    $gr_number = "GR-" . date('Ymd') . "-" . rand(1000, 9999);

    $stmt_po = $conn->prepare("SELECT total_amount FROM `06_purchase_orders` WHERE id = ?");
    $stmt_po->bind_param("i", $po_id);
    $stmt_po->execute();
    $po = $stmt_po->get_result()->fetch_assoc();
    $stmt_po->close();

    if (!$po) {
        echo "<script>alert('PO tidak ditemukan!'); window.location='vendor_bill.php';</script>";
        exit();
    }

    // 3-Way Matching Otomatis awal
    $is_matched = ($invoice_amount == $po['total_amount']) ? 1 : 0;
    $status     = $is_matched ? 'matched' : 'pending_match';

    $stmt = $conn->prepare(
        "INSERT INTO `06_vendor_bill_receipts`
            (po_id, gr_number, received_date, received_by, ticket_ref, vendor_invoice_number, invoice_amount, is_matched, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "isssssdis",
        $po_id,
        $gr_number,
        $received_date,
        $received_by,
        $ticket_ref,
        $vendor_invoice_number,
        $invoice_amount,
        $is_matched,
        $status
    );

    if ($stmt->execute()) {
        $status_po = $is_matched ? 'received' : 'ordered';
        $stmt_upd  = $conn->prepare("UPDATE `06_purchase_orders` SET status=? WHERE id=?");
        $stmt_upd->bind_param("si", $status_po, $po_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        $hasil = $is_matched
            ? 'MATCHED ✅ — PO siap dibayar!'
            : 'TIDAK MATCH ❌ — Nominal berbeda. Status ditahan, butuh Validasi Manual (Re-match)!';
        echo "<script>alert('Hasil Pengecekan: $hasil'); window.location='vendor_bill.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan: " . $conn->error . "');</script>";
    }
    $stmt->close();
    exit();
}

if (isset($_GET['rematch']) && is_numeric($_GET['rematch'])) {
    $id_vbr = (int) $_GET['rematch'];

    $stmt_vbr = $conn->prepare("SELECT * FROM `06_vendor_bill_receipts` WHERE id = ?");
    $stmt_vbr->bind_param("i", $id_vbr);
    $stmt_vbr->execute();
    $vbr = $stmt_vbr->get_result()->fetch_assoc();
    $stmt_vbr->close();

    if ($vbr) {

        $status_baru = 'matched';
        $is_matched_baru = 1;

        $stmt_upd = $conn->prepare("UPDATE `06_vendor_bill_receipts` SET status=?, is_matched=? WHERE id=?");
        $stmt_upd->bind_param("sii", $status_baru, $is_matched_baru, $id_vbr);
        $stmt_upd->execute();
        $stmt_upd->close();

        $status_po = 'received';
        $stmt_po2  = $conn->prepare("UPDATE `06_purchase_orders` SET status=? WHERE id=?");
        $stmt_po2->bind_param("si", $status_po, $vbr['po_id']);
        $stmt_po2->execute();
        $stmt_po2->close();

        echo "<script>alert('Validasi Manual Sukses! Status transaksi ID #$id_vbr dipaksa menjadi MATCHED ✅ karena nominal dinyatakan Benar Adanya.'); window.location='vendor_bill.php';</script>";
    }
    exit();
}

$po_available = $conn->query(
    "SELECT id, po_number, total_amount FROM `06_purchase_orders` ORDER BY id DESC"
);

$data_vbr = $conn->query(
    "SELECT vbr.*, po.po_number, po.total_amount AS po_amount
     FROM `06_vendor_bill_receipts` vbr
     LEFT JOIN `06_purchase_orders` po ON vbr.po_id = po.id
     ORDER BY vbr.id DESC"
);

$total_vbr = $conn->query("SELECT COUNT(*) AS jml FROM `06_vendor_bill_receipts`")->fetch_assoc()['jml'] ?? 0;
$matched   = $conn->query("SELECT COUNT(*) AS jml FROM `06_vendor_bill_receipts` WHERE status='matched'")->fetch_assoc()['jml'] ?? 0;
$not_match = $conn->query("SELECT COUNT(*) AS jml FROM `06_vendor_bill_receipts` WHERE status='pending_match'")->fetch_assoc()['jml'] ?? 0;

$department_name = "Finance Department (Staff Dashboard)";
$user_name       = $_SESSION['nama'];
$page_title      = "Vendor Bill";

ob_start();
?>

<div class="container-fluid" style="padding: 10px 0px; text-align: left;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 style="color: #FFB62A; font-size: 32px; margin: 0; font-weight: 700;">
                Vendor Bill &amp; Receipt
                <span style="font-size: 16px; color: #cbd5e1; font-weight: 400;">(3-Way Matching)</span>
            </h1>
            <p style="color: #cbd5e1; margin: 5px 0 0 0; font-size: 14px;">
                Verifikasi kesesuaian antara <strong>Purchase Order</strong>, <strong>Penerimaan Fisik Barang</strong>,
                dan <strong>Invoice Vendor</strong> sebelum pembayaran diproses.
            </p>
        </div>

        <form method="POST">
            <button type="submit" name="buat_dummy"
                style="background: rgba(255,182,42,0.1); color: #FFB62A; border: 1px solid #FFB62A;
                       padding: 8px 16px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                <i class="fa-solid fa-flask me-1"></i> Buat Dummy PO Acak
            </button>
        </form>
    </div>

    <div style="background: rgba(3, 43, 92, 0.4); padding:20px; border-radius:8px; margin-bottom:25px; border: 1px solid rgba(255,255,255,0.05);">
        <p style="color:#64748b; font-size:11px; font-weight:700; letter-spacing:1px; margin:0 0 12px 0;">
            ALUR 3-WAY MATCHING LOGISTIK MALL
        </p>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div style="background:#021F42; padding:8px 15px; border-radius:6px; border:1px solid #00cfd5; color:#00cfd5; font-size:12px; font-weight:600;">
                <i class="fa-solid fa-cart-shopping"></i> 1. Purchase Order
            </div>
            <i class="fa-solid fa-arrow-right" style="color:#64748b; font-size:12px;"></i>
            <div style="background:#021F42; padding:8px 15px; border-radius:6px; border:1px solid #FFB62A; color:#FFB62A; font-size:12px; font-weight:600;">
                <i class="fa-solid fa-box"></i> 2. Penerimaan Fisik
            </div>
            <i class="fa-solid fa-arrow-right" style="color:#64748b; font-size:12px;"></i>
            <div style="background:#021F42; padding:8px 15px; border-radius:6px; border:1px solid #a855f7; color:#a855f7; font-size:12px; font-weight:600;">
                <i class="fa-solid fa-file-invoice-dollar"></i> 3. Invoice Vendor
            </div>
            <i class="fa-solid fa-arrow-right" style="color:#64748b; font-size:12px;"></i>
            <div style="background:#021F42; padding:8px 15px; border-radius:6px; border:1px solid #22c55e; color:#22c55e; font-size:12px; font-weight:600;">
                <i class="fa-solid fa-check-double"></i> 4. Matched → Lunas
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:#011630; padding:20px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05); border-left:5px solid #00cfd5;">
            <p style="color:#cbd5e1; font-size:11px; letter-spacing:1px; margin:0;">TOTAL DATA</p>
            <h2 style="color:#fff; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $total_vbr ?></h2>
        </div>
        <div style="background:#011630; padding:20px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05); border-left:5px solid #22c55e;">
            <p style="color:#cbd5e1; font-size:11px; letter-spacing:1px; margin:0;">MATCHED</p>
            <h2 style="color:#22c55e; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $matched ?></h2>
        </div>
        <div style="background:#011630; padding:20px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05); border-left:5px solid #ef4444;">
            <p style="color:#cbd5e1; font-size:11px; letter-spacing:1px; margin:0;">PENDING MATCH</p>
            <h2 style="color:#ef4444; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $not_match ?></h2>
        </div>
    </div>

    <div style="background:#011630; padding:25px; border-radius:8px; margin-bottom:30px; border:1px solid rgba(255,255,255,0.06);">
        <h4 style="color:#FFB62A; margin-bottom:20px; font-size:15px; font-weight:600;">
            <i class="fa-solid fa-box-open me-2"></i> INPUT RECEIPT &amp; VENDOR INVOICE
        </h4>

        <?php if ($po_available && $po_available->num_rows > 0): ?>
            <form method="POST">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:15px;">
                    <div>
                        <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Pilih Dokumen Purchase Order (PO)</label>
                        <select name="po_id" required
                            style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                            <option value="">-- Pilih Dokumen PO --</option>
                            <?php $po_available->data_seek(0);
                            while ($po = $po_available->fetch_assoc()): ?>
                                <option value="<?= $po['id'] ?>">
                                    <?= htmlspecialchars($po['po_number']) ?> — (Rp <?= number_format($po['total_amount'], 0, ',', '.') ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Tanggal Penerimaan Fisik Barang</label>
                        <input type="date" name="received_date" required
                            style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                    </div>

                    <div>
                        <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Nomor Invoice / Faktur Vendor</label>
                        <input type="text" name="vendor_invoice_number" required placeholder="Contoh: INV/2026/089"
                            style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                    </div>

                    <div>
                        <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Nilai Tagihan Invoice Vendor (Rp)</label>
                        <input type="number" name="invoice_amount" required min="0" step="0.01" placeholder="Masukkan total nilai tagihan"
                            style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                    </div>
                </div>

                <div style="margin-top:15px; width:100%;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Referensi Tiket Modul M03 <span style="color:#64748b; font-weight:400;">(Opsional)</span></label>
                    <input type="text" name="ticket_ref" placeholder="Contoh: TKT-20260611-001"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                </div>

                <div style="margin-top:15px; padding:12px; background:rgba(0,207,213,0.05); border-radius:6px; border:1px solid rgba(0,207,213,0.15);">
                    <p style="margin:0; color:#00cfd5; font-size:12px;">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        Logika Validasi Otomatis: Jika nominal tagihan berbeda, gunakan tombol <strong style="color:#FFB62A;">Re-match</strong> di bawah untuk melakukan Validasi Manual/Persetujuan Khusus.
                    </p>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" name="input_vbr" style="background:#00cfd5; color:#021F42; font-weight:700; padding:10px 24px; border:none; border-radius:6px; font-size:13px; cursor:pointer;">
                        <i class="fa-solid fa-check-double me-1"></i> Eksekusi 3-Way Matching
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div style="padding:20px; background:rgba(255,255,255,0.03); border-radius:6px; border:1px dashed rgba(255,255,255,0.1); text-align:center;">
                <i class="fa-solid fa-folder-open" style="color:#64748b; font-size:24px; display:block; margin-bottom:10px;"></i>
                <p style="color:#cbd5e1; font-size:13px; margin:0;">Belum ada dokumen PO aktif.</p>
            </div>
        <?php endif; ?>
    </div>

    <h4 style="color:#fff; margin-bottom:15px; font-size:15px; font-weight:600;">
        <i class="fa-solid fa-list me-2"></i> MONITORING RIWAYAT MATCHING LOGISTIK
    </h4>
    <div style="background:#011630; padding:15px; border-radius:8px; border:1px solid rgba(255,255,255,0.06); overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px; color:#fff;">
            <thead>
                <tr style="border-bottom:2px solid rgba(255,255,255,0.1); color:#FFB62A; background:rgba(255,255,255,0.02);">
                    <th style="padding:12px 8px;">ID</th>
                    <th style="padding:12px 8px;">No. GR</th>
                    <th style="padding:12px 8px;">No. PO</th>
                    <th style="padding:12px 8px;">No. Invoice Vendor</th>
                    <th style="padding:12px 8px;">Ref. Tiket M03</th>
                    <th style="padding:12px 8px; text-align:right;">Total Nilai PO</th>
                    <th style="padding:12px 8px; text-align:right;">Nilai Invoice</th>
                    <th style="padding:12px 8px; text-align:center;">Tgl Terima</th>
                    <th style="padding:12px 8px; text-align:center;">Hasil 3-Way</th>
                    <th style="padding:12px 8px; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data_vbr && $data_vbr->num_rows > 0): ?>
                    <?php while ($row = $data_vbr->fetch_assoc()): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                            <td style="padding:14px 8px;"><strong style="color:#00cfd5;">#<?= $row['id'] ?></strong></td>
                            <td style="padding:14px 8px; color:#cbd5e1;"><?= htmlspecialchars($row['gr_number'] ?? '-') ?></td>
                            <td style="padding:14px 8px; color:#FFB62A;"><?= htmlspecialchars($row['po_number'] ?? '-') ?></td>
                            <td style="padding:14px 8px; color:#a855f7;"><?= htmlspecialchars($row['vendor_invoice_number'] ?? '') ?></td>
                            <td style="padding:14px 8px; color:#cbd5e1;"><?= !empty($row['ticket_ref']) ? htmlspecialchars($row['ticket_ref']) : '—' ?></td>
                            <td style="padding:14px 8px; text-align:right;">Rp <?= number_format($row['po_amount'] ?? 0, 0, ',', '.') ?></td>
                            <td style="padding:14px 8px; text-align:right;">Rp <?= number_format($row['invoice_amount'] ?? 0, 0, ',', '.') ?></td>
                            <td style="padding:14px 8px; text-align:center; color:#cbd5e1;">
                                <?= !empty($row['received_date']) ? date('d M Y', strtotime($row['received_date'])) : '-' ?>
                            </td>
                            <td style="padding:14px 8px; text-align:center;">
                                <?php if ($row['status'] === 'matched'): ?>
                                    <span style="background:#22c55e22; color:#22c55e; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; border:1px solid #22c55e44; display:inline-block;">
                                        ✅ Matched
                                    </span>
                                <?php else: ?>
                                    <span style="background:#ef444422; color:#ef4444; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; border:1px solid #ef444444; display:inline-block;">
                                        ❌ No Match
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:14px 8px; text-align:center;">
                                <?php if ($row['status'] !== 'matched'): ?>
                                    <a href="?rematch=<?= $row['id'] ?>"
                                        style="background:#FFB62A; color:#021F42; padding:6px 12px; border-radius:6px; font-size:11px; text-decoration:none; font-weight:600; display:inline-block;">
                                        <i class="fa-solid fa-rotate"></i> Approve Manual
                                    </a>
                                <?php else: ?>
                                    <span style="color:#64748b; font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding:30px; color:#cbd5e1;">
                            <i class="fa-solid fa-folder-open d-block fs-4 mb-2"></i> Belum ada data verifikasi logistik.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarM06.php';
?>