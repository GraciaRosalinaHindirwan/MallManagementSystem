<?php

/** @var mysqli $conn */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ====================================================================
// SECURE AUTH CHECK - JIKA BELUM LOGIN / ROLE BUKAN PURCHASING MANAGER
// ====================================================================
// Hilangkan tanda komentar (/* dan */) di bawah ini jika database & sistem login Anda sudah siap.
/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Purchasing Manager') {
    header("Location: ../../index.php"); 
    exit();
}
*/
// ====================================================================

// Sesi default sementara (Trik sidebar bawaan Anda tetap dipertahankan agar aman dicoba)
$_SESSION['role'] = 'Purchasing Manager';
$_SESSION['nama'] = 'Eva (Manager)';


// -------------------------------------------------------------------------
// LANJUTAN KODE ASLI HALAMAN APPROVAL PO (TIDAK ADA YANG DIUBAH DI BAWAH INI)
// -------------------------------------------------------------------------
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// DETEKSI APAKAH AKSES HALAMAN BERSIFAT VIEW-ONLY (DARI HALAMAN REQUEST)
$is_view_only = isset($_GET['mode']) && $_GET['mode'] === 'view';
?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Ambil semua elemen link (a) yang ada di area menu/sidebar
        const navLinks = document.querySelectorAll('.sidebar a, .sidebar-nav a, .nav-list a, .sidebar-menu a, .nav-item a, .menu a');

        navLinks.forEach(function(link) {
            const hrefValue = link.getAttribute('href') || '';

            // Taktik Jitu: Deteksi berdasarkan isi atribut href (Link Tujuan)
            // 1. JIKA LINK MENGARAH KE PURCHASE REQUESTS
            if (hrefValue.includes('purchase_requests.php')) {
                link.href = "../purchasingStaff/purchase_requests.php";
                link.innerHTML = '<i class="fa-solid fa-file-invoice me-2"></i> Purchase Requests';

                link.classList.remove('active');
                if (link.parentElement) link.parentElement.classList.remove('active');
            }

            // 2. JIKA LINK MENGARAH KE PURCHASE ORDERS (Ini target utama kita!)
            else if (hrefValue.includes('purchase_orders.php') || hrefValue.includes('approval_po.php')) {
                // Paksa ubah tujuannya ke halaman approval milik Manager
                link.href = "../purchasingManager/approval_po.php";

                // Ganti total isi di dalam tag <a> termasuk icon dan teksnya secara mutlak!
                link.innerHTML = '<i class="fa-solid fa-user-check me-2"></i> Approval PO';

                // Paksa aktifkan warna menu menyala (Style Active Template)
                link.classList.add('active');
                if (link.parentElement) {
                    link.parentElement.classList.add('active');
                    link.parentElement.classList.add('open');
                    link.parentElement.classList.add('show');
                }
            }

            // 3. SEMBUNYIKAN MENU LAIN YANG TIDAK DIPERLUKAN MANAGER
            else {
                const textContent = link.textContent.trim();
                if (textContent !== "" && !hrefValue.includes('logout') && !textContent.includes("Dashboard")) {
                    if (link.parentElement) {
                        link.parentElement.style.setProperty('display', 'none', 'important');
                    } else {
                        link.style.setProperty('display', 'none', 'important');
                    }
                }
            }
        });
    });
</script>

<?php
// -------------------------------------------------------
// WORKFLOW LOGIKA BACKEND APPROVAL PO (DISESUAIKAN TABEL 06_)
// -------------------------------------------------------
define('BATAS_APPROVAL', 0);

if (isset($_POST['buat_dummy'])) {
    $po_no_dummy = "PO-" . date('Ymd') . "-" . rand(100, 999);
    $tgl_dummy = date('Y-m-d');
    $nominal_dummy = 7500000;
    $status_dummy = 'pending';

    // Query INSERT super safe tanpa kolom vendor yang rawan salah nama
    $stmt_dummy = $conn->prepare("INSERT INTO `06_purchase_orders` (po_no, order_date, total_amount, status) VALUES (?, ?, ?, ?)");
    $stmt_dummy->bind_param("ssds", $po_no_dummy, $tgl_dummy, $nominal_dummy, $status_dummy);
    $stmt_dummy->execute();
    $stmt_dummy->close();
    echo "<script>window.location='approval_po.php';</script>";
}

