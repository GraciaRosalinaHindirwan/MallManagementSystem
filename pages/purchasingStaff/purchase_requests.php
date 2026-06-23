<?php

/** @var mysqli $conn */ // Memberitahu VS Code agar tidak memunculkan garis merah pada variabel $conn

// 1. Inisialisasi Session dan Deteksi Otomatis File Koneksi Database
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mengambil file koneksi database bawaan project secara aman (Mundur 2 tingkat ke config)
if (file_exists(__DIR__ . '/../../config/koneksi.php')) {
    require_once __DIR__ . '/../../config/koneksi.php';
} else {
    require_once __DIR__ . '/../../config/connection.php';
}

// Mengatur judul departemen dinamis untuk dibaca top-navbar (Otomatis dibaca navbar.php)
$department_name = "Purchasing Staff - Purchase Requests";
$role = $_SESSION['role'] ?? '';

// 2. Sertakan File Header Atas (Mengandung CSS & Pembuka HTML)
include __DIR__ . '/../../includes/header.php';
if (!file_exists(__DIR__ . '/../../includes/header.php')) {
    include __DIR__ . '/../../header.php'; 
}

// 3. Sertakan File Navbar & Sidebar
include __DIR__ . '/../../includes/navbar.php';
if (!file_exists(__DIR__ . '/../../includes/navbar.php')) {
    include __DIR__ . '/../../navbar.php'; 
}

