<?php
session_start();
require_once '../../config/konek.php';

// Ensure $conn variable exists to avoid undefined variable errors when
// the included config may not set it.
if (!isset($conn)) $conn = null;

/* ── Helpers ─────────────────────────────────────────────── */
function sanitize(string $val): string
{
  return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

function renderStars(float $avg, int $total = 5): string
{
  $html = '';
  for ($i = 1; $i <= $total; $i++) {
    if ($avg >= $i)      $html .= '<i class="bi bi-star-fill"></i>';
    elseif ($avg >= $i - 0.5) $html .= '<i class="bi bi-star-half"></i>';
    else                 $html .= '<i class="bi bi-star"></i>';
  }
  return $html;
}

/* ── Handle POST: simpan feedback baru ───────────────────── */
$alertMsg  = '';
$alertType = '';
$isSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
  $isSubmitted = true;
  $nama      = sanitize($_POST['nama']      ?? '');
  $rating    = (int) ($_POST['rating']      ?? 0);
  $komentar  = sanitize($_POST['komentar']  ?? '');
  $kategori  = sanitize($_POST['kategori']  ?? '');

  $errors = [];
  if ($nama    === '') $errors[] = 'Nama tidak boleh kosong.';
  if ($rating  < 1 || $rating > 5) $errors[] = 'Rating harus antara 1–5 bintang.';
  if ($komentar === '') $errors[] = 'Komentar tidak boleh kosong.';
  if ($kategori === '') $errors[] = 'Pilih kategori layanan.';

  if (empty($errors)) {
    if (!isset($conn) || $conn === null) {
      $alertMsg  = 'Gagal terhubung ke database.';
      $alertType = 'danger';
    } else {
      $stmt = $conn->prepare("INSERT INTO 05_cs_feedback (nama_pengunjung, rating, komentar, kategori, created_at) VALUES (?, ?, ?, ?, NOW())");
      $stmt->bind_param('siss', $nama, $rating, $komentar, $kategori);

      if ($stmt->execute()) {
        $alertMsg  = 'Terima kasih! Penilaian Anda sangat berarti bagi peningkatan layanan kami.';
        $alertType = 'success';
        // Hapus isi form setelah sukses agar bersih jika Kiosk dibuka lagi
        $_POST = array();
      } else {
        $alertMsg  = 'Gagal menyimpan. Silakan coba lagi.';
        $alertType = 'danger';
      }
      $stmt->close();
    }
  } else {
    $alertMsg  = implode(' ', $errors);
    $alertType = 'danger';
  }
}