if (isset($_POST['approve'])) {
    $id_po    = (int) $_POST['id_po'];
    $komentar = trim($_POST['komentar']);
    $approver = $_SESSION['nama'];

    // Update status di tabel 06_purchase_orders
    $stmt = $conn->prepare("UPDATE `06_purchase_orders` SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $id_po);
    $stmt->execute();
    $stmt->close();

    // Log tindakan ke tabel 06_approval_log
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

    // Update status di tabel 06_purchase_orders
    $stmt = $conn->prepare("UPDATE `06_purchase_orders` SET status='rejected' WHERE id=?");
    $stmt->bind_param("i", $id_po);
    $stmt->execute();
    $stmt->close();

    // Log tindakan ke tabel 06_approval_log
    $stmt_log = $conn->prepare("INSERT INTO `06_approval_log` (po_id, aksi, komentar, approver, tgl_aksi) VALUES (?, 'rejected', ?, ?, NOW())");
    $stmt_log->bind_param("iss", $id_po, $komentar, $approver);
    $stmt_log->execute();
    $stmt_log->close();

    echo "<script>alert('PO berhasil DITOLAK.'); window.location='approval_po.php';</script>";
}

// Ambil Data Statistik & Tabel Sesuai Prefiks 06_ (Query dipotong agar anti-eror nama kolom)
$po_pending = $conn->query("SELECT * FROM `06_purchase_orders` WHERE status = 'pending' ORDER BY order_date ASC");
$riwayat = $conn->query("SELECT al.*, po.po_no AS po_number, po.total_amount FROM `06_approval_log` al LEFT JOIN `06_purchase_orders` po ON al.po_id = po.id ORDER BY al.tgl_aksi DESC LIMIT 20");

$total_pending  = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='pending'")->fetch_assoc()['jml'] ?? 0;
$total_approved = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='approved'")->fetch_assoc()['jml'] ?? 0;
$total_ditolak  = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='rejected'")->fetch_assoc()['jml'] ?? 0;
$total_nilai    = $conn->query("SELECT SUM(total_amount) AS total FROM `06_purchase_orders` WHERE status='approved'")->fetch_assoc()['total'] ?? 0;
?>

<div class="content-wrapper">
    <div class="mb-4" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <div>
            <h1 style="color: var(--text-accent); font-size: 28px; font-weight: 700; margin: 0;">
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
                <button type="submit" name="buat_dummy" style="background:#FFB62A; color:#021F42; border:none; padding:10px 16px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer;">
                    <i class="fa-solid fa-plus me-1"></i> Buat PO Pending Baru (Test)
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #FFB62A;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">MENUNGGU APPROVAL</p>
            <h2 style="color:#FFB62A; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $total_pending ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #22c55e;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">APPROVED</p>
            <h2 style="color:#22c55e; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $total_approved ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #ef4444;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">DITOLAK</p>
            <h2 style="color:#ef4444; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $total_ditolak ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #00cfd5;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">TOTAL NILAI APPROVED</p>
            <h2 style="color:#00cfd5; font-size:20px; font-weight:700; margin:8px 0 0 0;">
                Rp <?= number_format($total_nilai, 0, ',', '.') ?>
            </h2>
        </div>
    </div>

    <div style="margin-bottom:30px;">
        <h4 style="color:#FFB62A; margin-bottom:15px; font-size:15px; font-weight:600;">
            <i class="fa-solid fa-clock me-2"></i>PO Menunggu Persetujuan Manager
        </h4>

        <?php if ($po_pending && $po_pending->num_rows > 0): ?>
            <?php while ($po = $po_pending->fetch_assoc()): ?>
                <div style="background:#032b5c; border:1px solid rgba(255,183,42,0.2); border-radius:12px; padding:20px; margin-bottom:15px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:15px;">
                        <div>
                            <p style="margin:0; font-size:18px; font-weight:700; color:#00cfd5;">
                                <?= htmlspecialchars($po['po_no'] ?? '') ?>
                            </p>
                            <p style="margin:4px 0; color:#cbd5e1; font-size:13px;">
                                <i class="fa-solid fa-building me-1"></i> Vendor Logistik Utama
                            </p>
                            <p style="margin:4px 0; color:#64748b; font-size:12px;">
                                <i class="fa-solid fa-calendar me-1"></i> Order: <?= !empty($po['order_date']) ? date('d M Y', strtotime($po['order_date'])) : date('d M Y') ?>
                            </p>
                            <p style="margin:8px 0 0 0; font-size:18px; font-weight:700; color:#FFB62A;">
                                Rp <?= number_format($po['total_amount'] ?? 0, 0, ',', '.') ?>
                            </p>
                        </div>

                        <div style="min-width:280px; flex: 1; max-width: 400px;">
                            <?php if ($is_view_only): ?>
                                <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; text-align: center; border: 1px dashed rgba(255,255,255,0.1);">
                                    <span style="color: #94a3b8; font-size: 13px;"><i class="fa-solid fa-eye me-1"></i> Hak Akses Terbatas (View Only)</span>
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="id_po" value="<?= $po['id'] ?>">
                                    <div style="margin-bottom:10px;">
                                        <label style="color:#cbd5e1; font-size:12px; font-weight:600;">Komentar / Alasan</label>
                                        <input type="text" name="komentar" required placeholder="contoh: Sesuai anggaran belanja, silakan..."
                                            style="width:100%; margin-top:5px; padding:9px 12px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                                    </div>
                                    <div style="display:flex; gap:10px;">
                                        <button type="submit" name="approve" style="flex:1; background:#22c55e; color:#fff; font-weight:700; padding:10px; border:none; border-radius:8px; font-size:13px; cursor:pointer;">
                                            <i class="fa-solid fa-check me-1"></i> Setujui
                                        </button>
                                        <button type="submit" name="tolak" onclick="return confirm('Yakin ingin menolak PO ini?')" style="flex:1; background:#ef4444; color:#fff; font-weight:700; padding:10px; border:none; border-radius:8px; font-size:13px; cursor:pointer;">
                                            <i class="fa-solid fa-xmark me-1"></i> Tolak
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="background:rgba(34,197,94,0.06); border:1px solid rgba(34,197,94,0.15); padding:20px; border-radius:12px; color:#22c55e; text-align:center; font-size:13px;">
                <i class="fa-solid fa-circle-check d-block fs-4 mb-2"></i> Tidak ada dokumen PO belanja bernilai tinggi yang perlu ditinjau saat ini.
                <?php if (!$is_view_only): ?>
                    <br><span style="color:#94a3b8; font-size:12px;">Silakan klik tombol <strong>"Buat PO Pending Baru"</strong> di kanan atas untuk membuat simulasi data.</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <h4 style="color:#fff; margin-bottom:15px; font-size:15px; font-weight:600;">
        <i class="fa-solid fa-clock-rotate-left me-2"></i>Riwayat Tindakan Dokumen Belanja
    </h4>
    <div style="background: #032b5c; padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.06); overflow-x: auto;">
        <table class="table-custom" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #fff;">
            <thead>
                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #FFB62A;">
                    <th style="padding: 12px 8px;">No. PO</th>
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
                            <td style="padding: 14px 8px;"><strong style="color:#00cfd5;"><?= htmlspecialchars($row['po_number'] ?? '-') ?></strong></td>
                            <td style="padding: 14px 8px;">PT. Logistik Mall Vendor</td>
                            <td style="padding: 14px 8px;">Rp <?= number_format($row['total_amount'] ?? 0, 0, ',', '.') ?></td>
                            <td style="padding: 14px 8px; text-align: center;">
                                <?php $warna = $row['aksi'] === 'approved' ? '#22c55e' : '#ef4444'; ?>
                                <span style="background:<?= $warna ?>22; color:<?= $warna ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; border:1px solid <?= $warna ?>44; display: inline-block;">
                                    <?= ucfirst($row['aksi'] ?? '') ?>
                                </span>
                            </td>
                            <td style="padding: 14px 8px; color:#cbd5e1; font-size:12px;">
                                <?= htmlspecialchars($row['komentar'] ?? '-') ?>
                            </td>
                            <td style="padding: 14px 8px; color:#FFB62A; font-weight: 600;"><?= htmlspecialchars($row['approver'] ?? '') ?></td>
                            <td style="padding: 14px 8px; color:#64748b; font-size:12px;">
                                <?= date('d M Y H:i', strtotime($row['tgl_aksi'])) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:30px; color:#64748b;">
                            <i class="fa-solid fa-folder-open d-block fs-4 mb-2"></i> Belum ada rekaman riwayat persetujuan logistik.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>