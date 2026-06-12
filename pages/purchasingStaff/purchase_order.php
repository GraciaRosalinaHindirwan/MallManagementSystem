<?php
session_start();
$_SESSION['role'] = 'Purchasing Staff';
$_SESSION['nama'] = 'Eva (Purchasing)';

// Panggil file koneksi terpusat
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// =============================================
// PBI-M06-03-02: BUAT PURCHASE ORDER BARU
// =============================================
if (isset($_POST['tambah_po'])) {
    $no_po        = "PO-" . date('Ymd') . "-" . rand(100, 999);
    $id_pr        = (int) $_POST['id_pr'];
    $nama_vendor  = $conn->real_escape_string($_POST['nama_vendor']);
    $nama_item    = $conn->real_escape_string($_POST['nama_item']);
    $jumlah       = (int) $_POST['jumlah'];
    $satuan       = $conn->real_escape_string($_POST['satuan']);
    $harga_satuan = (int) $_POST['harga_satuan'];
    $total_harga  = $jumlah * $harga_satuan;
    $tgl_kirim    = $conn->real_escape_string($_POST['tgl_pengiriman']);
    $dibuat_oleh  = $_SESSION['nama'];

    $sql = "INSERT INTO purchase_orders 
                (no_po, id_pr, nama_vendor, nama_item, jumlah, satuan, harga_satuan, total_harga, tgl_pengiriman, dibuat_oleh, status, tgl_dibuat)
            VALUES 
                ('$no_po', '$id_pr', '$nama_vendor', '$nama_item', '$jumlah', '$satuan', '$harga_satuan', '$total_harga', '$tgl_kirim', '$dibuat_oleh', 'Menunggu Approval', NOW())";

    if ($conn->query($sql)) {
        // Update status PR menjadi "Diproses"
        $conn->query("UPDATE purchase_requests SET status='Diproses' WHERE id_pr=$id_pr");
        echo "<script>alert('Purchase Order $no_po berhasil dibuat!'); window.location='purchase_order.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan PO: " . $conn->error . "');</script>";
    }
}

// Hapus PO (hanya yang masih Menunggu Approval)
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    // Kembalikan status PR ke Disetujui
    $po = $conn->query("SELECT id_pr FROM purchase_orders WHERE id_po=$id_hapus")->fetch_assoc();
    if ($po) {
        $conn->query("UPDATE purchase_requests SET status='Disetujui' WHERE id_pr=" . $po['id_pr']);
    }
    $conn->query("DELETE FROM purchase_orders WHERE id_po=$id_hapus AND status='Menunggu Approval'");
    echo "<script>window.location='purchase_order.php';</script>";
}

// Ambil data PR yang sudah Disetujui (bisa dijadikan PO)
$pr_disetujui = $conn->query("SELECT * FROM purchase_requests WHERE status='Disetujui' ORDER BY id_pr DESC");

// Ambil semua data PO
$data_po = $conn->query("SELECT * FROM purchase_orders ORDER BY id_po DESC");

// Statistik
$total_po    = $conn->query("SELECT COUNT(*) as jml FROM purchase_orders")->fetch_assoc()['jml'] ?? 0;
$menunggu    = $conn->query("SELECT COUNT(*) as jml FROM purchase_orders WHERE status='Menunggu Approval'")->fetch_assoc()['jml'] ?? 0;
$disetujui   = $conn->query("SELECT COUNT(*) as jml FROM purchase_orders WHERE status='Disetujui'")->fetch_assoc()['jml'] ?? 0;
$selesai     = $conn->query("SELECT COUNT(*) as jml FROM purchase_orders WHERE status='Selesai'")->fetch_assoc()['jml'] ?? 0;
?>

