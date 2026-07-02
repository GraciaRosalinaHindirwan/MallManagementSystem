<?php

session_start();

// SIMULASI LOGIN (hapus saat integrasi login asli)
$_SESSION['user_id'] = 1;
$_SESSION['nama']    = 'Tester User';

require_once __DIR__ . '/../../../config/konek.php';
require_once __DIR__ . '/infrastructure/queries/mysql/MysqlNotificationQueryInApp.php';

$query = new MysqlNotificationQueryInApp($conn);

$id           = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$notification = $query->get_by_id($id);

// ===============================
// TEMPLATE VARIABLES
// ===============================

$page_title      = 'Detail Notifikasi';
$user_name       = $_SESSION['nama'] ?? 'User';
$department_name = 'BI, Workflow & Notification';

$menu_items = [
    [
        'icon'        => 'fa-solid fa-chart-line',
        'label'       => 'Dashboard KPI',
        'link'        => '#',
        'active_page' => 'dashboard',
    ],
    [
        'icon'        => 'fa-solid fa-file-alt',
        'label'       => 'Laporan',
        'link'        => '#',
        'active_page' => 'laporan',
    ],
    [
        'icon'        => 'fa-solid fa-check-circle',
        'label'       => 'Approval',
        'link'        => '#',
        'active_page' => 'approval',
    ],
    [
        'icon'        => 'fa-solid fa-bell',
        'label'       => 'Notifikasi',
        'link'        => 'index.php',
        'active_page' => 'index',
    ],
];

// ===============================
// CONTENT BUFFER
// ===============================

ob_start();

// --- Helper: status ---
$status     = $notification->delivery_result->status->name ?? 'pending';
$badgeClass = match ($status) {
    'sent'   => 'badge-success',
    'failed' => 'badge-danger',
    default  => 'badge-warning',
};

// --- Helper: inisial avatar ---
$recipientName = $notification->recipient->name ?? '-';
$initials      = strtoupper(implode('', array_map(
    fn($w) => $w[0] ?? '',
    array_slice(explode(' ', $recipientName), 0, 2)
)));
?>

<style>
    /* ---- Back link ---- */
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: rgba(245, 247, 250, 0.55);
        text-decoration: none;
        margin-bottom: 20px;
        transition: color 0.15s;
    }
    .back-link:hover {
        color: var(--accent, #00D4D8);
    }

    /* ---- Detail card layout ---- */
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
    }

    .detail-row {
        display: flex;
        flex-direction: column;
        gap: 5px;
        padding: 16px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }
    .detail-row:nth-child(odd) {
        border-right: 1px solid rgba(255, 255, 255, 0.06);
    }
    /* baris yang span full width */
    .detail-row.full {
        grid-column: 1 / -1;
        border-right: none;
    }
    .detail-row:last-child,
    .detail-row.full:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: rgba(245, 247, 250, 0.4);
    }
    .detail-value {
        font-size: 14px;
        color: var(--text, #F5F7FA);
        line-height: 1.6;
    }
    .detail-value.mono {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        color: rgba(245, 247, 250, 0.65);
    }

    /* ---- Hero header in card ---- */
    .detail-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding: 20px 20px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        margin-bottom: 0;
    }
    .detail-hero-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .hero-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(0, 212, 216, 0.15);
        color: #00D4D8;
        font-size: 16px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .hero-subject {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 2px;
    }
    .hero-meta {
        font-size: 12px;
        color: rgba(245, 247, 250, 0.45);
        margin: 0;
    }

    /* ---- Message body box ---- */
    .message-body {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        padding: 14px 16px;
        font-size: 14px;
        line-height: 1.7;
        color: rgba(245, 247, 250, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    /* ---- Error box ---- */
    .error-box {
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 13px;
        color: #EF4444;
    }

    /* ---- Pill & id chip (sama dengan index) ---- */
    .pill {
        display: inline-block;
        font-size: 11px;
        padding: 3px 9px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.07);
        color: rgba(245, 247, 250, 0.7);
    }
    .id-chip {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.07);
        color: rgba(245, 247, 250, 0.55);
        letter-spacing: .03em;
    }

    /* ---- Timestamp ---- */
    .ts-main  { font-size: 14px; }
    .ts-sub   { font-size: 11px; color: rgba(245, 247, 250, 0.4); }

    /* ---- Not found ---- */
    .not-found-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 80px 20px;
        gap: 14px;
        color: rgba(245, 247, 250, 0.35);
        text-align: center;
    }
    .not-found-state i { font-size: 3rem; }
    .not-found-state p { font-size: 14px; margin: 0; }

    /* ---- Responsive ---- */
    @media (max-width: 576px) {
        .detail-grid { grid-template-columns: 1fr; }
        .detail-row:nth-child(odd) { border-right: none; }
        .detail-row { padding: 14px 16px; }
    }
</style>

