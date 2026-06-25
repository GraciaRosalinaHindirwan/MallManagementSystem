<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/koneksi.php';

$tenantId = isset($_GET['tenant_id']) ? (int) $_GET['tenant_id'] : 1;
$message = $_GET['msg'] ?? '';
$messageType = $_GET['type'] ?? '';

if (!isset($_SESSION['processed_submissions'])) {
    $_SESSION['processed_submissions'] = [];
}

function rupiah($value)
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function redirectToPage($page, $tenantId, $message = '', $type = 'success')
{
    $url = $page . '?tenant_id=' . (int) $tenantId;

    if ($message !== '') {
        $url .= '&msg=' . urlencode($message) . '&type=' . urlencode($type);
    }

    header('Location: ' . $url, true, 303);
    exit;
}

function checkSubmission($submissionId, $tenantId, $page)
{
    if ($submissionId === '') {
        redirectToPage($page, $tenantId, 'Submit tidak valid. Data tidak diproses.', 'error');
    }

    if (isset($_SESSION['processed_submissions'][$submissionId])) {
        redirectToPage($page, $tenantId, 'Submit ulang dicegah. Data tidak ditambahkan lagi.', 'error');
    }

    $_SESSION['processed_submissions'][$submissionId] = time();

    if (count($_SESSION['processed_submissions']) > 30) {
        $_SESSION['processed_submissions'] = array_slice($_SESSION['processed_submissions'], -30, null, true);
    }
}

function getTenantData(PDO $pdo, &$tenantId)
{
    $stmt = $pdo->prepare("
        SELECT
            t.id_tenant,
            t.tenant_name,
            t.brand_name,
            t.status,
            tc.name AS category_name,
            c.id_contract,
            c.contract_number,
            c.start_date,
            c.end_date,
            c.legal_document_url,
            u.unit_code
        FROM `02_tenants` t
        LEFT JOIN `01_tenant_categories` tc ON t.id_category = tc.id_tenant_categories
        LEFT JOIN `02_contracts` c ON t.id_tenant = c.id_tenant AND c.contract_status IN ('Active', 'Amended')
        LEFT JOIN `01_units` u ON c.id_unit = u.id_units
        WHERE t.id_tenant = ?
        ORDER BY c.id_contract DESC
        LIMIT 1
    ");
    $stmt->execute([$tenantId]);
    $tenant = $stmt->fetch();

    if (!$tenant) {
        $stmt = $pdo->query("
            SELECT
                t.id_tenant,
                t.tenant_name,
                t.brand_name,
                t.status,
                tc.name AS category_name,
                c.id_contract,
                c.contract_number,
                c.start_date,
                c.end_date,
                c.legal_document_url,
                u.unit_code
            FROM `02_tenants` t
            LEFT JOIN `01_tenant_categories` tc ON t.id_category = tc.id_tenant_categories
            LEFT JOIN `02_contracts` c ON t.id_tenant = c.id_tenant AND c.contract_status IN ('Active', 'Amended')
            LEFT JOIN `01_units` u ON c.id_unit = u.id_units
            ORDER BY t.id_tenant ASC
            LIMIT 1
        ");
        $tenant = $stmt->fetch();
        $tenantId = (int) ($tenant['id_tenant'] ?? 1);
    }

    return $tenant;
}

function getBills(PDO $pdo, $tenantId)
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM `06_invoices`
        WHERE tenant_id = ?
        ORDER BY due_date DESC, id DESC
    ");
    $stmt->execute([$tenantId]);
    return $stmt->fetchAll();
}

function getTickets(PDO $pdo, $tenantId)
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM `02_tenant_complaints`
        WHERE id_tenant = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$tenantId]);
    return $stmt->fetchAll();
}

function getDocuments($tenant, $bills)
{
    $documents = [];

    if (!empty($tenant['legal_document_url'])) {
        $documents[] = [
            'document_name' => 'Dokumen Kontrak Sewa',
            'document_no' => $tenant['contract_number'],
            'type' => 'Kontrak',
            'date' => $tenant['start_date'],
            'url' => $tenant['legal_document_url'],
        ];
    }

    foreach ($bills as $bill) {
        $documents[] = [
            'document_name' => 'Invoice ' . $bill['invoice_number'],
            'document_no' => $bill['invoice_number'],
            'type' => 'Invoice',
            'date' => $bill['period_start'],
            'url' => 'print_invoice.php?id=' . $bill['id'],
        ];
    }

    return $documents;
}

