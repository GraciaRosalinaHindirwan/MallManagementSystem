<?php
if (!isset($page)) {
    $page = '';
}
?>

<aside id="sidebar" class="fixed left-0 top-0 h-full w-[280px] z-50 bg-surface-container-lowest backdrop-blur-[25px]
border-r border-glass-stroke shadow-xl
flex flex-col py-stack-lg
transform -translate-x-full
transition-transform duration-300">

    <div class="px-6 mb-10">
        <h1 class="font-headline-h1 text-headline-h1 text-primary">
            Facility Hub
        </h1>

        <p class="text-on-surface-variant text-label-md">
            North Wing Sector
        </p>
    </div>

    <nav class="flex-1 space-y-1">

        <!-- <a href="Damage_Report.php"
            class="<?= $page == 'damage_report'
                        ? 'bg-primary-container/40 text-accent border-l-4 border-accent'
                        : 'text-on-surface-variant hover:bg-glass-fill hover:text-main' ?>
        px-4 py-3 flex items-center gap-3 transition-all">

            <span class="material-symbols-outlined">
                report_problem
            </span>

            <span>Damage Report</span>
        </a> -->

        <a href="Damage_List.php"
            class="<?= $page == 'damage_list'
                        ? 'bg-primary-container/40 text-accent border-l-4 border-accent'
                        : 'text-on-surface-variant hover:bg-glass-fill hover:text-main' ?>
        px-4 py-3 flex items-center gap-3 transition-all">

            <span class="material-symbols-outlined">
                confirmation_number
            </span>

            <span>Damage List</span>
        </a>

        <a href="Technician_Management.php"
            class="<?= $page == 'technician'
                        ? 'bg-primary-container/40 text-accent border-l-4 border-accent'
                        : 'text-on-surface-variant hover:bg-glass-fill hover:text-main' ?>
        px-4 py-3 flex items-center gap-3 transition-all">

            <span class="material-symbols-outlined">
                engineering
            </span>

            <span>Technicians</span>
        </a>

    </nav>

    <div class="px-4 mt-auto pt-4 border-t border-glass-stroke">

        <a href="Setting.php"
            class="<?= $page == 'setting'
                        ? 'bg-primary-container/40 text-accent border-l-4 border-accent'
                        : 'text-on-surface-variant hover:bg-glass-fill hover:text-main' ?>
        px-4 py-3 flex items-center gap-3 transition-all">

            <span class="material-symbols-outlined">
                settings
            </span>

            <span>Settings</span>
        </a>

        <a href="logout.php"
            class="text-on-surface-variant px-4 py-2 flex items-center gap-3 hover:text-danger">

            <span class="material-symbols-outlined">
                logout
            </span>

            <span>Logout</span>

        </a>

    </div>

</aside>

<div id="sidebarOverlay"
    class="fixed inset-0 bg-black/50 z-40 hidden">
</div>