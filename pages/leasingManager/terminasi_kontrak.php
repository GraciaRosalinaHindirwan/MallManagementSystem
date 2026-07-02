<?php
require_once "../../public/auth/checkSession.php";
require_once "../../config/konek.php";

$page_title  = 'Terminasi Kontrak';
$active_page = 'terminasi_kontrak';
$user_name   = $_SESSION['username'];   
$role        = $_SESSION['user_role'];     

require_once "../../includes/navbarM02.php";

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'terminate') {

    $id_contract      = (int) ($_POST['id_contract']      ?? 0);
    $termination_date = trim($_POST['termination_date']   ?? '');
    $termination_type = trim($_POST['termination_type']   ?? '');
    $reason           = trim($_POST['reason']             ?? '');
    $checklist        = $_POST['checklist']               ?? [];

    $required_checks = ['billing', 'handover', 'inspection', 'deposit'];
    $missing_checks  = array_diff($required_checks, $checklist);

    if (!$id_contract || !$termination_date || !$termination_type || !$reason) {
        $alert = ['type' => 'error', 'msg' => 'Semua field wajib diisi.'];

    } elseif (!empty($missing_checks)) {
        $alert = ['type' => 'error', 'msg' => 'Semua checklist penyelesaian harus dicentang sebelum memproses terminasi.'];

    } else {
        $stmt = $conn->prepare(
            "SELECT c.id_unit, c.id_tenant, c.contract_status
             FROM 02_contracts c
             WHERE c.id_contract = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id_contract);
        $stmt->execute();
        $contract = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$contract) {
            $alert = ['type' => 'error', 'msg' => 'Kontrak tidak ditemukan.'];

        } elseif ($contract['contract_status'] === 'Terminated') {
            $alert = ['type' => 'error', 'msg' => 'Kontrak ini sudah pernah diterminasi.'];

        } else {
            $id_unit   = (int) $contract['id_unit'];
            $id_tenant = (int) $contract['id_tenant'];

            $stmt2 = $conn->prepare(
                "SELECT COUNT(*) AS cnt
                 FROM 02_contracts
                 WHERE id_tenant = ? AND id_contract != ? AND contract_status = 'Active'"
            );
            $stmt2->bind_param('ii', $id_tenant, $id_contract);
            $stmt2->execute();
            $other_active = (int) $stmt2->get_result()->fetch_assoc()['cnt'];
            $stmt2->close();

            $conn->begin_transaction();
            $ok = true;

            $s1 = $conn->prepare(
                "UPDATE 02_contracts
                 SET contract_status = 'Terminated'
                 WHERE id_contract = ?"
            );
            $s1->bind_param('i', $id_contract);
            $ok = $ok && $s1->execute();
            $s1->close();

            $s2 = $conn->prepare(
                "UPDATE 01_units
                 SET status = 'available', tenant_id = NULL
                 WHERE id_units = ?"
            );
            $s2->bind_param('i', $id_unit);
            $ok = $ok && $s2->execute();
            $s2->close();

            if ($other_active === 0) {
                $s3 = $conn->prepare(
                    "UPDATE 02_tenants
                     SET status = 'Terminated'
                     WHERE id_tenant = ?"
                );
                $s3->bind_param('i', $id_tenant);
                $ok = $ok && $s3->execute();
                $s3->close();
            }

            if ($ok) {
                $conn->commit();
                $alert = ['type' => 'success', 'msg' => 'Terminasi kontrak berhasil diproses. Status unit telah diperbarui menjadi Available di Modul M01.'];
            } else {
                $conn->rollback();
                $alert = ['type' => 'error', 'msg' => 'Terjadi kesalahan saat memproses terminasi. Silakan coba lagi.'];
            }
        }
    }
}

$contracts_result = $conn->query(
    "SELECT
         c.id_contract,
         c.contract_number,
         c.start_date,
         c.end_date,
         c.contract_status,
         t.tenant_name,
         t.brand_name,
         u.unit_code,
         u.status  AS unit_status,
         COALESCE(
             (SELECT SUM(i.total_amount)
              FROM 06_invoices i
              WHERE i.contract_id = c.id_contract
                AND i.status = 'Belum Bayar'),
             0
         ) AS sisa_tagihan
     FROM 02_contracts c
     JOIN 02_tenants  t ON c.id_tenant = t.id_tenant
     JOIN 01_units    u ON c.id_unit   = u.id_units
     WHERE c.contract_status IN ('Active', 'Terminated')
     ORDER BY c.contract_status ASC, c.end_date ASC"
);

