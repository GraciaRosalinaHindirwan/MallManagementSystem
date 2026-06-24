<?php
/**
 * views/manajer.php — PBI-M04-02-04
 * Manajer: Monitoring kapasitas real-time, laporan, statistik.
 */
$role       = 'manajer';
$pageTitle  = 'Monitoring Kapasitas';
$currentNav = 'dashboard';
require_once __DIR__ . '/layout.php';
$pct = $state['totalSlots'] > 0 ? round($state['occupied'] / $state['totalSlots'] * 100) : 0;
?>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card stat-blue">
    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
    <div class="stat-info"><h3 id="m-total"><?= (int)$state['totalSlots'] ?></h3><p>Total Slot</p></div>
  </div>
  <div class="stat-card stat-red">
    <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:var(--danger)"><i class="fas fa-car"></i></div>
    <div class="stat-info"><h3 id="m-occupied"><?= (int)$state['occupied'] ?></h3><p>Kendaraan Aktif</p></div>
  </div>
  <div class="stat-card stat-green">
    <div class="stat-icon" style="background:rgba(34,197,94,0.15);color:var(--success)"><i class="fas fa-parking"></i></div>
    <div class="stat-info"><h3 id="m-available"><?= (int)$state['available'] ?></h3><p>Slot Tersedia</p></div>
  </div>
  <div class="stat-card stat-yellow">
    <div class="stat-icon" style="background:rgba(255,182,42,0.15);color:var(--warning)"><i class="fas fa-money-bill-wave"></i></div>
    <div class="stat-info"><h3 id="m-revenue" style="font-size:1.3rem">Rp <?= number_format((int)$state['stats']['revenue'],0,',','.') ?></h3><p>Pendapatan</p></div>
  </div>
</div>

<!-- GAUGE + STATISTIK HARIAN -->
<div class="grid-2" id="dashboard">

  <div class="card gauge-card">
    <div class="card-title" style="width:100%"><i class="fas fa-tachometer-alt"></i> Kapasitas Keseluruhan</div>
    <div class="gauge-wrap">
      <svg viewBox="0 0 180 180" xmlns="http://www.w3.org/2000/svg">
        <circle class="gauge-bg" cx="90" cy="90" r="80"/>
        <circle class="gauge-fg <?= $pct>=90?'crit':($pct>=70?'warn':'') ?>"
                id="gauge-circle" cx="90" cy="90" r="80"
                style="stroke-dasharray:503.2;stroke-dashoffset:503.2"/>
      </svg>
      <div class="gauge-text">
        <div class="gauge-pct" id="gauge-pct"><?= $pct ?>%</div>
        <div class="gauge-sub" id="gauge-sub"><?= (int)$state['occupied'] ?>/<?= (int)$state['totalSlots'] ?> slot</div>
      </div>
    </div>
    <span id="gauge-label" class="badge <?= $pct>=90?'badge-danger':($pct>=70?'badge-warning':'badge-success') ?>">
      <?= $pct>=90 ? 'PENUH' : ($pct>=70 ? 'HAMPIR PENUH' : 'NORMAL') ?>
    </span>
  </div>

  <div class="card" style="display:flex;flex-direction:column;gap:12px">
    <div class="card-title"><i class="fas fa-chart-bar"></i> Statistik Hari Ini</div>
    <div style="background:var(--primary-dark);border-radius:8px;padding:16px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;color:var(--text-secondary)"><i class="fas fa-sign-in-alt" style="color:var(--accent)"></i> Total Entry</span>
        <strong id="m-entry" style="font-size:20px"><?= (int)$state['stats']['entry'] ?></strong>
      </div>
      <div class="progress-wrap"><div class="progress-bar" id="pb-entry" style="width:<?= min(100,(int)$state['stats']['entry']) ?>%;background:var(--accent)"></div></div>
    </div>
    <div style="background:var(--primary-dark);border-radius:8px;padding:16px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span style="font-size:13px;color:var(--text-secondary)"><i class="fas fa-sign-out-alt" style="color:var(--success)"></i> Total Exit</span>
        <strong id="m-exit" style="font-size:20px"><?= (int)$state['stats']['exit'] ?></strong>
      </div>
      <div class="progress-wrap"><div class="progress-bar" id="pb-exit" style="width:<?= min(100,(int)$state['stats']['exit']) ?>%;background:var(--success)"></div></div>
    </div>
    <div style="background:var(--primary-dark);border-radius:8px;padding:16px;display:flex;justify-content:space-between;align-items:center">
      <span style="font-size:13px;color:var(--text-secondary)"><i class="fas fa-clock" style="color:var(--warning)"></i> Terakhir diperbarui</span>
      <span id="last-update" style="font-size:12px;color:var(--text-secondary)">-</span>
    </div>
    <div style="display:flex;gap:8px;margin-top:auto">
      <button class="btn btn-accent" style="flex:1" onclick="refreshData()"><i class="fas fa-sync-alt"></i> Refresh</button>
      <button class="btn btn-danger btn-sm" onclick="confirmReset()"><i class="fas fa-trash-alt"></i> Reset</button>
    </div>
  </div>
</div>

