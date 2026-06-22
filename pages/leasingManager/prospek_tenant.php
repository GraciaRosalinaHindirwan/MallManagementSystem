<?php
session_start();
require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php"; 

$page_title  = 'Pendaftaran Prospek';                  
$active_page = 'prospek';                              
$user_name   = $_SESSION['nama_lengkap'] ?? 'Guest';   
$role        = $_SESSION['role_user'] ?? 'tenant';     

require_once "../../includes/navbarM02.php"; 

$query  = "SELECT * FROM `02_tenant_prospects` ORDER BY id_prospect DESC";
$prospekList = mysqli_query($conn, $query);

if (!$prospekList) {
    die("Gagal mengambil data: " . mysqli_error($conn));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newProspek = [
        'brand_name'     => $_POST['nama_toko'],
        'id_category'    => $_POST['kategori'],
        'pic_name'       => $_POST['nama_pic'],
        'phone'          => $_POST['kontak'],
        'email'          => $_POST['email'],
        'notes'          => $_POST['catatan'],
        'unit'           => $_POST['unit_diminati'],
        'status'         => 'Prospect',
        'register_date'  => date("Y-m-d"),
    ];
    $query = "INSERT INTO `02_tenant_prospects` (brand_name, id_category, pic_name, phone, email, notes, status, register_date) 
              VALUES 
              ('$newProspek[brand_name]', '$newProspek[id_category]', '$newProspek[pic_name]', '$newProspek[phone]', '$newProspek[email]', '$newProspek[notes]', '$newProspek[status]', '$newProspek[register_date]')";

    // Eksekusi query
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data berhasil disimpan!'); window.location='index.php';</script>";
    } else {
        echo "Gagal menyimpan data: " . mysqli_error($conn);
    }
}

