<?php
/**
 * Mall ERP — Parking Management (M04)
 * Entry point: menangani AJAX action & render halaman utama.
 */
require_once __DIR__ . '/parking.php';

// ── JSON response helper ───────────────────────────────────────────────────────
function respondJson(array $data): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── AJAX handler ──────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? null;
if ($action !== null) {
    // Gabungkan POST body (form-data & JSON)
    $body = $_POST;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($ct, 'application/json')) {
            $raw  = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $body = array_merge($body, $json);
            }
        }
    } else {
        $body = $_GET;
    }

    switch ($action) {
        // ── Status parkir ──────────────────────────────────────────────────────
        case 'state':
            respondJson(['success' => true, 'state' => getParkingState()]);

        // ── Entry kendaraan ────────────────────────────────────────────────────
        case 'entry':
            respondJson(processEntry(
                plate:         $body['plate']          ?? '',
                tipeUser:      $body['tipe_user']      ?? ($body['type'] ?? 'umum'),
                tipeKendaraan: $body['tipe_kendaraan'] ?? 'mobil',
                zonaId:        (int) ($body['zona_id'] ?? 1),
                parkingSlot:   $body['parking_slot']   ?? '',
                idMember:      isset($body['id_member']) ? (int) $body['id_member'] : null
            ));

        // ── Exit kendaraan ─────────────────────────────────────────────────────
        case 'exit':
            respondJson(processExit(
                plate:         $body['plate']          ?? '',
                paymentMethod: $body['payment_method'] ?? 'cash'
            ));

        // ── Member ─────────────────────────────────────────────────────────────
        case 'member_add':
            respondJson(addMember(
                platNomor:      $body['plat_nomor']      ?? ($body['plate'] ?? ''),
                tipeKendaraan:  $body['tipe_kendaraan']  ?? 'mobil',
                membershipType: $body['membership_type'] ?? ($body['type'] ?? 'Reguler'),
                tenantId:       isset($body['tenant_id']) ? (int) $body['tenant_id'] : null
            ));

        case 'member_delete':
            respondJson(deleteMember($body['plat_nomor'] ?? ($body['plate'] ?? '')));

        // ── Zona (alias "subscription" untuk kompatibilitas frontend) ──────────
        case 'subscription_add':
        case 'zona_add':
            respondJson(addZona(
                namaZona:  $body['name']       ?? ($body['nama_zona'] ?? ''),
                totalSlot: (int) ($body['slots'] ?? ($body['total_slot'] ?? 0)),
                floorId:   isset($body['floor_id']) ? (int) $body['floor_id'] : null
            ));

        case 'subscription_delete':
        case 'zona_delete':
            if (isset($body['zona_id'])) {
                respondJson(deleteZona((int) $body['zona_id']));
            }
            respondJson(deleteSubscription($body['name'] ?? ($body['nama_zona'] ?? '')));

        // ── Statistik ──────────────────────────────────────────────────────────
        case 'stats_reset':
            respondJson(resetStats());

        case 'transactions_clear':
            respondJson(clearTransactions());

        default:
            respondJson(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }
}

// ── Render HTML ───────────────────────────────────────────────────────────────
$state = getParkingState();

function typeLabel(string $type): string
{
    return match ($type) {
        'member'    => 'Member',
        'corporate',
        'korporat'  => 'Korporat',
        default     => 'Pengunjung Biasa',
    };
}

