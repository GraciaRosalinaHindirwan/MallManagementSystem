<?php
// session_start();

// if(!isset($_SESSION['user_id'])){

//     header("Location: Login.php");
//     exit();
// }

$title = "Damage List";
$page = "damage_list";

include '../Includes/head.php';
include '../Config/konek.php';

if (isset($_GET['close'])) {

    $report_id = (int)$_GET['close'];

    // cari work order dan teknisi yang terkait
    $woQuery = mysqli_query(
        $conn,
        "
        SELECT
            work_order_id,
            technician_id
        FROM work_orders
        WHERE report_id = $report_id
        LIMIT 1
        "
    );

    $wo = mysqli_fetch_assoc($woQuery);

    if ($wo) {

        $work_order_id = $wo['work_order_id'];
        $technician_id = $wo['technician_id'];

        // kembalikan teknisi menjadi available
        mysqli_query(
            $conn,
            "
            UPDATE technicians
            SET status='Available'
            WHERE technician_id=$technician_id
            "
        );

        // tandai work order selesai
        mysqli_query(
            $conn,
            "
            UPDATE work_orders
            SET work_status='Completed'
            WHERE work_order_id=$work_order_id
            "
        );

        // simpan activity timeline
        mysqli_query(
            $conn,
            "
            INSERT INTO work_order_activities
            (
                work_order_id,
                activity_type,
                activity_note,
                created_at
            )
            VALUES
            (
                $work_order_id,
                'Closed',
                'Ticket closed by Facility Manager',
                NOW()
            )
            "
        );
    }

    // tutup ticket
    mysqli_query(
        $conn,
        "
        UPDATE damage_reports
        SET status='Closed'
        WHERE report_id=$report_id
        "
    );

    header("Location: Damage_List.php");
    exit();
}

$tickets = mysqli_query(
    $conn,
    "
SELECT
dr.*,

wo.work_order_id,
wo.work_order_number,
wo.work_status,
wo.sla_target,
wo.assigned_at,

t.full_name AS technician_name,
t.photo

FROM damage_reports dr

LEFT JOIN work_orders wo
ON dr.report_id = wo.report_id

LEFT JOIN technicians t
ON wo.technician_id = t.technician_id

ORDER BY dr.created_at DESC
"
);

$ticketDetails = [];

$detailQuery = mysqli_query(
    $conn,
    "
SELECT

dr.*,

wo.work_order_id,
wo.work_order_number,
wo.work_status,
wo.required_skill,
wo.sla_target,
wo.assigned_at,

t.full_name AS technician_name,
t.specialization

FROM damage_reports dr

LEFT JOIN work_orders wo
ON dr.report_id = wo.report_id

LEFT JOIN technicians t
ON wo.technician_id=t.technician_id
"
);

$timelineData = [];

$timelineQuery = mysqli_query(
    $conn,
    "
SELECT
woa.*,
wo.report_id
FROM work_order_activities woa
LEFT JOIN work_orders wo
ON woa.work_order_id = wo.work_order_id
ORDER BY woa.created_at ASC
"
);

while ($row = mysqli_fetch_assoc($timelineQuery)) {

    $timelineData[$row['report_id']][] = $row;
}

while ($row = mysqli_fetch_assoc($detailQuery)) {

    $ticketDetails[$row['report_id']] = $row;
}

$criticalCount = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "
SELECT COUNT(*) total
FROM damage_reports
WHERE priority='Critical'
AND status NOT IN ('Resolved','Closed')
"
    )
)['total'];

$awaitingCount = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "
SELECT COUNT(*) total
FROM damage_reports
WHERE status='Open'
"
    )
)['total'];

$completedToday = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "
SELECT COUNT(*) total
FROM damage_reports
WHERE status='Resolved'
AND DATE(created_at)=CURDATE()
"
    )
)['total'];

$totalTickets = mysqli_num_rows($tickets);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DAMAGE LIST</title>
</head>

