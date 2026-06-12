<?php
require_once __DIR__ . '/../../config/koneksi.php';

$pageTitle   = 'Semua Tiket — Customer Service';
$currentMenu = 'tiket';

$status_label = [
    'open'        => ['label' => 'Open',     'class' => 'bg-danger/15 text-danger'],
    'in_progress' => ['label' => 'Diproses', 'class' => 'bg-warning/15 text-warning'],
    'resolved'    => ['label' => 'Selesai',  'class' => 'bg-success/15 text-success'],
];

$kategori_label = [
    'facility' => ['label' => 'Facility', 'class' => 'bg-accent/10 text-accent'],
    'security' => ['label' => 'Security', 'class' => 'bg-warning/10 text-warning'],
    'cleaning' => ['label' => 'Cleaning', 'class' => 'bg-success/10 text-success'],
    'other'    => ['label' => 'Lainnya',  'class' => 'bg-text/10 text-text/60'],
];

$tiket_list = $pdo->query("
    SELECT *,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) AS umur_menit
    FROM tiket
    ORDER BY created_at DESC
")->fetchAll();

$total       = count($tiket_list);
$open        = count(array_filter($tiket_list, fn($t) => $t['status'] === 'open'));
$in_progress = count(array_filter($tiket_list, fn($t) => $t['status'] === 'in_progress'));
$breach      = count(array_filter($tiket_list, fn($t) => $t['umur_menit'] > $t['sla_menit'] && $t['status'] !== 'resolved'));

ob_start();
?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-accent/15 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-ticket-perforated text-accent text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Total Tiket</p>
            <p class="text-h2 font-bold"><?= $total ?></p>
        </div>
    </div>
    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-danger/15 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-record-circle text-danger text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Open</p>
            <p class="text-h2 font-bold text-danger"><?= $open ?></p>
        </div>
    </div>
    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-warning/15 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-arrow-repeat text-warning text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Diproses</p>
            <p class="text-h2 font-bold text-warning"><?= $in_progress ?></p>
        </div>
    </div>
    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-danger/15 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-exclamation-triangle text-danger text-lg"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">SLA Breach</p>
            <p class="text-h2 font-bold text-danger"><?= $breach ?></p>
        </div>
    </div>
</div>

<div class="cs-card space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <i class="bi bi-ticket-perforated text-accent"></i>
            <h2 class="text-label font-semibold">Daftar Tiket</h2>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <select id="filter-status" class="cs-input text-caption py-1.5 w-auto" style="background-color:#0B376D;color:#F5F7FA;">
                <option value="">Semua Status</option>
                <option value="open">Open</option>
                <option value="in_progress">Diproses</option>
                <option value="resolved">Selesai</option>
            </select>
            <select id="filter-kategori" class="cs-input text-caption py-1.5 w-auto" style="background-color:#0B376D;color:#F5F7FA;">
                <option value="">Semua Kategori</option>
                <option value="facility">Facility</option>
                <option value="security">Security</option>
                <option value="cleaning">Cleaning</option>
                <option value="other">Lainnya</option>
            </select>
            <a href="tiket-buat.php" class="cs-btn bg-accent text-background hover:bg-accent/90 text-caption px-4 py-1.5">
                <i class="bi bi-plus-lg"></i> Tiket Baru
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-label" id="tabel-tiket">
            <thead>
                <tr class="border-b border-border text-text/50 text-caption">
                    <th class="text-left pb-3 pr-4 font-medium">ID</th>
                    <th class="text-left pb-3 pr-4 font-medium">Pelapor</th>
                    <th class="text-left pb-3 pr-4 font-medium">Lokasi</th>
                    <th class="text-left pb-3 pr-4 font-medium">Kategori</th>
                    <th class="text-left pb-3 pr-4 font-medium">Prioritas</th>
                    <th class="text-left pb-3 pr-4 font-medium">Status</th>
                    <th class="text-left pb-3 pr-4 font-medium">SLA</th>
                    <th class="text-left pb-3 pr-4 font-medium">Waktu</th>
                    <th class="text-left pb-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <?php foreach ($tiket_list as $t):
                    $breach_row = $t['umur_menit'] > $t['sla_menit'] && $t['status'] !== 'resolved';
                    $sisa = $t['sla_menit'] - $t['umur_menit'];
                    $kat  = $kategori_label[$t['kategori']] ?? $kategori_label['other'];
                    $stat = $status_label[$t['status']] ?? $status_label['open'];
                ?>
                <tr class="hover:bg-white/3 transition-colors tiket-row"
                    data-status="<?= $t['status'] ?>"
                    data-kategori="<?= $t['kategori'] ?>">
                    <td class="py-3 pr-4">
                        <span class="font-mono text-caption text-accent"><?= htmlspecialchars($t['id']) ?></span>
                        <?php if ($breach_row): ?>
                            <span class="ml-1 text-caption text-danger"><i class="bi bi-exclamation-circle-fill"></i></span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 pr-4 text-text/80"><?= htmlspecialchars($t['pelapor']) ?></td>
                    <td class="py-3 pr-4 text-text/60 text-caption max-w-[160px] truncate"><?= htmlspecialchars($t['lokasi']) ?></td>
                    <td class="py-3 pr-4">
                        <span class="px-2 py-0.5 rounded-full text-caption font-medium <?= $kat['class'] ?>">
                            <?= $kat['label'] ?>
                        </span>
                    </td>
                    <td class="py-3 pr-4">
                        <?php
                        $prio_class = match($t['priority']) {
                            'Critical' => 'bg-danger/15 text-danger',
                            'High'     => 'bg-warning/15 text-warning',
                            'Low'      => 'bg-text/10 text-text/50',
                            default    => 'bg-accent/10 text-accent',
                        };
                        ?>
                        <span class="px-2 py-0.5 rounded-full text-caption font-medium <?= $prio_class ?>"><?= htmlspecialchars($t['priority']) ?></span>
                    </td>
                    <td class="py-3 pr-4">
                        <span class="px-2 py-0.5 rounded-full text-caption font-medium <?= $stat['class'] ?>">
                            <?= $stat['label'] ?>
                        </span>
                    </td>
                    <td class="py-3 pr-4">
                        <?php if ($t['status'] === 'resolved'): ?>
                            <span class="text-caption text-success"><i class="bi bi-check2"></i> Selesai</span>
                        <?php elseif ($breach_row): ?>
                            <span class="text-caption text-danger font-semibold">+<?= abs($sisa) ?> mnt</span>
                        <?php else: ?>
                            <span class="text-caption text-text/50"><?= $sisa ?> mnt lagi</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 pr-4 text-caption text-text/40"><?= date('Y-m-d H:i', strtotime($t['created_at'])) ?></td>
                    <td class="py-3">
                        <a href="tiket-detail.php?id=<?= urlencode($t['id']) ?>" class="cs-btn bg-white/5 hover:bg-white/10 text-text/70 text-caption px-3 py-1">
                            <i class="bi bi-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($tiket_list)): ?>
            <p class="text-center text-caption text-text/30 py-10">Belum ada tiket.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function filterTiket() {
    const status   = document.getElementById('filter-status').value;
    const kategori = document.getElementById('filter-kategori').value;
    document.querySelectorAll('.tiket-row').forEach(row => {
        const matchStatus   = !status   || row.dataset.status   === status;
        const matchKategori = !kategori || row.dataset.kategori === kategori;
        row.style.display = (matchStatus && matchKategori) ? '' : 'none';
    });
}
document.getElementById('filter-status').addEventListener('change', filterTiket);
document.getElementById('filter-kategori').addEventListener('change', filterTiket);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layout.php';
?>