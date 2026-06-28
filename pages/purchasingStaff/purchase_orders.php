<?php
/** @var mysqli $conn */ // Memberitahu VS Code agar tidak memunculkan garis merah pada variabel $conn

// 1. Inisialisasi Session dan Deteksi Otomatis File Koneksi Database
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'purchasingStaff') {
    header("Location: ../../index.php"); 
    exit();
}
*/

// Menyesuaikan Role agar menu sidebar langsung terbuka sesuai hak akses navbar
$_SESSION['role'] = 'purchasingStaff';
$_SESSION['nama'] = 'Staff';

if (file_exists(__DIR__ . '/../../config/konek.php')) {
    require_once __DIR__ . '/../../config/konek.php';
} elseif (file_exists(__DIR__ . '/../../config/connection.php')) {
    require_once __DIR__ . '/../../config/connection.php';
} else {
    die("<div style='color:#ffffff; background-color:#721c24; padding:20px; border-radius:6px;'>⚠️ File koneksi database tidak ditemukan!</div>");
}

// -------------------------------------------------------
// PBI-M06-03-02: BUAT PURCHASE ORDER (Disesuaikan Tabel 06_)
// -------------------------------------------------------
if (isset($_POST['tambah_po'])) {
    $po_number    = "PO-" . date('Ymd') . "-" . rand(100, 999);
    $pr_id        = !empty($_POST['pr_id']) ? (int) $_POST['pr_id'] : null;
    $vendor_name  = trim($_POST['vendor_name']); 
    $order_date   = $_POST['order_date'];
    $total_amount = (float) $_POST['total_amount'];
    $status       = 'pending';

    // DISESUAIKAN: Menggunakan `po_number` dan `vendor_name` sesuai database asli
    $stmt = $conn->prepare(
        "INSERT INTO `06_purchase_orders` (po_number, pr_id, vendor_name, order_date, total_amount, status) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sissds", $po_number, $pr_id, $vendor_name, $order_date, $total_amount, $status);

    if ($stmt->execute()) {
        echo "<script>alert('Purchase Order $po_number berhasil dibuat!'); window.location='purchase_orders.php';</script>";
        exit();
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
    exit();
}

// -------------------------------------------------------
// Query data beralaskan database terpasang (Disesuaikan Tabel 06_)
// -------------------------------------------------------
$pr_list = $conn->query("SELECT id, pr_number, description, estimated_amount FROM `06_purchase_requests` ORDER BY id DESC");

// DISESUAIKAN: po.po_no menjadi po.po_number
$data_po = $conn->query("SELECT po.*, pr.pr_number FROM `06_purchase_orders` po LEFT JOIN `06_purchase_requests` pr ON po.pr_id = pr.id ORDER BY po.id DESC");

$total_po = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders`")->fetch_assoc()['jml'] ?? 0;
$pending  = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='pending'")->fetch_assoc()['jml'] ?? 0;
$ordered  = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='ordered'")->fetch_assoc()['jml'] ?? 0;
$received = $conn->query("SELECT COUNT(*) AS jml FROM `06_purchase_orders` WHERE status='received'")->fetch_assoc()['jml'] ?? 0;


$department_name = "Purchasing Staff - Purchase Orders";
$user_name = $_SESSION['nama'];
$role = $_SESSION['role'] ?? '';
$page_title = "purchase_orders"; 


$menu_items = [
    [
        'icon'        => 'fa-solid fa-gauge',
        'label'       => 'Dashboard Staff',
        'link'        => 'dashboardPurchasingstaff.php',
        'active_page' => 'dashboardPurchasingstaff'
    ],
    [
        'icon'        => 'fa-solid fa-cart-shopping',
        'label'       => 'Purchase Requests',
        'link'        => 'purchase_requests.php',
        'active_page' => 'purchase_requests' // Otomatis menyala aktif jika filenya purchase_requests.php
    ],
    [
        'icon'        => 'fa-solid fa-file-invoice-dollar',
        'label'       => 'Purchase Orders',
        'link'        => 'purchase_orders.php',
        'active_page' => 'purchase_orders'
    ]
];

// Mulai menangkap output visual komponen tengah halaman
ob_start();
?>

<div class="container-fluid" style="padding: 10px 0px; text-align: left;">
    <div class="mb-4">
        <h4 class="mb-1" style="color: #FFB62A; font-weight: 700; margin: 0;">
            Purchase Order <span style="font-size: 14px; color: #cbd5e1; font-weight: normal;">(PBI-M06-03-02)</span>
        </h4>
        <p style="color: #cbd5e1; margin-top: 5px; font-size: 13px; opacity: 0.9;">
            Proses pembuatan PO ke vendor berdasarkan Purchase Request yang telah <strong style="color:#22c55e;">disetujui (Approved)</strong>.
        </p>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; margin-bottom:30px;">
        <div style="background:#011630; padding:20px; border-radius:8px; border-left:5px solid #00cfd5; border: 1px solid rgba(255,255,255,0.05);">
            <p style="color:#cbd5e1; font-size:11px; letter-spacing:1px; margin:0; font-weight:600;">TOTAL PO</p>
            <h2 style="color:#fff; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $total_po ?></h2>
        </div>
        <div style="background:#011630; padding:20px; border-radius:8px; border-left:5px solid #FFB62A; border: 1px solid rgba(255,255,255,0.05);">
            <p style="color:#cbd5e1; font-size:11px; letter-spacing:1px; margin:0; font-weight:600;">PENDING</p>
            <h2 style="color:#FFB62A; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $pending ?></h2>
        </div>
        <div style="background:#011630; padding:20px; border-radius:8px; border-left:5px solid #a855f7; border: 1px solid rgba(255,255,255,0.05);">
            <p style="color:#cbd5e1; font-size:11px; letter-spacing:1px; margin:0; font-weight:600;">ORDERED</p>
            <h2 style="color:#a855f7; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $ordered ?></h2>
        </div>
        <div style="background:#011630; padding:20px; border-radius:8px; border-left:5px solid #22c55e; border: 1px solid rgba(255,255,255,0.05);">
            <p style="color:#cbd5e1; font-size:11px; letter-spacing:1px; margin:0; font-weight:600;">RECEIVED</p>
            <h2 style="color:#22c55e; font-size:24px; font-weight:700; margin:8px 0 0 0;"><?= $received ?></h2>
        </div>
    </div>

    <div style="background:#011630; padding:25px; border-radius:8px; margin-bottom:30px; border:1px solid rgba(255,255,255,0.05);">
        <h4 style="color:#FFB62A; margin-bottom:20px; font-size:15px; font-weight:600;">
            <i class="fa-solid fa-cart-shopping me-2"></i>Buat Purchase Order Baru
        </h4>
        <form method="POST">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:15px;">
                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Referensi PR <span style="color:#64748b; font-weight:400;">(opsional)</span></label>
                    <select name="pr_id" style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                        <option value="">-- Tanpa PR / Langsung PO --</option>
                        <?php if ($pr_list): while ($pr = $pr_list->fetch_assoc()): ?>
                                <option value="<?= $pr['id'] ?>">
                                    <?= htmlspecialchars($pr['pr_no']) ?> — <?= htmlspecialchars(substr($pr['description'], 0, 30)) ?>... (Est. Rp <?= number_format($pr['estimated_amount'], 0, ',', '.') ?>)
                                </option>
                        <?php endwhile; endif; ?>
                    </select>
                </div>
                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Nama Vendor</label>
                    <input type="text" name="vendor_name" required placeholder="contoh: PT. Sumber Makmur..." style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                </div>
                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Tanggal Order</label>
                    <input type="date" name="order_date" value="<?= date('Y-m-d'); ?>" required style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                </div>
                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Total Nilai PO (Rp)</label>
                    <input type="number" name="total_amount" required min="0" step="0.01" placeholder="contoh: 5000000" style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:6px; color:#fff; font-size:13px;">
                </div>
            </div>

            <div style="margin-top:15px; padding:12px; background:rgba(255,183,42,0.08); border-radius:6px; border:1px solid rgba(255,183,42,0.2); text-align: left;">
                <p style="margin:0; color:#FFB62A; font-size:12px;">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> PO dengan nilai <strong>≥ Rp 5.000.000</strong> memerlukan persetujuan Purchasing Manager sebelum diproses ke vendor.
                </p>
            </div>

            <div style="margin-top:20px; text-align: left;">
                <button type="submit" name="tambah_po" style="background:#00cfd5; color:#021F42; font-weight:700; padding:10px 24px; border:none; border-radius:6px; font-size:13px; cursor:pointer;">
                    <i class="fa-solid fa-paper-plane me-1"></i> Buat Purchase Order
                </button>
            </div>
        </form>
    </div>

    <h4 style="color:#fff; margin-bottom:15px; font-size:15px; font-weight:600;">
        <i class="fa-solid fa-list me-2"></i>Daftar Purchase Order resmi
    </h4>

    <div style="background: #011630; padding: 0px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); overflow-x: auto;">
        <table class="table-custom mb-0" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #fff;">
            <thead>
                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #FFB62A;">
                    <th style="padding: 12px 15px;">No. PO</th>
                    <th style="padding: 12px 15px;">No. PR</th>
                    <th style="padding: 12px 15px;">Vendor</th>
                    <th style="padding: 12px 15px;">Tanggal Order</th>
                    <th style="padding: 12px 15px;">Total Nilai</th>
                    <th style="padding: 12px 15px;">Status</th>
                    <th style="padding: 12px 15px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data_po && $data_po->num_rows > 0): ?>
                    <?php while ($row = $data_po->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 14px 15px;"><strong style="color:#00cfd5;"><?= htmlspecialchars($row['po_number']) ?></strong></td>
                            <td style="padding: 14px 15px; color:#FFB62A;">
                                <?= $row['pr_no'] ? htmlspecialchars($row['pr_no']) : '—' ?>
                            </td>
                            <td style="padding: 14px 15px; color:#fff;"><?= htmlspecialchars($row['vendor_name'] ?? '') ?></td>
                            <td style="padding: 14px 15px; color:#cbd5e1;"><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                            <td style="padding: 14px 15px; color:#00cfd5; font-weight: 600;">Rp <?= number_format($row['total_amount'], 0, ',', '.') ?></td>
                            <td style="padding: 14px 15px;">
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
                            <td style="padding: 14px 15px; text-align: center;">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <a href="?hapus=<?= $row['id'] ?>" onclick="return confirm('Hapus PO ini?')" style="background:#ef4444; color:#fff; padding:6px 12px; border-radius:4px; font-size:11px; text-decoration:none; font-weight:600; display: inline-block;">
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
                            <i class="fa-solid fa-folder-open d-block fs-4 mb-2" style="color: #FFB62A;"></i> Belum ada dokumen Purchase Order.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// Melempar buffer isi konten ke komponen master template navbarM06.php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarM06.php'; 
?>
