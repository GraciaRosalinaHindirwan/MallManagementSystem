<?php
$role = $_SESSION['role'] ?? '';

// =============================================
// DEFINSIKAN ROLE GROUPS
// =============================================
$staff_roles = [
    'Finance Staff',
    'Purchasing Staff',
    'Facility Staff',
    'Tenant Staff'
];

$manager_roles = [
    'Finance Manager',
    'Purchasing Manager',
    'Facility Manager',
    'General Manager'
];

// =============================================
// TENTUKAN MENU BERDASARKAN ROLE
// =============================================
if (in_array($role, $staff_roles)) {
    // ========================
    // MENU UNTUK STAFF
    // ========================
    $menu_items = [
        [
            'icon' => 'fa-solid fa-file-circle-plus',
            'label' => 'Create Approval',
            'link' => 'createApproval.php',
            'active_page' => 'createApproval'
        ],
        [
            'icon' => 'fa-solid fa-list',
            'label' => 'My Approval',
            'link' => 'myApproval.php',
            'active_page' => 'myApproval'
        ],
        [
            'icon' => 'fa-solid fa-bell',
            'label' => 'Notifikasi',
            'link' => 'index.php',
            'active_page' => 'index'
        ]
    ];

    $page_title = 'Notification';
} elseif (in_array($role, $manager_roles)) {
    // ========================
    // MENU UNTUK MANAGER
    // ========================
    $menu_items = [
        [
            'icon' => 'fa-solid fa-gauge',
            'label' => 'Dashboard KPI',
            'link' => '../manager/08_dashboard.php',
            'active_page' => '08_dashboard'
        ],
        [
            'icon' => 'fa-solid fa-chart-line',
            'label' => 'Laporan',
            'link' => '../manager/08_laporan.php',
            'active_page' => '08_laporan'
        ],
        [
            'icon' => 'fa-solid fa-circle-check',
            'label' => 'Approval',
            'link' => 'approvalList.php',
            'active_page' => 'approvalList'
        ],
        [
            'icon' => 'fa-solid fa-clock-rotate-left',
            'label' => 'Audit Log',
            'link' => 'auditLog.php',
            'active_page' => 'auditLog'
        ],
        [
            'icon' => 'fa-solid fa-bell',
            'label' => 'Notifikasi',
            'link' => 'index.php',
            'active_page' => 'index'
        ]
    ];

    $page_title = 'Manager Notification';
} else {
    // ========================
    // MENU DEFAULT (Super Admin, Admin, dll)
    // ========================
    $menu_items = [
        [
            'icon' => 'fa-solid fa-chart-line',
            'label' => 'Dashboard KPI',
            'link' => '../manager/08_dashboard.php',
            'active_page' => '08_dashboard'
        ],
        [
            'icon' => 'fa-solid fa-file-alt',
            'label' => 'Laporan',
            'link' => '../manager/08_laporan.php',
            'active_page' => '08_laporan'
        ],
        [
            'icon' => 'fa-solid fa-check-circle',
            'label' => 'Approval',
            'link' => 'approvalList.php',
            'active_page' => 'approvalList'
        ],
        [
            'icon' => 'fa-solid fa-bell',
            'label' => 'Notifikasi',
            'link' => 'index.php',
            'active_page' => 'index'
        ]
    ];

    $page_title = 'Notification Center';
}

// =============================================
// VARIABEL LAINNYA
// =============================================
$user_name       = $_SESSION['nama'] ?? 'User';
$department_name = 'BI, Workflow & Notification';
