<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'Mall ERP' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Desain Dark Theme ala Dashboard HR */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0b192c; /* Warna background utama (Navy Gelap) */
            color: #e2e8f0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Area Sidebar Kiri */
        .sidebar {
            width: 250px;
            background-color: #06111e; /* Warna sidebar lebih gelap */
            display: flex;
            flex-direction: column;
            border-right: 1px solid #1e293b;
        }
        .sidebar-header {
            padding: 20px;
            font-size: 22px;
            font-weight: bold;
            color: #2dd4bf; /* Warna aksen Teal/Cyan */
            border-bottom: 1px solid #1e293b;
        }
        .menu-label {
            padding: 20px 20px 10px;
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .menu-item {
            padding: 12px 20px;
            color: #94a3b8;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }
        .menu-item:hover, .menu-item.active {
            background-color: #1e293b;
            color: #2dd4bf;
            border-left: 4px solid #2dd4bf;
        }

        /* Area Konten Kanan */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #0b192c;
        }
        .topbar {
            height: 65px;
            background-color: #06111e;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            border-bottom: 1px solid #1e293b;
        }
        .topbar-title {
            font-size: 20px;
            font-weight: bold;
            color: #f8fafc;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2dd4bf;
            background: rgba(45, 212, 191, 0.1);
            padding: 8px 15px;
            border-radius: 20px;
        }
        .content-area {
            padding: 25px;
            flex: 1;
            overflow-y: auto;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-building"></i> Mall ERP
        </div>
        <div class="menu-label"><?= isset($department_name) ? $department_name : 'MENU UTAMA' ?></div>
        
        <?php if(isset($menu_items) && is_array($menu_items)): ?>
            <?php foreach($menu_items as $item): ?>
                <a href="<?= $item['link'] ?>" class="menu-item <?= (isset($item['active_page']) && $item['active_page'] == 'buat_kontrak') ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i>
                    <?= $item['label'] ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title"><?= isset($page_title) ? $page_title : 'Dashboard' ?></div>
            <div class="user-info">
                <i class="fa-solid fa-user-circle"></i> <?= isset($user_name) ? $user_name : 'User' ?>
            </div>
        </div>

        <div class="content-area">
            <?= isset($content) ? $content : '' ?>
        </div>
    </div>

</body>
</html>