<?php
/** @var mysqli $conn */ // Memberitahu VS Code kalau $conn itu objek database sah!

if (session_status() == PHP_SESSION_NONE) { session_start(); }
// Uncomment ini nanti kalau auth sudah jalan:
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeStaff') {
//     header("Location: ../../index.php"); 
//     exit();
// }

$_SESSION['role'] = 'financeStaff'; 
$_SESSION['nama'] = 'Staff Finance'; 

// ── 1. KONEKSI DATABASE ───────────────────────────────────────────────────
require_once '../../config/konek.php';

// ── Helper: jalankan prepared statement MySQLi, return array of assoc ─────
function db_query(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function db_row(mysqli $conn, string $sql, string $types = '', array $params = []): array {
    $rows = db_query($conn, $sql, $types, $params);
    return $rows[0] ?? [];
}

// ── 2. SETTING WAKTU OTOMATIS ─────────────────────────────────────────────
date_default_timezone_set('Asia/Jakarta');

$bulan_indo = [
    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
    5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
    9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
];

$tahun_angka = (int)date('Y');

// Ambil filter bulan dari URL (?bulan=1 s/d 12), default = bulan sekarang
$bulan_angka = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$bulan_angka = max(1, min(12, $bulan_angka)); // Pastikan range 1-12

$bulan_aktif     = $bulan_indo[$bulan_angka] . ' ' . $tahun_angka;
$periode_sql     = $tahun_angka . '-' . str_pad($bulan_angka, 2, '0', STR_PAD_LEFT);
$bulan_lalu_angka = $bulan_angka === 1 ? 12 : $bulan_angka - 1;
$tahun_lalu       = $bulan_angka === 1 ? $tahun_angka - 1 : $tahun_angka;
$periode_lalu     = $tahun_lalu . '-' . str_pad($bulan_lalu_angka, 2, '0', STR_PAD_LEFT);
$bulan_list      = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
$bulan_aktif_idx = $bulan_angka - 1;

// ── Helper: format Rupiah ─────────────────────────────────────────────────
if (!function_exists('fmtRp')) {
    function fmtRp(float $n): string {
        if ($n >= 1_000_000_000) return 'Rp ' . number_format($n / 1_000_000_000, 1, ',', '.') . ' M';
        if ($n >= 1_000_000)     return 'Rp ' . number_format($n / 1_000_000, 1, ',', '.') . ' Jt';
        if ($n >= 1_000)         return 'Rp ' . number_format($n / 1_000, 0, ',', '.') . ' rb';
        return 'Rp ' . number_format($n, 0, ',', '.');
    }
}

// ── 3. QUERY STAT CARDS ───────────────────────────────────────────────────

// 3a. PARKIR — dari 04_parking_transaksi
$parkir = db_row($conn, "
    SELECT
        COALESCE(SUM(amount), 0) AS total,
        COUNT(DISTINCT DATE(exit_time)) AS jumlah_hari
    FROM 04_parking_transaksi
    WHERE DATE_FORMAT(exit_time, '%Y-%m') = ?
", 's', [$periode_sql]);

$parkir_lalu = db_row($conn, "
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM 04_parking_transaksi
    WHERE DATE_FORMAT(exit_time, '%Y-%m') = ?
", 's', [$periode_lalu]);

$pendapatan_sekarang = $parkir['total'] ?? 0;
$pendapatan_lalu = $parkir_lalu['total'] ?? 0;

if ($pendapatan_lalu > 0) {
    $parkir_trend_pct = (($pendapatan_sekarang - $pendapatan_lalu) / $pendapatan_lalu) * 100;
} else {
    $parkir_trend_pct = ($pendapatan_sekarang > 0) ? 100 : 0;
}

// 3b. EVENT & SPONSORSHIP 
$event = db_row($conn, "
    SELECT
        COALESCE(SUM(er.amount), 0)       AS total_event,
        COUNT(DISTINCT er.booking_id)      AS jumlah_event
    FROM 06_event_revenue er
    INNER JOIN 04_event_booking eb ON er.booking_id = eb.id_booking
    WHERE DATE_FORMAT(er.received_date, '%Y-%m') = ?
      AND er.status = 'received'
", 's', [$periode_sql]);

if (empty($event) || $event['total_event'] == 0) {
    $event = db_row($conn, "
        SELECT
            COALESCE(
                (SELECT SUM(es.nilai)
                 FROM 04_event_sponsorship es
                 INNER JOIN 04_event_booking eb2 ON es.id_booking = eb2.id_booking
                 WHERE DATE_FORMAT(eb2.tanggal_mulai, '%Y-%m') = ?
                   AND (es.status_bayar = 'lunas' OR es.status_bayar = 'confirmed')), 0)
            + COALESCE(
                (SELECT SUM(et.pendapatan)
                 FROM 04_event_tiket et
                 INNER JOIN 04_event_booking eb3 ON et.id_booking = eb3.id_booking
                 WHERE DATE_FORMAT(eb3.tanggal_mulai, '%Y-%m') = ?), 0)
            AS total_event,
            (SELECT COUNT(DISTINCT id_booking)
             FROM 04_event_booking
             WHERE DATE_FORMAT(tanggal_mulai, '%Y-%m') = ?
               AND (status = 'selesai' OR status = 'confirmed')) AS jumlah_event
    ", 'sss', [$periode_sql, $periode_sql, $periode_sql]);
}

$event_lalu = db_row($conn, "
    SELECT COALESCE(SUM(amount), 0) AS total_event
    FROM 06_event_revenue
    WHERE DATE_FORMAT(received_date, '%Y-%m') = ? AND status = 'received'
", 's', [$periode_lalu]);

if ($event_lalu['total_event'] > 0) {
    $event_trend_pct = (($event['total_event'] - $event_lalu['total_event']) / $event_lalu['total_event']) * 100;
} else {
    $event_trend_pct = ($event['total_event'] > 0) ? 100 : 0;
}
$event_trend_pct = round($event_trend_pct);

// 3c. IKLAN / BILLBOARD
$iklan = db_row($conn, "
    SELECT
        COALESCE(SUM(monthly_fee), 0) AS total_iklan,
        COUNT(*)                       AS jumlah_kontrak
    FROM 06_ad_contracts
    WHERE current_period LIKE ?
      AND status = 'active'
      AND billing_status = 'paid'
", 's', [$periode_sql . '%']);

$iklan_lalu = db_row($conn, "
    SELECT COALESCE(SUM(monthly_fee), 0) AS total_iklan
    FROM 06_ad_contracts
    WHERE current_period LIKE ? AND status = 'active' AND billing_status = 'paid'
", 's', [$periode_lalu . '%']);

if ($iklan_lalu['total_iklan'] > 0) {
    $iklan_trend_pct = (($iklan['total_iklan'] - $iklan_lalu['total_iklan']) / $iklan_lalu['total_iklan']) * 100;
} else {
    $iklan_trend_pct = ($iklan['total_iklan'] > 0) ? 100 : 0;
}
$iklan_trend_pct = round($iklan_trend_pct);

// 3d. BAGI HASIL
$bagi_hasil = db_row($conn, "
    SELECT
        COALESCE(SUM(ii.amount), 0)     AS total_bagi_hasil,
        COUNT(DISTINCT i.tenant_id)      AS jumlah_tenant
    FROM 06_invoices i
    INNER JOIN 06_invoice_items ii ON ii.invoice_id = i.id
    WHERE (DATE_FORMAT(i.period_start, '%Y-%m') = ? OR i.period_start LIKE ?)
      AND ii.charge_type = 'revenue_sharing'
", 'ss', [$periode_sql, $periode_sql . '%']);

if (empty($bagi_hasil) || $bagi_hasil['total_bagi_hasil'] == 0) {
    $rs_fallback = db_row($conn, "
        SELECT COUNT(DISTINCT id_contract) AS jumlah_tenant
        FROM 02_contract_cost
        WHERE charge_type = 'Revenue Sharing' OR charge_type = 'revenue_sharing'
    ");
    $bagi_hasil['jumlah_tenant'] = $rs_fallback['jumlah_tenant'] ?? 0;
    $bagi_hasil['total_bagi_hasil'] = 0; 
}

$bagi_hasil_lalu = db_row($conn, "
    SELECT COALESCE(SUM(ii.amount), 0) AS total_bagi_hasil
    FROM 06_invoices i
    INNER JOIN 06_invoice_items ii ON ii.invoice_id = i.id
    WHERE (DATE_FORMAT(i.period_start, '%Y-%m') = ? OR i.period_start LIKE ?) 
      AND ii.charge_type = 'revenue_sharing'
", 'ss', [$periode_lalu, $periode_lalu . '%']);

if ($bagi_hasil_lalu['total_bagi_hasil'] > 0) {
    $bagi_trend_pct = (($bagi_hasil['total_bagi_hasil'] - $bagi_hasil_lalu['total_bagi_hasil']) / $bagi_hasil_lalu['total_bagi_hasil']) * 100;
} else {
    $bagi_trend_pct = ($bagi_hasil['total_bagi_hasil'] > 0) ? 100 : 0;
}
$bagi_trend_pct = round($bagi_trend_pct);

// 3e. TOTAL keseluruhan
$total_nonsewa = $parkir['total'] + $event['total_event'] + $iklan['total_iklan'] + $bagi_hasil['total_bagi_hasil'];

// Susun stat_cards
$stat_cards = [
    [
        'label'    => 'Total Non-Sewa',
        'value'    => fmtRp($total_nonsewa),
        'sub'      => 'Bulan ' . $bulan_aktif,
        'trend'    => ($parkir_trend_pct >= 0 ? '+' : '') . round($parkir_trend_pct) . '%',
        'trend_up' => $parkir_trend_pct >= 0,
        'icon'     => 'fa-coins',
        'color'    => '#00D4D8',
    ],
    [
        'label'    => 'Parkir',
        'value'    => fmtRp($parkir['total']),
        'sub'      => ($parkir['jumlah_hari'] ?? 0) . ' hari terekap',
        'trend'    => ($parkir_trend_pct >= 0 ? '+' : '') . round($parkir_trend_pct) . '%',
        'trend_up' => $parkir_trend_pct >= 0,
        'icon'     => 'fa-square-parking',
        'color'    => '#00D4D8',
    ],
    [
        'label'    => 'Event & Sponsor',
        'value'    => fmtRp($event['total_event']),
        'sub'      => ($event['jumlah_event'] ?? 0) . ' selesai',
        'trend'    => ($event_trend_pct >= 0 ? '+' : '') . $event_trend_pct . '%',
        'trend_up' => $event_trend_pct >= 0,
        'icon'     => 'fa-calendar-days',
        'color'    => '#FFB62A',
    ],
    [
        'label'    => 'Iklan / Billboard',
        'value'    => fmtRp($iklan['total_iklan']),
        'sub'      => ($iklan['jumlah_kontrak'] ?? 0) . ' kontrak aktif',
        'trend'    => ($iklan_trend_pct >= 0 ? '+' : '') . $iklan_trend_pct . '%',
        'trend_up' => $iklan_trend_pct >= 0,
        'icon'     => 'fa-rectangle-ad',
        'color'    => '#22C55E',
    ],
    [
        'label'    => 'Bagi Hasil (RS)',
        'value'    => fmtRp($bagi_hasil['total_bagi_hasil']),
        'sub'      => ($bagi_hasil['jumlah_tenant'] ?? 0) . ' tenant',
        'trend'    => ($bagi_trend_pct >= 0 ? '+' : '') . $bagi_trend_pct . '%',
        'trend_up' => $bagi_trend_pct >= 0,
        'icon'     => 'fa-handshake',
        'color'    => '#A78BFA',
    ],
];

// ── 4. JURNAL HARIAN TERBARU ──────────────────────────────────────────────
$jurnal_rows = db_query($conn, "
    SELECT
        journal_date,
        description                AS keterangan,
        source_type                AS sumber,
        COALESCE(total_debit, 0)   AS debit,
        COALESCE(total_credit, 0)  AS kredit,
        status
    FROM 06_journal_entries
    WHERE DATE_FORMAT(journal_date, '%Y-%m') = ?
    ORDER BY journal_date DESC
    LIMIT 10
", 's', [$periode_sql]);

$sumber_label = [
    'parking'         => 'M04 Auto',
    'event'           => 'M05 Manual',
    'ad'              => 'M06 Iklan',
    'invoice_payment' => 'M06 Invoice',
    'vendor_payment'  => 'M06 Vendor',
    'manual'          => 'Manual',
];

$jurnal_terbaru = [];
foreach ($jurnal_rows as $r) {
    $jurnal_terbaru[] = [
        'tanggal'    => date('d M', strtotime($r['journal_date'])),
        'keterangan' => htmlspecialchars($r['keterangan']),
        'sumber'     => $sumber_label[$r['sumber']] ?? ucfirst($r['sumber'] ?? '-'),
        'debit'      => $r['debit']  > 0 ? number_format($r['debit'],  0, ',', '.') : '—',
        'kredit'     => $r['kredit'] > 0 ? number_format($r['kredit'], 0, ',', '.') : '—',
        'status'     => $r['status'] ?? 'draft',
    ];
}

// ── 5. CEK EXCEPTION ──────────────────────────────────────────────────────
$exception = db_row($conn, "
    SELECT COUNT(*) AS jumlah, COALESCE(SUM(total_revenue), 0) AS nominal
    FROM 06_daily_parking_summary
    WHERE DATE_FORMAT(summary_date, '%Y-%m') = ?
      AND status != 'completed'
", 's', [$periode_sql]);
$ada_exception = ($exception['jumlah'] ?? 0) > 0;

// ── 6. CHART DATA ─────────────────────────────────────────────────────────
$total_chart = max($total_nonsewa, 1);
$chart_data = [
    ['label'=>'Parkir',     'pct'=>round($parkir['total']                 / $total_chart * 100), 'val'=>fmtRp($parkir['total']),                'color'=>'#00D4D8'],
    ['label'=>'Event',      'pct'=>round($event['total_event']           / $total_chart * 100), 'val'=>fmtRp($event['total_event']),            'color'=>'#FFB62A'],
    ['label'=>'Iklan',      'pct'=>round($iklan['total_iklan']           / $total_chart * 100), 'val'=>fmtRp($iklan['total_iklan']),            'color'=>'#22C55E'],
    ['label'=>'Bagi Hasil', 'pct'=>round($bagi_hasil['total_bagi_hasil'] / $total_chart * 100), 'val'=>fmtRp($bagi_hasil['total_bagi_hasil']), 'color'=>'#A78BFA'],
];

// ── 7. AKTIVITAS TERBARU ──────────────────────────────────────────────────
$aktivitas_rows = db_query($conn, "
    SELECT * FROM (
        SELECT
            'parking' AS tipe,
            exit_time AS waktu,
            CONCAT('Transaksi Parkir Berhasil (M04) — ID: ', id_transaksi) AS teks,
            amount AS nominal,
            0 AS is_danger
        FROM 04_parking_transaksi
        WHERE DATE_FORMAT(exit_time, '%Y-%m') = ?
        ORDER BY exit_time DESC
        LIMIT 5
    ) p
    UNION ALL
    SELECT * FROM (
        SELECT
            'event' AS tipe,
            CAST(eb.tanggal_mulai AS DATETIME) AS waktu, 
            CONCAT('Event \"', eb.nama_event, '\" terkonfirmasi') AS teks,
            COALESCE(SUM(es.nilai), 0) AS nominal,
            0 AS is_danger
        FROM 04_event_booking eb
        LEFT JOIN 04_event_sponsorship es ON es.id_booking = eb.id_booking
        WHERE DATE_FORMAT(eb.tanggal_mulai, '%Y-%m') = ?
          AND (eb.status = 'selesai' OR eb.status = 'confirmed')
        GROUP BY eb.id_booking, eb.tanggal_mulai, eb.nama_event
        ORDER BY eb.tanggal_mulai DESC
        LIMIT 3
    ) e
    UNION ALL
    SELECT * FROM (
        SELECT
            'iklan' AS tipe,
            last_paid_date AS waktu,
            CONCAT('Iklan ', ad_type, ' — ', advertiser_name) AS teks,
            monthly_fee AS nominal,
            0 AS is_danger
        FROM 06_ad_contracts
        WHERE DATE_FORMAT(last_paid_date, '%Y-%m') = ?
          AND billing_status = 'paid'
        ORDER BY last_paid_date DESC
        LIMIT 3
    ) a
    ORDER BY waktu DESC
    LIMIT 10
", 'sss', [$periode_sql, $periode_sql, $periode_sql]);

$icon_map = [
    'parking' => ['fa-square-parking', '#00D4D8', 'rgba(0,212,216,0.12)'],
    'event'   => ['fa-calendar-days',  '#FFB62A', 'rgba(255,182,42,0.12)'],
    'iklan'   => ['fa-rectangle-ad',   '#22C55E', 'rgba(34,197,94,0.12)'],
];
$aktivitas = [];
foreach ($aktivitas_rows as $r) {
    [$icon, $color, $bg] = $icon_map[$r['tipe']] ?? ['fa-circle', '#94A3B8', 'rgba(148,163,184,0.12)'];
    $waktu_format = (strlen($r['waktu']) <= 10 || strpos($r['waktu'], '00:00:00') !== false) 
        ? date('d M', strtotime($r['waktu'])) 
        : date('d M, H:i', strtotime($r['waktu']));

    $aktivitas[] = [
        'icon'    => $icon,
        'color'   => $color,
        'bg'      => $bg,
        'teks'    => htmlspecialchars($r['teks']),
        'waktu'   => $waktu_format,
        'nominal' => ($r['is_danger'] ? '-' : '+') . fmtRp($r['nominal']),
        'danger'  => (bool)$r['is_danger'],
    ];
}

// ── Helper: status badge ──────────────────────────────────────────────────
if (!function_exists('statusBadge')) {
    function statusBadge(string $s): string {
        $map = ['posted' => 'verified', 'draft' => 'pending', 'reversed' => 'selisih'];
        $key = $map[$s] ?? $s;
        return match($key) {
            'verified' => '<span class="badge-status verified"><i class="fa-solid fa-circle-check"></i> Verified</span>',
            'pending'  => '<span class="badge-status pending"><i class="fa-solid fa-clock"></i> Pending</span>',
            'selisih'  => '<span class="badge-status selisih"><i class="fa-solid fa-triangle-exclamation"></i> Selisih Kas</span>',
            default    => '<span class="badge-status pending">' . htmlspecialchars($s) . '</span>',
        };
    }
}
// ==========================================
// CONFIG LAYOUT UTAMA UNTUK REQUIRE NAVBAR M06
// ==========================================
$department_name = "M06 FINANCE";
$page_title = "Dashboard Pendapatan Non-Sewa";
$user_name = $_SESSION['nama'];

$menu_items = [
    [
        'icon'        => 'fa-solid fa-chart-pie',
        'label'       => 'Dashboard Staff',
        'link'        => 'dashboardStaff.php',
        'active_page' => 'Dashboard Staff'
    ],
    [
        'icon'        => 'fa-solid fa-file-invoice',
        'label'       => 'Invoice Management',
        'link'        => 'invoiceManagement.php',
        'active_page' => 'Invoice Management'
    ],
    [
        'icon'        => 'fa-solid fa-bolt-lightning', 
        'label'       => 'Invoice Utilitas (Air/Listrik)',
        'link'        => 'utility_invoice.php', 
        'active_page' => 'utility_invoice'
    ],
    [
        'icon'        => 'fa-solid fa-cash-register',
        'label'       => 'Billing System',
        'link'        => 'billingManagement.php',
        'active_page' => 'Billing System'
    ],
     [
        'icon'        => 'fa-solid fa-file-invoice-dollar',
        'label'       => 'Vendor Bill',
        'link'        => 'vendor_bill.php',
        'active_page' => 'Vendor Bill'
    ],
    [
        'icon'        => 'fa-solid fa-book',
        'label'       => 'Jurnal Otomatis',
        'link'        => 'journalManagement.php',
        'active_page' => 'Jurnal Otomatis'
    ],
    [
        'icon'        => 'fa-solid fa-book-open',
        'label'       => 'Buku Besar (GL)',
        'link'        => 'bukuBesar.php',
        'active_page' => 'Buku Besar'
    ],
    [
        'icon'        => 'fa-solid fa-folder-open',
        'label'       => 'Dashboard Non Sewa',
        'link'        => 'dashboardNonSewa.php',
        'active_page' => 'Dashboard Non Sewa'
    ]
];

ob_start();
?>

<style>
:root { --accent: #FFB62A !important; }
body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #fff !important; }
.sidebar { background-color: #011630 !important; }
.topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); }

.page-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #00D4D8; margin-bottom: 4px; text-align: left; }
.page-title   { font-size: 24px; font-weight: 700; color: #fff; margin: 0; text-align: left; }
.page-sub     { font-size: 13px; color: #94A3B8; margin-top: 4px; text-align: left; }

.alert-exception { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.25); border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 13px; margin-bottom: 1.25rem; text-align: left; }
.alert-exception-content { display: flex; align-items: flex-start; gap: 10px; }
.alert-exception i { color: #EF4444; margin-top: 2px; }
.alert-exception .ae-title { font-weight: 600; color: #EF4444; }
.alert-exception .ae-body  { color: #94A3B8; margin-top: 2px; }
.btn-alert-action { background: #EF4444; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; text-decoration: none; cursor: pointer; transition: background 0.15s; white-space: nowrap; }
.btn-alert-action:hover { background: #DC2626; color: #fff; }

.filter-row { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 1.5rem; justify-content: flex-start; }
.btn-bulan { padding: 5px 12px; border-radius: 99px; font-size: 12px; border: 1px solid rgba(255,255,255,.1); background: transparent; color: #94A3B8; cursor: pointer; transition: all .15s; text-decoration: none; display: inline-block; }
.btn-bulan:hover { color: #fff; border-color: rgba(255,255,255,.3); text-decoration: none; }
.btn-bulan.active { background: #167E80; color: #fff; border-color: #167E80; }

.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 14px; margin-bottom: 1.75rem; text-align: left; }
.stat-card { background: #011630; border: 1px solid rgba(255,255,255,.08); border-radius: 12px; padding: 1.1rem 1.25rem; position: relative; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--card-accent, #00D4D8); border-radius: 12px 12px 0 0; }
.stat-card .sc-label { font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: .07em; color: #94A3B8; margin-bottom: 8px; }
.stat-card .sc-val   { font-size: 21px; font-weight: 700; color: #fff; line-height: 1.2; }
.stat-card .sc-sub   { font-size: 12px; color: #64748B; margin-top: 3px; }
.badge-trend { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; padding: 2px 8px; border-radius: 99px; margin-top: 8px; }
.badge-trend.up   { background: rgba(34,197,94,.15); color: #22C55E; }
.badge-trend.down { background: rgba(239,68,68,.15);  color: #EF4444; }

.main-grid { display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; align-items: start; text-align: left; }
@media(max-width:1100px){ .main-grid { grid-template-columns: 1fr; } }

.fin-card { background: #011630; border: 1px solid rgba(255,255,255,.08); border-radius: 12px; overflow: hidden; margin-bottom: 1.5rem; }
.fin-card-header { padding: .9rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.06); display: flex; align-items: center; justify-content: space-between; }
.fin-card-title { font-size: 14px; font-weight: 600; color: #fff; }
.fin-card-sub   { font-size: 12px; color: #64748B; margin-top: 2px; }
.fin-card-link  { font-size: 12px; color: #00D4D8; text-decoration: none; }
.fin-card-link:hover { text-decoration: underline; color: #00cfd5; }
.fin-card-body  { padding: 1.1rem 1.25rem; }

.tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.tbl th { font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: .06em; color: #64748B; padding: 8px 12px; border-bottom: 1px solid rgba(255,255,255,.06); text-align: left; }
.tbl td { padding: 10px 12px; color: #fff; border-bottom: 1px solid rgba(255,255,255,.04); vertical-align: middle; }
.tbl tbody tr:last-child td { border-bottom: none; }
.tbl tbody tr:hover { background: rgba(255,255,255,.02); }
.td-muted { color: #64748B !important; }
.tbl-empty { text-align: center; color: #64748B; padding: 24px; font-size: 13px; }

.badge-status { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 500; padding: 3px 10px; border-radius: 99px; }
.badge-status.verified { background: rgba(34,197,94,.12);  color: #22C55E; }
.badge-status.pending  { background: rgba(255,182,42,.12);  color: #FFB62A; }
.badge-status.selisih  { background: rgba(239,68,68,.12);   color: #EF4444; }

.chart-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.chart-lbl  { font-size: 12px; color: #94A3B8; width: 70px; flex-shrink: 0; text-align: right; }
.chart-track{ flex: 1; height: 22px; background: rgba(255,255,255,.05); border-radius: 6px; overflow: hidden; }
.chart-fill { height: 100%; border-radius: 6px; display: flex; align-items: center; padding-left: 8px; font-size: 11px; font-weight: 600; color: #021F42; min-width: 32px; }
.chart-val  { font-size: 12px; color: #fff; width: 72px; text-align: right; flex-shrink: 0; }

.act-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.05); font-size: 12px; }
.act-item:last-child { border-bottom: none; }
.act-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 12px; }
.act-txt  { color: #E2E8F0; line-height: 1.5; }
.act-time { font-size: 11px; color: #64748B; margin-top: 2px; }
.act-amt  { margin-left: auto; font-weight: 600; font-size: 12px; flex-shrink: 0; text-align: right; }
.act-empty { text-align: center; color: #64748B; padding: 20px 0; font-size: 13px; }

.btn-qa { display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 14px; border-radius: 8px; font-size: 13px; font-family: inherit; font-weight: 500; cursor: pointer; text-decoration: none; background: transparent; border: 1px solid rgba(255,255,255,.1); color: #94A3B8; transition: all .15s; margin-bottom: 8px; }
.btn-qa:hover { background: rgba(255,255,255,.05); color: #fff; border-color: rgba(255,255,255,.2); text-decoration: none; }
.btn-qa i { width: 16px; text-align: center; }
</style>

<div class="container-fluid" style="padding-top: 5px;">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="page-eyebrow">Finance & Accounting — PB-M06-02</div>
            <h1 class="page-title">Dashboard Pendapatan Non-Sewa</h1>
            <p class="page-sub">Ringkasan pendapatan parkir, event, iklan, dan bagi hasil bulan ini.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button onclick="window.print()" class="btn btn-sm" style="background:#167E80;color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;">
                <i class="fa-solid fa-print me-1"></i> Export Laporan
            </button>
            <button onclick="location.reload()" class="btn btn-sm" style="background:transparent;color:#94A3B8;border:1px solid rgba(255,255,255,.1);padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;">
                <i class="fa-solid fa-arrows-rotate me-1"></i> Refresh
            </button>
        </div>
    </div>

    <?php if ($ada_exception): ?>
    <div class="alert-exception">
        <div class="alert-exception-content">
            <i class="fa-solid fa-circle-exclamation fa-sm"></i>
            <div>
                <div class="ae-title"><?= $exception['jumlah'] ?> rekap parkir belum selesai</div>
                <div class="ae-body">
                    Total <?= fmtRp($exception['nominal']) ?> belum berstatus <em>completed</em> — perlu verifikasi &amp; jurnal penyesuaian.
                </div>
            </div>
        </div>
        <a href="parkirManagement.php" class="btn-alert-action">Periksa Selisih</a>
    </div>
    <?php endif; ?>

    <div class="filter-row">
        <?php foreach($bulan_list as $i => $b): ?>
            <a href="?bulan=<?= $i + 1 ?>" class="btn-bulan <?= $i === $bulan_aktif_idx ? 'active' : '' ?>"><?= $b ?></a>
        <?php endforeach; ?>
    </div>

    <div class="stat-grid">
        <?php foreach($stat_cards as $c): ?>
        <div class="stat-card" style="--card-accent: <?= $c['color'] ?>">
            <div class="sc-label"><i class="fa-solid <?= $c['icon'] ?> me-1"></i><?= $c['label'] ?></div>
            <div class="sc-val"><?= $c['value'] ?></div>
            <div class="sc-sub"><?= $c['sub'] ?></div>
            <div class="badge-trend <?= $c['trend_up'] ? 'up' : 'down' ?>">
                <i class="fa-solid <?= $c['trend_up'] ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?>"></i>
                <?= $c['trend'] ?> vs bulan lalu
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="main-grid">
        <div>
            <div class="fin-card">
                <div class="fin-card-header">
                    <div>
                        <div class="fin-card-title">Jurnal Harian Terbaru</div>
                        <div class="fin-card-sub">Entri otomatis &amp; manual bulan ini</div>
                    </div>
                    <a href="journalManagement.php" class="fin-card-link">
                        Lihat semua <i class="fa-solid fa-arrow-right fa-xs"></i>
                    </a>
                </div>
                <div style="overflow-x:auto;">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Tanggal</th><th>Keterangan</th><th>Sumber</th>
                                <th style="text-align:right">Debit</th>
                                <th style="text-align:right">Kredit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($jurnal_terbaru)): ?>
                            <tr><td colspan="6" class="tbl-empty">Belum ada jurnal untuk bulan ini.</td></tr>
                            <?php else: ?>
                            <?php foreach($jurnal_terbaru as $j): ?>
                            <tr>
                                <td class="td-muted"><?= $j['tanggal'] ?></td>
                                <td><?= $j['keterangan'] ?></td>
                                <td><span style="font-size:11px;background:rgba(255,255,255,.06);padding:2px 8px;border-radius:6px;color:#94A3B8;"><?= $j['sumber'] ?></span></td>
                                <td style="text-align:right;font-variant-numeric:tabular-nums;"><?= $j['debit'] ?></td>
                                <td style="text-align:right;font-variant-numeric:tabular-nums;" class="td-muted"><?= $j['kredit'] ?></td>
                                <td><?= statusBadge($j['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="fin-card">
                <div class="fin-card-header">
                    <div>
                        <div class="fin-card-title">Komposisi Pendapatan — <?= $bulan_aktif ?></div>
                        <div class="fin-card-sub">Per jenis sumber pendapatan non-sewa</div>
                    </div>
                </div>
                <div class="fin-card-body">
                    <?php foreach($chart_data as $cd): ?>
                    <div class="chart-row">
                        <div class="chart-lbl"><?= $cd['label'] ?></div>
                        <div class="chart-track">
                            <div class="chart-fill" style="width:<?= $total_nonsewa > 0 ? max($cd['pct'], 4) : 0 ?>%;background:<?= $cd['color'] ?>;">
                                <?= $cd['pct'] ?>%
                            </div>
                        </div>
                        <div class="chart-val"><?= $cd['val'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div>
            <div class="fin-card">
                <div class="fin-card-header">
                    <div>
                        <div class="fin-card-title">Aktivitas Terbaru</div>
                        <div class="fin-card-sub">Log transaksi &amp; notifikasi</div>
                    </div>
                </div>
                <div class="fin-card-body" style="padding-top:.5rem;padding-bottom:.5rem;">
                    <?php if (empty($aktivitas)): ?>
                    <div class="act-empty">Belum ada aktivitas bulan ini.</div>
                    <?php else: ?>
                    <?php foreach($aktivitas as $a): ?>
                    <div class="act-item">
                        <div class="act-icon" style="background:<?= $a['bg'] ?>;color:<?= $a['color'] ?>;">
                            <i class="fa-solid <?= $a['icon'] ?>"></i>
                        </div>
                        <div>
                            <div class="act-txt"><?= $a['teks'] ?></div>
                            <div class="act-time"><?= $a['waktu'] ?></div>
                        </div>
                        <div class="act-amt" style="color:<?= $a['danger'] ? '#EF4444' : '#22C55E' ?>">
                            <?= $a['nominal'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="fin-card">
                <div class="fin-card-header">
                    <div class="fin-card-title">Aksi Cepat</div>
                </div>
                <div class="fin-card-body">
                    <a href="parkirManagement.php" class="btn-qa"><i class="fa-solid fa-square-parking"></i> Verifikasi Rekap Parkir</a>
                    <a href="eventManagement.php"  class="btn-qa"><i class="fa-solid fa-calendar-days"></i> Catat Pendapatan Event</a>
                    <a href="iklanManagement.php"  class="btn-qa"><i class="fa-solid fa-rectangle-ad"></i> Input Pendapatan Iklan</a>
                    <a href="journalStaffManagement.php" class="btn-qa"><i class="fa-solid fa-book"></i> Buat Jurnal Staff Penyesuaian</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();

require_once '../../includes/navbarM06.php'; 
?>
