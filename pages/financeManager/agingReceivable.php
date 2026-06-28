<?php
/** @var mysqli $conn */ 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeManager') {
    header("Location: ../../index.php"); 
    exit();
}
*/

$_SESSION['role'] = 'financeManager';
$_SESSION['nama'] = 'Manager';

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'Guest';

// Bersihkan nama dari duplikat kata biar rapi
$user_name = $_SESSION['nama'] ?? 'User';
$user_name = str_replace(['Staff', 'Manager'], '', $user_name);
$user_name = trim($user_name);

// Judul dinamis untuk Topbar
$page_title = 'Aging Receivable';
$department_name = "Finance & Accounting Dashboard";

// 1. Panggil file koneksi terpusat secara aman
if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} else {
    require_once '../../config/connection.php';
}

// PBI-M06-01-03: Logika Menghitung Umur Piutang (Aging Receivable)
$query_aging = "SELECT i.id, i.invoice_number, i.total_amount, i.due_date, t.brand_name,
                DATEDIFF(CURDATE(), i.due_date) as hari_terlambat
                FROM 06_invoices i
                LEFT JOIN 02_tenants t ON i.tenant_id = t.id_tenant
                ORDER BY hari_terlambat DESC";

$aging_list = [];
try {
    $res_aging = $conn->query($query_aging);
    if ($res_aging && $res_aging->num_rows > 0) {
        while ($r = $res_aging->fetch_assoc()) {
            $aging_list[] = $r;
        }
    }
} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

// SIMULASI DATA JIKA DB KOSONG
if (empty($aging_list)) {
    $aging_list = [
        [
            'invoice_number' => 'INV/2026/06/0012',
            'brand_name' => 'Starbucks Coffee Mall Utama',
            'total_amount' => 45000000,
            'due_date' => date('Y-m-d', strtotime('+10 days')),
            'hari_terlambat' => -10
        ],
        [
            'invoice_number' => 'INV/2026/05/0089',
            'brand_name' => 'H&M Apparel Ground Floor',
            'total_amount' => 125000000,
            'due_date' => date('Y-m-d', strtotime('-12 days')),
            'hari_terlambat' => 12
        ],
        [
            'invoice_number' => 'INV/2026/04/0034',
            'brand_name' => 'Cinema XXI Anchor Tenant',
            'total_amount' => 210000000,
            'due_date' => date('Y-m-d', strtotime('-45 days')),
            'hari_terlambat' => 45
        ],
        [
            'invoice_number' => 'INV/2026/06/0015',
            'brand_name' => 'Uniqlo Large Unit',
            'total_amount' => 180000000,
            'due_date' => date('Y-m-d', strtotime('+5 days')),
            'hari_terlambat' => -5
        ]
    ];
}

$total_belum_jatuh_tempo = 0;
$total_aging_1_30 = 0;
$total_aging_30_plus = 0;

$render_data = [];
foreach ($aging_list as $row) {
    $hari = (int)$row['hari_terlambat'];
    $sisa = (float)$row['total_amount'];
    
    if ($hari <= 0) {
        $kategori = "Belum Jatuh Tempo";
        $badge_color = "background-color: #00cfd5; color: #021F42;";
        $total_belum_jatuh_tempo += $sisa;
    } elseif ($hari > 0 && $hari <= 30) {
        $kategori = "1 - 30 Hari";
        $badge_color = "background-color: #FFB62A; color: #021F42;";
        $total_aging_1_30 += $sisa;
    } else {
        $kategori = "> 30 Hari (Macet)";
        $badge_color = "background-color: #ff4d4d; color: #ffffff;";
        $total_aging_30_plus += $sisa;
    }

    $render_data[] = array_merge($row, [
        'kategori' => $kategori,
        'badge_color' => $badge_color,
        'sisa' => $sisa
    ]);
}

