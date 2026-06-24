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
    .info-card-custom {
        background-color: #fff3cd;
        border: 1px solid #ffe69c;
        border-radius: 10px;
        color: #664d03;
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
        background-color: #dc3545 !important; /* Crimson Red Urgensi */
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
        background-color: #fff5f5 !important; /* Tint merah tipis saat row di-hover */
        transition: background-color 0.2s ease-in-out;
    }
    
    /* Badge Status */
    .badge-alert {
        padding: 0.45em 0.85em;
        font-weight: 600;
        font-size: 0.8rem;
        border-radius: 6px;
    }
    .badge-overdue {
        background-color: #fce8e6;
        color: #c5221f;
    }
    .badge-warning-custom {
        background-color: #fff3cd;
        color: #664d03;
    }
    
    .invoice-code {
        background-color: #f1f3f4;
        color: #202124;
        padding: 0.25rem 0.6rem;
        border-radius: 4px;
        font-size: 0.85rem;
        font-family: monospace;
        font-weight: 600;
    }
    .date-text-danger {
        color: #d93025;
        font-weight: 600;
    }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h2><i class="fa-solid fa-bell text-danger me-2"></i>Daftar Tagihan Jatuh Tempo (Collection Reminder)</h2>
        </div>
    </div>

    <div class="card info-card-custom shadow-sm mb-4">
        <div class="card-body p-3 d-flex align-items-center">
            <i class="fa-solid fa-triangle-exclamation fa-2x me-3 text-warning"></i>
            <div>
                <h6 class="fw-bold mb-1">Monitoring Invoice Tenant Aktif</h6>
                <p class="mb-0 small text-secondary-custom">Daftar di bawah ini memuat seluruh data invoice berjalan yang belum terselesaikan dan memerlukan tindak lanjut komunikasi penagihan.</p>
            </div>
        </div>
    </div>

    <div class="card card-table-custom shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Nama Brand</th>
                            <th>Periode Sewa</th>
                            <th class="text-end">Total Tagihan</th>
                            <th>Batas Waktu (Due Date)</th>
                            <th class="text-center">Status / Sisa Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($reminders) > 0): ?>
                            <?php foreach ($reminders as $row): 
                                // LOGIKA OTOMATIS HITUNG SISA HARI / OVERDUE
                                $today = strtotime(date('Y-m-d'));
                                $due_date = strtotime($row['due_date']);
                                $diff = $due_date - $today;
                                $days_left = round($diff / (60 * 60 * 24));
                                
                                if ($days_left < 0) {
                                    $time_status = "Overdue " . abs($days_left) . " Hari";
                                    $badge_style = "badge-overdue";
                                    $date_style = "date-text-danger";
                                } elseif ($days_left == 0) {
                                    $time_status = "Jatuh Tempo Hari Ini";
                                    $badge_style = "badge-overdue fw-bold animate-pulse";
                                    $date_style = "date-text-danger";
                                } else {
                                    $time_status = $days_left . " Hari Lagi";
                                    $badge_style = "badge-warning-custom";
                                    $date_style = "text-dark fw-medium";
                                }
                            ?>
                                <tr>
                                    <td><span class="invoice-code"><?= htmlspecialchars($row['invoice_number']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($row['brand_name']) ?></strong></td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fa-regular fa-calendar text-muted me-1"></i>
                                            <?= date('d/m/Y', strtotime($row['period_start'])) ?> s/d <?= date('d/m/Y', strtotime($row['period_end'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        Rp <?= number_format($row['total_amount'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <span class="<?= $date_style; ?>">
                                            <i class="fa-regular fa-clock me-1"></i><?= date('d F Y', strtotime($row['due_date'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-alert <?= $badge_style; ?>">
                                            <?= htmlspecialchars($row['status']) ?> (<?= $time_status ?>)
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-success fw-bold">
                                    <i class="fa-solid fa-circle-check d-block mb-2 fa-3x text-success"></i>
                                    Hebat! Semua tagihan tenant untuk bulan ini sudah lunas sepenuhnya.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>