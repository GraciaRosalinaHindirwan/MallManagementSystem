<?php
require_once __DIR__ . '/../../config/konek_05.php';
// require_once __DIR__ . '/../../auth/checkSession.php';

$pageTitle   = 'Detail Tiket — Customer Service';
$currentMenu = 'tiket';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: tiket.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT *,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS umur_menit
    FROM `05_tiket`
    WHERE id = ?
");
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) {
    header('Location: tiket.php');
    exit;
}

$log_stmt = $pdo->prepare("
    SELECT * FROM `05_tiket_log`
    WHERE tiket_id = ?
    ORDER BY updated_at ASC
");
$log_stmt->execute([$id]);
$logs = $log_stmt->fetchAll();

$status_label = [
    'open'        => ['label' => 'Open', 'class' => 'bg-danger/15 text-danger'],
    'in_progress' => ['label' => 'Diproses', 'class' => 'bg-warning/15 text-warning'],
    'resolved'    => ['label' => 'Selesai', 'class' => 'bg-success/15 text-success'],
];

$kategori_label = [
    'facility' => ['label' => 'Facility', 'class' => 'bg-accent/10 text-accent'],
    'security' => ['label' => 'Security', 'class' => 'bg-warning/10 text-warning'],
    'cleaning' => ['label' => 'Cleaning', 'class' => 'bg-success/10 text-success'],
    'other'    => ['label' => 'Lainnya', 'class' => 'bg-white/10 text-white/60'],
];

$breach = $t['umur_menit'] > $t['sla_menit'] && $t['status'] !== 'resolved';

$sisa = $t['sla_menit'] - $t['umur_menit'];

$persen_sla = $t['sla_menit'] > 0
    ? min(100, round(($t['umur_menit'] / $t['sla_menit']) * 100))
    : 0;

$foto_list = !empty($t['foto']) ? json_decode($t['foto'], true) : [];

$kat  = $kategori_label[$t['kategori']] ?? $kategori_label['other'];
$stat = $status_label[$t['status']] ?? $status_label['open'];

ob_start();
?>

<link rel="stylesheet" href="/public/asset/designSystem.css">
<link rel="stylesheet" href="/public/asset/templateM05.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.tailwindcss.com"></script>

<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary: "#0B376D",
        accent: "#00D4D8",
        background: "#021F42",
        border: "rgba(255,255,255,0.1)",
        text: "#F5F7FA",
        danger: "#EF4444",
        warning: "#F59E0B",
        success: "#22C55E"
      }
    }
  }
}
</script>

<div class="flex items-center gap-2 text-caption text-text/40 mb-2">
    <a href="tiket.php" class="hover:text-accent">Semua Tiket</a>
    <i class="bi bi-chevron-right text-xs"></i>
    <span class="text-text/70"><?= htmlspecialchars($t['id']) ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-4">

        <div class="cs-card space-y-4">

            <div class="flex gap-2 flex-wrap">
                <span class="font-mono text-accent font-semibold">
                    <?= htmlspecialchars($t['id']) ?>
                </span>

                <span class="px-2 py-0.5 rounded-full text-caption <?= $kat['class'] ?>">
                    <?= $kat['label'] ?>
                </span>

                <span class="px-2 py-0.5 rounded-full text-caption <?= $stat['class'] ?>">
                    <?= $stat['label'] ?>
                </span>

                <?php if ($breach): ?>
                    <span class="px-2 py-0.5 rounded-full text-caption bg-danger/15 text-danger">
                        SLA Breach
                    </span>
                <?php endif; ?>
            </div>

            <p class="text-caption text-text/40">
                <?= date('d M Y H:i', strtotime($t['created_at'])) ?>
            </p>

            <div class="border-t border-border pt-3">
                <p class="text-label"><?= nl2br(htmlspecialchars($t['deskripsi'])) ?></p>
            </div>

            <?php if (!empty($foto_list)): ?>
            <div class="border-t border-border pt-3">
                <p class="text-caption text-text/40 mb-2">Foto</p>

                <div class="flex gap-3 flex-wrap">
                    <?php foreach ($foto_list as $foto): ?>
                        <a href="/public/uploads/tiket/<?= htmlspecialchars($foto) ?>" target="_blank"
                           class="w-24 h-24 rounded-md overflow-hidden border border-border">
                            <img src="/public/uploads/tiket/<?= htmlspecialchars($foto) ?>"
                                 class="w-full h-full object-cover">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

    </div>

    <div class="space-y-4">

        <div class="cs-card">
            <p class="text-caption text-text/40">SLA Progress</p>

            <div class="w-full bg-white/10 h-2 rounded-full mt-2">
                <div class="<?= $breach ? 'bg-danger' : 'bg-accent' ?> h-2 rounded-full"
                     style="width:<?= $persen_sla ?>%"></div>
            </div>

            <p class="text-caption mt-2">
                <?= $t['umur_menit'] ?> / <?= $t['sla_menit'] ?> menit
            </p>
        </div>

        <div class="cs-card">
            <form method="POST" action="tiket-update-status.php" class="space-y-3">

                <input type="hidden" name="id" value="<?= htmlspecialchars($t['id']) ?>">

                <select name="status_baru"
                        class="cs-input w-full"
                        style="background:#0B376D;color:#fff">
                    <option value="open">Open</option>
                    <option value="in_progress">Diproses</option>
                    <option value="resolved">Selesai</option>
                </select>

                <textarea name="catatan"
                          rows="3"
                          class="cs-input w-full"
                          placeholder="Catatan"></textarea>

                <button class="cs-btn bg-accent text-black w-full justify-center py-2">
                    Simpan
                </button>

            </form>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require_once "../../includes/navbarM05.php";
?>