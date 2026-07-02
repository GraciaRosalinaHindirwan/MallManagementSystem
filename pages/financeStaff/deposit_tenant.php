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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }
    .page-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
    }
    .card-table-custom {
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(149, 157, 165, 0.08) !important;
        overflow: hidden;
    }
    .table-custom {
        margin-bottom: 0;
    }
    .table-custom thead th {
        background-color: #198754 !important; /* Hijau Finansial Premium */
        color: #ffffff !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.82rem;
        letter-spacing: 0.5px;
        padding: 1.1rem 1.25rem;
        border-bottom: none;
    }
    .table-custom tbody td {
        padding: 1.1rem 1.25rem;
        font-size: 0.92rem;
        color: #495057;
        border-bottom: 1px solid #eaecf4;
    }
    .table-custom tbody tr:hover {
        background-color: #f3f7f4 !important; /* Efek hover hint hijau tipis */
        transition: background-color 0.2s ease-in-out;
    }
    
    /* Modern Badge Status */
    .badge-status {
        padding: 0.45em 0.85em;
        font-weight: 600;
        font-size: 0.8rem;
        border-radius: 6px;
    }
    .badge-paid {
        background-color: #e6f4ea;
        color: #137333;
    }
    .badge-unpaid {
        background-color: #fce8e6;
        color: #c5221f;
    }
    
    /* Tipe Deposit Styling */
    .deposit-type-text {
        font-size: 0.88rem;
        color: #5f6368;
        font-weight: 500;
    }
    .contract-badge {
        background-color: #f1f3f4;
        color: #3c4043;
        padding: 0.25rem 0.6rem;
        border-radius: 4px;
        font-size: 0.85rem;
        font-family: monospace;
    }
    
    .btn-add-custom {
        background-color: #198754;
        border-color: #198754;
        font-weight: 600;
        padding: 0.55rem 1.25rem;
        border-radius: 8px;
        font-size: 0.9rem;
        box-shadow: 0 4px 12px rgba(25, 135, 84, 0.2);
        transition: all 0.2s ease-in-out;
    }
    .btn-add-custom:hover {
        background-color: #157347;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(25, 135, 84, 0.3);
    }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h2><i class="fa-solid fa-vault me-2 text-success"></i>Pencatatan Jaminan / Deposit Tenant</h2>
            <p class="text-muted mb-0 mt-1 d-none d-sm-block">Gunakan daftar ini untuk memvalidasi uang jaminan (Security/Utility Deposit) yang sudah dibayarkan atau memerlukan pengembalian (*Refund*).</p>
        </div>
        <a href="02_deposit_tambah.php" class="btn btn-success btn-add-custom"><i class="fa fa-plus me-1"></i> Tambah Deposit Baru</a>
    </div>

    <div class="card card-table-custom shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nama Brand</th>
                            <th>No. Kontrak</th>
                            <th>Tipe Deposit</th>
                            <th class="text-end">Jumlah Deposit</th>
                            <th class="text-center">Status Pembayaran</th>
                            <th>Tanggal Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($deposits) > 0): ?>
                            <?php foreach ($deposits as $row): ?>
                                <tr>
                                    <td><strong class="text-dark"><?= htmlspecialchars($row['brand_name']) ?></strong></td>
                                    <td><span class="contract-badge"><?= htmlspecialchars($row['contract_number']) ?></span></td>
                                    <td>
                                        <span class="deposit-type-text">
                                            <i class="fa-solid fa-shield-halved me-1 text-muted" style="font-size: 0.8rem;"></i> 
                                            <?= htmlspecialchars($row['deposit_type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        Rp <?= number_format($row['amount'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['payment_status'] == 'Paid'): ?>
                                            <span class="badge badge-status badge-paid"><i class="fa-solid fa-circle-check me-1"></i>Paid</span>
                                        <?php elseif ($row['payment_status'] == 'Unpaid'): ?>
                                            <span class="badge badge-status badge-unpaid"><i class="fa-solid fa-circle-xmark me-1"></i>Unpaid</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary text-white badge-status"><?= htmlspecialchars($row['payment_status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['payment_date']): ?>
                                            <small class="text-dark fw-medium"><i class="fa-regular fa-clock text-muted me-1"></i> <?= date('d F Y', strtotime($row['payment_date'])) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted-light">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox d-block mb-2 fa-2x text-muted" style="opacity: 0.5;"></i>
                                    Data deposit unit tidak tersedia.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>