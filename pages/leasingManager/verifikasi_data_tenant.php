<?php
session_start();
// require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php"; 

$page_title  = 'Verifikasi Data Tenant';                  
$active_page = 'verifikasi';                              
// $user_name   = $_SESSION['nama_lengkap'] ?? 'Guest';   
// $role        = $_SESSION['role_user'] ?? 'tenant';     
$user_name   = 'Leasing Manager';
$role        = 'leasingManager';

require_once "../../includes/navbarM02.php"; 

$query  = "SELECT * FROM `02_tenant_prospects` WHERE status = 'Prospect' ORDER BY register_Date DESC";
$prospekList = mysqli_query($conn, $query);

if (!$prospekList) {
    die("Gagal mengambil data: " . mysqli_error($conn));
}
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
        }

        .page-wrapper {
            padding: 24px 32px;
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
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
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);

            justify-content: center;
            align-items: center;

            z-index: 9999;
        }

        .modal-box {
            background: var(--primary, #0B376D);
            width: 350px;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
        }

        .modal-box h3 {
            margin-top: 0;
        }

        .modal-action {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .btn-batal,
        .btn-ya {
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-batal {
            background: #ccc;
        }

        .btn-ya {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
<div class="page-wrapper">
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
                    <tr id="tenant-<?= $tenant['id_prospect'] ?>">
                        <td colspan="6" style="text-align: center; padding: 30px; color: #94a3b8;">
                            Tidak ada data calon tenant yang menunggu verifikasi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prospekList as $tenant): ?>
                        <tr id="tenant-<?= $tenant['id_prospect'] ?>">
                            <td >
                                P-2026-<?= str_pad($tenant['id_prospect'], 3, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td >
                                <?= htmlspecialchars($tenant['brand_name']) ?>
                            </td>
                            <?php
                                $unit_id = (int)($tenant['interested_unit'] ?? 0);

                                $unit = match($unit_id) {
                                    1 => 'LG-01', 2 => 'LG-02', 3 => 'LG-03',
                                    4 => 'LT1-01', 5 => 'LT1-02', 8 => 'LT1-03', 10 => 'LT1-04',
                                    6 => 'LT2-01', 7 => 'LT2-02', 9 => 'LT2-03',
                                    11 => 'KG-LG-01', 12 => 'KG-LG-02',
                                    13 => 'KG-LT1-01', 14 => 'KG-LT1-02', 
                                    15 => 'KG-LT2-01', 16 => 'KG-LT2-02',
                                    17 => 'KG-BLT-01', 18 => 'KG-BLT-02',
                                    19 => 'PIK-LG-01', 20 => 'PIK-LG-02',
                                    21 => 'PIK-LT1-01', 22 => 'PIK-LT1-02',
                                    default => 'Unknown'
                                };                            
                            ?>
                            <td><?= htmlspecialchars($unit) ?></td>
                            <td><a href="#">Lihat NIB/NPWP</a></td>
                            <td><span class="status-pending">Menunggu Verifikasi</span></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-approve" data-action="approve" data-id="<?= $tenant['id_prospect'] ?>" onclick="bukaModal(this)">
                                        Setujui
                                    </button>

                                    <button class="btn btn-reject" data-action="reject" data-id="<?= $tenant['id_prospect'] ?>" onclick="bukaModal(this)">
                                        Tolak
                                    </button>                                
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="modalKonfirmasi" class="modal-overlay">
    <div class="modal-box">
        <h3>Konfirmasi</h3>

        <p id="pesanModal"></p>

        <div class="modal-action">
            <button class="btn-batal" onclick="tutupModal()">
                Batal
            </button>

            <button class="btn-ya" onclick="prosesVerifikasi()">
                Ya
            </button>
        </div>
    </div>
</div>

</div>
<script>
let actionDipilih = '';
let idDipilih = '';

function bukaModal(button) {
    actionDipilih = button.dataset.action;
    idDipilih = button.dataset.id;

    const pesan = actionDipilih === 'approve'
        ? 'Apakah Anda yakin ingin menyetujui tenant ini?'
        : 'Apakah Anda yakin ingin menolak tenant ini?';

    document.getElementById('pesanModal').textContent = pesan;

    document.getElementById('modalKonfirmasi').style.display = 'flex';
}

function tutupModal() {
    document.getElementById('modalKonfirmasi').style.display = 'none';
}

function prosesVerifikasi() {
    fetch(`proses_verifikasi.php?action=${actionDipilih}&id=${idDipilih}`)
        .then(response => response.json())
        .then(data => {
            tutupModal();
            if (data.success) {
                const baris = document.getElementById(`tenant-${idDipilih}`);
                if (baris) baris.remove();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error(error);
            alert('Terjadi kesalahan jaringan.');
        });
}
</script>
</body>
</html>