<?php

$current_page = basename($_SERVER['PHP_SELF'], '.php');

if (!defined('BASE_URL')) {
    $project_root = realpath(__DIR__ . '/..');
    $doc_root     = realpath($_SERVER['DOCUMENT_ROOT']);
    $base = '';
    if ($doc_root && $project_root && strpos($project_root, $doc_root) === 0) {
        $base = substr($project_root, strlen($doc_root));
    }
    $base = str_replace('\\', '/', $base);
    define('BASE_URL', $base);
}

// Set default nilai kosong jika tidak didefinisikan oleh halaman pemanggil
$department_name = $department_name ?? 'Department ABC XYZ'; // Isi nama departmen di sini. Ganti kata yang diapit petik 2
$menu_items = $menu_items ?? [];  // Isi di sini untuk menu-menu yang ada di sidebar
// Contoh untuk sidebar (menu item untuk sidebar ya):
//$menu_items = [
//    ['icon' => 'fa-solid fa-gauge', 'label' => 'Dashboard', 'link' => BASE_URL . '/pages/Finance/dashboard.php', 'active_page' => 'dashboard'],
//    ['icon' => 'fa-solid fa-chart-pie', 'label' => 'Laporan Keuangan', 'link' => BASE_URL . '/pages/Finance/laporan/index.php', 'active_page' => 'laporan'],
//    ['icon' => 'fa-solid fa-file-invoice', 'label' => 'Invoice', 'link' => BASE_URL . '/pages/Finance/invoice/index.php', 'active_page' => 'invoice'],
//    ['icon' => 'fa-solid fa-receipt', 'label' => 'Transaksi', 'link' => BASE_URL . '/pages/Finance/transaksi/index.php', 'active_page' => 'transaksi'],
//];
$user_name = $user_name ?? 'User'; // Isi username di sini. Ganti kata yang diapit petik 2
$page_title = $page_title ?? 'Default Page Title'; // Isi judul halaman di sini. Ganti kata yang diapit petik 2
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? '[Judul Halaman1]' ?> — Mall Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/asset/css/designSystem.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/asset/css/template.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-building"></i>
                <span>Mall ERP</span>
            </div>
            <div class="sidebar-section-label"><?= htmlspecialchars($department_name) ?></div>
            <nav class="sidebar-nav">
                <?php if (empty($menu_items)): ?>
                    <!-- KOSONG: tidak menampilkan menu apapun -->
                    <!-- Nanti setiap departemen akan mengisi menu_items sendiri -->
                <?php else: ?>
                    <?php foreach ($menu_items as $item): ?>
                        <a href="<?= $item['link'] ?? '#' ?>" class="nav-item <?= ($current_page === ($item['active_page'] ?? '')) ? 'active' : '' ?>">
                            <i class="<?= $item['icon'] ?>"></i> <?= htmlspecialchars($item['label'] ?? '') ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="<?= BASE_URL ?>/public/logout.php" class="nav-item">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="topbar">
                <h1 class="page-title"><?= $page_title ?? '[Judul Halaman]' ?></h1>
                <div class="topbar-user">
                    <i class="fa-solid fa-circle-user"></i>
                    <span><?= htmlspecialchars($user_name) ?></span>
                </div>
            </div>
            <div class="content-body">
                <div class="container mt-4">
                    <?php
                    if (isset($content)) {
                        echo $content;
                    }
                    ?>
                </div>
                <?php require_once __DIR__ . '/footer.php'; ?>
            </div>
        </main>
    </div>
</body>

</html>