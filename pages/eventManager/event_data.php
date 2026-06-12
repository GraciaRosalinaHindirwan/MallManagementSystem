<?php
session_start();
if (!isset($_SESSION['event_areas'])) {
    $_SESSION['event_areas'] = [
        ['id' => 1, 'nama' => 'Atrium Utama',       'kapasitas' => 500, 'tarif_per_hari' => 5000000, 'fasilitas' => 'Sound system, AC, Lighting, Stage'],
        ['id' => 2, 'nama' => 'Rooftop Zone A',     'kapasitas' => 200, 'tarif_per_hari' => 2500000, 'fasilitas' => 'Outdoor, Lighting, Listrik'],
        ['id' => 3, 'nama' => 'Exhibition Hall',    'kapasitas' => 300, 'tarif_per_hari' => 3500000, 'fasilitas' => 'AC, Listrik, Loading dock'],
        ['id' => 4, 'nama' => 'Ground Floor Plaza', 'kapasitas' => 400, 'tarif_per_hari' => 4000000, 'fasilitas' => 'AC, Stage, Sound system'],
        ['id' => 5, 'nama' => 'Sky Lounge Lt. 8',   'kapasitas' => 150, 'tarif_per_hari' => 3000000, 'fasilitas' => 'AC, Projector, View city, Catering area'],
    ];
}

if (!isset($_SESSION['event_pengajuan'])) {
    $_SESSION['event_pengajuan'] = [
        [
            'id' => 'EVT-001', 'id_area' => 1, 'nama_area' => 'Atrium Utama',
            'pemohon' => 'PT Kreatif Nusantara', 'tipe_event' => 'Bazar / Pameran',
            'tanggal_mulai' => '2025-08-10', 'tanggal_selesai' => '2025-08-12',
            'estimasi_pengunjung' => 1500, 'kebutuhan' => 'Booth 20 unit, listrik extra 3-phase, backdrop 6x4m',
            'status' => 'approved', 'catatan_admin' => 'Dokumen lengkap, disetujui. DP sudah masuk.',
            'created_at' => '2025-07-20',
        ],
        [
            'id' => 'EVT-002', 'id_area' => 3, 'nama_area' => 'Exhibition Hall',
            'pemohon' => 'Tenant: Elektronik Zone', 'tipe_event' => 'Launching Produk',
            'tanggal_mulai' => '2025-08-15', 'tanggal_selesai' => '2025-08-15',
            'estimasi_pengunjung' => 300, 'kebutuhan' => 'Proyektor 4K, sound system, backdrop LED, 2 unit standing banner',
            'status' => 'pending', 'catatan_admin' => '',
            'created_at' => '2025-07-25',
        ],
        [
            'id' => 'EVT-003', 'id_area' => 2, 'nama_area' => 'Rooftop Zone A',
            'pemohon' => 'CV Harmoni Event', 'tipe_event' => 'Konser / Hiburan',
            'tanggal_mulai' => '2025-08-20', 'tanggal_selesai' => '2025-08-20',
            'estimasi_pengunjung' => 180, 'kebutuhan' => 'Sound system outdoor, lighting rooftop, stage portable 4x6m',
            'status' => 'approved', 'catatan_admin' => 'Izin keramaian sudah dilampirkan. OK.',
            'created_at' => '2025-07-28',
        ],
        [
            'id' => 'EVT-004', 'id_area' => 4, 'nama_area' => 'Ground Floor Plaza',
            'pemohon' => 'Dinas Koperasi Kota', 'tipe_event' => 'Bazar / Pameran',
            'tanggal_mulai' => '2025-09-01', 'tanggal_selesai' => '2025-09-05',
            'estimasi_pengunjung' => 3000, 'kebutuhan' => 'Booth 50 unit UMKM, listrik, toilet portable tambahan',
            'status' => 'revision', 'catatan_admin' => 'Proposal belum menyertakan izin keramaian dan asuransi event. Mohon dilengkapi.',
            'created_at' => '2025-07-30',
        ],
        [
            'id' => 'EVT-005', 'id_area' => 5, 'nama_area' => 'Sky Lounge Lt. 8',
            'pemohon' => 'PT Asuransi Maju Bersama', 'tipe_event' => 'Aktivasi Brand / Sponsor',
            'tanggal_mulai' => '2025-08-25', 'tanggal_selesai' => '2025-08-25',
            'estimasi_pengunjung' => 120, 'kebutuhan' => 'Catering untuk 120 pax, proyektor, standing desk branding',
            'status' => 'approved', 'catatan_admin' => 'Corporate event, kontrak sudah ditandatangani.',
            'created_at' => '2025-08-01',
        ],
        [
            'id' => 'EVT-006', 'id_area' => 1, 'nama_area' => 'Atrium Utama',
            'pemohon' => 'PT AutoMobil Indonesia', 'tipe_event' => 'Bazar / Pameran',
            'tanggal_mulai' => '2025-09-10', 'tanggal_selesai' => '2025-09-14',
            'estimasi_pengunjung' => 5000, 'kebutuhan' => 'Display 8 unit mobil, lighting premium, backdrop LED 10x5m, security tambahan',
            'status' => 'pending', 'catatan_admin' => '',
            'created_at' => '2025-08-05',
        ],
        [
            'id' => 'EVT-007', 'id_area' => 3, 'nama_area' => 'Exhibition Hall',
            'pemohon' => 'Universitas Nusantara', 'tipe_event' => 'Job Fair',
            'tanggal_mulai' => '2025-09-20', 'tanggal_selesai' => '2025-09-21',
            'estimasi_pengunjung' => 1200, 'kebutuhan' => 'Booth 30 perusahaan, meja, kursi, backdrop masing-masing booth',
            'status' => 'rejected', 'catatan_admin' => 'Tanggal bentrok dengan event yang sudah confirmed. Silakan pilih tanggal lain.',
            'created_at' => '2025-08-06',
        ],
    ];
}