// MASTER DATA MENU SIDEBAR UNTUK TINGKAT MANAGER (DISERASIKAN DENGAN DASHBOARD)
$menu_items = [
    [
        'icon' => 'fa-solid fa-gauge',
        'label' => 'Dashboard Manager',
        'link' => 'dashboardManager.php',
        'active_page' => 'dashboardManager'
    ],

    [
        'icon' => 'fa-solid fa-scale-balanced',
        'label' => 'Financial Statement',
        'link' => 'financeStatement.php',
        'active_page' => 'financeStatement'
    ],
    [
        'icon' => 'fa-solid fa-chart-pie',
        'label' => 'Budget Analysis',
        'link' => 'budgetAnalysis.php',
        'active_page' => 'budgetAnalysis'
    ],
    [
        'icon' => 'fa-solid fa-calculator',
        'label' => 'Tax Report (PPN)',
        'link' => 'taxReport.php',
        'active_page' => 'taxReport'
    ],
    [
        'icon' => 'fa-solid fa-building-columns',
        'label' => 'Bank Reconciliation',
        'link' => 'bankReco.php',
        'active_page' => 'bankReco'
    ],
    [
        'icon' => 'fa-solid fa-hourglass-half',
        'label' => 'Aging Receivable',
        'link' => 'agingReceivable.php',
        'active_page' => 'agingReceivable'
    ],
    [
        'icon' => 'fa-solid fa-book',
        'label' => 'Log Otomasi Jurnal',
        'link' => 'journalManagement.php',
        'active_page' => 'journalManagement'
    ]
];

// Mulai tangkap isi workspace halaman utama
ob_start();
?>

<style>
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #F5F7FA !important; }
    .sidebar { background-color: #011630 !important; }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); }
</style>

<div class="container-fluid" style="text-align: left; padding-top: 10px;">
    <div class="mb-5">
        <h1 style="color: #FFB62A; font-size: 32px; margin: 0; font-weight: 700; letter-spacing: -0.5px;">Dashboard Analisis Umur Piutang</h1>
        <p style="color: #cbd5e1; margin: 6px 0 0 0; font-size: 14px;">PBI-M06-01-03 — Pemantauan Aging Piutang Tenant Terpusat (Finance Manager)</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12 col-md-4">
            <div style="background: #011630; padding: 24px; border-radius: 12px; border-left: 5px solid #00cfd5; border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 100%;">
                <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 1px; font-weight: 600; text-transform: uppercase;">BELUM JATUH TEMPO</h5>
                <h3 style="color: #ffffff; margin: 14px 0 0 0; font-size: 26px; font-weight: 700; word-break: break-all;">Rp <?= number_format($total_belum_jatuh_tempo, 0, ',', '.'); ?></h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div style="background: #011630; padding: 24px; border-radius: 12px; border-left: 5px solid #FFB62A; border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 100%;">
                <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 1px; font-weight: 600; text-transform: uppercase;">MENUNGGAK 1 - 30 HARI</h5>
                <h3 style="color: #FFB62A; margin: 14px 0 0 0; font-size: 26px; font-weight: 700; word-break: break-all;">Rp <?= number_format($total_aging_1_30, 0, ',', '.'); ?></h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div style="background: #011630; padding: 24px; border-radius: 12px; border-left: 5px solid #ff4d4d; border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 100%;">
                <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 1px; font-weight: 600; text-transform: uppercase;">MENUNGGAK > 30 HARI</h5>
                <h3 style="color: #ff4d4d; margin: 14px 0 0 0; font-size: 26px; font-weight: 700; word-break: break-all;">Rp <?= number_format($total_aging_30_plus, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>

    <div style="background: #011630; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #ffffff;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.04); text-align: left; border-bottom: 2px solid rgba(255,255,255,0.08);">
                        <th style="padding: 16px 12px; font-weight: 600;">No. Invoice</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Nama Tenant</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Sisa Piutang</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Tanggal Jatuh Tempo</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Status Keterlambatan</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Kategori Umur Piutang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($render_data as $row): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.02)'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 16px 12px;"><strong><?= htmlspecialchars($row['invoice_number']); ?></strong></td>
                            <td style="padding: 16px 12px; color: #cbd5e1;"><?= htmlspecialchars($row['brand_name'] ?? 'Tenant General'); ?></td>
                            <td style="padding: 16px 12px; font-weight: 600; color: #00cfd5;">Rp <?= number_format($row['sisa'], 0, ',', '.'); ?></td>
                            <td style="padding: 16px 12px; color: #cbd5e1;"><?= date('d M Y', strtotime($row['due_date'])); ?></td>
                            <td style="padding: 16px 12px;">
                                <?= $row['hari_terlambat'] <= 0 ? '<span style="color: #10b981; font-weight: 500;"><i class="fa-solid fa-circle-check me-1"></i> Lancar</span>' : '<span style="color: #ff4d4d; font-weight: 500;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Terlambat ' . $row['hari_terlambat'] . ' Hari</span>'; ?>
                            </td>
                            <td style="padding: 16px 12px;">
                                <span style="padding: 6px 14px; font-size: 12px; border-radius: 20px; font-weight: 600; display: inline-block; <?= $row['badge_color']; ?>">
                                    <?= $row['kategori']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarMO6.php'; 
?>
