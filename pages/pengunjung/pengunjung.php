<?php
/**
 * views/pengunjung.php — PBI-M04-02-02
 * Pengunjung: Info tarif otomatis, cek kendaraan, struk parkir.
 */
$role       = 'pengunjung';
$pageTitle  = 'Informasi Parkir';
$currentNav = 'tarif';
require_once __DIR__ . '/layout.php';

// Ambil tarif AKTIF dari DB
$tarifList = [];
if ($useDb && $pdo) {
    try {
        $today = date('Y-m-d');
        $stmt  = $pdo->prepare(
            "SELECT tipe_kendaraan, tipe_user, tarif_jam_pertama, tarif_per_jam, tarif_harian_max
               FROM 04_parking_tarif
              WHERE berlaku_dari <= ?
                AND (berlaku_sampai IS NULL OR berlaku_sampai >= ?)
              ORDER BY tipe_kendaraan, tipe_user, berlaku_dari DESC"
        );
        $stmt->execute([$today, $today]);
        $raw = $stmt->fetchAll();
        // Ambil yang paling baru per (tipe_kendaraan, tipe_user)
        $seen = [];
        foreach ($raw as $row) {
            $key = $row['tipe_kendaraan'].'_'.$row['tipe_user'];
            if (!isset($seen[$key])) { $tarifList[] = $row; $seen[$key] = true; }
        }
    } catch (Throwable $e) {}
}
if (empty($tarifList)) {
    $tarifList = [
        ['tipe_kendaraan'=>'mobil','tipe_user'=>'umum',    'tarif_jam_pertama'=>5000, 'tarif_per_jam'=>3000,'tarif_harian_max'=>50000],
        ['tipe_kendaraan'=>'mobil','tipe_user'=>'member',  'tarif_jam_pertama'=>4000, 'tarif_per_jam'=>2400,'tarif_harian_max'=>40000],
        ['tipe_kendaraan'=>'mobil','tipe_user'=>'korporat','tarif_jam_pertama'=>3500, 'tarif_per_jam'=>2100,'tarif_harian_max'=>35000],
        ['tipe_kendaraan'=>'motor','tipe_user'=>'umum',    'tarif_jam_pertama'=>2000, 'tarif_per_jam'=>1000,'tarif_harian_max'=>15000],
        ['tipe_kendaraan'=>'motor','tipe_user'=>'member',  'tarif_jam_pertama'=>1600, 'tarif_per_jam'=>800, 'tarif_harian_max'=>12000],
        ['tipe_kendaraan'=>'motor','tipe_user'=>'korporat','tarif_jam_pertama'=>1400, 'tarif_per_jam'=>700, 'tarif_harian_max'=>10500],
        ['tipe_kendaraan'=>'truk', 'tipe_user'=>'umum',    'tarif_jam_pertama'=>10000,'tarif_per_jam'=>5000,'tarif_harian_max'=>100000],
        ['tipe_kendaraan'=>'truk', 'tipe_user'=>'member',  'tarif_jam_pertama'=>8000, 'tarif_per_jam'=>4000,'tarif_harian_max'=>80000],
        ['tipe_kendaraan'=>'truk', 'tipe_user'=>'korporat','tarif_jam_pertama'=>7000, 'tarif_per_jam'=>3500,'tarif_harian_max'=>70000],
    ];
}
?>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card stat-blue">
    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
    <div class="stat-info"><h3><?= (int)$state['available'] ?></h3><p>Slot Tersedia</p></div>
  </div>
  <div class="stat-card stat-red">
    <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:var(--danger)"><i class="fas fa-car"></i></div>
    <div class="stat-info"><h3><?= (int)$state['occupied'] ?></h3><p>Slot Terisi</p></div>
  </div>
  <div class="stat-card stat-yellow">
    <?php $pct = $state['totalSlots']>0 ? round($state['occupied']/$state['totalSlots']*100) : 0; ?>
    <div class="stat-icon" style="background:rgba(255,182,42,0.15);color:var(--warning)"><i class="fas fa-percentage"></i></div>
    <div class="stat-info"><h3><?= $pct ?>%</h3><p>Tingkat Hunian</p></div>
  </div>
</div>

