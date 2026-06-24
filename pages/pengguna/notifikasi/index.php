<?php

session_start();

// SIMULASI LOGIN (hapus saat integrasi login asli)
$_SESSION['user_id'] = 32;
$_SESSION['nama']    = 'Tester User';

require_once __DIR__ . '/../../../config/konek.php';
require_once __DIR__ . '/infrastructure/queries/mysql/MysqlNotificationQueryInApp.php';

$query = new MysqlNotificationQueryInApp($conn);

$user_id       = $_SESSION['user_id'] ?? 1;
$notifications = $query->get_by_user_id($user_id);

// ===============================
// STATISTIK
// ===============================

$total   = count($notifications);
$sent    = 0;
$pending = 0;
$failed  = 0;

foreach ($notifications as $notification) {
    $status = $notification->delivery_result->status->name ?? 'pending';
    switch ($status) {
        case 'sent':
            $sent++;
            break;
        case 'failed':
            $failed++;
            break;
        default:
            $pending++;
            break;
    }
}

// ===============================
// TEMPLATE VARIABLES
// ===============================

$page_title      = 'Notification Center';
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
?>

<style>
    /* ---- Row hover & zebra ---- */
    tbody tr {
        transition: background 0.15s ease;
    }

    tbody tr:hover {
        background: rgba(0, 212, 216, 0.06);
    }

    tbody tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.025);
    }

    tbody tr:nth-child(even):hover {
        background: rgba(0, 212, 216, 0.06);
    }

    /* ---- Divider antar baris ---- */
    tbody td {
        border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        vertical-align: middle;
    }

    tbody tr:last-child td {
        border-bottom: none;
    }

    /* ---- Kolom message truncate ---- */
    .td-message {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ---- Stat card accent warna per tipe ---- */
    .stat-card.accent-total {
        border-left-color: var(--accent, #00D4D8);
    }

    .stat-card.accent-sent {
        border-left-color: #22C55E;
    }

    .stat-card.accent-wait {
        border-left-color: #FFB62A;
    }

    .stat-card.accent-fail {
        border-left-color: #EF4444;
    }

    .stat-card.accent-total .stat-icon {
        background: rgba(0, 212, 216, 0.15);
        color: #00D4D8;
    }

    .stat-card.accent-sent .stat-icon {
        background: rgba(34, 197, 94, 0.15);
        color: #22C55E;
    }

    .stat-card.accent-wait .stat-icon {
        background: rgba(255, 182, 42, 0.15);
        color: #FFB62A;
    }

    .stat-card.accent-fail .stat-icon {
        background: rgba(239, 68, 68, 0.15);
        color: #EF4444;
    }

    /* ---- Card header ---- */
    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .card-header .card-title {
        margin-bottom: 0;
    }

    .record-count {
        font-size: 12px;
        color: rgba(245, 247, 250, 0.45);
        background: rgba(255, 255, 255, 0.06);
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 500;
    }

    /* ---- Empty state ---- */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 60px 20px;
        gap: 12px;
        color: rgba(245, 247, 250, 0.4);
        text-align: center;
    }

    .empty-state i {
        font-size: 2.5rem;
    }

    .empty-state p {
        font-size: 14px;
        margin: 0;
    }

    /* ---- Thead sticky ---- */
    thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }

    /* ---- ID chip ---- */
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

    /* ---- Channel & type pill ---- */
    .pill {
        display: inline-block;
        font-size: 11px;
        padding: 3px 9px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.07);
        color: rgba(245, 247, 250, 0.7);
    }

    /* ---- Date muted ---- */
    .td-date {
        font-size: 12px;
        color: rgba(245, 247, 250, 0.5);
        white-space: nowrap;
    }

    /* ---- Recipient name ---- */
    .recipient-name {
        display: flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
    }

    .recipient-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: rgba(0, 212, 216, 0.18);
        color: #00D4D8;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
</style>

<!-- ========================= -->
<!-- STATISTICS -->
<!-- ========================= -->