$feedbacks = [];
/* ── Ambil semua data feedback & Hitung Statistik ────────── */
if (!isset($conn) || $conn === null) {
  $feedbacks = [];
} else {
  $result      = $conn->query("SELECT * FROM 05_cs_feedback ORDER BY created_at DESC");
  $feedbacks   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
$total       = count($feedbacks);
$avg         = $total > 0 ? array_sum(array_column($feedbacks, 'rating')) / $total : 0;
$dist        = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($feedbacks as $fb) $dist[(int)$fb['rating']]++;

/* ── Filter ──────────────────────────────────────────────── */
$filterRating = isset($_GET['rating'])    ? (int)$_GET['rating']        : 0;
$filterSearch = isset($_GET['search'])    ? sanitize($_GET['search'])   : '';
$filterKat    = isset($_GET['kategori'])  ? sanitize($_GET['kategori']) : '';

$filtered = array_filter($feedbacks, function ($fb) use ($filterRating, $filterSearch, $filterKat) {
  if ($filterRating > 0 && (int)$fb['rating'] !== $filterRating) return false;
  if ($filterSearch !== '' && stripos($fb['komentar'] . $fb['nama_pengunjung'], $filterSearch) === false) return false;
  if ($filterKat    !== '' && $fb['kategori'] !== $filterKat) return false;
  return true;
});

$cats = ['Pelayanan Informasi', 'Penanganan Keluhan', 'Kebersihan', 'Keramahan Petugas', 'Kecepatan Respons'];

/* ==========================================================
   MULAI MERENDER KONTEN (Disimpan di $content)
   ========================================================== */
ob_start();
?>

<?php if ($alertMsg && $alertType === 'danger' && !$isSubmitted): ?>
  <div class="flex items-center gap-3 px-4 py-3 mb-6 rounded-md border-l-4 bg-danger/10 border-danger text-danger">
    <i class="bi bi-exclamation-circle text-lg flex-shrink-0"></i>
    <p class="text-label"><?= $alertMsg ?></p>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
  <div class="cs-card flex flex-col items-center justify-center text-center gap-2">
    <p class="text-caption text-text/50 font-medium uppercase tracking-widest">Rata-rata Rating</p>
    <p class="text-h1 font-bold text-text-accent leading-none"><?= number_format($avg, 1) ?></p>
    <div class="flex gap-1 text-text-accent text-xl"><?= renderStars($avg) ?></div>
    <p class="text-caption text-text/50">dari <?= $total ?> ulasan</p>
  </div>

  <div class="cs-card space-y-2">
    <p class="text-label font-semibold mb-3">Distribusi Rating</p>
    <?php for ($s = 5; $s >= 1; $s--): ?>
      <?php $pct = $total > 0 ? round($dist[$s] / $total * 100) : 0; ?>
      <div class="flex items-center gap-3">
        <span class="text-caption text-text/60 w-10 flex-shrink-0"><?= $s ?> <i class="bi bi-star-fill text-text-accent"></i></span>
        <div class="flex-1 h-2 bg-white/10 rounded-full overflow-hidden">
          <div class="h-full rounded-full bg-text-accent transition-all" style="width: <?= $pct ?>%"></div>
        </div>
        <span class="text-caption text-text/50 w-6 text-right"><?= $dist[$s] ?></span>
      </div>
    <?php endfor; ?>
  </div>

  <div class="cs-card space-y-2">
    <p class="text-label font-semibold mb-3">Feedback per Kategori</p>
    <?php
    $catCount = [];
    foreach ($cats as $c) $catCount[$c] = count(array_filter($feedbacks, fn($f) => $f['kategori'] === $c));
    arsort($catCount);
    foreach ($catCount as $cat => $cnt):
    ?>
      <div class="flex items-center justify-between">
        <span class="text-caption text-text/70 truncate mr-3"><?= $cat ?></span>
        <span class="text-caption font-semibold text-accent flex-shrink-0"><?= $cnt ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="cs-card bg-surface-raised border-accent/30 flex flex-col items-center justify-center py-8 md:py-10 text-center relative overflow-hidden mb-6">
  <div class="absolute -top-10 -right-10 w-32 h-32 bg-accent/10 rounded-full blur-2xl pointer-events-none"></div>
  <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-secondary/10 rounded-full blur-2xl pointer-events-none"></div>

  <i class="bi bi-tablet-landscape text-4xl text-accent mb-4"></i>
  <h2 class="text-h2 font-bold text-text mb-2">Mode Kiosk Penilaian</h2>
  <p class="text-caption text-text/60 mb-6 max-w-md px-4">Aktifkan mode layar penuh ini sebelum memberikan perangkat kepada pengunjung agar mereka dapat mengisi penilaian secara mandiri.</p>

  <button onclick="openKiosk()" class="cs-btn bg-accent text-background hover:brightness-110 hover:shadow-accent px-6 py-3 text-body font-bold rounded-full">
    Buka Layar Penilaian
  </button>
</div>

<div class="cs-card">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
    <div>
      <h2 class="text-body font-semibold">Daftar Feedback</h2>
      <p class="text-caption text-text/50"><?= count($filtered) ?> dari <?= $total ?> ulasan ditampilkan</p>
    </div>
    <form method="GET" action="" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
      <input type="text" name="search" placeholder="Cari pengunjung..." value="<?= $filterSearch ?>" class="cs-input flex-1 sm:w-44 !py-1.5 text-caption" />
      <button type="submit" class="cs-btn bg-accent text-background !py-1.5 text-caption">Filter</button>
      <a href="feedback.php" class="cs-btn bg-transparent border border-border text-text/60 hover:bg-white/5 !py-1.5 text-caption">Reset</a>
    </form>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-label border-collapse min-w-[600px]">
      <thead>
        <tr class="border-b border-border">
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Pengunjung</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Rating</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Komentar</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($filtered as $fb): ?>
          <tr class="border-b border-border/50 hover:bg-white/3">
            <td class="py-3 px-3 font-medium"><?= sanitize($fb['nama_pengunjung']) ?></td>
            <td class="py-3 px-3">
              <div class="text-text-accent text-sm"><?= renderStars((int)$fb['rating']) ?></div>
            </td>
            <td class="py-3 px-3 max-w-xs truncate"><?= sanitize($fb['komentar']) ?></td>
            <td class="py-3 px-3"><button onclick="openDetail(<?= htmlspecialchars(json_encode($fb), ENT_QUOTES) ?>)" class="text-caption text-accent hover:underline">Detail</button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="kioskModal" data-auto-open="<?= $isSubmitted ? 'true' : 'false' ?>"
  class="fixed inset-0 !z-[9999] !m-0 hidden flex-col bg-background overflow-y-auto p-4 sm:p-6">

  <div class="fixed top-4 right-4 z-[10000]">
    <button onclick="closeKiosk()" title="Keluar dari Mode Kiosk" class="flex items-center gap-2 px-3 py-2 bg-black/40 backdrop-blur-md hover:bg-danger/80 text-white/90 hover:text-white rounded-full transition-colors text-xs sm:text-sm border border-white/10 shadow-lg">
      <i class="bi bi-x-circle text-base"></i> <span class="hidden sm:inline font-medium">Tutup</span>
    </button>
  </div>

  <div class="m-auto w-full max-w-xl bg-surface-raised border border-border-strong rounded-2xl shadow-2xl p-6 sm:p-8 relative overflow-hidden my-auto mt-12 sm:mt-auto">

    <?php if ($isSubmitted && $alertType === 'success'): ?>
      <div class="text-center py-6 animate-[fadeIn_0.5s_ease-out]">
        <div class="w-16 h-16 bg-success/20 text-success rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
          <i class="bi bi-check-lg"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-text mb-2">Terima Kasih!</h2>
        <p class="text-sm sm:text-base text-text/70 mb-6"><?= $alertMsg ?></p>

        <button onclick="closeKiosk()" class="cs-btn bg-accent text-background px-6 py-2.5 rounded-full text-sm font-bold hover:shadow-accent">
          Kembali
        </button>
      </div>

    <?php else: ?>
      <div class="text-center mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-text-accent mb-1.5">Penilaian Layanan</h1>
        <p class="text-xs sm:text-sm text-text/60">Bantu kami meningkatkan kualitas pelayanan dengan ulasan Anda.</p>
      </div>

      <?php if ($alertMsg && $alertType === 'danger'): ?>
        <div class="flex items-center gap-3 px-3 py-2.5 rounded-md border-l-4 mb-5 bg-danger/10 border-danger text-danger text-xs sm:text-sm">
          <i class="bi bi-exclamation-triangle flex-shrink-0"></i>
          <p><?= $alertMsg ?></p>
        </div>
      <?php endif; ?>

      <form method="POST" action="" id="feedbackForm" novalidate>
        <input type="hidden" name="action" value="submit_feedback" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
          <div>
            <label class="block text-xs sm:text-sm font-medium mb-1.5 text-text/80">Nama Anda <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="cs-input py-2 text-sm" placeholder="Nama..." value="<?= sanitize($_POST['nama'] ?? '') ?>" />
          </div>
          <div>
            <label class="block text-xs sm:text-sm font-medium mb-1.5 text-text/80">Layanan <span class="text-danger">*</span></label>
            <select name="kategori" class="cs-input py-2 text-sm cursor-pointer">
              <option value="" disabled class="bg-primary text-white" <?= empty($_POST['kategori']) ? 'selected' : '' ?>>-- Pilih layanan --</option>
              <?php $pilihKat = $_POST['kategori'] ?? '';
              foreach ($cats as $c): ?>
                <option value="<?= $c ?>" class="bg-primary text-white" <?= $pilihKat === $c ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-5 text-center bg-background/50 p-4 rounded-xl border border-white/5">
          <label class="block text-xs sm:text-sm font-medium mb-2 text-text/80">Berapa bintang untuk kami? <span class="text-danger">*</span></label>

          <div class="flex flex-wrap items-center justify-center gap-2" id="starContainer">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <button type="button" class="star-btn text-3xl sm:text-4xl cursor-pointer transition-all duration-200 text-white/10 hover:text-text-accent hover:scale-110 drop-shadow-lg" data-value="<?= $i ?>">★</button>
            <?php endfor; ?>
          </div>

          <input type="hidden" name="rating" id="f-rating" value="<?= (int)($_POST['rating'] ?? 0) ?>" />
          <p class="text-xs sm:text-sm font-semibold text-text-accent mt-2 h-5" id="ratingLabel">Pilih bintang</p>
        </div>

        <div class="mb-6">
          <label class="block text-xs sm:text-sm font-medium mb-1.5 text-text/80">Komentar / Saran <span class="text-danger">*</span></label>
          <textarea name="komentar" rows="3" class="cs-input py-2 text-sm resize-none" placeholder="Ceritakan pengalaman Anda..." maxlength="500"><?= sanitize($_POST['komentar'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="w-full cs-btn bg-accent text-background hover:brightness-110 py-2.5 text-sm sm:text-base font-bold rounded-lg shadow-lg shadow-accent/20">
          Kirim Penilaian
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div id="detailModal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background: rgba(2,31,66,0.85); backdrop-filter: blur(4px);">
  <div class="bg-primary border border-border-strong rounded-xl p-6 w-full max-w-md mx-4 shadow-lg">
    <div class="flex items-center justify-between mb-5">
      <h3 class="text-subheading font-semibold text-text">Detail Feedback</h3>
      <button onclick="closeDetail()" class="text-text/40 hover:text-text"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="space-y-4">
      <p class="text-label font-semibold text-text" id="m-nama"></p>
      <p class="text-label text-text/80 bg-white/5 rounded-md p-3 border border-white/5" id="m-komentar"></p>
    </div>
    <button onclick="closeDetail()" class="mt-5 w-full cs-btn bg-accent text-background font-medium hover:brightness-110">Tutup</button>
  </div>
</div>

<?php
// Simpan semua output HTML di atas ke variabel $content
$content = ob_get_clean();

// ==========================================================
// SUNTIKAN TAILWIND & CUSTOM CSS KHUSUS MODUL CS
// ==========================================================
$content .= '
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: { DEFAULT: "#0B376D", dark: "#082A53" },
                    secondary: { DEFAULT: "#167E80", dark: "#0D4859" },
                    accent: "#00D4D8", success: "#22C55E", danger: "#EF4444", warning: "#F59E0B",
                    background: "#021F42", 
                    surface: { raised: "rgba(255,255,255,0.05)" },
                    border: { DEFAULT: "rgba(255,255,255,0.1)", strong: "rgba(255,255,255,0.2)" },
                    text: { DEFAULT: "#F5F7FA", accent: "#FFB62A" }
                },
                fontSize: {
                    caption: ["0.75rem", { lineHeight: "1rem" }],
                    label: ["0.875rem", { lineHeight: "1.25rem" }],
                    body: ["1rem", { lineHeight: "1.5rem" }],
                    subheading: ["1.25rem", { lineHeight: "1.75rem" }],
                    h2: ["1.5rem", { lineHeight: "2rem", fontWeight: "700" }],
                    h1: ["2.25rem", { lineHeight: "2.5rem", fontWeight: "700" }]
                }
            }
        }
    }
