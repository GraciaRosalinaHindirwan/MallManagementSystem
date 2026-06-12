<?php
session_start();
$_SESSION['role'] = 'Purchasing Manager';
$_SESSION['nama'] = 'Eva (Manager)';

if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

define('BATAS_APPROVAL', 5000000);

// Approve PO
if (isset($_POST['approve'])) {
    $id_po    = (int) $_POST['id_po'];
    $komentar = $conn->real_escape_string($_POST['komentar']);
    $approver = $_SESSION['nama'];

    $conn->query("UPDATE purchase_orders SET status='Disetujui' WHERE id_po=$id_po");
    $conn->query("INSERT INTO approval_log (id_po, aksi, komentar, approver, tgl_aksi) VALUES ($id_po, 'Disetujui', '$komentar', '$approver', NOW())");
    echo "<script>alert('PO berhasil DISETUJUI!'); window.location='approval_po.php';</script>";
}

// Tolak PO
if (isset($_POST['tolak'])) {
    $id_po    = (int) $_POST['id_po'];
    $komentar = $conn->real_escape_string($_POST['komentar']);
    $approver = $_SESSION['nama'];

    $conn->query("UPDATE purchase_orders SET status='Ditolak' WHERE id_po=$id_po");
    $conn->query("INSERT INTO approval_log (id_po, aksi, komentar, approver, tgl_aksi) VALUES ($id_po, 'Ditolak', '$komentar', '$approver', NOW())");

    $po = $conn->query("SELECT id_pr FROM purchase_orders WHERE id_po=$id_po")->fetch_assoc();
    if ($po) {
        $conn->query("UPDATE purchase_requests SET status='Disetujui' WHERE id_pr=" . $po['id_pr']);
    }
    echo "<script>alert('PO berhasil DITOLAK!'); window.location='approval_po.php';</script>";
}

// Auto-approve PO di bawah batas nilai
$po_auto = $conn->query("SELECT * FROM purchase_orders WHERE status='Menunggu Approval' AND total_harga < " . BATAS_APPROVAL);
if ($po_auto && $po_auto->num_rows > 0) {
    while ($po = $po_auto->fetch_assoc()) {
        $conn->query("UPDATE purchase_orders SET status='Disetujui' WHERE id_po=" . $po['id_po']);
        $conn->query("INSERT INTO approval_log (id_po, aksi, komentar, approver, tgl_aksi) VALUES (" . $po['id_po'] . ", 'Disetujui', 'Auto-approved (di bawah batas nilai)', 'System', NOW())");
    }
}

// Data PO menunggu approval
$po_pending = $conn->query("SELECT * FROM purchase_orders WHERE status='Menunggu Approval' AND total_harga >= " . BATAS_APPROVAL . " ORDER BY tgl_dibuat ASC");

