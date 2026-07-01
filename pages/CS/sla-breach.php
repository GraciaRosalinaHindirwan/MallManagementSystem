<?php
require_once __DIR__ . '/../../config/konek.php';
// require_once __DIR__ . '/../../auth/checkSession.php';

$pageTitle   = 'SLA Breach — Customer Service';
$currentMenu = 'sla-breach';

$status_label = [
    'open'        => ['label' => 'Open',     'class' => 'bg-danger/15 text-danger'],
    'in_progress' => ['label' => 'Diproses', 'class' => 'bg-warning/15 text-warning'],
];

$kategori_label = [
    'facility' => ['label' => 'Facility', 'class' => 'bg-accent/10 text-accent'],
    'security' => ['label' => 'Security', 'class' => 'bg-warning/10 text-warning'],
    'cleaning' => ['label' => 'Cleaning', 'class' => 'bg-success/10 text-success'],
    'other'    => ['label' => 'Lainnya',  'class' => 'bg-text/10 text-text/60'],
];

$query = "
    SELECT t.*,
        wo.sla_target,
        TIMESTAMPDIFF(MINUTE, wo.sla_target, NOW()) AS lewat_menit
    FROM `05_tiket` t
    LEFT JOIN `03_damage_reports` dr ON dr.ticket_id = t.id
    LEFT JOIN `03_work_orders` wo ON wo.report_id = dr.report_id
    WHERE t.status != 'resolved'
      AND wo.sla_target IS NOT NULL
      AND NOW() > wo.sla_target
    ORDER BY lewat_menit DESC
";

$result = $conn->query($query);

$breach_list = $result->fetch_all(MYSQLI_ASSOC);

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
            Terdapat <strong class="text-danger"><?= $total_breach ?> tiket</strong> yang melewati batas waktu SLA.
        </p>
    </div>
</div>
<?php else: ?>
<div class="flex items-center gap-3 bg-success/10 border border-success/30 rounded-lg px-5 py-3">
    <i class="bi bi-check-circle-fill text-success text-lg"></i>
    <p class="text-label text-success font-semibold">Semua tiket dalam batas waktu SLA.</p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-danger/15 flex items-center justify-center">
            <i class="bi bi-exclamation-circle text-danger text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Total Breach</p>
            <p class="text-h2 font-bold text-danger"><?= $total_breach ?></p>
        </div>
    </div>
    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-danger/15 flex items-center justify-center">
            <i class="bi bi-record-circle text-danger text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Open</p>
            <p class="text-h2 font-bold text-danger"><?= $open_count ?></p>
        </div>
    </div>
    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-warning/15 flex items-center justify-center">
            <i class="bi bi-arrow-repeat text-warning text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Diproses</p>
            <p class="text-h2 font-bold text-warning"><?= $proses_count ?></p>
        </div>
    </div>
</div>

<div class="cs-card space-y-3">
    <div class="flex items-center gap-2 pb-3 border-b border-border">
        <i class="bi bi-exclamation-triangle text-danger"></i>
        <h2 class="text-label font-semibold">Tiket Melebihi SLA</h2>
    </div>

    <?php if (empty($breach_list)): ?>
        <p class="text-caption text-text/30 text-center py-6">Tidak ada tiket yang breach SLA.</p>
    <?php else: ?>
        <?php foreach ($breach_list as $t):
            $lewat = (int) $t['lewat_menit'];
            $kat   = $kategori_label[$t['kategori']] ?? $kategori_label['other'];
            $stat  = $status_label[$t['status']] ?? $status_label['open'];
        ?>
        <div class="border border-danger/20 bg-danger/5 rounded-lg p-4 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-caption text-accent font-semibold"><?= htmlspecialchars($t['id']) ?></span>
                    <span class="px-2 py-0.5 rounded-full text-caption <?= $kat['class'] ?>"><?= $kat['label'] ?></span>
                    <span class="px-2 py-0.5 rounded-full text-caption <?= $stat['class'] ?>"><?= $stat['label'] ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-caption text-danger font-semibold bg-danger/15 px-3 py-1 rounded-full">
                        <i class="bi bi-clock"></i> Lewat <?= $lewat ?> menit
                    </span>
                    <a href="tiket-detail.php?id=<?= urlencode($t['id']) ?>"
                       class="cs-btn bg-white/5 hover:bg-white/10 text-text/70 text-caption px-3 py-1">
                        <i class="bi bi-eye"></i> Detail
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-caption">
                <div>
                    <p class="text-text/40 mb-0.5">Pelapor</p>
                    <p class="text-text/80"><?= htmlspecialchars($t['pelapor']) ?></p>
                </div>
                <div>
                    <p class="text-text/40 mb-0.5">Lokasi</p>
                    <p class="text-text/80"><?= htmlspecialchars($t['lokasi']) ?></p>
                </div>
                <div>
                    <p class="text-text/40 mb-0.5">SLA Target</p>
                    <p class="text-danger"><?= date('d M Y, H:i', strtotime($t['sla_target'])) ?></p>
                </div>
            </div>

            <p class="text-caption text-text/60"><?= htmlspecialchars($t['deskripsi']) ?></p>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>

<?php
$content = ob_get_clean();
require_once '../../includes/navbarM05.php';
?>