$tenant = getTenantData($pdo, $tenantId);
$bills = getBills($pdo, $tenantId);
$tickets = getTickets($pdo, $tenantId);
$documents = getDocuments($tenant, $bills);
$tenantName = $tenant['brand_name'] ?: $tenant['tenant_name'];
$ticketSubmissionId = bin2hex(random_bytes(16));
$paymentSubmissionBase = bin2hex(random_bytes(8));
?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $submissionId = $_POST['submission_id'] ?? '';

    checkSubmission($submissionId, $tenantId, 'tiket_keluhan.php');

    if ($action === 'create_complaint') {
        try {
            $ticketSubject = trim($_POST['ticket_subject'] ?? '');
            $ticketDescription = trim($_POST['ticket_description'] ?? '');
            $severityLevel = $_POST['severity_level'] ?? 'Low';

            if ($ticketSubject === '' || $ticketDescription === '') {
                throw new Exception('Judul dan deskripsi keluhan wajib diisi.');
            }

            $stmt = $pdo->prepare("
                SELECT id_unit
                FROM `02_contracts`
                WHERE id_tenant = ?
                  AND contract_status IN ('Active', 'Amended')
                ORDER BY id_contract DESC
                LIMIT 1
            ");
            $stmt->execute([$tenantId]);
            $activeContract = $stmt->fetch();

            if (!$activeContract) {
                throw new Exception('Tidak ada kontrak aktif untuk membuat tiket keluhan.');
            }

            $duplicateCheck = $pdo->prepare("
                SELECT id_complaint
                FROM `02_tenant_complaints`
                WHERE id_tenant = ?
                  AND title = ?
                  AND description = ?
                  AND severity_level = ?
                  AND status IN ('Open', 'In Progress')
                LIMIT 1
            ");
            $duplicateCheck->execute([
                $tenantId,
                $ticketSubject,
                $ticketDescription,
                $severityLevel
            ]);

            if ($duplicateCheck->fetch()) {
                redirectToPage('tiket_keluhan.php', $tenantId, 'Tiket dengan isi yang sama sudah pernah diajukan dan masih aktif.', 'error');
            }

            $stmt = $pdo->prepare("
                INSERT INTO `02_tenant_complaints`
                (id_tenant, id_unit, title, description, severity_level, status)
                VALUES (?, ?, ?, ?, ?, 'Open')
            ");
            $stmt->execute([
                $tenantId,
                $activeContract['id_unit'],
                $ticketSubject,
                $ticketDescription,
                $severityLevel,
            ]);

            redirectToPage('tiket_keluhan.php', $tenantId, 'Tiket keluhan berhasil diajukan dan tersimpan di database.', 'success');
        } catch (Exception $e) {
            redirectToPage('tiket_keluhan.php', $tenantId, 'Tiket keluhan gagal dibuat: ' . $e->getMessage(), 'error');
        }
    }

    redirectToPage('tiket_keluhan.php', $tenantId);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Keluhan</title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root {
    --primary: #0B376D;
    --primary-dark: #082A53;
    --secondary: #167E80;
    --secondary-dark: #0D4859;
    --accent: #00D4D8;
    --success: #22C55E;
    --danger: #EF4444;
    --background: #021F42;
    --text: #F5F7FA;
    --text-secondary: #B8C7D9;
    --text-accent: #FFB62A;
    --font-family: 'Poppins', sans-serif;
    --h1: 32px;
    --h2: 24px;
    --subheading: 20px;
    --body: 16px;
    --label: 14px;
    --caption: 12px;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--font-family);
    background: var(--background);
    color: var(--text);
    font-size: var(--body);
    min-height: 100vh;
}

/* Layout + sidebar sesuai contoh */
.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 250px;
    background: var(--primary-dark);
    display: flex;
    flex-direction: column;
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 10;
    border-right: 1px solid rgba(255,255,255,0.08);
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 26px 22px;
    color: var(--accent);
    font-size: 20px;
    font-weight: 700;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.sidebar-label {
    color: rgba(245,247,250,0.48);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 18px 20px 8px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 8px 12px;
    flex: 1;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 12px;
    border-radius: 8px;
    color: rgba(245,247,250,0.78);
    text-decoration: none;
    font-size: 14px;
    transition: .2s;
}

.nav-item:hover,
.nav-item.active {
    background: var(--secondary);
    color: var(--text);
    font-weight: 600;
}

.sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.main-content {
    margin-left: 250px;
    width: calc(100% - 250px);
    min-height: 100vh;
}

.topbar {
    background: var(--primary);
    padding: 22px 30px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    position: sticky;
    top: 0;
    z-index: 5;
}

.topbar h1 {
    font-size: 24px;
    color: var(--text);
    margin: 0;
}

.content-body {
    padding: 30px;
}

.page-wrapper {
    width: 100%;
    max-width: 1280px;
}

.card {
    background: var(--primary);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(2, 31, 66, 0.3);
}

.page-header,
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
}

.page-breadcrumb {
    color: var(--accent);
    font-size: var(--caption);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
    font-weight: 600;
}

h1,
.page-title {
    font-size: var(--h1);
    font-weight: 700;
    color: var(--text);
    margin-bottom: 10px;
}

h2,
.card-title {
    font-size: var(--h2);
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}

.description,
.page-description,
.card-subtitle {
    color: rgba(245, 247, 250, 0.65);
    font-size: var(--label);
    line-height: 1.6;
}

.alert {
    padding: 12px 16px;
    margin-bottom: 18px;
    border-radius: 8px;
    font-size: var(--label);
    font-weight: 500;
}

.alert.success {
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: var(--success);
}

.alert.error {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--danger);
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.stat-card {
    background: var(--primary);
    border-radius: 12px;
    padding: 20px 24px;
    border-left: 4px solid var(--text-accent);
}

.stat-label {
    display: block;
    color: rgba(245,247,250,0.65);
    font-size: var(--caption);
    margin-bottom: 6px;
}

.stat-value {
    display: block;
    font-size: var(--h2);
    font-weight: 700;
}

.form-input,
.search-input,
.form-group input,
.form-group select,
.form-group textarea {
    padding: 10px 14px;
    background: var(--background);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    color: var(--text);
    font-family: inherit;
    font-size: var(--label);
    outline: none;
}

.form-input:focus,
.search-input:focus,
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--accent);
}

