<?php
/**
 * views/layout.php — Shared sidebar + topbar
 *
 * Vars yang harus di-set sebelum include:
 *   $role        'petugas'|'pengunjung'|'admin'|'manajer'
 *   $pageTitle   string
 *   $currentNav  string (id menu aktif)
 *   $state       array dari getParkingState()
 *
 * PHP helper functions tersedia di sini (bukan di JS).
 */

// ── PHP helpers (BUKAN JS, ini server-side) ────────────────────────────────
function phpCap(string $v): string {
    return $v !== '' ? mb_strtoupper(mb_substr($v,0,1)) . mb_substr($v,1) : '-';
}

function phpBadgeClass(string $t): string {
    $t = strtolower($t);
    if ($t === 'member' || $t === 'vip' || $t === 'reguler') return 'member';
    if ($t === 'corporate' || $t === 'korporat') return 'corporate';
    return 'regular';
}

function phpTypeLabel(string $t): string {
    return match(strtolower($t)) {
        'member','reguler','vip' => 'Member',
        'corporate','korporat'   => 'Korporat',
        default                  => 'Pengunjung',
    };
}

function phpMemberBadge(string $type): string {
    return match($type) {
        'VIP'      => 'badge-vip',
        'Korporat' => 'badge-corporate',
        'Reguler'  => 'badge-member',
        default    => 'badge-regular',
    };
}

$roleLabel = match($role ?? 'petugas') {
    'pengunjung' => 'Pengunjung',
    'admin'      => 'Admin',
    'manajer'    => 'Manajer',
    default      => 'Petugas Parkir',
};

$roleIcon = match($role ?? 'petugas') {
    'pengunjung' => 'fa-user',
    'admin'      => 'fa-user-cog',
    'manajer'    => 'fa-chart-line',
    default      => 'fa-user-shield',
};

$menus = [
    'petugas' => [
        ['id'=>'entry',   'icon'=>'fa-sign-in-alt',   'label'=>'Entry Kendaraan'],
        ['id'=>'exit',    'icon'=>'fa-sign-out-alt',   'label'=>'Exit Kendaraan'],
        ['id'=>'aktif',   'icon'=>'fa-car',            'label'=>'Kendaraan Aktif'],
    ],
    'pengunjung' => [
        ['id'=>'tarif',   'icon'=>'fa-tags',           'label'=>'Info Tarif'],
        ['id'=>'cek',     'icon'=>'fa-search',         'label'=>'Cek Kendaraan'],
        ['id'=>'struk',   'icon'=>'fa-receipt',        'label'=>'Struk Parkir'],
    ],
    'admin' => [
        ['id'=>'member',      'icon'=>'fa-id-card',        'label'=>'Kelola Member'],
        ['id'=>'zona',        'icon'=>'fa-map-marker-alt', 'label'=>'Kelola Zona'],
        ['id'=>'tarif-admin', 'icon'=>'fa-tags',           'label'=>'Atur Tarif'],
    ],
    'manajer' => [
        ['id'=>'dashboard', 'icon'=>'fa-tachometer-alt', 'label'=>'Dashboard'],
        ['id'=>'kapasitas', 'icon'=>'fa-th-large',       'label'=>'Kapasitas Zona'],
        ['id'=>'laporan',   'icon'=>'fa-history',        'label'=>'Laporan Transaksi'],
    ],
];
$thisMenus = $menus[$role] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Parking') ?> — Mall ERP</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/parking.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

<div id="toast" class="toast hidden"></div>
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="layout">

  <aside class="sidebar" id="sidebar">
    <button class="sidebar-close" onclick="closeSidebar()"><i class="fas fa-times"></i></button>

    <div class="sidebar-brand">
      <i class="fas fa-parking"></i>
      <span>Mall ERP</span>
    </div>

    <div class="sidebar-section-label">PARKING M04</div>

    <nav class="sidebar-nav">
      <?php foreach ($thisMenus as $item): ?>
        <a class="nav-item <?= ($currentNav === $item['id']) ? 'active' : '' ?>"
           href="#<?= $item['id'] ?>"
           onclick="scrollToSection('<?= $item['id'] ?>', this)">
          <i class="fas <?= $item['icon'] ?>"></i>
          <?= $item['label'] ?>
        </a>
      <?php endforeach; ?>

      <hr class="sep" style="margin:14px 0 8px">
      <div class="sidebar-section-label" style="padding:0 0 6px">NAVIGASI</div>

      <?php foreach ([
        'petugas'    => ['fa-sign-in-alt',  'Entry & Exit'],
        'pengunjung' => ['fa-tags',          'Info Tarif'],
        'admin'      => ['fa-id-card',       'Kelola Member'],
        'manajer'    => ['fa-tachometer-alt','Monitoring'],
      ] as $r => [$ic, $lb]): ?>
        <a class="nav-item <?= $role===$r?'active':'' ?>" href="index.php?role=<?= $r ?>">
          <i class="fas <?= $ic ?>"></i> <?= $lb ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <i class="fas <?= $roleIcon ?>"></i> Login sebagai:<br>
      <span class="role-badge"><?= $roleLabel ?></span>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle" onclick="openSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? '') ?></h1>
      <div class="topbar-right">
        <span class="capacity-pill">
          <i class="fas fa-car"></i>
          <span id="cap-text"><?= ($state['occupied']??0) ?>/<?= ($state['totalSlots']??0) ?></span>
        </span>
        <div class="topbar-user">
          <i class="fas <?= $roleIcon ?>"></i>
          <span><?= $roleLabel ?></span>
        </div>
      </div>
    </header>

    <div class="content-body">
