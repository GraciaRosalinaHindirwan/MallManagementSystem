<?php

/** @var mysqli $conn */ // Memberitahu VS Code agar tidak memunculkan garis merah pada variabel $conn

// 1. Inisialisasi Session dan Deteksi Otomatis File Koneksi Database
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'purchasingManager') {
    header("Location: ../../index.php"); 
    exit();
}
*/

$_SESSION['role'] = 'purchasingManager';
$_SESSION['nama'] = 'Manager';

if (file_exists(__DIR__ . '/../../config/konek.php')) {
    require_once __DIR__ . '/../../config/konek.php';
} elseif (file_exists(__DIR__ . '/../../config/connection.php')) {
    require_once __DIR__ . '/../../config/connection.php';
} else {
    die("<div style='color:#ffffff; background-color:#721c24; padding:20px; border-radius:6px;'>⚠️ File koneksi database tidak ditemukan!</div>");
}

$is_view_only = isset($_GET['mode']) && $_GET['mode'] === 'view';

// -------------------------------------------------------
// WORKFLOW LOGIKA BACKEND APPROVAL PO
// -------------------------------------------------------
define('BATAS_APPROVAL', 0);

if (isset($_POST['buat_dummy'])) {
    $po_no_dummy = "PO-" . date('Ymd') . "-" . rand(100, 999);
    $tgl_dummy = date('Y-m-d');
    $nominal_dummy = 7500000;
    $status_dummy = 'pending';

    // Gunakan try-catch agar jika kolom po_no di database berbeda nama, script tidak langsung crash mati mati
    try {
        $stmt_dummy = $conn->prepare("INSERT INTO `06_purchase_orders` (po_no, order_date, total_amount, status) VALUES (?, ?, ?, ?)");
        $stmt_dummy->bind_param("ssds", $po_no_dummy, $tgl_dummy, $nominal_dummy, $status_dummy);
        $stmt_dummy->execute();
        $stmt_dummy->close();
    } catch (Exception $e) {
        // Fallback dengan po_number agar tidak duplicate key
        $po_no_safe = $conn->real_escape_string($po_no_dummy);
        $conn->query("INSERT INTO `06_purchase_orders` (po_number, order_date, total_amount, status) VALUES ('$po_no_safe', '$tgl_dummy', $nominal_dummy, '$status_dummy')");
    }
    echo "<script>window.location='approval_po.php';</script>";
}