if (!isset($_SESSION['event_vendors'])) {
    $_SESSION['event_vendors'] = [
        ['id' => 1, 'nama' => 'Soundmax Pro',       'kategori' => 'Sound System',       'kontak' => '0812-3456-7890', 'rating' => 4.8, 'riwayat' => 6],
        ['id' => 2, 'nama' => 'Dekor Indah',         'kategori' => 'Dekorasi',            'kontak' => '0821-9876-5432', 'rating' => 4.5, 'riwayat' => 8],
        ['id' => 3, 'nama' => 'LightShow ID',        'kategori' => 'Lighting',            'kontak' => '0857-1111-2222', 'rating' => 4.7, 'riwayat' => 4],
        ['id' => 4, 'nama' => 'MegaBooth Rental',    'kategori' => 'Booth / Backdrop',    'kontak' => '0813-5555-6666', 'rating' => 4.3, 'riwayat' => 10],
        ['id' => 5, 'nama' => 'SecureEvent Guard',   'kategori' => 'Keamanan Event',      'kontak' => '0811-7777-8888', 'rating' => 4.6, 'riwayat' => 5],
        ['id' => 6, 'nama' => 'Cita Rasa Catering',  'kategori' => 'Catering Sementara',  'kontak' => '0822-3333-4444', 'rating' => 4.4, 'riwayat' => 7],
        ['id' => 7, 'nama' => 'TechStage Solutions', 'kategori' => 'Sound System',       'kontak' => '0856-9999-0000', 'rating' => 4.2, 'riwayat' => 3],
    ];
}

if (!isset($_SESSION['event_tiket'])) {
    $_SESSION['event_tiket'] = [
        ['id' => 'TKT-001', 'id_event' => 'EVT-001', 'tipe' => 'Gratis',     'kuota' => 1500, 'terjual' => 1180, 'harga' => 0,      'pendapatan' => 0],
        ['id' => 'TKT-002', 'id_event' => 'EVT-001', 'tipe' => 'Early Bird', 'kuota' => 200,  'terjual' => 200,  'harga' => 25000,  'pendapatan' => 5000000],
        ['id' => 'TKT-003', 'id_event' => 'EVT-001', 'tipe' => 'Regular',    'kuota' => 500,  'terjual' => 340,  'harga' => 35000,  'pendapatan' => 11900000],
        ['id' => 'TKT-004', 'id_event' => 'EVT-003', 'tipe' => 'Regular',    'kuota' => 150,  'terjual' => 150,  'harga' => 75000,  'pendapatan' => 11250000],
        ['id' => 'TKT-005', 'id_event' => 'EVT-003', 'tipe' => 'VIP',        'kuota' => 30,   'terjual' => 28,   'harga' => 200000, 'pendapatan' => 5600000],
        ['id' => 'TKT-006', 'id_event' => 'EVT-005', 'tipe' => 'Gratis',     'kuota' => 120,  'terjual' => 110,  'harga' => 0,      'pendapatan' => 0],
    ];
}