</script>
<style type="text/tailwindcss">
    @layer components {
        .cs-card { @apply bg-white/5 border border-white/10 rounded-xl p-4 sm:p-6 shadow-lg backdrop-blur-sm mb-6; }
        .cs-input { @apply w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 sm:px-4 sm:py-2.5 text-white focus:outline-none focus:border-accent transition-colors; }
        .cs-btn { @apply inline-flex items-center justify-center gap-2 px-4 py-2 sm:py-2.5 rounded-lg font-medium transition-all duration-200; }
    }
</style>
<style>
/* Animasi kecil untuk Layar Sukses */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<script>
/* Logika Bintang Rating Kiosk */
const stars = document.querySelectorAll(".star-btn");
const ratingIn = document.getElementById("f-rating");
const ratingLbl = document.getElementById("ratingLabel");
const labels = ["Pilih bintang","Sangat Buruk 😞","Kurang Baik 😕","Cukup 😐","Baik 🙂","Sangat Baik! 🤩"];
let current = parseInt(ratingIn?.value) || 0;

function paintStars(val) {
  if(!stars.length) return;
  stars.forEach((s, i) => {
    s.style.color = i < val ? "#FFB62A" : "rgba(255,255,255,0.05)";
    s.style.transform = i < val ? "scale(1.15)" : "scale(1)";
  });
}