function badgeClass(string $type): string
{
    return match ($type) {
        'member'   => 'member',
        'corporate',
        'korporat' => 'corporate',
        default    => 'regular',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mall ERP - Parking Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
<div id="toast" class="toast hidden"></div>
<div class="modal-backdrop hidden" id="modal-backdrop"></div>
<div class="modal hidden" id="modal-container">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Modal</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div id="modal-body"></div>
    </div>
</div>

<div class="erp-layout">
    <main class="main-content">
        <header class="topbar">
            <h2>Parking Management (M04)</h2>
            <div class="topbar-right">
                <?php if ($useDb): ?>
                    <span style="color:#22c55e;font-size:12px;"><i class="fas fa-database"></i> DB: mall_erp</span>
                <?php else: ?>
                    <span style="color:#f59e0b;font-size:12px;"><i class="fas fa-exclamation-triangle"></i> Mode Sesi</span>
                <?php endif; ?>
                &nbsp;
                <span class="capacity-status">
                    <i class="fas fa-info-circle"></i>
                    <span id="capacity-text"><?= $state['occupied'] ?>/<?= $state['totalSlots'] ?></span>
                </span>
            </div>
        </header>

        <div class="tabs">
            <button class="tab-btn active" type="button" onclick="switchTab('entry', this)">
                <i class="fas fa-sign-in-alt"></i> Entry/Exit
            </button>
            <button class="tab-btn" type="button" onclick="switchTab('monitoring', this)">
                <i class="fas fa-chart-line"></i> Monitoring
            </button>
            <button class="tab-btn" type="button" onclick="switchTab('members', this)">
                <i class="fas fa-id-card"></i> Member
            </button>
            <button class="tab-btn" type="button" onclick="switchTab('zona', this)">
                <i class="fas fa-map-marker-alt"></i> Zona
            </button>
        </div>

        <div class="content-wrapper">

            <!-- ════ TAB: ENTRY / EXIT ════ -->
            <div id="tab-entry" class="tab-content active">
                <section class="dashboard-cards">
                    <div class="card status-total">
                        <p>Total Slot</p>
                        <h3 id="dash-total"><?= $state['totalSlots'] ?></h3>
                    </div>
                    <div class="card status-occupied">
                        <p>Terisi</p>
                        <h3 id="dash-occupied"><?= $state['occupied'] ?></h3>
                    </div>
                    <div class="card status-available">
                        <p>Tersedia</p>
                        <h3 id="dash-available"><?= $state['available'] ?></h3>
                    </div>
                </section>

                <div class="grid-2">
                    <!-- ENTRY -->
                    <div class="card panel">
                        <h3><i class="fas fa-sign-in-alt"></i> Entry Kendaraan</h3>
                        <input type="text" id="entry-plate" placeholder="Plat Nomor (B 1234 XYZ)"
                               onkeypress="if(event.key==='Enter') submitEntry()">

                        <select id="entry-tipe-kendaraan">
                            <option value="mobil">Mobil</option>
                            <option value="motor">Motor</option>
                            <option value="truk">Truk</option>
                        </select>

                        <select id="entry-tipe-user" onchange="updateEntryForm()">
                            <option value="umum">Pengunjung Umum</option>
                            <option value="member">Member</option>
                            <option value="korporat">Korporat</option>
                        </select>

                        <select id="entry-zona">
                            <?php foreach ($state['zonas'] as $z): ?>
                                <option value="<?= $z['id_zona'] ?>">
                                    <?= htmlspecialchars($z['nama_zona']) ?>
                                    (<?= $z['total_slot'] - $z['occupied_slot'] ?> tersedia)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="text" id="entry-slot" placeholder="Slot (opsional, contoh: A-01)">

                        <div id="entry-member-wrapper" class="hidden">
                            <select id="entry-member">
                                <option value="">-- Pilih Member --</option>
                            </select>
                        </div>

                        <button class="btn btn-primary w-100" type="button" onclick="submitEntry()">
                            <i class="fas fa-arrow-right"></i> Scan Masuk
                        </button>
                        <div id="qr-entry" class="qr-box hidden"></div>
                    </div>

                    <!-- EXIT -->
                    <div class="card panel">
                        <h3><i class="fas fa-sign-out-alt"></i> Exit Kendaraan</h3>
                        <input type="text" id="exit-plate" placeholder="Plat Nomor..."
                               onkeypress="if(event.key==='Enter') submitExit()">
                        <select id="exit-payment">
                            <option value="cash">Cash</option>
                            <option value="cashless">Cashless / QRIS</option>
                            <option value="e-toll">E-Toll</option>
                            <option value="member-auto">Member Auto</option>
                        </select>
                        <button class="btn btn-danger w-100" type="button" onclick="submitExit()">
                            <i class="fas fa-arrow-left"></i> Kalkulasi & Keluar
                        </button>
                        <div id="receipt" class="receipt-box hidden">
                            <h4><i class="fas fa-receipt"></i> Struk Pembayaran</h4>
                            <div id="receipt-content"></div>
                        </div>
                    </div>
                </div>

                <!-- Kendaraan aktif -->
                <section class="card panel mt-20">
                    <h3><i class="fas fa-car"></i> Kendaraan di Dalam Parkir</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Plat Nomor</th>
                                <th>Tipe</th>
                                <th>Kendaraan</th>
                                <th>Zona</th>
                                <th>Slot</th>
                                <th>Waktu Masuk</th>
                                <th>Durasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="vehicles-table">
                            <?php if (empty($state['vehicles'])): ?>
                                <tr><td colspan="8" class="text-center">Parkir kosong</td></tr>
                            <?php else: ?>
                                <?php foreach ($state['vehicles'] as $plate => $v): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($plate) ?></strong></td>
                                        <td><span class="badge badge-<?= badgeClass($v['type']) ?>"><?= typeLabel($v['type']) ?></span></td>
                                        <td><?= htmlspecialchars(ucfirst($v['tipeKendaraan'] ?? 'mobil')) ?></td>
                                        <td><?= htmlspecialchars($v['zona_id'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($v['parking_slot'] ?? '-') ?></td>
                                        <td><?= date('H:i:s', $v['time']) ?></td>
                                        <td><?= max(1, (int)ceil((time() - $v['time']) / 60)) ?> mnt</td>
                                        <td>
                                            <button class="btn-small btn-danger"
                                                    onclick="quickExit('<?= htmlspecialchars($plate) ?>')">
                                                Keluarkan
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- ════ TAB: MONITORING ════ -->
            <div id="tab-monitoring" class="tab-content hidden">
                <section class="dashboard-cards">
                    <div class="card status-total"><p>Total Entry (Hari ini)</p><h3 id="stat-entry"><?= $state['stats']['entry'] ?></h3></div>
                    <div class="card status-occupied"><p>Total Exit</p><h3 id="stat-exit"><?= $state['stats']['exit'] ?></h3></div>
                    <div class="card status-available"><p>Pendapatan</p><h3 id="stat-revenue">Rp <?= number_format($state['stats']['revenue'], 0, ',', '.') ?></h3></div>
                </section>

                <!-- Zona kapasitas -->
                <section class="card panel mt-20">
                    <h3><i class="fas fa-map-marker-alt"></i> Kapasitas per Zona</h3>
                    <table class="table">
                        <thead>
                            <tr><th>Zona</th><th>Total Slot</th><th>Terisi</th><th>Tersedia</th><th>Utilisasi</th></tr>
                        </thead>
                        <tbody id="zona-monitor-table">
                            <?php foreach ($state['zonas'] as $z): ?>
                                <?php $util = $z['total_slot'] > 0 ? round($z['occupied_slot'] / $z['total_slot'] * 100) : 0; ?>
                                <tr>
                                    <td><?= htmlspecialchars($z['nama_zona']) ?></td>
                                    <td><?= $z['total_slot'] ?></td>
                                    <td><?= $z['occupied_slot'] ?></td>
                                    <td><?= $z['total_slot'] - $z['occupied_slot'] ?></td>
                                    <td>
                                        <div style="background:#e5e7eb;border-radius:4px;height:10px;width:100%">
                                            <div style="background:<?= $util >= 90 ? '#ef4444' : ($util >= 70 ? '#f59e0b' : '#22c55e') ?>;
                                                        width:<?= $util ?>%;height:10px;border-radius:4px"></div>
                                        </div>
                                        <?= $util ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <!-- Riwayat transaksi -->
                <section class="card panel mt-20">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <h3><i class="fas fa-history"></i> Riwayat Transaksi</h3>
                        <div>
                            <button class="btn btn-danger" onclick="confirmResetStats()">Reset Hari Ini</button>
                        </div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr><th>Plat</th><th>Tipe</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th>Total</th></tr>
                        </thead>
                        <tbody id="transactions-table">
                            <?php if (empty($state['transactions'])): ?>
                                <tr><td colspan="6" class="text-center">Belum ada transaksi</td></tr>
                            <?php else: ?>
                                <?php foreach ($state['transactions'] as $tx): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tx['plate']) ?></td>
                                        <td><span class="badge badge-<?= badgeClass($tx['type']) ?>"><?= typeLabel($tx['type']) ?></span></td>
                                        <td><?= htmlspecialchars($tx['entryTime'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($tx['exitTime']) ?></td>
                                        <td><?= $tx['duration'] ?> mnt</td>
                                        <td><strong>Rp <?= number_format($tx['total'], 0, ',', '.') ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- ════ TAB: MEMBER ════ -->
            <div id="tab-members" class="tab-content hidden">
                <section class="card panel">
                    <h3><i class="fas fa-user-plus"></i> Tambah Member</h3>
                    <div class="grid-2">
                        <input type="text" id="m-plate"    placeholder="Plat Nomor">
                        <select id="m-kendaraan">
                            <option value="mobil">Mobil</option>
                            <option value="motor">Motor</option>
                            <option value="truk">Truk</option>
                        </select>
                        <select id="m-type">
                            <option value="Reguler">Reguler</option>
                            <option value="VIP">VIP (+25%)</option>
                            <option value="Korporat">Korporat (+30%)</option>
                        </select>
                        <input type="number" id="m-tenant" placeholder="Tenant ID (opsional, untuk korporat)">
                    </div>
                    <button class="btn btn-primary mt-10" onclick="submitAddMember()">
                        <i class="fas fa-plus"></i> Tambah Member
                    </button>
                </section>

                <section class="card panel mt-20">
                    <h3><i class="fas fa-id-card"></i> Daftar Member</h3>
                    <table class="table">
                        <thead>
                            <tr><th>Plat Nomor</th><th>Kendaraan</th><th>Tipe</th><th>Tenant</th><th>Diskon</th><th>Aksi</th></tr>
                        </thead>
                        <tbody id="member-list">
                            <?php if (empty($state['members'])): ?>
                                <tr><td colspan="6" class="text-center">Belum ada member</td></tr>
                            <?php else: ?>
                                <?php foreach ($state['members'] as $m): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['plate'] ?? $m['plat_nomor'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars(ucfirst($m['tipeKendaraan'] ?? '-')) ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= htmlspecialchars($m['type'] ?? $m['membership_type'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($m['name'] ?? '-') ?></td>
                                        <td><?= $m['discountPercent'] ?? 0 ?>%</td>
                                        <td>
                                            <button class="btn-small btn-danger"
                                                    onclick="deleteMember('<?= htmlspecialchars($m['plate'] ?? $m['plat_nomor'] ?? '') ?>')">
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- ════ TAB: ZONA ════ -->
            <div id="tab-zona" class="tab-content hidden">
                <section class="card panel">
                    <h3><i class="fas fa-plus-circle"></i> Tambah Zona Parkir</h3>
                    <div class="grid-2">
                        <input type="text"   id="z-nama"  placeholder="Nama Zona (contoh: Basement 3)">
                        <input type="number" id="z-slots" placeholder="Total Slot" min="1">
                        <input type="number" id="z-floor" placeholder="Floor ID (opsional)">
                    </div>
                    <button class="btn btn-primary mt-10" onclick="submitAddZona()">
                        <i class="fas fa-plus"></i> Tambah Zona
                    </button>
                </section>

                <section class="card panel mt-20">
                    <h3><i class="fas fa-th-large"></i> Daftar Zona</h3>
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>Zona</th><th>Total Slot</th><th>Terisi</th><th>Tersedia</th><th>Aksi</th></tr>
                        </thead>
                        <tbody id="zona-list">
                            <?php foreach ($state['zonas'] as $z): ?>
                                <tr>
                                    <td><?= $z['id_zona'] ?></td>
                                    <td><?= htmlspecialchars($z['nama_zona']) ?></td>
                                    <td><?= $z['total_slot'] ?></td>
                                    <td><?= $z['occupied_slot'] ?></td>
                                    <td><?= $z['total_slot'] - $z['occupied_slot'] ?></td>
                                    <td>
                                        <button class="btn-small btn-danger"
                                                onclick="deleteZona(<?= $z['id_zona'] ?>, '<?= htmlspecialchars($z['nama_zona']) ?>')">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </div>

        </div><!-- /content-wrapper -->
    </main>
</div>

<script>
// ── State reaktif ──────────────────────────────────────────────────────────────
let state = <?= json_encode($state, JSON_UNESCAPED_UNICODE) ?>;

// ── Fetch helper ───────────────────────────────────────────────────────────────
async function api(action, params = {}) {
    const fd = new FormData();
    Object.entries(params).forEach(([k, v]) => { if (v !== null && v !== undefined && v !== '') fd.append(k, v); });
    const res  = await fetch(`index.php?action=${action}`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.state) {
        state = data.state;
        renderAll();
    }
    return data;
}

// ── Entry ──────────────────────────────────────────────────────────────────────
async function submitEntry() {
    const plate   = document.getElementById('entry-plate').value.trim();
    const tipeU   = document.getElementById('entry-tipe-user').value;
    const tipeK   = document.getElementById('entry-tipe-kendaraan').value;
    const zonaId  = document.getElementById('entry-zona').value;
    const slot    = document.getElementById('entry-slot').value.trim();
    const mSelect = document.getElementById('entry-member');
    const idMember = (tipeU !== 'umum' && mSelect && mSelect.value) ? mSelect.value : '';

    if (!plate) { showToast('Masukkan plat nomor!', 'error'); return; }

    const res = await api('entry', {
        plate, tipe_user: tipeU, tipe_kendaraan: tipeK, zona_id: zonaId, parking_slot: slot, id_member: idMember
    });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        document.getElementById('entry-plate').value = '';
        document.getElementById('entry-slot').value  = '';
        showQr(res.ticket);
    }
}

// ── Exit ───────────────────────────────────────────────────────────────────────
async function submitExit() {
    const plate   = document.getElementById('exit-plate').value.trim();
    const payment = document.getElementById('exit-payment').value;
    if (!plate) { showToast('Masukkan plat nomor!', 'error'); return; }

    const res = await api('exit', { plate, payment_method: payment });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        document.getElementById('exit-plate').value = '';
        showReceipt(res.receipt);
    }
}

async function quickExit(plate) {
    if (!confirm(`Keluarkan kendaraan ${plate}?`)) return;
    const res = await api('exit', { plate, payment_method: 'cash' });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) showReceipt(res.receipt);
}

// ── Member ─────────────────────────────────────────────────────────────────────
async function submitAddMember() {
    const plate    = document.getElementById('m-plate').value.trim();
    const tipeK    = document.getElementById('m-kendaraan').value;
    const mType    = document.getElementById('m-type').value;
    const tenantId = document.getElementById('m-tenant').value;
    if (!plate) { showToast('Plat nomor dibutuhkan!', 'error'); return; }

    const res = await api('member_add', {
        plat_nomor: plate, tipe_kendaraan: tipeK, membership_type: mType,
        tenant_id: tenantId || ''
    });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) document.getElementById('m-plate').value = '';
}

async function deleteMember(plate) {
    if (!confirm(`Hapus member ${plate}?`)) return;
    const res = await api('member_delete', { plat_nomor: plate });
    showToast(res.message, res.success ? 'success' : 'error');
}

// ── Zona ───────────────────────────────────────────────────────────────────────
async function submitAddZona() {
    const nama   = document.getElementById('z-nama').value.trim();
    const slots  = document.getElementById('z-slots').value;
    const floor  = document.getElementById('z-floor').value;
    if (!nama || !slots) { showToast('Nama zona & total slot dibutuhkan!', 'error'); return; }

    const res = await api('zona_add', { name: nama, slots, floor_id: floor || '' });
    showToast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
        document.getElementById('z-nama').value  = '';
        document.getElementById('z-slots').value = '';
    }
}

async function deleteZona(zonaId, namaZona) {
    if (!confirm(`Hapus zona "${namaZona}"?`)) return;
    const res = await api('zona_delete', { zona_id: zonaId });
    showToast(res.message, res.success ? 'success' : 'error');
}

// ── Reset stats ────────────────────────────────────────────────────────────────
async function confirmResetStats() {
    if (!confirm('Reset semua transaksi hari ini? Data kendaraan aktif tidak terhapus.')) return;
    const res = await api('stats_reset');
    showToast(res.message, res.success ? 'success' : 'error');
}

// ── Render ─────────────────────────────────────────────────────────────────────
function renderAll() {
    // Kapasitas header
    document.getElementById('capacity-text').textContent = `${state.occupied}/${state.totalSlots}`;
    document.getElementById('dash-total').textContent    = state.totalSlots;
    document.getElementById('dash-occupied').textContent = state.occupied;
    document.getElementById('dash-available').textContent= state.available;

    // Stats
    document.getElementById('stat-entry').textContent   = state.stats.entry;
    document.getElementById('stat-exit').textContent    = state.stats.exit;
    document.getElementById('stat-revenue').textContent = 'Rp ' + formatCurrency(state.stats.revenue);

    renderVehiclesTable();
    renderTransactionsTable();
    renderMemberTable();
    renderZonaTable();
    renderZonaDropdown();
    renderMemberDropdown();
}

function renderVehiclesTable() {
    const tbody = document.getElementById('vehicles-table');
    const vehicles = Object.entries(state.vehicles || {});
    if (!vehicles.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Parkir kosong</td></tr>';
        return;
    }
    tbody.innerHTML = '';
    const now = Math.floor(Date.now() / 1000);
    vehicles.forEach(([plate, v]) => {
        const dur = Math.max(1, Math.ceil((now - v.time) / 60));
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td><strong>${plate}</strong></td>
                <td><span class="badge badge-${badgeClass(v.type)}">${typeLabel(v.type)}</span></td>
                <td>${capitalize(v.tipeKendaraan || 'mobil')}</td>
                <td>${v.zona_id || '-'}</td>
                <td>${v.parking_slot || '-'}</td>
                <td>${formatTime(v.time)}</td>
                <td>${dur} mnt</td>
                <td><button class="btn-small btn-danger" onclick="quickExit('${plate}')">Keluarkan</button></td>
            </tr>
        `);
    });
}

function renderTransactionsTable() {
    const tbody = document.getElementById('transactions-table');
    const txs   = state.transactions || [];
    if (!txs.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Belum ada transaksi</td></tr>';
        return;
    }
    tbody.innerHTML = '';
    txs.forEach(tx => {
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>${tx.plate}</td>
                <td><span class="badge badge-${badgeClass(tx.type)}">${typeLabel(tx.type)}</span></td>
                <td>${tx.entryTime || '-'}</td>
                <td>${tx.exitTime}</td>
                <td>${tx.duration} mnt</td>
                <td><strong>Rp ${formatCurrency(tx.total)}</strong></td>
            </tr>
        `);
    });
}

function renderMemberTable() {
    const tbody = document.getElementById('member-list');
    const members = state.members || [];
    if (!members.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Belum ada member</td></tr>';
        return;
    }
    tbody.innerHTML = '';
    members.forEach(m => {
        const plate = m.plate || m.plat_nomor || '-';
        const type  = m.type  || m.membership_type || '-';
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>${plate}</td>
                <td>${capitalize(m.tipeKendaraan || '-')}</td>
                <td><span class="badge badge-info">${type}</span></td>
                <td>${m.name || '-'}</td>
                <td>${m.discountPercent || 0}%</td>
                <td><button class="btn-small btn-danger" onclick="deleteMember('${plate}')">Hapus</button></td>
            </tr>
        `);
    });
}

function renderZonaTable() {
    const tbody = document.getElementById('zona-list');
    if (!tbody) return;
    const zonas = state.zonas || [];
    tbody.innerHTML = '';
    if (!zonas.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Belum ada zona</td></tr>';
        return;
    }
    zonas.forEach(z => {
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>${z.id_zona}</td>
                <td>${z.nama_zona}</td>
                <td>${z.total_slot}</td>
                <td>${z.occupied_slot}</td>
                <td>${z.total_slot - z.occupied_slot}</td>
                <td><button class="btn-small btn-danger" onclick="deleteZona(${z.id_zona}, '${z.nama_zona}')">Hapus</button></td>
            </tr>
        `);
    });

    // Update monitoring zona
    const mTbody = document.getElementById('zona-monitor-table');
    if (!mTbody) return;
    mTbody.innerHTML = '';
    zonas.forEach(z => {
        const util = z.total_slot > 0 ? Math.round(z.occupied_slot / z.total_slot * 100) : 0;
        const color = util >= 90 ? '#ef4444' : (util >= 70 ? '#f59e0b' : '#22c55e');
        mTbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>${z.nama_zona}</td>
                <td>${z.total_slot}</td>
                <td>${z.occupied_slot}</td>
                <td>${z.total_slot - z.occupied_slot}</td>
                <td>
                    <div style="background:#e5e7eb;border-radius:4px;height:10px;width:100%">
                        <div style="background:${color};width:${util}%;height:10px;border-radius:4px"></div>
                    </div>
                    ${util}%
                </td>
            </tr>
        `);
    });
}

function renderZonaDropdown() {
    const sel = document.getElementById('entry-zona');
    if (!sel) return;
    const cur = sel.value;
    sel.innerHTML = '';
    (state.zonas || []).forEach(z => {
        const opt = document.createElement('option');
        opt.value       = z.id_zona;
        opt.textContent = `${z.nama_zona} (${z.total_slot - z.occupied_slot} tersedia)`;
        sel.appendChild(opt);
    });
    if (cur) sel.value = cur;
}

function renderMemberDropdown() {
    const sel = document.getElementById('entry-member');
    if (!sel) return;
    sel.innerHTML = '<option value="">-- Pilih Member --</option>';
    (state.members || []).forEach(m => {
        const opt = document.createElement('option');
        opt.value       = m.id_member ?? '';
        opt.textContent = `${m.plate || m.plat_nomor} (${m.type || m.membership_type})`;
        sel.appendChild(opt);
    });
}

function updateEntryForm() {
    const tipe    = document.getElementById('entry-tipe-user').value;
    const wrapper = document.getElementById('entry-member-wrapper');
    wrapper.classList.toggle('hidden', tipe === 'umum');
}

// ── UI helpers ─────────────────────────────────────────────────────────────────
function showQr(ticket) {
    const box = document.getElementById('qr-entry');
    box.innerHTML = '';
    if (!ticket) { box.classList.add('hidden'); return; }
    box.classList.remove('hidden');
    box.innerHTML = `<p class="qr-label">Tiket: <strong>${ticket}</strong></p>`;
    new QRCode(box, { text: ticket, width: 140, height: 140 });
}

function showReceipt(r) {
    if (!r) return;
    const container = document.getElementById('receipt');
    const content   = document.getElementById('receipt-content');
    container.classList.remove('hidden');
    content.innerHTML = `
        <p class="receipt-line"><span>Plat Nomor:</span> <strong>${r.plate}</strong></p>
        <p class="receipt-line"><span>Nama/Perusahaan:</span> <strong>${r.owner_name || '-'}</strong></p>
        <p class="receipt-line"><span>Tiket:</span> <strong>${r.ticket || '-'}</strong></p>
        <p class="receipt-line"><span>Waktu Masuk:</span> <strong>${r.entryTime || '-'}</strong></p>
        <p class="receipt-line"><span>Waktu Keluar:</span> <strong>${r.exitTime || '-'}</strong></p>
        <p class="receipt-line"><span>Durasi:</span> <strong>${r.hours} jam ${r.duration % 60} menit</strong></p>
        <hr>
        <p class="receipt-line"><span>Tarif:</span> Rp ${formatCurrency(r.baseTariff)}</p>
        ${r.discountAmount > 0 ? `<p class="receipt-line discount"><span>Diskon ${r.discountPercent}%:</span> -Rp ${formatCurrency(r.discountAmount)}</p>` : ''}
        <p class="receipt-line total"><span>Total Bayar:</span> <strong>Rp ${formatCurrency(r.total)}</strong></p>
    `;
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className   = `toast ${type}`;
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3500);
}

function formatTime(ts) {
    return new Date(ts * 1000).toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
}

function formatCurrency(v) {
    return Number(v).toLocaleString('id-ID');
}

function typeLabel(t) {
    if (t === 'member') return 'Member';
    if (t === 'corporate' || t === 'korporat') return 'Korporat';
    return 'Pengunjung';
}

function badgeClass(t) {
    if (t === 'member') return 'member';
    if (t === 'corporate' || t === 'korporat') return 'corporate';
    return 'regular';
}

function capitalize(v) {
    if (!v) return '';
    return v.charAt(0).toUpperCase() + v.slice(1);
}

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    btn.classList.add('active');
}

function closeModal() {
    document.getElementById('modal-backdrop').classList.add('hidden');
    document.getElementById('modal-container').classList.add('hidden');
}

// ── Init ───────────────────────────────────────────────────────────────────────
renderAll();
</script>
</body>
</html>
