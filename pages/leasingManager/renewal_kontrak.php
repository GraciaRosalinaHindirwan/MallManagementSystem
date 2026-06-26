<?php
require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$page_title  = 'Renewal Kontrak';
$active_page = 'renewal';
$user_name   = $_SESSION['username'];   
$role        = $_SESSION['user_role'];     

function setFlash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_renewal') {
    $idContractLama = (int)($_POST['id_contract'] ?? 0);
    $newStart       = trim($_POST['new_start_date'] ?? '');
    $newEnd         = trim($_POST['new_end_date'] ?? '');

    $chargeTypes = $_POST['charge_type'] ?? [];
    $calcBasis   = $_POST['calculation_basis'] ?? [];
    $amounts     = $_POST['amount_or_percentage'] ?? [];
    $cycles      = $_POST['billing_cycle'] ?? [];

    try {
        if ($idContractLama <= 0 || $newStart === '' || $newEnd === '') {
            throw new Exception('Data tidak lengkap. Kontrak, tanggal mulai, dan tanggal akhir kontrak baru wajib diisi.');
        }
        if (strtotime($newStart) === false || strtotime($newEnd) === false) {
            throw new Exception('Format tanggal tidak valid.');
        }
        if (strtotime($newEnd) <= strtotime($newStart)) {
            throw new Exception('Tanggal akhir kontrak baru harus setelah tanggal mulai.');
        }

        $stmt = $conn->prepare("SELECT * FROM `02_contracts` WHERE id_contract = ?");
        $stmt->bind_param('i', $idContractLama);
        $stmt->execute();
        $oldContract = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$oldContract) {
            throw new Exception('Kontrak yang akan di-renewal tidak ditemukan.');
        }
        if (!in_array($oldContract['contract_status'], ['Active', 'Amended', 'Expired'], true)) {
            throw new Exception('Status kontrak ini ("' . $oldContract['contract_status'] . '") tidak memenuhi syarat untuk diproses renewal.');
        }

        $conn->begin_transaction();

        $tempNumber = 'TEMP';
        $idTenant   = (int)$oldContract['id_tenant'];
        $idUnit     = (int)$oldContract['id_unit'];
        $stmtInsert = $conn->prepare(
            "INSERT INTO `02_contracts`
                (contract_number, id_tenant, id_unit, start_date, end_date, contract_status, legal_document_url)
             VALUES (?, ?, ?, ?, ?, 'Active', NULL)"
        );
        $stmtInsert->bind_param('siiss', $tempNumber, $idTenant, $idUnit, $newStart, $newEnd);
        $stmtInsert->execute();
        $newId = (int)$conn->insert_id;
        $stmtInsert->close();

        $newContractNumber = 'CONT-' . date('Y') . '-' . str_pad((string)$newId, 3, '0', STR_PAD_LEFT);
        $stmtUpdateNumber = $conn->prepare("UPDATE `02_contracts` SET contract_number = ? WHERE id_contract = ?");
        $stmtUpdateNumber->bind_param('si', $newContractNumber, $newId);
        $stmtUpdateNumber->execute();
        $stmtUpdateNumber->close();

        $stmtCost = $conn->prepare(
            "INSERT INTO `02_contract_cost`
                (id_contract, charge_type, calculation_basis, amount_or_percentage, billing_cycle)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($chargeTypes as $i => $type) {
            $type   = trim((string)$type);
            $basis  = (string)($calcBasis[$i] ?? 'Fixed Monthly');
            $amount = (float)($amounts[$i] ?? 0);
            $cycle  = (string)($cycles[$i] ?? 'Monthly');

            if ($type === '') {
                continue;
            }
            $stmtCost->bind_param('issds', $newId, $type, $basis, $amount, $cycle);
            $stmtCost->execute();
        }
        $stmtCost->close();

        $stmtExpire = $conn->prepare("UPDATE `02_contracts` SET contract_status = 'Expired' WHERE id_contract = ?");
        $stmtExpire->bind_param('i', $idContractLama);
        $stmtExpire->execute();
        $stmtExpire->close();

        $stmtTenant = $conn->prepare("UPDATE `02_tenants` SET status = 'Active' WHERE id_tenant = ? AND status <> 'Active'");
        $stmtTenant->bind_param('i', $idTenant);
        $stmtTenant->execute();
        $stmtTenant->close();

        $conn->commit();

        setFlash(
            'success',
            "Renewal berhasil diproses. Kontrak baru {$newContractNumber} kini Active, menggantikan kontrak {$oldContract['contract_number']}."
        );
    } catch (Throwable $e) {
        if ($conn instanceof mysqli) {
            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }
        }
        setFlash('error', 'Gagal memproses renewal: ' . $e->getMessage());
    }

    header('Location: renewal_kontrak.php');
    exit;
}