if(stars.length > 0) {
    stars.forEach((btn, idx) => {
      btn.addEventListener("mouseenter", () => paintStars(idx + 1));
      btn.addEventListener("mouseleave", () => paintStars(current));
      btn.addEventListener("click", () => {
        current = idx + 1; ratingIn.value = current; ratingLbl.textContent = labels[current]; paintStars(current);
      });
    });
    paintStars(current);
    if(current > 0) ratingLbl.textContent = labels[current];
}

/* Logika Kiosk Modal */
const kioskModal = document.getElementById("kioskModal");

function openKiosk() {
    kioskModal.classList.remove("hidden");
    kioskModal.classList.add("flex");
}

function closeKiosk() {
    kioskModal.classList.add("hidden");
    kioskModal.classList.remove("flex");
    if (kioskModal.dataset.autoOpen === "true") {
        window.location.href = window.location.pathname;
    }
}

if (kioskModal && kioskModal.dataset.autoOpen === "true") {
    openKiosk();
}

/* Modal Detail Biasa */
const modal = document.getElementById("detailModal");
function openDetail(fb) {
  document.getElementById("m-nama").textContent = fb.nama_pengunjung;
  document.getElementById("m-komentar").textContent = fb.komentar;
  modal.style.display = "flex";
}
function closeDetail() { modal.style.display = "none"; }
</script>
';

/* ==========================================================
   PANGGIL MASTER LAYOUT (HARUS DI LUAR ob_start)
   ========================================================== */
$page_title   = "Rating & Feedback";
$current_page = "feedback";

require_once "../../includes/navbarM05.php";
?>