.form-input option,
.form-group select option {
    background: var(--primary);
}

.table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

table,
.data-table {
    width: 100%;
    border-collapse: collapse;
}

th,
td,
.data-table th,
.data-table td {
    padding: 14px 16px;
    text-align: left;
    vertical-align: middle;
    font-size: var(--label);
}

th,
.data-table th {
    background: var(--primary-dark);
    color: var(--accent);
    font-weight: 600;
    font-size: var(--caption);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}

td,
.data-table td {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--text);
}

tbody tr:hover {
    background: rgba(0, 212, 216, 0.04);
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 99px;
    font-size: var(--caption);
    font-weight: 600;
    white-space: nowrap;
}

.badge-success {
    background: rgba(34, 197, 94, 0.12);
    color: var(--success);
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.badge-warning {
    background: rgba(255, 182, 42, 0.15);
    color: var(--text-accent);
    border: 1px solid rgba(255, 182, 42, 0.3);
}

.badge-info {
    background: rgba(0, 212, 216, 0.12);
    color: var(--accent);
    border: 1px solid rgba(0, 212, 216, 0.3);
}

.money {
    color: var(--text-accent);
    font-weight: 600;
}

.btn-primary,
.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-family: inherit;
    font-size: var(--caption);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease-in-out;
}

.btn-primary {
    background: var(--accent);
    color: var(--background);
    border: 1px solid var(--accent);
}

.btn-primary:disabled {
    opacity: .65;
    cursor: not-allowed;
}

.btn-secondary {
    background: transparent;
    color: var(--text);
    border: 1px solid rgba(255, 255, 255, 0.25);
}

.btn-secondary:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding-top: 18px;
}

