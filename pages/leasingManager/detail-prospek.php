<?php
session_start();
// require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php";

$page_title  = 'Detail Prospek';
$active_page = 'prospek';
$user_name   = 'Leasing Manager';
$role        = 'leasingManager';

$idProspect = (int)($_GET['id'] ?? 0);
if ($idProspect <= 0) {
    header('Location: prospek_tenant.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT p.*, c.name AS category_name, u.unit_code, u.area_size, u.status AS unit_status
     FROM `02_tenant_prospects` p
     LEFT JOIN `01_tenant_categories` c ON p.id_category = c.id_tenant_categories
     LEFT JOIN `01_units` u ON p.interested_unit = u.id_units
     WHERE p.id_prospect = ?"
);
$stmt->bind_param('i', $idProspect);
$stmt->execute();
$prospek = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prospek) {
    header('Location: prospek_tenant.php');
    exit;
}

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function statusBadgeClass(string $status): string
{
    return match ($status) {
        'Verified' => 'badge--verified',
        'Converted' => 'badge--converted',
        'Rejected' => 'badge--rejected',
        default => 'badge--prospek',
    };
}

function formatTanggalID(?string $date): string
{
    if (!$date) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($date);
    if ($ts === false) return $date;
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function whatsappUrl(?string $phone): string
{
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if ($digits === '') return '#';
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    }
    return 'https://wa.me/' . $digits;
}

$unitLabel = $prospek['unit_code']
    ? $prospek['unit_code'] . ($prospek['area_size'] ? ' (' . rtrim(rtrim((string)$prospek['area_size'], '0'), '.') . ' m2)' : '')
    : 'Belum ditentukan';

require_once "../../includes/navbarM02.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Prospek</title>
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
            gap: 12px;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .card-title {
            font-size: var(--subheading, 20px);
            font-weight: 600;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            padding: 24px;
        }

        .detail-section {
            display: grid;
            gap: 14px;
            align-content: start;
        }

        .section-title {
            font-size: var(--label, 14px);
            color: var(--accent, #00D4D8);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .info-item {
            background: rgba(2,31,66,0.55);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 14px;
            min-width: 0;
        }

        .info-label {
            display: block;
            font-size: var(--caption, 12px);
            color: rgba(245,247,250,0.62);
            margin-bottom: 6px;
        }

        .info-value {
            font-size: var(--label, 14px);
            font-weight: 600;
            color: var(--text, #F5F7FA);
            overflow-wrap: anywhere;
        }

        .notes-box {
            grid-column: 1 / -1;
            background: rgba(2,31,66,0.55);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 18px;
            min-height: 110px;
            line-height: 1.7;
            color: rgba(245,247,250,0.86);
            white-space: pre-wrap;
        }

        .btn-primary,
        .btn-secondary,
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-radius: 8px;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.15s, border-color 0.15s;
        }

        .btn-secondary {
            background: transparent;
            color: var(--text, #F5F7FA);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            font-size: var(--label, 14px);
        }
        .btn-secondary:hover { border-color: var(--accent, #00D4D8); }

        .btn-primary {
            background: var(--accent, #00D4D8);
            color: var(--background, #021F42);
            border: none;
            padding: 10px 20px;
            font-size: var(--label, 14px);
            font-weight: 600;
        }
        .btn-primary:hover,
        .btn-action:hover { opacity: 0.85; }

        .btn-action {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: var(--caption, 12px);
            font-weight: 600;
        }

        .btn-action--edit {
            background: rgba(255,182,42,0.15);
            color: var(--text-accent, #FFB62A);
            border: 1px solid rgba(255,182,42,0.3);
        }

        .action-row {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            padding: 0 24px 24px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: var(--caption, 12px);
            font-weight: 600;
            white-space: nowrap;
        }
        .badge--prospek { background: rgba(255,182,42,0.15); color: var(--text-accent, #FFB62A); border: 1px solid rgba(255,182,42,0.3); }
        .badge--verified { background: rgba(0,212,216,0.15); color: var(--accent, #00D4D8); border: 1px solid rgba(0,212,216,0.3); }
        .badge--converted { background: rgba(34,197,94,0.15); color: var(--success, #22C55E); border: 1px solid rgba(34,197,94,0.3); }
        .badge--rejected { background: rgba(239,68,68,0.15); color: var(--danger, #EF4444); border: 1px solid rgba(239,68,68,0.3); }

        @media (max-width: 768px) {
            .page-wrapper { padding: 16px; }
            .page-header,
            .card-header { flex-direction: column; align-items: flex-start; }
            .detail-grid,
            .info-grid { grid-template-columns: 1fr; }
            .action-row { justify-content: stretch; }
            .action-row a { width: 100%; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="page-header">
        <a href="prospek_tenant.php" class="btn-secondary">Kembali</a>
        <div>
            <p class="page-breadcrumb">Tenant &amp; Leasing / Detail Prospek</p>
            <h1 class="page-title">Detail Prospek: <?= e($prospek['brand_name']) ?></h1>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Profil Prospek Tenant</h2>
            <span class="badge <?= statusBadgeClass($prospek['status']) ?>"><?= e($prospek['status']) ?></span>
        </div>

        <div class="detail-grid">
            <div class="detail-section">
                <h3 class="section-title">Profil Bisnis &amp; Unit</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Kategori Bisnis</span>
                        <span class="info-value"><?= e($prospek['category_name'] ?: '-') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Unit yang Diminati</span>
                        <span class="info-value"><?= e($unitLabel) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tanggal Daftar</span>
                        <span class="info-value"><?= e(formatTanggalID($prospek['register_date'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status Saat Ini</span>
                        <span class="badge <?= statusBadgeClass($prospek['status']) ?>"><?= e($prospek['status']) ?></span>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h3 class="section-title">Informasi Kontak</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Nama PIC</span>
                        <span class="info-value"><?= e($prospek['pic_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nomor Kontak</span>
                        <span class="info-value"><?= e($prospek['phone']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= e($prospek['email'] ?: '-') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">WhatsApp</span>
                        <a class="info-value" style="color: var(--accent, #00D4D8);" href="<?= e(whatsappUrl($prospek['phone'])) ?>" target="_blank" rel="noopener">
                            Hubungi via WhatsApp
                        </a>
                    </div>
                </div>
            </div>

            <div class="detail-section" style="grid-column: 1 / -1;">
                <h3 class="section-title">Catatan Tambahan</h3>
                <div class="notes-box"><?= e($prospek['notes'] ?: 'Belum ada catatan tambahan untuk prospek ini.') ?></div>
            </div>
        </div>

        <div class="action-row">
            <a href="edit-prospek.php?id=<?= (int)$prospek['id_prospect'] ?>" class="btn-action btn-action--edit">Edit Data Prospek</a>
        </div>
    </div>
</div>
</body>
</html>
