<?php
require_once __DIR__ . '/../../config/konek.php';
// require_once __DIR__ . '/../../auth/checkSession.php';

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

function formatNomorWA(?string $no): string
{
    $no = preg_replace('/\D/', '', (string) $no); // sisakan digit saja
    if ($no === '') {
        return '';
    }

    if (str_starts_with($no, '0')) {
        $no = '62' . substr($no, 1);
    } elseif (!str_starts_with($no, '62')) {
        $no = '62' . $no;
    }

    return $no;
}

/**
 * Susun teks pesan konfirmasi penyelesaian keluhan untuk satu tiket.
 */
function buatPesanKonfirmasi(array $t, array $kat): string
{
    return "Halo {$t['pelapor']},\n\n"
        . "Kami informasikan bahwa keluhan Anda dengan ID tiket *{$t['id']}* mengenai "
        . "*{$kat['label']}* di lokasi *{$t['lokasi']}* telah *selesai* kami tangani.\n\n"
        . "Terima kasih atas kesabaran dan kepercayaan Anda. Jika masih ada kendala terkait "
        . "hal ini, jangan ragu untuk menghubungi kami kembali.\n\n"
        . "Terima kasih";
}

$query = "
    SELECT t.*,
        wo.sla_target,
        CASE
            WHEN wo.sla_target IS NOT NULL
            AND NOW() > wo.sla_target
            AND t.status != 'resolved'
            THEN 1
            ELSE 0
        END AS is_breach,
        CASE
            WHEN wo.sla_target IS NOT NULL
            THEN TIMESTAMPDIFF(MINUTE, NOW(), wo.sla_target)
            ELSE NULL
        END AS sisa_menit
    FROM `05_tiket` t
    LEFT JOIN `03_damage_reports` dr ON dr.ticket_id = t.id
    LEFT JOIN `03_work_orders` wo ON wo.report_id = dr.report_id
    ORDER BY t.created_at DESC
";

$result = $conn->query($query);

$tiket_list = $result->fetch_all(MYSQLI_ASSOC);

$total       = count($tiket_list);
$open        = count(array_filter($tiket_list, fn($t) => $t['status'] === 'open'));
$in_progress = count(array_filter($tiket_list, fn($t) => $t['status'] === 'in_progress'));
$breach      = count(array_filter($tiket_list, fn($t) => $t['is_breach'] == 1));

ob_start();
?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-accent/15 flex items-center justify-center">
            <i class="bi bi-ticket-perforated text-accent"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Total Tiket</p>
            <p class="text-h2 font-bold"><?= $total ?></p>
        </div>
    </div>

    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-danger/15 flex items-center justify-center">
            <i class="bi bi-record-circle text-danger"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Open</p>
            <p class="text-h2 font-bold text-danger"><?= $open ?></p>
        </div>
    </div>

    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-warning/15 flex items-center justify-center">
            <i class="bi bi-arrow-repeat text-warning"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">Diproses</p>
            <p class="text-h2 font-bold text-warning"><?= $in_progress ?></p>
        </div>
    </div>

    <div class="cs-card flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-danger/15 flex items-center justify-center">
            <i class="bi bi-exclamation-triangle text-danger"></i>
        </div>
        <div>
            <p class="text-caption text-text/50">SLA Breach</p>
            <p class="text-h2 font-bold text-danger"><?= $breach ?></p>
        </div>
    </div>

</div>