$contracts = [];
while ($row = $contracts_result->fetch_assoc()) {
    $contracts[] = $row;
}

$active_contracts = array_filter($contracts, fn($c) => $c['contract_status'] === 'Active');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminasi Kontrak - Mall ERP</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .container {
            background: var(--primary, #0B376D);
            padding: 32px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 32px rgba(2, 31, 66, 0.4);
            max-width: 1100px;
            width: 100%;
        }

        h2 {
            color: var(--text, #F5F7FA);
            font-weight: 700;
            font-size: var(--h2, 24px);
            margin-bottom: 6px;
        }

        p {
            color: rgba(245, 247, 250, 0.5);
            font-size: var(--label, 14px);
            margin-bottom: 24px;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .toolbar input,
        .toolbar select {
            background: var(--primary-dark, #082A53);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 8px;
            padding: 9px 14px;
            color: var(--text, #F5F7FA);
            font-size: var(--label, 14px);
            outline: none;
            font-family: inherit;
        }
        .toolbar input { flex: 1; min-width: 200px; }
        .toolbar input::placeholder { color: rgba(245,247,250,.35); }
        .toolbar select option { background: var(--primary-dark, #082A53); }
        .toolbar input:focus,
        .toolbar select:focus { border-color: var(--accent, #00D4D8); }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: transparent;
            text-align: left;
        }

        th, td {
            padding: 14px 20px;
            font-size: var(--label, 14px);
        }

        th {
            background-color: var(--primary-dark, #082A53);
            color: var(--accent, #00D4D8);
            font-weight: 600;
            text-transform: uppercase;
            font-size: var(--caption, 12px);
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            white-space: nowrap;
        }

        td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text, #F5F7FA);
            vertical-align: middle;
        }

        tbody tr:hover {
            background: rgba(0, 212, 216, 0.04);
        }

        .badge {
            display: inline-block;
            border-radius: 99px;
            padding: 4px 12px;
            font-size: var(--caption, 12px);
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-active      { background: rgba(34,197,94,.15);  color: var(--success, #22C55E); border: 1px solid rgba(34,197,94,.3); }
        .badge-terminated  { background: rgba(239,68,68,.15);  color: var(--danger, #EF4444);  border: 1px solid rgba(239,68,68,.3); }
        .badge-available   { background: rgba(0,212,216,.12);  color: var(--accent, #00D4D8);  border: 1px solid rgba(0,212,216,.25); }
        .badge-occupied    { background: rgba(255,182,42,.15); color: var(--text-accent, #FFB62A); border: 1px solid rgba(255,182,42,.3); }
        .badge-overdue     { background: rgba(239,68,68,.12);  color: #fca5a5;  border: 1px solid rgba(239,68,68,.25); font-size: 11px; }

        .btn-group {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: var(--caption, 12px);
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
        }

        .btn-terminate {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger, #EF4444);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .btn-terminate:hover {
            background: var(--danger, #EF4444);
            color: var(--text, #F5F7FA);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }
        .btn-terminate:disabled { opacity: .35; cursor: not-allowed; }

        .btn-secondary {
            background: rgba(255,255,255,.08);
            color: var(--text, #F5F7FA);
            border: 1px solid rgba(255,255,255,.15);
        }
        .btn-secondary:hover { opacity: .85; }

        .btn-danger {
            background: var(--danger, #EF4444);
            color: #fff;
        }
        .btn-danger:hover {
            opacity: .9;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: var(--label, 14px);
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert-success {
            background: rgba(34,197,94,.12);
            border: 1px solid rgba(34,197,94,.3);
            color: #86efac;
        }
        .alert-error {
            background: rgba(239,68,68,.12);
            border: 1px solid rgba(239,68,68,.3);
            color: #fca5a5;
        }
        .alert-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

        .form-section { display: none; margin-top: 24px; }
        .form-section.visible { display: block; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }

        .form-group label {
            font-size: var(--label, 14px);
            font-weight: 500;
            color: rgba(245,247,250,.75);
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            background: var(--primary-dark, #082A53);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 8px;
            padding: 10px 14px;
            color: var(--text, #F5F7FA);
            font-size: var(--label, 14px);
            font-family: inherit;
            outline: none;
            transition: border-color .2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { border-color: var(--accent, #00D4D8); }
        .form-group textarea { resize: vertical; min-height: 90px; }
        .form-group select option { background: var(--primary-dark, #082A53); }

        .checklist-block {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 10px;
            padding: 16px 18px;
        }
        .checklist-block p {
            font-size: var(--label, 14px);
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text-accent, #FFB62A);
        }
        .checklist-block label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: var(--label, 14px);
            padding: 6px 0;
            cursor: pointer;
            color: rgba(245,247,250,.8);
            border-bottom: 1px solid rgba(255,255,255,.05);
        }
        .checklist-block label:last-child { border-bottom: none; }
        .checklist-block input[type="checkbox"] {
            accent-color: var(--accent, #00D4D8);
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .note-box {
            background: rgba(0,212,216,.07);
            border: 1px solid rgba(0,212,216,.2);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: var(--caption, 12px);
            color: rgba(245,247,250,.7);
            line-height: 1.55;
        }
        .note-box strong { color: var(--accent, #00D4D8); }

        .btn-row {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .section-title {
            font-size: var(--label, 14px);
            font-weight: 600;
            color: var(--accent, #00D4D8);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 6px;
        }

        .empty-msg {
            text-align: center;
            padding: 30px;
            color: rgba(245, 247, 250, 0.4);
        }
    </style>
</head>
<body>
<div class="page-wrapper">
<div class="container">
    <h2>Terminasi Kontrak</h2>
    <p>Proses terminasi kontrak tenant — unit akan otomatis kembali tersedia di Modul M01 setelah terminasi berhasil.</p>

    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] === 'success' ? 'success' : 'error' ?>">
        <span class="alert-icon"><?= $alert['type'] === 'success' ? '✓' : '✕' ?></span>
        <span><?= htmlspecialchars($alert['msg']) ?></span>
    </div>
    <?php endif; ?>

    <div class="toolbar">
        <input type="text" id="searchInput" placeholder="Cari tenant / no. kontrak…">
        <select id="statusFilter">
            <option value="all">Semua Status</option>
            <option value="Active">Active</option>
            <option value="Terminated">Terminated</option>
        </select>
    </div>

    <div class="table-responsive">
        <table id="contractTable">
            <thead>
                <tr>
                    <th>No. Kontrak</th>
                    <th>Tenant</th>
                    <th>Unit</th>
                    <th>Berakhir</th>
                    <th>Sisa Tagihan</th>
                    <th>Status Kontrak</th>
                    <th>Status Unit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="contractBody">
            <?php if (empty($contracts)): ?>
                <tr><td colspan="8" class="empty-msg">Belum ada data kontrak.</td></tr>
            <?php else: ?>
                <?php foreach ($contracts as $c): ?>
                <?php
                    $is_terminated = $c['contract_status'] === 'Terminated';
                ?>
                <tr class="contract-row"
                    data-search="<?= strtolower($c['tenant_name'].' '.$c['contract_number']) ?>"
                    data-status="<?= $c['contract_status'] ?>">
                    <td><strong style="color:var(--accent,#00D4D8)"><?= htmlspecialchars($c['contract_number']) ?></strong></td>
                    <td><?= htmlspecialchars($c['tenant_name']) ?><br>
                        <small style="opacity:.5"><?= htmlspecialchars($c['brand_name']) ?></small></td>
                    <td><?= htmlspecialchars($c['unit_code']) ?></td>
                    <td style="font-size:12px"><?= date('d M Y', strtotime($c['end_date'])) ?></td>
                    <td>
                        <?php if ($c['sisa_tagihan'] > 0): ?>
                            <span class="badge badge-overdue">Rp <?= number_format($c['sisa_tagihan'], 0, ',', '.') ?></span>
                        <?php else: ?>
                            <span style="opacity:.4;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $is_terminated ? 'badge-terminated' : 'badge-active' ?>">
                            <?= $c['contract_status'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $c['unit_status'] === 'available' ? 'badge-available' : 'badge-occupied' ?>">
                            <?= ucfirst($c['unit_status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                        <?php if (!$is_terminated): ?>
                            <button class="btn btn-terminate"
                                    onclick="showTerminationForm(<?= $c['id_contract'] ?>, '<?= addslashes($c['contract_number']) ?>', '<?= addslashes($c['tenant_name']) ?>')">
                                Terminasi
                            </button>
                        <?php else: ?>
                            <span style="font-size:11px;opacity:.35">Selesai</span>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <p id="emptyMsg" class="empty-msg" style="display:none">Tidak ada hasil yang cocok.</p>
    </div>

    <!-- Form Terminasi -->
    <div class="form-section" id="terminationSection">
        <div class="section-title">Form Terminasi Kontrak</div>
        <p style="margin-bottom:14px">Lengkapi semua field dan checklist sebelum memproses terminasi.</p>

        <form method="POST" id="terminationForm">
            <input type="hidden" name="action" value="terminate">

            <div class="form-grid">
                <div class="form-group full">
                    <label for="id_contract">Kontrak Tenant</label>
                    <select name="id_contract" id="id_contract" required>
                        <option value="">Pilih kontrak…</option>
                        <?php foreach ($active_contracts as $c): ?>
                        <option value="<?= $c['id_contract'] ?>">
                            <?= htmlspecialchars($c['contract_number']) ?> — <?= htmlspecialchars($c['tenant_name']) ?> (<?= htmlspecialchars($c['unit_code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="termination_date">Tanggal Terminasi</label>
                    <input type="date" name="termination_date" id="termination_date"
                           value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="termination_type">Jenis Terminasi</label>
                    <select name="termination_type" id="termination_type" required>
                        <option value="">Pilih jenis…</option>
                        <option value="contract_end">Kontrak Berakhir</option>
                        <option value="early_termination">Terminasi Lebih Awal</option>
                        <option value="breach">Pelanggaran Kontrak</option>
                        <option value="mutual_agreement">Kesepakatan Bersama</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label for="reason">Alasan Terminasi</label>
                    <textarea name="reason" id="reason" rows="4"
                              placeholder="Jelaskan alasan terminasi kontrak ini…" required></textarea>
                </div>

                <div class="form-group full">
                    <div class="checklist-block">
                        <p>Checklist Penyelesaian</p>
                        <label>
                            <input type="checkbox" name="checklist[]" value="billing">
                            Tagihan dan kewajiban keuangan telah diperiksa
                        </label>
                        <label>
                            <input type="checkbox" name="checklist[]" value="handover">
                            Berita acara serah terima unit telah disiapkan
                        </label>
                        <label>
                            <input type="checkbox" name="checklist[]" value="inspection">
                            Kondisi fisik unit telah diperiksa
                        </label>
                        <label>
                            <input type="checkbox" name="checklist[]" value="deposit">
                            Status deposit telah ditentukan
                        </label>
                    </div>
                </div>

                <div class="form-group full">
                    <div class="note-box">
                        Setelah terminasi berhasil diproses, status kontrak akan berubah menjadi
                        <strong>Terminated</strong> dan status unit otomatis diperbarui menjadi
                        <strong>Available</strong> pada Modul M01. Tindakan ini tidak dapat dibatalkan.
                    </div>
                </div>
            </div>

            <div class="btn-row">
                <button type="button" class="btn btn-secondary" onclick="hideForm()">Batal</button>
                <button type="submit" class="btn btn-danger">Proses Terminasi</button>
            </div>
        </form>
    </div>
</div>
</div>

<script>
function showTerminationForm(id, contractNumber, tenantName) {
    const section = document.getElementById('terminationSection');
    section.classList.add('visible');
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });

    const sel = document.getElementById('id_contract');
    for (let i = 0; i < sel.options.length; i++) {
        if (parseInt(sel.options[i].value) === id) {
            sel.selectedIndex = i;
            break;
        }
    }
}

function hideForm() {
    document.getElementById('terminationSection').classList.remove('visible');
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const rows   = document.querySelectorAll('.contract-row');
    let visible  = 0;

    rows.forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchStatus = status === 'all' || row.dataset.status === status;
        const show = matchSearch && matchStatus;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('emptyMsg').style.display = visible === 0 ? 'block' : 'none';
}

document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);

<?php if ($alert && $alert['type'] === 'error' && !empty($_POST['id_contract'])): ?>
document.getElementById('terminationSection').classList.add('visible');
document.getElementById('id_contract').value      = '<?= (int)$_POST['id_contract'] ?>';
document.getElementById('termination_date').value = '<?= htmlspecialchars($_POST['termination_date'] ?? '') ?>';
document.getElementById('termination_type').value = '<?= htmlspecialchars($_POST['termination_type'] ?? '') ?>';
document.getElementById('reason').value            = '<?= addslashes($_POST['reason'] ?? '') ?>';
<?php endif; ?>
</script>
</body>
</html>
