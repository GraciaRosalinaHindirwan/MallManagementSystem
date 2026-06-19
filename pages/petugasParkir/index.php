<?php
require_once 'parking.php';

function respondJson($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$action = $_GET['action'] ?? null;
if ($action !== null) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = $_POST;
        if (empty($body) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $body = array_merge($body, $json);
            }
        }
    } else {
        $body = $_GET;
    }

    switch ($action) {
        case 'state':
            respondJson(['success' => true, 'state' => getParkingState()]);
            break;
        case 'entry':
            $plate = $body['plate'] ?? '';
            $type = $body['type'] ?? 'regular';
            $owner_name = $body['owner_name'] ?? '';
            respondJson(processEntry($plate, $type, $owner_name));
            break;
        case 'exit':
            $plate = $body['plate'] ?? '';
            respondJson(processExit($plate));
            break;
        case 'member_add':
            $name = $body['name'] ?? '';
            $email = $body['email'] ?? '';
            $phone = $body['phone'] ?? '';
            $type = $body['type'] ?? 'regular';
            respondJson(addMember($name, $email, $phone, $type));
            break;
        case 'member_delete':
            $email = $body['email'] ?? '';
            respondJson(deleteMember($email));
            break;
        case 'subscription_add':
            $name = $body['name'] ?? '';
            $slots = $body['slots'] ?? 0;
            $package = $body['package'] ?? 'basic';
            respondJson(addSubscription($name, $slots, $package));
            break;
        case 'subscription_delete':
            $name = $body['name'] ?? '';
            respondJson(deleteSubscription($name));
            break;
        case 'stats_reset':
            respondJson(resetStats());
            break;
        case 'transactions_clear':
            respondJson(clearTransactions());
            break;
        default:
            respondJson(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    }
}

$state = getParkingState();

function typeLabel($type) {
    return match ($type) {
        'member' => 'Member',
        'corporate' => 'Korporat',
        default => 'Pengunjung Biasa',
    };
}

