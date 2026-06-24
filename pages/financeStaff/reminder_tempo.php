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
$page_title = "Reminder Jatuh Tempo";
$user_name = "Muhammad Naufal";

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

$query = "SELECT i.*, t.brand_name 
          FROM `06_invoices` i
          JOIN `02_tenants` t ON i.tenant_id = t.id_tenant
          WHERE i.status = 'Belum Bayar'
          ORDER BY i.due_date ASC";

$result = $conn->query($query);

$reminders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reminders[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Daftar Tagihan Jatuh Tempo (Collection Reminder)</h2>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light">
            <h5><i class="fa-solid fa-triangle-exclamation text-warning"></i> Monitoring Invoice Tenant</h5>
            <p class="mb-0">Daftar di bawah ini memuat data tagihan berjalan tenant yang membutuhkan tindak lanjut penagihan.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead class="table-danger">
                    <tr>
                        <th>No. Invoice</th>
                        <th>Nama Brand</th>
                        <th>Periode Sewa</th>
                        <th>Total Tagihan</th>
                        <th>Batas Waktu (Due Date)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reminders) > 0): ?>
                        <?php foreach ($reminders as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['invoice_number']) ?></strong></td>
                                <td><?= htmlspecialchars($row['brand_name']) ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($row['period_start'])) ?> s/d <?= date('d/m/Y', strtotime($row['period_end'])) ?>
                                </td>
                                <td class="fw-bold text-danger">
                                    Rp <?= number_format($row['total_amount'], 0, ',', '.') ?>
                                </td>
                                <td>
                                    <span class="text-dark fw-bold"><?= date('d F Y', strtotime($row['due_date'])) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-danger"><?= htmlspecialchars($row['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-success fw-bold">Hebat! Semua tagihan tenant untuk bulan ini sudah lunas.</td>
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