<!-- KAPASITAS PER ZONA -->
<div class="card" id="kapasitas">
  <div class="card-title"><i class="fas fa-th-large"></i> Kapasitas Per Zona — Real-time</div>

  <div class="tarif-grid" id="zona-cards">
    <?php foreach ($state['zonas'] as $z):
      $u = $z['total_slot']>0 ? round($z['occupied_slot']/$z['total_slot']*100) : 0;
      $color = $u>=90?'var(--danger)':($u>=70?'var(--warning)':'var(--success)');
      $badge = $u>=90?'badge-danger':($u>=70?'badge-warning':'badge-success');
    ?>
      <div class="tarif-card" style="cursor:default;border-left-color:<?= $color ?>">
        <h4><i class="fas fa-map-marker-alt" style="margin-right:6px;color:<?= $color ?>"></i><?= htmlspecialchars($z['nama_zona']) ?></h4>
        <div style="font-size:22px;font-weight:700;color:<?= $color ?>"><?= $u ?>%</div>
        <div class="tarif-sub"><?= (int)$z['occupied_slot'] ?>/<?= (int)$z['total_slot'] ?> slot terisi</div>
        <div class="progress-wrap" style="margin-top:8px"><div class="progress-bar" style="width:<?= $u ?>%;background:<?= $color ?>"></div></div>
        <span class="badge <?= $badge ?>" style="margin-top:8px"><?= $u>=90?'PENUH':($u>=70?'HAMPIR PENUH':'TERSEDIA') ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (empty($state['zonas'])): ?>
      <div class="empty-state"><i class="fas fa-map-marker-alt"></i> Belum ada zona</div>
    <?php endif; ?>
  </div>

  <div class="table-wrap" style="margin-top:20px">
    <table>
      <thead><tr><th>Zona</th><th>Total</th><th>Terisi</th><th>Tersedia</th><th>Utilisasi</th><th>Status</th></tr></thead>
      <tbody id="zona-monitor-tbody">
        <?php foreach ($state['zonas'] as $z):
          $u = $z['total_slot']>0 ? round($z['occupied_slot']/$z['total_slot']*100) : 0;
          $color = $u>=90?'var(--danger)':($u>=70?'var(--warning)':'var(--success)');
          $badge = $u>=90?'badge-danger':($u>=70?'badge-warning':'badge-success');
        ?>
          <tr>
            <td><strong><?= htmlspecialchars($z['nama_zona']) ?></strong></td>
            <td><?= (int)$z['total_slot'] ?></td>
            <td><?= (int)$z['occupied_slot'] ?></td>
            <td><?= (int)$z['total_slot']-(int)$z['occupied_slot'] ?></td>
            <td>
              <div class="progress-wrap" style="width:100px;display:inline-block">
                <div class="progress-bar" style="width:<?= $u ?>%;background:<?= $color ?>"></div>
              </div>
              <span style="font-size:12px;color:<?= $color ?>;margin-left:4px"><?= $u ?>%</span>
            </td>
            <td><span class="badge <?= $badge ?>"><?= $u>=90?'PENUH':($u>=70?'HAMPIR PENUH':'NORMAL') ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- LAPORAN TRANSAKSI -->
<div class="card" id="laporan">
  <div class="card-title" style="justify-content:space-between">
    <span><i class="fas fa-history"></i> Laporan Transaksi Terbaru</span>
    <span style="font-size:12px;color:var(--text-secondary)">20 transaksi terakhir</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Plat</th><th>Kendaraan</th><th>Tipe</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th>Total Bayar</th></tr></thead>
      <tbody id="tx-tbody">
        <?php if (empty($state['transactions'])): ?>
          <tr><td colspan="7"><div class="empty-state"><i class="fas fa-receipt"></i> Belum ada transaksi selesai</div></td></tr>
        <?php else: ?>
          <?php foreach ($state['transactions'] as $tx): ?>
            <tr>
              <td><strong><?= htmlspecialchars($tx['plate']) ?></strong></td>
              <td><?= phpCap($tx['type']??'-') ?></td>
              <td><span class="badge badge-<?= phpBadgeClass($tx['type']??'regular') ?>"><?= phpTypeLabel($tx['type']??'regular') ?></span></td>
              <td><?= htmlspecialchars($tx['entryTime']??'-') ?></td>
              <td><?= htmlspecialchars($tx['exitTime']??'-') ?></td>
              <td><?= (int)($tx['duration']??0) ?> mnt</td>
              <td><strong>Rp <?= number_format((int)($tx['total']??0),0,',','.') ?></strong></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const CIRCUMFERENCE = 2 * Math.PI * 80;

(function initGauge() {
  const pct = <?= $pct ?>;
  setTimeout(() => updateGauge(pct), 120);
  document.getElementById('last-update').textContent = new Date().toLocaleTimeString('id-ID');
})();

