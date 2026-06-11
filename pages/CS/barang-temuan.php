<?php
session_start();
require_once '../../config/db_lostnfound.php';

$alertMsg  = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'kembalikan') {
        $id = (int) $_POST['id'];
        $stmt = $conn->prepare("UPDATE found_items SET status = 'Dikembalikan' WHERE id = ?");
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
        $location_found = htmlspecialchars(strip_tags(trim($_POST['location_found'] ?? '')));
        $description    = htmlspecialchars(strip_tags(trim($_POST['description']    ?? '')));
        $photo          = null;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $uploadDir = '../../uploads/found_items/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $photo = time() . '_' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photo);
        }

        $stmt = $conn->prepare("INSERT INTO found_items (photo, location_found, description) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $photo, $location_found, $description);

        if ($stmt->execute()) {
            $alertMsg  = 'Data barang temuan berhasil disimpan.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Gagal menyimpan data.';
            $alertType = 'danger';
        }
        $stmt->close();
    }
}

$result = $conn->query("SELECT * FROM found_items ORDER BY created_at DESC");
$items  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

ob_start();
?>

<?php if ($alertMsg): ?>
<div class="flex items-center gap-3 px-4 py-3 rounded-md border-l-4 <?= $alertType === 'success' ? 'bg-success/10 border-success text-success' : 'bg-danger/10 border-danger text-danger' ?>">
  <i class="bi bi-<?= $alertType === 'success' ? 'check-circle' : 'exclamation-circle' ?> text-lg"></i>
  <p class="text-label"><?= $alertMsg ?></p>
</div>
<?php endif; ?>

<div class="cs-card">
  <h2 class="text-body font-semibold mb-1">Input Barang Temuan</h2>
  <p class="text-caption text-text/50 mb-5">Catat barang temuan yang diserahkan ke meja CS.</p>

  <form method="POST" enctype="multipart/form-data" class="space-y-5">
    <div>
      <label class="block text-label font-medium mb-1.5">Lokasi Ditemukan <span class="text-danger">*</span></label>
      <input type="text" name="location_found" required class="cs-input" placeholder="Contoh: Lantai 2, dekat eskalator" />
    </div>
    <div>
      <label class="block text-label font-medium mb-1.5">Ciri-ciri Barang <span class="text-danger">*</span></label>
      <textarea name="description" rows="4" required class="cs-input resize-none" placeholder="Contoh: Dompet warna hitam, berisi KTP atas nama..."></textarea>
    </div>
    <div>
      <label class="block text-label font-medium mb-1.5">Foto Barang</label>
      <input type="file" name="photo" accept=".jpg,.jpeg,.png" class="cs-input" />
    </div>
    <div>
      <button type="submit" class="cs-btn bg-accent text-background hover:brightness-110">
        <i class="bi bi-plus-circle"></i> Simpan
      </button>
    </div>
  </form>
</div>

<div class="cs-card">
  <h2 class="text-body font-semibold mb-4">Daftar Barang Temuan</h2>
  <div class="overflow-x-auto">
    <table class="w-full text-label border-collapse">
      <thead>
        <tr class="border-b border-border">
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">No</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Foto</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Lokasi</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Ciri-ciri</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Status</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Tanggal</th>
          <th class="text-left text-caption font-semibold text-text/40 uppercase py-2 px-3">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr class="border-b border-border/50 hover:bg-white/3">
          <td class="py-3 px-3"><?= $i + 1 ?></td>
          <td class="py-3 px-3">
            <?php if ($item['photo']): ?>
              <img src="../../uploads/found_items/<?= $item['photo'] ?>" class="w-12 h-12 object-cover rounded-md" />
            <?php else: ?>
              <span class="text-text/30">—</span>
            <?php endif; ?>
          </td>
          <td class="py-3 px-3"><?= $item['location_found'] ?></td>
          <td class="py-3 px-3 max-w-xs truncate"><?= $item['description'] ?></td>
          <td class="py-3 px-3">
            <span class="px-2 py-1 rounded-full text-caption font-semibold <?= $item['status'] === 'Dikembalikan' ? 'bg-success/20 text-success' : 'bg-accent/20 text-accent' ?>">
              <?= $item['status'] ?>
            </span>
          </td>
          <td class="py-3 px-3 text-text/50"><?= date('d/m/Y', strtotime($item['created_at'])) ?></td>
          <td class="py-3 px-3">
            <?php if ($item['status'] !== 'Dikembalikan'): ?>
            <form method="POST">
              <input type="hidden" name="id" value="<?= $item['id'] ?>">
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
$pageTitle   = 'Barang Temuan — Mall ERP CS';
$currentMenu = 'barang-temuan';
require_once '../../includes/layout_cs.php';
?>