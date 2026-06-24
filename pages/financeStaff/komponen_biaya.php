<?php
//include '../../public/auth/checkSession.php';
?>


<?php
// ==========================================
// 1. KONEKSI DATABASE LANGSUNG (INSTAN & AMAN)
// ==========================================
$database_name = "mall_erp_(22-07)"; // Sesuaikan dengan nama DB di phpMyAdmin kamu

$conn = new mysqli("localhost", "root", "", $database_name);

if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}
// 2. DEFINISIKAN VARIABEL UNTUK TEMPLATE
$department_name = "Tenant & Leasing Management";
$page_title = "Manajemen Komponen Biaya";
$user_name = "Muhammad Naufal"; 

// Menu Sidebar Modul 2
$menu_items = [
    [
        'icon' => 'fa-solid fa-sliders',
        'label' => 'Komponen Biaya',
        'link' => 'komponen_biaya.php',
        'active_page' => 'komponen_biaya'
    ],
    [
        'icon' => 'fa-solid fa-vault',
        'label' => 'Deposit Tenant',
        'link' => 'deposit_tenant.php',
        'active_page' => 'deposit_tenant'
    ],
    [
        'icon' => 'fa-solid fa-clock',
        'label' => 'Reminder Jatuh Tempo',
        'link' => 'reminder_tempo.php',
        'active_page' => 'reminder'
    ]
];

// 3. KONTEN HALAMAN
ob_start();

// Query mengambil data komponen biaya menggunakan MySQLi
$query = "SELECT cc.*, c.contract_number, t.brand_name 
          FROM `02_contract_cost` cc
          JOIN `02_contracts` c ON cc.id_contract = c.id_contract
          JOIN `02_tenants` t ON c.id_tenant = t.id_tenant
          ORDER BY c.contract_number ASC";

$result = $conn->query($query);

// Tampung hasil data ke dalam array $costs
$costs = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $costs[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Pengaturan Komponen Biaya Sewa</h2>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted">Daftar parameter komponen biaya resmi (Fixed Rent, Service Charge, dll) berdasarkan ikatan kontrak tenant.</p>
            
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>No. Kontrak</th>
                        <th>Nama Brand</th>
                        <th>Jenis Biaya</th>
                        <th>Basis Perhitungan</th>
                        <th>Nominal / Persentase</th>
                        <th>Siklus Tagihan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($costs) > 0): ?>
                        <?php foreach ($costs as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['contract_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['brand_name']) ?></td>
                                <td>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($row['charge_type']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['calculation_basis']) ?></td>
                                <td>
                                    <?php if ($row['calculation_basis'] == 'Percentage'): ?>
                                        <?= number_format($row['amount_or_percentage'], 2) ?>%
                                    <?php else: ?>
                                        Rp <?= number_format($row['amount_or_percentage'], 0, ',', '.') ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['billing_cycle']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Data komponen biaya tidak ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// 4. PANGGIL TEMPLATE UTAMA
require_once '../../includes/navbar.php';
?>