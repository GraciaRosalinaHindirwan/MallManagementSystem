<?php
/**
 * views/petugas.php — PBI-M04-02-01
 * Petugas: Entry / Exit kendaraan via QR / tiket digital.
 */
$role       = 'petugas';
$pageTitle  = 'Entry / Exit Kendaraan';
$currentNav = 'entry';
require_once __DIR__ . '/layout.php';
?>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card stat-blue">
    <div class="stat-icon"><i class="fas fa-car-side"></i></div>
    <div class="stat-info"><h3 id="p-total"><?= (int)$state['totalSlots'] ?></h3><p>Total Slot</p></div>
  </div>
  <div class="stat-card stat-red">
    <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:var(--danger)"><i class="fas fa-car"></i></div>
    <div class="stat-info"><h3 id="p-occupied"><?= (int)$state['occupied'] ?></h3><p>Terisi</p></div>
  </div>
  <div class="stat-card stat-green">
    <div class="stat-icon" style="background:rgba(34,197,94,0.15);color:var(--success)"><i class="fas fa-parking"></i></div>
    <div class="stat-info"><h3 id="p-available"><?= (int)$state['available'] ?></h3><p>Tersedia</p></div>
  </div>
</div>

<!-- ENTRY + EXIT -->
<div class="grid-2">

  <!-- ENTRY -->
  <div class="card" id="entry">
    <div class="card-title"><i class="fas fa-sign-in-alt"></i> Entry Kendaraan</div>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Scan QR tiket atau input plat nomor manual.</div>

    <div class="form-group">
      <label>Plat Nomor</label>
      <input type="text" id="e-plate" placeholder="B 1234 XYZ" style="text-transform:uppercase" onkeypress="if(event.key==='Enter') doEntry()">
    </div>
    <div class="form-group">
      <label>Tipe Kendaraan</label>
      <select id="e-kendaraan">
        <option value="mobil">Mobil</option>
        <option value="motor">Motor</option>
        <option value="truk">Truk</option>
      </select>
    </div>
    <div class="form-group">
      <label>Tipe Pengguna</label>
      <select id="e-tipe" onchange="toggleMemberSelect()">
        <option value="umum">Pengunjung Umum</option>
        <option value="member">Member</option>
        <option value="korporat">Korporat</option>
      </select>
    </div>
    <div class="form-group hidden" id="member-select-wrap">
      <label>Pilih Member (opsional)</label>
      <select id="e-member">
        <option value="">-- Pilih Member --</option>
        <?php foreach ($state['members'] as $m): ?>
          <option value="<?= (int)$m['id_member'] ?>">
            <?= htmlspecialchars($m['plate'] ?? $m['plat_nomor'] ?? '-') ?>
            (<?= htmlspecialchars($m['type'] ?? $m['membership_type'] ?? '-') ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Zona</label>
      <select id="e-zona">
        <?php foreach ($state['zonas'] as $z): ?>
          <option value="<?= (int)$z['id_zona'] ?>">
            <?= htmlspecialchars($z['nama_zona']) ?> (<?= (int)$z['total_slot']-(int)$z['occupied_slot'] ?> tersedia)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Nomor Slot <span style="color:var(--text-secondary)">(opsional)</span></label>
      <input type="text" id="e-slot" placeholder="Contoh: A-12">
    </div>
    <button class="btn btn-primary btn-full" style="margin-top:4px" onclick="doEntry()">
      <i class="fas fa-sign-in-alt"></i> Proses Masuk
    </button>
    <div id="qr-box" class="qr-box hidden" style="margin-top:16px">
      <p id="qr-label" style="color:#082A53;font-weight:600;margin-bottom:8px">-</p>
      <div id="qr-canvas"></div>
    </div>
  </div>

  <!-- EXIT -->
  <div class="card" id="exit">
    <div class="card-title"><i class="fas fa-sign-out-alt"></i> Exit Kendaraan</div>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Input plat atau scan QR tiket saat keluar.</div>

    <div class="form-group">
      <label>Plat Nomor</label>
      <input type="text" id="x-plate" placeholder="B 1234 XYZ" style="text-transform:uppercase" onkeypress="if(event.key==='Enter') doExit()">
    </div>
    <div class="form-group">
      <label>Metode Pembayaran</label>
      <select id="x-payment">
        <option value="cash">Cash</option>
        <option value="cashless">Cashless / QRIS</option>
        <option value="e-toll">E-Toll</option>
        <option value="member-auto">Member Auto</option>
      </select>
    </div>
    <button class="btn btn-full" style="background:var(--danger);color:#fff;margin-top:4px" onclick="doExit()">
      <i class="fas fa-sign-out-alt"></i> Kalkulasi &amp; Proses Keluar
    </button>
    <div id="receipt-box" class="receipt-box hidden">
      <h4><i class="fas fa-receipt"></i> Struk Pembayaran</h4>
      <div id="receipt-content"></div>
    </div>
  </div>
</div>

<!-- KENDARAAN AKTIF -->
<div class="card" id="aktif" style="margin-top:24px">
  <div class="card-title" style="justify-content:space-between">
    <span><i class="fas fa-car"></i> Kendaraan di Dalam Parkir</span>
    <button class="btn btn-accent btn-sm" onclick="refreshVehicles()"><i class="fas fa-sync-alt"></i> Refresh</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Plat Nomor</th><th>Tipe</th><th>Kendaraan</th><th>Zona</th><th>Slot</th><th>Masuk</th><th>Durasi</th><th>Aksi</th></tr></thead>
      <tbody id="vehicles-tbody">
        <?php if (empty($state['vehicles'])): ?>
          <tr><td colspan="8"><div class="empty-state"><i class="fas fa-parking"></i> Parkir kosong</div></td></tr>
        <?php else: ?>
          <?php foreach ($state['vehicles'] as $plate => $v):
            $dur = max(1, (int)ceil((time() - ($v['time']??time())) / 60));
          ?>
            <tr>
              <td><strong><?= htmlspecialchars($plate) ?></strong></td>
              <td><span class="badge badge-<?= phpBadgeClass($v['type']??'umum') ?>"><?= phpTypeLabel($v['type']??'umum') ?></span></td>
              <td><?= phpCap($v['tipeKendaraan']??'mobil') ?></td>
              <td><?= htmlspecialchars((string)($v['zona_id']??'-')) ?></td>
              <td><?= htmlspecialchars($v['parking_slot']??'-') ?></td>
              <td><?= date('H:i:s', $v['time']??time()) ?></td>
              <td><?= $dur ?> mnt</td>
              <td>
                <button class="btn btn-danger btn-sm" onclick="quickExit('<?= htmlspecialchars($plate) ?>')">
                  <i class="fas fa-sign-out-alt"></i> Keluarkan
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function toggleMemberSelect() {
  const tipe = document.getElementById('e-tipe').value;
  document.getElementById('member-select-wrap').classList.toggle('hidden', tipe === 'umum');
}

async function doEntry() {
  const plate    = document.getElementById('e-plate').value.trim().toUpperCase();
  const kendaraan= document.getElementById('e-kendaraan').value;
  const tipe     = document.getElementById('e-tipe').value;
  const zona     = document.getElementById('e-zona').value;
  const slot     = document.getElementById('e-slot').value.trim();
  const member   = document.getElementById('e-member')?.value || '';
  if (!plate) { showToast('Masukkan plat nomor!','error'); return; }
  const res = await api('entry',{ plate, tipe_kendaraan:kendaraan, tipe_user:tipe, zona_id:zona, parking_slot:slot, id_member:tipe!=='umum'?member:'' });
  showToast(res.message, res.success?'success':'error');
  if (res.success) {
    document.getElementById('e-plate').value = '';
    document.getElementById('e-slot').value  = '';
    showQr(res.ticket, plate);
    if (res.state) { renderPetugasStats(res.state); renderVehicles(res.state); }
  }
}

async function doExit() {
  const plate   = document.getElementById('x-plate').value.trim().toUpperCase();
  const payment = document.getElementById('x-payment').value;
  if (!plate) { showToast('Masukkan plat nomor!','error'); return; }
  const res = await api('exit',{ plate, payment_method:payment });
  showToast(res.message, res.success?'success':'error');
  if (res.success) {
    document.getElementById('x-plate').value = '';
    showReceipt(res.receipt);
    if (res.state) { renderPetugasStats(res.state); renderVehicles(res.state); }
  }
}

async function quickExit(plate) {
  if (!confirm(`Keluarkan kendaraan ${plate}?`)) return;
  const res = await api('exit',{ plate, payment_method:'cash' });
  showToast(res.message, res.success?'success':'error');
  if (res.success) { showReceipt(res.receipt); if (res.state) { renderPetugasStats(res.state); renderVehicles(res.state); } }
}

async function refreshVehicles() {
  const res = await api('state',{});
  if (res.state) { renderPetugasStats(res.state); renderVehicles(res.state); }
}

function renderPetugasStats(s) {
  document.getElementById('p-total').textContent    = s.totalSlots;
  document.getElementById('p-occupied').textContent = s.occupied;
  document.getElementById('p-available').textContent= s.available;
}

function renderVehicles(state) {
  const tbody = document.getElementById('vehicles-tbody');
  const vehicles = Object.entries(state.vehicles || {});
  if (!vehicles.length) { tbody.innerHTML='<tr><td colspan="8"><div class="empty-state"><i class="fas fa-parking"></i> Parkir kosong</div></td></tr>'; return; }
  const now = Math.floor(Date.now()/1000);
  tbody.innerHTML = vehicles.map(([plate,v]) => {
    const dur = Math.max(1, Math.ceil((now - v.time)/60));
    return `<tr>
      <td><strong>${plate}</strong></td>
      <td><span class="badge badge-${badgeClass(v.type)}">${typeLabel(v.type)}</span></td>
      <td>${jsCap(v.tipeKendaraan||'mobil')}</td>
      <td>${v.zona_id||'-'}</td>
      <td>${v.parking_slot||'-'}</td>
      <td>${tsToTime(v.time)}</td>
      <td>${dur} mnt</td>
      <td><button class="btn btn-danger btn-sm" onclick="quickExit('${plate}')"><i class="fas fa-sign-out-alt"></i> Keluarkan</button></td>
    </tr>`;
  }).join('');
}

function showQr(ticket, plate) {
  if (!ticket) return;
  const box = document.getElementById('qr-box');
  document.getElementById('qr-canvas').innerHTML = '';
  document.getElementById('qr-label').textContent = `Tiket: ${ticket} | Plat: ${plate}`;
  box.classList.remove('hidden');
  new QRCode(document.getElementById('qr-canvas'), { text:ticket, width:150, height:150 });
}

function showReceipt(r) {
  if (!r) return;
  document.getElementById('receipt-box').classList.remove('hidden');
  document.getElementById('receipt-content').innerHTML = `
    <div class="receipt-line"><span>Plat Nomor</span><strong>${r.plate}</strong></div>
    <div class="receipt-line"><span>Waktu Masuk</span><strong>${r.entryTime||'-'}</strong></div>
    <div class="receipt-line"><span>Waktu Keluar</span><strong>${r.exitTime||'-'}</strong></div>
    <div class="receipt-line"><span>Durasi</span><strong>${r.hours||1} jam ${(r.duration||0)%60} mnt</strong></div>
    <div class="receipt-line"><span>Biaya</span><strong>${formatRp(r.baseTariff||0)}</strong></div>
    ${r.discountAmount>0?`<div class="receipt-line discount"><span>Diskon ${r.discountPercent}%</span><strong>-${formatRp(r.discountAmount)}</strong></div>`:''}
    <div class="receipt-line total"><span>Total Bayar</span><strong>${formatRp(r.total||0)}</strong></div>`;
}
</script>

<?php require_once __DIR__ . '/layout_close.php'; ?>
