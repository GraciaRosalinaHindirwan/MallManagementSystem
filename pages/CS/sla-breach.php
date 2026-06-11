<?php
require_once __DIR__ . '/../../config/koneksi.php';

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

$breach_list = $pdo->query("
    SELECT *,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS umur_menit,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) - sla_menit AS lewat_menit
    FROM tiket
    WHERE status != 'resolved'
      AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) > sla_menit
    ORDER BY lewat_menit DESC
")->fetchAll();

$total_breach = count($breach_list);
$open_count   = count(array_filter($breach_list, fn($t) => $t['status'] === 'open'));
$proses_count = count(array_filter($breach_list, fn($t) => $t['status'] === 'in_progress'));

ob_start();
?>

<?php if ($total_breach > 0): ?>
<div class="flex items-center gap-3 bg-danger/10 border border-danger/30 rounded-lg px-5 py-3">
    <i class="bi bi-exclamation-triangle-fill text-danger text-lg"></i>
    <div>
        <p class="text-label font-semibold text-danger">Peringatan SLA Breach</p>
        <p class="text-caption text-text/60">Terdapat <strong class="text-danger"><?= $total_breach ?> tiket</strong> yang melewati batas waktu penanganan. Segera eskalasi ke departemen terkait.</p>
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
        <div class="w-10 h-10 rounded-lg bg-danger/15 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-exclamation-circle text-danger text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Total Breach</p>
            <p class="text-h2 font-bold text-danger"><?= $total_breach ?></p>
        </div>
    </div>
    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-danger/15 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-record-circle text-danger text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Status Open</p>
            <p class="text-h2 font-bold text-danger"><?= $open_count ?></p>
        </div>
    </div>
    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-warning/15 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-arrow-repeat text-warning text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Sedang Diproses</p>
            <p class="text-h2 font-bold text-warning"><?= $proses_count ?></p>
        </div>
    </div>
</div>

<div class="cs-card space-y-4">
    <div class="flex items-center gap-3 pb-4 border-b border-border">
        <i class="bi bi-exclamation-triangle text-danger"></i>
        <h2 class="text-label font-semibold">Tiket Melebihi SLA</h2>
    </div>

    <?php if (empty($breach_list)): ?>
        <p class="text-center text-caption text-text/30 py-10">Tidak ada tiket yang breach SLA.</p>
    <?php endif; ?>

    <div class="space-y-3">
        <?php foreach ($breach_list as $t):
            $lewat  = (int) $t['lewat_menit'];
            $persen = min(100, round(($t['umur_menit'] / $t['sla_menit']) * 100));
            $kat    = $kategori_label[$t['kategori']] ?? $kategori_label['other'];
            $stat   = $status_label[$t['status']] ?? $status_label['open'];
        ?>
        <div class="border border-danger/30 bg-danger/5 rounded-lg p-4 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                <div class="flex items-center gap-3">
                    <span class="font-mono text-caption text-accent font-semibold"><?= htmlspecialchars($t['id']) ?></span>
                    <span class="px-2 py-0.5 rounded-full text-caption font-medium <?= $kat['class'] ?>"><?= $kat['label'] ?></span>
                    <span class="px-2 py-0.5 rounded-full text-caption font-medium <?= $stat['class'] ?>"><?= $stat['label'] ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-caption text-danger font-semibold bg-danger/15 px-3 py-1 rounded-full">
                        <i class="bi bi-clock"></i> Lewat <?= $lewat ?> menit
                    </span>
                    <a href="tiket-detail.php?id=<?= urlencode($t['id']) ?>" class="cs-btn bg-white/5 hover:bg-white/10 text-text/70 text-caption px-3 py-1">
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
                    <p class="text-text/40 mb-0.5">Departemen</p>
                    <p class="text-text/80"><?= htmlspecialchars($t['dept']) ?></p>
                </div>
            </div>

            <div>
                <p class="text-caption text-text/40 mb-0.5">Deskripsi</p>
                <p class="text-caption text-text/70"><?= htmlspecialchars($t['deskripsi']) ?></p>
            </div>

            <div class="space-y-1">
                <div class="flex justify-between text-caption text-text/40">
                    <span>Durasi: <?= $t['umur_menit'] ?> menit</span>
                    <span>SLA: <?= $t['sla_menit'] ?> menit</span>
                </div>
                <div class="w-full bg-white/10 rounded-full h-1.5">
                    <div class="bg-danger h-1.5 rounded-full" style="width:<?= $persen ?>%"></div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-1 border-t border-border">
                <p class="text-caption text-text/30">Dibuat: <?= date('Y-m-d H:i', strtotime($t['created_at'])) ?></p>
                <button class="cs-btn bg-danger/15 text-danger hover:bg-danger/25 text-caption px-3 py-1">
                    <i class="bi bi-send"></i> Eskalasi
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>