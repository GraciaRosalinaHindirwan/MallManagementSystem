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

$_SESSION['role'] = 'purchasingStaff';
$_SESSION['nama'] = 'Eva';

if (file_exists(__DIR__ . '/../../config/konek.php')) {
    require_once __DIR__ . '/../../config/konek.php';
} else {
    require_once __DIR__ . '/../../config/connection.php';
}

// -------------------------------------------------------------------------
// PROSES INSERT
// -------------------------------------------------------------------------
if (isset($_POST['tambah_pr'])) {
    $pr_number        = "PR-" . date('Ymd') . "-" . rand(100, 999);
    $description      = trim($_POST['description']);
    $estimated_amount = trim($_POST['estimated_amount']);

    $requested_by     = $_SESSION['nama'];
    $requested_at     = date('Y-m-d H:i:s');
    $status           = 'draft';

    $stmt = $conn->prepare(
        "INSERT INTO `06_purchase_requests` 
            (`pr_number`, `description`, `estimated_amount`, `requested_by`, `requested_at`, `status`) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssdsss", $pr_number, $description, $estimated_amount, $requested_by, $requested_at, $status);

    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Draft Purchase Request berhasil dibuat!";
    } else {
        $_SESSION['error_msg'] = "Gagal membuat PR: " . $stmt->error;
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// -------------------------------------------------------------------------
// PROSES UPDATE/DELETE
// -------------------------------------------------------------------------
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'submit') {
        $stmt = $conn->prepare("UPDATE `06_purchase_requests` SET `status` = 'pending' WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Purchase Request berhasil diajukan!";
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM `06_purchase_requests` WHERE `id` = ? AND `status` = 'draft'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Draft Purchase Request berhasil dihapus!";
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$query = "SELECT * FROM `06_purchase_requests` ORDER BY id DESC";
$result = $conn->query($query);

$department_name = "Purchasing Staff - Purchase Requests";
$user_name = $_SESSION['nama'];
$role = $_SESSION['role'] ?? '';
$page_title = "purchase_requests";


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
        'active_page' => 'purchase_requests'
    ],
    [
        'icon'        => 'fa-solid fa-file-invoice-dollar',
        'label'       => 'Purchase Orders',
        'link'        => 'purchase_orders.php',
        'active_page' => 'purchase_orders'
    ]
];

ob_start();

?>

<div class="container-fluid" style="padding: 20px 0px; text-align: left;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1" style="color: #FFB62A; font-weight: 700; margin: 0; font-family: 'Poppins', sans-serif;">
                <i class="fa-solid fa-cart-shopping me-2"></i> PURCHASE REQUESTS
            </h4>
            <p class="small mb-0" style="color: #cbd5e1 !important; font-weight: 400; opacity: 0.9; margin-top: 5px;">
                Form pengajuan internal belanja barang logistik operasional mall.
            </p>
        </div>

        <button class="btn fw-bold px-3 py-2" style="background-color: #00cfd5; border: none; color: #011630; font-size: 13px; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#modalTambahPR">
            <i class="fa-solid fa-circle-plus me-1"></i> Buat Pengajuan PR
        </button>
    </div>

    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success bg-success text-white border-0 p-2 mb-3 small" style="border-radius: 6px;"><?= $_SESSION['success_msg'];
                                                                                                                    unset($_SESSION['success_msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger bg-danger text-white border-0 p-2 mb-3 small" style="border-radius: 6px;"><?= $_SESSION['error_msg'];
                                                                                                                    unset($_SESSION['error_msg']); ?></div>
    <?php endif; ?>

    <div class="card text-white border-0 shadow-sm mb-4" style="background-color: #011630 !important; border: 1px solid rgba(255,255,255,0.05) !important; border-radius: 8px;">
        <div class="card-body p-0" style="overflow-x: auto;">
            <table class="table mb-0" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #fff;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); background-color: rgba(255,255,255,0.02);">
                        <th width="15%" style="padding: 15px !important; color: #FFB62A; font-weight: 600;">No. PR</th>
                        <th width="35%" style="padding: 15px !important; color: #FFB62A; font-weight: 600;">Deskripsi Transaksi</th>
                        <th width="15%" class="text-end" style="padding: 15px !important; color: #FFB62A; font-weight: 600;">Estimasi Biaya</th>
                        <th width="15%" style="padding: 15px !important; color: #FFB62A; font-weight: 600;">Diajukan Oleh</th>
                        <th width="10%" style="padding: 15px !important; color: #FFB62A; font-weight: 600;">Status</th>
                        <th width="10%" class="text-center" style="padding: 15px !important; color: #FFB62A; font-weight: 600;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $status = $row['status'];

                            $status_color = '#cbd5e1';
                            if ($status === 'pending') $status_color = '#FFB62A';
                            if ($status === 'approved') $status_color = '#22c55e';
                            if ($status === 'rejected') $status_color = '#ef4444';

                            echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                            echo "<td style='padding: 15px;'><span class='badge px-2 py-1' style='border-radius:4px; font-weight:600; background-color: #1e293b !important; color: #fff;'>{$row['pr_number']}</span></td>";
                            echo "<td style='padding: 15px; color: #fff;'>" . htmlspecialchars($row['description']) . " <br><small style='color: #64748b; font-size: 11px;'>" . date('d-m-Y H:i', strtotime($row['requested_at'])) . "</small></td>";
                            echo "<td class='text-end' style='padding: 15px; color: #00cfd5; font-weight: 600;'>Rp " . number_format($row['estimated_amount'], 0, ',', '.') . "</td>";
                            echo "<td style='padding: 15px; color: #cbd5e1;'>" . htmlspecialchars($row['requested_by'] ?? 'Eva') . "</td>";
                            echo "<td style='padding: 15px; font-weight: 700; color: {$status_color}; text-transform: uppercase;'>{$status}</td>";
                            echo "<td class='text-center' style='padding: 15px;'>";

                            if ($status === 'draft') {
                                echo "<div style='display:flex; justify-content:center; gap:10px;'>";
                                echo "<a href='?action=submit&id={$row['id']}' style='font-size:11px; padding: 5px 16px; border-radius: 4px; background-color:#22c55e; border:none; color:#fff; text-decoration:none; white-space:nowrap; font-weight:700; display:inline-block;' onclick='return confirm(\"Ajukan PR ini?\")'>Ajukan</a>";
                                echo "<a href='?action=delete&id={$row['id']}' style='font-size:11px; padding: 5px 16px; border-radius: 4px; background-color:#ef4444; border:none; color:#fff; text-decoration:none; white-space:nowrap; font-weight:700; display:inline-block;' onclick='return confirm(\"Hapus draft ini?\")'>Hapus</a>";
                                echo "</div>";
                            } else {
                                echo "<span style='color: #64748b; font-size: 12px;'>-</span>";
                            }

                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center' style='padding: 40px; color: #64748b !important;'><i class='fa-solid fa-folder-open d-block mb-2' style='color: #FFB62A; font-size: 24px;'></i>Belum ada riwayat pengajuan Purchase Request.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== MODAL TAMBAH PR ===================== -->
<div class="modal fade" id="modalTambahPR" tabindex="-1" aria-labelledby="modalTambahPRLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background-color: #011630; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding: 15px 20px;">
                <h5 class="modal-title fw-bold" id="modalTambahPRLabel" style="color: #FFB62A; font-size: 15px;">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>Formulir Pengajuan Belanja (PR)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body text-white" style="font-size: 13px; padding: 24px;">

                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <!-- Kolom Kiri: Deskripsi Pengadaan -->
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 600; color: #cbd5e1; margin-bottom: 6px; font-size: 13px;">Deskripsi Pengadaan</label>
                            <input type="text" name="description"
                                class="form-control text-white border-0"
                                style="background-color: #021F42; padding: 11px 12px; font-size: 13px; border-radius: 6px; width: 100%; box-sizing: border-box;"
                                placeholder="Contoh: Pembelian AC Kantor"
                                required>
                        </div>

                        <!-- Kolom Kanan: Estimasi Total Biaya -->
                        <div style="flex: 1;">
                            <label style="display: block; font-weight: 600; color: #cbd5e1; margin-bottom: 6px; font-size: 13px;">Estimasi Total Biaya</label>
                            <input type="number" name="estimated_amount"
                                class="form-control text-white border-0"
                                style="background-color: #021F42; padding: 11px 12px; font-size: 13px; border-radius: 6px; width: 100%; box-sizing: border-box;"
                                placeholder="Contoh: 7500000"
                                required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05); padding: 12px 20px; display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        style="background-color: #4b5563; border: none; font-size: 12px; font-weight: 600; border-radius: 6px; padding: 8px 16px; color: #fff;">
                        Batal
                    </button>
                    <button type="submit" name="tambah_pr" class="btn fw-bold"
                        style="background-color: #00cfd5; border: none; font-size: 12px; border-radius: 6px; padding: 8px 16px; color: #011630;">
                        Simpan Draft
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarMO6.php';
?>
