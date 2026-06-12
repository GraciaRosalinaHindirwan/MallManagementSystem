<?php
// Set default values jika variabel tidak didefinisikan di halaman pemanggil
$pageTitle   = $pageTitle ?? 'Mall ERP CS';
$currentMenu = $currentMenu ?? '';
$extraScript = $extraScript ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Poppins', 'sans-serif'] },
          fontSize: {
            'h1':         ['32px', { lineHeight: '1.25', fontWeight: '700' }],
            'h2':         ['24px', { lineHeight: '1.25', fontWeight: '600' }],
            'subheading': ['20px', { lineHeight: '1.25', fontWeight: '600' }],
            'body':       ['16px', { lineHeight: '1.6'  }],
            'label':      ['14px', { lineHeight: '1.5'  }],
            'caption':    ['12px', { lineHeight: '1.5'  }],
          },
          colors: {
            primary:          { DEFAULT: '#0B376D', dark: '#082A53' },
            secondary:        { DEFAULT: '#167E80', dark: '#0D4859' },
            accent:           '#00D4D8',
            background:       '#021F42',
            surface:          '#0B376D',
            'surface-raised': '#102F5C',
            success:          '#22C55E',
            danger:           '#EF4444',
            warning:          '#F59E0B',
            text: {
              DEFAULT: '#F5F7FA',
              muted:   'rgba(245,247,250,0.55)',
              accent:  '#FFB62A',
            },
            border: {
              DEFAULT: 'rgba(0,212,216,0.15)',
              strong:  'rgba(0,212,216,0.35)',
            },
          },
          borderRadius: { sm:'6px', md:'10px', lg:'16px', xl:'24px', full:'9999px' },
          boxShadow: {
            sm: '0 1px 3px rgba(0,0,0,0.3)',
            md: '0 4px 16px rgba(0,0,0,0.35)',
            lg: '0 8px 32px rgba(0,0,0,0.45)',
            accent: '0 0 20px rgba(0,212,216,0.2)',
          },
        },
      },
    };
  </script>
  
  <style type="text/tailwindcss">
    .cs-card   { @apply bg-surface border border-border rounded-lg p-6 shadow-md; }
    .cs-input  { @apply w-full px-4 py-2 bg-white/5 border border-border rounded-md text-label text-text placeholder:text-text/30 outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 transition-all; }
    .cs-btn    { @apply inline-flex items-center justify-center gap-2 px-5 py-2 text-label font-semibold rounded-md border border-transparent cursor-pointer transition-all; }
    .cs-nav-item   { @apply flex items-center gap-3 px-4 py-2.5 text-label text-text/70 rounded-md transition-all hover:bg-white/5 hover:text-text border-l-2 border-transparent; }
    .cs-nav-active { @apply bg-accent/20 text-accent border-l-2 border-accent font-medium; }
  </style>
  <title><?= htmlspecialchars($pageTitle) ?></title>
</head>
<body class="bg-background text-text font-sans min-h-screen">

