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
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

    :root {
        /* colors */
        --primary: #0B376D;
        --primary-dark: #082A53;
        --secondary: #167E80;
        --secondary-dark: #0D4859;
        --accent: #00D4D8;
        --success: #22C55E;
        --danger: #EF4444;

        /* background */
        --background: #021F42;

        /* text colors */
        --text: #F5F7FA;
        --text-secondary: #B8C7D9;
        --text-accent: #FFB62A;

        /* Typography */
        --font-family: 'Poppins', sans-serif;
        --h1: 32px;
        --h2: 24px;
        --subheading: 20px;
        --body: 16px;
        --label: 14px;
        --caption: 12px;
    }

    body {
        background-color: var(--background);
        color: var(--text);
        font-family: var(--font-family);
        font-size: var(--body);
    }
    
    .page-header h2 {
        font-size: var(--h2);
        font-weight: 700;
        color: var(--text);
    }

    .page-header p {
        font-size: var(--label);
        color: var(--text-secondary) !important;
    }
    
    .card-table-custom {
        background-color: var(--primary);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2) !important;
        overflow: hidden;
    }
    
    .table-custom {
        margin-bottom: 0;
    }
    
    .table-custom thead th {
        background-color: var(--primary-dark) !important;
        color: var(--accent) !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: var(--caption);
        letter-spacing: 0.5px;
        padding: 1.1rem 1.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .table-custom tbody td {
        padding: 1.1rem 1.25rem;
        font-size: var(--label);
        color: var(--text-secondary);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        background-color: transparent !important;
    }
    
    .table-custom tbody tr {
        background-color: transparent !important;
    }

    .table-custom tbody tr:hover td {
        background-color: rgba(255, 255, 255, 0.03) !important;
        color: var(--text);
        transition: all 0.2s ease-in-out;
    }
    
    /* Modern Badge Status */
    .badge-status {
        padding: 0.45em 0.85em;
        font-weight: 600;
        font-size: var(--caption);
        border-radius: 6px;
    }
    .badge-paid {
        background-color: rgba(34, 197, 94, 0.15);
        color: var(--success);
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    .badge-unpaid {
        background-color: rgba(239, 68, 68, 0.15);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    
    /* Tipe Deposit Styling */
    .deposit-type-text {
        font-size: var(--label);
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    .contract-badge {
        background-color: var(--primary-dark);
        color: var(--accent);
        padding: 0.25rem 0.6rem;
        border-radius: 4px;
        font-size: var(--caption);
        font-family: monospace;
        border: 1px solid rgba(0, 212, 216, 0.2);
    }
    
    .btn-add-custom {
        background-color: var(--secondary);
        border-color: var(--secondary);
        color: var(--text);
        font-weight: 600;
        padding: 0.55rem 1.25rem;
        border-radius: 8px;
        font-size: var(--label);
        box-shadow: 0 4px 12px rgba(22, 126, 128, 0.2);
        transition: all 0.2s ease-in-out;
    }
    .btn-add-custom:hover {
        background-color: var(--secondary-dark);
        border-color: var(--secondary-dark);
        color: var(--text);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(13, 72, 89, 0.4);
    }
    
    .text-amount {
        color: var(--text-accent) !important;
    }
    .text-brand {
        color: var(--text) !important;
    }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h2><i class="fa-solid fa-vault me-2" style="color: var(--accent);"></i>Pencatatan Jaminan / Deposit Tenant</h2>
            <p class="text-muted mb-0 mt-1 d-none d-sm-block">Gunakan daftar ini untuk memvalidasi uang jaminan (Security/Utility Deposit) yang sudah dibayarkan atau memerlukan pengembalian (*Refund*).</p>
        </div>
        <a href="02_deposit_tambah.php" class="btn btn-add-custom"><i class="fa fa-plus me-1"></i> Tambah Deposit Baru</a>
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
                                    <td><strong class="text-brand"><?= htmlspecialchars($row['brand_name']) ?></strong></td>
                                    <td><span class="contract-badge"><?= htmlspecialchars($row['contract_number']) ?></span></td>
                                    <td>
                                        <span class="deposit-type-text">
                                            <i class="fa-solid fa-shield-halved me-1" style="font-size: 0.8rem; color: var(--text-secondary);"></i> 
                                            <?= htmlspecialchars($row['deposit_type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-amount">
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
                                            <small class="fw-medium" style="color: var(--text);"><i class="fa-regular fa-clock me-1" style="color: var(--text-secondary);"></i> <?= date('d F Y', strtotime($row['payment_date'])) ?></small>
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary);">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5" style="color: var(--text-secondary);">
                                    <i class="fa-solid fa-inbox d-block mb-2 fa-2x" style="opacity: 0.5; color: var(--text-secondary);"></i>
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