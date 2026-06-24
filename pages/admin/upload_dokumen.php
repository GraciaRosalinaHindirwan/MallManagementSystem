<?php
session_start();
// require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php";

$page_title  = 'Upload Dokumen Legal';
$active_page = 'dokumen';
// $user_name   = $_SESSION['nama_lengkap'] ?? 'Guest';
// $role        = $_SESSION['role_user'] ?? 'tenant';
$user_name   = 'Admin';
$role        = 'admin';

/* ─────────────────────────────────────────────
   Helpers
───────────────────────────────────────────── */
$ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];
$ALLOWED_EXT   = ['pdf', 'jpg', 'jpeg', 'png'];
$MAX_SIZE      = 10 * 1024 * 1024; // 10 MB
$UPLOAD_DIR    = __DIR__ . '/../../documents/contracts/';

if (!is_dir($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0755, true);
}

/* ─────────────────────────────────────────────
   POST handler
───────────────────────────────────────────── */
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ── Hapus dokumen ── */
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $contract_id = (int)($_POST['contract_id'] ?? 0);
        if ($contract_id > 0) {
            $stmt = $conn->prepare("SELECT legal_document_url FROM 02_contracts WHERE id_contract = ?");
            $stmt->bind_param('i', $contract_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row && $row['legal_document_url']) {
                $file_path = __DIR__ . '/../../' . ltrim($row['legal_document_url'], '/');
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                $stmt = $conn->prepare("UPDATE 02_contracts SET legal_document_url = NULL WHERE id_contract = ?");
                $stmt->bind_param('i', $contract_id);
                $stmt->execute();
                $stmt->close();
                $flash = ['type' => 'success', 'msg' => 'Dokumen berhasil dihapus.'];
            } else {
                $flash = ['type' => 'warning', 'msg' => 'Tidak ada dokumen yang terdaftar pada kontrak ini.'];
            }
        }
    }

    /* ── Upload dokumen ── */
    elseif (isset($_POST['action']) && $_POST['action'] === 'upload') {
        $contract_id = (int)($_POST['contract_id'] ?? 0);

        if ($contract_id <= 0) {
            $flash = ['type' => 'error', 'msg' => 'Pilih kontrak terlebih dahulu.'];
        } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
            $flash = ['type' => 'error', 'msg' => 'Tidak ada file yang diunggah.'];
        } elseif ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $flash = ['type' => 'error', 'msg' => 'Upload gagal. Kode error: ' . $_FILES['document']['error']];
        } elseif ($_FILES['document']['size'] > $MAX_SIZE) {
            $flash = ['type' => 'error', 'msg' => 'Ukuran file melebihi batas 10 MB.'];
        } else {
            $file = $_FILES['document'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = mime_content_type($file['tmp_name']);

            if (!in_array($ext, $ALLOWED_EXT, true) || !in_array($mime, $ALLOWED_TYPES, true)) {
                $flash = ['type' => 'error', 'msg' => 'Tipe file tidak diizinkan. Gunakan PDF, JPG, atau PNG.'];
            } else {
                $stmt = $conn->prepare(
                    "SELECT id_contract, contract_number, legal_document_url FROM 02_contracts WHERE id_contract = ?"
                );
                $stmt->bind_param('i', $contract_id);
                $stmt->execute();
                $contract = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$contract) {
                    $flash = ['type' => 'error', 'msg' => 'Kontrak tidak ditemukan.'];
                } else {
                    if ($contract['legal_document_url']) {
                        $old = __DIR__ . '/../../' . ltrim($contract['legal_document_url'], '/');
                        if (file_exists($old)) {
                            unlink($old);
                        }
                    }

                    $safe_cn  = preg_replace('/[^A-Za-z0-9\-]/', '_', $contract['contract_number']);
                    $filename = $safe_cn . '_' . date('Ymd_His') . '.' . $ext;
                    $dest     = $UPLOAD_DIR . $filename;

                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $url  = '/documents/contracts/' . $filename;
                        $stmt = $conn->prepare("UPDATE 02_contracts SET legal_document_url = ? WHERE id_contract = ?");
                        $stmt->bind_param('si', $url, $contract_id);
                        $stmt->execute();
                        $stmt->close();
                        $flash = [
                            'type' => 'success',
                            'msg'  => 'Dokumen berhasil diunggah untuk kontrak ' . htmlspecialchars($contract['contract_number']) . '.',
                        ];
                    } else {
                        $flash = ['type' => 'error', 'msg' => 'Gagal menyimpan file ke server.'];
                    }
                }
            }
        }
    }
}