// Riwayat approval
$riwayat = $conn->query("
    SELECT al.*, po.no_po, po.nama_vendor, po.nama_item, po.total_harga
    FROM approval_log al
    LEFT JOIN purchase_orders po ON al.id_po = po.id_po
    ORDER BY al.tgl_aksi DESC LIMIT 20
");

// Statistik
$total_pending  = $conn->query("SELECT COUNT(*) as jml FROM purchase_orders WHERE status='Menunggu Approval' AND total_harga >= " . BATAS_APPROVAL)->fetch_assoc()['jml'] ?? 0;
$total_approved = $conn->query("SELECT COUNT(*) as jml FROM purchase_orders WHERE status='Disetujui'")->fetch_assoc()['jml'] ?? 0;
$total_ditolak  = $conn->query("SELECT COUNT(*) as jml FROM purchase_orders WHERE status='Ditolak'")->fetch_assoc()['jml'] ?? 0;
$total_nilai    = $conn->query("SELECT SUM(total_harga) as total FROM purchase_orders WHERE status='Disetujui'")->fetch_assoc()['total'] ?? 0;
?>

<div class="content-container">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 32px; font-weight: 700; margin: 0;">
            Approval Purchase Order <span style="font-size: 16px; color: #64748b;">(PBI-M06-03-04)</span>
        </h1>
        <p style="color: #cbd5e1; margin-top: 5px;">
            Persetujuan PO untuk nilai di atas 
            <strong style="color:#FFB62A;">Rp <?= number_format(BATAS_APPROVAL, 0, ',', '.') ?></strong>. 
            PO di bawah batas disetujui otomatis oleh sistem.
        </p>
    </div>

    <!-- STATISTIK -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #FFB62A;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">MENUNGGU APPROVAL</p>
            <h2 style="color:#FFB62A; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $total_pending ?></h2>
            <p style="color:#64748b; font-size:11px; margin:5px 0 0 0;">Perlu tindakan segera</p>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #22c55e;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">DISETUJUI</p>
            <h2 style="color:#22c55e; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $total_approved ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #ef4444;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">DITOLAK</p>
            <h2 style="color:#ef4444; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $total_ditolak ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #00cfd5;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">TOTAL NILAI DISETUJUI</p>
            <h2 style="color:#00cfd5; font-size:20px; font-weight:700; margin:8px 0 0 0;">Rp <?= number_format($total_nilai, 0, ',', '.') ?></h2>
        </div>
    </div>

    <!-- PO MENUNGGU APPROVAL -->
    <div style="margin-bottom:30px;">
        <h4 style="color:#FFB62A; margin-bottom:15px; font-size:16px; font-weight:600;">
            <i class="fa-solid fa-clock"></i> PO Menunggu Persetujuan Manager
        </h4>

        <?php if ($po_pending && $po_pending->num_rows > 0): ?>
            <?php while ($po = $po_pending->fetch_assoc()): ?>
            <div style="background:#032b5c; border:1px solid rgba(255,183,42,0.3); border-radius:12px; padding:20px; margin-bottom:15px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:15px;">
                    <div>
                        <p style="margin:0; font-size:18px; font-weight:700; color:#00cfd5;"><?= $po['no_po'] ?></p>
                        <p style="margin:4px 0; color:#cbd5e1; font-size:14px;">
                            <i class="fa-solid fa-box"></i> <?= htmlspecialchars($po['nama_item']) ?> — <?= $po['jumlah'] ?> <?= $po['satuan'] ?>
                        </p>
                        <p style="margin:4px 0; color:#cbd5e1; font-size:14px;">
                            <i class="fa-solid fa-building"></i> <?= htmlspecialchars($po['nama_vendor']) ?>
                        </p>
                        <p style="margin:4px 0; color:#cbd5e1; font-size:13px;">
                            <i class="fa-solid fa-calendar"></i> Pengiriman: <?= date('d M Y', strtotime($po['tgl_pengiriman'])) ?>
                        </p>
                        <p style="margin:8px 0 0 0; font-size:20px; font-weight:700; color:#FFB62A;">
                            Rp <?= number_format($po['total_harga'], 0, ',', '.') ?>
                        </p>
                    </div>
                    <div style="min-width:280px;">
                        <form method="POST">
                            <input type="hidden" name="id_po" value="<?= $po['id_po'] ?>">
                            <div style="margin-bottom:10px;">
                                <label style="color:#cbd5e1; font-size:12px; font-weight:600;">Komentar / Alasan</label>
                                <input type="text" name="komentar" placeholder="contoh: Budget sesuai, silakan proses..."
                                    style="width:100%; margin-top:5px; padding:9px 12px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                            </div>
                            <div style="display:flex; gap:10px;">
                                <button type="submit" name="approve"
                                    style="flex:1; background:#22c55e; color:#fff; font-weight:700; padding:9px; border:none; border-radius:8px; font-size:13px; cursor:pointer;">
                                    <i class="fa-solid fa-check"></i> Setujui
                                </button>
                                <button type="submit" name="tolak"
                                    onclick="return confirm('Yakin ingin menolak PO ini?')"
                                    style="flex:1; background:#ef4444; color:#fff; font-weight:700; padding:9px; border:none; border-radius:8px; font-size:13px; cursor:pointer;">
                                    <i class="fa-solid fa-xmark"></i> Tolak
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.2); padding:20px; border-radius:12px; color:#22c55e; text-align:center;">
                <i class="fa-solid fa-circle-check" style="font-size:24px; margin-bottom:8px; display:block;"></i>
                Tidak ada PO yang menunggu persetujuan. Semua sudah ditangani!
            </div>
        <?php endif; ?>
    </div>

    <!-- RIWAYAT APPROVAL -->
    <h4 style="color:#fff; margin-bottom:15px; font-size:16px; font-weight:600;">
        <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Approval
    </h4>

    <table class="table-custom">
        <thead>
            <tr>
                <th>No. PO</th>
                <th>Vendor</th>
                <th>Item</th>
                <th>Total Harga</th>
                <th>Keputusan</th>
                <th>Komentar</th>
                <th>Approver</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($riwayat && $riwayat->num_rows > 0): ?>
                <?php while ($row = $riwayat->fetch_assoc()): ?>
                <tr>
                    <td><strong style="color:#00cfd5;"><?= $row['no_po'] ?></strong></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($row['nama_vendor'] ?? '-') ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($row['nama_item'] ?? '-') ?></td>
                    <td>Rp <?= number_format($row['total_harga'] ?? 0, 0, ',', '.') ?></td>
                    <td>
                        <?php
                            $warna = match($row['aksi']) {
                                'Disetujui' => '#22c55e',
                                'Ditolak'   => '#ef4444',
                                default     => '#94a3b8',
                            };
                        ?>
                        <span style="background:<?= $warna ?>22; color:<?= $warna ?>; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; border:1px solid <?= $warna ?>44;">
                            <?= $row['aksi'] ?>
                        </span>
                    </td>
                    <td style="font-size:12px; color:#cbd5e1;"><?= htmlspecialchars($row['komentar'] ?? '-') ?></td>
                    <td style="font-size:13px; color:#FFB62A;"><?= $row['approver'] ?></td>
                    <td style="font-size:12px; color:#64748b;"><?= date('d M Y H:i', strtotime($row['tgl_aksi'])) ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding:30px; color:#64748b;">
                        Belum ada riwayat approval.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>
