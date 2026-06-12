<?php
session_start();
$_SESSION['role'] = 'Finance Staff';
$_SESSION['nama'] = 'Eva (Finance)';

// Panggil file koneksi terpusat
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// =============================================
// PBI-M06-03-03: 3-WAY MATCHING
// =============================================

// Catat penerimaan barang
if (isset($_POST['catat_penerimaan'])) {
    $id_po          = (int) $_POST['id_po'];
    $jumlah_terima  = (int) $_POST['jumlah_terima'];
    $kondisi        = $conn->real_escape_string($_POST['kondisi']);
    $tgl_terima     = $conn->real_escape_string($_POST['tgl_terima']);
    $catatan        = $conn->real_escape_string($_POST['catatan']);
    $dicatat_oleh   = $_SESSION['nama'];

    $no_terima = "GR-" . date('Ymd') . "-" . rand(100, 999);

    $sql = "INSERT INTO penerimaan_barang 
                (no_terima, id_po, jumlah_terima, kondisi, tgl_terima, catatan, dicatat_oleh, status)
            VALUES 
                ('$no_terima', '$id_po', '$jumlah_terima', '$kondisi', '$tgl_terima', '$catatan', '$dicatat_oleh', 'Diterima')";

    if ($conn->query($sql)) {
        $conn->query("UPDATE purchase_orders SET status='Selesai' WHERE id_po=$id_po");
        echo "<script>alert('Penerimaan barang $no_terima berhasil dicatat!'); window.location='three_way_matching.php';</script>";
    } else {
        echo "<script>alert('Gagal mencatat penerimaan: " . $conn->error . "');</script>";
    }
}

// Input invoice vendor
if (isset($_POST['input_invoice'])) {
    $id_po          = (int) $_POST['id_po_inv'];
    $no_invoice_vendor = $conn->real_escape_string($_POST['no_invoice_vendor']);
    $jumlah_tagihan = (int) $_POST['jumlah_tagihan'];
    $tgl_invoice    = $conn->real_escape_string($_POST['tgl_invoice']);
    $dicatat_oleh   = $_SESSION['nama'];

    $sql = "INSERT INTO invoice_vendor 
                (id_po, no_invoice_vendor, jumlah_tagihan, tgl_invoice, dicatat_oleh, status)
            VALUES 
                ('$id_po', '$no_invoice_vendor', '$jumlah_tagihan', '$tgl_invoice', '$dicatat_oleh', 'Menunggu Matching')";

    if ($conn->query($sql)) {
        echo "<script>alert('Invoice vendor berhasil diinput!'); window.location='three_way_matching.php';</script>";
    } else {
        echo "<script>alert('Gagal input invoice: " . $conn->error . "');</script>";
    }
}

// Proses 3-way matching & setujui pembayaran
if (isset($_GET['matching'])) {
    $id_inv = (int) $_GET['matching'];

    // Ambil data invoice
    $inv = $conn->query("SELECT * FROM invoice_vendor WHERE id_invoice_vendor=$id_inv")->fetch_assoc();

    if ($inv) {
        $id_po = $inv['id_po'];

        // Ambil data PO
        $po = $conn->query("SELECT * FROM purchase_orders WHERE id_po=$id_po")->fetch_assoc();

        // Ambil data penerimaan
        $terima = $conn->query("SELECT * FROM penerimaan_barang WHERE id_po=$id_po ORDER BY id_terima DESC LIMIT 1")->fetch_assoc();

        if ($po && $terima) {
            $total_po     = $po['total_harga'];
            $jumlah_tagih = $inv['jumlah_tagihan'];
            $jumlah_terima = $terima['jumlah_terima'];
            $jumlah_po    = $po['jumlah'];

            // Cek matching: invoice = PO dan jumlah terima = jumlah PO
            if ($jumlah_tagih == $total_po && $jumlah_terima == $jumlah_po) {
                $hasil = 'Matched';
                $conn->query("UPDATE invoice_vendor SET status='Matched - Siap Bayar' WHERE id_invoice_vendor=$id_inv");
            } else {
                $hasil = 'Tidak Match';
                $conn->query("UPDATE invoice_vendor SET status='Tidak Match - Perlu Review' WHERE id_invoice_vendor=$id_inv");
            }
            echo "<script>alert('Hasil 3-Way Matching: $hasil!'); window.location='three_way_matching.php';</script>";
        }
    }
}

// Ambil data PO yang sudah Disetujui (siap penerimaan)
$po_disetujui = $conn->query("SELECT * FROM purchase_orders WHERE status='Disetujui' ORDER BY id_po DESC");

// Ambil data PO yang sudah Selesai (siap input invoice)
$po_selesai = $conn->query("SELECT * FROM purchase_orders WHERE status='Selesai' ORDER BY id_po DESC");

