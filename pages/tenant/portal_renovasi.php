<?php
require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php";

$user_id = $_SESSION['user_id'];

$tenantQuery = mysqli_query($conn,
    "SELECT id_tenant, brand_name FROM `02_tenants` WHERE user_id = $user_id LIMIT 1"
);

if (!$tenantQuery || mysqli_num_rows($tenantQuery) === 0) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Akses Ditolak</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
            body { font-family: 'Poppins', sans-serif; background: #021F42; color: #F5F7FA;
                   display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .err-box { background: #0B376D; border: 1px solid rgba(239,68,68,0.4); border-left: 4px solid #EF4444;
                       border-radius: 12px; padding: 40px 48px; max-width: 480px; text-align: center; }
            .err-icon { font-size: 48px; margin-bottom: 16px; }
            .err-title { font-size: 20px; font-weight: 700; color: #EF4444; margin-bottom: 8px; }
            .err-msg { font-size: 14px; color: #B8C7D9; line-height: 1.6; }
        </style>
    </head>
    <body>
        <div class="err-box">
            <div class="err-title">Akses Ditolak</div>
            <p class="err-msg">Akun Anda belum dihubungkan ke data tenant.<br>
               Hubungi Leasing Manager untuk menghubungkan akun ini ke data tenant yang sesuai.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$tenantData = mysqli_fetch_assoc($tenantQuery);
$id_tenant  = (int) $tenantData['id_tenant'];
$brand_name = $tenantData['brand_name'];

$page_title  = 'Permohonan Renovasi';
$active_page = 'portal-renovasi';
$user_name   = $_SESSION['username'] ?? 'Tenant';
$role        = 'tenant';

require_once "../../includes/navbarM02.php";

$contractResult = mysqli_query($conn,
    "SELECT c.id_contract, c.contract_number, u.unit_code
     FROM `02_contracts` c
     JOIN `01_units` u ON u.id_units = c.id_unit
     WHERE c.id_tenant = $id_tenant AND c.contract_status = 'Active'
     ORDER BY u.unit_code"
);

$alertMsg  = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_contract = (int) ($_POST['id_contract'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $start_date  = $_POST['proposed_start_date'] ?? '';
    $end_date    = $_POST['proposed_end_date']   ?? '';
    $file        = $_FILES['attachment_plan']    ?? null;

    $cekKontrak = mysqli_fetch_row(mysqli_query($conn,
        "SELECT COUNT(*) FROM `02_contracts`
         WHERE id_contract = $id_contract AND id_tenant = $id_tenant AND contract_status = 'Active'"
    ))[0];

    if ($cekKontrak == 0) {
        $alertMsg  = 'Kontrak tidak valid atau bukan milik akun Anda.';
        $alertType = 'danger';
    } elseif (empty($description)) {
        $alertMsg  = 'Deskripsi rencana renovasi wajib diisi.';
        $alertType = 'danger';
    } elseif (empty($start_date) || empty($end_date)) {
        $alertMsg  = 'Tanggal mulai dan selesai wajib diisi.';
        $alertType = 'danger';
    } elseif ($end_date <= $start_date) {
        $alertMsg  = 'Tanggal selesai harus setelah tanggal mulai.';
        $alertType = 'danger';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $alertMsg  = 'Dokumen rencana renovasi wajib diunggah.';
        $alertType = 'danger';
    } elseif (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['pdf','jpg','jpeg','png'], true)) {
        $alertMsg  = 'Format dokumen tidak valid. Gunakan PDF, JPG, JPEG, atau PNG.';
        $alertType = 'danger';
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $alertMsg  = 'Ukuran dokumen melebihi batas 5 MB.';
        $alertType = 'danger';
    } else {
        /* 2. Cek duplikasi: apakah unit ini masih punya pengajuan Pending/In Review */
        $cekPending = mysqli_fetch_row(mysqli_query($conn,
            "SELECT COUNT(*) FROM `02_tenant_renovations`
             WHERE id_contract = $id_contract AND status IN ('Pending','In Review')"
        ))[0];

        if ($cekPending > 0) {
            $alertMsg  = 'Unit ini masih memiliki pengajuan renovasi yang sedang diproses. Tunggu hingga selesai sebelum mengajukan yang baru.';
            $alertType = 'warning';
        } else {
            $uploadDir = "../../uploads/renovasi/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'renov_' . $id_contract . '_' . time() . '.' . $ext;

            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $alertMsg  = 'Gagal mengunggah dokumen. Silakan coba lagi.';
                $alertType = 'danger';
            } else {
                $attachmentPath = '/MallManagementSystem/uploads/renovasi/' . $filename;

                $stmt = mysqli_prepare($conn,
                    "INSERT INTO `02_tenant_renovations`
                         (id_contract, description, proposed_start_date, proposed_end_date, attachment_plan_url, status)
                     VALUES (?, ?, ?, ?, ?, 'Pending')"
                );
                mysqli_stmt_bind_param($stmt, 'issss',
                    $id_contract, $description, $start_date, $end_date, $attachmentPath
                );

                if (mysqli_stmt_execute($stmt)) {
                    $alertMsg  = 'Pengajuan renovasi berhasil dikirim! Tim Leasing akan segera meninjau permohonan Anda.';
                    $alertType = 'success';
                    /* Re-query contract list setelah insert agar dropdown tidak stale */
                    mysqli_data_seek($contractResult, 0);
                } else {
                    $alertMsg  = 'Gagal menyimpan data: ' . mysqli_error($conn);
                    $alertType = 'danger';
                }
            }
        }
    }
}

$statTotal    = (int) mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM `02_tenant_renovations` r
     JOIN `02_contracts` c ON c.id_contract = r.id_contract
     WHERE c.id_tenant = $id_tenant"))[0];

$statProses   = (int) mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM `02_tenant_renovations` r
     JOIN `02_contracts` c ON c.id_contract = r.id_contract
     WHERE c.id_tenant = $id_tenant AND r.status IN ('Pending','In Review')"))[0];

$statApproved = (int) mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM `02_tenant_renovations` r
     JOIN `02_contracts` c ON c.id_contract = r.id_contract
     WHERE c.id_tenant = $id_tenant AND r.status = 'Approved'"))[0];

$riwayatResult = mysqli_query($conn,
    "SELECT r.id_renovation, r.description, r.proposed_start_date, r.proposed_end_date,
            r.attachment_plan_url, r.status, c.contract_number, u.unit_code
     FROM `02_tenant_renovations` r
     JOIN `02_contracts` c ON c.id_contract = r.id_contract
     JOIN `01_units` u ON u.id_units = c.id_unit
     WHERE c.id_tenant = $id_tenant
     ORDER BY r.id_renovation DESC"
);

function badgeRenovasi(string $status): string {
    $map = [
        'Pending'   => 'badge--pending',
        'In Review' => 'badge--in-review',
        'Approved'  => 'badge--approved',
        'Rejected'  => 'badge--rejected',
    ];
    $cls = $map[$status] ?? 'badge--pending';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($status) . '</span>';
}
?>

<style>
    .ren-wrap {
        padding: 28px 32px;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .ren-breadcrumb {
        font-size: var(--caption, 12px);
        color: var(--accent, #00D4D8);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 4px;
    }
    .ren-page-title {
        font-size: var(--h2, 24px);
        font-weight: 700;
        color: var(--text, #F5F7FA);
    }
    .ren-page-sub {
        font-size: var(--label, 14px);
        color: var(--text-secondary, #B8C7D9);
        margin-top: 2px;
    }

    .ren-alert {
        border-radius: 10px;
        padding: 14px 18px;
        font-size: var(--label, 14px);
        display: flex;
        align-items: flex-start;
        gap: 10px;
        border: 1px solid transparent;
    }
    .ren-alert--success {
        background: rgba(34,197,94,0.1);
        border-color: rgba(34,197,94,0.35);
        color: var(--success, #22C55E);
    }
    .ren-alert--warning {
        background: rgba(255,182,42,0.1);
        border-color: rgba(255,182,42,0.35);
        color: var(--text-accent, #FFB62A);
    }
    .ren-alert--danger {
        background: rgba(239,68,68,0.1);
        border-color: rgba(239,68,68,0.35);
        color: var(--danger, #EF4444);
    }
    .ren-alert-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

    .ren-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .ren-stat-card {
        background: var(--primary, #0B376D);
        border: 1px solid rgba(0,212,216,0.15);
        border-radius: 12px;
        padding: 20px 24px;
    }
    .ren-stat-label {
        font-size: var(--caption, 12px);
        color: var(--accent, #00D4D8);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 6px;
    }
    .ren-stat-value {
        font-size: var(--h2, 24px);
        font-weight: 700;
    }

    .ren-card {
        background: var(--primary, #0B376D);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 12px;
        overflow: hidden;
    }
    .ren-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .ren-card-title {
        font-size: var(--subheading, 20px);
        font-weight: 600;
    }

    .ren-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px 24px;
        padding: 24px;
    }
    .ren-full { grid-column: 1 / -1; }

    .ren-form-group { display: flex; flex-direction: column; gap: 6px; }
    .ren-form-label {
        font-size: var(--label, 14px);
        font-weight: 500;
        color: rgba(245,247,250,0.85);
    }
    .ren-required { color: var(--danger, #EF4444); }

    .ren-input {
        background: var(--background, #021F42);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 8px;
        padding: 10px 14px;
        color: var(--text, #F5F7FA);
        font-family: var(--font-family, 'Poppins', sans-serif);
        font-size: var(--label, 14px);
        outline: none;
        transition: border-color 0.15s;
        width: 100%;
    }
    .ren-input:focus { border-color: var(--accent, #00D4D8); }
    .ren-input option { background: #0B376D; }
    .ren-textarea { resize: vertical; min-height: 90px; }

    .ren-hint {
        font-size: var(--caption, 12px);
        color: rgba(184,199,217,0.6);
        margin-top: 2px;
    }

    .ren-form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        padding: 0 24px 24px;
    }

    .ren-btn-primary {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--accent, #00D4D8);
        color: var(--background, #021F42);
        border: none; border-radius: 8px;
        padding: 10px 22px;
        font-family: inherit; font-size: var(--label, 14px); font-weight: 700;
        cursor: pointer; transition: opacity 0.15s;
    }
    .ren-btn-primary:hover { opacity: 0.85; }

    .ren-btn-secondary {
        background: transparent;
        color: var(--text, #F5F7FA);
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 8px;
        padding: 10px 22px;
        font-family: inherit; font-size: var(--label, 14px);
        cursor: pointer; transition: border-color 0.15s;
    }
    .ren-btn-secondary:hover { border-color: var(--accent, #00D4D8); }

    .ren-search {
        background: var(--background, #021F42);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 8px;
        padding: 8px 14px;
        color: var(--text, #F5F7FA);
        font-family: inherit; font-size: var(--label, 14px);
        width: 230px; outline: none;
        transition: border-color 0.15s;
    }
    .ren-search:focus { border-color: var(--accent, #00D4D8); }

    .ren-table-wrap { overflow-x: auto; }
    .ren-table {
        width: 100%;
        border-collapse: collapse;
        font-size: var(--label, 14px);
    }
    .ren-table thead tr { background: var(--primary-dark, #082A53); }
    .ren-table th {
        padding: 12px 16px; text-align: left;
        font-weight: 600; font-size: var(--caption, 12px);
        text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--accent, #00D4D8); white-space: nowrap;
    }
    .ren-table td {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        vertical-align: middle;
    }
    .ren-table tbody tr:hover { background: rgba(0,212,216,0.04); }
    .ren-table .td-bold { font-weight: 600; }

    .ren-empty {
        text-align: center; padding: 48px 16px;
        color: rgba(245,247,250,0.4);
        font-size: var(--label, 14px);
    }

    .badge {
        display: inline-block; padding: 3px 10px;
        border-radius: 99px; font-size: var(--caption, 12px);
        font-weight: 600; white-space: nowrap;
    }
    .badge--pending   { background: rgba(255,182,42,0.15); color: var(--text-accent, #FFB62A); border: 1px solid rgba(255,182,42,0.3); }
    .badge--in-review { background: rgba(0,212,216,0.15);  color: var(--accent, #00D4D8);      border: 1px solid rgba(0,212,216,0.3); }
    .badge--approved  { background: rgba(34,197,94,0.15);  color: var(--success, #22C55E);     border: 1px solid rgba(34,197,94,0.3); }
    .badge--rejected  { background: rgba(239,68,68,0.15);  color: var(--danger, #EF4444);      border: 1px solid rgba(239,68,68,0.3); }

    .ren-doc-link {
        padding: 4px 12px; border-radius: 6px; font-size: var(--caption, 12px);
        font-weight: 500; text-decoration: none;
        background: rgba(0,212,216,0.12); color: var(--accent, #00D4D8);
        border: 1px solid rgba(0,212,216,0.3);
        transition: opacity 0.15s;
    }
    .ren-doc-link:hover { opacity: 0.75; }

    @media (max-width: 768px) {
        .ren-wrap          { padding: 16px; }
        .ren-stats         { grid-template-columns: 1fr; }
        .ren-form-grid     { grid-template-columns: 1fr; }
        .ren-card-header   { flex-direction: column; align-items: flex-start; gap: 10px; }
        .ren-search        { width: 100%; }
        .ren-full          { grid-column: 1; }
    }
</style>

<div class="ren-wrap">

    <div>
        <p class="ren-breadcrumb">Tenant Portal / Renovasi</p>
        <h1 class="ren-page-title">Permohonan Renovasi Unit</h1>
        <p class="ren-page-sub"><?= htmlspecialchars($brand_name) ?></p>
    </div>

    <?php if ($alertMsg): ?>
    <div class="ren-alert ren-alert--<?= $alertType ?>">
        <span><?= htmlspecialchars($alertMsg) ?></span>
    </div>
    <?php endif; ?>

    <!-- Statistik -->
    <div class="ren-stats">
        <div class="ren-stat-card">
            <div class="ren-stat-label">Total Pengajuan</div>
            <div class="ren-stat-value"><?= $statTotal ?></div>
        </div>
        <div class="ren-stat-card">
            <div class="ren-stat-label">Sedang Diproses</div>
            <div class="ren-stat-value" style="color: var(--text-accent)"><?= $statProses ?></div>
        </div>
        <div class="ren-stat-card">
            <div class="ren-stat-label">Disetujui</div>
            <div class="ren-stat-value" style="color: var(--success)"><?= $statApproved ?></div>
        </div>
    </div>

    <!-- Form pengajuan -->
    <div class="ren-card">
        <div class="ren-card-header">
            <h2 class="ren-card-title">Form Pengajuan Renovasi Baru</h2>
        </div>

        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" enctype="multipart/form-data">
            <div class="ren-form-grid">

                <div class="ren-form-group">
                    <label class="ren-form-label" for="id_contract">
                        Unit / Kontrak <span class="ren-required">*</span>
                    </label>
                    <select id="id_contract" name="id_contract" class="ren-input" required>
                        <option value="" disabled selected>Pilih unit aktif...</option>
                        <?php
                        if ($contractResult && mysqli_num_rows($contractResult) > 0):
                            mysqli_data_seek($contractResult, 0);
                            while ($c = mysqli_fetch_assoc($contractResult)):
                        ?>
                            <option value="<?= $c['id_contract'] ?>"
                                <?= (isset($_POST['id_contract']) && (int)$_POST['id_contract'] === (int)$c['id_contract']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['unit_code']) ?> — <?= htmlspecialchars($c['contract_number']) ?>
                            </option>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <option disabled>Tidak ada kontrak aktif</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="ren-form-group">
                    <label class="ren-form-label" for="attachment_plan">
                        Dokumen Rencana Renovasi <span class="ren-required">*</span>
                    </label>
                    <input type="file" id="attachment_plan" name="attachment_plan"
                           class="ren-input" accept=".pdf,.jpg,.jpeg,.png" required>
                    <span class="ren-hint">PDF / JPG / PNG — maks. 5 MB</span>
                </div>

                <div class="ren-form-group">
                    <label class="ren-form-label" for="proposed_start_date">
                        Tanggal Mulai Renovasi <span class="ren-required">*</span>
                    </label>
                    <input type="date" id="proposed_start_date" name="proposed_start_date"
                           class="ren-input" min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['proposed_start_date'] ?? '') ?>" required>
                </div>

                <div class="ren-form-group">
                    <label class="ren-form-label" for="proposed_end_date">
                        Tanggal Selesai Renovasi <span class="ren-required">*</span>
                    </label>
                    <input type="date" id="proposed_end_date" name="proposed_end_date"
                           class="ren-input"
                           value="<?= htmlspecialchars($_POST['proposed_end_date'] ?? '') ?>" required>
                </div>

                <div class="ren-form-group ren-full">
                    <label class="ren-form-label" for="description">
                        Deskripsi Rencana Renovasi <span class="ren-required">*</span>
                    </label>
                    <textarea id="description" name="description"
                              class="ren-input ren-textarea"
                              placeholder="Jelaskan detail pekerjaan renovasi yang akan dilakukan, termasuk material, metode, dan area yang terdampak..."
                              required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

            </div>

            <div class="ren-form-actions">
                <button type="reset" class="ren-btn-secondary">Reset</button>
                <button type="submit" class="ren-btn-primary">Kirim Pengajuan</button>
            </div>
        </form>
    </div>

    <div class="ren-card">
        <div class="ren-card-header">
            <h2 class="ren-card-title">Riwayat Pengajuan</h2>
            <input type="text" id="ren-search" class="ren-search"
                   placeholder="Cari unit..." oninput="renFilter()">
        </div>

        <div class="ren-table-wrap">
            <table class="ren-table" id="renTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Unit</th>
                        <th>Nomor Kontrak</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Deskripsi</th>
                        <th>Dokumen</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$riwayatResult || mysqli_num_rows($riwayatResult) === 0): ?>
                    <tr>
                        <td colspan="8" class="ren-empty">
                            Belum ada pengajuan renovasi yang tercatat.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 0; while ($h = mysqli_fetch_assoc($riwayatResult)): $no++; ?>
                    <tr>
                        <td><?= $no ?></td>
                        <td class="td-bold"><?= htmlspecialchars($h['unit_code']) ?></td>
                        <td><?= htmlspecialchars($h['contract_number']) ?></td>
                        <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($h['proposed_start_date'])) ?></td>
                        <td style="white-space:nowrap"><?= date('d/m/Y', strtotime($h['proposed_end_date']))   ?></td>
                        <td style="max-width:240px"><?= htmlspecialchars(mb_strimwidth($h['description'], 0, 70, '…')) ?></td>
                        <td>
                            <?php if (!empty($h['attachment_plan_url'])): ?>
                                <a href="<?= htmlspecialchars($h['attachment_plan_url']) ?>"
                                   target="_blank" rel="noopener" class="ren-doc-link">Lihat</a>
                            <?php else: ?>
                                <span style="opacity:0.4">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= badgeRenovasi($h['status']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function renFilter() {
        const q    = document.getElementById('ren-search').value.toLowerCase();
        const rows = document.querySelectorAll('#renTable tbody tr');
        rows.forEach(function (row) {
            const unit = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
            row.style.display = unit.includes(q) ? '' : 'none';
        });
    }

    document.getElementById('proposed_start_date').addEventListener('change', function () {
        const endInput = document.getElementById('proposed_end_date');
        if (endInput.value && endInput.value <= this.value) {
            endInput.value = '';
        }
        endInput.min = this.value;
    });
</script>