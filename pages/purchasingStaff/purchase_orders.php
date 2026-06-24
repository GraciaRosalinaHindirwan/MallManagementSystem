<?php
session_start();
// Menyesuaikan Role agar menu sidebar langsung terbuka sesuai hak akses navbar
$_SESSION['role'] = 'Purchasing Staff';
$_SESSION['nama'] = 'Eva';

if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// -------------------------------------------------------
// PBI-M06-03-02: BUAT PURCHASE ORDER (Disesuaikan Tabel 06_)
// -------------------------------------------------------
if (isset($_POST['tambah_po'])) {
    $po_no        = "PO-" . date('Ymd') . "-" . rand(100, 999);
    $pr_id        = !empty($_POST['pr_id']) ? (int) $_POST['pr_id'] : null;
    $vendor_name  = trim($_POST['vendor_name']); // Input dari form tetap vendor_name
    $order_date   = $_POST['order_date'];
    $total_amount = (float) $_POST['total_amount'];
    $status       = 'pending';

    // Kolom database disesuaikan dari vendor_name menjadi vendor
    $stmt = $conn->prepare(
        "INSERT INTO `06_purchase_orders` (po_no, pr_id, vendor, order_date, total_amount, status) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sissds", $po_no, $pr_id, $vendor_name, $order_date, $total_amount, $status);

    if ($stmt->execute()) {
        echo "<script>alert('Purchase Order $po_no berhasil dibuat!'); window.location='purchase_orders.php';</script>";
    } else {
        echo "<script>alert('Gagal membuat PO: " . $conn->error . "');</script>";
    }
    $stmt->close();
}

// -------------------------------------------------------
// HAPUS PO — hanya boleh jika masih 'pending' (Disesuaikan Tabel 06_)
// -------------------------------------------------------
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];

    $stmt = $conn->prepare("DELETE FROM `06_purchase_orders` WHERE id=? AND status='pending'");
    $stmt->bind_param("i", $id_hapus);
    $stmt->execute();
    $stmt->close();

    echo "<script>window.location='purchase_orders.php';</script>";
}

// -------------------------------------------------------
// Query data beralaskan database terpasang (Disesuaikan Tabel 06_)
// -------------------------------------------------------
$pr_list = $conn->query("SELECT id, pr_no, description, estimated_amount FROM `06_purchase_requests` ORDER BY id DESC");
$data_po = $conn->query("SELECT po.*, pr.pr_no FROM `06_purchase_orders` po LEFT JOIN `06_purchase_requests` pr ON po.pr_id = pr.id ORDER BY po.id DESC");