<div class="content-container">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 32px; font-weight: 700; margin: 0;">
            Purchase Order <span style="font-size: 16px; color: #64748b;">(PBI-M06-03-02)</span>
        </h1>
        <p style="color: #cbd5e1; margin-top: 5px;">Proses pembuatan PO ke vendor terpilih berdasarkan Purchase Request yang telah disetujui.</p>
    </div>

    <!-- STATISTIK -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #00cfd5;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">TOTAL PO</p>
            <h2 style="color:#fff; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $total_po ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #FFB62A;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">MENUNGGU APPROVAL</p>
            <h2 style="color:#FFB62A; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $menunggu ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #22c55e;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">DISETUJUI</p>
            <h2 style="color:#22c55e; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $disetujui ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #00cfd5;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">SELESAI</p>
            <h2 style="color:#00cfd5; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $selesai ?></h2>
        </div>
    </div>

    <!-- FORM BUAT PO BARU -->
    <div style="background:#032b5c; padding:25px; border-radius:12px; margin-bottom:30px; border:1px solid rgba(255,255,255,0.06);">
        <h4 style="color:#FFB62A; margin-bottom:20px; font-size:16px;">
            <i class="fa-solid fa-cart-shopping"></i> Buat Purchase Order Baru
        </h4>

        <?php if ($pr_disetujui && $pr_disetujui->num_rows > 0): ?>
        <form method="POST">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Pilih Purchase Request</label>
                    <select name="id_pr" id="pilih_pr" onchange="isiOtomatis(this)"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                        <option value="">-- Pilih PR yang Disetujui --</option>
                        <?php while ($pr = $pr_disetujui->fetch_assoc()): ?>
                            <option value="<?= $pr['id_pr'] ?>"
                                data-item="<?= htmlspecialchars($pr['nama_item']) ?>"
                                data-jumlah="<?= $pr['jumlah'] ?>"
                                data-satuan="<?= $pr['satuan'] ?>"
                                data-estimasi="<?= $pr['estimasi_harga'] ?>">
                                <?= $pr['no_pr'] ?> — <?= htmlspecialchars($pr['nama_item']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Nama Vendor</label>
                    <input type="text" name="nama_vendor" required placeholder="contoh: PT. Sumber Makmur..."
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Nama Item</label>
                    <input type="text" name="nama_item" id="nama_item" required placeholder="Terisi otomatis dari PR"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Jumlah</label>
                    <input type="number" name="jumlah" id="jumlah" required min="1"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Satuan</label>
                    <select name="satuan" id="satuan"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                        <option value="Unit">Unit</option>
                        <option value="Pcs">Pcs</option>
                        <option value="Kg">Kg</option>
                        <option value="Liter">Liter</option>
                        <option value="Meter">Meter</option>
                        <option value="Paket">Paket</option>
                        <option value="Set">Set</option>
                    </select>
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Harga Satuan (Rp)</label>
                    <input type="number" name="harga_satuan" id="harga_satuan" required min="0"
                        placeholder="Harga per satuan dari vendor"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

                <div>
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Tanggal Pengiriman</label>
                    <input type="date" name="tgl_pengiriman" required
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:14px;">
                </div>

                <div style="display:flex; align-items:flex-end;">
                    <div style="background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:10px 14px; width:100%; margin-top:6px;">
                        <p style="margin:0; font-size:12px; color:#64748b;">Total Harga (otomatis)</p>
                        <p id="preview_total" style="margin:0; font-size:16px; font-weight:700; color:#00cfd5;">Rp 0</p>
                    </div>
                </div>

            </div>

            <div style="margin-top:20px;">
                <button type="submit" name="tambah_po"
                    style="background:#00cfd5; color:#021F42; font-weight:700; padding:10px 24px; border:none; border-radius:8px; font-size:14px; cursor:pointer;">
                    <i class="fa-solid fa-paper-plane"></i> Buat Purchase Order
                </button>
            </div>
        </form>

        <?php else: ?>
            <div style="background:rgba(255,183,42,0.1); border:1px solid rgba(255,183,42,0.3); padding:15px 20px; border-radius:8px; color:#FFB62A;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Belum ada Purchase Request yang berstatus <strong>Disetujui</strong>. Minta approval PR terlebih dahulu.
            </div>
        <?php endif; ?>
    </div>

    <!-- TABEL DAFTAR PO -->
    <h4 style="color:#fff; margin-bottom:15px; font-size:16px; font-weight:600;">
        <i class="fa-solid fa-list"></i> Daftar Purchase Order
    </h4>

    <table class="table-custom">
        <thead>
            <tr>
                <th>No. PO</th>
                <th>No. PR</th>
                <th>Vendor</th>
                <th>Nama Item</th>
                <th>Jumlah</th>
                <th>Total Harga</th>
                <th>Tgl Pengiriman</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($data_po && $data_po->num_rows > 0): ?>
                <?php while ($row = $data_po->fetch_assoc()): ?>
                <tr>
                    <td><strong style="color:#00cfd5;"><?= $row['no_po'] ?></strong></td>
                    <td style="color:#FFB62A; font-size:13px;">
                        <?php
                            $pr_info = $conn->query("SELECT no_pr FROM purchase_requests WHERE id_pr=" . $row['id_pr']);
                            echo $pr_info ? $pr_info->fetch_assoc()['no_pr'] ?? '-' : '-';
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['nama_vendor']) ?></td>
                    <td><?= htmlspecialchars($row['nama_item']) ?></td>
                    <td><?= $row['jumlah'] ?> <?= $row['satuan'] ?></td>
                    <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                    <td><?= date('d M Y', strtotime($row['tgl_pengiriman'])) ?></td>
                    <td>
                        <?php
                            $status = $row['status'];
                            $warna  = match($status) {
                                'Disetujui'         => '#22c55e',
                                'Ditolak'           => '#ef4444',
                                'Selesai'           => '#00cfd5',
                                'Menunggu Approval' => '#FFB62A',
                                default             => '#94a3b8',
                            };
                        ?>
                        <span style="background:<?= $warna ?>22; color:<?= $warna ?>; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; border:1px solid <?= $warna ?>44;">
                            <?= $status ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Menunggu Approval'): ?>
                            <a href="?hapus=<?= $row['id_po'] ?>"
                               onclick="return confirm('Hapus PO ini?')"
                               style="background:#ef4444; color:#fff; padding:5px 12px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">
                               <i class="fa-solid fa-trash"></i> Hapus
                            </a>
                        <?php else: ?>
                            <span style="color:#64748b; font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:30px; color:#64748b;">
                        Belum ada Purchase Order. Buat PO dari PR yang sudah disetujui!
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Isi otomatis field dari data PR yang dipilih
function isiOtomatis(select) {
    const opt = select.options[select.selectedIndex];
    document.getElementById('nama_item').value  = opt.dataset.item    || '';
    document.getElementById('jumlah').value     = opt.dataset.jumlah  || '';

    // Set satuan dropdown
    const satuan = opt.dataset.satuan || 'Unit';
    const satuanEl = document.getElementById('satuan');
    for (let i = 0; i < satuanEl.options.length; i++) {
        if (satuanEl.options[i].value === satuan) {
            satuanEl.selectedIndex = i;
            break;
        }
    }

    document.getElementById('harga_satuan').value = opt.dataset.estimasi || '';
    hitungTotal();
}

// Hitung total harga otomatis
function hitungTotal() {
    const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
    const harga  = parseInt(document.getElementById('harga_satuan').value) || 0;
    const total  = jumlah * harga;
    document.getElementById('preview_total').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

document.getElementById('jumlah')?.addEventListener('input', hitungTotal);
document.getElementById('harga_satuan')?.addEventListener('input', hitungTotal);
</script>

<?php require_once '../../includes/footer.php'; ?>