/* ─────────────────────────────────────────────
   Query: daftar kontrak
───────────────────────────────────────────── */
$filter_status = $_GET['status'] ?? '';
$search        = trim($_GET['search'] ?? '');
$where         = '1=1';
$params        = [];
$types         = '';

if ($filter_status !== '') {
    $where   .= ' AND c.contract_status = ?';
    $params[] = $filter_status;
    $types   .= 's';
}
if ($search !== '') {
    $like     = '%' . $search . '%';
    $where   .= ' AND (c.contract_number LIKE ? OR t.tenant_name LIKE ? OR t.brand_name LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

$sql = "
    SELECT c.id_contract, c.contract_number, c.contract_status,
           c.start_date, c.end_date, c.legal_document_url,
           t.tenant_name, t.brand_name,
           u.unit_code, cat.name AS category_name
    FROM 02_contracts c
    JOIN 02_tenants t  ON c.id_tenant = t.id_tenant
    JOIN 01_units u    ON c.id_unit   = u.id_units
    JOIN 01_tenant_categories cat ON t.id_category = cat.id_tenant_categories
    WHERE $where
    ORDER BY c.id_contract DESC
";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$contracts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_contracts = count($contracts);
$with_doc        = count(array_filter($contracts, fn($c) => !empty($c['legal_document_url'])));
$without_doc     = $total_contracts - $with_doc;

$all_rs = $conn->query(
    "SELECT c.id_contract, c.contract_number, t.brand_name
     FROM 02_contracts c JOIN 02_tenants t ON c.id_tenant = t.id_tenant
     ORDER BY c.id_contract DESC"
);
$all_contracts = $all_rs->fetch_all(MYSQLI_ASSOC);

$statuses = ['Draft', 'Waiting Approval', 'Active', 'Amended', 'Expired', 'Terminated'];

ob_start();
?>

<?php if ($flash): ?>
<div id="flashMsg" class="doc-flash doc-flash-<?= htmlspecialchars($flash['type']) ?>">
    <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-x-circle-fill') ?>"></i>
    <span><?= htmlspecialchars($flash['msg']) ?></span>
    <button type="button" class="doc-flash-close" onclick="this.parentElement.remove()" aria-label="Tutup">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
<?php endif; ?>

<style>
.doc-page { max-width: 1200px; margin: 0 auto; }

/* Flash message */
.doc-flash {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    border-radius: 10px;
    margin-bottom: 24px;
    font-size: var(--label);
    font-weight: 500;
}
.doc-flash i:first-child { font-size: 18px; flex-shrink: 0; }
.doc-flash span { flex: 1; }
.doc-flash-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); color: #86efac; }
.doc-flash-warning { background: rgba(255,182,42,0.12); border: 1px solid rgba(255,182,42,0.3); color: var(--text-accent); }
.doc-flash-error   { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
.doc-flash-close { background: none; border: none; color: inherit; font-size: 14px; cursor: pointer; opacity: .6; flex-shrink: 0; padding: 4px; }
.doc-flash-close:hover { opacity: 1; }

/* Header */
.doc-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 28px;
}
.doc-page-header h1 { font-size: var(--h2); font-weight: 700; color: var(--text); margin: 0 0 4px; }
.doc-page-header p { font-size: var(--label); color: var(--text-secondary); margin: 0; }
.doc-pbi-badge {
    font-size: 10px;
    background: rgba(0,212,216,0.12);
    color: var(--accent);
    border: 1px solid rgba(0,212,216,0.25);
    border-radius: 20px;
    padding: 3px 11px;
    font-weight: 600;
    letter-spacing: .05em;
    display: inline-block;
    margin-bottom: 8px;
}

