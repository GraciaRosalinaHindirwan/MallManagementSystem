<?php
require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php";

$page_title  = 'Edit Prospek';
$active_page = 'prospek';
$user_name   = $_SESSION['username'];   
$role        = $_SESSION['user_role'];     

$idProspect = (int)($_GET['id'] ?? $_POST['id_prospect'] ?? 0);
if ($idProspect <= 0) {
    header('Location: prospek_tenant.php');
    exit;
}

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusOptions = [
    'Prospect' => 'Prospect',
    'Verified' => 'Verified',
    'Rejected' => 'Rejected',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brandName      = trim($_POST['nama_toko'] ?? '');
    $idCategory     = (int)($_POST['kategori'] ?? 0);
    $picName        = trim($_POST['nama_pic'] ?? '');
    $phone          = trim($_POST['kontak'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $interestedUnit = ($_POST['unit_diminati'] ?? '') === '' ? null : (int)$_POST['unit_diminati'];
    $notes          = trim($_POST['catatan'] ?? '');
    $status         = $_POST['status'] ?? 'Prospect';

    if (!array_key_exists($status, $statusOptions)) {
        $status = 'Prospect';
    }

    if ($brandName === '' || $idCategory <= 0 || $picName === '' || $phone === '') {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Nama toko, kategori, nama PIC, dan nomor kontak wajib diisi.'];
        header('Location: edit-prospek.php?id=' . $idProspect);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE `02_tenant_prospects`
         SET brand_name = ?, id_category = ?, pic_name = ?, phone = ?, email = ?, interested_unit = ?, notes = ?, status = ?
         WHERE id_prospect = ?"
    );
    $stmt->bind_param(
        'sisssissi',
        $brandName,
        $idCategory,
        $picName,
        $phone,
        $email,
        $interestedUnit,
        $notes,
        $status,
        $idProspect
    );

    if ($stmt->execute()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Data prospek berhasil diperbarui.'];
        $stmt->close();
        header('Location: detail-prospek.php?id=' . $idProspect);
        exit;
    }

    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal memperbarui data prospek: ' . $conn->error];
    $stmt->close();
    header('Location: edit-prospek.php?id=' . $idProspect);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM `02_tenant_prospects` WHERE id_prospect = ?");
$stmt->bind_param('i', $idProspect);
$stmt->execute();
$prospek = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prospek) {
    header('Location: prospek_tenant.php');
    exit;
}

$kategoriOptions = mysqli_query($conn, "SELECT id_tenant_categories, name FROM `01_tenant_categories` ORDER BY name ASC");

$unitStmt = $conn->prepare(
    "SELECT id_units, unit_code
     FROM `01_units`
     WHERE status = 'available'
     ORDER BY unit_code ASC"
);

$unitStmt->execute();
$unitOptions = $unitStmt->get_result();
$unitStmt->execute();
$unitOptions = $unitStmt->get_result();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

require_once "../../includes/navbarM02.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prospek</title>
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
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
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
            overflow-wrap: anywhere;
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

        .form-card { padding-bottom: 8px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 24px;
            padding: 24px;
        }

        .form-group { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
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
            width: 100%;
        }
        .form-input:focus { border-color: var(--accent, #00D4D8); }
        .form-input option { background: #0B376D; }

        .form-textarea {
            resize: vertical;
            min-height: 110px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 12px;
            padding: 0 24px 24px;
        }

        .btn-primary,
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 8px;
            padding: 10px 20px;
            font-family: inherit;
            font-size: var(--label, 14px);
            cursor: pointer;
            transition: opacity 0.15s, border-color 0.15s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--accent, #00D4D8);
            color: var(--background, #021F42);
            border: none;
            font-weight: 600;
        }
        .btn-primary:hover { opacity: 0.85; }

        .btn-secondary {
            background: transparent;
            color: var(--text, #F5F7FA);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .btn-secondary:hover { border-color: var(--accent, #00D4D8); }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: var(--label, 14px);
            border: 1px solid transparent;
        }
        .alert-success { background: rgba(34,197,94,0.12); color: var(--success, #22C55E); border-color: rgba(34,197,94,0.3); }
        .alert-error { background: rgba(239,68,68,0.12); color: var(--danger, #EF4444); border-color: rgba(239,68,68,0.3); }

        .hint {
            color: rgba(245,247,250,0.5);
            font-size: var(--caption, 12px);
        }

        @media (max-width: 768px) {
            .page-wrapper { padding: 16px; }
            .page-header,
            .card-header { flex-direction: column; align-items: flex-start; gap: 12px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-actions { flex-direction: column-reverse; align-items: stretch; }
            .form-actions a,
            .form-actions button { width: 100%; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="page-header">
        <div>
            <p class="page-breadcrumb">Tenant &amp; Leasing / Edit Prospek</p>
            <h1 class="page-title">Edit Prospek: <?= e($prospek['brand_name']) ?></h1>
        </div>
        <a href="detail-prospek.php?id=<?= (int)$prospek['id_prospect'] ?>" class="btn-secondary">Lihat Detail</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
            <?= e($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="card form-card">
        <div class="card-header">
            <h2 class="card-title">Form Edit Prospek</h2>
        </div>

        <form method="POST" action="edit-prospek.php?id=<?= (int)$prospek['id_prospect'] ?>">
            <input type="hidden" name="id_prospect" value="<?= (int)$prospek['id_prospect'] ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="nama_toko">Nama Toko / Tenant <span class="required">*</span></label>
                    <input type="text" id="nama_toko" name="nama_toko" class="form-input" value="<?= e($prospek['brand_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="kategori">Kategori Bisnis <span class="required">*</span></label>
                    <select id="kategori" name="kategori" class="form-input" required>
                        <option value="" disabled>Pilih kategori...</option>
                        <?php while ($kat = mysqli_fetch_assoc($kategoriOptions)): ?>
                            <option value="<?= (int)$kat['id_tenant_categories'] ?>" <?= (int)$prospek['id_category'] === (int)$kat['id_tenant_categories'] ? 'selected' : '' ?>>
                                <?= e($kat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nama_pic">Nama PIC <span class="required">*</span></label>
                    <input type="text" id="nama_pic" name="nama_pic" class="form-input" value="<?= e($prospek['pic_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="kontak">Nomor Kontak <span class="required">*</span></label>
                    <input type="text" id="kontak" name="kontak" class="form-input" value="<?= e($prospek['phone']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" value="<?= e($prospek['email']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="unit_diminati">Unit yang Diminati</label>
                    <select id="unit_diminati" name="unit_diminati" class="form-input">
                        <option value="">Belum ditentukan</option>
                        <?php while ($unit = mysqli_fetch_assoc($unitOptions)): ?>
                            <option value="<?= (int)$unit['id_units'] ?>" <?= (int)$prospek['interested_unit'] === (int)$unit['id_units'] ? 'selected' : '' ?>>
                                <?= e($unit['unit_code']) ?>
                            </option>
                        <?php endwhile; ?>                    
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status Pipeline <span class="required">*</span></label>
                    <select id="status" name="status" class="form-input" required>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $prospek['status'] === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group form-group--full">
                    <label class="form-label" for="catatan">Catatan</label>
                    <textarea id="catatan" name="catatan" class="form-input form-textarea"><?= e($prospek['notes']) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="prospek_tenant.php" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
