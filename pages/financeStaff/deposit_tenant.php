<?php
//include '../../public/auth/checkSession.php';
?>


<?php
// ==========================================
// 1. KONEKSI DATABASE LANGSUNG (INSTAN & AMAN)
// ==========================================
$database_name = "mall_erp_(22-07)"; // Sesuai dengan nama DB Anda

$conn = new mysqli("localhost", "root", "", $database_name);

if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// 2. DEFINISIKAN VARIABEL UNTUK TEMPLATE
$department_name = "Tenant & Leasing Management";
$page_title = "Pengelolaan Deposit Tenant";
$user_name = "Muhammad Naufal";

$menu_items = [
    [
        'icon' => 'fa-solid fa-money-bill-wave',
        'label' => 'Komponen Biaya',
        'link' => '02_biaya_tampil.php',
        'active_page' => 'biaya'
    ],
    [
        'icon' => 'fa-solid fa-vault',
        'label' => 'Pengelolaan Deposit',
        'link' => '02_deposit_tampil.php',
        'active_page' => 'deposit'
    ],
    [
        'icon' => 'fa-solid fa-clock',
        'label' => 'Reminder Jatuh Tempo',
        'link' => '02_reminder_tampil.php',
        'active_page' => 'reminder'
    ]
];

// 3. KONTEN HALAMAN (Mulai Tangkap Konten)
ob_start();

$query = "SELECT td.*, c.contract_number, t.brand_name 
          FROM `02_tenant_deposits` td
          JOIN `02_contracts` c ON td.id_contract = c.id_contract
          JOIN `02_tenants` t ON c.id_tenant = t.id_tenant
          ORDER BY td.id_deposit DESC";

$result = $conn->query($query);

$deposits = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $deposits[] = $row;
    }
}
?>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>💵 Pencatatan Jaminan / Deposit Tenant</h2>
        <a href="02_deposit_tambah.php" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Deposit Baru</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted">Gunakan daftar ini untuk memvalidasi uang jaminan (Security/Utility Deposit) yang sudah dibayarkan atau yang memerlukan pengembalian (*Refund*).</p>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Brand</th>
                            <th>No. Kontrak</th>
                            <th>Tipe Deposit</th>
                            <th>Jumlah Deposit</th>
                            <th>Status Pembayaran</th>
                            <th>Tanggal Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($deposits) > 0): ?>
                            <?php foreach ($deposits as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['brand_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['contract_number']) ?></td>
                                    <td><?= htmlspecialchars($row['deposit_type']) ?></td>
                                    <td class="fw-bold text-success">
                                        Rp <?= number_format($row['amount'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <?php if ($row['payment_status'] == 'Paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php elseif ($row['payment_status'] == 'Unpaid'): ?>
                                            <span class="badge bg-warning text-dark">Unpaid</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($row['payment_status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $row['payment_date'] ? date('d F Y', strtotime($row['payment_date'])) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Data deposit unit tidak tersedia.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean(); // Menutup output buffering dan menyimpan ke variabel $content

// 4. PANGGIL TEMPLATE UTAMA
require_once '../../includes/navbar.php';
?>