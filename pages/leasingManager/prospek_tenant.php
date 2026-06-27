<?php
require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php"; 

$page_title  = 'Pendaftaran Prospek';                  
$active_page = 'prospek';                              
$user_name   = $_SESSION['username'];   
$role        = $_SESSION['user_role'];     

require_once "../../includes/navbarM02.php"; 

$query  = "SELECT * FROM `02_tenant_prospects` ORDER BY id_prospect DESC";
$prospekList = mysqli_query($conn, $query);

if (!$prospekList) {
    die("Gagal mengambil data: " . mysqli_error($conn));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newProspek = [
        'brand_name'     => $_POST['nama_toko'],
        'id_category'    => match ($_POST['kategori']) {
            'F&B' => 1,
            'Retail' => 2,
            'Entertainment' => 3,
            'Service' => 4,
            'Health & Beauty' => 5,
            'Education' => 6,
            'Gaming' => 7,
            default => null,
        },
        'pic_name'       => $_POST['nama_pic'],
        'phone'          => $_POST['kontak'],
        'email'          => $_POST['email'],
        'notes'          => $_POST['catatan'],
        'unit'           => match ($_POST['unit_diminati']) {
            'LG-01' => 1, 'LG-02' => 2, 'LG-03' => 3,
            'LT1-01' => 4, 'LT1-02' => 5, 'LT1-03' => 8, 'LT1-04' => 10,
            'LT2-01' => 6, 'LT2-02' => 7, 'LT2-03' => 9,
            'KG-LG-01' => 11, 'KG-LG-02' => 12,
            'KG-LT1-01' => 13, 'KG-LT1-02' => 14, 
            'KG-LT2-01' => 15, 'KG-LT2-02' => 16,
            'KG-BLT-01' => 17, 'KG-BLT-02' => 18,
            'PIK-LG-01' => 19, 'PIK-LG-02' => 20,
            'PIK-LT1-01' => 21, 'PIK-LT1-02' => 22,
            default => null,
        },
        'status'         => 'Prospect',
        'register_date'  => date("Y-m-d"),
    ];
    $query = "INSERT INTO `02_tenant_prospects` (brand_name, id_category, pic_name, phone, email, notes, interested_unit, status, register_date) 
              VALUES 
              ('$newProspek[brand_name]', '$newProspek[id_category]', '$newProspek[pic_name]', '$newProspek[phone]', '$newProspek[email]', '$newProspek[notes]', '$newProspek[unit]', '$newProspek[status]', '$newProspek[register_date]')";

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

        .page-wrapper {
            padding: 24px 32px;
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

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
<div class="page-wrapper">
 
    <div class="page-header">
        <div>
            <p class="page-breadcrumb">Tenant & Leasing / Tenant Lifecycle</p>
            <h1 class="page-title">Pendaftaran Prospek Tenant</h1>
        </div>
    </div>
 
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
                        <?php while ($kat = mysqli_fetch_assoc($kategoriOptions)): ?>
                            <option value="<?= $kat['name'] ?>"><?= $kat['name'] ?></option>
                        <?php endwhile; ?>
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
                        <?php while ($unit = mysqli_fetch_assoc($unitOptions)): ?>
                            <option value="<?= $unit['unit_code'] ?>"><?= $unit['unit_code'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
 
                <div class="form-group form-group--full">
                    <label class="form-label" for="catatan">Catatan</label>
                    <textarea id="catatan" name="catatan" class="form-input form-textarea"
                              placeholder="Catatan tambahan mengenai prospek ini..."></textarea>
                </div>
 
            </div>
 
            <input type="hidden" name="status" value="Prospek">
            <input type="hidden" name="tgl_daftar" value="<?= date('Y-m-d') ?>">
 
            <div class="form-actions">
                <button type="reset" class="btn-secondary">Reset</button>
                <button type="submit" class="btn-primary">Simpan Prospek</button>
            </div>
        </form>
    </div>
 
    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-label">Total Prospek</span>
            <span class="stat-value"><?= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM `02_tenant_prospects`"))[0] ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Bulan Ini</span>
            <span class="stat-value"><?= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM `02_tenant_prospects` WHERE MONTH(register_Date) = MONTH(CURDATE()) AND YEAR(register_Date) = YEAR(CURDATE())"))[0] ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Menunggu Verifikasi</span>
            <span class="stat-value"><?= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM `02_tenant_prospects` WHERE status = 'Prospect'"))[0] ?></span>
        </div>
    </div>
 
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
                        <?php 
                            $query = "SELECT p.*, c.name AS category_name, u.unit_code AS unit
                                      FROM `02_tenant_prospects` p
                                      LEFT JOIN `01_tenant_categories` c ON p.id_category = c.id_tenant_categories
                                      LEFT JOIN `01_units` u ON p.interested_unit = u.id_units
                                      ORDER BY p.register_Date DESC";
                            $i = mysqli_query($conn, $query);
                            $j = 0;
                            while ($p = mysqli_fetch_assoc($i)):
                            ?>
                            <tr>
                                <td><?= $j = $j + 1 ?></td>
                                <td class="td-bold"><?= htmlspecialchars($p['brand_name']) ?></td>
                                <td><?= htmlspecialchars($p['category_name']) ?></td>
                                <td><?= htmlspecialchars($p['pic_name']) ?></td>
                                <td><?= htmlspecialchars($p['phone']) ?></td>
                                <td><?= htmlspecialchars($p['unit']) ?></td>
                                <td><?= htmlspecialchars($p['register_date']) ?></td>
                                <td><span class="badge badge--prospek"><?= $p['status'] ?></span></td>
                                <td>
                                    <div class="action-group">
                                        <a href="detail-prospek.php?id=<?= $p['id_prospect'] ?>" class="btn-action btn-action--view" title="Detail">
                                            Detail
                                        </a>
                                        <a href="edit-prospek.php?id=<?= $p['id_prospect'] ?>" class="btn-action btn-action--edit" title="Edit">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>