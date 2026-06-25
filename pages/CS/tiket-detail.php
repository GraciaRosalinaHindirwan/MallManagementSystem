<?php
require_once __DIR__ . '/../../config/konek.php';
// require_once __DIR__ . '/../../auth/checkSession.php';

$pageTitle   = 'Detail Tiket — Customer Service';
$currentMenu = 'tiket';

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: tiket.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT *,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS umur_menit
    FROM `05_tiket`
    WHERE id = ?
");

$stmt->bind_param("s", $id);

$stmt->execute();

$result = $stmt->get_result();

$t = $result->fetch_assoc();

if (!$t) {
    header('Location: tiket.php');
    exit;
}

$log_stmt = $conn->prepare("
    SELECT * FROM `05_tiket_log`
    WHERE tiket_id = ?
    ORDER BY updated_at ASC
");

$log_stmt->bind_param("s", $id);

$log_stmt->execute();

$log_result = $log_stmt->get_result();

$logs = $log_result->fetch_all(MYSQLI_ASSOC);

$status_label = [
    'open'        => ['label' => 'Open',     'class' => 'bg-danger/15 text-danger',   'dot' => 'bg-danger'],
    'in_progress' => ['label' => 'Diproses', 'class' => 'bg-warning/15 text-warning', 'dot' => 'bg-warning'],
    'resolved'    => ['label' => 'Selesai',  'class' => 'bg-success/15 text-success', 'dot' => 'bg-success'],
];

$kategori_label = [
    'facility' => ['label' => 'Facility', 'class' => 'bg-accent/10 text-accent'],
    'security' => ['label' => 'Security', 'class' => 'bg-warning/10 text-warning'],
    'cleaning' => ['label' => 'Cleaning', 'class' => 'bg-success/10 text-success'],
    'other'    => ['label' => 'Lainnya',  'class' => 'bg-white/10 text-white/60'],
];

$breach     = $t['umur_menit'] > $t['sla_menit'] && $t['status'] !== 'resolved';
$sisa       = $t['sla_menit'] - $t['umur_menit'];
$persen_sla = $t['sla_menit'] > 0 ? min(100, round(($t['umur_menit'] / $t['sla_menit']) * 100)) : 0;
$foto_list  = !empty($t['foto']) ? json_decode($t['foto'], true) : [];
$kat        = $kategori_label[$t['kategori']] ?? $kategori_label['other'];
$stat       = $status_label[$t['status']] ?? $status_label['open'];

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

<div class="flex items-center gap-2 text-caption text-text/40 mb-4">
    <a href="tiket.php" class="hover:text-accent transition-colors">Semua Tiket</a>
    <i class="bi bi-chevron-right text-xs"></i>
    <span class="text-text/70"><?= htmlspecialchars($t['id']) ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-4">

        <div class="cs-card space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="flex gap-2 flex-wrap items-center">
                    <span class="font-mono text-accent font-semibold"><?= htmlspecialchars($t['id']) ?></span>
                    <span class="px-2 py-0.5 rounded-full text-caption <?= $kat['class'] ?>"><?= $kat['label'] ?></span>
                    <span class="px-2 py-0.5 rounded-full text-caption <?= $stat['class'] ?>"><?= $stat['label'] ?></span>
                    <?php if ($breach): ?>
                        <span class="px-2 py-0.5 rounded-full text-caption bg-danger/15 text-danger">
                            <i class="bi bi-exclamation-circle-fill"></i> SLA Breach
                        </span>
                    <?php endif; ?>
                </div>
                <a href="tiket.php" class="cs-btn bg-white/5 hover:bg-white/10 text-white/60 text-caption px-4 py-1.5 self-start">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <p class="text-caption text-text/40">Dibuat: <?= date('d M Y, H:i', strtotime($t['created_at'])) ?></p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-border">
                <div class="space-y-3">
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Nama Pelapor</p>
                        <p class="text-label"><?= htmlspecialchars($t['pelapor']) ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Nomor HP</p>
                        <p class="text-label"><?= $t['no_hp'] ? htmlspecialchars($t['no_hp']) : '—' ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Lokasi</p>
                        <p class="text-label"><?= htmlspecialchars($t['lokasi']) ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Lantai / Area</p>
                        <p class="text-label"><?= htmlspecialchars(($t['floor_name'] ?: '—') . ' / ' . ($t['area_name'] ?: '—')) ?></p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Nama Aset</p>
                        <p class="text-label"><?= $t['asset_name'] ? htmlspecialchars($t['asset_name']) : '—' ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Kode Aset</p>
                        <p class="text-label"><?= $t['asset_code'] ? htmlspecialchars($t['asset_code']) : '—' ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Jenis Kerusakan</p>
                        <p class="text-label"><?= $t['damage_type'] ? htmlspecialchars($t['damage_type']) : '—' ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Prioritas / Severity</p>
                        <p class="text-label"><?= htmlspecialchars($t['priority']) ?> · <?= (int)$t['severity_level'] ?>/10</p>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-border">
                <p class="text-caption text-text/40 mb-1">Deskripsi Masalah</p>
                <p class="text-label leading-relaxed"><?= nl2br(htmlspecialchars($t['deskripsi'])) ?></p>
            </div>

            <?php if (!empty($foto_list)): ?>
            <div class="pt-3 border-t border-border">
                <p class="text-caption text-text/40 mb-2">Foto Lampiran</p>
                <div class="flex gap-3 flex-wrap">
                    <?php foreach ($foto_list as $foto): ?>
                        <a href="/<?= htmlspecialchars($foto) ?>" target="_blank"
                           class="w-24 h-24 rounded-md overflow-hidden border border-border block hover:border-accent transition-colors">
                            <img src="/<?= htmlspecialchars($foto) ?>" class="w-full h-full object-cover">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="cs-card space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-border">
                <i class="bi bi-clock-history text-accent"></i>
                <h3 class="text-label font-semibold">Riwayat Status</h3>
            </div>

            <?php if (empty($logs)): ?>
                <p class="text-caption text-text/30 text-center py-4">Belum ada perubahan status.</p>
            <?php else: ?>
            <div class="space-y-0">
                <?php foreach ($logs as $i => $log):
                    $s = $status_label[$log['status_baru']] ?? $status_label['open'];
                ?>
                <div class="flex gap-3">
                    <div class="flex flex-col items-center">
                        <div class="w-2.5 h-2.5 rounded-full mt-1 flex-shrink-0 <?= $s['dot'] ?>"></div>
                        <?php if ($i < count($logs) - 1): ?>
                            <div class="w-px flex-1 bg-border mt-1"></div>
                        <?php endif; ?>
                    </div>
                    <div class="pb-4 space-y-0.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded-full text-caption <?= $s['class'] ?>"><?= $s['label'] ?></span>
                            <?php if ($log['status_lama'] && isset($status_label[$log['status_lama']])): ?>
                                <span class="text-caption text-text/30">dari <?= $status_label[$log['status_lama']]['label'] ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($log['catatan']): ?>
                            <p class="text-caption text-text/60"><?= htmlspecialchars($log['catatan']) ?></p>
                        <?php endif; ?>
                        <p class="text-caption text-text/30"><?= date('d M Y, H:i', strtotime($log['updated_at'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="space-y-4">

        <div class="cs-card space-y-3">
            <div class="flex items-center gap-2 pb-3 border-b border-border">
                <i class="bi bi-speedometer2 text-accent"></i>
                <h3 class="text-label font-semibold">Status SLA</h3>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between text-caption">
                    <span class="text-text/40">Durasi berjalan</span>
                    <span class="<?= $breach ? 'text-danger font-semibold' : 'text-white/70' ?>"><?= $t['umur_menit'] ?> menit</span>
                </div>
                <div class="flex justify-between text-caption">
                    <span class="text-text/40">Batas SLA</span>
                    <span class="text-white/70"><?= $t['sla_menit'] ?> menit</span>
                </div>
                <div class="w-full bg-white/10 rounded-full h-2">
                    <div class="<?= $breach ? 'bg-danger' : 'bg-accent' ?> h-2 rounded-full" style="width:<?= $persen_sla ?>%"></div>
                </div>
                <?php if ($t['status'] === 'resolved'): ?>
                    <p class="text-caption text-success text-center pt-1"><i class="bi bi-check-circle"></i> Selesai</p>
                <?php elseif ($breach): ?>
                    <p class="text-caption text-danger text-center pt-1"><i class="bi bi-exclamation-triangle"></i> Lewat <?= abs($sisa) ?> menit</p>
                <?php else: ?>
                    <p class="text-caption text-text/40 text-center pt-1">Sisa <?= $sisa ?> menit</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="cs-card space-y-3">
            <div class="flex items-center gap-2 pb-3 border-b border-border">
                <i class="bi bi-info-circle text-accent"></i>
                <h3 class="text-label font-semibold">Info Tiket</h3>
            </div>
            <div class="space-y-2 text-caption">
                <div class="flex justify-between">
                    <span class="text-text/40">Departemen</span>
                    <span class="text-white/80"><?= htmlspecialchars($t['dept']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text/40">Tanggal Lapor</span>
                    <span class="text-white/80"><?= date('d M Y', strtotime($t['report_date'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text/40">Prioritas</span>
                    <span class="text-white/80"><?= htmlspecialchars($t['priority']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text/40">Severity</span>
                    <span class="text-white/80"><?= (int)$t['severity_level'] ?>/10</span>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require_once '../../includes/navbarM05.php';
?>