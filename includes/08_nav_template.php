<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Navbar Modul 08</title>
    <link rel="stylesheet" href="../public/asset/css/designSystem.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</head>

<style>
    body {
        font-family: var(--font-family);
        background-color: var(--text);
        margin: 0;
        padding: 0;
    }

    /* ── Offcanvas Menu Links ───────────────────────── */
    .menu-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        text-decoration: none;
        color: var(--text);
        border-radius: 8px;
        margin-bottom: 8px;
        transition: all 0.3s ease;
        font-size: var(--body);
    }

    .menu-link:hover {
        background-color: rgba(255, 182, 42, 0.1);
    }

    .menu-link.active {
        border: 2px solid var(--text-accent);
        background-color: transparent;
        color: var(--text-accent);
    }

    .menu-link.active svg path {
        fill: var(--text-accent);
    }

    /* ── Navbar ─────────────────────────────────────── */
    nav {
        background-color: var(--background);
        padding: 10px 16px;
        display: flex;
        align-items: center;
        position: relative;
        min-height: 60px;
    }

    .navbar-brand {
        color: var(--text-accent);
        font-size: var(--h2);
        font-weight: bold;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        pointer-events: none;
        max-width: calc(100% - 120px);
        overflow: hidden;
        text-overflow: ellipsis;
    }

    nav button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    nav button:focus {
        outline: none;
        box-shadow: none;
    }

    /* ── Offcanvas ──────────────────────────────────── */
    .offcanvas-header {
        color: var(--text-accent);
        font-size: var(--subheading);
        font-weight: bold;
        background-color: var(--background);
        justify-content: space-between;
        padding: 32px;
    }

    .offcanvas-header button svg {
        width: 24px;
        height: 24px;
        transition: width 0.2s, height 0.2s;
    }

    .offcanvas-header button {
        background: none !important;
        border: none;
        padding: 0;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    @media (max-width: 768px) {
        .offcanvas-header button svg {
            width: 20px;
            height: 20px;
        }
    }

    @media (max-width: 480px) {
        .offcanvas-header button svg {
            width: 16px;
            height: 16px;
        }
    }

    .offcanvas-body {
        background-color: var(--background);
        color: var(--text-accent);
        padding: 32px;
        font-size: var(--subheading);
    }

    .offcanvas-title {
        color: var(--text-accent);
        font-size: var(--subheading);
        font-weight: 600;
    }

    .btn-close {
        background-color: var(--text-accent);
    }

    .offcanvas-body svg {
        padding-right: 5px;
    }

    .offcanvas-body a {
        color: var(--text-accent);
    }

    /* ── Offcanvas Responsive ───────────────────────── */
    .offcanvas.offcanvas-start {
        width: 400px;
    }

    @media (max-width: 768px) {
        .offcanvas.offcanvas-start {
            width: 280px;
        }

        .offcanvas-header {
            padding: 20px;
        }

        .offcanvas-body {
            padding: 20px;
            font-size: var(--label);
        }

        .offcanvas-title {
            font-size: var(--body);
        }

        .menu-link {
            padding: 10px 12px;
            font-size: var(--label);
        }
    }

    @media (max-width: 480px) {
        .offcanvas.offcanvas-start {
            width: 75vw;
        }

        .offcanvas-header {
            padding: 16px;
        }

        .offcanvas-body {
            padding: 16px;
            font-size: var(--caption);
        }

        .offcanvas-title {
            font-size: var(--body);
        }

        .menu-link {
            padding: 10px 10px;
            font-size: var(--caption);
            gap: 8px;
        }

        .menu-link svg {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }
    }

    /* ── Sub Nav ────────────────────────────────────── */
    .sub-nav-container {
        display: flex;
        justify-content: center;
        width: 100%;
        padding: 0 16px;
        overflow: hidden;
    }

    .sub-nav {
        display: flex;
        background-color: var(--text);
        padding: 8px;
        border-radius: 20px;
        margin: 20px auto;
        gap: 6px;
        border: 1px solid #D9D9D9;
        align-items: center;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        flex-wrap: nowrap;
        width: fit-content;
        max-width: 100%;
    }

    .sub-nav::-webkit-scrollbar {
        display: none;
    }

    .sub-nav-item {
        padding: 10px 24px;
        text-decoration: none;
        color: black;
        font-size: var(--body);
        border-radius: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
        flex-shrink: 0;
        white-space: nowrap;
    }

    .sub-nav-item:hover {
        background-color: var(--background);
        color: var(--text);
    }

    .sub-nav-item.active {
        background-color: var(--background);
        color: var(--text);
        font-weight: 600;
    }

    /* ── Tablet (max 768px) ─────────────────────────── */
    @media (max-width: 768px) {

        .navbar-brand {
            font-size: var(--subheading);
            max-width: calc(100% - 100px);
        }

        .sub-nav-item {
            padding: 8px 18px;
            font-size: var(--label);
        }

        .sub-nav {
            margin: 14px auto;
        }
    }

    /* ── Mobile (max 480px) ─────────────────────────── */
    @media (max-width: 480px) {

        nav {
            padding: 10px 12px;
        }

        .navbar-brand {
            font-size: var(--label);
            max-width: calc(100% - 80px);
        }

        nav button svg {
            width: 30px;
            height: 30px;
        }

        .sub-nav-container {
            padding: 0 12px;
            justify-content: flex-start;
        }

        .sub-nav {
            margin: 12px 0;
            padding: 6px;
            gap: 4px;
            border-radius: 14px;
            width: 100%;
        }

        .sub-nav-item {
            padding: 8px 14px;
            font-size: var(--caption);
            border-radius: 6px;
        }
    }
</style>

<body>
    <nav>
        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasScrolling" aria-controls="offcanvasScrolling">
            <svg width="40" height="40" viewBox="0 0 61 61" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7.625 43.2083H53.375M7.625 30.5H53.375M7.625 17.7916H53.375" stroke="#FFB62A" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
        <span class="navbar-brand">Mall Management System</span>
    </nav>

    <div class="offcanvas offcanvas-start" data-bs-scroll="true" data-bs-backdrop="false" tabindex="-1" id="offcanvasScrolling" aria-labelledby="offcanvasScrollingLabel">
        <div class="offcanvas-header">
            <p class="offcanvas-title" id="offcanvasScrollingLabel">Mall Menu</p>
            <button type="button" data-bs-dismiss="offcanvas" aria-label="Close">
                <svg viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M21.4579 19.0208L5.00037 33.303C3.86456 34.2887 2.17666 34.2887 1.04085 33.303C-0.34687 32.0987 -0.346871 29.9448 1.04085 28.7405L10.1937 20.7974C12.4908 18.8039 12.4908 15.2383 10.1937 13.2448L1.04085 5.30178C-0.346869 4.09748 -0.34687 1.9435 1.04085 0.739202C2.17666 -0.246479 3.86456 -0.24648 5.00037 0.739202L21.4579 15.0214C22.0689 15.5518 22.4121 16.2711 22.4121 17.0211C22.4121 17.7711 22.0689 18.4904 21.4579 19.0208Z" fill="#FFB62A" />
                </svg>
            </button>
        </div>
        <div class="offcanvas-body">
            <a href="" class="menu-link active">
                <svg width="35" height="40" viewBox="0 0 35 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.3947 23.3333H7.59649V11.6667H11.3947V23.3333ZM18.9912 23.3333H15.193V6.66667H18.9912V23.3333ZM26.5877 23.3333H22.7895V16.6667H26.5877V23.3333ZM30.386 26.6667H3.79825V3.33333H30.386V26.8333M30.386 0H3.79825C1.70921 0 0 1.5 0 3.33333V26.6667C0 28.5 1.70921 30 3.79825 30H30.386C32.475 30 34.1842 28.5 34.1842 26.6667V3.33333C34.1842 1.5 32.475 0 30.386 0Z" fill="#FFB62A" />
                </svg>
                BI, Approval & Notification
            </a>
        </div>
    </div>

    <div class="sub-nav-container">
        <div class="sub-nav">
            <a href="#" class="sub-nav-item active">Dashboard</a>
            <a href="#" class="sub-nav-item">Approval</a>
            <a href="#" class="sub-nav-item">Laporan</a>
            <a href="#" class="sub-nav-item">Notifikasi</a>
        </div>
    </div>
</body>

</html>