<!-- TARIF -->
<div class="card" id="tarif">
  <div class="card-title"><i class="fas fa-tags"></i> Tarif Parkir</div>

  <div class="form-group" style="max-width:300px;margin-bottom:20px">
    <label>Filter Kendaraan</label>
    <select onchange="filterTarif(this.value)">
      <option value="">Semua Kendaraan</option>
      <option value="mobil">Mobil</option>
      <option value="motor">Motor</option>
      <option value="truk">Truk</option>
    </select>
  </div>

  <div class="table-wrap">
    <table id="tarif-table">
      <thead>
        <tr><th>Kendaraan</th><th>Tipe Pengguna</th><th>Jam Pertama</th><th>Per Jam Berikutnya</th><th>Maks/Hari</th></tr>
      </thead>
      <tbody>
        <?php foreach ($tarifList as $t):
          $userLabels = ['umum'=>'Umum','member'=>'Member','korporat'=>'Korporat'];
          $icons = ['mobil'=>'fa-car','motor'=>'fa-motorcycle','truk'=>'fa-truck'];
          $uLabel = $userLabels[$t['tipe_user']] ?? phpCap($t['tipe_user']);
          $icon   = $icons[$t['tipe_kendaraan']] ?? 'fa-car';
        ?>
          <tr data-kendaraan="<?= $t['tipe_kendaraan'] ?>">
            <td><i class="fas <?= $icon ?>" style="margin-right:6px;color:var(--accent)"></i><?= phpCap($t['tipe_kendaraan']) ?></td>
            <td>
              <span class="badge <?= $t['tipe_user']==='korporat'?'badge-corporate':($t['tipe_user']==='member'?'badge-member':'badge-regular') ?>">
                <?= $uLabel ?>
              </span>
            </td>
            <td><strong style="color:var(--accent)">Rp <?= number_format((float)$t['tarif_jam_pertama'],0,',','.') ?></strong></td>
            <td>Rp <?= number_format((float)$t['tarif_per_jam'],0,',','.') ?></td>
            <td><?= $t['tarif_harian_max'] ? 'Rp '.number_format((float)$t['tarif_harian_max'],0,',','.') : '<span style="color:var(--text-secondary)">-</span>' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- KALKULATOR ESTIMASI -->
<div class="card">
  <div class="card-title"><i class="fas fa-calculator"></i> Estimasi Biaya Parkir</div>
  <div class="grid-2">
    <div>
      <div class="form-group">
        <label>Tipe Kendaraan</label>
        <select id="calc-kendaraan" onchange="hitungEstimasi()">
          <option value="mobil">Mobil</option>
          <option value="motor">Motor</option>
          <option value="truk">Truk</option>
        </select>
      </div>
      <div class="form-group">
        <label>Tipe Pengguna</label>
        <select id="calc-user" onchange="hitungEstimasi()">
          <option value="umum">Pengunjung Umum</option>
          <option value="member">Member</option>
          <option value="korporat">Korporat</option>
        </select>
      </div>
      <div class="form-group">
        <label>Estimasi Durasi (jam)</label>
        <input type="number" id="calc-jam" value="2" min="1" max="24" oninput="hitungEstimasi()">
      </div>
      <button class="btn btn-primary" onclick="hitungEstimasi()"><i class="fas fa-calculator"></i> Hitung</button>
    </div>
    <div id="estimasi-result" class="receipt-box hidden">
      <h4><i class="fas fa-coins"></i> Estimasi Biaya</h4>
      <div id="estimasi-content"></div>
    </div>
  </div>
</div>

<!-- CEK KENDARAAN -->
<div class="card" id="cek">
  <div class="card-title"><i class="fas fa-search"></i> Cek Status Kendaraan</div>
  <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <div class="form-group" style="flex:1;min-width:200px;margin:0">
      <label>Plat Nomor Kendaraan</label>
      <input type="text" id="cek-plate" placeholder="B 1234 XYZ" style="text-transform:uppercase"
             onkeypress="if(event.key==='Enter') cekKendaraan()">
    </div>
    <button class="btn btn-accent" style="margin-bottom:0" onclick="cekKendaraan()">
      <i class="fas fa-search"></i> Cek
    </button>
  </div>
  <div id="cek-result" style="margin-top:16px"></div>
</div>