<div class="cs-card space-y-4 mt-4">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 ml-2 sm:ml-3">
        <div class="flex items-center gap-3">
            <i class="bi bi-ticket-perforated text-accent"></i>
            <h2 class="text-label font-semibold">Daftar Tiket</h2>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <select id="filter-status" class="cs-input text-caption py-1.5 w-auto rounded-lg p-2" style="background-color:#0B376D;color:#F5F7FA">
                <option value="">Semua Status</option>
                <option value="open">Open</option>
                <option value="in_progress">Diproses</option>
                <option value="resolved">Selesai</option>
            </select>

            <select id="filter-kategori" class="cs-input text-caption py-1.5 w-auto rounded-lg p-2" style="background-color:#0B376D;color:#F5F7FA">
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
        <table class="w-full text-label">
            <thead>
                <tr class="border-b border-border text-text/50 text-caption">
                    <th class="text-left pb-3 pr-4">ID</th>
                    <th class="text-left pb-3 pr-4">Pelapor</th>
                    <th class="text-left pb-3 pr-4">Lokasi</th>
                    <th class="text-left pb-3 pr-4">Kategori</th>
                    <th class="text-left pb-3 pr-4">Prioritas</th>
                    <th class="text-left pb-3 pr-4">Status</th>
                    <th class="text-left pb-3 pr-4">SLA</th>
                    <th class="text-left pb-3 pr-4">Waktu</th>
                    <th class="text-left pb-3">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-border">
                <?php foreach ($tiket_list as $t):

                    $breach_row = $t['is_breach'] == 1;

                    $kat  = $kategori_label[$t['kategori']] ?? $kategori_label['other'];
                    $stat = $status_label[$t['status']] ?? $status_label['open'];

                    // WA Konfirmasi
                    $wa_link = null;

                    if (!empty($t['no_hp'])) {

                        $nomorWA = formatNomorWA($t['no_hp']);

                        if ($nomorWA !== '') {

                            $pesan = buatPesanKonfirmasi($t, $kat);

                            $wa_link = "https://wa.me/"
                                . $nomorWA
                                . "?text="
                                . urlencode($pesan);
                        }
                    }

                    $prio_class = match ($t['priority']) {
                        'Critical' => 'bg-danger/15 text-danger',
                        'High'     => 'bg-warning/15 text-warning',
                        'Low'      => 'bg-text/10 text-text/50',
                        default    => 'bg-accent/10 text-accent',
                    };

                ?>
                    <tr class="tiket-row hover:bg-white/5 transition"
                        data-status="<?= $t['status'] ?>"
                        data-kategori="<?= $t['kategori'] ?>">

                        <td class="py-3 pr-4 font-mono text-caption text-accent">
                            <?= htmlspecialchars($t['id']) ?>

                            <?php if ($breach_row): ?>
                                <i class="bi bi-exclamation-circle-fill text-danger ml-1"></i>
                            <?php endif; ?>
                        </td>

                        <td class="py-3 pr-4 text-text/80">
                            <?= htmlspecialchars($t['pelapor']) ?>
                        </td>

                        <td class="py-3 pr-4 text-text/60 text-caption max-w-[160px] truncate">
                            <?= htmlspecialchars($t['lokasi']) ?>
                        </td>

                        <td class="py-3 pr-4">
                            <span class="px-2 py-0.5 rounded-full text-caption <?= $kat['class'] ?>">
                                <?= $kat['label'] ?>
                            </span>
                        </td>

                        <td class="py-3 pr-4">
                            <span class="px-2 py-0.5 rounded-full text-caption <?= $prio_class ?>">
                                <?= htmlspecialchars($t['priority']) ?>
                            </span>
                        </td>

                        <td class="py-3 pr-4">
                            <span class="px-2 py-0.5 rounded-full text-caption <?= $stat['class'] ?>">
                                <?= $stat['label'] ?>
                            </span>
                        </td>

                        <td class="py-3 pr-4 text-caption">
                            <?php if ($t['status'] === 'resolved'): ?>

                                <span class="text-success">
                                    Selesai
                                </span>

                            <?php elseif ($t['sla_target'] === null): ?>

                                <span class="text-text/30">—</span>

                            <?php elseif ($breach_row): ?>

                                <span class="text-danger">
                                    +<?= abs((int)$t['sisa_menit']) ?> mnt
                                </span>

                            <?php else: ?>

                                <span class="text-text/50">
                                    <?= (int)$t['sisa_menit'] ?> mnt lagi
                                </span>

                            <?php endif; ?>
                        </td>

                        <td class="py-3 pr-4 text-caption text-text/40">
                            <?= date('Y-m-d H:i', strtotime($t['created_at'])) ?>
                        </td>

                        <td class="py-3 flex items-center gap-2">

                            <a href="tiket-detail.php?id=<?= urlencode($t['id']) ?>"
                                class="cs-btn bg-white/5 hover:bg-white/10 text-caption px-3 py-1 rounded-lg p-2">

                                <i class="bi bi-eye"></i>
                                Detail
                            </a>

                            <?php if ($t['status'] === 'resolved'): ?>

                                <?php if ($wa_link): ?>

                                    <a href="<?= htmlspecialchars($wa_link) ?>"
                                        target="_blank"
                                        rel="noopener"

                                        class="cs-btn bg-[#25D366] hover:bg-[#1fb958]
                          text-white text-caption px-3 py-1 rounded-lg p-2">

                                        <i class="bi bi-whatsapp"></i>
                                        WA
                                    </a>

                                <?php else: ?>

                                    <span class="cs-btn bg-white/5 text-text/30 px-3 py-1 rounded-lg p-2">

                                        <i class="bi bi-whatsapp"></i>
                                        Tanpa nomor

                                    </span>

                                <?php endif; ?>

                            <?php endif; ?>

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

<?php
$content = ob_get_clean();
$content .= <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
function filterTiket() {
    const status   = document.getElementById('filter-status').value;
    const kategori = document.getElementById('filter-kategori').value;
    document.querySelectorAll('.tiket-row').forEach(row => {
        const matchStatus   = !status || row.dataset.status === status;
        const matchKategori = !kategori || row.dataset.kategori === kategori;
        row.style.display = (matchStatus && matchKategori) ? '' : 'none';
    });
}
document.getElementById('filter-status').addEventListener('change', filterTiket);
document.getElementById('filter-kategori').addEventListener('change', filterTiket);
</script>
<style type="text/tailwindcss">
.cs-card { @apply bg-white/5 border border-white/10 rounded-xl p-4 shadow-lg; }
.cs-input { @apply bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-text; }
.cs-btn   { @apply inline-flex items-center justify-center gap-2 rounded-lg font-medium transition; }
</style>
HTML;

require_once "../../includes/navbarM05.php";
?>