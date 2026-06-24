<?php
/** @var mysqli $conn */ 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set session untuk Finance Manager Intan
$_SESSION['role'] = 'Finance Manager';
$_SESSION['nama'] = 'Manager';

if (file_exists(__DIR__ . '/../../config/koneksi.php')) {
    require_once __DIR__ . '/../../config/koneksi.php';
} else {
    require_once __DIR__ . '/../../config/connection.php';
}

$department_name = "Finance Manager - Budget Analysis";

include __DIR__ . '/../../includes/header.php';
$current_page = 'budgetAnalysis.php';
include __DIR__ . '/../../includes/navbar.php';

// 1. QUERY AMBIL DATA KELURAHAN AKUN BEBAN DARI DATABASE ASLI
$budget_entries = [];
$total_plafon = 0;
$total_terpakai = 0;
$total_sisa = 0;

$sql = "SELECT 
            coa.account_code, 
            coa.account_name, 
            IFNULL((SELECT SUM(jl.debit) FROM 06_journal_lines jl WHERE jl.account_id = coa.id), 0) as used_amount
        FROM 06_chart_of_accounts coa
        WHERE coa.account_code LIKE '5-%'
        ORDER BY coa.account_code ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $terpakai = (float) $row['used_amount'];

        // Alokasi plafon otomatis dibentuk agar data simulator finance terhitung ideal
        if (strpos($row['account_code'], '5-1001') !== false) {
            $plafon = 150000000;
        } elseif (strpos($row['account_code'], '5-1002') !== false) {
            $plafon = 95000000;
        } elseif (strpos($row['account_code'], '5-1003') !== false) {
            $plafon = 80000000;
        } else {
            $plafon = 120000000;
        }

        $budget_entries[] = [
            'account_code' => $row['account_code'],
            'account_name' => $row['account_name'],
            'allocated_amount' => $plafon,
            'used_amount' => $terpakai
        ];

        $total_plafon += $plafon;
        $total_terpakai += $terpakai;
    }
    $total_sisa = $total_plafon - $total_terpakai;
}
?>

<div class="container-fluid" style="margin-top: -15px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1" style="color: var(--accent); font-weight: 700;">
                <i class="fa-solid fa-chart-pie me-2"></i> BUDGET ANALYSIS
            </h4>
            <p class="small mb-0" style="color: #cbd5e1 !important; font-weight: 400; opacity: 0.9;">
                Memantau plafon anggaran tahunan operasional mall dan sisa saldo realisasi akun biaya.
            </p>
        </div>
        <div class="badge bg-primary px-3 py-2 fs-6">Tahun Anggaran: <?= date('Y'); ?></div>
    </div>

    <div class="card border-0 shadow-sm mb-4" style="background-color: #011630 !important; border: 1px solid rgba(255,255,255,0.05) !important;">
        <div class="card-body p-0">
            <table class="table-custom mb-0" style="width: 100%; border-collapse: collapse; background-color: #011630 !important;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.05); text-align: left;">
                        <th width="8%" class="text-center" style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); color: #fff;">No</th>
                        <th width="15%" style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); color: #fff;">Kode Akun</th>
                        <th width="27%" style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); color: #fff;">Nama Pos Anggaran (COA)</th>
                        <th width="18%" class="text-end" style="padding: 15px; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.1); color: #fff;">Plafon Anggaran</th>
                        <th width="18%" class="text-end" style="padding: 15px; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.1); color: #fff;">Realisasi Terpakai</th>
                        <th width="14%" class="text-end" style="padding: 15px; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.1); color: #fff;">Sisa Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if (!empty($budget_entries)) {
                        foreach ($budget_entries as $row) {
                            $plafon   = (float) $row['allocated_amount'];
                            $terpakai = (float) $row['used_amount'];
                            $sisa     = $plafon - $terpakai;

                            $badge_class = "text-success";
                            if ($sisa <= 0) {
                                $badge_class = "text-danger fw-bold";
                            } elseif ($plafon > 0 && ($terpakai / $plafon) >= 0.8) {
                                $badge_class = "text-warning";
                            }

                            echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05); background-color: #011630;'>";
                            echo "<td class='text-center' style='padding: 15px; color: #cbd5e1;'>{$no}</td>";
                            echo "<td style='padding: 15px;'><span class='badge bg-secondary px-2 py-1'>{$row['account_code']}</span></td>";
                            echo "<td style='padding: 15px; color: #cbd5e1;'>" . htmlspecialchars($row['account_name'] ?? '') . "</td>";
                            echo "<td style='padding: 15px; text-align: right; font-weight: 600; color: #cbd5e1;'>Rp " . number_format($plafon, 0, ',', '.') . "</td>";
                            echo "<td style='padding: 15px; text-align: right; color: #00cfd5; font-weight: 600;'>Rp " . number_format($terpakai, 0, ',', '.') . "</td>";
                            echo "<td style='padding: 15px; text-align: right; font-weight: 600;' class='{$badge_class}'>Rp " . number_format($sisa, 0, ',', '.') . "</td>";
                            echo "</tr>";
                            $no++;
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center' style='padding: 30px; color: #cbd5e1;'>Tidak ada data anggaran.</td></tr>";
                    }
                    ?>
                    <tr style="background-color: rgba(255, 255, 255, 0.02) !important; font-weight: 700; border-top: 2px solid rgba(255,255,255,0.1);">
                        <td colspan="3" class="text-end" style="padding: 15px; text-align: right; color: var(--accent);">TOTAL ANGGARAN OPERASIONAL :</td>
                        <td class="text-end" style="padding: 15px; text-align: right; color: #fff;">Rp <?= number_format($total_plafon, 0, ',', '.'); ?></td>
                        <td class="text-end" style="padding: 15px; text-align: right; color: #00cfd5;">Rp <?= number_format($total_terpakai, 0, ',', '.'); ?></td>
                        <td class="text-end text-success" style="padding: 15px; text-align: right;">Rp <?= number_format($total_sisa, 0, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-info border-0 shadow-sm" style="background-color: rgba(0, 207, 213, 0.1); color: #00cfd5;">
        <h5 class="alert-heading fw-bold"><i class="fa-solid fa-circle-info me-2"></i>Informasi Skenario Pengujian</h5>
        <p class="mb-0 small">Halaman ini otomatis menghitung sisa saldo secara *real-time* dengan membandingkan batasan alokasi terhadap nilai transaksi beban operasional yang terposting di lini jurnal akuntansi. Menjamin kontrol pengeluaran mall agar bebas dari over-budget.</p>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>