// Ambil data matching
$data_matching = $conn->query("
    SELECT iv.*, po.no_po, po.nama_vendor, po.nama_item, po.total_harga as total_po,
           po.jumlah as jumlah_po, pb.jumlah_terima
    FROM invoice_vendor iv
    LEFT JOIN purchase_orders po ON iv.id_po = po.id_po
    LEFT JOIN penerimaan_barang pb ON pb.id_po = iv.id_po
    ORDER BY iv.id_invoice_vendor DESC
");

// Statistik
$total_match   = $conn->query("SELECT COUNT(*) as jml FROM invoice_vendor")->fetch_assoc()['jml'] ?? 0;
$matched       = $conn->query("SELECT COUNT(*) as jml FROM invoice_vendor WHERE status LIKE 'Matched%'")->fetch_assoc()['jml'] ?? 0;
$tidak_match   = $conn->query("SELECT COUNT(*) as jml FROM invoice_vendor WHERE status LIKE 'Tidak Match%'")->fetch_assoc()['jml'] ?? 0;
$menunggu_match = $conn->query("SELECT COUNT(*) as jml FROM invoice_vendor WHERE status='Menunggu Matching'")->fetch_assoc()['jml'] ?? 0;
?>

<div class="content-container">
    <div class="mb-4">
        <h1 style="color: var(--text-accent); font-size: 32px; font-weight: 700; margin: 0;">
            3-Way Matching <span style="font-size: 16px; color: #64748b;">(PBI-M06-03-03)</span>
        </h1>
        <p style="color: #cbd5e1; margin-top: 5px;">Verifikasi PO, penerimaan barang, dan invoice vendor sebelum pembayaran diproses.</p>
    </div>

    <!-- ALUR 3-WAY MATCHING -->
    <div style="background:#032b5c; padding:20px; border-radius:12px; margin-bottom:25px; border:1px solid rgba(255,255,255,0.06);">
        <p style="color:#64748b; font-size:12px; font-weight:700; letter-spacing:1px; margin:0 0 12px 0;">ALUR PROSES</p>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div style="background:#021F42; padding:10px 18px; border-radius:8px; border:1px solid #00cfd5; color:#00cfd5; font-size:13px; font-weight:600;">
                <i class="fa-solid fa-cart-shopping"></i> 1. Purchase Order
            </div>
            <i class="fa-solid fa-arrow-right" style="color:#64748b;"></i>
            <div style="background:#021F42; padding:10px 18px; border-radius:8px; border:1px solid #FFB62A; color:#FFB62A; font-size:13px; font-weight:600;">
                <i class="fa-solid fa-box"></i> 2. Penerimaan Barang
            </div>
            <i class="fa-solid fa-arrow-right" style="color:#64748b;"></i>
            <div style="background:#021F42; padding:10px 18px; border-radius:8px; border:1px solid #a855f7; color:#a855f7; font-size:13px; font-weight:600;">
                <i class="fa-solid fa-file-invoice-dollar"></i> 3. Invoice Vendor
            </div>
            <i class="fa-solid fa-arrow-right" style="color:#64748b;"></i>
            <div style="background:#021F42; padding:10px 18px; border-radius:8px; border:1px solid #22c55e; color:#22c55e; font-size:13px; font-weight:600;">
                <i class="fa-solid fa-check-double"></i> 4. Matching → Bayar
            </div>
        </div>
    </div>

    <!-- STATISTIK -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #00cfd5;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">TOTAL INVOICE</p>
            <h2 style="color:#fff; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $total_match ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #FFB62A;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">MENUNGGU MATCHING</p>
            <h2 style="color:#FFB62A; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $menunggu_match ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #22c55e;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">MATCHED</p>
            <h2 style="color:#22c55e; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $matched ?></h2>
        </div>
        <div style="background:#032b5c; padding:20px; border-radius:12px; border-left:5px solid #ef4444;">
            <p style="color:#a0aec0; font-size:11px; letter-spacing:1px; margin:0;">TIDAK MATCH</p>
            <h2 style="color:#ef4444; font-size:26px; font-weight:700; margin:8px 0 0 0;"><?= $tidak_match ?></h2>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">

        <!-- FORM CATAT PENERIMAAN BARANG -->
        <div style="background:#032b5c; padding:25px; border-radius:12px; border:1px solid rgba(255,255,255,0.06);">
            <h4 style="color:#FFB62A; margin-bottom:18px; font-size:15px;">
                <i class="fa-solid fa-box"></i> Catat Penerimaan Barang
            </h4>
            <?php if ($po_disetujui && $po_disetujui->num_rows > 0): ?>
            <form method="POST">
                <div style="margin-bottom:12px;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Pilih PO</label>
                    <select name="id_po" required
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                        <option value="">-- Pilih PO --</option>
                        <?php
                            $po_disetujui->data_seek(0);
                            while ($po = $po_disetujui->fetch_assoc()):
                        ?>
                            <option value="<?= $po['id_po'] ?>"><?= $po['no_po'] ?> — <?= htmlspecialchars($po['nama_item']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Jumlah Diterima</label>
                    <input type="number" name="jumlah_terima" required min="1"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Kondisi Barang</label>
                    <select name="kondisi"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                        <option value="Baik">Baik</option>
                        <option value="Rusak Sebagian">Rusak Sebagian</option>
                        <option value="Rusak Total">Rusak Total</option>
                    </select>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Tanggal Terima</label>
                    <input type="date" name="tgl_terima" required
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Catatan</label>
                    <input type="text" name="catatan" placeholder="opsional..."
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                </div>
                <button type="submit" name="catat_penerimaan"
                    style="background:#FFB62A; color:#021F42; font-weight:700; padding:9px 20px; border:none; border-radius:8px; font-size:13px; cursor:pointer;">
                    <i class="fa-solid fa-box-open"></i> Catat Penerimaan
                </button>
            </form>
            <?php else: ?>
                <p style="color:#64748b; font-size:13px;">Belum ada PO berstatus <strong>Disetujui</strong>.</p>
            <?php endif; ?>
        </div>

        <!-- FORM INPUT INVOICE VENDOR -->
        <div style="background:#032b5c; padding:25px; border-radius:12px; border:1px solid rgba(255,255,255,0.06);">
            <h4 style="color:#a855f7; margin-bottom:18px; font-size:15px;">
                <i class="fa-solid fa-file-invoice-dollar"></i> Input Invoice Vendor
            </h4>
            <?php if ($po_selesai && $po_selesai->num_rows > 0): ?>
            <form method="POST">
                <div style="margin-bottom:12px;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Pilih PO (Barang Diterima)</label>
                    <select name="id_po_inv" required
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                        <option value="">-- Pilih PO --</option>
                        <?php while ($po = $po_selesai->fetch_assoc()): ?>
                            <option value="<?= $po['id_po'] ?>"><?= $po['no_po'] ?> — <?= htmlspecialchars($po['nama_vendor']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">No. Invoice Vendor</label>
                    <input type="text" name="no_invoice_vendor" required placeholder="contoh: INV/VENDOR/2026/001"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Jumlah Tagihan (Rp)</label>
                    <input type="number" name="jumlah_tagihan" required min="0"
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="color:#cbd5e1; font-size:13px; font-weight:600;">Tanggal Invoice</label>
                    <input type="date" name="tgl_invoice" required
                        style="width:100%; margin-top:6px; padding:10px 14px; background:#021F42; border:1px solid rgba(255,255,255,0.1); border-radius:8px; color:#fff; font-size:13px;">
                </div>
                <button type="submit" name="input_invoice"
                    style="background:#a855f7; color:#fff; font-weight:700; padding:9px 20px; border:none; border-radius:8px; font-size:13px; cursor:pointer;">
                    <i class="fa-solid fa-file-import"></i> Input Invoice
                </button>
            </form>
            <?php else: ?>
                <p style="color:#64748b; font-size:13px;">Belum ada PO berstatus <strong>Selesai</strong> (barang diterima).</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABEL HASIL MATCHING -->
    <h4 style="color:#fff; margin-bottom:15px; font-size:16px; font-weight:600;">
        <i class="fa-solid fa-code-compare"></i> Hasil 3-Way Matching
    </h4>

    <table class="table-custom">
        <thead>
            <tr>
                <th>No. PO</th>
                <th>Vendor</th>
                <th>Item</th>
                <th>No. Invoice Vendor</th>
                <th>Total PO</th>
                <th>Tagihan Vendor</th>
                <th>Jml PO</th>
                <th>Jml Terima</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($data_matching && $data_matching->num_rows > 0): ?>
                <?php while ($row = $data_matching->fetch_assoc()): ?>
                <tr>
                    <td><strong style="color:#00cfd5;"><?= $row['no_po'] ?></strong></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($row['nama_vendor']) ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($row['nama_item']) ?></td>
                    <td style="font-size:13px; color:#a855f7;"><?= $row['no_invoice_vendor'] ?></td>
                    <td>Rp <?= number_format($row['total_po'], 0, ',', '.') ?></td>
                    <td>Rp <?= number_format($row['jumlah_tagihan'], 0, ',', '.') ?></td>
                    <td><?= $row['jumlah_po'] ?></td>
                    <td><?= $row['jumlah_terima'] ?? '-' ?></td>
                    <td>
                        <?php
                            $status = $row['status'];
                            $warna  = match(true) {
                                str_contains($status, 'Matched')      => '#22c55e',
                                str_contains($status, 'Tidak Match')  => '#ef4444',
                                default                               => '#FFB62A',
                            };
                        ?>
                        <span style="background:<?= $warna ?>22; color:<?= $warna ?>; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; border:1px solid <?= $warna ?>44;">
                            <?= $status ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Menunggu Matching'): ?>
                            <a href="?matching=<?= $row['id_invoice_vendor'] ?>"
                               onclick="return confirm('Proses 3-Way Matching sekarang?')"
                               style="background:#22c55e; color:#fff; padding:5px 12px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">
                               <i class="fa-solid fa-check-double"></i> Matching
                            </a>
                        <?php else: ?>
                            <span style="color:#64748b; font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" style="text-align:center; padding:30px; color:#64748b;">
                        Belum ada data invoice vendor. Input invoice setelah barang diterima!
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>