function updateGauge(pct) {
  const circle = document.getElementById('gauge-circle');
  const offset = CIRCUMFERENCE - (pct/100) * CIRCUMFERENCE;
  circle.style.strokeDasharray  = CIRCUMFERENCE;
  circle.style.strokeDashoffset = offset;
  circle.className = 'gauge-fg' + (pct>=90?' crit':pct>=70?' warn':'');
  document.getElementById('gauge-pct').textContent = pct + '%';
  const lbl = document.getElementById('gauge-label');
  lbl.textContent = pct>=90?'PENUH':pct>=70?'HAMPIR PENUH':'NORMAL';
  lbl.className   = 'badge ' + (pct>=90?'badge-danger':pct>=70?'badge-warning':'badge-success');
}

async function refreshData() {
  const res = await api('state',{});
  if (!res.state) return;
  const s = res.state;
  document.getElementById('m-total').textContent    = s.totalSlots;
  document.getElementById('m-occupied').textContent = s.occupied;
  document.getElementById('m-available').textContent= s.available;
  document.getElementById('m-revenue').textContent  = formatRp(s.stats?.revenue||0);
  document.getElementById('m-entry').textContent    = s.stats?.entry||0;
  document.getElementById('m-exit').textContent     = s.stats?.exit||0;
  document.getElementById('gauge-sub').textContent  = `${s.occupied}/${s.totalSlots} slot`;
  const pct = s.totalSlots>0 ? Math.round(s.occupied/s.totalSlots*100) : 0;
  updateGauge(pct);
  renderZonasMonitor(s.zonas);
  renderTxTable(s.transactions);
  document.getElementById('last-update').textContent = new Date().toLocaleTimeString('id-ID');
  showToast('Data diperbarui.','success');
}

async function confirmReset() {
  if (!confirm('Reset transaksi hari ini?')) return;
  const res = await api('stats_reset',{});
  showToast(res.message, res.success?'success':'error');
  if (res.success) refreshData();
}

function renderZonasMonitor(zonas) {
  const tbody = document.getElementById('zona-monitor-tbody');
  const cards = document.getElementById('zona-cards');
  if (!zonas||!zonas.length) {
    tbody.innerHTML='<tr><td colspan="6"><div class="empty-state"><i class="fas fa-map-marker-alt"></i> Belum ada zona</div></td></tr>';
    cards.innerHTML='<div class="empty-state"><i class="fas fa-map-marker-alt"></i> Belum ada zona</div>';
    return;
  }
  tbody.innerHTML = zonas.map(z => {
    const u = z.total_slot>0?Math.round(z.occupied_slot/z.total_slot*100):0;
    const c = u>=90?'var(--danger)':u>=70?'var(--warning)':'var(--success)';
    const b = u>=90?'badge-danger':u>=70?'badge-warning':'badge-success';
    return `<tr><td><strong>${z.nama_zona}</strong></td><td>${z.total_slot}</td><td>${z.occupied_slot}</td><td>${z.total_slot-z.occupied_slot}</td>
      <td><div class="progress-wrap" style="width:100px;display:inline-block"><div class="progress-bar" style="width:${u}%;background:${c}"></div></div>
          <span style="font-size:12px;color:${c};margin-left:4px">${u}%</span></td>
      <td><span class="badge ${b}">${u>=90?'PENUH':u>=70?'HAMPIR PENUH':'NORMAL'}</span></td></tr>`;
  }).join('');
  cards.innerHTML = zonas.map(z => {
    const u = z.total_slot>0?Math.round(z.occupied_slot/z.total_slot*100):0;
    const c = u>=90?'var(--danger)':u>=70?'var(--warning)':'var(--success)';
    const b = u>=90?'badge-danger':u>=70?'badge-warning':'badge-success';
    return `<div class="tarif-card" style="cursor:default;border-left-color:${c}">
      <h4><i class="fas fa-map-marker-alt" style="margin-right:6px;color:${c}"></i>${z.nama_zona}</h4>
      <div style="font-size:22px;font-weight:700;color:${c}">${u}%</div>
      <div class="tarif-sub">${z.occupied_slot}/${z.total_slot} slot terisi</div>
      <div class="progress-wrap" style="margin-top:8px"><div class="progress-bar" style="width:${u}%;background:${c}"></div></div>
      <span class="badge ${b}" style="margin-top:8px">${u>=90?'PENUH':u>=70?'HAMPIR PENUH':'TERSEDIA'}</span>
    </div>`;
  }).join('');
}

function renderTxTable(txs) {
  const tbody = document.getElementById('tx-tbody');
  if (!txs||!txs.length) { tbody.innerHTML='<tr><td colspan="7"><div class="empty-state"><i class="fas fa-receipt"></i> Belum ada transaksi</div></td></tr>'; return; }
  tbody.innerHTML = txs.map(tx => `<tr>
    <td><strong>${tx.plate}</strong></td>
    <td>${jsCap(tx.type||'-')}</td>
    <td><span class="badge badge-${badgeClass(tx.type)}">${typeLabel(tx.type)}</span></td>
    <td>${tx.entryTime||'-'}</td><td>${tx.exitTime||'-'}</td><td>${tx.duration||0} mnt</td>
    <td><strong>${formatRp(tx.total||0)}</strong></td>
  </tr>`).join('');
}

setInterval(refreshData, 30000);
</script>

<?php require_once __DIR__ . '/layout_close.php'; ?>
