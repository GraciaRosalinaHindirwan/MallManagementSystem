<?php
session_start();

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeManager') {
    header("Location: ../../index.php"); 
    exit();
}
*/

// Default session sementara
$_SESSION['role'] = 'financeManager'; 
$_SESSION['nama'] = 'Manager';
// 1. Panggil file koneksi terpusat secara aman
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

// 2. Panggil navbar dan header khusus manager
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// PBI-M06-01-03: Logika Menghitung Umur Piutang (Aging Receivable) - PENYESUAIAN STRUKTUR DB ASLI
// Query mengambil data invoice dan join menggunakan i.tenant_id = t.id_tenant sesuai isi file .sql kamu
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

// =========================================================================
// OOMP JIKA DATA PENAGIHAN BELUM ADA DI DB, INI SIMULASI SINKRONISASI COA NYA
// =========================================================================
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

// Siapkan variabel untuk akumulasi nilai total ringkasan widget manager
$total_belum_jatuh_tempo = 0;
$total_aging_1_30 = 0;
$total_aging_30_plus = 0;

// Lakukan kalkulasi data penunggakan sebelum render HTML
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
        $badge_color = "background-color: var(--accent); color: #021F42;";
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
?>

<div class="content-container" style="padding: 20px; background: var(--bg-primary); min-height: 80vh; color: #ffffff;">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: var(--h1); margin: 0; font-weight: 700;">Dashboard Analisis Umur Piutang</h1>
        <p style="color: #cbd5e1; margin: 5px 0 0 0; font-size: 14px;">PBI-M06-01-03 — Pemantauan Aging Piutang Tenant Terpusat (Finance Manager)</p>
    </div>

    <div class="row mb-4" style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 250px; background: rgba(255,255,255,0.03); padding: 20px; border-radius: 8px; border-left: 5px solid #00cfd5; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 0.8px; font-weight: 600;">BELUM JATUH TEMPO</h5>
            <h3 id="widget-lancar" style="color: #ffffff; margin: 10px 0 0 0; font-size: 28px; font-weight: 700;">Rp <?= number_format($total_belum_jatuh_tempo, 0, ',', '.'); ?></h3>
        </div>
        <div style="flex: 1; min-width: 250px; background: rgba(255,255,255,0.03); padding: 20px; border-radius: 8px; border-left: 5px solid var(--accent); box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 0.8px; font-weight: 600;">MENUNGGAK 1 - 30 HARI</h5>
            <h3 id="widget-menengah" style="color: var(--accent); margin: 10px 0 0 0; font-size: 28px; font-weight: 700;">Rp <?= number_format($total_aging_1_30, 0, ',', '.'); ?></h3>
        </div>
        <div style="flex: 1; min-width: 250px; background: rgba(255,255,255,0.03); padding: 20px; border-radius: 8px; border-left: 5px solid #ff4d4d; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 0.8px; font-weight: 600;">MENUNGGAK > 30 HARI</h5>
            <h3 id="widget-macet" style="color: #ff4d4d; margin: 10px 0 0 0; font-size: 28px; font-weight: 700;">Rp <?= number_format($total_aging_30_plus, 0, ',', '.'); ?></h3>
        </div>
    </div>

    <div style="background: #011630; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 20px; margin-top: 25px;">
        <table class="table-custom" style="width: 100%; border-collapse: collapse; font-size: 13px; color: #ffffff; background-color: #011630 !important;">
            <thead>
                <tr style="background: rgba(255,255,255,0.05); text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <th style="padding: 12px; color: #fff;">No. Invoice</th>
                    <th style="padding: 12px; color: #fff;">Nama Tenant</th>
                    <th style="padding: 12px; color: #fff;">Sisa Piutang</th>
                    <th style="padding: 12px; color: #fff;">Tanggal Jatuh Tempo</th>
                    <th style="padding: 12px; color: #fff;">Status Keterlambatan</th>
                    <th style="padding: 12px; color: #fff;">Kategori Umur Piutang</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($render_data as $row): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); background-color: #011630;">
                        <td style="padding: 12px; color: #ffffff;"><strong><?= htmlspecialchars($row['invoice_number']); ?></strong></td>
                        <td style="padding: 12px; color: #cbd5e1;"><?= htmlspecialchars($row['brand_name'] ?? 'Tenant General'); ?></td>
                        <td style="padding: 12px; font-weight: 600; color: #00cfd5;">Rp <?= number_format($row['sisa'], 0, ',', '.'); ?></td>
                        <td style="padding: 12px; color: #cbd5e1;"><?= date('d M Y', strtotime($row['due_date'])); ?></td>
                        <td style="padding: 12px;">
                            <?= $row['hari_terlambat'] <= 0 ? '<span style="color: #10b981; font-weight: 500;">Lancar</span>' : '<span style="color: #ff4d4d; font-weight: 500;">Terlambat ' . $row['hari_terlambat'] . ' Hari</span>'; ?>
                        </td>
                        <td style="padding: 12px;">
                            <span style="padding: 6px 12px; font-size: 11px; border-radius: 20px; font-weight: 600; display: inline-block; <?= $row['badge_color']; ?>">
                                <?= $row['kategori']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
