<?php
// includes/hr_header.php
// Shared header untuk semua halaman HR
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'HR Management' ?> — Mall Management System</title>
    <link rel="stylesheet" href="/asset/css/designSystem.css">
    <link rel="stylesheet" href="/asset/css/hr.css">
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
        <div class="sidebar-section-label">HR Management</div>
        <nav class="sidebar-nav">
            <a href="/pages/HR/dashboard.php" class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
            <a href="/pages/HR/pegawai/index.php" class="nav-item <?= in_array($current_page, ['index','tambah','edit']) && strpos($_SERVER['PHP_SELF'], 'pegawai') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Data Pegawai
            </a>
            <a href="/pages/HR/shift/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'shift') ? 'active' : '' ?>">
                <i class="fa-solid fa-calendar-days"></i> Jadwal Shift
            </a>
            <a href="/pages/HR/absensi/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'absensi') ? 'active' : '' ?>">
                <i class="fa-solid fa-fingerprint"></i> Absensi
            </a>
            <a href="/pages/HR/payroll/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'payroll') ? 'active' : '' ?>">
                <i class="fa-solid fa-money-bill-wave"></i> Payroll
            </a>
            <a href="/pages/HR/cuti/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'cuti') ? 'active' : '' ?>">
                <i class="fa-solid fa-umbrella-beach"></i> Cuti
            </a>
            <a href="/pages/HR/kpi/index.php" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'kpi') ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i> KPI
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="/public/logout.php" class="nav-item">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="topbar">
            <h1 class="page-title"><?= $page_title ?? 'HR Management' ?></h1>
            <div class="topbar-user">
                <i class="fa-solid fa-circle-user"></i>
                <span>HR Admin</span>
            </div>
        </div>
        <div class="content-body">
