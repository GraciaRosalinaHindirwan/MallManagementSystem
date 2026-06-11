<?php
session_start();
require_once '../../config/db_lostnfound.php';

$alertMsg  = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'kembalikan') {
        $id = (int) $_POST['id'];
        $stmt = $conn->prepare("UPDATE lost_reports SET status = 'Dikembalikan' WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $alertMsg  = 'Status berhasil diubah menjadi Dikembalikan.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Gagal mengubah status.';
            $alertType = 'danger';
        }
        $stmt->close();
    } else {
        $item_description = htmlspecialchars(strip_tags(trim($_POST['item_description'] ?? '')));
        $contact_number   = htmlspecialchars(strip_tags(trim($_POST['contact_number']   ?? '')));

        $stmt = $conn->prepare("INSERT INTO lost_reports (item_description, contact_number) VALUES (?, ?)");
        $stmt->bind_param('ss', $item_description, $contact_number);

        if ($stmt->execute()) {
            $alertMsg  = 'Laporan kehilangan berhasil disimpan.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Gagal menyimpan data.';
            $alertType = 'danger';
        }
        $stmt->close();
    }
}

$result  = $conn->query("SELECT * FROM lost_reports ORDER BY reported_at DESC");
$reports = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

ob_start();
?>

<?php if ($alertMsg): ?>
<div class="flex items-center gap-3 px-4 py-3 rounded-md border-l-4 <?= $alertType === 'success' ? 'bg-success/10 border-success text-success' : 'bg-danger/10 border-danger text-danger' ?>">
  <i class="bi bi-<?= $alertType === 'success' ? 'check-circle' : 'exclamation-circle' ?> text-lg"></i>
  <p class="text-label"><?= $alertMsg ?></p>
</div>
<?php endif; ?>

<div class="cs-card">
  <h2 class="text-body font-semibold mb-1">Input Laporan Kehilangan</h2>
  <p class="text-caption text-text/50 mb-5">Catat laporan kehilangan barang dari pengunjung.</p>

  <form method="POST" class="space-y-5">
    <div>
      <label class="block text-label font-medium mb-1.5">Deskripsi Barang Hilang <span class="text-danger">*</span></label>
      <textarea name="item_description" rows="4" required class="cs-input resize-none" placeholder="Contoh: Tas ransel warna biru, berisi laptop..."></textarea>
    </div>
    <div>
      <label class="block text-label font-medium mb-1.5">Nomor Kontak Pelapor <span class="text-danger">*</span></label>
      <input type="text" name="contact_number" required class="cs-input" placeholder="Contoh: 08123456789" />
    </div>
    <div>
      <button type="submit" class="cs-btn bg-accent text-background hover:brightness-110">
        <i class="bi bi-plus-circle"></i> Simpan
      </button>
    </div>
  </form>
</div>

<div class="cs-card">
  <h2 class="text-body font-semibold mb-4">Daftar Laporan Kehilangan</h2>
  <div class="overflow-x-auto">
    <table class="w-full text-label border-collapse">
      <thead>
        <tr class="border-b border-border">
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">No</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Deskripsi Barang</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Kontak</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Status</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Tanggal</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $i => $report): ?>
        <tr class="border-b border-border/50 hover:bg-white/3">
          <td class="py-3 px-3"><?= $i + 1 ?></td>
          <td class="py-3 px-3 max-w-xs truncate"><?= $report['item_description'] ?></td>
          <td class="py-3 px-3"><?= $report['contact_number'] ?></td>
          <td class="py-3 px-3">
            <span class="px-2 py-1 rounded-full text-caption font-semibold <?= $report['status'] === 'Dikembalikan' ? 'bg-success/20 text-success' : 'bg-warning/20 text-warning' ?>">
              <?= $report['status'] ?>
            </span>
          </td>
          <td class="py-3 px-3 text-text/50"><?= date('d/m/Y', strtotime($report['reported_at'])) ?></td>
          <td class="py-3 px-3">
            <?php if ($report['status'] !== 'Dikembalikan'): ?>
            <form method="POST">
              <input type="hidden" name="id" value="<?= $report['id'] ?>">
              <button type="submit" name="action" value="kembalikan" class="cs-btn bg-success/20 text-success border border-success/30 hover:bg-success/30 text-caption !px-3 !py-1">
                <i class="bi bi-check-circle"></i> Dikembalikan
              </button>
            </form>
            <?php else: ?>
            <span class="text-caption text-text/30">Selesai</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content     = ob_get_clean();
$pageTitle   = 'Laporan Kehilangan — Mall ERP CS';
$currentMenu = 'barang-hilang';
require_once '../../includes/layout_cs.php';
?>