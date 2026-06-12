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
        /* @import font Poppins untuk kesan modern & profesional */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        /* Pengaturan dasar halaman */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f4f8; /* Warna latar belakang soft */
            color: #334155;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
        }

        /* Kotak pembungkus utama */
        .container {
            background: #ffffff;
            padding: 32px;
            border-radius: 16px; /* Corner lebih melengkung modern */
            box-shadow: 0 10px 25px rgba(2, 31, 66, 0.05); /* Soft shadow ala dashboard premium */
            max-width: 1100px;
            width: 100%;
        }

        h2 {
            color: #0f172a;
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 6px;
        }

        p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* Styling Tabel Responsif */
        .table-responsive {
            overflow-x: auto; /* Mencegah tabel rusak di layar kecil */
            margin-top: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            text-align: left;
        }

        th, td {
            padding: 16px 20px;
            font-size: 14px;
        }

        th {
            background-color: #f8fafc; /* Kontras lembut untuk header */
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        /* Efek baris ketika di-hover */
        tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Link Dokumen Legal */
        table a {
            color: #0284c7;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        table a:hover {
            color: #0369a1;
            text-decoration: underline;
        }

        /* Styling Tombol Aksi */
        .btn-group {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
        }

        .btn-approve {
            background-color: #dcfce7;
            color: #15803d;
        }

        .btn-approve:hover {
            background-color: #15803d;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(21, 128, 61, 0.2);
        }

        .btn-reject {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .btn-reject:hover {
            background-color: #b91c1c;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(185, 28, 28, 0.2);
        }

        /* Styling Status Badge (Capsule) */
        .status-pending {
            display: inline-block;
            background-color: #fef3c7;
            color: #d97706;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(217, 119, 6, 0.2);
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
                            <td style="font-weight: 600; color: #475569;">
                                P-2026-<?= str_pad($tenant['id'], 3, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td style="font-weight: 600; color: #0f172a;">
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