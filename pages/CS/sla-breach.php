<?php
require_once __DIR__ . '/../../config/konek_05.php';
// require_once __DIR__ . '/../../auth/checkSession.php';

$pageTitle   = 'SLA Breach — Customer Service';
$currentMenu = 'sla-breach';

$status_label = [
    'open'        => ['label' => 'Open', 'class' => 'bg-danger/15 text-danger'],
    'in_progress' => ['label' => 'Diproses', 'class' => 'bg-warning/15 text-warning'],
];

$kategori_label = [
    'facility' => ['label' => 'Facility', 'class' => 'bg-accent/10 text-accent'],
    'security' => ['label' => 'Security', 'class' => 'bg-warning/10 text-warning'],
    'cleaning' => ['label' => 'Cleaning', 'class' => 'bg-success/10 text-success'],
    'other'    => ['label' => 'Lainnya',  'class' => 'bg-text/10 text-text/60'],
];

$breach_list = $pdo->query("
    SELECT *,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS umur_menit,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) - sla_menit AS lewat_menit
    FROM `05_tiket`
    WHERE status != 'resolved'
      AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) > sla_menit
    ORDER BY lewat_menit DESC
")->fetchAll();

$total_breach = count($breach_list);
$open_count   = count(array_filter($breach_list, fn($t) => $t['status'] === 'open'));
$proses_count = count(array_filter($breach_list, fn($t) => $t['status'] === 'in_progress'));

ob_start();
?>

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
        success: "#22C55E",
        danger: "#EF4444",
        warning: "#F59E0B",
        text: "#F5F7FA",
        border: "rgba(255,255,255,0.1)"
      }
    }
  }
};
</script>

<div class="space-y-4">

<?php if ($total_breach > 0): ?>
<div class="flex items-center gap-3 bg-danger/10 border border-danger/30 rounded-lg px-5 py-3">
    <i class="bi bi-exclamation-triangle-fill text-danger text-lg"></i>
    <div>
        <p class="text-label font-semibold text-danger">Peringatan SLA Breach</p>
        <p class="text-caption text-text/60">
            Terdapat <strong class="text-danger"><?= $total_breach ?> tiket</strong>
        </p>
    </div>
</div>
<?php else: ?>
<div class="flex items-center gap-3 bg-success/10 border border-success/30 rounded-lg px-5 py-3">
    <i class="bi bi-check-circle-fill text-success text-lg"></i>
    <p class="text-label text-success font-semibold">Semua tiket aman</p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="cs-card">
        <p>Total Breach</p>
        <p class="text-danger text-h2"><?= $total_breach ?></p>
    </div>

    <div class="cs-card">
        <p>Open</p>
        <p class="text-danger text-h2"><?= $open_count ?></p>
    </div>

    <div class="cs-card">
        <p>Diproses</p>
        <p class="text-warning text-h2"><?= $proses_count ?></p>
    </div>
</div>

<div class="cs-card space-y-3">
    <h2 class="text-label font-semibold">Tiket SLA Breach</h2>

    <?php if (empty($breach_list)): ?>
        <p class="text-text/40">Tidak ada data</p>
    <?php else: ?>
        <?php foreach ($breach_list as $t): ?>
            <?php
                $lewat  = (int) $t['lewat_menit'];
                $kat    = $kategori_label[$t['kategori']] ?? $kategori_label['other'];
                $stat   = $status_label[$t['status']] ?? $status_label['open'];
            ?>
            <div class="border border-danger/20 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-accent font-mono"><?= htmlspecialchars($t['id']) ?></span>
                    <span class="text-danger">+<?= $lewat ?> menit</span>
                </div>

                <div class="flex gap-2">
                    <span class="px-2 py-0.5 rounded-full text-caption <?= $kat['class'] ?>">
                        <?= $kat['label'] ?>
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-caption <?= $stat['class'] ?>">
                        <?= $stat['label'] ?>
                    </span>
                </div>

                <p class="text-text/70 text-caption">
                    <?= htmlspecialchars($t['deskripsi']) ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>

<?php
$content = ob_get_clean();
require_once '../../includes/navbarM05.php';
?>