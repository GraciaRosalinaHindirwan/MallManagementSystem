    </div><!-- /content-body -->
  </div><!-- /main-content -->
</div><!-- /layout -->

<script>
/* ── Sidebar ── */
function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('show'); }
function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('show'); }

function scrollToSection(id, el) {
  const sec = document.getElementById(id);
  if (sec) { sec.scrollIntoView({ behavior:'smooth', block:'start' }); }
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (el) el.classList.add('active');
}

/* ── Toast ── */
function showToast(msg, type='info') {
  const t = document.getElementById('toast');
  const icons = {success:'fa-check-circle', error:'fa-times-circle', info:'fa-info-circle'};
  t.innerHTML = `<i class="fas ${icons[type]||icons.info}"></i> ${msg}`;
  t.className = `toast ${type==='error'?'error':type==='success'?'success':'info'}`;
  t.classList.remove('hidden');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.add('hidden'), 3500);
}

/* ── AJAX helper ── */
async function api(action, params={}) {
  const fd = new FormData();
  Object.entries(params).forEach(([k,v]) => { if (v !== null && v !== undefined && v !== '') fd.append(k, v); });
  try {
    const res  = await fetch(`index.php?action=${action}`, { method:'POST', body:fd });
    const data = await res.json();
    if (data.state) updateCapacity(data.state);
    return data;
  } catch(e) {
    showToast('Koneksi gagal. Coba lagi.','error');
    return { success:false, message:'Koneksi gagal.' };
  }
}

/* ── Kapasitas topbar ── */
function updateCapacity(state) {
  const el = document.getElementById('cap-text');
  if (el && state) el.textContent = `${state.occupied}/${state.totalSlots}`;
}

/* ── JS helpers (front-end only) ── */
function formatRp(v)   { return 'Rp ' + Number(v).toLocaleString('id-ID'); }
function jsCap(v)      { return v ? v.charAt(0).toUpperCase() + v.slice(1) : '-'; }
function typeLabel(t)  {
  t = (t||'').toLowerCase();
  if (t==='member'||t==='vip'||t==='reguler') return 'Member';
  if (t==='corporate'||t==='korporat') return 'Korporat';
  return 'Pengunjung';
}
function badgeClass(t) {
  t = (t||'').toLowerCase();
  if (t==='vip') return 'vip';
  if (t==='reguler'||t==='member') return 'member';
  if (t==='korporat'||t==='corporate') return 'corporate';
  return 'regular';
}
function tsToTime(ts)  { return new Date(ts*1000).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'}); }
</script>
</body>
</html>