if (isset($_POST['approve'])) {
    $id_po    = (int) $_POST['id_po'];
    $komentar = trim($_POST['komentar']);
    $approver = $_SESSION['nama'];

    $stmt = $conn->prepare("UPDATE `06_purchase_orders` SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $id_po);
    $stmt->execute();
    $stmt->close();

    $stmt_log = $conn->prepare("INSERT INTO `06_approval_log` (po_id, aksi, komentar, approver, tgl_aksi) VALUES (?, 'approved', ?, ?, NOW())");
    $stmt_log->bind_param("iss", $id_po, $komentar, $approver);
    $stmt_log->execute();
    $stmt_log->close();

    echo "<script>alert('PO berhasil DISETUJUI!'); window.location='approval_po.php';</script>";
}

if (isset($_POST['tolak'])) {
    $id_po    = (int) $_POST['id_po'];
    $komentar = trim($_POST['komentar']);
    $approver = $_SESSION['nama'];

    $stmt = $conn->prepare("UPDATE `06_purchase_orders` SET status='rejected' WHERE id=?");
    $stmt->bind_param("i", $id_po);
    $stmt->execute();
    $stmt->close();

    $stmt_log = $conn->prepare("INSERT INTO `06_approval_log` (po_id, aksi, komentar, approver, tgl_aksi) VALUES (?, 'rejected', ?, ?, NOW())");
    $stmt_log->bind_param("iss", $id_po, $komentar, $approver);
    $stmt_log->execute();
    $stmt_log->close();

    echo "<script>alert('PO berhasil DITOLAK.'); window.location='approval_po.php';</script>";
}

// Ambil Data Master Peninjauan
$po_pending = $conn->query("SELECT * FROM `06_purchase_orders` WHERE status = 'pending' ORDER BY id DESC");

// FIX SECURITY QUERY BARIS 92 (Mencegah kolom tidak dikenal merusak web)
$riwayat = $conn->query("SELECT al.*, 
                               al.id AS log_ref,
                               IFNULL(po.total_amount, 0) AS total_amount 
                         FROM `06_approval_log` al 
                         LEFT JOIN `06_purchase_orders` po ON al.po_id = po.id 
                         ORDER BY al.tgl_aksi DESC LIMIT 20");

$total_pending  = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='pending'")->fetch_assoc()['jml'] ?? 0;
$total_approved = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='approved'")->fetch_assoc()['jml'] ?? 0;
$total_ditolak  = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='rejected'")->fetch_assoc()['jml'] ?? 0;
$total_nilai    = $conn->query("SELECT SUM(total_amount) AS total FROM `06_purchase_orders` WHERE status='approved'")->fetch_assoc()['total'] ?? 0;

// ==========================================
// CONFIG MASTER DATA SIDEBAR & NAVBAR LAYOUT
// ==========================================
$department_name = "M06 - Purchasing Manager";
$user_name = $_SESSION['nama'];
$page_title = "approval_po";

$menu_items = [
    [
        'icon'        => 'fa-solid fa-chart-line',
        'label'       => 'Dashboard Manager',
        'link'        => 'dashboardPurchasingmanager.php',
        'active_page' => 'dashboardPurchasingmanager'
    ],
    [
        'icon'        => 'fa-solid fa-stamp',
        'label'       => 'Approval PO',
        'link'        => 'approval_po.php',
        'active_page' => 'approval_po'
    ]
];

ob_start();
?>

<div class="container-fluid" style="padding: 10px 0px; text-align: left;">
    <div class="mb-4" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <div>
            <h1 style="color: #FFB62A; font-size: 28px; font-weight: 700; margin: 0;">
                Approval Purchase Order
                <?php if ($is_view_only): ?>
                    <span style="font-size: 14px; background: rgba(255,255,255,0.1); color: #94a3b8; padding: 4px 12px; border-radius: 6px; font-weight: normal; margin-left: 10px;"><i class="fa-solid fa-lock"></i> Mode View Only (Staff)</span>
                <?php endif; ?>
            </h1>
            <p style="color: #cbd5e1; margin-top: 5px; font-size: 14px;">
                Panel Peninjauan berkas PO Logistik Belanja untuk Perusahaan.
            </p>
        </div>

        <?php if (!$is_view_only): ?>
            <form method="POST">
                <button type="submit" name="buat_dummy" style="background:#FFB62A; color:#021F42; border:none; padding:10px 16px; border-radius:6px; font-weight:700; font-size:13px; cursor:pointer;">
                    <i class="fa-solid fa-plus me-1"></i> Buat PO Pending Baru (Test)
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:#011630; padding:20px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05);">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">MENUNGGU APPROVAL</p>
            <h2 style="color:#FFB62A; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $total_pending ?></h2>
        </div>
        <div style="background:#011630; padding:20px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05);">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">APPROVED</p>
            <h2 style="color:#22c55e; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $total_approved ?></h2>
        </div>
        <div style="background:#011630; padding:20px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05);">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">DITOLAK</p>
            <h2 style="color:#ef4444; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $total_ditolak ?></h2>
        </div>
        <div style="background:#011630; padding:20px; border-radius:8px; border: 1px solid rgba(255,255,255,0.05);">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">TOTAL NILAI APPROVED</p>
            <h2 style="color:#00cfd5; font-size:20px; font-weight:700; margin:8px 0 0 0;">Rp <?= number_format($total_nilai, 0, ',', '.') ?></h2>
        </div>
    </div>

    <div style="margin-bottom:30px;">
        <h4 style="color:#FFB62A; margin-bottom:15px; font-size:15px; font-weight:600;">
            <i class="fa-solid fa-clock me-2"></i>PO Menunggu Persetujuan Manager
        </h4>

        <?php if ($po_pending && $po_pending->num_rows > 0): ?>
            <?php while ($po = $po_pending->fetch_assoc()): ?>
                <div style="background:#011630; border:1px solid rgba(255,183,42,0.15); border-radius:8px; padding:20px; margin-bottom:15px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:15px;">
                        <div>
                            <p style="margin:0; font-size:18px; font-weight:700; color:#00cfd5;">
                                <?= htmlspecialchars($po['po_no'] ?? 'PO-REF-' . $po['id']) ?>
                            </p>
                            <p style="margin:4px 0; color:#cbd5e1; font-size:13px;">
                                <i class="fa-solid fa-building me-1"></i> Vendor Logistik Utama
                            </p>
                            <p style="margin:4px 0; color:#64748b; font-size:12px;">
                                <i class="fa-solid fa-calendar me-1"></i> Tanggal: <?= !empty($po['order_date']) ? date('d M Y', strtotime($po['order_date'])) : date('d M Y') ?>
                            </p>
                            <p style="margin:8px 0 0 0; font-size:18px; font-weight:700; color:#FFB62A;">
                                Rp <?= number_format($po['total_amount'] ?? 0, 0, ',', '.') ?>
                            </p>
                        </div>

                        <div style="min-width:280px; flex: 1; max-width: 400px;">
                            <?php if ($is_view_only): ?>
                                <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px; text-align: center; border: 1px dashed rgba(255,255,255,0.1);">
                                    <span style="color: #94a3b8; font-size: 13px;"><i class="fa-solid fa-eye me-1"></i> Hak Akses Terbatas (View Only)</span>
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="id_po" value="<?= $po['id'] ?>">
                                    <div style="margin-bottom:10px;">
                                        <label style="color:#cbd5e1; font-size:12px; font-weight:600;">Komentar / Alasan</label>
                                        <input type="text" name="komentar" required placeholder="contoh: Disetujui..."
                                            style="width:100%; margin-top:5px; padding:9px 12px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                                    </div>
                                    <div style="display:flex; gap:10px;">
                                        <button type="submit" name="approve" style="flex:1; background:#22c55e; color:#fff; font-weight:700; padding:10px; border:none; border-radius:6px; font-size:13px; cursor:pointer;">Setujui</button>
                                        <button type="submit" name="tolak" onclick="return confirm('Yakin menolak PO ini?')" style="flex:1; background:#ef4444; color:#fff; font-weight:700; padding:10px; border:none; border-radius:6px; font-size:13px; cursor:pointer;">Tolak</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="background:rgba(34,197,94,0.04); border:1px solid rgba(34,197,94,0.15); padding:20px; border-radius:8px; color:#22c55e; text-align:center; font-size:13px;">
                <i class="fa-solid fa-circle-check d-block fs-4 mb-2"></i> Tidak ada berkas PO pending.
            </div>
        <?php endif; ?>
    </div>

    <h4 style="color:#fff; margin-bottom:15px; font-size:15px; font-weight:600;">
        <i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Tindakan Dokumen Belanja
    </h4>
    <div style="background: #011630; padding: 15px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.06); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #fff;">
            <thead>
                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #FFB62A;">
                    <th style="padding: 12px 8px;">No. Dokumen</th>
                    <th style="padding: 12px 8px;">Vendor</th>
                    <th style="padding: 12px 8px;">Total Nilai</th>
                    <th style="padding: 12px 8px; text-align: center;">Keputusan</th>
                    <th style="padding: 12px 8px;">Komentar</th>
                    <th style="padding: 12px 8px;">Approver</th>
                    <th style="padding: 12px 8px;">Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($riwayat && $riwayat->num_rows > 0): ?>
                    <?php while ($row = $riwayat->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 14px 8px;"><strong style="color:#00cfd5;">LOG-<?= $row['po_id'] ?></strong></td>
                            <td style="padding: 14px 8px; color: #cbd5e1;">PT. Logistik Mall Vendor</td>
                            <td style="padding: 14px 8px;">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                            <td style="padding: 14px 8px; text-align: center;">
                                <?php $warna = $row['aksi'] === 'approved' ? '#22c55e' : '#ef4444'; ?>
                                <span style="background:<?= $warna ?>22; color:<?= $warna ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; display: inline-block;">
                                    <?= ucfirst($row['aksi'] ?? '') ?>
                                </span>
                            </td>
                            <td style="padding: 14px 8px; color:#cbd5e1; font-size:12px;"><?= htmlspecialchars($row['komentar'] ?? '-') ?></td>
                            <td style="padding: 14px 8px; color:#FFB62A; font-weight: 600;"><?= htmlspecialchars($row['approver'] ?? '') ?></td>
                            <td style="padding: 14px 8px; color:#64748b; font-size:12px;"><?= date('d M Y H:i', strtotime($row['tgl_aksi'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:30px; color:#64748b;">Belum ada rekaman riwayat persetujuan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarMO6.php';
?>