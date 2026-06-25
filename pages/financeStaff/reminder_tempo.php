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
    
    .info-card-custom {
        background-color: rgba(11, 55, 109, 0.4);
        border: 1px solid rgba(0, 212, 216, 0.2);
        border-radius: 10px;
        color: var(--text);
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
    
    /* Badge Status */
    .badge-alert {
        padding: 0.45em 0.85em;
        font-weight: 600;
        font-size: var(--caption);
        border-radius: 6px;
    }
    
    .badge-overdue {
        background-color: rgba(239, 68, 68, 0.15);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    
    .badge-warning-custom {
        background-color: rgba(255, 182, 42, 0.15);
        color: var(--text-accent);
        border: 1px solid rgba(255, 182, 42, 0.3);
    }
    
    .invoice-code {
        background-color: var(--primary-dark);
        color: var(--accent);
        padding: 0.25rem 0.6rem;
        border-radius: 4px;
        font-size: var(--caption);
        font-family: monospace;
        font-weight: 600;
        border: 1px solid rgba(0, 212, 216, 0.15);
    }
    
    .date-text-danger {
        color: var(--danger);
        font-weight: 600;
    }

    .text-amount {
        color: var(--text-accent) !important;
    }

    .text-brand {
        color: var(--text) !important;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }
    .animate-pulse {
        animation: pulse 2s infinite;
    }
</style>

<div class="container-fluid mt-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <h2><i class="fa-solid fa-bell me-2" style="color: var(--danger);"></i>Daftar Tagihan Jatuh Tempo (Collection Reminder)</h2>
        </div>
    </div>

    <div class="card info-card-custom shadow-sm mb-4">
        <div class="card-body p-3 d-flex align-items-center">
            <i class="fa-solid fa-triangle-exclamation fa-2x me-3" style="color: var(--text-accent);"></i>
            <div>
                <h6 class="fw-bold mb-1" style="font-size: var(--body);">Monitoring Invoice Tenant Aktif</h6>
                <p class="mb-0 small" style="color: var(--text-secondary); font-size: var(--caption);">Daftar di bawah ini memuat seluruh data invoice berjalan yang belum terselesaikan dan memerlukan tindak lanjut komunikasi penagihan.</p>
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
                                    $date_style = "text-light fw-medium";
                                }
                            ?>
                                <tr>
                                    <td><span class="invoice-code"><?= htmlspecialchars($row['invoice_number']) ?></span></td>
                                    <td><strong class="text-brand"><?= htmlspecialchars($row['brand_name']) ?></strong></td>
                                    <td>
                                        <small style="color: var(--text-secondary); font-size: var(--caption);">
                                            <i class="fa-regular fa-calendar me-1" style="color: var(--text-secondary);"></i>
                                            <?= date('d/m/Y', strtotime($row['period_start'])) ?> s/d <?= date('d/m/Y', strtotime($row['period_end'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-end fw-bold text-amount">
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
                                <td colspan="6" class="text-center py-5 fw-bold" style="color: var(--success); font-size: var(--body);">
                                    <i class="fa-solid fa-circle-check d-block mb-2 fa-3x" style="color: var(--success);"></i>
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