$sql = "
    SELECT
        c.id_contract, c.contract_number, c.start_date, c.end_date, c.contract_status,
        t.id_tenant, t.tenant_name, t.brand_name, t.status AS tenant_status,
        u.id_units, u.unit_code, u.area_size,
        DATEDIFF(c.end_date, CURDATE()) AS days_to_expiry
    FROM `02_contracts` c
    JOIN `02_tenants` t ON c.id_tenant = t.id_tenant
    JOIN `01_units`   u ON c.id_unit   = u.id_units
    WHERE c.contract_status IN ('Active','Amended','Expired')
      AND NOT EXISTS (
            SELECT 1 FROM `02_contracts` c2
            WHERE c2.id_tenant = c.id_tenant
              AND c2.id_unit   = c.id_unit
              AND c2.start_date > c.start_date
      )
    ORDER BY c.end_date ASC
";
$contractsResult = mysqli_query($conn, $sql);
$contracts = mysqli_fetch_all($contractsResult, MYSQLI_ASSOC);

$costByContract = [];
if (!empty($contracts)) {
    $ids = array_map('intval', array_column($contracts, 'id_contract'));
    $idList = implode(',', $ids);
    $costResult = mysqli_query($conn, "SELECT * FROM `02_contract_cost` WHERE id_contract IN ($idList)");
    while ($row = mysqli_fetch_assoc($costResult)) {
        $costByContract[$row['id_contract']][] = $row;
    }
}

function urgencyInfo(int $days): array
{
    if ($days < 0) {
        return ['key' => 'expired', 'label' => 'Sudah Berakhir', 'class' => 'badge-danger'];
    }
    if ($days <= 90) {
        return ['key' => 'soon', 'label' => 'Segera Berakhir (<=3 bln)', 'class' => 'badge-warning'];
    }
    if ($days <= 180) {
        return ['key' => 'near', 'label' => 'Mendekati Akhir (3-6 bln)', 'class' => 'badge-caution'];
    }
    return ['key' => 'safe', 'label' => 'Aman', 'class' => 'badge-safe'];
}