$total_po = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders`")->fetch_assoc()['jml'] ?? 0;
$pending  = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='pending'")->fetch_assoc()['jml'] ?? 0;
$ordered  = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='ordered'")->fetch_assoc()['jml'] ?? 0;
$received = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='received'")->fetch_assoc()['jml'] ?? 0;
?>

<div class="content-wrapper">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 28px; font-weight: 700; margin: 0;">
            Purchase Order <span style="font-size: 15px; color: #64748b; font-weight: normal;">(PBI-M06-03-02)</span>
        </h1>
        <p style="color: #cbd5e1; margin-top: 5px; font-size: 14px;">
            Proses pembuatan PO ke vendor berdasarkan Purchase Request yang telah <strong style="color:#22c55e;">disetujui (Approved)</strong>.
        </p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #00cfd5;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">TOTAL PO</p>
            <h2 style="color:#fff; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $total_po ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #FFB62A;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">PENDING</p>
            <h2 style="color:#FFB62A; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $pending ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #a855f7;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">ORDERED</p>
            <h2 style="color:#a855f7; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $ordered ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #22c55e;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">RECEIVED</p>
            <h2 style="color:#22c55e; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $received ?></h2>
        </div>
    </div>

    <div style="background:#032b5c; padding:25px; border-radius:12px; margin-bottom:30px; border:1px solid rgba(255,255,255,0.06);">
        <h4 style="color:#FFB62A; margin-bottom:20px; font-size:15px; font-weight:600;">
            <i class="fa-solid fa-cart-shopping me-2"></i>Buat Purchase Order Baru
        </h4>
        <form method="POST">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:15px;">
                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Referensi PR <span style="color:#64748b; font-weight:400;">(opsional)</span></label>
                    <select name="pr_id" style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                        <option value="">-- Tanpa PR / Langsung PO --</option>
                        <?php if ($pr_list): while ($pr = $pr_list->fetch_assoc()): ?>
                                <option value="<?= $pr['id'] ?>">
                                    <?= htmlspecialchars($pr['pr_no']) ?> — <?= htmlspecialchars(substr($pr['description'], 0, 30)) ?>... (Est. Rp <?= number_format($pr['estimated_amount'], 0, ',', '.') ?>)
                                </option>
                        <?php endwhile;
                        endif; ?>
                    </select>
                </div>
                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Nama Vendor</label>
                    <input type="text" name="vendor_name" required placeholder="contoh: PT. Sumber Makmur..." style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                </div>
                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Tanggal Order</label>
                    <input type="date" name="order_date" value="<?= date('Y-m-d'); ?>" required style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                </div>
                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Total Nilai PO (Rp)</label>
                    <input type="number" name="total_amount" required min="0" step="0.01" placeholder="contoh: 5000000" style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                </div>
            </div>

            <div style="margin-top:15px; padding:12px; background:rgba(255,183,42,0.08); border-radius:8px; border:1px solid rgba(255,183,42,0.2);">
                <p style="margin:0; color:#FFB62A; font-size:12px;">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> PO dengan nilai <strong>≥ Rp 5.000.000</strong> memerlukan persetujuan Purchasing Manager sebelum diproses ke vendor.
                </p>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" name="tambah_po" style="background:#00cfd5; color:#021F42; font-weight:700; padding:10px 24px; border:none; border-radius:8px; font-size:13px; cursor:pointer;">
                    <i class="fa-solid fa-paper-plane me-1"></i> Buat Purchase Order
                </button>
            </div>
        </form>
    </div>

    <h4 style="color:#fff; margin-bottom:15px; font-size:15px; font-weight:600;">
        <i class="fa-solid fa-list me-2"></i>Daftar Purchase Order resmi
    </h4>

    <div style="background: #032b5c; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
        <table class="table-custom" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #fff;">
            <thead>
                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #FFB62A;">
                    <th style="padding: 12px 8px;">No. PO</th>
                    <th style="padding: 12px 8px;">No. PR</th>
                    <th style="padding: 12px 8px;">Vendor</th>
                    <th style="padding: 12px 8px;">Tanggal Order</th>
                    <th style="padding: 12px 8px;">Total Nilai</th>
                    <th style="padding: 12px 8px;">Status</th>
                    <th style="padding: 12px 8px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data_po && $data_po->num_rows > 0): ?>
                    <?php while ($row = $data_po->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 14px 8px;"><strong style="color:#00cfd5;"><?= htmlspecialchars($row['po_no']) ?></strong></td>
                            <td style="padding: 14px 8px; color:#FFB62A;">
                                <?= $row['pr_no'] ? htmlspecialchars($row['pr_no']) : '—' ?>
                            </td>
                            <!-- Diubah dari $row['vendor_name'] menjadi $row['vendor'] mengikuti database tim -->
                            <td style="padding: 14px 8px;"><?= htmlspecialchars($row['vendor'] ?? '') ?></td>
                            <td style="padding: 14px 8px"><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                            <td style="padding: 14px 8px;">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                            <td style="padding: 14px 8px;">
                                <?php
                                $warna = match ($row['status']) {
                                    'approved' => '#22c55e',
                                    'received' => '#00cfd5',
                                    'ordered'  => '#a855f7',
                                    'paid'     => '#22c55e',
                                    default    => '#FFB62A',
                                };
                                ?>
                                <span style="background:<?= $warna ?>22; color:<?= $warna ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; border:1px solid <?= $warna ?>44;">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td style="padding: 14px 8px; text-align: center;">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Hapus PO ini?')" style="background:#ef4444; color:#fff; padding:6px 12px; border-radius:6px; font-size:11px; text-decoration:none; font-weight:600; display: inline-block;">
                                        <i class="fa-solid fa-trash me-1"></i> Hapus
                                    </a>
                                <?php else: ?>
                                    <span style="color:#64748b; font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:30px; color:#64748b;">
                            <i class="fa-solid fa-folder-open d-block fs-4 mb-2"></i> Belum ada dokumen Purchase Order.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>