// -------------------------------------------------------------------------
// PROSES INSERT: Menyesuaikan tabel 06_purchase_requests & requested_by_id
// -------------------------------------------------------------------------
if (isset($_POST['tambah_pr'])) {
    $pr_no            = "PR-" . date('Ymd') . "-" . rand(100, 999);
    $description      = trim($_POST['description']);
    $estimated_amount = trim($_POST['estimated_amount']);
    
    // SESUAI DATABASE TIM: Menggunakan ID User angka 1 (Pancingan sementara)
    $requested_by_id  = 1; 
    $requested_at     = date('Y-m-d H:i:s');
    $status           = 'draft';

    $stmt = $conn->prepare(
        "INSERT INTO `06_purchase_requests` 
            (`pr_no`, `description`, `estimated_amount`, `requested_by_id`, `requested_at`, `status`) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssdiss", $pr_no, $description, $estimated_amount, $requested_by_id, $requested_at, $status);

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
// PROSES UPDATE/DELETE: Menggunakan tabel 06_purchase_requests
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

// -------------------------------------------------------------------------
// QUERY SELECT: Ambil dari 06_purchase_requests JOIN ke tabel user tim (09_users)
// -------------------------------------------------------------------------
$query = "SELECT pr.*, COALESCE(u.username, 'Eva (Staff)') AS nama_pengaju 
          FROM `06_purchase_requests` pr
          LEFT JOIN `09_users` u ON pr.requested_by_id = u.id 
          ORDER BY pr.id DESC";
$result = $conn->query($query);
?>

<div class="content-wrapper">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--accent); font-weight: 700;">
                    <i class="fa-solid fa-cart-shopping me-2"></i> PURCHASE REQUESTS
                </h4>
                <p class="small mb-0" style="color: #cbd5e1 !important; font-weight: 400; opacity: 0.9;">
                    Form pengajuan internal belanja barang logistik operasional mall.
                </p>
            </div>
            
            <?php if ($role === 'Purchasing Staff' || $role === 'Purchasing Manager' || $role === 'Finance Manager' || $role === 'Finance Staff'): ?>
                <button class="btn btn-primary fw-bold px-3 py-2" style="background-color: #00cfd5; border: none; color: #011630;" data-bs-toggle="modal" data-bs-target="#modalTambahPR">
                    <i class="fa-solid fa-circle-plus me-1"></i> Buat Pengajuan PR
                </button>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success bg-success text-white border-0 p-2 mb-3 small"><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger bg-danger text-white border-0 p-2 mb-3 small"><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
        <?php endif; ?>

        <div class="card bg-dark text-white border-0 shadow-sm mb-4" style="background-color: #011630 !important; border: 1px solid rgba(255,255,255,0.05) !important;">
            <div class="card-body p-0">
                <table class="table-custom mb-0" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="15%" class="p-2" style="padding: 8px 15px !important;">No. PR</th>
                            <th width="30%" class="p-2" style="padding: 8px 15px !important;">Deskripsi Transaksi</th>
                            <th width="15%" class="text-end p-2" style="padding: 8px 15px !important;">Estimasi Biaya</th>
                            <th width="15%" class="p-2" style="padding: 8px 15px !important;">Diajukan Oleh</th>
                            <th width="10%" class="p-2" style="padding: 8px 15px !important;">Status</th>
                            <th width="15%" class="text-center p-2" style="padding: 8px 15px !important;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $status = $row['status'];
                                
                                $status_color = '#cbd5e1';
                                if ($status === 'pending') $status_color = 'var(--accent)';
                                if ($status === 'approved') $status_color = '#00cfd5';
                                if ($status === 'rejected') $status_color = '#ef4444';

                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                echo "<td style='padding: 12px 15px;'><span class='badge bg-secondary px-2 py-1'>{$row['pr_no']}</span></td>";
                                echo "<td style='padding: 12px 15px;'>" . htmlspecialchars($row['description']) . " <br><small class='text-muted'>" . date('d-m-Y H:i', strtotime($row['requested_at'])) . "</small></td>";
                                echo "<td class='text-end' style='padding: 12px 15px; color: #00cfd5; font-weight: 600;'>Rp " . number_format($row['estimated_amount'], 0, ',', '.') . "</td>";
                                echo "<td style='padding: 12px 15px;'>" . htmlspecialchars($row['nama_pengaju']) . "</td>";
                                echo "<td style='padding: 12px 15px; font-weight: 700; color: {$status_color}; text-transform: uppercase;'>{$status}</td>";
                                echo "<td class='text-center' style='padding: 12px 15px;'>";
                                
                                if ($status === 'draft') {
                                    echo "<a href='?action=submit&id={$row['id']}' class='btn btn-sm btn-success px-2 py-1 me-1 small text-white' style='font-size:0.8rem;' onclick='return confirm(\"Ajukan PR ini?\")'>Ajukan</a>";
                                    echo "<a href='?action=delete&id={$row['id']}' class='btn btn-sm btn-danger px-2 py-1 small text-white' style='font-size:0.8rem;' onclick='return confirm(\"Hapus draft ini?\")'>Hapus</a>";
                                } else {
                                    echo "<span class='text-muted small'>-</span>";
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center' style='padding: 30px; color: #cbd5e1 !important;'><i class='fa-solid fa-folder-open me-2' style='color: var(--accent);'></i>Belum ada riwayat pengajuan Purchase Request.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<div class="modal fade text-white" id="modalTambahPR" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: #011630; border: 1px solid rgba(255,255,255,0.1);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <h5 class="modal-title font-weight-bold" style="color: var(--accent);">Formulir Pengajuan Belanja (PR)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold" style="color: #cbd5e1;">Deskripsi Pengadaan / Kebutuhan</label>
                        <input type="text" name="description" class="form-control text-white border-0" style="background-color: #061f41;" placeholder="Contoh: Pembelian AC Kantor Lantai 2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold" style="color: #cbd5e1;">Estimasi Total Biaya (Rupiah)</label>
                        <input type="number" name="estimated_amount" class="form-control text-white border-0" style="background-color: #061f41;" placeholder="Contoh: 7500000" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background-color: #4b5563; border: none;">Batal</button>
                    <button type="submit" name="tambah_pr" class="btn text-dark fw-bold" style="background-color: #00cfd5; border: none;">Simpan Draft</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// 4. Sertakan File Footer
include __DIR__ . '/../../includes/footer.php';
if (!file_exists(__DIR__ . '/../../includes/footer.php')) {
    include __DIR__ . '/../../footer.php'; 
}
?>