function badgeClass($type) {
    return match ($type) {
        'member' => 'member',
        'corporate' => 'corporate',
        default => 'regular',
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
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <span class="capacity-status"><i class="fas fa-info-circle"></i> <span id="capacity-text"><?= $state['occupied'] ?>/<?= $state['totalSlots'] ?></span></span>
                </div>
            </header>

            <div class="tabs">
                <button class="tab-btn active" type="button" onclick="switchTab('entry', this)"><i class="fas fa-sign-in-alt"></i> Entry/Exit</button>
                <button class="tab-btn" type="button" onclick="switchTab('monitoring', this)"><i class="fas fa-chart-line"></i> Monitoring</button>
                <button class="tab-btn" type="button" onclick="switchTab('members', this)"><i class="fas fa-users"></i> Member</button>
                <button class="tab-btn" type="button" onclick="switchTab('subscription', this)"><i class="fas fa-receipt"></i> Langganan</button>
            </div>

            <div class="content-wrapper">
                <div id="tab-entry" class="tab-content active">
                    <section class="dashboard-cards">
                        <div class="card status-total"><p>Total Slot</p><h3 id="dash-total"><?= $state['totalSlots'] ?></h3></div>
                        <div class="card status-occupied"><p>Terisi</p><h3 id="dash-occupied"><?= $state['occupied'] ?></h3></div>
                        <div class="card status-available"><p>Tersedia</p><h3 id="dash-available"><?= $state['available'] ?></h3></div>
                    </section>

                    <div class="grid-2">
                        <div class="card panel">
                            <h3><i class="fas fa-sign-in-alt"></i> Entry Kendaraan</h3>
                            <input type="text" id="entry-plate" placeholder="Plat Nomor (B 1234 XYZ)" onkeypress="if(event.key==='Enter') submitEntry()">
                            <select id="entry-type" onchange="updateEntryForm()">
                                <option value="regular">Pengunjung Biasa</option>
                                <option value="member">Member</option>
                                <option value="corporate">Korporat</option>
                            </select>
                            <select id="entry-member" class="hidden" onchange="">
                                <option value="">-- Pilih Member --</option>
                            </select>
                            <select id="entry-corporate" class="hidden" onchange="">
                                <option value="">-- Pilih Korporat --</option>
                            </select>
                            <button class="btn btn-primary w-100" type="button" onclick="submitEntry()"><i class="fas fa-arrow-right"></i> Scan Masuk</button>
                            <div id="qr-entry" class="qr-box hidden"></div>
                        </div>
                        <div class="card panel">
                            <h3><i class="fas fa-sign-out-alt"></i> Exit Kendaraan</h3>
                            <input type="text" id="exit-plate" placeholder="Plat Nomor..." onkeypress="if(event.key==='Enter') submitExit()">
                            <button class="btn btn-danger w-100" type="button" onclick="submitExit()"><i class="fas fa-arrow-left"></i> Kalkulasi & Keluar</button>
                            <div id="receipt" class="receipt-box hidden">
                                <h4><i class="fas fa-receipt"></i> Struk Pembayaran</h4>
                                <div id="receipt-content"></div>
                            </div>
                        </div>
                    </div>

                    <section class="card panel mt-20">
                        <h3><i class="fas fa-car"></i> Kendaraan di Dalam Mall</h3>
                        <table class="table">
                            <thead><tr><th>Plat</th><th>Tipe</th><th>Nama/Perusahaan</th><th>Waktu Masuk</th><th>Durasi</th></tr></thead>
                            <tbody id="parking-list-body">
                            <?php if (empty($state['vehicles'])): ?>
                                <tr><td colspan="5" class="text-center">Parkiran kosong</td></tr>
                            <?php else: ?>
                                <?php foreach ($state['vehicles'] as $plate => $v): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($plate) ?></td>
                                        <td><span class="badge badge-<?= badgeClass($v['type']) ?>"><?= typeLabel($v['type']) ?></span></td>
                                        <td><?= htmlspecialchars($v['owner_name'] ?? '-') ?></td>
                                        <td><?= date('H:i:s', $v['time']) ?></td>
                                        <td><?= ceil((time() - $v['time']) / 60) ?> menit</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </section>
                </div>

                <div id="tab-monitoring" class="tab-content hidden">
                    <div class="grid-2">
                        <div class="card panel">
                            <h3><i class="fas fa-gauge-high"></i> Kapasitas Real-Time</h3>
                            <div class="capacity-gauge">
                                <div class="gauge-circle">
                                    <svg viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="45" class="gauge-bg"></circle>
                                        <circle cx="50" cy="50" r="45" class="gauge-progress" id="gauge-progress"></circle>
                                    </svg>
                                    <div class="gauge-text">
                                        <span id="gauge-percent"><?= round($state['occupied'] / max(1, $state['totalSlots']) * 100) ?>%</span>
                                        <small>Terisi</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card panel">
                            <h3><i class="fas fa-coins"></i> Statistik Hari Ini</h3>
                            <div class="stat-actions">
                                <button class="btn-small" type="button" onclick="resetStats()"><i class="fas fa-eraser"></i> Reset Statistik</button>
                                <button class="btn-small" type="button" onclick="clearTransactions()"><i class="fas fa-trash"></i> Hapus Riwayat</button>
                            </div>
                            <div class="stat-row"><span>Total Kendaraan Masuk:</span><strong id="stat-entry"><?= $state['stats']['entry'] ?></strong></div>
                            <div class="stat-row"><span>Total Kendaraan Keluar:</span><strong id="stat-exit"><?= $state['stats']['exit'] ?></strong></div>
                            <div class="stat-row"><span>Total Pendapatan:</span><strong id="stat-revenue">Rp <?= number_format($state['stats']['revenue'], 0, ',', '.') ?></strong></div>
                            <div class="stat-row"><span>Rata-rata Durasi:</span><strong id="stat-avg-duration">- menit</strong></div>
                        </div>
                    </div>
                    <div class="card panel mt-20">
                        <h3><i class="fas fa-history"></i> Riwayat Transaksi Hari Ini</h3>
                        <table class="table">
                            <thead><tr><th>Plat</th><th>Tipe</th><th>Waktu Keluar</th><th>Durasi</th><th>Tarif</th></tr></thead>
                            <tbody id="transaction-list"><tr><td colspan="5">Belum ada transaksi</td></tr></tbody>
                        </table>
                    </div>
                </div>

                <div id="tab-members" class="tab-content hidden">
                    <div class="grid-2">
                        <div class="card panel">
                            <h3><i class="fas fa-user-plus"></i> Daftarkan Member Baru</h3>
                            <input type="text" id="member-name" placeholder="Nama Member">
                            <input type="email" id="member-email" placeholder="Email">
                            <input type="text" id="member-phone" placeholder="No. Telepon">
                            <select id="member-type">
                                <option value="regular">Regular</option>
                                <option value="premium">Premium</option>
                            </select>
                            <button class="btn btn-primary w-100" type="button" onclick="submitMember()"><i class="fas fa-save"></i> Simpan Member</button>
                        </div>
                        <div class="card panel">
                            <h3><i class="fas fa-search"></i> Cari Member</h3>
                            <input type="text" id="search-member" placeholder="Email atau No. Telepon" oninput="searchMember(this.value)">
                            <div id="member-search-result" class="search-result hidden">
                                <div id="member-info"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card panel mt-20">
                        <h3><i class="fas fa-list"></i> Daftar Member</h3>
                        <table class="table">
                            <thead><tr><th>Nama</th><th>Email</th><th>Tipe</th><th>Diskon</th><th>Aksi</th></tr></thead>
                            <tbody id="member-list"><tr><td colspan="5">Belum ada member</td></tr></tbody>
                        </table>
                    </div>
                </div>

                <div id="tab-subscription" class="tab-content hidden">
                    <div class="card panel">
                        <h3><i class="fas fa-handshake"></i> Manajemen Langganan Korporat</h3>
                        <div class="form-grid">
                            <div><input type="text" id="corp-name" placeholder="Nama Perusahaan"></div>
                            <div><input type="number" id="corp-slots" placeholder="Jumlah Slot" min="1"></div>
                            <div>
                                <select id="corp-package">
                                    <option value="basic">Basic (20% diskon)</option>
                                    <option value="premium">Premium (35% diskon)</option>
                                    <option value="ultimate">Ultimate (50% diskon)</option>
                                </select>
                            </div>
                            <div><button class="btn btn-primary" type="button" onclick="submitSubscription()"><i class="fas fa-plus"></i> Tambah Langganan</button></div>
                        </div>
                    </div>
                    <div class="card panel mt-20">
                        <h3><i class="fas fa-building"></i> Daftar Langganan Korporat</h3>
                        <table class="table">
                            <thead><tr><th>Perusahaan</th><th>Slot</th><th>Paket</th><th>Diskon</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody id="subscription-list"><tr><td colspan="6">Belum ada langganan</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        const initialState = <?= json_encode($state, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let state = JSON.parse(JSON.stringify(initialState));
        let clientEntryTime = null;
        let transactionsVisibleCount = 5;
        let transactionsShowingAll = false;

        document.addEventListener('DOMContentLoaded', () => {
            renderState();
            fetchState();
            setInterval(fetchState, 5000);
        });

        function updateEntryForm() {
            const type = document.getElementById('entry-type').value;
            const memberSelect = document.getElementById('entry-member');
            const corporateSelect = document.getElementById('entry-corporate');

            memberSelect.classList.add('hidden');
            corporateSelect.classList.add('hidden');

            if (type === 'member') {
                memberSelect.innerHTML = '<option value="">-- Pilih Member --</option>';
                state.members.forEach(m => {
                    memberSelect.innerHTML += `<option value="${m.email}">${m.name}</option>`;
                });
                memberSelect.classList.remove('hidden');
            } else if (type === 'corporate') {
                corporateSelect.innerHTML = '<option value="">-- Pilih Korporat --</option>';
                state.subscriptions.forEach(s => {
                    corporateSelect.innerHTML += `<option value="${s.name}">${s.name}</option>`;
                });
                corporateSelect.classList.remove('hidden');
            }
        }

        async function fetchState() {
            try {
                const response = await fetch('index.php?action=state');
                const data = await response.json();
                if (data.success) {
                    state = data.state;
                    renderState();
                }
            } catch (error) {
                console.error('Fetch state failed', error);
            }
        }

        async function submitEntry() {
            const plate = document.getElementById('entry-plate').value.trim();
            const type = document.getElementById('entry-type').value;
            if (!plate) return showToast('Plat nomor wajib diisi!', 'error');

            let ownerName = '';
            if (type === 'member') {
                ownerName = document.getElementById('entry-member').value;
                if (!ownerName) return showToast('Pilih member terlebih dahulu!', 'error');
            } else if (type === 'corporate') {
                ownerName = document.getElementById('entry-corporate').value;
                if (!ownerName) return showToast('Pilih korporat terlebih dahulu!', 'error');
            }

            clientEntryTime = Math.floor(Date.now() / 1000);
            const payload = { plate, type, owner_name: ownerName, entry_time_ms: Date.now() };
            const response = await sendRequest('entry', payload);
            if (!response) return;
            if (!response.success) return showToast(response.message, 'error');

            state = response.state;
            renderState();
            document.getElementById('entry-plate').value = '';
            document.getElementById('entry-member').value = '';
            document.getElementById('entry-corporate').value = '';
            updateEntryForm();
            showToast(response.message, 'success');
            showQr(response.ticket);
        }

        async function submitExit() {
            const plate = document.getElementById('exit-plate').value.trim();
            if (!plate) return showToast('Plat nomor wajib diisi!', 'error');

            // show immediate temporary receipt with client time for UX
            const nowObj = new Date();
            const clientExitTime = nowObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const vehicle = state.vehicles[plate];
            const tempReceipt = {
                plate: plate,
                owner_name: vehicle ? (vehicle.owner_name || '-') : '-',
                ticket: vehicle ? (vehicle.ticket || '-') : '-',
                entryTime: vehicle ? formatTime(vehicle.time) : '-',
                clientExitTime: clientExitTime,
                hours: 0,
                duration: 0,
                baseTariff: 0,
                discountPercent: 0,
                discountAmount: 0,
                total: 0,
            };
            showReceipt(tempReceipt);

            const response = await sendRequest('exit', { plate });
            if (!response) return;
            if (!response.success) return showToast(response.message, 'error');

            state = response.state;
            renderState();
            document.getElementById('exit-plate').value = '';
            showToast(response.message, 'success');
            // replace temporary receipt with server-authoritative receipt
            showReceipt(response.receipt);
        }

        async function submitMember() {
            const name = document.getElementById('member-name').value.trim();
            const email = document.getElementById('member-email').value.trim();
            const phone = document.getElementById('member-phone').value.trim();
            const type = document.getElementById('member-type').value;
            if (!name || !email || !phone) return showToast('Semua field member harus diisi!', 'error');

            const response = await sendRequest('member_add', { name, email, phone, type });
            if (!response) return;
            if (!response.success) return showToast(response.message, 'error');

            state = response.state;
            renderState();
            document.getElementById('member-name').value = '';
            document.getElementById('member-email').value = '';
            document.getElementById('member-phone').value = '';
            showToast(response.message, 'success');
        }

        async function submitSubscription() {
            const name = document.getElementById('corp-name').value.trim();
            const slots = parseInt(document.getElementById('corp-slots').value, 10);
            const pkg = document.getElementById('corp-package').value;
            if (!name || !slots || slots <= 0) return showToast('Nama perusahaan dan jumlah slot harus diisi!', 'error');

            const response = await sendRequest('subscription_add', { name, slots, package: pkg });
            if (!response) return;
            if (!response.success) return showToast(response.message, 'error');

            state = response.state;
            renderState();
            document.getElementById('corp-name').value = '';
            document.getElementById('corp-slots').value = '';
            showToast(response.message, 'success');
        }

        async function deleteMember(email) {
            if (!confirm('Hapus member ini?')) return;
            const response = await sendRequest('member_delete', { email });
            if (!response) return;
            if (!response.success) return showToast(response.message, 'error');

            state = response.state;
            renderState();
            showToast(response.message, 'success');
        }

        async function deleteSubscription(name) {
            if (!confirm('Hapus langganan korporat ini?')) return;
            const response = await sendRequest('subscription_delete', { name });
            if (!response) return;
            if (!response.success) return showToast(response.message, 'error');

            state = response.state;
            renderState();
            showToast(response.message, 'success');
        }

        async function resetStats() {
            if (!confirm('Reset statistik dan riwayat transaksi?')) return;
            const response = await sendRequest('stats_reset', {});
            if (!response) return;
            if (!response.success) return showToast(response.message, 'error');
            state = response.state;
            renderState();
            showToast(response.message, 'success');
        }

        async function clearTransactions() {
            if (!confirm('Hapus semua riwayat transaksi?')) return;
            const response = await sendRequest('transactions_clear', {});
            if (!response) return;
            if (!response.success) return showToast(response.message, 'error');
            state = response.state;
            renderState();
            showToast(response.message, 'success');
        }

        function toggleTransactions() {
            transactionsShowingAll = !transactionsShowingAll;
            renderState();
        }

        async function sendRequest(action, payload) {
            try {
                const response = await fetch(`index.php?action=${encodeURIComponent(action)}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                return await response.json();
            } catch (error) {
                console.error('Request failed', error);
                showToast('Terjadi kesalahan jaringan.', 'error');
                return null;
            }
        }

        function renderState() {
            document.getElementById('dash-total').textContent = state.totalSlots;
            document.getElementById('dash-occupied').textContent = state.occupied;
            document.getElementById('dash-available').textContent = state.available;
            document.getElementById('capacity-text').textContent = `${state.occupied}/${state.totalSlots}`;
            document.getElementById('gauge-percent').textContent = `${Math.round((state.occupied / Math.max(1, state.totalSlots)) * 100)}%`;

            const circumference = 2 * Math.PI * 45;
            const offset = circumference - ((state.occupied / Math.max(1, state.totalSlots)) * circumference);
            const gauge = document.getElementById('gauge-progress');
            if (gauge) gauge.style.strokeDashoffset = offset;

            updateParkingTable();
            updateMonitoring();
            updateMemberTable();
            updateSubscriptionTable();
        }

        function updateParkingTable() {
            const tbody = document.getElementById('parking-list-body');
            const plates = Object.keys(state.vehicles);
            if (!plates.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">Parkiran kosong</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            const now = Math.floor(Date.now() / 1000);
            plates.forEach((plate) => {
                const v = state.vehicles[plate];
                const durationMinutes = Math.ceil((now - v.time) / 60);
                const durationText = durationMinutes < 60 ? `${durationMinutes} menit` : `${Math.floor(durationMinutes / 60)} jam ${durationMinutes % 60} m`;
                const ownerName = v.owner_name || '-';
                tbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td>${plate}</td>
                        <td><span class="badge badge-${badgeClass(v.type)}">${typeLabel(v.type)}</span></td>
                        <td>${ownerName}</td>
                        <td>${formatTime(v.time)}</td>
                        <td>${durationText}</td>
                    </tr>
                `);
            });
        }

        function updateMonitoring() {
            document.getElementById('stat-entry').textContent = state.stats.entry;
            document.getElementById('stat-exit').textContent = state.stats.exit;
            document.getElementById('stat-revenue').textContent = `Rp ${formatCurrency(state.stats.revenue)}`;
            const avgDuration = state.stats.durations.length ? Math.round(state.stats.durations.reduce((a, b) => a + b, 0) / state.stats.durations.length) : 0;
            document.getElementById('stat-avg-duration').textContent = `${avgDuration} menit`;

            const tbody = document.getElementById('transaction-list');
            if (!state.transactions.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">Belum ada transaksi</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            const txs = transactionsShowingAll ? state.transactions : state.transactions.slice(0, transactionsVisibleCount);
            txs.forEach((tx) => {
                tbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td>${tx.plate}</td>
                        <td><span class="badge badge-${badgeClass(tx.type)}">${typeLabel(tx.type)}</span></td>
                        <td>${tx.exitTime}</td>
                        <td>${tx.duration} menit</td>
                        <td><strong>Rp ${formatCurrency(tx.total)}</strong></td>
                    </tr>
                `);
            });
            const moreRow = document.getElementById('transactions-more-row');
            if (state.transactions.length > transactionsVisibleCount) {
                moreRow.classList.remove('hidden');
                document.getElementById('transactions-more-count').textContent = state.transactions.length - transactionsVisibleCount;
                document.getElementById('transactions-toggle-btn').textContent = transactionsShowingAll ? 'Tampilkan lebih sedikit' : 'Tampilkan semua';
            } else {
                moreRow.classList.add('hidden');
            }
        }

        function updateMemberTable() {
            const tbody = document.getElementById('member-list');
            if (!state.members.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">Belum ada member</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            state.members.forEach((m) => {
                const discount = m.discountPercent ?? (m.type === 'premium' ? 25 : 0);
                tbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td>${m.name}</td>
                        <td>${m.email}</td>
                        <td><span class="badge badge-info">${m.type === 'premium' ? 'Premium' : 'Regular'}</span></td>
                        <td>${discount}%</td>
                        <td><button class="btn-small" onclick="deleteMember('${m.email}')">Hapus</button></td>
                    </tr>
                `);
            });
        }

        function updateSubscriptionTable() {
            const tbody = document.getElementById('subscription-list');
            if (!state.subscriptions.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">Belum ada langganan</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            state.subscriptions.forEach((s) => {
                tbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td>${s.name}</td>
                        <td>${s.slots}</td>
                        <td><span class="badge badge-success">${capitalize(s.package)}</span></td>
                        <td>${s.discount}%</td>
                        <td><span class="badge badge-success">Aktif</span></td>
                        <td><button class="btn-small" onclick="deleteSubscription('${s.name}')">Hapus</button></td>
                    </tr>
                `);
            });
        }

        function showReceipt(receipt) {
            const container = document.getElementById('receipt');
            const content = document.getElementById('receipt-content');
            const ownerName = receipt.owner_name || '-';
            // If server provided exitTime, show it as authoritative. If only clientExitTime exists, show that as temporary.
            const clientExit = receipt.clientExitTime;
            const serverExit = receipt.exitTime;
            let exitLines = '';
            if (clientExit && !serverExit) {
                exitLines = `<p class="receipt-line"><span>Waktu Keluar (tampilan):</span> <strong>${clientExit}</strong></p>`;
            } else if (clientExit && serverExit) {
                exitLines = `<p class="receipt-line"><span>Waktu Keluar (tampilan):</span> <strong>${clientExit}</strong></p>` +
                            `<p class="receipt-line"><span>Waktu Keluar (tercatat server):</span> <strong>${serverExit}</strong></p>`;
            } else if (serverExit) {
                exitLines = `<p class="receipt-line"><span>Waktu Keluar:</span> <strong>${serverExit}</strong></p>`;
            } else {
                const exitTimeObj = new Date();
                exitLines = `<p class="receipt-line"><span>Waktu Keluar:</span> <strong>${exitTimeObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}</strong></p>`;
            }

            content.innerHTML = `
                <p class="receipt-line"><span>Plat Nomor:</span> <strong>${receipt.plate}</strong></p>
                <p class="receipt-line"><span>Nama/Perusahaan:</span> <strong>${ownerName}</strong></p>
                <p class="receipt-line"><span>Tiket:</span> <strong>${receipt.ticket || '-'}</strong></p>
                <p class="receipt-line"><span>Waktu Masuk:</span> <strong>${receipt.entryTime}</strong></p>
                ${exitLines}
                <p class="receipt-line"><span>Durasi:</span> <strong>${receipt.hours} jam ${Math.floor(receipt.duration % 60)} menit</strong></p>
                <hr>
                <p class="receipt-line"><span>Tarif Dasar:</span> Rp ${formatCurrency(receipt.baseTariff)}</p>
                ${receipt.discountAmount > 0 ? `<p class="receipt-line discount"><span>Diskon ${receipt.discountPercent}%:</span> -Rp ${formatCurrency(receipt.discountAmount)}</p>` : ''}
                <p class="receipt-line total"><span>Total Bayar:</span> <strong>Rp ${formatCurrency(receipt.total)}</strong></p>
            `;
            container.classList.remove('hidden');
        }

        // helper: show server-authoritative note below receipt
        function showServerNote() {
            const container = document.getElementById('receipt');
            let note = container.querySelector('.server-note');
            if (!note) {
                note = document.createElement('div');
                note.className = 'server-note';
                note.style.marginTop = '8px';
                note.style.fontSize = '12px';
                note.style.color = '#666';
                container.querySelector('.receipt-box')?.appendChild(note);
            }
            note.textContent = 'Catatan: Waktu yang tercatat oleh server adalah otoritatif untuk laporan dan audit.';
        }

        function showQr(ticket) {
            const qrBox = document.getElementById('qr-entry');
            qrBox.innerHTML = '';
            if (!ticket) {
                qrBox.classList.add('hidden');
                return;
            }
            qrBox.classList.remove('hidden');
            qrBox.innerHTML = `<p class="qr-label">Tiket: <strong>${ticket}</strong></p>`;
            new QRCode(qrBox, {
                text: ticket,
                width: 140,
                height: 140,
                colorDark: '#000000',
                colorLight: '#ffffff',
            });
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp * 1000);
            return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }

        function formatCurrency(value) {
            return Number(value).toLocaleString('id-ID');
        }

        function typeLabel(type) {
            return type === 'member' ? 'Member' : type === 'corporate' ? 'Korporat' : 'Pengunjung';
        }

        function badgeClass(type) {
            return type === 'member' ? 'member' : type === 'corporate' ? 'corporate' : 'regular';
        }

        function capitalize(value) {
            return value.charAt(0).toUpperCase() + value.slice(1);
        }

        function switchTab(tab, button) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.remove('hidden');
            button.classList.add('active');
        }

        function closeModal() {
            document.getElementById('modal-backdrop').classList.add('hidden');
            document.getElementById('modal-container').classList.add('hidden');
        }
    </script>
</body>
</html>
