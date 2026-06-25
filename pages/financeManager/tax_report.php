<?php

/** @var mysqli $conn */ // Memberitahu VS Code agar tidak memunculkan garis merah pada variabel $conn

// 1. Inisialisasi Session dan Deteksi Otomatis File Koneksi Database
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mengambil file koneksi database bawaan project secara aman (Mundur 2 tingkat ke config)
if (file_exists(__DIR__ . '/../../config/koneksi.php')) {
    require_once __DIR__ . '/../../config/koneksi.php';
} else {
    require_once __DIR__ . '/../../config/connection.php';
}

// Mengatur judul departemen dinamis untuk dibaca top-navbar (Otomatis dibaca navbar.php)
$department_name = "Finance Manager - Tax Report";

// 2. Sertakan File Header Atas (Mengandung CSS & Pembuka HTML)
include __DIR__ . '/../../includes/header.php';
if (!file_exists(__DIR__ . '/../../includes/header.php')) {
    include __DIR__ . '/../../header.php'; // Alternatif jika ditaruh di root luar
}

// 3. Sertakan File Navbar & Sidebar (Otomatis mendeteksi role Finance Manager & Nama Eva)
include __DIR__ . '/../../includes/navbar.php';
if (!file_exists(__DIR__ . '/../../includes/navbar.php')) {
    include __DIR__ . '/../../navbar.php'; // Alternatif jika ditaruh di root luar
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--accent); font-weight: 700;">
                    <i class="fa-solid fa-receipt me-2"></i> TAX REPORT
                </h4>
                <p class="small mb-0" style="color: #cbd5e1 !important; font-weight: 400; opacity: 0.9;">
                    Rekapitulasi pelaporan pajak masukan dan keluaran berdasarkan mutasi akun PPN pada jurnal transaksi mall.
                </p>
            </div>
            <div class="badge bg-primary px-3 py-2 fs-6">Bulan Laporan: <?php echo date('F Y'); ?></div>
        </div>

        <div class="card bg-dark text-white border-0 shadow-sm mb-4" style="background-color: #011630 !important; border: 1px solid rgba(255,255,255,0.05) !important;">
            <div class="card-body p-0">
                <table class="table-custom mb-0" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="15%">Tanggal Jurnal</th>
                            <th width="15%">No. Jurnal</th>
                            <th width="35%">Keterangan Transaksi</th>
                            <th width="15%" class="text-end">Pajak Masukan (Debet)</th>
                            <th width="15%" class="text-end">Pajak Keluaran (Kredit)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        /**
                         * Query SQL untuk menyaring data Jurnal yang mengandung transaksi perpajakan.
                         * Sistem menyaring baris akun COA yang memiliki unsur kata 'Pajak' atau 'PPN' 
                         * pada tabel journal_entry_items dan menghubungkannya ke induk journal_entries.
                         */
                        $sql = "SELECT je.journal_date, je.journal_number, je.description, jti.debit, jti.credit, coa.account_name
                                FROM journal_entry_items jti
                                JOIN journal_entries je ON jti.journal_id = je.id
                                JOIN chart_of_accounts coa ON jti.account_id = coa.id
                                WHERE coa.account_name LIKE '%Pajak%' OR coa.account_name LIKE '%PPN%'
                                ORDER BY je.journal_date DESC, je.journal_number DESC";

                        $result = $conn->query($sql);
                        $no = 1;

                        $total_masukan  = 0;
                        $total_keluaran = 0;

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $debit  = (float) $row['debit'];
                                $credit = (float) $row['credit'];

                                $total_masukan  += $debit;
                                $total_keluaran += $credit;

                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                echo "<td class='text-center' style='padding: 15px;'>{$no}</td>";
                                echo "<td style='padding: 15px;'>" . date('d-m-Y', strtotime($row['journal_date'])) . "</td>";
                                echo "<td style='padding: 15px;'><span class='badge bg-secondary px-2 py-1'>{$row['journal_number']}</span></td>";
                                echo "<td style='padding: 15px;'>" . htmlspecialchars($row['description'] ?? '') . " <br><small class='text-info'>(" . htmlspecialchars($row['account_name']) . ")</small></td>";
                                echo "<td class='text-end style='padding: 15px;'>" . ($debit > 0 ? "Rp " . number_format($debit, 2, ',', '.') : "-") . "</td>";
                                echo "<td class='text-end text-warning' style='padding: 15px;'> " . ($credit > 0 ? "Rp " . number_format($credit, 2, ',', '.') : "-") . "</td>";
                                echo "</tr>";
                                $no++;
                            }

                            // Baris Total Akumulasi Pajak
                            echo "<tr style='background-color: rgba(255,182,42,0.05); font-weight: 700; border-top: 2px solid rgba(255,255,255,0.1);'>";
                            echo "<td colspan='4' class='text-end' style='padding: 15px; color: var(--accent);'>TOTAL REKAPITULASI PAJAK :</td>";
                            echo "<td class='text-end text-white' style='padding: 15px;'>Rp " . number_format($total_masukan, 2, ',', '.') . "</td>";
                            echo "<td class='text-end text-warning' style='padding: 15px;'>Rp " . number_format($total_keluaran, 2, ',', '.') . "</td>";
                            echo "</tr>";
                        } else {
                            // Pemberitahuan Data Kosong Berwarna Terang Kontras
                            echo "<tr><td colspan='6' class='text-center py-4' style='padding: 30px; color: #cbd5e1 !important;'><i class='fa-solid fa-folder-open me-2' style='color: var(--accent);'></i>Belum ada riwayat transaksi jurnal akuntansi yang mengandung unsur Pajak/PPN.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-info border-0 shadow-sm" style="background-color: rgba(0, 207, 213, 0.1); color: #00cfd5;">
            <h5 class="alert-heading fw-bold"><i class="fa-solid fa-circle-info me-2"></i>Informasi Skenario Pengujian (Eva)</h5>
            <p class="mb-0 small">Halaman ini menguji kapabilitas penyaringan data laporan keuangan (*Tax Ledger Reporting*). Sistem secara otomatis membaca seluruh baris item jurnal (`journal_entry_items`) dan memisahkan Pajak Masukan (Debet) atau Pajak Keluaran (Kredit) untuk mempermudah pelaporan SPT Masa PPN Mall.</p>
        </div>
    </div>
</div>

<?php
// 4. Sertakan File Footer (Mengandung Script Bootstrap & Penutup HTML)
include __DIR__ . '/../../includes/footer.php';
if (!file_exists(__DIR__ . '/../../includes/footer.php')) {
    include __DIR__ . '/../../footer.php'; // Alternatif jika ditaruh di root luar
}
?>