<!-- STRUK PARKIR -->
<div class="card" id="struk">
  <div class="card-title"><i class="fas fa-receipt"></i> Struk Parkir</div>
  <div class="alert alert-info"><i class="fas fa-info-circle"></i> Masukkan plat nomor untuk melihat rincian biaya sebelum keluar.</div>
  <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    <div class="form-group" style="flex:1;min-width:200px;margin:0">
      <label>Plat Nomor</label>
      <input type="text" id="struk-plate" placeholder="B 1234 XYZ" style="text-transform:uppercase"
             onkeypress="if(event.key==='Enter') lihatStruk()">
    </div>
    <button class="btn btn-primary" style="margin-bottom:0" onclick="lihatStruk()">
      <i class="fas fa-receipt"></i> Lihat Struk
    </button>
  </div>
  <div id="struk-result" style="margin-top:16px"></div>
</div>

<script>
/* Data tarif dari PHP (untuk kalkulator JS) */
const TARIF_DATA = <?= json_encode(array_map(function($t){ return ['tipe_kendaraan'=>$t['tipe_kendaraan'],'tipe_user'=>$t['tipe_user'],'tarif_jam_pertama'=>(float)$t['tarif_jam_pertama'],'tarif_per_jam'=>(float)$t['tarif_per_jam'],'tarif_harian_max'=>(float)($t['tarif_harian_max']??0)]; }, $tarifList), JSON_UNESCAPED_UNICODE) ?>;

function filterTarif(val) {
  document.querySelectorAll('#tarif-table tbody tr').forEach(tr => {
    tr.style.display = (!val || tr.dataset.kendaraan === val) ? '' : 'none';
  });
}

function hitungEstimasi() {
  const kendaraan = document.getElementById('calc-kendaraan').value;
  const user      = document.getElementById('calc-user').value;
  const jam       = parseInt(document.getElementById('calc-jam').value) || 1;
  const result    = document.getElementById('estimasi-result');
  const content   = document.getElementById('estimasi-content');
  result.classList.remove('hidden');
  const tarif = TARIF_DATA.find(t => t.tipe_kendaraan === kendaraan && t.tipe_user === user);
  if (!tarif) { content.innerHTML='<div class="receipt-line"><span>Tarif tidak tersedia</span></div>'; return; }
  const j1   = tarif.tarif_jam_pertama;
  const pjm  = tarif.tarif_per_jam;
  const maks = tarif.tarif_harian_max || Infinity;
  let total  = j1 + Math.max(0, jam-1) * pjm;
  const capped = total >= maks && maks < Infinity;
  if (capped) total = maks;
  const uLabels = {umum:'Umum',member:'Member',korporat:'Korporat'};
  content.innerHTML = `
    <div class="receipt-line"><span>Kendaraan</span><strong>${jsCap(kendaraan)} / ${uLabels[user]||user}</strong></div>
    <div class="receipt-line"><span>Durasi Estimasi</span><strong>${jam} jam</strong></div>
    <div class="receipt-line"><span>Jam Pertama</span><strong>${formatRp(j1)}</strong></div>
    ${jam>1?`<div class="receipt-line"><span>Jam ke-2 s/d ke-${jam}</span><strong>${formatRp(Math.max(0,jam-1)*pjm)}</strong></div>`:''}
    ${capped?`<div class="receipt-line"><span style="color:var(--warning)">Cap Harian Berlaku</span><strong style="color:var(--warning)">${formatRp(maks)}</strong></div>`:''}
    <div class="receipt-line total"><span>Estimasi Total</span><strong>${formatRp(total)}</strong></div>`;
}

