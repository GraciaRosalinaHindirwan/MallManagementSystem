<?php
require_once __DIR__ . '/../../config/koneksi.php';

$pageTitle   = 'Detail Tiket — Customer Service';
$currentMenu = 'tiket';

$id = $_GET['id'] ?? '';

if (!$id) {
    header('Location: tiket.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT *,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS umur_menit
    FROM tiket
    WHERE id = ?
");
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) {
    header('Location: tiket.php');
    exit;
}

$log_list = $pdo->prepare("
    SELECT tl.*, pc.nama AS nama_petugas
    FROM tiket_log tl
    LEFT JOIN petugas_cs pc ON tl.updated_by = pc.id
    WHERE tl.tiket_id = ?
    ORDER BY tl.updated_at ASC
");
$log_list->execute([$id]);
$logs = $log_list->fetchAll();

$status_label = [
    'open'        => ['label' => 'Open',     'class' => 'bg-danger/15 text-danger',   'dot' => 'bg-danger'],
    'in_progress' => ['label' => 'Diproses', 'class' => 'bg-warning/15 text-warning', 'dot' => 'bg-warning'],
    'resolved'    => ['label' => 'Selesai',  'class' => 'bg-success/15 text-success', 'dot' => 'bg-success'],
];

$kategori_label = [
    'facility' => ['label' => 'Facility', 'class' => 'bg-accent/10 text-accent'],
    'security' => ['label' => 'Security', 'class' => 'bg-warning/10 text-warning'],
    'cleaning' => ['label' => 'Cleaning', 'class' => 'bg-success/10 text-success'],
    'other'    => ['label' => 'Lainnya',  'class' => 'bg-text/10 text-text/60'],
];

$breach     = $t['umur_menit'] > $t['sla_menit'] && $t['status'] !== 'resolved';
$sisa       = $t['sla_menit'] - $t['umur_menit'];
$persen_sla = min(100, round(($t['umur_menit'] / $t['sla_menit']) * 100));
$foto_list  = $t['foto'] ? json_decode($t['foto'], true) : [];
$kat        = $kategori_label[$t['kategori']] ?? $kategori_label['other'];
$stat       = $status_label[$t['status']] ?? $status_label['open'];

ob_start();
?>

<div class="flex items-center gap-2 text-caption text-text/40 mb-2">
    <a href="tiket.php" class="hover:text-accent transition-colors">Semua Tiket</a>
    <i class="bi bi-chevron-right text-xs"></i>
    <span class="text-text/70"><?= htmlspecialchars($t['id']) ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-4">

        <div class="cs-card space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-label text-accent font-semibold"><?= htmlspecialchars($t['id']) ?></span>
                        <span class="px-2 py-0.5 rounded-full text-caption font-medium <?= $kat['class'] ?>"><?= $kat['label'] ?></span>
                        <span class="px-2 py-0.5 rounded-full text-caption font-medium <?= $stat['class'] ?>"><?= $stat['label'] ?></span>
                        <?php if ($breach): ?>
                            <span class="px-2 py-0.5 rounded-full text-caption font-medium bg-danger/15 text-danger">
                                <i class="bi bi-exclamation-circle-fill"></i> SLA Breach
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-caption text-text/40">Dibuat: <?= date('d M Y, H:i', strtotime($t['created_at'])) ?></p>
                </div>
                <a href="tiket.php" class="cs-btn bg-white/5 hover:bg-white/10 text-text/60 text-caption px-4 py-1.5 self-start">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-border">
                <div class="space-y-3">
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Nama Aset</p>
                        <p class="text-label text-text/90"><?= $t['asset_name'] ? htmlspecialchars($t['asset_name']) : '<span class="text-text/30">—</span>' ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Kode Aset</p>
                        <p class="text-label text-text/90"><?= $t['asset_code'] ? htmlspecialchars($t['asset_code']) : '<span class="text-text/30">—</span>' ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Lantai / Area</p>
                        <p class="text-label text-text/90"><?= htmlspecialchars(($t['floor_name'] ?: '-') . ' / ' . ($t['area_name'] ?: '-')) ?></p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Jenis Kerusakan</p>
                        <p class="text-label text-text/90"><?= $t['damage_type'] ? htmlspecialchars($t['damage_type']) : '<span class="text-text/30">—</span>' ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Prioritas / Severity</p>
                        <p class="text-label text-text/90"><?= htmlspecialchars($t['priority']) ?> · <?= (int)$t['severity_level'] ?>/10</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-border">
                <div class="space-y-3">
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Nama Pelapor</p>
                        <p class="text-label text-text/90"><?= htmlspecialchars($t['pelapor']) ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Nomor HP</p>
                        <p class="text-label text-text/90"><?= $t['no_hp'] ? htmlspecialchars($t['no_hp']) : '<span class="text-text/30">—</span>' ?></p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Lokasi Kejadian</p>
                        <p class="text-label text-text/90"><?= htmlspecialchars($t['lokasi']) ?></p>
                    </div>
                    <div>
                        <p class="text-caption text-text/40 mb-0.5">Departemen</p>
                        <p class="text-label text-text/90"><?= htmlspecialchars($t['dept']) ?></p>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-border">
                <p class="text-caption text-text/40 mb-1">Deskripsi Masalah</p>
                <p class="text-label text-text/80 leading-relaxed"><?= nl2br(htmlspecialchars($t['deskripsi'])) ?></p>
            </div>

            <?php if (!empty($foto_list)): ?>
            <div class="pt-2 border-t border-border">
                <p class="text-caption text-text/40 mb-2">Foto Lampiran</p>
                <div class="flex gap-3 flex-wrap">
                    <?php foreach ($foto_list as $foto): ?>
                        <a href="<?= htmlspecialchars($foto) ?>" target="_blank" class="w-24 h-24 rounded-md overflow-hidden border border-border block hover:border-accent transition-colors">
                            <img src="<?= htmlspecialchars($foto) ?>" class="w-full h-full object-cover" />
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
                            <span class="px-2 py-0.5 rounded-full text-caption font-medium <?= $s['class'] ?>"><?= $s['label'] ?></span>
                            <?php if ($log['status_lama']): $sl = $status_label[$log['status_lama']] ?? null; ?>
                                <?php if ($sl): ?>
                                    <span class="text-caption text-text/30">dari <?= $sl['label'] ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($log['catatan']): ?>
                            <p class="text-caption text-text/60"><?= htmlspecialchars($log['catatan']) ?></p>
                        <?php endif; ?>
                        <p class="text-caption text-text/30">
                            <?= date('d M Y, H:i', strtotime($log['updated_at'])) ?>
                            <?= $log['nama_petugas'] ? '· ' . htmlspecialchars($log['nama_petugas']) : '' ?>
                        </p>
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
                    <span class="<?= $breach ? 'text-danger font-semibold' : 'text-text/70' ?>"><?= $t['umur_menit'] ?> menit</span>
                </div>
                <div class="flex justify-between text-caption">
                    <span class="text-text/40">Batas SLA</span>
                    <span class="text-text/70"><?= $t['sla_menit'] ?> menit</span>
                </div>
                <div class="w-full bg-white/10 rounded-full h-2 mt-1">
                    <div class="<?= $breach ? 'bg-danger' : 'bg-accent' ?> h-2 rounded-full transition-all" style="width:<?= $persen_sla ?>%"></div>
                </div>
                <?php if ($t['status'] === 'resolved'): ?>
                    <p class="text-caption text-success text-center pt-1"><i class="bi bi-check-circle"></i> Tiket sudah diselesaikan</p>
                <?php elseif ($breach): ?>
                    <p class="text-caption text-danger text-center pt-1"><i class="bi bi-exclamation-triangle"></i> Lewat <?= abs($sisa) ?> menit</p>
                <?php else: ?>
                    <p class="text-caption text-text/40 text-center pt-1">Sisa <?= $sisa ?> menit</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="cs-card space-y-3">
            <div class="flex items-center gap-2 pb-3 border-b border-border">
                <i class="bi bi-pencil-square text-accent"></i>
                <h3 class="text-label font-semibold">Update Status</h3>
            </div>
            <form method="POST" action="tiket-update-status.php" class="space-y-3">
                <input type="hidden" name="id" value="<?= htmlspecialchars($t['id']) ?>" />
                <div class="space-y-1.5">
                    <label class="text-caption text-text/50">Status Baru</label>
                    <select name="status_baru" class="cs-input text-caption" style="background-color:#0B376D;color:#F5F7FA;" required>
                        <option value="open"        <?= $t['status'] === 'open'        ? 'selected' : '' ?>>Open</option>
                        <option value="in_progress" <?= $t['status'] === 'in_progress' ? 'selected' : '' ?>>Diproses</option>
                        <option value="resolved"    <?= $t['status'] === 'resolved'    ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-caption text-text/50">Catatan</label>
                    <textarea name="catatan" class="cs-input resize-none text-caption" rows="3" placeholder="Opsional..."></textarea>
                </div>
                <button type="submit" class="cs-btn bg-accent text-background hover:bg-accent/90 w-full justify-center py-2">
                    <i class="bi bi-check2-circle"></i> Simpan Status
                </button>
            </form>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>