/* Stat cards */
.doc-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.doc-stat-card {
    background: var(--primary);
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-left: 4px solid transparent;
}
.doc-stat-card.total { border-left-color: var(--accent); }
.doc-stat-card.done  { border-left-color: var(--success); }
.doc-stat-card.miss  { border-left-color: var(--danger); }
.doc-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.doc-stat-card.total .doc-stat-icon { background: rgba(0,212,216,0.12); color: var(--accent); }
.doc-stat-card.done  .doc-stat-icon { background: rgba(34,197,94,0.12); color: var(--success); }
.doc-stat-card.miss  .doc-stat-icon { background: rgba(239,68,68,0.12); color: var(--danger); }
.doc-stat-value { font-size: 26px; font-weight: 700; line-height: 1.1; }
.doc-stat-label { font-size: var(--caption); color: var(--text-secondary); margin-top: 2px; }

/* Grid layout */
.doc-grid {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 20px;
    align-items: start;
}
.doc-panel {
    background: var(--primary);
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.07);
    overflow: hidden;
}
.doc-panel-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    font-size: var(--label);
    font-weight: 600;
    color: var(--accent);
    text-transform: uppercase;
    letter-spacing: .07em;
}
.doc-panel-body { padding: 20px; }

/* Form */
.doc-form-group { margin-bottom: 18px; }
.doc-form-label { display: block; font-size: var(--label); font-weight: 500; margin-bottom: 7px; color: var(--text); }
.doc-form-label span { color: var(--danger); margin-left: 2px; }
.doc-form-select {
    width: 100%;
    background: var(--primary-dark);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 8px;
    padding: 10px 14px;
    color: var(--text);
    font-size: var(--label);
    font-family: var(--font-family);
    outline: none;
    transition: border-color .15s;
    -webkit-appearance: none;
}
.doc-form-select:focus { border-color: var(--accent); }
.doc-form-select option { background: var(--primary-dark); }

.doc-dropzone {
    position: relative;
    border: 2px dashed rgba(0,212,216,0.25);
    border-radius: 10px;
    background: rgba(0,212,216,0.04);
    padding: 28px 16px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.doc-dropzone:hover,
.doc-dropzone.drag-over { border-color: var(--accent); background: rgba(0,212,216,0.08); }
.doc-dropzone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.doc-dropzone-icon { font-size: 32px; margin-bottom: 10px; color: var(--accent); opacity: .7; }
.doc-dropzone-text { font-size: var(--label); color: var(--text-secondary); }
.doc-dropzone-hint { font-size: var(--caption); color: rgba(184,199,217,0.45); margin-top: 5px; }
.doc-file-preview {
    display: none;
    align-items: center;
    gap: 10px;
    margin-top: 12px;
    background: var(--primary-dark);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: var(--caption);
    color: var(--text-secondary);
    word-break: break-all;
}
.doc-file-preview.visible { display: flex; }
.doc-file-preview i { font-size: 18px; flex-shrink: 0; color: var(--accent); }

.doc-btn-submit {
    width: 100%;
    background: linear-gradient(135deg, var(--accent), var(--secondary));
    color: var(--primary-dark);
    font-weight: 700;
    font-size: var(--label);
    font-family: var(--font-family);
    border: none;
    border-radius: 10px;
    padding: 12px;
    cursor: pointer;
    transition: opacity .15s, transform .1s;
}
.doc-btn-submit:hover { opacity: .9; transform: translateY(-1px); }

.doc-rules { font-size: var(--caption); color: var(--text-secondary); line-height: 1.7; padding-left: 18px; margin: 0; }
.doc-rules li { margin-bottom: 4px; }

/* Filter bar */
.doc-filter-bar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.doc-filter-select,
.doc-filter-input {
    background: var(--primary-dark);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 8px;
    padding: 8px 14px;
    color: var(--text);
    font-size: var(--label);
    font-family: var(--font-family);
    outline: none;
}
.doc-filter-input { flex: 1; min-width: 160px; }
.doc-filter-input::placeholder { color: rgba(184,199,217,0.4); }
.doc-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--secondary);
    color: var(--text);
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: var(--label);
    font-family: var(--font-family);
    font-weight: 600;
    cursor: pointer;
    transition: opacity .15s;
    white-space: nowrap;
    text-decoration: none;
}
.doc-filter-btn:hover { opacity: .85; }
.doc-filter-btn.reset { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); }