if (!isset($_SESSION['event_sponsorship'])) {
    $_SESSION['event_sponsorship'] = [
        ['id' => 'SPO-001', 'id_event' => 'EVT-001', 'sponsor' => 'Brand Minuman Segar X',  'paket' => 'Gold',         'nilai' => 15000000, 'status_bayar' => 'lunas'],
        ['id' => 'SPO-002', 'id_event' => 'EVT-001', 'sponsor' => 'Bank Digital Nusa',      'paket' => 'Platinum',     'nilai' => 25000000, 'status_bayar' => 'lunas'],
        ['id' => 'SPO-003', 'id_event' => 'EVT-001', 'sponsor' => 'Telko Provider Z',       'paket' => 'Silver',       'nilai' => 8000000,  'status_bayar' => 'belum'],
        ['id' => 'SPO-004', 'id_event' => 'EVT-003', 'sponsor' => 'Apparel Brand Lokal',    'paket' => 'Gold',         'nilai' => 12000000, 'status_bayar' => 'lunas'],
        ['id' => 'SPO-005', 'id_event' => 'EVT-005', 'sponsor' => 'PT Investasi Makmur',    'paket' => 'Media Partner', 'nilai' => 5000000,  'status_bayar' => 'belum'],
    ];
}

if (!isset($_SESSION['event_analytics_extended'])) {
    $_SESSION['event_analytics_extended'] = [
        [
            'id_event' => 'EVT-001', 'nama_event' => 'Bazar UMKM Aug 2025',
            'tipe' => 'Bazar / Pameran', 'area' => 'Atrium Utama', 'tanggal' => '10–12 Agt 2025',
            'jml_pengunjung' => 1820, 'target_pengunjung' => 1500,
            'revenue_sewa' => 15000000, 'revenue_tiket' => 16900000, 'revenue_sponsor' => 48000000,
            'traffic_before' => 3200, 'traffic_during' => 5800, 'traffic_after' => 3600,
            'rating_kepuasan' => 4.4, 'rating_vendor' => 4.6,
            'catatan' => 'Event sangat ramai, booth habis terjual. Perlu area lebih luas untuk tahun depan.',
        ],
        [
            'id_event' => 'EVT-003', 'nama_event' => 'Rooftop Concert Aug 2025',
            'tipe' => 'Konser / Hiburan', 'area' => 'Rooftop Zone A', 'tanggal' => '20 Agt 2025',
            'jml_pengunjung' => 175, 'target_pengunjung' => 180,
            'revenue_sewa' => 2500000, 'revenue_tiket' => 16850000, 'revenue_sponsor' => 12000000,
            'traffic_before' => 1800, 'traffic_during' => 2900, 'traffic_after' => 2100,
            'rating_kepuasan' => 4.7, 'rating_vendor' => 4.5,
            'catatan' => 'Tiket VIP habis terjual. Penonton sangat antusias. Akustik rooftop perlu improvement.',
        ],
        [
            'id_event' => 'EVT-SAMPLE', 'nama_event' => 'Car Exhibition Jul 2025',
            'tipe' => 'Pameran Produk', 'area' => 'Exhibition Hall', 'tanggal' => '5–7 Jul 2025',
            'jml_pengunjung' => 920, 'target_pengunjung' => 1000,
            'revenue_sewa' => 10500000, 'revenue_tiket' => 0, 'revenue_sponsor' => 25000000,
            'traffic_before' => 2800, 'traffic_during' => 4100, 'traffic_after' => 3000,
            'rating_kepuasan' => 4.1, 'rating_vendor' => 4.3,
            'catatan' => 'Sponsor otomotif sangat besar. Traffic meningkat 46% selama event.',
        ],
    ];
}