<body>
    <?php include '../Includes/sidebar.php'; ?>
    <?php include '../Includes/topbar.php'; ?>
    <main class="mt-16 p-gutter h-[calc(100vh-64px)] overflow-y-auto custom-scrollbar">
        <div class="flex justify-between items-end mb-stack-lg">
            <div>
                <h2 class="font-headline-h2 text-headline-h2 text-text-main mb-1">
                    Incoming Tickets
                </h2>
                <nav class="flex text-caption-sm text-on-surface-variant gap-2">
                    <span>Operations</span>
                    /
                    <span class="text-accent">
                        Tickets Queue
                    </span>
                </nav>
            </div>
        </div>
        <div class="glass-card rounded-xl overflow-hidden mb-gutter">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-high/60">
                        <tr>
                            <th class="px-6 py-4">Ticket #</th>
                            <th class="px-6 py-4">Asset Name</th>
                            <th class="px-6 py-4">Location</th>
                            <th class="px-6 py-4">Priority</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Technician</th>
                            <th class="px-6 py-4">Created</th>
                            <th class="px-6 py-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-glass-stroke">
                        <?php while ($row = mysqli_fetch_assoc($tickets)): ?>
                            <tr
                                class="hover:bg-white/5 transition-colors cursor-pointer group"
                                onclick="openDrawer(<?= $row['report_id'] ?>)">
                                <td class="px-6 py-4 font-semibold text-accent">
                                    <?= $row['ticket_id'] ?>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-on-surface font-medium">
                                            <?= htmlspecialchars($row['asset_name']) ?>
                                        </span>
                                        <span class="text-caption-sm text-on-surface-variant">
                                            <?= htmlspecialchars($row['damage_type']) ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-on-surface-variant">
                                    <?= htmlspecialchars($row['location']) ?>
                                    <?= htmlspecialchars($row['floor_name']) ?>
                                </td>

                                <td class="px-6 py-4">
                                    <?php
                                    $priorityColor = "";
                                    switch ($row['priority']) {
                                        case "Critical":
                                            $priorityColor = "danger";
                                            break;

                                        case "High":
                                            $priorityColor = "text-accent";
                                            break;

                                        case "Medium":
                                            $priorityColor = "success";
                                            break;

                                        default:
                                            $priorityColor = "secondary";
                                    }
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-caption-sm font-semibold bg-<?= $priorityColor ?>/20 text-<?= $priorityColor ?> border border-<?= $priorityColor ?>/30 inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        <?= $row['priority'] ?>
                                    </span>

                                </td>

                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-caption-sm font-semibold bg-primary-container/40 text-on-primary-container border border-primary-container/50">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <?php if (!empty($row['technician_name'])): ?>
                                            <div class="w-8 h-8 rounded-full bg-surface-variant flex items-center justify-center text-xs font-bold">
                                                <?= strtoupper(substr($row['technician_name'], 0, 1)) ?>
                                            </div>
                                            <span class="text-on-surface-variant">
                                                <?= $row['technician_name'] ?>
                                            </span>
                                        <?php else: ?>
                                            <div class="w-8 h-8 rounded-full bg-surface-variant flex items-center justify-center text-xs font-bold">
                                                UC
                                            </div>

                                            <span class="text-on-surface-variant">
                                                Unassigned
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="px-6 py-4 w-48">
                                    <?php
                                    $percent = 100;
                                    if (!empty($row['sla_target'])) {
                                        $total = strtotime($row['sla_target']) - strtotime($row['created_at']);
                                        $remain = strtotime($row['sla_target']) - time();
                                        if ($total > 0) {
                                            $percent = max(
                                                0,
                                                min(
                                                    100,
                                                    ($remain / $total) * 100
                                                )
                                            );
                                        }
                                    }
                                    ?>
                                    <div class="flex flex-col gap-1">
                                        <div class="flex justify-between text-caption-sm">
                                            <span class="text-accent font-bold">
                                                <?= round($percent) ?>%
                                            </span>

                                            <span class="text-on-surface-variant">
                                                SLA
                                            </span>
                                        </div>

                                        <div class="h-1.5 w-full bg-primary-dark rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-gradient-to-r from-secondary to-accent"
                                                style="width:<?= round($percent) ?>%">
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-caption-sm text-on-surface-variant">
                                    <?= date(
                                        'd M Y H:i',
                                        strtotime($row['created_at'])
                                    ) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 flex items-center justify-between border-t border-glass-stroke bg-surface-container-low/40">
                <span class="text-caption-sm text-on-surface-variant">
                    Showing 1 to <?= $totalTickets ?> of <?= $totalTickets ?> results
                </span>
                <div class="flex items-center gap-2">
                    <button
                        class="p-2 rounded opacity-30 cursor-not-allowed"
                        disabled>
                        <span class="material-symbols-outlined">
                            chevron_left
                        </span>
                    </button>
                    <button
                        class="w-8 h-8 rounded bg-accent text-primary-dark font-bold text-xs">
                        1
                    </button>
                    <button
                        class="p-2 rounded opacity-30 cursor-not-allowed"
                        disabled>
                        <span class="material-symbols-outlined">
                            chevron_right
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-gutter">
            <!-- Critical Tickets -->
            <div class="glass-card p-4 rounded-xl flex items-center gap-4">
                <div class="p-3 rounded-lg bg-danger/10 text-danger border border-danger/20">
                    <span class="material-symbols-outlined">
                        warning
                    </span>
                </div>
                <div>
                    <p class="text-caption-sm text-on-surface-variant">
                        Critical Tickets
                    </p>
                    <p class="text-subheading font-bold text-text-main">
                        <?= $criticalCount ?>
                    </p>
                </div>
            </div>

            <!-- Awaiting Response -->
            <div class="glass-card p-4 rounded-xl flex items-center gap-4">
                <div class="p-3 rounded-lg bg-accent/10 text-accent border border-accent/20">
                    <span class="material-symbols-outlined">
                        pending_actions
                    </span>
                </div>
                <div>
                    <p class="text-caption-sm text-on-surface-variant">
                        Awaiting Response
                    </p>
                    <p class="text-subheading font-bold text-text-main">
                        <?= $awaitingCount ?>
                    </p>
                </div>
            </div>

            <!-- Completed Today -->
            <div class="glass-card p-4 rounded-xl flex items-center gap-4">
                <div class="p-3 rounded-lg bg-success/10 text-success border border-success/20">
                    <span class="material-symbols-outlined">
                        verified
                    </span>
                </div>
                <div>
                    <p class="text-caption-sm text-on-surface-variant">
                        Completed Today
                    </p>
                    <p class="text-subheading font-bold text-text-main">
                        <?= $completedToday ?>
                    </p>
                </div>
            </div>

            <!-- Total Tickets -->
            <div class="glass-card p-4 rounded-xl flex items-center gap-4">
                <div class="p-3 rounded-lg bg-secondary/10 text-secondary border border-secondary/20">
                    <span class="material-symbols-outlined">
                        confirmation_number
                    </span>
                </div>
                <div>
                    <p class="text-caption-sm text-on-surface-variant">
                        Total Tickets
                    </p>
                    <p class="text-subheading font-bold text-text-main">
                        <?= $totalTickets ?>
                    </p>
                </div>
            </div>
        </div>
    </main>
    <!-- Backdrop -->
    <div
        id="drawerOverlay"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 hidden"
        onclick="closeDrawer()">
    </div>

    <!-- Detail Drawer -->
    <aside
        id="detailDrawer"
        class="fixed top-0 right-0 h-full w-full md:w-[520px] bg-surface-container-high border-l border-glass-stroke shadow-2xl z-50 transform translate-x-full transition-transform duration-300 overflow-y-auto custom-scrollbar">

        <!-- HEADER -->
        <div class="sticky top-0 bg-surface-container-high border-b border-glass-stroke p-6 z-10">
            <div class="flex justify-between items-start">
                <div>
                    <span
                        id="drawerTicketID"
                        class="inline-flex px-3 py-1 rounded-full bg-accent/10 text-accent text-xs font-bold">
                        TK-0000
                    </span>

                    <h3
                        id="drawerAssetName"
                        class="mt-3 text-2xl font-bold text-text-main">
                        Asset Name
                    </h3>
                </div>

                <button
                    onclick="closeDrawer()"
                    class="p-2 rounded-lg hover:bg-white/5">
                    <span class="material-symbols-outlined">
                        close
                    </span>
                </button>
            </div>

            <div class="flex gap-2 mt-4">
                <span
                    id="drawerPriority"
                    class="px-3 py-1 rounded-full bg-danger/10 text-danger text-xs font-bold">
                    Critical
                </span>
                <span
                    id="drawerStatus"
                    class="px-3 py-1 rounded-full bg-success/10 text-success text-xs font-bold">
                    Open
                </span>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="p-6 space-y-6">
            <!-- PHOTO -->
            <section>
                <h4
                    class="text-caption-sm font-bold text-accent uppercase tracking-widest mb-3">
                    Photo Evidence
                </h4>

                <div class="glass-card rounded-xl overflow-hidden">
                    <img
                        id="drawerPhoto"
                        src="../Uploads/no-image.jpg"
                        class="w-full h-64 object-cover">
                </div>
            </section>

            <!-- ASSET DETAILS -->
            <section>
                <h4 class="text-caption-sm font-bold text-accent uppercase tracking-widest mb-3">
                    Asset Details
                </h4>

                <div class="glass-card rounded-xl p-5">

                    <div class="grid grid-cols-2 gap-5">

                        <div>
                            <p class="text-caption-sm text-on-surface-variant">
                                Asset Code
                            </p>
                            <p id="drawerAssetCode" class="font-semibold">
                                -
                            </p>
                        </div>

                        <div>
                            <p class="text-caption-sm text-on-surface-variant">
                                Category
                            </p>
                            <p id="drawerCategory" class="font-semibold">
                                -
                            </p>
                        </div>

                        <div>
                            <p class="text-caption-sm text-on-surface-variant">
                                Location
                            </p>
                            <p id="drawerLocation" class="font-semibold">
                                -
                            </p>
                        </div>

                        <div>
                            <p class="text-caption-sm text-on-surface-variant">
                                Damage Type
                            </p>
                            <p id="drawerDamageType" class="font-semibold">
                                -
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h4 class="text-caption-sm font-bold text-accent uppercase tracking-widest mb-3">
                    SLA Countdown
                </h4>

                <div class="glass-card rounded-xl p-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-on-surface-variant">
                            Remaining SLA
                        </span>

                        <span
                            id="drawerSLA"
                            class="font-bold text-danger">
                            --
                        </span>
                    </div>

                    <div class="h-3 bg-primary-dark rounded-full overflow-hidden">
                        <div
                            id="slaBar"
                            class="h-full bg-gradient-to-r from-danger via-secondary to-success"
                            style="width:100%">
                        </div>
                    </div>
                </div>
            </section>

            <!-- DESCRIPTION -->
            <section>
                <h4
                    class="text-caption-sm font-bold text-accent uppercase tracking-widest mb-3">
                    Damage Description
                </h4>

                <div class="glass-card rounded-xl p-4">
                    <p
                        id="drawerDescription"
                        class="leading-relaxed text-on-surface-variant">
                        -
                    </p>
                </div>
            </section>

            <!-- TECHNICIAN -->
            <section>
                <h4
                    class="text-caption-sm font-bold text-accent uppercase tracking-widest mb-3">
                    Assigned Technician
                </h4>

                <div class="glass-card rounded-xl p-4">
                    <div class="flex items-center gap-4">
                        <div
                            id="techAvatar"
                            class="w-12 h-12 rounded-full bg-accent text-primary-dark flex items-center justify-center font-bold">
                            T
                        </div>

                        <div>
                            <p
                                id="drawerTechnician"
                                class="font-semibold">
                                Unassigned
                            </p>

                            <p id="drawerSpecialization" class="text-caption-sm text-on-surface-variant">
                                -
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- WORK ORDER -->
            <section>
                <h4 class="text-caption-sm font-bold text-accent uppercase tracking-widest mb-3">
                    Work Order
                </h4>

                <div class="glass-card rounded-xl p-4">
                    <p id="drawerWorkOrder" class="font-semibold">
                        Not Created
                    </p>
                </div>
            </section>

            <!-- TIMELINE -->
            <section>
                <h4 class="text-caption-sm font-bold text-accent uppercase tracking-widest mb-3">
                    Activity Timeline
                </h4>

                <div
                    id="timelineContainer"
                    class="space-y-3">
                </div>

                <div class="glass-card rounded-xl p-3">
                    <p class="font-medium">
                        Ticket Created
                    </p>
                    <p class="text-caption-sm text-on-surface-variant">
                        Waiting for assignment
                    </p>
                </div>
        </div>
        </section>

        <!-- ACTION BUTTON -->
        <div class="grid grid-cols-1 gap-3">
            <a id="assignBtn" href="work_Order.php" class="w-full bg-accent text-primary-dark font-bold py-3 rounded-xl flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">
                    person_add
                </span>
                Assign Technician
            </a>
            <a
                id="closeBtn"
                href="#"
                class="w-full border border-danger text-danger py-3 rounded-xl font-semibold text-center">
                Close Ticket
            </a>
        </div>
        </div>
    </aside>

    <script>
        const ticketData =
            <?= json_encode($ticketDetails); ?>;

        const timelineData =
            <?= json_encode($timelineData); ?>;

        function openDrawer(reportId) {
            const data = ticketData[reportId];

            // console.log(data);
            // alert(JSON.stringify(data, null, 2));
            if (!data) return;
            document.getElementById('drawerTicketID').innerText =
                data.ticket_id;
            document.getElementById('drawerAssetName').innerText =
                data.asset_name;
            document.getElementById('drawerAssetCode').innerText =
                data.asset_code;
            document.getElementById('drawerCategory').innerText =
                data.asset_category;
            document.getElementById('drawerLocation').innerText =
                data.location + " - " + data.floor_name;
            document.getElementById('drawerDamageType').innerText =
                data.damage_type;
            document.getElementById('drawerDescription').innerText =
                data.description;
            document.getElementById('drawerPriority').innerText =
                data.priority;
            document.getElementById('drawerStatus').innerText =
                data.status;
            document.getElementById('drawerTechnician').innerText =
                data.technician_name ?? 'Unassigned';
            document.getElementById('drawerSpecialization').innerText =
                data.specialization ?? '-';
            document.getElementById('drawerWorkOrder').innerText =
                data.work_order_number ?? 'Not Created';
            // SLA
            if (data.sla_target && data.assigned_at) {

    let start = new Date(data.assigned_at);
    let end = new Date(data.sla_target);
    let now = new Date();

    let total = end - start;
    let remain = end - now;

    let percent = Math.max(
        0,
        Math.min(
            100,
            (remain / total) * 100
        )
    );

    let hours =
        Math.floor(remain / (1000 * 60 * 60));

    let mins =
        Math.floor(
            (remain % (1000 * 60 * 60))
            /
            (1000 * 60)
        );

    document.getElementById('drawerSLA').innerText =
        hours + 'h ' + mins + 'm';

    document.getElementById('slaBar').style.width =
        percent + '%';

} else {

    document.getElementById('drawerSLA').innerText =
        'Not Assigned';

    document.getElementById('slaBar').style.width =
        '100%';
}
            document.getElementById('techAvatar').innerText =
                data.technician_name ?
                data.technician_name.charAt(0).toUpperCase() :
                'U';
            if (data.attachment_file) {
                document.getElementById('drawerPhoto').src =
                    '../uploads/damage_reports/' + data.attachment_file;
            }
            const assignBtn =
                document.getElementById('assignBtn');
            if (data.status === 'Open') {
                assignBtn.href =
                    'Work_Order.php?report_id=' + data.report_id;
                assignBtn.style.display = 'flex';
            } else {
                assignBtn.href =
                    'Work_Order.php?report_id=' + data.report_id;
                assignBtn.innerHTML =
                    '<span class="material-symbols-outlined">assignment</span>Reassign Technician';
            }
            document.getElementById(
                    'closeBtn'
                ).href =
                'Damage_List.php?close=' +
                data.report_id;
            document
                .getElementById('detailDrawer')
                .classList.remove('translate-x-full');
            document
                .getElementById('drawerOverlay')
                .classList.remove('hidden');
            const timeline =
                timelineData[reportId] || [];
            let html = '';
            html += `
                <div class="glass-card rounded-xl p-3">
                <p class="font-medium">
                Ticket Created
                </p>
                <p class="text-caption-sm text-on-surface-variant">
                ${data.created_at}
                </p>
                </div>`;
            timeline.forEach(item => {
                html += `
                <div class="glass-card rounded-xl p-3">
                <p class="font-medium">
                ${item.activity_type}
                </p>
                <p class="text-caption-sm text-on-surface-variant">
                ${item.activity_note ?? ''}
                </p>
                <p class="text-caption-sm text-accent mt-1">
                ${item.created_at}
                </p>
                </div>`;
            });
            document.getElementById('timelineContainer').innerHTML =
                html;
        }

        function closeDrawer() {
            document
                .getElementById('detailDrawer')
                .classList.add('translate-x-full');
            document
                .getElementById('drawerOverlay')
                .classList.add('hidden');
        }
    </script>
    <script src="../Public/Asset/sidebar.js"></script>
</body>

</html>