async function cekKendaraan() {
  const plate = document.getElementById('cek-plate').value.trim().toUpperCase();
  if (!plate) { showToast('Masukkan plat nomor!','error'); return; }
  const res = await api('state',{});
  const el  = document.getElementById('cek-result');
  const vehicles = res.state?.vehicles || {};
  const v = vehicles[plate];
  if (v) {
    const now = Math.floor(Date.now()/1000);
    const dur = Math.max(1, Math.ceil((now - v.time)/60));
    el.innerHTML = `<div class="receipt-box">
      <h4><i class="fas fa-car"></i> Kendaraan Ditemukan</h4>
      <div class="receipt-line"><span>Plat Nomor</span><strong>${plate}</strong></div>
      <div class="receipt-line"><span>Tipe</span><strong><span class="badge badge-${badgeClass(v.type)}">${typeLabel(v.type)}</span></strong></div>
      <div class="receipt-line"><span>Kendaraan</span><strong>${jsCap(v.tipeKendaraan||'mobil')}</strong></div>
      <div class="receipt-line"><span>Zona / Slot</span><strong>${v.zona_id||'-'} / ${v.parking_slot||'-'}</strong></div>
      <div class="receipt-line"><span>Waktu Masuk</span><strong>${tsToTime(v.time)}</strong></div>
      <div class="receipt-line total"><span>Durasi Parkir</span><strong>${dur} menit</strong></div>
    </div>`;
  } else {
    el.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Kendaraan <strong>${plate}</strong> tidak ditemukan di parkir saat ini.</div>`;
  }
}

async function lihatStruk() {
  const plate = document.getElementById('struk-plate').value.trim().toUpperCase();
  if (!plate) { showToast('Masukkan plat nomor!','error'); return; }
  const res = await api('state',{});
  const el  = document.getElementById('struk-result');
  const vehicles = res.state?.vehicles || {};
  const v = vehicles[plate];
  if (!v) {
    el.innerHTML = `<div class="alert alert-danger"><i class="fas fa-times-circle"></i> Kendaraan <strong>${plate}</strong> tidak ditemukan di parkir.</div>`;
    return;
  }
  const now    = Math.floor(Date.now()/1000);
  const dur    = Math.max(1, Math.ceil((now - v.time)/60));
  const jam    = Math.max(1, Math.ceil(dur/60));
  const kendaraan = v.tipeKendaraan || 'mobil';
  const tipeUser  = (v.type==='corporate'||v.type==='korporat') ? 'korporat' : (v.type==='member'||v.type==='reguler'||v.type==='vip' ? 'member' : 'umum');
  const tarif  = TARIF_DATA.find(t => t.tipe_kendaraan===kendaraan && t.tipe_user===tipeUser) || TARIF_DATA.find(t=>t.tipe_kendaraan===kendaraan&&t.tipe_user==='umum') || {tarif_jam_pertama:5000,tarif_per_jam:3000,tarif_harian_max:50000};
  let total    = tarif.tarif_jam_pertama + Math.max(0,jam-1)*tarif.tarif_per_jam;
  if (tarif.tarif_harian_max) total = Math.min(total, tarif.tarif_harian_max);
  el.innerHTML = `<div class="receipt-box">
    <h4><i class="fas fa-receipt"></i> Struk Parkir (Estimasi)</h4>
    <div class="receipt-line"><span>Plat Nomor</span><strong>${plate}</strong></div>
    <div class="receipt-line"><span>Tipe Pengguna</span><strong><span class="badge badge-${badgeClass(v.type)}">${typeLabel(v.type)}</span></strong></div>
    <div class="receipt-line"><span>Kendaraan</span><strong>${jsCap(kendaraan)}</strong></div>
    <div class="receipt-line"><span>Waktu Masuk</span><strong>${tsToTime(v.time)}</strong></div>
    <div class="receipt-line"><span>Waktu Sekarang</span><strong>${new Date().toLocaleTimeString('id-ID')}</strong></div>
    <div class="receipt-line"><span>Durasi</span><strong>${jam} jam ${dur%60} mnt</strong></div>
    <div class="receipt-line"><span>Tarif Jam Pertama</span><strong>${formatRp(tarif.tarif_jam_pertama)}</strong></div>
    ${jam>1?`<div class="receipt-line"><span>Jam Berikutnya (${jam-1} jam)</span><strong>${formatRp((jam-1)*tarif.tarif_per_jam)}</strong></div>`:''}
    <div class="receipt-line total"><span>Estimasi Biaya</span><strong>${formatRp(total)}</strong></div>
    <p style="font-size:11px;color:var(--text-secondary);margin-top:8px"><i class="fas fa-info-circle"></i> Biaya final dihitung saat kendaraan keluar oleh petugas.</p>
  </div>`;
}

/* Init kalkulator */
hitungEstimasi();
</script>

<?php require_once __DIR__ . '/layout_close.php'; ?>
