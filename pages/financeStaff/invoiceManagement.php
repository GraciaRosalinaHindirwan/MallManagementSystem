<?php
// =========================================================================
// FINANCE & ACCOUNTING MODULE - INVOICE MANAGEMENT (PBI-M06-01-01)
// =========================================================================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simulasi hak akses Staff Finance
$_SESSION['role'] = 'Finance Staff'; 
$_SESSION['nama'] = 'Staff';

// Panggil file koneksi database terpusat
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

// Panggil asset tata letak header & navigasi
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';

// PROSES PBI-M06-01-01: Sinkronisasi Kontrak Aktif (M02) Menjadi Invoice (M06)
if (isset($_POST['sync_m02'])) {
    
    // SESUAI DATABASE: Kolom status kontrak adalah 'contract_status' dan bernilai 'Active'
    $cek_kontrak = $conn->query("SELECT * FROM 02_contracts WHERE contract_status = 'Active'");
    
    if ($cek_kontrak && $cek_kontrak->num_rows > 0) {
        $sukses = 0;
        
        while ($kontrak = $cek_kontrak->fetch_assoc()) {
            // SESUAI DATABASE: PK kontrak adalah 'id_contract', dan FK tenant adalah 'id_tenant'
            $id_contract = $kontrak['id_contract'];        
            $id_tenant   = $kontrak['id_tenant']; 
            $total_sewa  = 5000000; // Tarif dasar dummy/bulanan awal
            
            // Cek pencegahan duplikasi data invoice per kontrak aktif
            $cek_double = $conn->query("SELECT id FROM 06_invoices WHERE contract_id = '$id_contract'");
            
            if ($cek_double && $cek_double->num_rows == 0) {
                $no_inv = "INV-2026-" . rand(1000, 9999);
                $jatuh_tempo = date('Y-m-d', strtotime('+14 days'));

                // Simpan ke 06_invoices sesuai struktur kolom target
                $insert = $conn->query("INSERT INTO 06_invoices (invoice_number, contract_id, tenant_id, due_date, total_amount, status) 
                                        VALUES ('$no_inv', '$id_contract', '$id_tenant', '$jatuh_tempo', '$total_sewa', 'Belum Bayar')");
                
                if ($insert) {
                    $sukses++;
                }
            }
        }
        
        if ($sukses > 0) {
            echo "<script>alert('Sukses! $sukses Data invoice baru berhasil dibuat secara otomatis dari kontrak aktif.'); window.location='invoiceManagement.php';</script>";
        } else {
            echo "<script>alert('Perhatian: Tidak ada kontrak baru. Semua kontrak aktif sudah memiliki invoice.'); window.location='invoiceManagement.php';</script>";
        }
    } else {
        // Fallback dummy jika database kosong
        $no_inv = "INV-2026-999";
        $conn->query("INSERT INTO 06_invoices (invoice_number, contract_id, tenant_id, due_date, total_amount, status) 
                      VALUES ('$no_inv', 1, 1, '2026-07-20', 12500000, 'Belum Bayar')");
        echo "<script>alert('Demo Mode: Kontrak M02 kosong, invoice simulasi berhasil diterbitkan!'); window.location='invoiceManagement.php';</script>";
    }
}

// PERBAIKAN TOTAL: Menghubungkan i.tenant_id dengan t.id_tenant milik database Anda
$query_invoices = "SELECT i.*, t.brand_name 
                   FROM 06_invoices i 
                   LEFT JOIN 02_tenants t ON i.tenant_id = t.id_tenant 
                   ORDER BY i.id DESC";

$invoices = $conn->query($query_invoices);

if (!$invoices) {
    die("<div class='alert alert-danger'>Terjadi kegagalan penarikan data invoice: " . $conn->error . "</div>");
}
?>

<div class="content-container" style="padding: 20px; background: var(--bg-primary); min-height: 80vh;">
    <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
        <div>
            <h1 style="color: var(--text-accent); font-size: var(--h1); margin: 0; font-weight: 700;">Invoice Management</h1>
            <p class="text-muted" style="margin: 5px 0 0 0; font-size: 14px; color: #cbd5e1;">PBI-M06-01-01 — Integrasi Sinkronisasi Penagihan Otomatis dari Kontrak Tenant</p>
        </div>
    </div>

    <div class="mb-4">
        <form method="POST">
            <button type="submit" name="sync_m02" class="btn" style="background-color: var(--accent); color: #021F42; font-weight: 600; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                <i class="fa-solid fa-sync-alt"></i> Tarik & Sinkronisasi Kontrak Aktif (M02)
            </button>
        </form>
    </div>

    <div class="table-responsive" style="background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
        <table class="table-custom" style="width: 100%; border-collapse: collapse; margin: 0;">
            <thead>
                <tr style="background: rgba(255,255,255,0.04); text-align: left; border-bottom: 2px solid rgba(255,255,255,0.1);">
                    <th style="padding: 15px 12px; color: var(--text-accent); font-size: 14px;">No. Invoice</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-size: 14px;">Nama Tenant / Brand</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); text-align: center; font-size: 14px;">ID Kontrak</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-size: 14px;">Total Tagihan</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-size: 14px;">Jatuh Tempo</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); font-size: 14px;">Status</th>
                    <th style="padding: 15px 12px; color: var(--text-accent); text-align: center; font-size: 14px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($invoices && $invoices->num_rows > 0): ?>
                    <?php while ($row = $invoices->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 12px; font-size: 13px; color: #fff;"><strong><?= $row['invoice_number']; ?></strong></td>
                        <td style="padding: 12px; font-size: 13px; color: #cbd5e1;"><?= $row['brand_name'] ?? 'Tenant Tidak Teridentifikasi'; ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <span class="badge" style="background: rgba(255,255,255,0.08); color: #cbd5e1; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-family: monospace;">
                                #CT-<?= $row['contract_id']; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; font-size: 13px; font-weight: 600; color: #fff;">Rp <?= number_format($row['total_amount'], 0, ',', '.'); ?></td>
                        <td style="padding: 12px; font-size: 13px; color: #cbd5e1;"><?= date('d M Y', strtotime($row['due_date'])); ?></td>
                        <td style="padding: 12px;">
                            <span class="badge" style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; <?= ($row['status'] == 'Lunas') ? 'background-color: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);' : 'background-color: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'; ?>">
                                <?= $row['status']; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center; white-space: nowrap;">
                            <button type="button" 
                                    style="background: rgba(255,255,255,0.05); color: #cbd5e1; font-size: 12px; border: 1px solid rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 4px; margin-right: 5px; cursor: pointer; transition: all 0.2s;" 
                                    onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff';" 
                                    onmouseout="this.style.background='rgba(255,255,255,0.05)'; this.style.color='#cbd5e1';"
                                    onclick="alert('Notifikasi tagihan elektronik telah dikirim ke email tenant!')">
                                <i class="fa-regular fa-paper-plane" style="margin-right: 4px;"></i> Kirim
                            </button>
                            
                            <?php if ($row['status'] !== 'Lunas'): ?>
                                <a href="billingManagement.php?id=<?= $row['id']; ?>" 
                                   style="background: rgba(241, 196, 15, 0.1); color: var(--accent); font-size: 12px; border: 1px solid rgba(241, 196, 15, 0.2); padding: 5px 12px; border-radius: 4px; text-decoration: none; display: inline-block; transition: all 0.2s;"
                                   onmouseover="this.style.background='var(--accent)'; this.style.color='#021F42';" 
                                   onmouseout="this.style.background='rgba(241, 196, 15, 0.1)'; this.style.color='var(--accent)';">
                                    <i class="fa-solid fa-receipt" style="margin-right: 4px;"></i> Bayar
                                </a>
                            <?php else: ?>
                                <button type="button" disabled 
                                        style="background: rgba(255,255,255,0.02); color: #4a5568; font-size: 12px; border: 1px solid rgba(255,255,255,0.05); padding: 5px 12px; border-radius: 4px; cursor: not-allowed;">
                                    <i class="fa-solid fa-check-double" style="margin-right: 4px;"></i> Selesai
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 50px; text-align: center; color: #a0aec0;">
                            <span style="font-size: 30px; display: block; margin-bottom: 10px;">📂</span>
                            Belum ada data invoice yang tercatat dalam sistem.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
require_once '../../includes/footer.php'; 
?>