function getAreas() { return $_SESSION['event_areas']; }
function getPengajuan() { return $_SESSION['event_pengajuan']; }
function getVendors() { return $_SESSION['event_vendors']; }
function getAnalytics() { return $_SESSION['event_analytics'] ?? []; }

function getAreaById($id) {
    foreach ($_SESSION['event_areas'] as $a) {
        if ($a['id'] == $id) return $a;
    }
    return null;
}

function getPengajuanById($id) {
    foreach ($_SESSION['event_pengajuan'] as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

function addPengajuan($data) {
    $max = 0;
    foreach ($_SESSION['event_pengajuan'] as $p) {
        $num = (int)substr($p['id'], 4);
        if ($num > $max) $max = $num;
    }
    $id   = 'EVT-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    $area = getAreaById($data['id_area']);
    $_SESSION['event_pengajuan'][] = [
        'id'                  => $id,
        'id_area'             => (int)$data['id_area'],
        'nama_area'           => $area ? $area['nama'] : '-',
        'pemohon'             => htmlspecialchars($data['pemohon']),
        'tipe_event'          => htmlspecialchars($data['tipe_event']),
        'tanggal_mulai'       => $data['tanggal_mulai'],
        'tanggal_selesai'     => $data['tanggal_selesai'],
        'estimasi_pengunjung' => (int)$data['estimasi_pengunjung'],
        'kebutuhan'           => htmlspecialchars($data['kebutuhan']),
        'status'              => 'pending',
        'catatan_admin'       => '',
        'created_at'          => date('Y-m-d'),
    ];
    return $id;
}

function checkConflict($id_area, $tanggal_mulai, $tanggal_selesai, $exclude_id = null) {
    $konflik = [];
    foreach ($_SESSION['event_pengajuan'] as $p) {
        if ($p['id_area'] != $id_area) continue;
        if ($p['status'] === 'rejected') continue;
        if ($exclude_id && $p['id'] === $exclude_id) continue;
        if ($tanggal_mulai <= $p['tanggal_selesai'] && $tanggal_selesai >= $p['tanggal_mulai']) {
            $konflik[] = $p;
        }
    }
    return $konflik;
}

function updateStatusPengajuan($id, $status, $catatan = '') {
    foreach ($_SESSION['event_pengajuan'] as &$p) {
        if ($p['id'] === $id) {
            $p['status']        = $status;
            $p['catatan_admin'] = htmlspecialchars($catatan);
            return true;
        }
    }
    return false;
}

function deletePengajuan($id) {
    $_SESSION['event_pengajuan'] = array_values(
        array_filter($_SESSION['event_pengajuan'], fn($p) => $p['id'] !== $id)
    );
    if (isset($_SESSION['event_tiket'])) {
        $_SESSION['event_tiket'] = array_values(
            array_filter($_SESSION['event_tiket'], fn($t) => $t['id_event'] !== $id)
        );
    }
    if (isset($_SESSION['event_sponsorship'])) {
        $_SESSION['event_sponsorship'] = array_values(
            array_filter($_SESSION['event_sponsorship'], fn($s) => $s['id_event'] !== $id)
        );
    }
}

function deleteVendor($id) {
    $_SESSION['event_vendors'] = array_values(
        array_filter($_SESSION['event_vendors'], fn($v) => $v['id'] != $id)
    );
}

function deleteTiket($id) {
    $_SESSION['event_tiket'] = array_values(
        array_filter($_SESSION['event_tiket'], fn($t) => $t['id'] !== $id)
    );
}

function deleteSponsor($id) {
    $_SESSION['event_sponsorship'] = array_values(
        array_filter($_SESSION['event_sponsorship'], fn($s) => $s['id'] !== $id)
    );
}

function statusBadge($status) {
    $map = [
        'pending'  => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'revision' => '<span class="badge bg-info text-dark">Perlu Revisi</span>',
    ];
    return $map[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}