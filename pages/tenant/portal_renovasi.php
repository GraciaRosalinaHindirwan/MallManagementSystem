<?php
session_start();
// require_once "../../public/auth/checkSessionTenant.php";
require_once "../../config/konek.php";

$page_title  = 'Permohonan Renovasi';
$active_page = 'portal-renovasi';
// $user_name   = $_SESSION['brand_name'] ?? 'Guest';
// $role        = $_SESSION['role_user'] ?? 'tenant';
$user_name   = 'Tenant';
$role        = 'tenant';

require_once "../../includes/navbarM02.php";

$id_tenant = $_SESSION['id_tenant'] ?? 1; //dummy doang

$query   = "SELECT c.id_contract, c.contract_number, u.unit_code
            FROM `02_contracts` c
            JOIN `01_units` u ON u.id_units = c.id_unit
            WHERE c.id_tenant = $id_tenant AND c.contract_status = 'Active'
            ORDER BY u.unit_code";
$contractList = mysqli_query($conn, $query);

if (!$contractList) {
    die("Gagal mengambil data kontrak: " . mysqli_error($conn));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_contract = (int) ($_POST['id_contract'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $start_date  = $_POST['proposed_start_date'] ?? '';
    $end_date    = $_POST['proposed_end_date'] ?? '';
    $file        = $_FILES['attachment_plan'] ?? null;

    $cekPending = mysqli_fetch_row(mysqli_query($conn, "
        SELECT COUNT(*) FROM `02_tenant_renovations`
        WHERE id_contract = $id_contract AND status IN ('Pending','In Review')
    "))[0];

    if ($end_date <= $start_date) {
        echo "<script>alert('Tanggal selesai harus setelah tanggal mulai.');</script>";
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('Dokumen rencana renovasi wajib diunggah.');</script>";
    } elseif (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png'], true)) {
        echo "<script>alert('Dokumen harus berformat PDF, JPG, atau PNG.');</script>";
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        echo "<script>alert('Ukuran dokumen maksimal 5MB.');</script>";
    } elseif ($cekPending > 0) {
        echo "<script>alert('Sudah ada pengajuan renovasi yang masih diproses untuk unit ini.');</script>";
    } else {
        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uploadDir = "../../uploads/renovasi/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'renov_' . $id_contract . '_' . time() . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
        $attachmentPath = '/MallManagementSystem/uploads/renovasi/' . $filename;

        $stmt = mysqli_prepare($conn, "
            INSERT INTO `02_tenant_renovations`
                (id_contract, description, proposed_start_date, proposed_end_date, attachment_plan_url, status)
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");
        mysqli_stmt_bind_param($stmt, "issss", $id_contract, $description, $start_date, $end_date, $attachmentPath);

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Pengajuan renovasi berhasil dikirim!'); window.location='portal_renovasi.php';</script>";
        } else {
            echo "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }
}

$riwayatQuery = "SELECT r.id_renovation, r.description, r.proposed_start_date, r.proposed_end_date,
                         r.attachment_plan_url, r.status, c.contract_number, u.unit_code
                  FROM `02_tenant_renovations` r
                  JOIN `02_contracts` c ON c.id_contract = r.id_contract
                  JOIN `01_units` u ON u.id_units = c.id_unit
                  WHERE c.id_tenant = $id_tenant
                  ORDER BY r.id_renovation DESC";
$riwayatResult = mysqli_query($conn, $riwayatQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Renovasi</title>
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

        .form-textarea { resize: vertical; min-height: 90px; }

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

        .data-table thead tr { background: var(--primary-dark, #082A53); }

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

        .data-table tbody tr:hover { background: rgba(0,212,216,0.04); }

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
        .badge--pending {
            background: rgba(255,182,42,0.15);
            color: var(--text-accent, #FFB62A);
            border: 1px solid rgba(255,182,42,0.3);
        }
        .badge--in-review {
            background: rgba(0,212,216,0.15);
            color: var(--accent, #00D4D8);
            border: 1px solid rgba(0,212,216,0.3);
        }
        .badge--approved {
            background: rgba(34,197,94,0.15);
            color: var(--success, #22C55E);
            border: 1px solid rgba(34,197,94,0.3);
        }
        .badge--rejected {
            background: rgba(239,68,68,0.15);
            color: var(--danger, #EF4444);
            border: 1px solid rgba(239,68,68,0.3);
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
    function filterTable() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows  = document.querySelectorAll('#riwayatTable tbody tr');

        rows.forEach(row => {
            const unit = row.cells[1]?.textContent.toLowerCase() || '';
            row.style.display = unit.includes(input) ? '' : 'none';
        });
    }
</script>

<body>
<div class="page-wrapper">

    <div class="page-header">
        <div>
            <p class="page-breadcrumb">Tenant / Tenant Portal</p>
            <h1 class="page-title">Pengajuan Renovasi Unit</h1>
        </div>
    </div>

    <div class="card form-card" id="formRenovasi">
        <div class="card-header">
            <h2 class="card-title">Form Pengajuan Renovasi Baru</h2>
        </div>

        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" enctype="multipart/form-data">
            <div class="form-grid">

                <div class="form-group">
                    <label class="form-label" for="id_contract">Unit / Kontrak <span class="required">*</span></label>
                    <select id="id_contract" name="id_contract" class="form-input" required>
                        <option value="" disabled selected>Pilih unit...</option>
                        <?php while ($c = mysqli_fetch_assoc($contractList)): ?>
                            <option value="<?= $c['id_contract'] ?>"><?= htmlspecialchars($c['unit_code']) ?> — <?= htmlspecialchars($c['contract_number']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="attachment_plan">Dokumen Rencana Renovasi <span class="required">*</span></label>
                    <input type="file" id="attachment_plan" name="attachment_plan" class="form-input" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="proposed_start_date">Tanggal Mulai Renovasi <span class="required">*</span></label>
                    <input type="date" id="proposed_start_date" name="proposed_start_date" class="form-input" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="proposed_end_date">Tanggal Selesai Renovasi <span class="required">*</span></label>
                    <input type="date" id="proposed_end_date" name="proposed_end_date" class="form-input" required>
                </div>

                <div class="form-group form-group--full">
                    <label class="form-label" for="description">Deskripsi Rencana Renovasi <span class="required">*</span></label>
                    <textarea id="description" name="description" class="form-input form-textarea"
                              placeholder="Jelaskan rencana renovasi unit Anda..." required></textarea>
                </div>

            </div>

            <div class="form-actions">
                <button type="reset" class="btn-secondary">Reset</button>
                <button type="submit" class="btn-primary">Kirim Pengajuan</button>
            </div>
        </form>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-label">Total Pengajuan</span>
            <span class="stat-value"><?= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM `02_tenant_renovations` r JOIN `02_contracts` c ON c.id_contract = r.id_contract WHERE c.id_tenant = $id_tenant"))[0] ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Sedang Diproses</span>
            <span class="stat-value"><?= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM `02_tenant_renovations` r JOIN `02_contracts` c ON c.id_contract = r.id_contract WHERE c.id_tenant = $id_tenant AND r.status IN ('Pending','In Review')"))[0] ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Disetujui</span>
            <span class="stat-value"><?= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM `02_tenant_renovations` r JOIN `02_contracts` c ON c.id_contract = r.id_contract WHERE c.id_tenant = $id_tenant AND r.status = 'Approved'"))[0] ?></span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Riwayat Pengajuan Renovasi</h2>
            <div class="search-wrapper">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari unit..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table" id="riwayatTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Unit</th>
                        <th>Kontrak</th>
                        <th>Periode Renovasi</th>
                        <th>Deskripsi</th>
                        <th>Dokumen</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($riwayatResult) === 0): ?>
                        <tr>
                            <td colspan="7" class="empty-state">Belum ada pengajuan renovasi yang tercatat.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 0; while ($h = mysqli_fetch_assoc($riwayatResult)): $no++; ?>
                            <tr>
                                <td><?= $no ?></td>
                                <td class="td-bold"><?= htmlspecialchars($h['unit_code']) ?></td>
                                <td><?= htmlspecialchars($h['contract_number']) ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($h['proposed_start_date'])) ?>
                                    -
                                    <?= date('d/m/Y', strtotime($h['proposed_end_date'])) ?>
                                </td>
                                <td><?= htmlspecialchars(mb_strimwidth($h['description'], 0, 60, '...')) ?></td>
                                <td>
                                    <?php if (!empty($h['attachment_plan_url'])): ?>
                                        <a href="<?= htmlspecialchars($h['attachment_plan_url']) ?>" target="_blank" class="btn-action btn-action--view">Lihat</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge--<?= strtolower(str_replace(' ', '-', $h['status'])) ?>"><?= $h['status'] ?></span>
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