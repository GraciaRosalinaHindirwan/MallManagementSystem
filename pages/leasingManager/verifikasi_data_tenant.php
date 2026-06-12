<?php
// Mengaktifkan session untuk mengambil data dari halaman pendaftaran
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mengambil data dari session. Jika session kosong, dibuatkan fallback data awal
$prospekList = $_SESSION['prospekList'] ?? [
    [
        'id'         => 1,
        'nama_toko'  => 'Kopi Nusantara',
        'kategori'   => 'F&B',
        'pic'        => 'Budi Santoso',
        'kontak'     => '081234567890',
        'unit'       => 'LG-12',
        'status'     => 'Prospek',
        'tgl_daftar' => '2025-06-01',
    ],
    [
        'id'         => 2,
        'nama_toko'  => 'Sepatu Keren',
        'kategori'   => 'Fashion',
        'pic'        => 'Ani Wijaya',
        'kontak'     => '082198765432',
        'unit'       => '1F-05',
        'status'     => 'Prospek',
        'tgl_daftar' => '2025-06-03',
    ],
    [
        'id'         => 3,
        'nama_toko'  => 'Gadget World',
        'kategori'   => 'Electronics',
        'pic'        => 'Rudi Hartono',
        'kontak'     => '085611223344',
        'unit'       => '2F-08',
        'status'     => 'Prospek',
        'tgl_daftar' => '2025-06-05',
    ], 
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Data Tenant - Mall ERP</title>
    
    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--font-family, 'Poppins', sans-serif);
    background: var(--background, #021F42);
    color: var(--text, #F5F7FA);
    font-size: var(--body, 16px);
    padding: 40px 20px;
    display: flex;
    justify-content: center;
}

/* ── Container utama ── */
.container {
    background: var(--primary, #0B376D);
    padding: 32px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 32px rgba(2, 31, 66, 0.4);
    max-width: 1100px;
    width: 100%;
}

h2 {
    color: var(--text, #F5F7FA);
    font-weight: 700;
    font-size: var(--h2, 24px);
    margin-bottom: 6px;
}

p {
    color: rgba(245, 247, 250, 0.5);
    font-size: var(--label, 14px);
    margin-bottom: 24px;
}

/* ── Tabel ── */
.table-responsive {
    overflow-x: auto;
    margin-top: 20px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
    background-color: transparent;
    text-align: left;
}

th, td {
    padding: 14px 20px;
    font-size: var(--label, 14px);
}

th {
    background-color: var(--primary-dark, #082A53);
    color: var(--accent, #00D4D8);
    font-weight: 600;
    text-transform: uppercase;
    font-size: var(--caption, 12px);
    letter-spacing: 0.05em;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    white-space: nowrap;
}

td {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text, #F5F7FA);
    vertical-align: middle;
}

tbody tr:hover {
    background: rgba(0, 212, 216, 0.04);
}

/* ── Link dokumen legal ── */
table a {
    color: var(--accent, #00D4D8);
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.2s ease;
}

table a:hover {
    opacity: 0.75;
    text-decoration: underline;
}

/* ── Tombol aksi Approve / Reject ── */
.btn-group {
    display: flex;
    gap: 8px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: var(--caption, 12px);
    font-weight: 600;
    font-family: inherit;
    text-decoration: none;
    transition: all 0.2s ease-in-out;
}

.btn-approve {
    background: rgba(34, 197, 94, 0.15);
    color: var(--success, #22C55E);
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.btn-approve:hover {
    background: var(--success, #22C55E);
    color: var(--background, #021F42);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25);
}

.btn-reject {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger, #EF4444);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-reject:hover {
    background: var(--danger, #EF4444);
    color: var(--text, #F5F7FA);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
}

/* ── Badge status ── */
.status-pending {
    display: inline-block;
    background: rgba(255, 182, 42, 0.15);
    color: var(--text-accent, #FFB62A);
    padding: 4px 12px;
    border-radius: 99px;
    font-size: var(--caption, 12px);
    font-weight: 600;
    border: 1px solid rgba(255, 182, 42, 0.3);
    white-space: nowrap;
}
    </style>
</head>
<body>

<div class="container">
    <h2>Verifikasi Data Calon Tenant</h2>
    <p>Daftar calon tenant yang menunggu validasi dokumen dan kelayakan data.</p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID Prospek</th>
                    <th>Nama Tenant</th>
                    <th>Unit Diminta</th>
                    <th>Dokumen Legal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prospekList)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #94a3b8;">
                            Tidak ada data calon tenant yang menunggu verifikasi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prospekList as $tenant): ?>
                        <tr>
                            <td >
                                P-2026-<?= str_pad($tenant['id'], 3, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td >
                                <?= htmlspecialchars($tenant['nama_toko']) ?>
                            </td>
                            <td><?= htmlspecialchars($tenant['unit']) ?></td>
                            <td><a href="#">Lihat NIB/NPWP</a></td>
                            <td><span class="status-pending">Menunggu Verifikasi</span></td>
                            <td>
                                <div class="btn-group">
                                    <a href="proses_verifikasi.php?action=approve&id=<?= $tenant['id'] ?>" class="btn btn-approve">Setujui</a>
                                    <a href="proses_verifikasi.php?action=reject&id=<?= $tenant['id'] ?>" class="btn btn-reject">Tolak</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>