function formatRupiah($n): string
{
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function formatTanggalID(string $isoDate): string
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($isoDate);
    if ($ts === false) return $isoDate;
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

$statSegera = 0;
$statHabis  = 0;
$statTotal  = count($contracts);

foreach ($contracts as $c) {
    $u = urgencyInfo((int)$c['days_to_expiry']);
    if ($u['key'] === 'expired') $statHabis++;
    elseif (in_array($u['key'], ['soon', 'near'], true)) $statSegera++;
}

$jsData = [];
foreach ($contracts as $c) {
    $defaultStart = date('Y-m-d', strtotime($c['end_date'] . ' +1 day'));
    $defaultEnd = date('Y-m-d', strtotime($defaultStart . ' +2 years -1 day'));

    $costs = [];
    foreach ($costByContract[$c['id_contract']] ?? [] as $cost) {
        $costs[] = [
            'charge_type'           => $cost['charge_type'],
            'calculation_basis'     => $cost['calculation_basis'],
            'amount_or_percentage'  => $cost['amount_or_percentage'],
            'billing_cycle'         => $cost['billing_cycle'],
        ];
    }

    $jsData[$c['id_contract']] = [
        'contract_number' => $c['contract_number'],
        'tenant_name'     => $c['tenant_name'],
        'brand_name'      => $c['brand_name'],
        'unit_code'       => $c['unit_code'],
        'area_size'       => $c['area_size'],
        'start_date'      => $c['start_date'],
        'end_date'        => $c['end_date'],
        'default_start'   => $defaultStart,
        'default_end'     => $defaultEnd,
        'costs'           => $costs,
    ];
}

$flash = getFlash();

require_once "../../includes/navbarM02.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renewal Kontrak</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html,
        body {
            width: 100%;
            min-height: 100%;
            overflow-x: hidden;
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
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
            min-width: 0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            min-width: 0;
        }

        .page-breadcrumb {
            font-size: var(--caption, 12px);
            color: var(--accent, #00D4D8);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .page-title {
            font-size: clamp(24px, 4vw, var(--h1, 32px));
            font-weight: 700;
            color: var(--text, #F5F7FA);
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .page-subtitle {
            color: rgba(245,247,250,0.68);
            font-size: var(--label, 14px);
            margin-top: 6px;
            max-width: 760px;
            line-height: 1.6;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
            min-width: 0;
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
            min-width: 0;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            min-width: 0;
        }

        .card-title {
            font-size: var(--subheading, 20px);
            font-weight: 600;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            min-width: 0;
        }

        .search-input,
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
            max-width: 100%;
            min-width: 0;
        }

        .search-input { width: min(300px, 100%); }
        .search-input:focus,
        .form-input:focus { border-color: var(--accent, #00D4D8); }
        .form-input option { background: #0B376D; }

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
            transition: opacity 0.15s, border-color 0.15s, background 0.15s;
        }

        .btn-primary {
            background: var(--accent, #00D4D8);
            color: var(--background, #021F42);
            border: none;
            padding: 10px 20px;
            font-size: var(--label, 14px);
            font-weight: 600;
        }
        .btn-primary:hover { opacity: 0.85; }

        .btn-secondary {
            background: transparent;
            color: var(--text, #F5F7FA);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            font-size: var(--label, 14px);
        }
        .btn-secondary:hover { border-color: var(--accent, #00D4D8); }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: var(--caption, 12px);
            font-weight: 600;
            border: 1px solid rgba(0,212,216,0.3);
            background: rgba(0,212,216,0.15);
            color: var(--accent, #00D4D8);
        }
        .btn-action:hover { opacity: 0.8; }

        .table-wrapper {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--label, 14px);
            table-layout: fixed;
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
            white-space: normal;
        }

        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .data-table tbody tr:hover { background: rgba(0,212,216,0.04); }
        .data-table tbody tr.row-expired { background: rgba(239,68,68,0.06); }
        .data-table tbody tr.row-soon { background: rgba(255,182,42,0.05); }

        .td-bold { font-weight: 600; }
        .td-muted { color: rgba(245,247,250,0.58); font-size: var(--caption, 12px); }

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
            white-space: normal;
        }
        .badge-danger,
        .badge-status-expired { background: rgba(239,68,68,0.15); color: var(--danger, #EF4444); border: 1px solid rgba(239,68,68,0.3); }
        .badge-warning { background: rgba(255,182,42,0.15); color: var(--text-accent, #FFB62A); border: 1px solid rgba(255,182,42,0.3); }
        .badge-caution { background: rgba(255,213,128,0.14); color: #FFD580; border: 1px solid rgba(255,213,128,0.3); }
        .badge-safe { background: rgba(34,197,94,0.15); color: var(--success, #22C55E); border: 1px solid rgba(34,197,94,0.3); }
        .badge-status-active { background: rgba(0,212,216,0.15); color: var(--accent, #00D4D8); border: 1px solid rgba(0,212,216,0.3); }
        .badge-status-amended { background: rgba(22,126,128,0.25); color: var(--secondary, #5EEAD4); border: 1px solid rgba(22,126,128,0.35); }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: var(--label, 14px);
            border: 1px solid transparent;
        }
        .alert-success { background: rgba(34,197,94,0.12); color: var(--success, #22C55E); border-color: rgba(34,197,94,0.3); }
        .alert-error { background: rgba(239,68,68,0.12); color: var(--danger, #EF4444); border-color: rgba(239,68,68,0.3); }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(2,31,66,0.78);
            z-index: 500;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.open { display: flex; }

        .modal {
            background: var(--primary, #0B376D);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            width: 100%;
            max-width: 720px;
            max-height: calc(100dvh - 40px);
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.45);
            min-width: 0;
        }

        .modal-header,
        .modal-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .modal-actions { justify-content: flex-end; border-top: 1px solid rgba(255,255,255,0.08); border-bottom: none; }
        .modal-title { font-size: var(--subheading, 20px); font-weight: 600; }
        .modal-subtitle { color: rgba(245,247,250,0.6); font-size: var(--caption, 12px); margin-top: 4px; }
        .modal-body { padding: 24px; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 18px;
            background: rgba(2,31,66,0.42);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 18px;
        }
        .info-label { display: block; color: rgba(245,247,250,0.62); font-size: var(--caption, 12px); margin-bottom: 4px; }
        .info-value { font-weight: 600; color: var(--text, #F5F7FA); overflow-wrap: anywhere; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 14px;
        }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-label { font-size: var(--label, 14px); font-weight: 500; color: rgba(245,247,250,0.8); }
        .duration-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }

        #continuityNote { font-size: var(--caption, 12px); padding: 8px 10px; border-radius: 8px; margin-bottom: 14px; display: none; }
        .continuity-ok { display: block !important; background: rgba(34,197,94,0.12); color: var(--success, #22C55E); }
        .continuity-warn { display: block !important; background: rgba(239,68,68,0.12); color: var(--danger, #EF4444); }

        .cost-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: var(--caption, 12px); }
        .cost-table th,
        .cost-table td { padding: 8px; border-bottom: 1px solid rgba(255,255,255,0.08); text-align: left; }
        .cost-table th { color: var(--accent, #00D4D8); font-weight: 600; }
        .cost-table input[type="number"] { width: 100%; min-width: 0; }

        @media (max-width: 900px) {
            .page-wrapper { padding: 20px; }
        }

        @media (max-width: 768px) {
            .page-wrapper { padding: 16px; }
            .page-header,
            .card-header { flex-direction: column; align-items: flex-start; }
            .search-input { width: 100%; }
            .toolbar { width: 100%; }
            .toolbar .form-input { width: 100%; }
            .modal-actions { flex-direction: column-reverse; align-items: stretch; }
        }

        @media (max-width: 640px) {
            .page-wrapper { padding: 12px; gap: 16px; }
            .card-header,
            .modal-header,
            .modal-body,
            .modal-actions { padding: 16px; }
            .stat-card { padding: 16px; }
            .modal-overlay { padding: 10px; align-items: flex-start; overflow-y: auto; }
            .modal { max-height: none; margin: 10px 0; }
            .info-grid { grid-template-columns: 1fr; }
            .duration-actions .btn-secondary,
            .modal-actions .btn-secondary,
            .modal-actions .btn-primary { width: 100%; }

            .data-table,
            .data-table thead,
            .data-table tbody,
            .data-table th,
            .data-table td,
            .data-table tr { display: block; width: 100%; }

            .data-table thead { display: none; }

            .data-table tr {
                padding: 12px 14px;
                border-bottom: 1px solid rgba(255,255,255,0.08);
            }

            .data-table td {
                display: grid;
                grid-template-columns: minmax(112px, 40%) minmax(0, 1fr);
                gap: 10px;
                padding: 8px 0;
                border-bottom: none;
                white-space: normal;
            }

            .data-table td::before {
                content: attr(data-label);
                color: var(--accent, #00D4D8);
                font-size: var(--caption, 12px);
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .cost-table {
                table-layout: fixed;
            }

            .cost-table,
            .cost-table thead,
            .cost-table tbody,
            .cost-table th,
            .cost-table td,
            .cost-table tr { display: block; width: 100%; }

            .cost-table thead { display: none; }

            .cost-table tr {
                padding: 10px 0;
                border-bottom: 1px solid rgba(255,255,255,0.08);
            }

            .cost-table td {
                display: grid;
                grid-template-columns: minmax(104px, 38%) minmax(0, 1fr);
                gap: 10px;
                padding: 7px 0;
                border-bottom: none;
                overflow-wrap: anywhere;
            }

            .cost-table td::before {
                content: attr(data-label);
                color: var(--accent, #00D4D8);
                font-size: var(--caption, 12px);
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="page-header">
        <div>
            <p class="page-breadcrumb">Tenant &amp; Leasing / Tenant Lifecycle</p>
            <h1 class="page-title">Renewal Kontrak Sewa</h1>
            <p class="page-subtitle">
                Daftar kontrak tenant aktif yang mendekati atau telah melewati tanggal berakhir.
            </p>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="stats-row">
        <div class="stat-card">
            <span class="stat-label">Total Kontrak</span>
            <span class="stat-value"><?= $statTotal ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Segera Berakhir</span>
            <span class="stat-value"><?= $statSegera ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Sudah Berakhir</span>
            <span class="stat-value"><?= $statHabis ?></span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Daftar Kontrak Renewal</h2>
            <div class="toolbar">
                <input type="text" id="searchInput" class="search-input" placeholder="Cari kontrak, tenant, atau unit...">
                <select id="urgencyFilter" class="form-input">
                    <option value="all">Semua Status</option>
                    <option value="expired">Sudah Berakhir</option>
                    <option value="soon">Segera Berakhir (&lt;=90 hari)</option>
                    <option value="near">Mendekati Akhir (91-180 hari)</option>
                    <option value="safe">Aman</option>
                </select>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table" id="renewalTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No. Kontrak</th>
                        <th>Tenant</th>
                        <th>Unit</th>
                        <th>Periode Sewa</th>
                        <th>Sisa Waktu</th>
                        <th>Status Kontrak</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="contractTableBody">
                    <?php if (empty($contracts)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">Tidak ada kontrak yang memerlukan proses renewal saat ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $i => $c):
                            $days = (int)$c['days_to_expiry'];
                            $u = urgencyInfo($days);
                            $rowClass = $u['key'] === 'expired' ? 'row-expired' : ($u['key'] === 'soon' ? 'row-soon' : '');
                            $searchKey = strtolower($c['contract_number'] . ' ' . $c['tenant_name'] . ' ' . $c['brand_name'] . ' ' . $c['unit_code']);
                            $statusClass = $c['contract_status'] === 'Expired' ? 'badge-status-expired' : ($c['contract_status'] === 'Amended' ? 'badge-status-amended' : 'badge-status-active');
                            $sisaLabel = $days >= 0 ? "{$days} hari lagi" : (abs($days) . ' hari lewat');
                        ?>
                            <tr data-row data-urgency="<?= $u['key'] ?>" data-search="<?= htmlspecialchars($searchKey) ?>" class="<?= $rowClass ?>">
                                <td data-label="No"><?= $i + 1 ?></td>
                                <td data-label="No. Kontrak" class="td-bold"><?= htmlspecialchars($c['contract_number']) ?></td>
                                <td data-label="Tenant">
                                    <span class="td-bold"><?= htmlspecialchars($c['brand_name']) ?></span><br>
                                    <span class="td-muted"><?= htmlspecialchars($c['tenant_name']) ?></span>
                                </td>
                                <td data-label="Unit"><?= htmlspecialchars($c['unit_code']) ?> <span class="td-muted">(<?= htmlspecialchars((string)$c['area_size']) ?> m2)</span></td>
                                <td data-label="Periode Sewa"><?= formatTanggalID($c['start_date']) ?> - <?= formatTanggalID($c['end_date']) ?></td>
                                <td data-label="Sisa Waktu"><span class="badge <?= $u['class'] ?>"><?= $sisaLabel ?></span></td>
                                <td data-label="Status Kontrak"><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($c['contract_status']) ?></span></td>
                                <td data-label="Aksi">
                                    <button type="button" class="btn-action" onclick="openRenewalModal(<?= (int)$c['id_contract'] ?>)">
                                        Proses Renewal
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="renewalModalOverlay">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Proses Renewal Kontrak</h2>
                <p class="modal-subtitle">Lengkapi periode sewa baru dan sesuaikan komponen biaya bila diperlukan.</p>
            </div>
        </div>

        <form method="POST" action="renewal_kontrak.php" id="renewalForm" onsubmit="return validateRenewalForm();">
            <div class="modal-body">
                <input type="hidden" name="action" value="process_renewal">
                <input type="hidden" name="id_contract" id="inputIdContract">
                <input type="hidden" id="modalOldEndDateHidden">

                <div class="info-grid">
                    <div><span class="info-label">No. Kontrak Lama</span><span class="info-value" id="modalOldContractNumber">-</span></div>
                    <div><span class="info-label">Tenant</span><span class="info-value" id="modalTenantName">-</span></div>
                    <div><span class="info-label">Unit</span><span class="info-value" id="modalUnitCode">-</span></div>
                    <div><span class="info-label">Periode Lama</span><span class="info-value" id="modalOldPeriode">-</span></div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="new_start_date">Tanggal Mulai Kontrak Baru</label>
                        <input type="date" name="new_start_date" id="new_start_date" class="form-input" required onchange="checkContinuity()">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_end_date">Tanggal Akhir Kontrak Baru</label>
                        <input type="date" name="new_end_date" id="new_end_date" class="form-input" required onchange="checkContinuity()">
                    </div>
                </div>

                <div class="duration-actions">
                    <button type="button" class="btn-secondary" onclick="setDurasi(1)">+1 Tahun</button>
                    <button type="button" class="btn-secondary" onclick="setDurasi(2)">+2 Tahun</button>
                    <button type="button" class="btn-secondary" onclick="setDurasi(3)">+3 Tahun</button>
                </div>

                <div id="continuityNote"></div>

                <label class="form-label">Komponen Biaya (nominal dapat disesuaikan)</label>
                <div class="table-wrapper">
                    <table class="cost-table">
                        <thead>
                            <tr><th>Jenis Biaya</th><th>Basis</th><th>Siklus</th><th>Nominal Baru</th></tr>
                        </thead>
                        <tbody id="costRowsBody">
                            <tr><td colspan="4" class="empty-state">Pilih kontrak terlebih dahulu.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeRenewalModal()">Batal</button>
                <button type="submit" class="btn-primary">Simpan &amp; Aktifkan Kontrak Baru</button>
            </div>
        </form>
    </div>
</div>

<script>
    const RENEWAL_DATA = <?= json_encode($jsData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[char];
        });
    }

    function formatTglJS(iso) {
        if (!iso) return '-';
        const d = new Date(iso + 'T00:00:00');
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function openRenewalModal(id) {
        const d = RENEWAL_DATA[id];
        if (!d) { alert('Data kontrak tidak ditemukan.'); return; }

        document.getElementById('inputIdContract').value = id;
        document.getElementById('modalOldContractNumber').textContent = d.contract_number;
        document.getElementById('modalTenantName').textContent = d.brand_name + ' (' + d.tenant_name + ')';
        document.getElementById('modalUnitCode').textContent = d.unit_code + ' - ' + d.area_size + ' m2';
        document.getElementById('modalOldPeriode').textContent = formatTglJS(d.start_date) + ' s/d ' + formatTglJS(d.end_date);
        document.getElementById('modalOldEndDateHidden').value = d.end_date;

        document.getElementById('new_start_date').value = d.default_start;
        document.getElementById('new_end_date').value = d.default_end;

        const tbody = document.getElementById('costRowsBody');
        tbody.innerHTML = '';
        if (!d.costs || d.costs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="empty-state">Tidak ada komponen biaya tercatat pada kontrak lama.</td></tr>';
        } else {
            d.costs.forEach(function (c) {
                const suffix = c.calculation_basis === 'Percentage' ? '%' : 'Rp';
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td data-label="Jenis Biaya">' + escapeHtml(c.charge_type) + '</td>' +
                    '<td data-label="Basis">' + escapeHtml(c.calculation_basis) + '</td>' +
                    '<td data-label="Siklus">' + escapeHtml(c.billing_cycle) + '</td>' +
                    '<td data-label="Nominal Baru">' +
                        '<input type="hidden" name="charge_type[]" value="' + escapeHtml(c.charge_type) + '">' +
                        '<input type="hidden" name="calculation_basis[]" value="' + escapeHtml(c.calculation_basis) + '">' +
                        '<input type="hidden" name="billing_cycle[]" value="' + escapeHtml(c.billing_cycle) + '">' +
                        '<input type="number" step="0.01" min="0" name="amount_or_percentage[]" class="form-input" value="' + escapeHtml(c.amount_or_percentage) + '" required> <span class="td-muted">' + suffix + '</span>' +
                    '</td>';
                tbody.appendChild(tr);
            });
        }

        checkContinuity();
        document.getElementById('renewalModalOverlay').classList.add('open');
    }

    function closeRenewalModal() {
        document.getElementById('renewalModalOverlay').classList.remove('open');
    }

    function setDurasi(tahun) {
        const startVal = document.getElementById('new_start_date').value;
        if (!startVal) { alert('Isi tanggal mulai kontrak baru terlebih dahulu.'); return; }
        const d = new Date(startVal + 'T00:00:00');
        d.setFullYear(d.getFullYear() + tahun);
        d.setDate(d.getDate() - 1);
        document.getElementById('new_end_date').value = d.toISOString().slice(0, 10);
        checkContinuity();
    }

    function checkContinuity() {
        const oldEnd   = document.getElementById('modalOldEndDateHidden').value;
        const newStart = document.getElementById('new_start_date').value;
        const note     = document.getElementById('continuityNote');
        if (!oldEnd || !newStart) { note.style.display = 'none'; return; }

        const oldEndDate   = new Date(oldEnd + 'T00:00:00');
        const newStartDate = new Date(newStart + 'T00:00:00');
        const diffDays = Math.round((newStartDate - oldEndDate) / 86400000);

        if (diffDays === 1) {
            note.textContent = 'OK: Tidak ada celah waktu, kontinuitas sewa terjaga.';
            note.className = 'continuity-ok';
        } else if (diffDays > 1) {
            note.textContent = 'Peringatan: Ada celah ' + (diffDays - 1) + ' hari antara akhir kontrak lama dan mulai kontrak baru.';
            note.className = 'continuity-warn';
        } else {
            note.textContent = 'Peringatan: Tanggal mulai kontrak baru tumpang tindih ' + (1 - diffDays) + ' hari dengan kontrak lama.';
            note.className = 'continuity-warn';
        }
    }

    function validateRenewalForm() {
        const start = document.getElementById('new_start_date').value;
        const end   = document.getElementById('new_end_date').value;
        if (!start || !end) {
            alert('Tanggal mulai dan akhir kontrak baru wajib diisi.');
            return false;
        }
        if (new Date(end) <= new Date(start)) {
            alert('Tanggal akhir kontrak baru harus setelah tanggal mulai.');
            return false;
        }
        return true;
    }

    function filterTable() {
        const q = document.getElementById('searchInput').value.toLowerCase();
        const urgency = document.getElementById('urgencyFilter').value;
        document.querySelectorAll('#contractTableBody tr[data-row]').forEach(function (tr) {
            const text = tr.dataset.search || '';
            const u = tr.dataset.urgency || '';
            const matchText = text.indexOf(q) !== -1;
            const matchUrgency = (urgency === 'all') || (urgency === u);
            tr.style.display = (matchText && matchUrgency) ? '' : 'none';
        });
    }

    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('urgencyFilter').addEventListener('change', filterTable);
    document.getElementById('renewalModalOverlay').addEventListener('click', function (event) {
        if (event.target === this) closeRenewalModal();
    });
</script>
</body>
</html>