<div class="stats-grid">

    <div class="stat-card accent-total">
        <div class="stat-icon">
            <i class="fa-solid fa-bell"></i>
        </div>
        <div class="stat-info">
            <h3><?= $total ?></h3>
            <p>Total Notifikasi</p>
        </div>
    </div>

    <div class="stat-card accent-sent">
        <div class="stat-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-info">
            <h3><?= $sent ?></h3>
            <p>Terkirim</p>
        </div>
    </div>

    <div class="stat-card accent-wait">
        <div class="stat-icon">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-info">
            <h3><?= $pending ?></h3>
            <p>Pending</p>
        </div>
    </div>

    <div class="stat-card accent-fail">
        <div class="stat-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
            <h3><?= $failed ?></h3>
            <p>Gagal</p>
        </div>
    </div>

</div>

<!-- ========================= -->
<!-- NOTIFICATION TABLE -->
<!-- ========================= -->

<div class="card">

    <div class="card-header">
        <h2 class="card-title">
            <i class="fa-solid fa-bell" style="margin-right:8px; opacity:.7;"></i>
            Notification Center
        </h2>
        <?php if (!empty($notifications)): ?>
            <span class="record-count"><?= $total ?> record</span>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)) : ?>

        <div class="empty-state">
            <i class="fa-solid fa-bell-slash"></i>
            <p>Belum ada notifikasi untuk pengguna ini.</p>
        </div>

    <?php else : ?>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Pesan</th>
                        <th>Tipe</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Penerima</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($notifications as $notification) : ?>

                        <?php

                        $status = $notification->delivery_result->status->name ?? 'pending';

                        $badgeClass = match ($status) {
                            'sent'   => 'badge-success',
                            'failed' => 'badge-danger',
                            default  => 'badge-warning',
                        };

                        $recipientName = $notification->recipient->name ?? '-';
                        $initials      = strtoupper(implode('', array_map(
                            fn($w) => $w[0] ?? '',
                            array_slice(explode(' ', $recipientName), 0, 2)
                        )));

                        ?>

                        <tr>

                            <td>
                                <span class="id-chip">
                                    #<?= htmlspecialchars($notification->id) ?>
                                </span>
                            </td>

                            <td style="font-weight:500;">
                                <?= htmlspecialchars(
                                    $notification->notification_content->subject ?? '-'
                                ) ?>
                            </td>

                            <td class="td-message" title="<?= htmlspecialchars(
                                                                $notification->notification_content->body ?? ''
                                                            ) ?>">
                                <?= htmlspecialchars(
                                    $notification->notification_content->body ?? '-'
                                ) ?>
                            </td>

                            <td>
                                <span class="pill">
                                    <?= htmlspecialchars(
                                        $notification->notification_content->type->name ?? '-'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <span class="pill">
                                    <i class="fa-solid fa-satellite-dish"
                                        style="font-size:10px; margin-right:4px; opacity:.7;"></i>
                                    <?= htmlspecialchars(
                                        $notification->channel->name ?? '-'
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </td>

                            <td>
                                <div class="recipient-name">
                                    <div class="recipient-avatar">
                                        <?= htmlspecialchars($initials ?: '?') ?>
                                    </div>
                                    <?= htmlspecialchars($recipientName) ?>
                                </div>
                            </td>

                            <td class="td-date">
                                <?php if ($notification->created_at): ?>
                                    <i class="fa-regular fa-calendar"
                                        style="margin-right:4px; opacity:.5;"></i>
                                    <?= $notification->created_at->format('d M Y') ?><br>
                                    <span style="margin-left:16px; font-size:11px; opacity:.6;">
                                        <?= $notification->created_at->format('H:i') ?>
                                    </span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="detail.php?id=<?= urlencode($notification->id) ?>"
                                    class="btn btn-primary"
                                    style="font-size:12px; padding:6px 14px;">
                                    <i class="fa-solid fa-eye"></i>
                                    Detail
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php

$content = ob_get_clean();

// ===============================
// LOAD TEMPLATE
// ===============================

include __DIR__ . '/../../../includes/08_nav_template.php';

