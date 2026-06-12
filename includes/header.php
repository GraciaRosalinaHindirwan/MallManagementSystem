<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mall Management System - Finance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-dark: #021F42;
            --accent: #FFB62A;
            --text-accent: #FFB62A;
            --sidebar-bg: #011630;
        }
        body {
            background-color: var(--primary-dark);
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        nav {
            background-color: #011630;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 2px solid var(--accent);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        .navbar-brand {
            color: var(--accent);
            font-weight: bold;
            font-size: 22px;
        }
        .offcanvas-sidebar {
            width: 280px !important;
            background-color: var(--sidebar-bg) !important;
            border-right: 2px solid rgba(255,182,42,0.1) !important;
        }
        .content-wrapper {
            margin-top: 75px; /* Memberikan ruang agar tidak tertutup top navbar */
            padding: 30px;
            min-height: calc(100vh - 75px);
        }
        .nav-sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border-radius: 8px;
            margin: 2px 15px;
        }
        .nav-sidebar-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }
        .nav-sidebar-item.active {
            background-color: rgba(0, 207, 213, 0.15) !important;
            color: #00cfd5 !important;
            font-weight: 600;
        }
        .table-custom {
            width: 100%;
            background-color: #032b5c;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .table-custom th {
            background-color: #011630;
            color: var(--accent);
            padding: 15px;
            font-size: 14px;
        }
        .table-custom td {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #ffffff;
        }
    </style>
</head>
<body>