$kategoriOptions = mysqli_query($conn, "SELECT name FROM `01_tenant_categories`");
$unitOptions     = mysqli_query($conn, "SELECT unit_code FROM `01_units` WHERE status = 'available'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prospek Tenant</title>
    <!-- style -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font-family, 'Poppins', sans-serif);
            background: var(--background, #021F42);
            color: var(--text, #F5F7FA);
            font-size: var(--body, 16px);
        }

        /* layout */
        .page-wrapper {
            padding: 24px 32px;
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* header page */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .page-breadcrumb {
            font-size: var(--caption, 12px);
            color: var(--accent, #00D4D8);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .page-title {
            font-size: var(--h1, 32px);
            font-weight: 700;
            color: var(--text, #F5F7FA);
        }

        /* stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .stat-card {
            background: var(--primary, #0B376D);
            border-radius: 12px;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            border: 1px solid rgba(0,212,216,0.15);
        }

        .stat-label {
            font-size: var(--caption, 12px);
            color: var(--accent, #00D4D8);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .stat-value {
            font-size: var(--h2, 24px);
            font-weight: 700;
            color: var(--text, #F5F7FA);
        }

        /* card */
        .card {
            background: var(--primary, #0B376D);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.08);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .card-title {
            font-size: var(--subheading, 20px);
            font-weight: 600;
        }

        /* button */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent, #00D4D8);
            color: var(--background, #021F42);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-family: inherit;
            font-size: var(--label, 14px);
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s;
            text-decoration: none;
        }
        .btn-primary:hover { opacity: 0.85; }

        .btn-secondary {
            background: transparent;
            color: var(--text, #F5F7FA);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            padding: 10px 20px;
            font-family: inherit;
            font-size: var(--label, 14px);
            cursor: pointer;
            transition: border-color 0.15s;
        }
        .btn-secondary:hover { border-color: var(--accent, #00D4D8); }

        .btn-close {
            background: none;
            border: none;
            color: var(--text, #F5F7FA);
            font-size: 18px;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.15s;
        }
        .btn-close:hover { opacity: 1; }

        /* form */
        .form-card { padding-bottom: 8px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 24px;
            padding: 24px;
        }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group--full { grid-column: 1 / -1; }

        .form-label {
            font-size: var(--label, 14px);
            font-weight: 500;
            color: rgba(245,247,250,0.8);
        }
        .required { color: var(--danger, #EF4444); }

        .form-input {
            background: var(--background, #021F42);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 10px 14px;
            color: var(--text, #F5F7FA);
            font-family: inherit;
            font-size: var(--label, 14px);
            outline: none;
            transition: border-color 0.15s;
        }
        .form-input:focus { border-color: var(--accent, #00D4D8); }
        .form-input option { background: #0B376D; }

        .form-textarea {
            resize: vertical;
            min-height: 90px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 0 24px 24px;
        }

        /* search */
        .search-wrapper { position: relative; }
        .search-input {
            background: var(--background, #021F42);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            padding: 8px 14px;
            color: var(--text, #F5F7FA);
            font-family: inherit;
            font-size: var(--label, 14px);
            width: 240px;
            outline: none;
            transition: border-color 0.15s;
        }
        .search-input:focus { border-color: var(--accent, #00D4D8); }

        /* tabel */
        .table-wrapper { overflow-x: auto; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--label, 14px);
        }

        .data-table thead tr {
            background: var(--primary-dark, #082A53);
        }

        .data-table th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            font-size: var(--caption, 12px);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent, #00D4D8);
            white-space: nowrap;
        }

        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }

        .data-table tbody tr:hover {
            background: rgba(0,212,216,0.04);
        }

        .td-bold { font-weight: 600; }

        .empty-state {
            text-align: center;
            padding: 48px 16px;
            color: rgba(245,247,250,0.4);
            font-size: var(--label, 14px);
        }

        /* badge */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: var(--caption, 12px);
            font-weight: 600;
            white-space: nowrap;
        }
        .badge--prospek {
            background: rgba(255,182,42,0.15);
            color: var(--text-accent, #FFB62A);
            border: 1px solid rgba(255,182,42,0.3);
        }

        /* ---------- Action Buttons ---------- */
        .action-group { display: flex; gap: 8px; }

        .btn-action {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: var(--caption, 12px);
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .btn-action:hover { opacity: 0.8; }

        .btn-action--view {
            background: rgba(0,212,216,0.15);
            color: var(--accent, #00D4D8);
            border: 1px solid rgba(0,212,216,0.3);
        }

        .btn-action--edit {
            background: rgba(255,182,42,0.15);
            color: var(--text-accent, #FFB62A);
            border: 1px solid rgba(255,182,42,0.3);
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {
            .page-wrapper { padding: 16px; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
            .stats-row { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .card-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .search-input { width: 100%; }
        }
    </style>
</head>
<script>
    // Toggle form tambah prospek
    function toggleForm() {
        const form = document.getElementById('formProspek');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') {
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Filter tabel berdasarkan pencarian
    function filterTable() {
        const input  = document.getElementById('searchInput').value.toLowerCase();
        const rows   = document.querySelectorAll('#prospekTable tbody tr');

        rows.forEach(row => {
            const namaToko = row.cells[1]?.textContent.toLowerCase() || '';
            row.style.display = namaToko.includes(input) ? '' : 'none';
        });
    }
</script>

<body>
<!-- Page Content -->
<div class="page-wrapper">
 
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <p class="page-breadcrumb">Tenant & Leasing / Tenant Lifecycle</p>
            <h1 class="page-title">Pendaftaran Prospek Tenant</h1>
        </div>
    </div>
 
    <!-- Form Tambah Prospek -->
    <div class="card form-card" id="formProspek">
        <div class="card-header">
            <h2 class="card-title">Form Pendaftaran Prospek Baru</h2>
        </div>
 
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
            <div class="form-grid">
 
                <div class="form-group">
                    <label class="form-label" for="nama_toko">Nama Toko / Tenant <span class="required">*</span></label>
                    <input type="text" id="nama_toko" name="nama_toko" class="form-input"
                           placeholder="Contoh: Kopi Nusantara" required>
                </div>
 
                <div class="form-group">
                    <label class="form-label" for="kategori">Kategori Bisnis <span class="required">*</span></label>
                    <select id="kategori" name="kategori" class="form-input" required>
                        <option value="" disabled selected>Pilih kategori...</option>
                        <?php foreach ($kategoriOptions as $kat): ?>
                            <option value="<?= $kat ?>"><?= $kat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
 
                <div class="form-group">
                    <label class="form-label" for="nama_pic">Nama PIC <span class="required">*</span></label>
                    <input type="text" id="nama_pic" name="nama_pic" class="form-input"
                           placeholder="Nama penanggung jawab" required>
                </div>
 
                <div class="form-group">
                    <label class="form-label" for="kontak">Nomor Kontak <span class="required">*</span></label>
                    <input type="text" id="kontak" name="kontak" class="form-input"
                           placeholder="08xxxxxxxxxx" required>
                </div>
 
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input"
                           placeholder="email@contoh.com">
                </div>
 
                <div class="form-group">
                    <label class="form-label" for="unit_diminati">Unit yang Diminati</label>
                    <select id="unit_diminati" name="unit_diminati" class="form-input">
                        <option value="" selected>Belum ditentukan</option>
                        <?php foreach ($unitOptions as $unit): ?>
                            <option value="<?= $unit ?>"><?= $unit ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
 
                <div class="form-group form-group--full">
                    <label class="form-label" for="catatan">Catatan</label>
                    <textarea id="catatan" name="catatan" class="form-input form-textarea"
                              placeholder="Catatan tambahan mengenai prospek ini..."></textarea>
                </div>
 
            </div>
 
            <!-- Status otomatis -->
            <input type="hidden" name="status" value="Prospek">
            <input type="hidden" name="tgl_daftar" value="<?= date('Y-m-d') ?>">
 
            <div class="form-actions">
                <button type="reset" class="btn-secondary">Reset</button>
                <button type="submit" class="btn-primary">Simpan Prospek</button>
            </div>
        </form>
    </div>
 
    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-label">Total Prospek</span>
            <span class="stat-value"><?= count($prospekList) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Bulan Ini</span>
            <span class="stat-value">3</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Menunggu Verifikasi</span>
            <span class="stat-value">3</span>
        </div>
    </div>
 
    <!-- Tabel Daftar Prospek -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Pipeline Prospek</h2>
            <div class="search-wrapper">
                <input type="text" id="searchInput" class="search-input"
                       placeholder="Cari nama toko..." onkeyup="filterTable()">
            </div>
        </div>
 
        <div class="table-wrapper">
            <table class="data-table" id="prospekTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Toko</th>
                        <th>Kategori</th>
                        <th>PIC</th>
                        <th>Kontak</th>
                        <th>Unit Diminati</th>
                        <th>Tgl Daftar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prospekList)): ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                Belum ada data prospek yang terdaftar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($prospekList as $i => $p): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="td-bold"><?= htmlspecialchars($p['nama_toko']) ?></td>
                                <td><?= htmlspecialchars($p['kategori']) ?></td>
                                <td><?= htmlspecialchars($p['pic']) ?></td>
                                <td><?= htmlspecialchars($p['kontak']) ?></td>
                                <td><?= htmlspecialchars($p['unit']) ?></td>
                                <td><?= htmlspecialchars($p['tgl_daftar']) ?></td>
                                <td><span class="badge badge--prospek"><?= $p['status'] ?></span></td>
                                <td>
                                    <div class="action-group">
                                        <a href="detail-prospek.php?id=<?= $p['id'] ?>" class="btn-action btn-action--view" title="Detail">
                                            Detail
                                        </a>
                                        <a href="edit-prospek.php?id=<?= $p['id'] ?>" class="btn-action btn-action--edit" title="Edit">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
 
</div>
</body>
</html>