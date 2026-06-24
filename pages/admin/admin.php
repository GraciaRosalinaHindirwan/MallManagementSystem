<?php
/**
 * views/admin.php — PBI-M04-02-03
 * Admin: Kelola Member, Zona, dan Tarif Parkir.
 */
$role       = 'admin';
$pageTitle  = 'Kelola Member & Zona';
$currentNav = 'member';
require_once __DIR__ . '/layout.php';

// Tenant untuk dropdown korporat
$tenants = [];
if ($useDb && $pdo) {
    try {
        $tenants = $pdo->query("SELECT id_tenant, tenant_name FROM 02_tenants WHERE status='Active' ORDER BY tenant_name")->fetchAll();
    } catch (Throwable $e) {}
}

// Tarif aktif untuk tab Atur Tarif
$tarifAktif = [];
if ($useDb && $pdo) {
    try {
        $today = date('Y-m-d');
        $stmt  = $pdo->prepare(
            "SELECT id_tarif, tipe_kendaraan, tipe_user, tarif_jam_pertama, tarif_per_jam,
                    tarif_harian_max, berlaku_dari, berlaku_sampai
               FROM 04_parking_tarif
              ORDER BY tipe_kendaraan, tipe_user, berlaku_dari DESC"
        );
        $stmt->execute();
        $tarifAktif = $stmt->fetchAll();
    } catch (Throwable $e) {}
}
?>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card stat-blue">
    <div class="stat-icon"><i class="fas fa-users"></i></div>
    <div class="stat-info"><h3><?= count($state['members']) ?></h3><p>Total Member</p></div>
  </div>
  <?php
    $cntVip = count(array_filter($state['members'], fn($m)=>($m['type']??$m['membership_type']??'')==='VIP'));
    $cntKorp= count(array_filter($state['members'], fn($m)=>($m['type']??$m['membership_type']??'')==='Korporat'));
  ?>
  <div class="stat-card stat-yellow">
    <div class="stat-icon" style="background:rgba(255,182,42,0.15);color:var(--warning)"><i class="fas fa-star"></i></div>
    <div class="stat-info"><h3><?= $cntVip ?></h3><p>Member VIP</p></div>
  </div>
  <div class="stat-card stat-blue">
    <div class="stat-icon" style="background:rgba(0,212,216,0.15);color:var(--accent)"><i class="fas fa-building"></i></div>
    <div class="stat-info"><h3><?= $cntKorp ?></h3><p>Member Korporat</p></div>
  </div>
  <div class="stat-card stat-green">
    <div class="stat-icon" style="background:rgba(34,197,94,0.15);color:var(--success)"><i class="fas fa-map-marker-alt"></i></div>
    <div class="stat-info"><h3><?= count($state['zonas']) ?></h3><p>Total Zona</p></div>
  </div>
</div>