.form-group {
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group-full {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    font-size: var(--label);
    font-weight: 500;
    color: rgba(245, 247, 250, 0.8);
}

.required {
    color: var(--danger);
}

textarea.form-input {
    resize: vertical;
    min-height: 90px;
}

.info-box {
    padding: 14px 16px;
    margin-bottom: 16px;
    border-left: 4px solid var(--text-accent);
    background: rgba(255, 182, 42, 0.08);
    border-radius: 0 8px 8px 0;
    line-height: 1.6;
    font-size: var(--label);
    color: rgba(245, 247, 250, 0.85);
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.empty-row {
    padding: 40px 20px;
    text-align: center;
    color: rgba(245, 247, 250, 0.45);
}

.tabs {
    display: flex;
    gap: 10px;
    padding: 18px 0;
    flex-wrap: wrap;
}

.tab-button {
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: transparent;
    color: var(--text);
    border-radius: 8px;
    padding: 10px 16px;
    font-family: inherit;
    font-size: var(--label);
    cursor: pointer;
}

.tab-button.active {
    background: var(--accent);
    color: var(--background);
    border-color: var(--accent);
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

@media (max-width: 768px) {
    .layout {
        display: block;
    }

    .sidebar {
        position: relative;
        width: 100%;
        bottom: auto;
    }

    .main-content {
        margin-left: 0;
        width: 100%;
    }

    .content-body {
        padding: 14px;
    }

    .page-header,
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-grid,
    .stats-row {
        grid-template-columns: 1fr;
    }

    th,
    td {
        padding: 10px 12px;
        font-size: var(--caption);
    }
}

/* Gap/spacing tambahan agar layout lebih rapi */
.content-body {
    padding: 36px 42px;
}

.page-wrapper {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.page-header {
    margin-bottom: 0;
    align-items: stretch;
    gap: 28px;
}

.page-header > div:first-child {
    flex: 1;
    padding-top: 10px;
}

.page-header > .card {
    align-self: flex-start;
}

.page-title {
    margin-bottom: 14px;
}

.page-description {
    max-width: 850px;
    margin-bottom: 0;
}

.stats-row {
    gap: 20px;
    margin-top: 0;
    margin-bottom: 4px;
}

.stat-card {
    min-height: 112px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.card {
    margin-bottom: 0;
}

.card-header {
    margin-bottom: 18px;
}

.tabs {
    padding: 14px 0 18px;
    gap: 12px;
}

.info-box {
    margin-bottom: 18px;
}

.table-wrapper {
    margin-top: 8px;
}

.form-grid {
    gap: 18px 22px;
}

.form-actions {
    margin-top: 8px;
}

@media (max-width: 768px) {
    .content-body {
        padding: 18px;
    }

    .page-header {
        gap: 16px;
    }

    .stats-row {
        gap: 14px;
    }
}
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span>🏢</span>
            <span>Mall ERP</span>
        </div>

        <div class="sidebar-label">M02 Module</div>

        <nav class="sidebar-nav">
            <a class="nav-item" href="tenant_portal_gabe.php?tenant_id=<?= (int) $tenantId ?>">
                <span>👤</span>
                <span>Tenant Portal</span>
            </a>

            <a class="nav-item" href="tagihan.php?tenant_id=<?= (int) $tenantId ?>">
                <span>💳</span>
                <span>Tagihan</span>
            </a>

            <a class="nav-item" href="invoice_dokumen.php?tenant_id=<?= (int) $tenantId ?>">
                <span>📁</span>
                <span>Dokumen</span>
            </a>

            <a class="nav-item active" href="tiket_keluhan.php?tenant_id=<?= (int) $tenantId ?>">
                <span>🎫</span>
                <span>Tiket Keluhan</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a class="nav-item" href="tenant_portal.php">
                <span>↩</span>
                <span>Back</span>
            </a>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <h1>Tiket Keluhan</h1>
        </header>

        <section class="content-body">
            <div class="page-wrapper">

                <div class="page-header">
                    <div>
                        <p class="page-breadcrumb">Tenant Portal / Tiket Keluhan</p>
                        <h1 class="page-title">Tiket Keluhan</h1>
                        <p class="page-description">
                            Tenant dapat mengajukan tiket keluhan dan melihat riwayat tiket.
                        </p>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?= htmlspecialchars($messageType) ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="info-box">
                        Tenant dapat mengajukan tiket keluhan. Data akan masuk ke tabel <strong>02_tenant_complaints</strong>.
                    </div>

                    <form method="POST" action="tiket_keluhan.php?tenant_id=<?= (int) $tenantId ?>" id="complaintForm">
                        <input type="hidden" name="action" value="create_complaint">
                        <input type="hidden" name="submission_id" value="<?= htmlspecialchars($ticketSubmissionId) ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="severity_level">Prioritas <span class="required">*</span></label>
                                <select id="severity_level" name="severity_level" class="form-input" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="ticket_subject">Judul Keluhan <span class="required">*</span></label>
                                <input type="text" id="ticket_subject" name="ticket_subject" class="form-input" placeholder="Contoh: Lampu tenant mati" required>
                            </div>

                            <div class="form-group form-group-full">
                                <label class="form-label" for="ticket_description">Deskripsi Keluhan <span class="required">*</span></label>
                                <textarea id="ticket_description" name="ticket_description" class="form-input" placeholder="Tuliskan detail permasalahan yang dialami..." required></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="reset" class="btn-secondary">Reset</button>
                            <button type="submit" class="btn-primary">Ajukan Tiket</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Riwayat Tiket Keluhan</h2>
                            <p class="card-subtitle">Daftar tiket keluhan yang pernah diajukan tenant.</p>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID Tiket</th>
                                    <th>Judul</th>
                                    <th>Prioritas</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($tickets)): ?>
                                    <tr>
                                        <td colspan="5" class="empty-row">Belum ada tiket keluhan.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td class="td-bold">TKT-<?= str_pad((string) $ticket['id_complaint'], 4, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= htmlspecialchars($ticket['title']) ?></td>
                                            <td><?= htmlspecialchars($ticket['severity_level']) ?></td>
                                            <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                                            <td>
                                                <?php if ($ticket['status'] === 'Resolved' || $ticket['status'] === 'Closed'): ?>
                                                    <span class="badge badge-success"><?= htmlspecialchars($ticket['status']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-info"><?= htmlspecialchars($ticket['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<script>
document.querySelectorAll('form[method="POST"]').forEach(function (form) {
    form.addEventListener('submit', function () {
        const submitButton = form.querySelector('button[type="submit"]');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Memproses...';
        }
    });
});
</script>
</body>
</html>
