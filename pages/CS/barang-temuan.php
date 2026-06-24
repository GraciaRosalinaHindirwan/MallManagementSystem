```php
<?php
session_start();
require_once '../../config/konek.php';

$alertMsg  = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'kembalikan') {
        $id = (int) $_POST['id'];
        $stmt = $conn->prepare("UPDATE 05_found_items SET status = 'Dikembalikan' WHERE id = ?");
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
            $uploadDir = '../../storage/uploads/found_items/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $photo = time() . '_' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photo);
        }

        $stmt = $conn->prepare("INSERT INTO 05_found_items (photo, location_found, description) VALUES (?, ?, ?)");
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

$result = $conn->query("SELECT * FROM 05_found_items ORDER BY created_at DESC");
$items  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$page_title = 'Barang Temuan';

ob_start();
?>

<?php if ($alertMsg): ?>
<div class="card" style="border-left: 4px solid <?= $alertType === 'success' ? '#22C55E' : '#EF4444' ?>; margin-bottom: 16px;">
    <p style="color: <?= $alertType === 'success' ? '#22C55E' : '#EF4444' ?>; margin: 0;">
        <?= $alertMsg ?>
    </p>
</div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">Input Barang Temuan</h2>
    <p style="color: rgba(245,247,250,0.5); font-size: 13px; margin-bottom: 20px;">Catat barang temuan yang diserahkan ke meja CS.</p>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Lokasi Ditemukan <span style="color:#EF4444">*</span></label>
            <input type="text" name="location_found" required placeholder="Contoh: Lantai 2, dekat eskalator" />
        </div>
        <div class="form-group">
            <label>Ciri-ciri Barang <span style="color:#EF4444">*</span></label>
            <textarea name="description" rows="4" required placeholder="Contoh: Dompet warna hitam, berisi KTP atas nama..."></textarea>
        </div>
        <div class="form-group">
            <label>Foto Barang</label>
            <input type="file" name="photo" accept=".jpg,.jpeg,.png" />
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-plus-circle"></i> Simpan
        </button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">Daftar Barang Temuan</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Foto</th>
                    <th>Lokasi</th>
                    <th>Ciri-ciri</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <?php if ($item['photo']): ?>
                            <img src="../../storage/uploads/found_items/<?= $item['photo'] ?>" style="width:48px; height:48px; object-fit:cover; border-radius:6px;" />
                        <?php else: ?>
                            <span style="color:rgba(245,247,250,0.3)">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $item['location_found'] ?></td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= $item['description'] ?></td>
                    <td>
                        <span class="badge <?= $item['status'] === 'Dikembalikan' ? 'badge-success' : 'badge-warning' ?>">
                            <?= $item['status'] ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($item['created_at'])) ?></td>
                    <td>
                        <?php if ($item['status'] !== 'Dikembalikan'): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" name="action" value="kembalikan" class="btn" style="background:rgba(34,197,94,0.2); color:#22C55E; border:1px solid rgba(34,197,94,0.3); padding:4px 12px; font-size:12px;">
                                <i class="fa-solid fa-check-circle"></i> Dikembalikan
                            </button>
                        </form>
                        <?php else: ?>
                        <span style="color:rgba(245,247,250,0.3); font-size:12px;">Selesai</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:rgba(245,247,250,0.3); padding:24px;">Belum ada data barang temuan.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../../includes/navbarM05.php';
?>