<!-- Back link -->
<a href="index.php" class="back-link">
    <i class="fa-solid fa-arrow-left"></i>
    Kembali ke Notification Center
</a>

<?php if (!$notification) : ?>

    <div class="card">
        <div class="not-found-state">
            <i class="fa-solid fa-circle-exclamation"></i>
            <p>Notifikasi tidak ditemukan.</p>
            <a href="index.php" class="btn btn-primary" style="margin-top:8px;">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

<?php else : ?>

    <div class="card" style="padding:0; overflow:hidden;">

        <!-- HERO: Avatar + Subject + badge status -->
        <div class="detail-hero">
            <div class="detail-hero-left">
                <div class="hero-avatar"><?= htmlspecialchars($initials ?: '?') ?></div>
                <div>
                    <p class="hero-subject">
                        <?= htmlspecialchars($notification->notification_content->subject ?? '-') ?>
                    </p>
                    <p class="hero-meta">
                        Kepada: <?= htmlspecialchars($recipientName) ?>
                        &nbsp;·&nbsp;
                        <?= htmlspecialchars($notification->recipient->email ?? '-') ?>
                    </p>
                </div>
            </div>
            <span class="badge <?= $badgeClass ?>" style="font-size:12px; padding:5px 14px;">
                <?php
                $statusIcon = match ($status) {
                    'sent'   => 'fa-circle-check',
                    'failed' => 'fa-circle-xmark',
                    default  => 'fa-clock',
                };
                ?>
                <i class="fa-solid <?= $statusIcon ?>" style="margin-right:5px;"></i>
                <?= ucfirst($status) ?>
            </span>
        </div>

        <!-- DETAIL GRID -->
        <div class="detail-grid">

            <!-- ID -->
            <div class="detail-row">
                <span class="detail-label">ID</span>
                <span class="detail-value">
                    <span class="id-chip">#<?= htmlspecialchars($notification->id) ?></span>
                </span>
            </div>

            <!-- Notification ID -->
            <div class="detail-row">
                <span class="detail-label">Notification ID</span>
                <span class="detail-value mono">
                    <?= htmlspecialchars($notification->notification_id) ?>
                </span>
            </div>

            <!-- Type -->
            <div class="detail-row">
                <span class="detail-label">Tipe</span>
                <span class="detail-value">
                    <span class="pill">
                        <i class="fa-solid fa-tag" style="font-size:10px; margin-right:4px; opacity:.6;"></i>
                        <?= htmlspecialchars($notification->notification_content->type->name ?? '-') ?>
                    </span>
                </span>
            </div>

            <!-- Channel -->
            <div class="detail-row">
                <span class="detail-label">Channel</span>
                <span class="detail-value">
                    <span class="pill">
                        <i class="fa-solid fa-satellite-dish" style="font-size:10px; margin-right:4px; opacity:.6;"></i>
                        <?= htmlspecialchars($notification->channel->name ?? '-') ?>
                    </span>
                </span>
            </div>

            <!-- Created At -->
            <div class="detail-row">
                <span class="detail-label">Dibuat</span>
                <div class="detail-value">
                    <?php if ($notification->created_at): ?>
                        <span class="ts-main">
                            <?= $notification->created_at->format('d M Y') ?>
                        </span><br>
                        <span class="ts-sub">
                            <i class="fa-regular fa-clock" style="margin-right:3px;"></i>
                            <?= $notification->created_at->format('H:i:s') ?>
                        </span>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sent At -->
            <div class="detail-row">
                <span class="detail-label">Terkirim Pada</span>
                <div class="detail-value">
                    <?php if ($notification->get_sent_at()): ?>
                        <span class="ts-main">
                            <?= $notification->get_sent_at()->format('d M Y') ?>
                        </span><br>
                        <span class="ts-sub">
                            <i class="fa-regular fa-clock" style="margin-right:3px;"></i>
                            <?= $notification->get_sent_at()->format('H:i:s') ?>
                        </span>
                    <?php else: ?>
                        <span style="color:rgba(245,247,250,0.3); font-size:13px;">Belum terkirim</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Message body — full width -->
            <div class="detail-row full">
                <span class="detail-label">Isi Pesan</span>
                <div class="message-body">
                    <?= nl2br(htmlspecialchars($notification->notification_content->body ?? '-')) ?>
                </div>
            </div>

            <!-- Error Message — full width, hanya tampil jika ada error -->
            <?php
            $errorMsg = $notification->get_error_messsage() ?? null;
            if ($errorMsg) : ?>
            <div class="detail-row full">
                <span class="detail-label">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-right:4px; color:#EF4444;"></i>
                    Error
                </span>
                <div class="error-box"><?= htmlspecialchars($errorMsg) ?></div>
            </div>
            <?php endif; ?>

        </div>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();

include __DIR__ . '/../../../includes/08_nav_template.php';
?>
