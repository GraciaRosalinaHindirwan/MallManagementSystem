<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'mall_erp';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}

$tenantId = isset($_GET['tenant_id']) ? (int) $_GET['tenant_id'] : 0;
$message = $_GET['msg'] ?? '';
$messageType = $_GET['type'] ?? '';

if (empty($_SESSION['gabriel_tenant_portal_token'])) {
    $_SESSION['gabriel_tenant_portal_token'] = bin2hex(random_bytes(16));
}

$formToken = $_SESSION['gabriel_tenant_portal_token'];

function rupiah($value)
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function currentUrlWithTenant($tenantId)
{
    return 'tenant_portal_detail.php?tenant_id=' . (int) $tenantId;
}

if (isset($_GET['print_invoice'])) {
    $invoiceId = (int) $_GET['print_invoice'];

    $stmt = $pdo->prepare("
        SELECT
            i.*,
            t.tenant_name,
            t.brand_name,
            c.contract_number,
            u.unit_code
        FROM `06_invoices` i
        JOIN `02_tenants` t ON i.tenant_id = t.id_tenant
        JOIN `02_contracts` c ON i.contract_id = c.id_contract
        JOIN `01_units` u ON c.id_unit = u.id_units
        WHERE i.id = ?
        LIMIT 1
    ");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        die('Invoice tidak ditemukan.');
    }

    $tenantName = $invoice['brand_name'] ?: $invoice['tenant_name'];
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Invoice <?= htmlspecialchars($invoice['invoice_number']) ?></title>
        <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root{
    /* colors */
    --primary: #0B376D; ;
    --primary-dark: #082A53;

    --secondary: #167E80;
    --secondary-dark: #0D4859;

    --accent: #00D4D8;
    --success: #22C55E;
    --danger: #EF4444;

    /* background */
    --background: #021F42;

    /* text colors */
    --text: #F5F7FA;
    --text-secondary: #B8C7D9;
    --text-accent: #FFB62A;

    /* Typography */
    --font-family: 'Poppins', sans-serif;
    --h1: 32px;
    --h2: 24px;
    --subheading: 20px;
    --body: 16px;
    --label: 14px;
    --caption: 12px;
}

/* =============================================
   MODULE STYLES - VERSI STABIL
   ============================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-family, 'Poppins', sans-serif);
    background: var(--background, #021F42);
    color: var(--text, #F5F7FA);
    min-height: 100vh;
}

/* ---- LAYOUT ---- */
.layout {
    display: flex;
    min-height: 100vh;
}

/* ---- SIDEBAR (DESKTOP) ---- */
.sidebar {
    width: 260px;
    background: var(--primary-dark, #082A53);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 24px 20px;
    font-size: 20px;
    font-weight: 700;
    color: var(--accent, #00D4D8);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-section-label {
    font-size: 12px;
    color: rgba(245, 247, 250, 0.4);
    padding: 16px 20px 6px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 12px;
    flex: 1;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: rgba(245, 247, 250, 0.75);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text);
}

.nav-item.active {
    background: var(--secondary, #167E80);
    color: var(--text);
    font-weight: 600;
}

.sidebar-footer {
    padding: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* ---- MAIN CONTENT (DESKTOP) ---- */
.main-content {
    margin-left: 260px;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 32px;
    background: var(--primary, #0B376D);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: sticky;
    top: 0;
    z-index: 50;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.topbar-user {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--accent, #00D4D8);
    font-size: 14px;
}

.content-body {
    padding: 32px;
}

/* ---- TOMBOL HAMBURGER - SEMBUNYI DI DESKTOP ---- */
.menu-toggle {
    display: none;
}

/* ---- TOMBOL CLOSE DI SIDEBAR - SEMBUNYI DI DESKTOP ---- */
.sidebar-close {
    display: none;
}

/* ---- TABLET (max-width: 768px) ---- */
@media (max-width: 768px) {
    .sidebar {
        width: 240px;
    }
    
    .main-content {
        margin-left: 240px;
    }
    
    .topbar {
        padding: 12px 24px;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .content-body {
        padding: 24px;
    }
}

/* ---- MOBILE (max-width: 576px) ---- */
@media (max-width: 576px) {
    .sidebar {
        position: fixed;
        left: -280px;
        width: 280px;
        z-index: 1000;
        transition: left 0.3s ease;
    }
    
    .sidebar.open {
        left: 0;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.3);
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
    
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        min-height: 55px;
        position: relative;
    }
    
    .menu-toggle {
        display: flex !important;
        align-items: center;
        justify-content: center;
        background: var(--secondary, #167E80);
        border: none;
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        z-index: 1;
    }
    
    
    .menu-toggle i {
        font-size: 18px;
        color: var(--text);
    }
    
    .page-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        padding: 0;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        text-align: center;
    }
    
    .topbar-user {
        display: flex;
        align-items: center;
        gap: 6px;
        z-index: 1;
        background: var(--primary, #0B376D);
    }
    
    .topbar-user span {
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px; 
    }
    
    .content-body {
        padding: 16px;
    }
    
    .sidebar-close {
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: 12px;
        right: 12px;
        background: transparent;
        border: none;
        font-size: 20px;
        color: rgba(245, 247, 250, 0.6);
        cursor: pointer;
        z-index: 1002;
        width: 36px;
        height: 36px;
        border-radius: 50%;
    }
    
    .sidebar-close:hover {
        color: var(--accent, #00D4D8);
        background: rgba(255, 255, 255, 0.1);
    }
    
    body.sidebar-open .menu-toggle {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    
    /* Cards dan grid */
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .card {
        padding: 16px;
        margin-bottom: 16px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    table {
        min-width: 500px;
    }
    
    .table-wrap {
        overflow-x: auto;
    }
}

/* LAYAR SANGAT KECIL (max-width: 375px) */
@media (max-width: 375px) {
    .page-title {
        font-size: 13px;
    }
    
    .topbar-user {
        gap: 4px;
    }
    
    .topbar-user span {
        font-size: 11px;
        display: inline-block; 
    }
    
    .menu-toggle {
        padding: 6px 10px;
    }
    
    .menu-toggle i {
        font-size: 16px;
    }
}

/* ---- CARDS & UTILITY (tetap) ---- */
.card {
    background: var(--primary, #0B376D);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 16px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--primary, #0B376D);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border-left: 4px solid var(--text-accent, #FFB62A);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: rgba(0, 212, 216, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--accent, #00D4D8);
}

.stat-info h3 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.stat-info p {
    font-size: 12px;
    color: rgba(245, 247, 250, 0.6);
    margin: 0;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

thead th {
    background: var(--primary-dark, #082A53);
    padding: 12px 16px;
    text-align: left;
}

tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: rgba(34, 197, 94, 0.15);
    color: #22C55E;
}

.badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #EF4444;
}

.badge-warning {
    background: rgba(255, 182, 42, 0.15);
    color: #FFB62A;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--secondary, #167E80);
    color: var(--text);
}

.btn-primary:hover {
    background: #0D4859;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.form-group label {
    font-size: 14px;
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    background: var(--primary-dark, #082A53);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--accent, #00D4D8);
}

/* =============================================
   GABRIEL M02 PAGE EXTENSION
   Mengikuti designSystem.css + template.css
   ============================================= */

.content-header {
    margin-bottom: 24px;
}

.content-header .breadcrumb {
    color: var(--accent);
    font-size: var(--caption, 12px);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 600;
    margin-bottom: 6px;
}

.content-header p {
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--label, 14px);
    line-height: 1.7;
    max-width: 880px;
}

.card-subtitle {
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--caption, 12px);
    margin-top: -8px;
    margin-bottom: 16px;
}

.toolbar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.toolbar input,
.toolbar select,
.search-input {
    background: var(--primary-dark, #082A53);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    outline: none;
    min-width: 240px;
}

.toolbar input:focus,
.toolbar select:focus,
.search-input:focus {
    border-color: var(--accent, #00D4D8);
}

.form-grid,
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 24px;
}

.form-grid .form-group-full,
.form-row .form-group-full {
    grid-column: 1 / -1;
}

.form-group textarea {
    min-height: 96px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 12px;
}

.btn-secondary {
    background: transparent;
    color: var(--text);
    border: 1px solid rgba(255, 255, 255, 0.22);
}

.btn-secondary:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.30);
}

.btn-danger:hover {
    background: var(--danger);
    color: var(--text);
}

.btn:disabled,
.btn-primary:disabled,
.btn-secondary:disabled,
.btn-danger:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.badge-info {
    background: rgba(0, 212, 216, 0.15);
    color: var(--accent);
}

.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: var(--label, 14px);
    font-weight: 600;
}

.alert.success {
    background: rgba(34, 197, 94, 0.15);
    color: var(--success);
    border: 1px solid rgba(34, 197, 94, 0.30);
}

.alert.error {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.30);
}

.info-box,
.note,
.checklist {
    padding: 14px 16px;
    border-left: 4px solid var(--text-accent, #FFB62A);
    background: rgba(255, 182, 42, 0.08);
    border-radius: 0 8px 8px 0;
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--label, 14px);
    line-height: 1.7;
    margin-bottom: 18px;
}

.checklist label {
    display: block;
    color: var(--text);
    margin-top: 10px;
    cursor: pointer;
}

.empty-row {
    text-align: center;
    padding: 36px 16px;
    color: var(--text-secondary, #B8C7D9);
}

.money,
.text-warning {
    color: var(--text-accent, #FFB62A);
    font-weight: 700;
}

.text-success {
    color: var(--success, #22C55E);
    font-weight: 700;
}

.tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.tab-button {
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: transparent;
    color: var(--text);
    border-radius: 8px;
    padding: 10px 16px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.tab-button.active {
    background: var(--secondary, #167E80);
    color: var(--text);
    border-color: var(--secondary, #167E80);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.print-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

@media print {
    .sidebar,
    .topbar,
    .no-print {
        display: none !important;
    }

    .main-content {
        margin-left: 0 !important;
    }

    body {
        background: #fff;
        color: #111;
    }

    .card {
        background: #fff;
        color: #111;
        border: 1px solid #ddd;
    }

    thead th {
        color: #111;
        background: #eee;
    }

    tbody td {
        color: #111;
    }
}

@media (max-width: 576px) {
    .form-row,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .toolbar input,
    .toolbar select,
    .search-input {
        width: 100%;
        min-width: unset;
    }

    .form-actions,
    .print-actions {
        flex-direction: column;
    }
}
        </style>
    </head>
    <body>
        <div class="content-body">
            <div class="card">
                <p class="content-header breadcrumb">Invoice Tenant</p>
                <h1 class="page-title"><?= htmlspecialchars($invoice['invoice_number']) ?></h1>
                <p class="card-subtitle">
                    Tenant: <strong><?= htmlspecialchars($tenantName) ?></strong><br>
                    Kontrak: <?= htmlspecialchars($invoice['contract_number']) ?><br>
                    Unit: <?= htmlspecialchars($invoice['unit_code']) ?>
                </p>

                <div class="table-wrap">
                    <table>
                        <tr><th>Periode</th><td><?= htmlspecialchars($invoice['period_start']) ?> s.d. <?= htmlspecialchars($invoice['period_end']) ?></td></tr>
                        <tr><th>Subtotal</th><td><?= rupiah($invoice['subtotal']) ?></td></tr>
                        <tr><th>PPN</th><td><?= rupiah($invoice['tax_amount']) ?></td></tr>
                        <tr><th>Total</th><td class="money"><?= rupiah($invoice['total_amount']) ?></td></tr>
                        <tr><th>Jatuh Tempo</th><td><?= htmlspecialchars($invoice['due_date']) ?></td></tr>
                        <tr><th>Status</th><td><?= htmlspecialchars($invoice['status']) ?></td></tr>
                    </table>
                </div>

                <div class="print-actions no-print">
                    <button class="btn btn-primary" onclick="window.print()">Print / Simpan PDF</button>
                    <a class="btn btn-secondary" href="tenant_portal_detail.php?tenant_id=<?= (int) $invoice['tenant_id'] ?>">Kembali</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (isset($_GET['print_contract'])) {
    $contractId = (int) $_GET['print_contract'];

    $stmt = $pdo->prepare("
        SELECT
            c.*,
            t.tenant_name,
            t.brand_name,
            tc.name AS category_name,
            u.unit_code,
            u.area_size
        FROM `02_contracts` c
        JOIN `02_tenants` t ON c.id_tenant = t.id_tenant
        LEFT JOIN `01_tenant_categories` tc ON t.id_category = tc.id_tenant_categories
        JOIN `01_units` u ON c.id_unit = u.id_units
        WHERE c.id_contract = ?
        LIMIT 1
    ");
    $stmt->execute([$contractId]);
    $contract = $stmt->fetch();

    if (!$contract) {
        die('Kontrak tidak ditemukan.');
    }

    $tenantName = $contract['brand_name'] ?: $contract['tenant_name'];
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Dokumen Kontrak <?= htmlspecialchars($contract['contract_number']) ?></title>
        <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root{
    /* colors */
    --primary: #0B376D; ;
    --primary-dark: #082A53;

    --secondary: #167E80;
    --secondary-dark: #0D4859;

    --accent: #00D4D8;
    --success: #22C55E;
    --danger: #EF4444;

    /* background */
    --background: #021F42;

    /* text colors */
    --text: #F5F7FA;
    --text-secondary: #B8C7D9;
    --text-accent: #FFB62A;

    /* Typography */
    --font-family: 'Poppins', sans-serif;
    --h1: 32px;
    --h2: 24px;
    --subheading: 20px;
    --body: 16px;
    --label: 14px;
    --caption: 12px;
}

/* =============================================
   MODULE STYLES - VERSI STABIL
   ============================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-family, 'Poppins', sans-serif);
    background: var(--background, #021F42);
    color: var(--text, #F5F7FA);
    min-height: 100vh;
}

/* ---- LAYOUT ---- */
.layout {
    display: flex;
    min-height: 100vh;
}

/* ---- SIDEBAR (DESKTOP) ---- */
.sidebar {
    width: 260px;
    background: var(--primary-dark, #082A53);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 24px 20px;
    font-size: 20px;
    font-weight: 700;
    color: var(--accent, #00D4D8);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-section-label {
    font-size: 12px;
    color: rgba(245, 247, 250, 0.4);
    padding: 16px 20px 6px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 12px;
    flex: 1;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: rgba(245, 247, 250, 0.75);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text);
}

.nav-item.active {
    background: var(--secondary, #167E80);
    color: var(--text);
    font-weight: 600;
}

.sidebar-footer {
    padding: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* ---- MAIN CONTENT (DESKTOP) ---- */
.main-content {
    margin-left: 260px;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 32px;
    background: var(--primary, #0B376D);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: sticky;
    top: 0;
    z-index: 50;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.topbar-user {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--accent, #00D4D8);
    font-size: 14px;
}

.content-body {
    padding: 32px;
}

/* ---- TOMBOL HAMBURGER - SEMBUNYI DI DESKTOP ---- */
.menu-toggle {
    display: none;
}

/* ---- TOMBOL CLOSE DI SIDEBAR - SEMBUNYI DI DESKTOP ---- */
.sidebar-close {
    display: none;
}

/* ---- TABLET (max-width: 768px) ---- */
@media (max-width: 768px) {
    .sidebar {
        width: 240px;
    }
    
    .main-content {
        margin-left: 240px;
    }
    
    .topbar {
        padding: 12px 24px;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .content-body {
        padding: 24px;
    }
}

/* ---- MOBILE (max-width: 576px) ---- */
@media (max-width: 576px) {
    .sidebar {
        position: fixed;
        left: -280px;
        width: 280px;
        z-index: 1000;
        transition: left 0.3s ease;
    }
    
    .sidebar.open {
        left: 0;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.3);
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
    
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        min-height: 55px;
        position: relative;
    }
    
    .menu-toggle {
        display: flex !important;
        align-items: center;
        justify-content: center;
        background: var(--secondary, #167E80);
        border: none;
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        z-index: 1;
    }
    
    
    .menu-toggle i {
        font-size: 18px;
        color: var(--text);
    }
    
    .page-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        padding: 0;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        text-align: center;
    }
    
    .topbar-user {
        display: flex;
        align-items: center;
        gap: 6px;
        z-index: 1;
        background: var(--primary, #0B376D);
    }
    
    .topbar-user span {
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px; 
    }
    
    .content-body {
        padding: 16px;
    }
    
    .sidebar-close {
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: 12px;
        right: 12px;
        background: transparent;
        border: none;
        font-size: 20px;
        color: rgba(245, 247, 250, 0.6);
        cursor: pointer;
        z-index: 1002;
        width: 36px;
        height: 36px;
        border-radius: 50%;
    }
    
    .sidebar-close:hover {
        color: var(--accent, #00D4D8);
        background: rgba(255, 255, 255, 0.1);
    }
    
    body.sidebar-open .menu-toggle {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    
    /* Cards dan grid */
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .card {
        padding: 16px;
        margin-bottom: 16px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    table {
        min-width: 500px;
    }
    
    .table-wrap {
        overflow-x: auto;
    }
}

/* LAYAR SANGAT KECIL (max-width: 375px) */
@media (max-width: 375px) {
    .page-title {
        font-size: 13px;
    }
    
    .topbar-user {
        gap: 4px;
    }
    
    .topbar-user span {
        font-size: 11px;
        display: inline-block; 
    }
    
    .menu-toggle {
        padding: 6px 10px;
    }
    
    .menu-toggle i {
        font-size: 16px;
    }
}

/* ---- CARDS & UTILITY (tetap) ---- */
.card {
    background: var(--primary, #0B376D);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 16px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--primary, #0B376D);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border-left: 4px solid var(--text-accent, #FFB62A);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: rgba(0, 212, 216, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--accent, #00D4D8);
}

.stat-info h3 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.stat-info p {
    font-size: 12px;
    color: rgba(245, 247, 250, 0.6);
    margin: 0;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

thead th {
    background: var(--primary-dark, #082A53);
    padding: 12px 16px;
    text-align: left;
}

tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: rgba(34, 197, 94, 0.15);
    color: #22C55E;
}

.badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #EF4444;
}

.badge-warning {
    background: rgba(255, 182, 42, 0.15);
    color: #FFB62A;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--secondary, #167E80);
    color: var(--text);
}

.btn-primary:hover {
    background: #0D4859;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.form-group label {
    font-size: 14px;
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    background: var(--primary-dark, #082A53);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--accent, #00D4D8);
}

/* =============================================
   GABRIEL M02 PAGE EXTENSION
   Mengikuti designSystem.css + template.css
   ============================================= */

.content-header {
    margin-bottom: 24px;
}

.content-header .breadcrumb {
    color: var(--accent);
    font-size: var(--caption, 12px);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 600;
    margin-bottom: 6px;
}

.content-header p {
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--label, 14px);
    line-height: 1.7;
    max-width: 880px;
}

.card-subtitle {
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--caption, 12px);
    margin-top: -8px;
    margin-bottom: 16px;
}

.toolbar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.toolbar input,
.toolbar select,
.search-input {
    background: var(--primary-dark, #082A53);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    outline: none;
    min-width: 240px;
}

.toolbar input:focus,
.toolbar select:focus,
.search-input:focus {
    border-color: var(--accent, #00D4D8);
}

.form-grid,
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 24px;
}

.form-grid .form-group-full,
.form-row .form-group-full {
    grid-column: 1 / -1;
}

.form-group textarea {
    min-height: 96px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 12px;
}

.btn-secondary {
    background: transparent;
    color: var(--text);
    border: 1px solid rgba(255, 255, 255, 0.22);
}

.btn-secondary:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.30);
}

.btn-danger:hover {
    background: var(--danger);
    color: var(--text);
}

.btn:disabled,
.btn-primary:disabled,
.btn-secondary:disabled,
.btn-danger:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.badge-info {
    background: rgba(0, 212, 216, 0.15);
    color: var(--accent);
}

.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: var(--label, 14px);
    font-weight: 600;
}

.alert.success {
    background: rgba(34, 197, 94, 0.15);
    color: var(--success);
    border: 1px solid rgba(34, 197, 94, 0.30);
}

.alert.error {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.30);
}

.info-box,
.note,
.checklist {
    padding: 14px 16px;
    border-left: 4px solid var(--text-accent, #FFB62A);
    background: rgba(255, 182, 42, 0.08);
    border-radius: 0 8px 8px 0;
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--label, 14px);
    line-height: 1.7;
    margin-bottom: 18px;
}

.checklist label {
    display: block;
    color: var(--text);
    margin-top: 10px;
    cursor: pointer;
}

.empty-row {
    text-align: center;
    padding: 36px 16px;
    color: var(--text-secondary, #B8C7D9);
}

.money,
.text-warning {
    color: var(--text-accent, #FFB62A);
    font-weight: 700;
}

.text-success {
    color: var(--success, #22C55E);
    font-weight: 700;
}

.tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.tab-button {
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: transparent;
    color: var(--text);
    border-radius: 8px;
    padding: 10px 16px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.tab-button.active {
    background: var(--secondary, #167E80);
    color: var(--text);
    border-color: var(--secondary, #167E80);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.print-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

@media print {
    .sidebar,
    .topbar,
    .no-print {
        display: none !important;
    }

    .main-content {
        margin-left: 0 !important;
    }

    body {
        background: #fff;
        color: #111;
    }

    .card {
        background: #fff;
        color: #111;
        border: 1px solid #ddd;
    }

    thead th {
        color: #111;
        background: #eee;
    }

    tbody td {
        color: #111;
    }
}

@media (max-width: 576px) {
    .form-row,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .toolbar input,
    .toolbar select,
    .search-input {
        width: 100%;
        min-width: unset;
    }

    .form-actions,
    .print-actions {
        flex-direction: column;
    }
}
        </style>
    </head>
    <body>
        <div class="content-body">
            <div class="card">
                <p class="content-header breadcrumb">Dokumen Kontrak Tenant</p>
                <h1 class="page-title"><?= htmlspecialchars($contract['contract_number']) ?></h1>
                <p class="card-subtitle">Arsip kontrak berdasarkan data kontrak di database.</p>

                <div class="table-wrap">
                    <table>
                        <tr><th>Tenant</th><td><?= htmlspecialchars($tenantName) ?></td></tr>
                        <tr><th>Kategori</th><td><?= htmlspecialchars($contract['category_name'] ?? '-') ?></td></tr>
                        <tr><th>Unit</th><td><?= htmlspecialchars($contract['unit_code']) ?></td></tr>
                        <tr><th>Luas Unit</th><td><?= htmlspecialchars($contract['area_size']) ?> m²</td></tr>
                        <tr><th>Periode Kontrak</th><td><?= htmlspecialchars($contract['start_date']) ?> s.d. <?= htmlspecialchars($contract['end_date']) ?></td></tr>
                        <tr><th>Status</th><td><?= htmlspecialchars($contract['contract_status']) ?></td></tr>
                        <tr><th>File Legal</th><td><?= htmlspecialchars($contract['legal_document_url'] ?? '-') ?></td></tr>
                    </table>
                </div>

                <div class="print-actions no-print">
                    <button class="btn btn-primary" onclick="window.print()">Print / Simpan PDF</button>
                    <a class="btn btn-secondary" href="tenant_portal_detail.php?tenant_id=<?= (int) $contract['id_tenant'] ?>">Kembali</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['form_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($postedToken === '' || !hash_equals($_SESSION['gabriel_tenant_portal_token'] ?? '', $postedToken)) {
        header('Location: ' . currentUrlWithTenant($tenantId) . '&msg=' . urlencode('Submit ulang dicegah. Data tidak ditambahkan lagi.') . '&type=error');
        exit;
    }

    unset($_SESSION['gabriel_tenant_portal_token']);

    if ($action === 'pay_invoice') {
        $invoiceId = (int) ($_POST['invoice_id'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'Bank Transfer';

        try {
            $stmt = $pdo->prepare("
                UPDATE `06_invoices`
                SET status = 'Lunas',
                    payment_date = NOW(),
                    payment_method = ?,
                    amount_paid = total_amount
                WHERE id = ?
                  AND tenant_id = ?
                  AND status <> 'Lunas'
            ");
            $stmt->execute([$paymentMethod, $invoiceId, $tenantId]);

            header('Location: ' . currentUrlWithTenant($tenantId) . '&msg=' . urlencode('Pembayaran berhasil diproses. Status invoice menjadi Lunas.') . '&type=success');
            exit;
        } catch (PDOException $e) {
            header('Location: ' . currentUrlWithTenant($tenantId) . '&msg=' . urlencode('Pembayaran gagal: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    if ($action === 'create_complaint') {
        $title = trim($_POST['ticket_subject'] ?? '');
        $description = trim($_POST['ticket_description'] ?? '');
        $severity = $_POST['severity_level'] ?? 'Low';

        try {
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

            if ($title === '' || $description === '') {
                throw new Exception('Judul dan deskripsi keluhan wajib diisi.');
            }

            $stmt = $pdo->prepare("
                SELECT id_complaint
                FROM `02_tenant_complaints`
                WHERE id_tenant = ?
                  AND title = ?
                  AND description = ?
                  AND severity_level = ?
                  AND status IN ('Open', 'In Progress')
                LIMIT 1
            ");
            $stmt->execute([$tenantId, $title, $description, $severity]);

            if ($stmt->fetch()) {
                throw new Exception('Tiket dengan isi yang sama sudah pernah diajukan dan masih aktif.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO `02_tenant_complaints`
                (id_tenant, id_unit, title, description, severity_level, status)
                VALUES (?, ?, ?, ?, ?, 'Open')
            ");
            $stmt->execute([$tenantId, $activeContract['id_unit'], $title, $description, $severity]);

            header('Location: ' . currentUrlWithTenant($tenantId) . '&msg=' . urlencode('Tiket keluhan berhasil diajukan dan tersimpan di database.') . '&type=success');
            exit;
        } catch (Exception $e) {
            header('Location: ' . currentUrlWithTenant($tenantId) . '&msg=' . urlencode('Tiket keluhan gagal: ' . $e->getMessage()) . '&type=error');
            exit;
        }
    }

    header('Location: ' . currentUrlWithTenant($tenantId));
    exit;
}

if (empty($_SESSION['gabriel_tenant_portal_token'])) {
    $_SESSION['gabriel_tenant_portal_token'] = bin2hex(random_bytes(16));
}

$formToken = $_SESSION['gabriel_tenant_portal_token'];

if ($tenantId <= 0) {
    $stmt = $pdo->query("
        SELECT
            t.id_tenant,
            t.tenant_name,
            t.brand_name,
            t.status,
            tc.name AS category_name,
            c.contract_number,
            c.contract_status,
            u.unit_code,
            COUNT(i.id) AS invoice_count,
            COALESCE(SUM(CASE WHEN i.status <> 'Lunas' THEN i.total_amount ELSE 0 END), 0) AS unpaid_amount
        FROM `02_tenants` t
        LEFT JOIN `01_tenant_categories` tc ON t.id_category = tc.id_tenant_categories
        LEFT JOIN `02_contracts` c ON t.id_tenant = c.id_tenant AND c.contract_status IN ('Active', 'Amended')
        LEFT JOIN `01_units` u ON c.id_unit = u.id_units
        LEFT JOIN `06_invoices` i ON t.id_tenant = i.tenant_id
        GROUP BY
            t.id_tenant, t.tenant_name, t.brand_name, t.status,
            tc.name, c.contract_number, c.contract_status, u.unit_code
        ORDER BY t.id_tenant ASC
    ");
    $tenantList = $stmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pilih Tenant - Tenant Portal</title>
        <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root{
    /* colors */
    --primary: #0B376D; ;
    --primary-dark: #082A53;

    --secondary: #167E80;
    --secondary-dark: #0D4859;

    --accent: #00D4D8;
    --success: #22C55E;
    --danger: #EF4444;

    /* background */
    --background: #021F42;

    /* text colors */
    --text: #F5F7FA;
    --text-secondary: #B8C7D9;
    --text-accent: #FFB62A;

    /* Typography */
    --font-family: 'Poppins', sans-serif;
    --h1: 32px;
    --h2: 24px;
    --subheading: 20px;
    --body: 16px;
    --label: 14px;
    --caption: 12px;
}

/* =============================================
   MODULE STYLES - VERSI STABIL
   ============================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-family, 'Poppins', sans-serif);
    background: var(--background, #021F42);
    color: var(--text, #F5F7FA);
    min-height: 100vh;
}

/* ---- LAYOUT ---- */
.layout {
    display: flex;
    min-height: 100vh;
}

/* ---- SIDEBAR (DESKTOP) ---- */
.sidebar {
    width: 260px;
    background: var(--primary-dark, #082A53);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 24px 20px;
    font-size: 20px;
    font-weight: 700;
    color: var(--accent, #00D4D8);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-section-label {
    font-size: 12px;
    color: rgba(245, 247, 250, 0.4);
    padding: 16px 20px 6px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 12px;
    flex: 1;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: rgba(245, 247, 250, 0.75);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text);
}

.nav-item.active {
    background: var(--secondary, #167E80);
    color: var(--text);
    font-weight: 600;
}

.sidebar-footer {
    padding: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* ---- MAIN CONTENT (DESKTOP) ---- */
.main-content {
    margin-left: 260px;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 32px;
    background: var(--primary, #0B376D);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: sticky;
    top: 0;
    z-index: 50;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.topbar-user {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--accent, #00D4D8);
    font-size: 14px;
}

.content-body {
    padding: 32px;
}

/* ---- TOMBOL HAMBURGER - SEMBUNYI DI DESKTOP ---- */
.menu-toggle {
    display: none;
}

/* ---- TOMBOL CLOSE DI SIDEBAR - SEMBUNYI DI DESKTOP ---- */
.sidebar-close {
    display: none;
}

/* ---- TABLET (max-width: 768px) ---- */
@media (max-width: 768px) {
    .sidebar {
        width: 240px;
    }
    
    .main-content {
        margin-left: 240px;
    }
    
    .topbar {
        padding: 12px 24px;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .content-body {
        padding: 24px;
    }
}

/* ---- MOBILE (max-width: 576px) ---- */
@media (max-width: 576px) {
    .sidebar {
        position: fixed;
        left: -280px;
        width: 280px;
        z-index: 1000;
        transition: left 0.3s ease;
    }
    
    .sidebar.open {
        left: 0;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.3);
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
    
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        min-height: 55px;
        position: relative;
    }
    
    .menu-toggle {
        display: flex !important;
        align-items: center;
        justify-content: center;
        background: var(--secondary, #167E80);
        border: none;
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        z-index: 1;
    }
    
    
    .menu-toggle i {
        font-size: 18px;
        color: var(--text);
    }
    
    .page-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        padding: 0;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        text-align: center;
    }
    
    .topbar-user {
        display: flex;
        align-items: center;
        gap: 6px;
        z-index: 1;
        background: var(--primary, #0B376D);
    }
    
    .topbar-user span {
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px; 
    }
    
    .content-body {
        padding: 16px;
    }
    
    .sidebar-close {
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: 12px;
        right: 12px;
        background: transparent;
        border: none;
        font-size: 20px;
        color: rgba(245, 247, 250, 0.6);
        cursor: pointer;
        z-index: 1002;
        width: 36px;
        height: 36px;
        border-radius: 50%;
    }
    
    .sidebar-close:hover {
        color: var(--accent, #00D4D8);
        background: rgba(255, 255, 255, 0.1);
    }
    
    body.sidebar-open .menu-toggle {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    
    /* Cards dan grid */
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .card {
        padding: 16px;
        margin-bottom: 16px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    table {
        min-width: 500px;
    }
    
    .table-wrap {
        overflow-x: auto;
    }
}

/* LAYAR SANGAT KECIL (max-width: 375px) */
@media (max-width: 375px) {
    .page-title {
        font-size: 13px;
    }
    
    .topbar-user {
        gap: 4px;
    }
    
    .topbar-user span {
        font-size: 11px;
        display: inline-block; 
    }
    
    .menu-toggle {
        padding: 6px 10px;
    }
    
    .menu-toggle i {
        font-size: 16px;
    }
}

/* ---- CARDS & UTILITY (tetap) ---- */
.card {
    background: var(--primary, #0B376D);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 16px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--primary, #0B376D);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border-left: 4px solid var(--text-accent, #FFB62A);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: rgba(0, 212, 216, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--accent, #00D4D8);
}

.stat-info h3 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.stat-info p {
    font-size: 12px;
    color: rgba(245, 247, 250, 0.6);
    margin: 0;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

thead th {
    background: var(--primary-dark, #082A53);
    padding: 12px 16px;
    text-align: left;
}

tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: rgba(34, 197, 94, 0.15);
    color: #22C55E;
}

.badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #EF4444;
}

.badge-warning {
    background: rgba(255, 182, 42, 0.15);
    color: #FFB62A;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--secondary, #167E80);
    color: var(--text);
}

.btn-primary:hover {
    background: #0D4859;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.form-group label {
    font-size: 14px;
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    background: var(--primary-dark, #082A53);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--accent, #00D4D8);
}

/* =============================================
   GABRIEL M02 PAGE EXTENSION
   Mengikuti designSystem.css + template.css
   ============================================= */

.content-header {
    margin-bottom: 24px;
}

.content-header .breadcrumb {
    color: var(--accent);
    font-size: var(--caption, 12px);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 600;
    margin-bottom: 6px;
}

.content-header p {
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--label, 14px);
    line-height: 1.7;
    max-width: 880px;
}

.card-subtitle {
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--caption, 12px);
    margin-top: -8px;
    margin-bottom: 16px;
}

.toolbar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.toolbar input,
.toolbar select,
.search-input {
    background: var(--primary-dark, #082A53);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    outline: none;
    min-width: 240px;
}

.toolbar input:focus,
.toolbar select:focus,
.search-input:focus {
    border-color: var(--accent, #00D4D8);
}

.form-grid,
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 24px;
}

.form-grid .form-group-full,
.form-row .form-group-full {
    grid-column: 1 / -1;
}

.form-group textarea {
    min-height: 96px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 12px;
}

.btn-secondary {
    background: transparent;
    color: var(--text);
    border: 1px solid rgba(255, 255, 255, 0.22);
}

.btn-secondary:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.30);
}

.btn-danger:hover {
    background: var(--danger);
    color: var(--text);
}

.btn:disabled,
.btn-primary:disabled,
.btn-secondary:disabled,
.btn-danger:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.badge-info {
    background: rgba(0, 212, 216, 0.15);
    color: var(--accent);
}

.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: var(--label, 14px);
    font-weight: 600;
}

.alert.success {
    background: rgba(34, 197, 94, 0.15);
    color: var(--success);
    border: 1px solid rgba(34, 197, 94, 0.30);
}

.alert.error {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.30);
}

.info-box,
.note,
.checklist {
    padding: 14px 16px;
    border-left: 4px solid var(--text-accent, #FFB62A);
    background: rgba(255, 182, 42, 0.08);
    border-radius: 0 8px 8px 0;
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--label, 14px);
    line-height: 1.7;
    margin-bottom: 18px;
}

.checklist label {
    display: block;
    color: var(--text);
    margin-top: 10px;
    cursor: pointer;
}

.empty-row {
    text-align: center;
    padding: 36px 16px;
    color: var(--text-secondary, #B8C7D9);
}

.money,
.text-warning {
    color: var(--text-accent, #FFB62A);
    font-weight: 700;
}

.text-success {
    color: var(--success, #22C55E);
    font-weight: 700;
}

.tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.tab-button {
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: transparent;
    color: var(--text);
    border-radius: 8px;
    padding: 10px 16px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.tab-button.active {
    background: var(--secondary, #167E80);
    color: var(--text);
    border-color: var(--secondary, #167E80);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.print-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

@media print {
    .sidebar,
    .topbar,
    .no-print {
        display: none !important;
    }

    .main-content {
        margin-left: 0 !important;
    }

    body {
        background: #fff;
        color: #111;
    }

    .card {
        background: #fff;
        color: #111;
        border: 1px solid #ddd;
    }

    thead th {
        color: #111;
        background: #eee;
    }

    tbody td {
        color: #111;
    }
}

@media (max-width: 576px) {
    .form-row,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .toolbar input,
    .toolbar select,
    .search-input {
        width: 100%;
        min-width: unset;
    }

    .form-actions,
    .print-actions {
        flex-direction: column;
    }
}
        </style>
    </head>
    <body>
    <div class="layout">

        <aside class="sidebar" id="sidebar">
            <button class="sidebar-close" type="button" onclick="toggleSidebar()">×</button>

            <div class="sidebar-brand">
                <span>🏢</span>
                <span>Mall ERP</span>
            </div>

            <div class="sidebar-section-label">M02 Module</div>

            <nav class="sidebar-nav">
                <a href="../leasingManager/terminasi_kontrak.php" class="nav-item nav-terminasi">
                    <span>📝</span>
                    <span>Terminasi Kontrak</span>
                </a>

                <a href="../tenant/tenant_portal.php" class="nav-item nav-portal">
                    <span>👤</span>
                    <span>Tenant Portal</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="#" class="nav-item">
                    <span>↩</span>
                    <span>Back</span>
                </a>
            </div>
        </aside>


        <main class="main-content">

            <header class="topbar">
                <button class="menu-toggle" type="button" onclick="toggleSidebar()">
                    <span style="color: var(--text); font-size: 18px;">☰</span>
                </button>

                <h1 class="page-title">Tenant Portal</h1>
            </header>


            <section class="content-body">
                <div class="content-header">
                    <p class="breadcrumb">M02 Tenant & Leasing / PBI-M02-04-01 s.d. 04-03</p>
                    <h2 class="card-title">Pilih Tenant</h2>
                    <p>
                        Pilih tenant terlebih dahulu agar dashboard, invoice, dokumen kontrak,
                        dan tiket keluhan yang terbuka sesuai tenant tersebut.
                    </p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <h3><?= count($tenantList) ?></h3>
                            <p>Total Tenant</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">🔗</div>
                        <div class="stat-info">
                            <h3>Per Tenant</h3>
                            <p>Akses Portal</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">🗄️</div>
                        <div class="stat-info">
                            <h3>Database</h3>
                            <p>Sumber Data</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Daftar Tenant</h2>
                            <p class="card-subtitle">Klik Buka Portal pada tenant yang ingin dilihat.</p>
                        </div>
                        <input type="text" id="tenantSearch" class="search-input" placeholder="Cari tenant, unit, atau kontrak...">
                    </div>

                    <div class="table-wrap">
                        <table id="tenantTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tenant</th>
                                    <th>Kategori</th>
                                    <th>Unit</th>
                                    <th>No. Kontrak</th>
                                    <th>Tagihan Belum Lunas</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tenantList)): ?>
                                    <tr>
                                        <td colspan="8" class="empty-row">Belum ada data tenant di database.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tenantList as $tenantRow): ?>
                                        <?php $tenantName = $tenantRow['brand_name'] ?: $tenantRow['tenant_name']; ?>
                                        <tr>
                                            <td class="td-bold"><?= (int) $tenantRow['id_tenant'] ?></td>
                                            <td><?= htmlspecialchars($tenantName) ?></td>
                                            <td><?= htmlspecialchars($tenantRow['category_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($tenantRow['unit_code'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($tenantRow['contract_number'] ?? '-') ?></td>
                                            <td class="money"><?= rupiah($tenantRow['unpaid_amount']) ?></td>
                                            <td>
                                                <?php if ($tenantRow['status'] === 'Active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php elseif ($tenantRow['status'] === 'Terminated'): ?>
                                                    <span class="badge badge-danger">Terminated</span>
                                                <?php else: ?>
                                                    <span class="badge badge-info"><?= htmlspecialchars($tenantRow['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a class="btn btn-primary" href="tenant_portal_detail.php?tenant_id=<?= (int) $tenantRow['id_tenant'] ?>">
                                                    Buka Portal
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
    
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
    document.body.classList.toggle('sidebar-open');
}


    document.addEventListener('DOMContentLoaded', function () {
        document.querySelector('.nav-portal').classList.add('active');

        document.getElementById('tenantSearch').addEventListener('input', function () {
            const keyword = this.value.toLowerCase();

            document.querySelectorAll('#tenantTable tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(keyword) ? '' : 'none';
            });
        });
    });
    </script>
    </body>
    </html>
    <?php
    exit;
}

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
    die('Tenant tidak ditemukan. <a href="tenant_portal_detail.php">Kembali ke daftar tenant</a>');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM `06_invoices`
    WHERE tenant_id = ?
    ORDER BY due_date DESC, id DESC
");
$stmt->execute([$tenantId]);
$bills = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT *
    FROM `02_tenant_complaints`
    WHERE id_tenant = ?
    ORDER BY created_at DESC
");
$stmt->execute([$tenantId]);
$tickets = $stmt->fetchAll();

$documents = [];

if (!empty($tenant['id_contract'])) {
    $documents[] = [
        'document_name' => 'Dokumen Kontrak Sewa',
        'document_no' => $tenant['contract_number'],
        'type' => 'Kontrak',
        'date' => $tenant['start_date'],
        'url' => 'tenant_portal_detail.php?tenant_id=' . $tenantId . '&print_contract=' . (int) $tenant['id_contract'],
    ];
}

foreach ($bills as $bill) {
    $documents[] = [
        'document_name' => 'Invoice ' . $bill['invoice_number'],
        'document_no' => $bill['invoice_number'],
        'type' => 'Invoice',
        'date' => $bill['period_start'],
        'url' => 'tenant_portal_detail.php?tenant_id=' . $tenantId . '&print_invoice=' . (int) $bill['id'],
    ];
}

$tenantDisplayName = $tenant['brand_name'] ?: $tenant['tenant_name'];
$unpaidTotal = array_sum(array_map(function ($bill) {
    return $bill['status'] === 'Lunas' ? 0 : (float) $bill['total_amount'];
}, $bills));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Portal - <?= htmlspecialchars($tenantDisplayName) ?></title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root{
    /* colors */
    --primary: #0B376D; ;
    --primary-dark: #082A53;

    --secondary: #167E80;
    --secondary-dark: #0D4859;

    --accent: #00D4D8;
    --success: #22C55E;
    --danger: #EF4444;

    /* background */
    --background: #021F42;

    /* text colors */
    --text: #F5F7FA;
    --text-secondary: #B8C7D9;
    --text-accent: #FFB62A;

    /* Typography */
    --font-family: 'Poppins', sans-serif;
    --h1: 32px;
    --h2: 24px;
    --subheading: 20px;
    --body: 16px;
    --label: 14px;
    --caption: 12px;
}

/* =============================================
   MODULE STYLES - VERSI STABIL
   ============================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-family, 'Poppins', sans-serif);
    background: var(--background, #021F42);
    color: var(--text, #F5F7FA);
    min-height: 100vh;
}

/* ---- LAYOUT ---- */
.layout {
    display: flex;
    min-height: 100vh;
}

/* ---- SIDEBAR (DESKTOP) ---- */
.sidebar {
    width: 260px;
    background: var(--primary-dark, #082A53);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 24px 20px;
    font-size: 20px;
    font-weight: 700;
    color: var(--accent, #00D4D8);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar-section-label {
    font-size: 12px;
    color: rgba(245, 247, 250, 0.4);
    padding: 16px 20px 6px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 12px;
    flex: 1;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: rgba(245, 247, 250, 0.75);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text);
}

.nav-item.active {
    background: var(--secondary, #167E80);
    color: var(--text);
    font-weight: 600;
}

.sidebar-footer {
    padding: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* ---- MAIN CONTENT (DESKTOP) ---- */
.main-content {
    margin-left: 260px;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 32px;
    background: var(--primary, #0B376D);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: sticky;
    top: 0;
    z-index: 50;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.topbar-user {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--accent, #00D4D8);
    font-size: 14px;
}

.content-body {
    padding: 32px;
}

/* ---- TOMBOL HAMBURGER - SEMBUNYI DI DESKTOP ---- */
.menu-toggle {
    display: none;
}

/* ---- TOMBOL CLOSE DI SIDEBAR - SEMBUNYI DI DESKTOP ---- */
.sidebar-close {
    display: none;
}

/* ---- TABLET (max-width: 768px) ---- */
@media (max-width: 768px) {
    .sidebar {
        width: 240px;
    }
    
    .main-content {
        margin-left: 240px;
    }
    
    .topbar {
        padding: 12px 24px;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .content-body {
        padding: 24px;
    }
}

/* ---- MOBILE (max-width: 576px) ---- */
@media (max-width: 576px) {
    .sidebar {
        position: fixed;
        left: -280px;
        width: 280px;
        z-index: 1000;
        transition: left 0.3s ease;
    }
    
    .sidebar.open {
        left: 0;
        box-shadow: 2px 0 12px rgba(0, 0, 0, 0.3);
    }
    
    .main-content {
        margin-left: 0;
        width: 100%;
    }
    
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        min-height: 55px;
        position: relative;
    }
    
    .menu-toggle {
        display: flex !important;
        align-items: center;
        justify-content: center;
        background: var(--secondary, #167E80);
        border: none;
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        z-index: 1;
    }
    
    
    .menu-toggle i {
        font-size: 18px;
        color: var(--text);
    }
    
    .page-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        padding: 0;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        text-align: center;
    }
    
    .topbar-user {
        display: flex;
        align-items: center;
        gap: 6px;
        z-index: 1;
        background: var(--primary, #0B376D);
    }
    
    .topbar-user span {
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px; 
    }
    
    .content-body {
        padding: 16px;
    }
    
    .sidebar-close {
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: 12px;
        right: 12px;
        background: transparent;
        border: none;
        font-size: 20px;
        color: rgba(245, 247, 250, 0.6);
        cursor: pointer;
        z-index: 1002;
        width: 36px;
        height: 36px;
        border-radius: 50%;
    }
    
    .sidebar-close:hover {
        color: var(--accent, #00D4D8);
        background: rgba(255, 255, 255, 0.1);
    }
    
    body.sidebar-open .menu-toggle {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    
    /* Cards dan grid */
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .card {
        padding: 16px;
        margin-bottom: 16px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    table {
        min-width: 500px;
    }
    
    .table-wrap {
        overflow-x: auto;
    }
}

/* LAYAR SANGAT KECIL (max-width: 375px) */
@media (max-width: 375px) {
    .page-title {
        font-size: 13px;
    }
    
    .topbar-user {
        gap: 4px;
    }
    
    .topbar-user span {
        font-size: 11px;
        display: inline-block; 
    }
    
    .menu-toggle {
        padding: 6px 10px;
    }
    
    .menu-toggle i {
        font-size: 16px;
    }
}

/* ---- CARDS & UTILITY (tetap) ---- */
.card {
    background: var(--primary, #0B376D);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 16px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--primary, #0B376D);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border-left: 4px solid var(--text-accent, #FFB62A);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: rgba(0, 212, 216, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--accent, #00D4D8);
}

.stat-info h3 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.stat-info p {
    font-size: 12px;
    color: rgba(245, 247, 250, 0.6);
    margin: 0;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

thead th {
    background: var(--primary-dark, #082A53);
    padding: 12px 16px;
    text-align: left;
}

tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: rgba(34, 197, 94, 0.15);
    color: #22C55E;
}

.badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #EF4444;
}

.badge-warning {
    background: rgba(255, 182, 42, 0.15);
    color: #FFB62A;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background: var(--secondary, #167E80);
    color: var(--text);
}

.btn-primary:hover {
    background: #0D4859;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.form-group label {
    font-size: 14px;
    font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
    background: var(--primary-dark, #082A53);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--accent, #00D4D8);
}

/* =============================================
   GABRIEL M02 PAGE EXTENSION
   Mengikuti designSystem.css + template.css
   ============================================= */

.content-header {
    margin-bottom: 24px;
}

.content-header .breadcrumb {
    color: var(--accent);
    font-size: var(--caption, 12px);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 600;
    margin-bottom: 6px;
}

.content-header p {
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--label, 14px);
    line-height: 1.7;
    max-width: 880px;
}

.card-subtitle {
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--caption, 12px);
    margin-top: -8px;
    margin-bottom: 16px;
}

.toolbar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.toolbar input,
.toolbar select,
.search-input {
    background: var(--primary-dark, #082A53);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: 14px;
    outline: none;
    min-width: 240px;
}

.toolbar input:focus,
.toolbar select:focus,
.search-input:focus {
    border-color: var(--accent, #00D4D8);
}

.form-grid,
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 24px;
}

.form-grid .form-group-full,
.form-row .form-group-full {
    grid-column: 1 / -1;
}

.form-group textarea {
    min-height: 96px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 12px;
}

.btn-secondary {
    background: transparent;
    color: var(--text);
    border: 1px solid rgba(255, 255, 255, 0.22);
}

.btn-secondary:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.30);
}

.btn-danger:hover {
    background: var(--danger);
    color: var(--text);
}

.btn:disabled,
.btn-primary:disabled,
.btn-secondary:disabled,
.btn-danger:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.badge-info {
    background: rgba(0, 212, 216, 0.15);
    color: var(--accent);
}

.alert {
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 18px;
    font-size: var(--label, 14px);
    font-weight: 600;
}

.alert.success {
    background: rgba(34, 197, 94, 0.15);
    color: var(--success);
    border: 1px solid rgba(34, 197, 94, 0.30);
}

.alert.error {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.30);
}

.info-box,
.note,
.checklist {
    padding: 14px 16px;
    border-left: 4px solid var(--text-accent, #FFB62A);
    background: rgba(255, 182, 42, 0.08);
    border-radius: 0 8px 8px 0;
    color: var(--text-secondary, #B8C7D9);
    font-size: var(--label, 14px);
    line-height: 1.7;
    margin-bottom: 18px;
}

.checklist label {
    display: block;
    color: var(--text);
    margin-top: 10px;
    cursor: pointer;
}

.empty-row {
    text-align: center;
    padding: 36px 16px;
    color: var(--text-secondary, #B8C7D9);
}

.money,
.text-warning {
    color: var(--text-accent, #FFB62A);
    font-weight: 700;
}

.text-success {
    color: var(--success, #22C55E);
    font-weight: 700;
}

.tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.tab-button {
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: transparent;
    color: var(--text);
    border-radius: 8px;
    padding: 10px 16px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.tab-button.active {
    background: var(--secondary, #167E80);
    color: var(--text);
    border-color: var(--secondary, #167E80);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.print-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

@media print {
    .sidebar,
    .topbar,
    .no-print {
        display: none !important;
    }

    .main-content {
        margin-left: 0 !important;
    }

    body {
        background: #fff;
        color: #111;
    }

    .card {
        background: #fff;
        color: #111;
        border: 1px solid #ddd;
    }

    thead th {
        color: #111;
        background: #eee;
    }

    tbody td {
        color: #111;
    }
}

@media (max-width: 576px) {
    .form-row,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .toolbar input,
    .toolbar select,
    .search-input {
        width: 100%;
        min-width: unset;
    }

    .form-actions,
    .print-actions {
        flex-direction: column;
    }
}
    </style>
</head>
<body>
<div class="layout">

        <aside class="sidebar" id="sidebar">
            <button class="sidebar-close" type="button" onclick="toggleSidebar()">×</button>

            <div class="sidebar-brand">
                <span>🏢</span>
                <span>Mall ERP</span>
            </div>

            <div class="sidebar-section-label">M02 Module</div>

            <nav class="sidebar-nav">
                <a href="../leasingManager/terminasi_kontrakk.php" class="nav-item nav-terminasi">
                    <span>📝</span>
                    <span>Terminasi Kontrak</span>
                </a>

                <a href="../tenant/tenant_portal.php" class="nav-item nav-portal">
                    <span>👤</span>
                    <span>Tenant Portal</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="#" class="nav-item">
                    <span>↩</span>
                    <span>Back</span>
                </a>
            </div>
        </aside>


    <main class="main-content">

            <header class="topbar">
                <button class="menu-toggle" type="button" onclick="toggleSidebar()">
                    <span style="color: var(--text); font-size: 18px;">☰</span>
                </button>

                <h1 class="page-title">Tenant Portal</h1>

                <div class="topbar-user">
                    <span>Gabriel</span>
                    <span>●</span>
                </div>
            </header>


        <section class="content-body">
            <div class="content-header">
                <p class="breadcrumb">M02 Tenant Portal / PBI-M02-04-01 s.d. 04-03</p>
                <h2 class="card-title">Tenant Portal <?= htmlspecialchars($tenantDisplayName) ?></h2>
                <p>
                    Dashboard tagihan dan pembayaran, unduh invoice/dokumen kontrak,
                    serta pengajuan tiket keluhan berdasarkan tenant yang dipilih.
                </p>
                <br>
                <a href="tenant_portal_detail.php" class="btn btn-secondary">← Kembali ke Daftar Tenant</a>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert <?= htmlspecialchars($messageType) ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💳</div>
                    <div class="stat-info">
                        <h3><?= rupiah($unpaidTotal) ?></h3>
                        <p>Total Belum Lunas</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📁</div>
                    <div class="stat-info">
                        <h3><?= count($documents) ?></h3>
                        <p>Dokumen Tersedia</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🎫</div>
                    <div class="stat-info">
                        <h3><?= count($tickets) ?></h3>
                        <p>Total Tiket</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Profil Tenant</h2>
                        <p class="card-subtitle">Data tenant aktif yang sedang dibuka.</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <tr><th>Tenant</th><td><?= htmlspecialchars($tenantDisplayName) ?></td></tr>
                        <tr><th>Unit</th><td><?= htmlspecialchars($tenant['unit_code'] ?? '-') ?></td></tr>
                        <tr><th>Kategori</th><td><?= htmlspecialchars($tenant['category_name'] ?? '-') ?></td></tr>
                        <tr><th>No. Kontrak</th><td><?= htmlspecialchars($tenant['contract_number'] ?? '-') ?></td></tr>
                        <tr><th>Periode</th><td><?= htmlspecialchars($tenant['start_date'] ?? '-') ?> s.d. <?= htmlspecialchars($tenant['end_date'] ?? '-') ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="tabs">
                    <button class="tab-button active" type="button" onclick="openTab('tagihan', this)">Tagihan & Pembayaran</button>
                    <button class="tab-button" type="button" onclick="openTab('dokumen', this)">Invoice & Dokumen</button>
                    <button class="tab-button" type="button" onclick="openTab('keluhan', this)">Tiket Keluhan</button>
                </div>

                <div id="tagihan" class="tab-content active">
                    <div class="info-box">
                        Tenant dapat melihat tagihan dan melakukan pembayaran. Setelah pembayaran diproses,
                        status invoice berubah menjadi <strong>Lunas</strong> di database.
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. Invoice</th>
                                    <th>Periode</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bills)): ?>
                                    <tr>
                                        <td colspan="6" class="empty-row">Belum ada invoice untuk tenant ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bills as $bill): ?>
                                        <tr>
                                            <td class="td-bold"><?= htmlspecialchars($bill['invoice_number']) ?></td>
                                            <td><?= htmlspecialchars($bill['period_start']) ?> s.d. <?= htmlspecialchars($bill['period_end']) ?></td>
                                            <td><?= htmlspecialchars($bill['due_date']) ?></td>
                                            <td class="money"><?= rupiah($bill['total_amount']) ?></td>
                                            <td>
                                                <?php if ($bill['status'] === 'Lunas'): ?>
                                                    <span class="badge badge-success">Lunas</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning"><?= htmlspecialchars($bill['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($bill['status'] === 'Lunas'): ?>
                                                    <button class="btn btn-secondary" type="button" disabled>Selesai</button>
                                                <?php else: ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="form_token" value="<?= htmlspecialchars($formToken) ?>">
                                                        <input type="hidden" name="action" value="pay_invoice">
                                                        <input type="hidden" name="invoice_id" value="<?= (int) $bill['id'] ?>">
                                                        <input type="hidden" name="payment_method" value="Bank Transfer">
                                                        <button class="btn btn-primary" type="submit">Bayar</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="dokumen" class="tab-content">
                    <div class="info-box">
                        Tenant dapat membuka invoice dan dokumen kontrak untuk arsip pribadi.
                        Tombol unduh akan membuka halaman cetak yang dapat disimpan sebagai PDF.
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Dokumen</th>
                                    <th>No. Dokumen</th>
                                    <th>Jenis</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($documents)): ?>
                                    <tr>
                                        <td colspan="5" class="empty-row">Belum ada dokumen untuk tenant ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $document): ?>
                                        <tr>
                                            <td class="td-bold"><?= htmlspecialchars($document['document_name']) ?></td>
                                            <td><?= htmlspecialchars($document['document_no']) ?></td>
                                            <td><?= htmlspecialchars($document['type']) ?></td>
                                            <td><?= htmlspecialchars($document['date']) ?></td>
                                            <td>
                                                <a class="btn btn-secondary" href="<?= htmlspecialchars($document['url']) ?>" target="_blank">
                                                    Unduh
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="keluhan" class="tab-content">
                    <div class="info-box">
                        Tenant dapat mengajukan tiket keluhan. Data tersimpan ke tabel
                        <strong>02_tenant_complaints</strong> sesuai ID tenant yang sedang dibuka.
                    </div>

                    <form method="POST" id="complaintForm">
                        <input type="hidden" name="form_token" value="<?= htmlspecialchars($formToken) ?>">
                        <input type="hidden" name="action" value="create_complaint">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="severity_level">Prioritas <span class="required">*</span></label>
                                <select id="severity_level" name="severity_level" required>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="ticket_subject">Judul Keluhan <span class="required">*</span></label>
                                <input type="text" id="ticket_subject" name="ticket_subject" placeholder="Contoh: Lampu tenant mati" required>
                            </div>

                            <div class="form-group form-group-full">
                                <label for="ticket_description">Deskripsi Keluhan <span class="required">*</span></label>
                                <textarea id="ticket_description" name="ticket_description" placeholder="Tuliskan detail permasalahan yang dialami..." required></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="reset" class="btn btn-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary">Ajukan Tiket</button>
                        </div>
                    </form>

                    <br>

                    <div class="table-wrap">
                        <table>
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
                                        <td colspan="5" class="empty-row">Belum ada tiket keluhan untuk tenant ini.</td>
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
                                                <?php elseif ($ticket['status'] === 'Open'): ?>
                                                    <span class="badge badge-info">Open</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning"><?= htmlspecialchars($ticket['status']) ?></span>
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

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
    document.body.classList.toggle('sidebar-open');
}


function openTab(tabId, button) {
    document.querySelectorAll('.tab-content').forEach(function (content) {
        content.classList.remove('active');
    });

    document.querySelectorAll('.tab-button').forEach(function (tabButton) {
        tabButton.classList.remove('active');
    });

    document.getElementById(tabId).classList.add('active');
    button.classList.add('active');
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('.nav-portal').classList.add('active');

    document.querySelectorAll('form[method="POST"]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const submitButton = form.querySelector('button[type="submit"]');

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Memproses...';
            }
        });
    });
});
</script>
</body>
</html>
