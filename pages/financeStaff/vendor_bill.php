<?php

/** @var mysqli $conn */ // Memberitahu VS Code kalau $conn itu objek database sah!

session_start();
// Mengunci ke role asli Finance Staff agar menu sidebar menciut menjadi 2 Menu otomatis
$_SESSION['role'] = 'Finance Staff';
$_SESSION['nama'] = 'Eva (Finance)';

// Pastikan file koneksi langsung dipanggil secara tegas menggunakan __DIR__ agar editor tidak bingung mencari jalurnya
if (file_exists(__DIR__ . '/../../config/koneksi.php')) {
    require_once __DIR__ . '/../../config/koneksi.php';
} else {
    require_once __DIR__ . '/../../config/connection.php';
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// -------------------------------------------------------
// PBI-M06-03-03: INPUT PENERIMAAN BARANG + INVOICE VENDOR
// -------------------------------------------------------
if (isset($_POST['input_vbr'])) {
    $po_id         = (int) $_POST['po_id'];
    $tgl_terima    = $_POST['received_date'];
    $tiket         = trim($_POST['ticket_ref'] ?? '');
    $no_invoice    = trim($_POST['vendor_invoice_number']);
    $total_invoice = (float) $_POST['invoice_amount'];

    // Ambil total_amount PO untuk matching dari tabel 06_purchase_orders
    $stmt_po = $conn->prepare(
        "SELECT total_amount FROM `06_purchase_orders` WHERE id = ?"
    );
    $stmt_po->bind_param("i", $po_id);
    $stmt_po->execute();
    $po = $stmt_po->get_result()->fetch_assoc();
    $stmt_po->close();

    // 3-Way Matching logic
    $is_matched = ($po && $total_invoice == $po['total_amount']) ? 1 : 0;
    $status     = $is_matched ? 'matched' : 'pending_match';

    // Query INSERT disesuaikan dengan struktur tabel 06_vendor_bill_receipts milik Eva
    $stmt = $conn->prepare(
        "INSERT INTO `06_vendor_bill_receipts` 
            (po_id, tgl_terima, tiket, no_invoice, total_invoice, status)
        VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
        "isssds",
        $po_id,
        $tgl_terima,
        $tiket,
        $no_invoice,
        $total_invoice,
        $status
    );

    if ($stmt->execute()) {
        $status_po = $is_matched ? 'received' : 'ordered';
        $stmt_upd  = $conn->prepare(
            "UPDATE `06_purchase_orders` SET status=? WHERE id=?"
        );
        $stmt_upd->bind_param("si", $status_po, $po_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        $hasil = $is_matched
            ? 'MATCHED ✅ — PO siap dibayar!'
            : 'TIDAK MATCH ❌ — Jumlah invoice berbeda dengan PO. Perlu review!';
        echo "<script>alert('Hasil 3-Way Matching: $hasil'); window.location='vendor_bill.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan: " . $conn->error . "');</script>";
    }
    $stmt->close();
}

// -------------------------------------------------------
// RE-MATCH MANUAL — cocokkan ulang jika sebelumnya tidak match
// -------------------------------------------------------
if (isset($_GET['rematch']) && is_numeric($_GET['rematch'])) {
    $id_vbr = (int) $_GET['rematch'];

    $stmt_vbr = $conn->prepare(
        "SELECT * FROM `06_vendor_bill_receipts` WHERE Id = ?"
    );
    $stmt_vbr->bind_param("i", $id_vbr);
    $stmt_vbr->execute();
    $vbr = $stmt_vbr->get_result()->fetch_assoc();
    $stmt_vbr->close();

    if ($vbr) {
        $stmt_po = $conn->prepare(
            "SELECT total_amount FROM `06_purchase_orders` WHERE id = ?"
        );
        $stmt_po->bind_param("i", $vbr['po_id']);
        $stmt_po->execute();
        $po = $stmt_po->get_result()->fetch_assoc();
        $stmt_po->close();

        $is_matched = ($po && $vbr['total_invoice'] == $po['total_amount']) ? 1 : 0;
        $status     = $is_matched ? 'matched' : 'pending_match';

        $stmt_upd = $conn->prepare(
            "UPDATE `06_vendor_bill_receipts` SET status=? WHERE Id=?"
        );
        $stmt_upd->bind_param("si", $status, $id_vbr);
        $stmt_upd->execute();
        $stmt_upd->close();

        $status_po = $is_matched ? 'received' : 'ordered';
        $stmt_po2  = $conn->prepare(
            "UPDATE `06_purchase_orders` SET status=? WHERE id=?"
        );
        $stmt_po2->bind_param("si", $status_po, $vbr['po_id']);
        $stmt_po2->execute();
        $stmt_po2->close();

        $hasil = $is_matched ? 'MATCHED ✅' : 'MASIH TIDAK MATCH ❌';
        echo "<script>alert('Re-matching selesai: $hasil'); window.location='vendor_bill.php';</script>";
    }
}

// -------------------------------------------------------
// BINDING DATA QUERY YANG AMAN UNTUK TABEL EVA
// -------------------------------------------------------
$po_available = $conn->query(
    "SELECT id, po_no, total_amount FROM `06_purchase_orders` WHERE status IN ('pending','ordered','approved') ORDER BY id DESC"
);

$data_vbr = $conn->query(
    "SELECT vbr.*, po.po_no AS po_number, po.total_amount AS po_amount
    FROM `06_vendor_bill_receipts` vbr
    LEFT JOIN `06_purchase_orders` po ON vbr.po_id = po.id
    ORDER BY vbr.Id DESC"
);

$total_vbr = $conn->query("SELECT COUNT(*) AS jml FROM `06_vendor_bill_receipts`")->fetch_assoc()['jml'] ?? 0;
$matched   = $conn->query("SELECT COUNT(*) AS jml FROM `06_vendor_bill_receipts` WHERE status='matched'")->fetch_assoc()['jml'] ?? 0;
$not_match = $conn->query("SELECT COUNT(*) AS jml FROM `06_vendor_bill_receipts` WHERE status='pending_match'")->fetch_assoc()['jml'] ?? 0;
?>

<div class="content-wrapper">

    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 28px; font-weight: 700; margin: 0;">
            Vendor Bill &amp; Receipt <span style="font-size: 15px; color: #64748b; font-weight: normal;">(3-Way Matching)</span>
        </h1>
        <p style="color: #cbd5e1; margin-top: 5px; font-size: 14px;">
            Verifikasi kesesuaian antara <strong>Purchase Order</strong>, <strong>Penerimaan Fisik Barang</strong>, dan <strong>Invoice Vendor</strong> sebelum pembayaran diproses.
        </p>
    </div>

    <div style="background: rgba(3, 43, 92, 0.4); padding:20px; border-radius:12px; margin-bottom:25px; border: 1px solid rgba(255,255,255,0.05);">
        <p style="color:#64748b; font-size:11px; font-weight:700; letter-spacing:1px; margin:0 0 12px 0;">
            ALUR 3-WAY MATCHING LOGISTIK MALL
        </p>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div style="background:#021F42; padding:8px 15px; border-radius:8px; border:1px solid #00cfd5; color:#00cfd5; font-size:12px; font-weight:600;">
                <i class="fa-solid fa-cart-shopping"></i> 1. Purchase Order
            </div>
            <i class="fa-solid fa-arrow-right" style="color:#64748b; font-size: 12px;"></i>
            <div style="background:#021F42; padding:8px 15px; border-radius:8px; border:1px solid #FFB62A; color:#FFB62A; font-size:12px; font-weight:600;">
                <i class="fa-solid fa-box"></i> 2. Penerimaan Fisik
            </div>
            <i class="fa-solid fa-arrow-right" style="color:#64748b; font-size: 12px;"></i>
            <div style="background:#021F42; padding:8px 15px; border-radius:8px; border:1px solid #a855f7; color:#a855f7; font-size:12px; font-weight:600;">
                <i class="fa-solid fa-file-invoice-dollar"></i> 3. Invoice Vendor
            </div>
            <i class="fa-solid fa-arrow-right" style="color:#64748b; font-size: 12px;"></i>
            <div style="background:#021F42; padding:8px 15px; border-radius:8px; border:1px solid #22c55e; color:#22c55e; font-size:12px; font-weight:600;">
                <i class="fa-solid fa-check-double"></i> 4. Matched → Lunas
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #00cfd5;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">TOTAL DATA</p>
            <h2 style="color:#fff; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $total_vbr ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #22c55e;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">MATCHED</p>
            <h2 style="color:#22c55e; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $matched ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #ef4444;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">PENDING MATCH</p>
            <h2 style="color:#ef4444; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $not_match ?></h2>
        </div>
    </div>

    <div style="background:#032b5c; padding:25px; border-radius:12px; margin-bottom:30px; border:1px solid rgba(255,255,255,0.06);">
        <h4 style="color:#FFB62A; margin-bottom:20px; font-size:15px; font-weight:600;">
            <i class="fa-solid fa-box-open me-2"></i> INPUT RECEIPT &amp; VENDOR INVOICE
        </h4>
        <?php if ($po_available && $po_available->num_rows > 0): ?>
            <form method="POST">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:15px;">
                    <div>
                        <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Pilih Dokumen Purchase Order (PO)</label>
                        <select name="po_id" required
                            style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42;
                            border:1px solid rgba(255,255,255,0.1); border-radius:8px;
                            color:#fff; font-size:13px;">
                            <option value="">-- Pilih Dokumen PO --</option>
                            <?php $po_available->data_seek(0);
                            while ($po = $po_available->fetch_assoc()): ?>
                                <option value="<?= $po['id'] ?>">
                                    <?= htmlspecialchars($po['po_no'] ?? '') ?> — (Rp <?= number_format($po['total_amount'] ?? 0, 0, ',', '.') ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div>
                        <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Tanggal Penerimaan Fisik Barang</label>
                        <input type="date" name="received_date" required
                            style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42;
                            border:1px solid rgba(255,255,255,0.1); border-radius:8px;
                            color:#fff; font-size:13px;">
                    </div>

                    <div>
                        <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Nomor Invoice / Faktur Vendor</label>
                        <input type="text" name="vendor_invoice_number" required
                            placeholder="Contoh: INV/2026/089"
                            style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42;
                            border:1px solid rgba(255,255,255,0.1); border-radius:8px;
                            color:#fff; font-size:13px;">
                    </div>

                    <div>
                        <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Nilai Tagihan Invoice Vendor (Rp)</label>
                        <input type="number" name="invoice_amount" required min="0" step="0.01"
                            placeholder="Masukkan total nilai tagihan"
                            style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42;
                            border:1px solid rgba(255,255,255,0.1); border-radius:8px;
                            color:#fff; font-size:13px;">
                    </div>
                </div>

                <div style="margin-top:15px; width: 100%;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">
                        Referensi Tiket Modul M03 <span style="color:#64748b; font-weight:400;">(Opsional — Nomor tiket serah terima maintenance)</span>
                    </label>
                    <input type="text" name="ticket_ref"
                        placeholder="Contoh: TKT-20260611-001"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42;
                        border:1px solid rgba(255,255,255,0.1); border-radius:8px;
                        color:#fff; font-size:13px;">
                </div>

                <div style="margin-top:15px; padding:12px; background:rgba(0,207,213,0.05); border-radius:8px; border:1px solid rgba(0,207,213,0.15);">
                    <p style="margin:0; color:#00cfd5; font-size:12px;">
                        <i class="fa-solid fa-info-circle me-1"></i> Logika Validasi Otomatis: Jika <strong>Nilai Tagihan Invoice</strong> setara dengan <strong>Total Nilai PO</strong>, status otomatis berubah menjadi <strong style="color: #22c55e;">MATCHED</strong> dan aman untuk dicairkan pembayarannya.
                    </p>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" name="input_vbr" style="background:#00cfd5; color:#021F42; font-weight:700; padding:10px 24px; border:none; border-radius:8px; font-size:13px; cursor:pointer;">
                        <i class="fa-solid fa-check-double me-1"></i> Eksekusi 3-Way Matching
                    </button>
                </div>
            </form>
        <?php else: ?>
            <p style="color:#64748b; font-size:13px; margin: 0;">Belum ada dokumen PO aktif yang siap diproses (Status: pending / approved).</p>
        <?php endif; ?>
    </div>

    <h4 style="color:#fff; margin-bottom:15px; font-size:15px; font-weight:600;">
        <i class="fa-solid fa-list me-2"></i> MONITORING RIWAYAT MATCHING LOGISTIK
    </h4>
    <div style="background: #032b5c; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.06); overflow-x: auto;">
        <table class="table-custom" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #fff;">
            <thead>
                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #FFB62A;">
                    <th style="padding: 12px 8px;">ID Transaksi</th>
                    <th style="padding: 12px 8px;">No. PO</th>
                    <th style="padding: 12px 8px;">No. Invoice Vendor</th>
                    <th style="padding: 12px 8px;">Ref. Tiket M03</th>
                    <th style="padding: 12px 8px;">Total Nilai PO</th>
                    <th style="padding: 12px 8px;">Nilai Invoice</th>
                    <th style="padding: 12px 8px;">Tgl Terima</th>
                    <th style="padding: 12px 8px; text-align: center;">Hasil 3-Way</th>
                    <th style="padding: 12px 8px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data_vbr && $data_vbr->num_rows > 0): ?>
                    <?php while ($row = $data_vbr->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 14px 8px;"><strong style="color:#00cfd5;">#<?= htmlspecialchars($row['Id'] ?? '') ?></strong></td>
                            <td style="padding: 14px 8px; color:#FFB62A;"><?= htmlspecialchars($row['po_number'] ?? '-') ?></td>
                            <td style="padding: 14px 8px; color:#a855f7;"><?= htmlspecialchars($row['no_invoice'] ?? '') ?></td>
                            <td style="padding: 14px 8px; color:#64748b;"><?= !empty($row['tiket']) ? htmlspecialchars($row['tiket']) : '—' ?></td>
                            <td style="padding: 14px 8px;">Rp <?= number_format($row['po_amount'] ?? 0, 0, ',', '.') ?></td>
                            <td style="padding: 14px 8px;">Rp <?= number_format($row['total_invoice'] ?? 0, 0, ',', '.') ?></td>
                            <td style="padding: 14px 8px; color: #cbd5e1;"><?= !empty($row['tgl_terima']) ? date('d M Y', strtotime($row['tgl_terima'])) : '-' ?></td>
                            <td style="padding: 14px 8px; text-align: center;">
                                <?php if ($row['status'] === 'matched'): ?>
                                    <span style="background:#22c55e22; color:#22c55e; padding:4px 10px;
                                        border-radius:20px; font-size:11px; font-weight:600;
                                        border:1px solid #22c55e44; display: inline-block;">✅ Matched</span>
                                <?php else: ?>
                                    <span style="background:#ef444422; color:#ef4444; padding:4px 10px;
                                        border-radius:20px; font-size:11px; font-weight:600;
                                        border:1px solid #ef444444; display: inline-block;">❌ No Match</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 14px 8px; text-align: center;">
                                <?php if ($row['status'] !== 'matched'): ?>
                                    <a href="?rematch=<?= $row['Id'] ?>" style="background:#FFB62A; color:#021F42; padding:6px 12px; border-radius:6px; font-size:11px; text-decoration:none; font-weight:600; display: inline-block;">
                                        <i class="fa-solid fa-rotate"></i> Re-match
                                    </a>
                                <?php else: ?>
                                    <span style="color:#64748b; font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:30px; color:#64748b;">
                            <i class="fa-solid fa-folder-open d-block fs-4 mb-2"></i> Belum ada data verifikasi logistik yang tercatat.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>