/* Table */
.doc-table-wrap { overflow-x: auto; }
.doc-table { width: 100%; border-collapse: collapse; font-size: var(--label); min-width: 760px; }
.doc-table thead th {
    background: var(--primary-dark);
    color: var(--text);
    font-weight: 600;
    padding: 12px 16px;
    text-align: left;
    white-space: nowrap;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.doc-table tbody td { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
.doc-table tbody tr:hover td { background: rgba(255,255,255,0.025); }
.doc-table tbody tr:last-child td { border-bottom: none; }

.doc-status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: var(--caption); font-weight: 600; }
.doc-status.Active            { background: rgba(34,197,94,0.15); color: #86efac; }
.doc-status.Draft             { background: rgba(255,255,255,0.08); color: var(--text-secondary); }
.doc-status.Expired           { background: rgba(239,68,68,0.15); color: #fca5a5; }
.doc-status.Terminated        { background: rgba(239,68,68,0.12); color: #fca5a5; }
.doc-status.Amended           { background: rgba(255,182,42,0.15); color: var(--text-accent); }
.doc-status.Waiting-Approval  { background: rgba(0,212,216,0.12); color: var(--accent); }

.doc-has-doc { display: inline-flex; align-items: center; gap: 6px; font-size: var(--caption); }
.doc-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.doc-dot.filled { background: var(--success); }
.doc-dot.empty  { background: rgba(255,255,255,0.2); }

.doc-action-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.doc-btn-view,
.doc-btn-select,
.doc-btn-delete {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border-radius: 6px;
    padding: 5px 12px;
    font-size: var(--caption);
    font-family: var(--font-family);
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s;
    border: 1px solid transparent;
}
.doc-btn-view   { background: rgba(0,212,216,0.1); border-color: rgba(0,212,216,0.25); color: var(--accent); }
.doc-btn-view:hover { background: rgba(0,212,216,0.2); color: var(--accent); }
.doc-btn-select { background: rgba(255,182,42,0.1); border-color: rgba(255,182,42,0.25); color: var(--text-accent); }
.doc-btn-select:hover { background: rgba(255,182,42,0.2); }
.doc-btn-delete { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.25); color: #fca5a5; padding: 5px 10px; }
.doc-btn-delete:hover { background: rgba(239,68,68,0.2); }

.doc-empty { text-align: center; padding: 48px 24px; color: var(--text-secondary); font-size: var(--label); opacity: .55; }
.doc-empty-icon { font-size: 40px; margin-bottom: 12px; color: rgba(184,199,217,0.4); }

.doc-table-footnote { margin-top: 12px; font-size: var(--caption); color: rgba(184,199,217,0.4); }

/* Modal */
.doc-modal-bg {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(4px);
    z-index: 900;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.doc-modal-bg.open { display: flex; }
.doc-modal { background: var(--primary); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 28px 28px 24px; max-width: 400px; width: 100%; text-align: center; }
.doc-modal-icon { font-size: 36px; margin-bottom: 12px; color: var(--danger); }
.doc-modal h3 { font-size: var(--subheading); font-weight: 700; margin-bottom: 8px; }
.doc-modal p { font-size: var(--label); color: var(--text-secondary); margin-bottom: 20px; }
.doc-modal-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.doc-modal-cancel {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: var(--text);
    border-radius: 8px;
    padding: 9px 22px;
    font-size: var(--label);
    font-family: var(--font-family);
    cursor: pointer;
}
.doc-modal-confirm {
    background: var(--danger);
    border: none;
    color: #fff;
    border-radius: 8px;
    padding: 9px 22px;
    font-size: var(--label);
    font-family: var(--font-family);
    font-weight: 700;
    cursor: pointer;
    transition: opacity .15s;
}
.doc-modal-confirm:hover { opacity: .85; }

/* ───────── Responsive ───────── */
@media (max-width: 992px) {
    .doc-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .doc-stats { grid-template-columns: 1fr; }
    .doc-page-header { flex-direction: column; }
    .doc-panel-body { padding: 16px; }
    .doc-filter-bar { flex-direction: column; }
    .doc-filter-input { min-width: 0; width: 100%; }
    .doc-filter-select,
    .doc-filter-btn { width: 100%; justify-content: center; }
    .doc-action-row { flex-direction: column; align-items: stretch; }
    .doc-btn-view, .doc-btn-select, .doc-btn-delete { justify-content: center; }
}
</style>

<div class="doc-page">

    <div class="doc-page-header">
        <div>
            <div class="doc-pbi-badge">PBI-M02-02-03</div>
            <h1>Upload Dokumen Legal Kontrak</h1>
            <p>Unggah dan kelola arsip dokumen legal untuk setiap kontrak sewa tenant.</p>
        </div>
    </div>

    <div class="doc-stats">
        <div class="doc-stat-card total">
            <div class="doc-stat-icon"><i class="bi bi-folder2-open"></i></div>
            <div>
                <div class="doc-stat-value"><?= $total_contracts ?></div>
                <div class="doc-stat-label">Total Kontrak Ditampilkan</div>
            </div>
        </div>
        <div class="doc-stat-card done">
            <div class="doc-stat-icon"><i class="bi bi-file-earmark-check"></i></div>
            <div>
                <div class="doc-stat-value"><?= $with_doc ?></div>
                <div class="doc-stat-label">Sudah Ada Dokumen</div>
            </div>
        </div>
        <div class="doc-stat-card miss">
            <div class="doc-stat-icon"><i class="bi bi-file-earmark-x"></i></div>
            <div>
                <div class="doc-stat-value"><?= $without_doc ?></div>
                <div class="doc-stat-label">Belum Ada Dokumen</div>
            </div>
        </div>
    </div>

    <div class="doc-grid">

        <!-- Upload form -->
        <div>
            <div class="doc-panel">
                <div class="doc-panel-header"><i class="bi bi-upload"></i> Unggah Dokumen</div>
                <div class="doc-panel-body">
                    <form method="POST" enctype="multipart/form-data" id="uploadForm">
                        <input type="hidden" name="action" value="upload">

                        <div class="doc-form-group">
                            <label class="doc-form-label" for="contract_id">
                                Pilih Kontrak <span>*</span>
                            </label>
                            <select name="contract_id" id="contract_id" class="doc-form-select" required>
                                <option value="">— Pilih nomor kontrak —</option>
                                <?php foreach ($all_contracts as $c): ?>
                                <option value="<?= $c['id_contract'] ?>">
                                    <?= htmlspecialchars($c['contract_number']) ?> — <?= htmlspecialchars($c['brand_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="doc-form-group">
                            <label class="doc-form-label">File Dokumen <span>*</span></label>
                            <div class="doc-dropzone" id="dropzone"
                                 ondragover="event.preventDefault();this.classList.add('drag-over')"
                                 ondragleave="this.classList.remove('drag-over')"
                                 ondrop="handleDrop(event)">
                                <input type="file" name="document" id="fileInput"
                                       accept=".pdf,.jpg,.jpeg,.png"
                                       onchange="showPreview(this)">
                                <div class="doc-dropzone-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                                <div class="doc-dropzone-text">Klik atau seret file ke sini</div>
                                <div class="doc-dropzone-hint">PDF, JPG, PNG — Maks. 10 MB</div>
                            </div>
                            <div class="doc-file-preview" id="filePreview">
                                <i class="bi bi-file-earmark" id="previewIcon"></i>
                                <span id="previewName">—</span>
                            </div>
                        </div>

                        <button type="submit" class="doc-btn-submit">Unggah Dokumen</button>
                    </form>
                </div>
            </div>

            <div class="doc-panel" style="margin-top:16px">
                <div class="doc-panel-header"><i class="bi bi-info-circle"></i> Ketentuan</div>
                <div class="doc-panel-body">
                    <ul class="doc-rules">
                        <li>Format yang diterima: <strong style="color:var(--text)">PDF, JPG, PNG</strong></li>
                        <li>Ukuran maksimal: <strong style="color:var(--text)">10 MB</strong></li>
                        <li>Mengunggah dokumen baru akan <strong style="color:var(--text-accent)">menimpa</strong> dokumen lama</li>
                        <li>Nama file distandarisasi otomatis berdasarkan nomor kontrak</li>
                        <li>Klik tombol <strong style="color:var(--text-accent)">Pilih</strong> pada baris tabel untuk pre-fill form</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tabel kontrak -->
        <div class="doc-panel">
            <div class="doc-panel-header"><i class="bi bi-card-list"></i> Daftar Kontrak &amp; Arsip Dokumen</div>
            <div class="doc-panel-body">

                <form method="GET" class="doc-filter-bar">
                    <input type="text" name="search" class="doc-filter-input"
                           placeholder="Cari no. kontrak / nama tenant..."
                           value="<?= htmlspecialchars($search) ?>">
                    <select name="status" class="doc-filter-select">
                        <option value="">Semua Status</option>
                        <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $filter_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="doc-filter-btn"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search !== '' || $filter_status !== ''): ?>
                    <a href="upload_dokumen.php" class="doc-filter-btn reset"><i class="bi bi-x-circle"></i> Reset</a>
                    <?php endif; ?>
                </form>

                <div class="doc-table-wrap">
                    <?php if (empty($contracts)): ?>
                    <div class="doc-empty">
                        <div class="doc-empty-icon"><i class="bi bi-search"></i></div>
                        Tidak ada kontrak yang sesuai filter.
                    </div>
                    <?php else: ?>
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>No. Kontrak</th>
                                <th>Tenant / Brand</th>
                                <th>Unit</th>
                                <th>Status</th>
                                <th>Periode</th>
                                <th>Dokumen</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($contracts as $c): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--accent);font-size:var(--caption)">
                                    <?= htmlspecialchars($c['contract_number']) ?>
                                </strong>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:var(--label)"><?= htmlspecialchars($c['brand_name']) ?></div>
                                <div style="font-size:var(--caption);color:var(--text-secondary)"><?= htmlspecialchars($c['tenant_name']) ?></div>
                            </td>
                            <td>
                                <span style="font-size:var(--caption);background:rgba(255,255,255,0.06);padding:3px 9px;border-radius:6px">
                                    <?= htmlspecialchars($c['unit_code']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="doc-status <?= htmlspecialchars(str_replace(' ', '-', $c['contract_status'])) ?>">
                                    <?= htmlspecialchars($c['contract_status']) ?>
                                </span>
                            </td>
                            <td style="font-size:var(--caption);color:var(--text-secondary);white-space:nowrap">
                                <?= date('d M Y', strtotime($c['start_date'])) ?><br>
                                s/d <?= date('d M Y', strtotime($c['end_date'])) ?>
                            </td>
                            <td>
                                <?php if ($c['legal_document_url']): ?>
                                <div class="doc-has-doc">
                                    <span class="doc-dot filled"></span>
                                    <span style="color:var(--success);font-weight:600;font-size:var(--caption)">Ada</span>
                                </div>
                                <?php else: ?>
                                <div class="doc-has-doc">
                                    <span class="doc-dot empty"></span>
                                    <span style="font-size:var(--caption);opacity:.5">Kosong</span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="doc-action-row">
                                    <button type="button" class="doc-btn-select"
                                            onclick="selectContract(<?= $c['id_contract'] ?>)">
                                        <i class="bi bi-arrow-up-circle"></i> Pilih
                                    </button>
                                    <?php if ($c['legal_document_url']): ?>
                                    <a href="<?= htmlspecialchars($c['legal_document_url']) ?>"
                                       target="_blank" rel="noopener" class="doc-btn-view">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                    <button type="button" class="doc-btn-delete"
                                            onclick="confirmDelete(<?= $c['id_contract'] ?>, '<?= addslashes($c['contract_number']) ?>')">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <?php if (!empty($contracts)): ?>
                <div class="doc-table-footnote">
                    Menampilkan <?= count($contracts) ?> kontrak<?= ($search || $filter_status) ? ' (hasil filter)' : '' ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete modal -->
<div class="doc-modal-bg" id="deleteModal">
    <div class="doc-modal">
        <div class="doc-modal-icon"><i class="bi bi-trash3"></i></div>
        <h3>Hapus Dokumen?</h3>
        <p id="deleteModalDesc">Dokumen pada kontrak ini akan dihapus permanen dari server.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="contract_id" id="deleteContractId">
            <div class="doc-modal-actions">
                <button type="button" class="doc-modal-cancel" onclick="closeDeleteModal()">Batal</button>
                <button type="submit" class="doc-modal-confirm">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function showPreview(input) {
    var preview = document.getElementById('filePreview');
    var name    = document.getElementById('previewName');
    var icon    = document.getElementById('previewIcon');
    if (input.files && input.files[0]) {
        var file = input.files[0];
        var ext  = file.name.split('.').pop().toLowerCase();
        icon.className = ext === 'pdf' ? 'bi bi-file-earmark-pdf' : 'bi bi-file-earmark-image';
        name.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
        preview.classList.add('visible');
    } else {
        preview.classList.remove('visible');
    }
}

function handleDrop(e) {
    e.preventDefault();
    document.getElementById('dropzone').classList.remove('drag-over');
    var dt    = e.dataTransfer;
    var input = document.getElementById('fileInput');
    if (dt.files.length) {
        var transfer = new DataTransfer();
        transfer.items.add(dt.files[0]);
        input.files = transfer.files;
        showPreview(input);
    }
}

function selectContract(id) {
    var select = document.getElementById('contract_id');
    select.value = id;
    select.closest('.doc-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
    select.style.borderColor = 'var(--accent)';
    select.style.boxShadow   = '0 0 0 3px rgba(0,212,216,0.2)';
    setTimeout(function () {
        select.style.borderColor = '';
        select.style.boxShadow   = '';
    }, 1800);
}

function confirmDelete(contractId, contractNumber) {
    document.getElementById('deleteContractId').value = contractId;
    document.getElementById('deleteModalDesc').textContent =
        'Dokumen pada kontrak ' + contractNumber + ' akan dihapus permanen dari server.';
    document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('open');
}

document.getElementById('deleteModal').addEventListener('click', function (e) {
    if (e.target === this) closeDeleteModal();
});

(function () {
    var msg = document.getElementById('flashMsg');
    if (msg) {
        setTimeout(function () {
            msg.style.transition = 'opacity 0.5s';
            msg.style.opacity    = '0';
            setTimeout(function () { msg.remove(); }, 500);
        }, 4500);
    }
})();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarM02.php';
?>
        <div class="m02-content">
            <?= $content ?>
        </div>
        <footer class="m02-footer">
            Mall ERP &middot; M02 Tenant &amp; Leasing Management &middot; PBI-M02-02-03
        </footer>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</body>
</html>