<!-- ═══ BAGIAN MEMBER ═══ -->
<div class="card" id="member">
  <div class="card-title"><i class="fas fa-user-plus"></i> Tambah Member Baru</div>
  <div class="alert alert-info"><i class="fas fa-info-circle"></i> Member terdaftar otomatis mendapat diskon saat parkir sesuai tipe keanggotaan.</div>

  <div class="grid-2">
    <div class="form-group">
      <label>Plat Nomor</label>
      <input type="text" id="m-plate" placeholder="B 1234 XYZ" style="text-transform:uppercase">
    </div>
    <div class="form-group">
      <label>Tipe Kendaraan</label>
      <select id="m-kendaraan">
        <option value="mobil">Mobil</option>
        <option value="motor">Motor</option>
        <option value="truk">Truk</option>
      </select>
    </div>
    <div class="form-group">
      <label>Tipe Keanggotaan</label>
      <select id="m-type" onchange="toggleTenantField()">
        <option value="Reguler">Reguler (0% diskon)</option>
        <option value="VIP">VIP (25% diskon)</option>
        <option value="Korporat">Korporat (30% diskon)</option>
      </select>
    </div>
    <div class="form-group hidden" id="tenant-wrap">
      <label>Tenant / Perusahaan</label>
      <select id="m-tenant">
        <option value="">-- Pilih Tenant --</option>
        <?php foreach ($tenants as $t): ?>
          <option value="<?= (int)$t['id_tenant'] ?>"><?= htmlspecialchars($t['tenant_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <button class="btn btn-primary" onclick="tambahMember()"><i class="fas fa-plus"></i> Daftarkan Member</button>
</div>

<!-- DAFTAR MEMBER -->
<div class="card">
  <div class="card-title" style="justify-content:space-between">
    <span><i class="fas fa-id-card"></i> Daftar Member Parkir</span>
    <div class="search-box" style="margin:0;min-width:220px">
      <i class="fas fa-search"></i>
      <input type="text" id="search-member" placeholder="Cari plat / tipe..." oninput="filterMembers()">
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Plat Nomor</th><th>Kendaraan</th><th>Keanggotaan</th><th>Tenant</th><th>Diskon</th><th>Aksi</th></tr></thead>
      <tbody id="member-tbody">
        <?php if (empty($state['members'])): ?>
          <tr><td colspan="6"><div class="empty-state"><i class="fas fa-users"></i> Belum ada member</div></td></tr>
        <?php else: ?>
          <?php foreach ($state['members'] as $m):
            $mType  = $m['type'] ?? $m['membership_type'] ?? '-';
            $plate  = $m['plate'] ?? $m['plat_nomor'] ?? '-';
            $kend   = $m['tipeKendaraan'] ?? $m['tipe_kendaraan'] ?? '-';
            $disc   = (int)($m['discountPercent'] ?? 0);
            $tname  = $m['name'] ?? '-';
          ?>
            <tr>
              <td><strong><?= htmlspecialchars($plate) ?></strong></td>
              <td><?= phpCap($kend) ?></td>
              <td><span class="badge <?= phpMemberBadge($mType) ?>"><?= htmlspecialchars($mType) ?></span></td>
              <td><?= htmlspecialchars($tname) ?></td>
              <td><?= $disc > 0 ? "<span class='badge badge-success'>{$disc}%</span>" : '<span style="color:var(--text-secondary)">-</span>' ?></td>
              <td>
                <button class="btn btn-danger btn-sm" onclick="hapusMember('<?= htmlspecialchars($plate) ?>')">
                  <i class="fas fa-trash"></i> Hapus
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ═══ BAGIAN ZONA ═══ -->
<div class="card" id="zona">
  <div class="card-title"><i class="fas fa-plus-circle"></i> Tambah Zona Parkir</div>
  <div class="grid-2">
    <div class="form-group">
      <label>Nama Zona</label>
      <input type="text" id="z-nama" placeholder="Contoh: Basement 3">
    </div>
    <div class="form-group">
      <label>Total Slot</label>
      <input type="number" id="z-slots" placeholder="100" min="1">
    </div>
  </div>
  <button class="btn btn-primary" onclick="tambahZona()"><i class="fas fa-plus"></i> Tambah Zona</button>
</div>

<div class="card">
  <div class="card-title"><i class="fas fa-th-large"></i> Daftar Zona Parkir</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>Nama Zona</th><th>Total Slot</th><th>Terisi</th><th>Tersedia</th><th>Utilisasi</th><th>Aksi</th></tr></thead>
      <tbody id="zona-tbody">
        <?php if (empty($state['zonas'])): ?>
          <tr><td colspan="7"><div class="empty-state"><i class="fas fa-map-marker-alt"></i> Belum ada zona</div></td></tr>
        <?php else: ?>
          <?php foreach ($state['zonas'] as $z):
            $util = $z['total_slot']>0 ? round($z['occupied_slot']/$z['total_slot']*100) : 0;
            $color= $util>=90?'var(--danger)':($util>=70?'var(--warning)':'var(--success)');
          ?>
            <tr>
              <td><?= (int)$z['id_zona'] ?></td>
              <td><?= htmlspecialchars($z['nama_zona']) ?></td>
              <td><?= (int)$z['total_slot'] ?></td>
              <td><?= (int)$z['occupied_slot'] ?></td>
              <td><?= (int)$z['total_slot']-(int)$z['occupied_slot'] ?></td>
              <td>
                <div class="progress-wrap" style="width:100px;display:inline-block">
                  <div class="progress-bar" style="width:<?= $util ?>%;background:<?= $color ?>"></div>
                </div>
                <span style="font-size:12px;color:<?= $color ?>;margin-left:6px"><?= $util ?>%</span>
              </td>
              <td>
                <button class="btn btn-danger btn-sm" onclick="hapusZona(<?= (int)$z['id_zona'] ?>, '<?= htmlspecialchars($z['nama_zona'], ENT_QUOTES) ?>')">
                  <i class="fas fa-trash"></i> Hapus
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ═══ BAGIAN TARIF ═══ -->
<div class="card" id="tarif-admin">
  <div class="card-title"><i class="fas fa-tags"></i> Atur Tarif Parkir</div>

  <!-- Form tambah/edit tarif -->
  <div class="alert alert-info" style="margin-bottom:16px">
    <i class="fas fa-info-circle"></i> Tambah entri tarif baru. Tarif dengan tanggal lebih baru akan digunakan secara otomatis.
  </div>
  <div class="grid-2" style="gap:12px">
    <div class="form-group">
      <label>Tipe Kendaraan</label>
      <select id="t-kendaraan">
        <option value="mobil">Mobil</option>
        <option value="motor">Motor</option>
        <option value="truk">Truk</option>
      </select>
    </div>
    <div class="form-group">
      <label>Tipe Pengguna</label>
      <select id="t-user">
        <option value="umum">Umum</option>
        <option value="member">Member</option>
        <option value="korporat">Korporat</option>
      </select>
    </div>
    <div class="form-group">
      <label>Tarif Jam Pertama (Rp)</label>
      <input type="number" id="t-j1" placeholder="5000" min="0">
    </div>
    <div class="form-group">
      <label>Tarif Per Jam Berikutnya (Rp)</label>
      <input type="number" id="t-pjm" placeholder="3000" min="0">
    </div>
    <div class="form-group">
      <label>Tarif Harian Maks (Rp, opsional)</label>
      <input type="number" id="t-maks" placeholder="50000" min="0">
    </div>
    <div class="form-group">
      <label>Berlaku Dari</label>
      <input type="date" id="t-dari" value="<?= date('Y-m-d') ?>">
    </div>
    <div class="form-group">
      <label>Berlaku Sampai <span style="color:var(--text-secondary)">(kosong = selamanya)</span></label>
      <input type="date" id="t-sampai">
    </div>
  </div>
  <button class="btn btn-primary" onclick="tambahTarif()"><i class="fas fa-plus"></i> Tambah Tarif</button>

  <hr class="sep" style="margin:20px 0">

  <!-- Tabel tarif -->
  <div class="card-title" style="margin-bottom:14px"><i class="fas fa-list"></i> Daftar Semua Tarif</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>ID</th><th>Kendaraan</th><th>Pengguna</th><th>Jam Pertama</th><th>Per Jam</th><th>Maks/Hari</th><th>Berlaku Dari</th><th>Berlaku Sampai</th><th>Aksi</th></tr>
      </thead>
      <tbody id="tarif-tbody">
        <?php if (empty($tarifAktif)): ?>
          <tr><td colspan="9"><div class="empty-state"><i class="fas fa-tags"></i> Belum ada tarif</div></td></tr>
        <?php else: ?>
          <?php foreach ($tarifAktif as $t):
            $today = date('Y-m-d');
            $aktif = $t['berlaku_dari'] <= $today && ($t['berlaku_sampai'] === null || $t['berlaku_sampai'] >= $today);
          ?>
            <tr style="<?= $aktif ? '' : 'opacity:0.5' ?>">
              <td><?= (int)$t['id_tarif'] ?></td>
              <td><?= phpCap($t['tipe_kendaraan']) ?></td>
              <td><span class="badge <?= $t['tipe_user']==='korporat'?'badge-corporate':($t['tipe_user']==='member'?'badge-member':'badge-regular') ?>"><?= phpCap($t['tipe_user']) ?></span></td>
              <td>Rp <?= number_format((float)$t['tarif_jam_pertama'],0,',','.') ?></td>
              <td>Rp <?= number_format((float)$t['tarif_per_jam'],0,',','.') ?></td>
              <td><?= $t['tarif_harian_max'] ? 'Rp '.number_format((float)$t['tarif_harian_max'],0,',','.') : '-' ?></td>
              <td><?= htmlspecialchars($t['berlaku_dari']) ?></td>
              <td><?= $t['berlaku_sampai'] ? htmlspecialchars($t['berlaku_sampai']) : '<span style="color:var(--success)">Selamanya</span>' ?></td>
              <td>
                <?php if ($aktif): ?>
                  <span class="badge badge-success">Aktif</span>
                <?php else: ?>
                  <button class="btn btn-danger btn-sm" onclick="hapusTarif(<?= (int)$t['id_tarif'] ?>)">
                    <i class="fas fa-trash"></i>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function toggleTenantField() {
  document.getElementById('tenant-wrap').classList.toggle('hidden', document.getElementById('m-type').value !== 'Korporat');
}

function filterMembers() {
  const q = document.getElementById('search-member').value.toLowerCase();
  document.querySelectorAll('#member-tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

async function tambahMember() {
  const plate    = document.getElementById('m-plate').value.trim().toUpperCase();
  const kendaraan= document.getElementById('m-kendaraan').value;
  const type     = document.getElementById('m-type').value;
  const tenant   = document.getElementById('m-tenant')?.value || '';
  if (!plate) { showToast('Plat nomor wajib diisi!','error'); return; }
  const res = await api('member_add',{ plat_nomor:plate, tipe_kendaraan:kendaraan, membership_type:type, tenant_id:tenant });
  showToast(res.message, res.success?'success':'error');
  if (res.success) {
    document.getElementById('m-plate').value = '';
    if (res.state) renderMemberTable(res.state.members);
  }
}

async function hapusMember(plate) {
  if (!confirm(`Hapus member ${plate}?`)) return;
  const res = await api('member_delete',{ plat_nomor:plate });
  showToast(res.message, res.success?'success':'error');
  if (res.success && res.state) renderMemberTable(res.state.members);
}

async function tambahZona() {
  const nama  = document.getElementById('z-nama').value.trim();
  const slots = document.getElementById('z-slots').value;
  if (!nama || !slots) { showToast('Nama zona & total slot wajib diisi!','error'); return; }
  const res = await api('zona_add',{ name:nama, slots });
  showToast(res.message, res.success?'success':'error');
  if (res.success) {
    document.getElementById('z-nama').value  = '';
    document.getElementById('z-slots').value = '';
    if (res.state) renderZonaTable(res.state.zonas);
  }
}

async function hapusZona(id, nama) {
  if (!confirm(`Hapus zona "${nama}"?`)) return;
  const res = await api('zona_delete',{ zona_id:id });
  showToast(res.message, res.success?'success':'error');
  if (res.success && res.state) renderZonaTable(res.state.zonas);
}

async function tambahTarif() {
  const data = {
    tipe_kendaraan: document.getElementById('t-kendaraan').value,
    tipe_user:      document.getElementById('t-user').value,
    tarif_jam_pertama: document.getElementById('t-j1').value,
    tarif_per_jam:  document.getElementById('t-pjm').value,
    tarif_harian_max: document.getElementById('t-maks').value || '',
    berlaku_dari:   document.getElementById('t-dari').value,
    berlaku_sampai: document.getElementById('t-sampai').value || '',
  };
  if (!data.tarif_jam_pertama || !data.tarif_per_jam || !data.berlaku_dari) {
    showToast('Tarif jam pertama, per jam, dan tanggal berlaku wajib diisi!','error'); return;
  }
  const res = await api('tarif_add', data);
  showToast(res.message, res.success?'success':'error');
  if (res.success) location.reload(); // reload to refresh tarif table
}

async function hapusTarif(id) {
  if (!confirm('Hapus entri tarif ini?')) return;
  const res = await api('tarif_delete',{ id_tarif:id });
  showToast(res.message, res.success?'success':'error');
  if (res.success) location.reload();
}

function renderMemberTable(members) {
  const tbody = document.getElementById('member-tbody');
  if (!members||!members.length) { tbody.innerHTML='<tr><td colspan="6"><div class="empty-state"><i class="fas fa-users"></i> Belum ada member</div></td></tr>'; return; }
  const badgeMap = {VIP:'badge-vip',Korporat:'badge-corporate',Reguler:'badge-member'};
  tbody.innerHTML = members.map(m => {
    const mType = m.type||m.membership_type||'-';
    const plate = m.plate||m.plat_nomor||'-';
    const kend  = m.tipeKendaraan||m.tipe_kendaraan||'-';
    const disc  = m.discountPercent||0;
    return `<tr>
      <td><strong>${plate}</strong></td>
      <td>${jsCap(kend)}</td>
      <td><span class="badge ${badgeMap[mType]||'badge-regular'}">${mType}</span></td>
      <td>${m.name||'-'}</td>
      <td>${disc>0?`<span class='badge badge-success'>${disc}%</span>`:'<span style="color:var(--text-secondary)">-</span>'}</td>
      <td><button class="btn btn-danger btn-sm" onclick="hapusMember('${plate}')"><i class="fas fa-trash"></i> Hapus</button></td>
    </tr>`;
  }).join('');
}

function renderZonaTable(zonas) {
  const tbody = document.getElementById('zona-tbody');
  if (!zonas||!zonas.length) { tbody.innerHTML='<tr><td colspan="7"><div class="empty-state"><i class="fas fa-map-marker-alt"></i> Belum ada zona</div></td></tr>'; return; }
  tbody.innerHTML = zonas.map(z => {
    const util  = z.total_slot>0 ? Math.round(z.occupied_slot/z.total_slot*100) : 0;
    const color = util>=90?'var(--danger)':util>=70?'var(--warning)':'var(--success)';
    return `<tr>
      <td>${z.id_zona}</td><td>${z.nama_zona}</td><td>${z.total_slot}</td><td>${z.occupied_slot}</td>
      <td>${z.total_slot-z.occupied_slot}</td>
      <td><div class="progress-wrap" style="width:100px;display:inline-block"><div class="progress-bar" style="width:${util}%;background:${color}"></div></div>
          <span style="font-size:12px;color:${color};margin-left:6px">${util}%</span></td>
      <td><button class="btn btn-danger btn-sm" onclick="hapusZona(${z.id_zona},'${z.nama_zona}')"><i class="fas fa-trash"></i> Hapus</button></td>
    </tr>`;
  }).join('');
}
</script>

<?php require_once __DIR__ . '/layout_close.php'; ?>