<div class="flex min-h-screen">
  <aside class="w-60 min-h-screen bg-primary-dark flex flex-col flex-shrink-0 fixed top-0 left-0 z-50">
    <div class="px-5 py-5 border-b border-border">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-md bg-accent/20 flex items-center justify-center">
          <i class="bi bi-headset text-accent text-base"></i>
        </div>
        <div>
          <p class="text-label font-semibold text-text leading-none">Customer Service</p>
          <p class="text-caption text-text/50 mt-0.5">Mall ERP</p>
        </div>
      </div>
    </div>
    
    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
      <p class="text-caption text-text/30 font-semibold uppercase tracking-widest px-4 pt-2 pb-1">Dashboard</p>
      <a href="dashboard.php" class="cs-nav-item <?= $currentMenu === 'dashboard' ? 'cs-nav-active' : '' ?>"><i class="bi bi-speedometer2 w-4"></i> Dashboard</a>

      <p class="text-caption text-text/30 font-semibold uppercase tracking-widest px-4 pt-4 pb-1">Informasi</p>
      <a href="cari-tenant.php" class="cs-nav-item <?= $currentMenu === 'tenant' ? 'cs-nav-active' : '' ?>"><i class="bi bi-shop w-4"></i> Cari Tenant</a>
      <a href="fasilitas.php"   class="cs-nav-item <?= $currentMenu === 'fasilitas' ? 'cs-nav-active' : '' ?>"><i class="bi bi-geo-alt w-4"></i> Fasilitas Umum</a>
      <a href="event.php"       class="cs-nav-item <?= $currentMenu === 'event' ? 'cs-nav-active' : '' ?>"><i class="bi bi-calendar-event w-4"></i> Jadwal Event</a>

      <p class="text-caption text-text/30 font-semibold uppercase tracking-widest px-4 pt-4 pb-1">Keluhan</p>
      <a href="tiket.php"      class="cs-nav-item <?= $currentMenu === 'tiket' ? 'cs-nav-active' : '' ?>"><i class="bi bi-ticket-perforated w-4"></i> Semua Tiket</a>
      <a href="tiket-buat.php" class="cs-nav-item <?= $currentMenu === 'tiket-buat' ? 'cs-nav-active' : '' ?>"><i class="bi bi-plus-circle w-4"></i> Buat Tiket Baru</a>
      <a href="sla-breach.php" class="cs-nav-item <?= $currentMenu === 'sla-breach' ? 'cs-nav-active' : '' ?>"><i class="bi bi-exclamation-triangle w-4"></i> SLA Breach</a>

      <p class="text-caption text-text/30 font-semibold uppercase tracking-widest px-4 pt-4 pb-1">Barang Hilang</p>
      <a href="barang-temuan.php" class="cs-nav-item <?= $currentMenu === 'barang-temuan' ? 'cs-nav-active' : '' ?>"><i class="bi bi-bag-check w-4"></i> Barang Temuan</a>
      <a href="barang-hilang.php" class="cs-nav-item <?= $currentMenu === 'barang-hilang' ? 'cs-nav-active' : '' ?>"><i class="bi bi-bag-x w-4"></i> Laporan Kehilangan</a>
      <a href="feedback.php"      class="cs-nav-item <?= $currentMenu === 'feedback' ? 'cs-nav-active' : '' ?>"><i class="bi bi-star w-4"></i> Rating & Feedback</a>
    </nav>
    
    <div class="px-4 py-4 border-t border-border">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-secondary flex items-center justify-center text-caption font-bold flex-shrink-0">
          <?= isset($_SESSION['initials']) ? htmlspecialchars($_SESSION['initials']) : 'CS' ?>
        </div>
        <div class="min-w-0">
          <p class="text-label font-medium text-text truncate leading-none">
            <?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Petugas CS' ?>
          </p>
          <p class="text-caption text-text/50 mt-0.5">Customer Service</p>
        </div>
        <a href="../../public/logout.php" class="ml-auto text-text/40 hover:text-danger transition-colors">
          <i class="bi bi-box-arrow-right text-base"></i>
        </a>
      </div>
    </div>
  </aside>

  <div class="ml-60 flex-1 flex flex-col">
    <header class="sticky top-0 z-40 bg-primary/80 backdrop-blur-md border-b border-border px-6 py-4 flex items-center justify-between">
      <div>
        <h1 class="text-subheading font-semibold"><?= htmlspecialchars(explode('—', $pageTitle)[0]) ?></h1>
        <p class="text-caption text-text/50">Mall ERP › Customer Service </p>
      </div>
      <div class="flex items-center gap-4">
        <div class="relative">
          <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-text/40 text-label"></i>
          <input type="text" placeholder="Cari tenant, tiket..." class="pl-9 pr-4 py-1.5 bg-white/5 border border-border rounded-md text-label text-text placeholder:text-text/30 outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 w-52 transition-all" />
        </div>
        <button class="relative text-text/60 hover:text-text transition-colors">
          <i class="bi bi-bell text-lg"></i>
          <span class="absolute -top-1 -right-1 w-2 h-2 bg-danger rounded-full"></span>
        </button>
      </div>
    </header>

    <main class="flex-1 p-6 space-y-6">
      <?= $content ?? '' ?>
    </main>

    <footer class="border-t border-border px-6 py-3 text-center">
      <p class="text-caption text-text/30">Mall ERP System — Customer Service Module © 2026</p>
    </footer>
  </div>
</div>

<?= $extraScript ?? '' ?>

</body>
</html>