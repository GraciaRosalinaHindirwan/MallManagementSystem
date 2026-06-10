<header
class="fixed top-0 left-0 right-0 z-40
bg-glass-fill
backdrop-blur-[15px]
border-b border-glass-stroke
shadow-sm
h-16
flex items-center
justify-between
px-4">

    <!-- LEFT -->
    <div class="flex items-center gap-4">

        <button
        id="hamburgerBtn"
        class="p-2 rounded-lg hover:bg-white/10 transition-colors">

            <span class="material-symbols-outlined text-primary">
                menu
            </span>

        </button>

        <h1
        class="text-xl font-semibold text-accent">
            <?= $title ?>
        </h1>

    </div>

    <!-- RIGHT -->
    <div class="flex items-center gap-2">

        <button
        class="p-2 rounded-full hover:bg-white/10 transition-colors relative">

            <span class="material-symbols-outlined text-on-surface-variant">
                notifications
            </span>

            <span
            class="absolute top-2 right-2
            w-2 h-2
            bg-danger
            rounded-full">
            </span>

        </button>

        <a href="../Pages/help.php"
        class="p-2 rounded-full hover:bg-white/10 transition-colors">

            <span class="material-symbols-outlined text-on-surface-variant">
                help
            </span>

</a>

    </div>

</header>