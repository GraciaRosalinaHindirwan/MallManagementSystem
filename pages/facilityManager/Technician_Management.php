<?php
// session_start();

// if(!isset($_SESSION['user_id'])){

//     header("Location: Login.php");
//     exit();
// }


$title = "Technician Management";
$page = "technician_management";

include '../Config/konek.php';

$search = $_GET['search'] ?? '';
$expertise = $_GET['expertise'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "
SELECT
t.*,

(
SELECT COUNT(*)
FROM technician_skills ts
WHERE ts.technician_id=t.technician_id
) AS skill_count,

(
SELECT wo.work_order_number
FROM work_orders wo
WHERE wo.technician_id=t.technician_id
AND wo.work_status IN ('Assigned','In Progress')
ORDER BY wo.assigned_at DESC
LIMIT 1
) AS active_task

FROM technicians t
WHERE 1=1
";

if ($search != '') {
    $sql .= " AND t.full_name LIKE '%$search%'";
}

if ($expertise != '') {
    $sql .= " AND t.specialization='$expertise'";
}

if ($status != '') {
    $sql .= " AND t.status='$status'";
}

$sql .= " ORDER BY t.full_name";

$technicians = mysqli_query($conn, $sql);

$skillMatrix = mysqli_query(
    $conn,
    "
SELECT
skill_name,
ROUND(AVG(proficiency_level)) AS coverage
FROM technician_skills
GROUP BY skill_name
ORDER BY coverage DESC
"
);

include '../Includes/head.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TECHNICIAN MANAGEMENT</title>
</head>

<body>
    <?php include '../Includes/sidebar.php'; ?>
    <?php include '../Includes/topbar.php'; ?>
    <main class="mt-16 p-4 md:p-container-padding min-h-screen bg-surface-dim">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-headline-h1 text-headline-h1 text-on-surface">
                        Technician Directory
                    </h2>
                    <p class="text-on-surface-variant">
                        Manage and monitor technician resources.
                    </p>
                </div>
                <div class="flex gap-3">
                    <button
                        id="cardViewBtn"
                        class="px-4 py-2 rounded-lg bg-accent text-primary-dark font-semibold">
                        Card View
                    </button>
                    <button
                        id="tableViewBtn"
                        class="px-4 py-2 rounded-lg border border-glass-stroke">
                        Table View
                    </button>
                </div>
            </div>

            <!-- MATRIX + FILTER -->
            <div class="grid xl:grid-cols-12 gap-6">
                <!-- SKILL MATRIX -->
                <section class="xl:col-span-4 glass-card rounded-xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-semibold text-primary">
                            Skill Proficiency Matrix
                        </h3>
                        <span class="material-symbols-outlined text-accent">
                            query_stats
                        </span>
                    </div>
                    <div class="space-y-4">
                        <?php while ($skill = mysqli_fetch_assoc($skillMatrix)): ?>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>
                                        <?= htmlspecialchars($skill['skill_name']) ?>
                                    </span>
                                    <span class="text-accent">
                                        <?= $skill['coverage'] ?>%
                                    </span>
                                </div>
                                <div class="h-2 bg-primary-dark rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-accent"
                                        style="width: <?= $skill['coverage'] ?>%;">
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </section>

                <!-- DIRECTORY -->
                <section class="xl:col-span-8 space-y-4">
                    <!-- FILTER -->
                    <form method="GET"
                        class="glass-card p-4 rounded-xl flex flex-wrap gap-3 items-center justify-between">
                        <div class="flex flex-wrap gap-3">
                            <input
                                type="text"
                                name="search"
                                value="<?= htmlspecialchars($search) ?>"
                                placeholder="Search technician..."
                                class="px-4 py-2 rounded-lg bg-primary-dark border border-glass-stroke">
                            <select
                                name="expertise"
                                class="px-4 py-2 rounded-lg bg-primary-dark border border-glass-stroke">
                                <option value="">
                                    All Expertise
                                </option>
                                <option value="HVAC">
                                    HVAC
                                </option>
                                <option value="Electrical">
                                    Electrical
                                </option>
                                <option value="Mechanical">
                                    Mechanical
                                </option>
                                <option value="Plumbing">
                                    Plumbing
                                </option>
                            </select>

                            <select
                                name="status"
                                class="px-4 py-2 rounded-lg bg-primary-dark border border-glass-stroke">
                                <option value="">
                                    All Status
                                </option>
                                <option value="Available">
                                    Available
                                </option>
                                <option value="On-Duty">
                                    On-Duty
                                </option>
                            </select>
                            <button
                                type="submit"
                                class="px-4 py-2 rounded-lg bg-accent text-primary-dark">
                                Filter
                            </button>
                        </div>
                    </form>

                    <!-- CARD VIEW -->
                    <div
                        id="directoryContainer"
                        class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php while ($tech = mysqli_fetch_assoc($technicians)): ?>
                            <div class="glass-card rounded-xl p-5 flex flex-col">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="relative">
                                        <img
                                            src="<?= !empty($tech['photo']) ? $tech['photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($tech['full_name']) ?>"
                                            class="h-16 w-16 rounded-full object-cover border-2 border-accent">
                                    </div>

                                    <div class="text-right">
                                        <span class="<?= $tech['status'] == 'Available' ? 'text-green-400' : 'text-yellow-400' ?> text-xs font-bold">
                                            <?= $tech['status'] ?>
                                        </span>
                                        <p class="text-xs text-on-surface-variant">
                                            <?= $tech['employee_code'] ?>
                                        </p>
                                    </div>
                                </div>

                                <h4 class="font-semibold text-lg">
                                    <?= htmlspecialchars($tech['full_name']) ?>
                                </h4>

                                <p class="text-secondary text-sm mb-4">
                                    <?= htmlspecialchars($tech['specialization']) ?>
                                </p>

                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div>
                                        <p class="text-xs text-on-surface-variant">
                                            Certification
                                        </p>
                                        <p class="text-sm">
                                            <?= htmlspecialchars($tech['certification']) ?>
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs text-on-surface-variant">
                                            Active Task
                                        </p>
                                        <p class="text-sm truncate">
                                            <?= $tech['active_task']
                                                ? $tech['active_task']
                                                : 'None (Standby)' ?>
                                        </p>
                                    </div>

                                </div>
                                <div class="space-y-2 mb-4">
                                    <p class="text-sm">
                                        📞 <?= $tech['phone'] ?>
                                    </p>
                                    <p class="text-sm truncate">
                                        ✉ <?= $tech['email'] ?>
                                    </p>
                                </div>

                                <div class="mb-4">
                                    <span class="px-2 py-1 rounded bg-accent/10 text-accent text-xs">
                                        <?= $tech['skill_count'] ?> Skills
                                    </span>
                                </div>
                                <a
                                    href="Work_Order.php?technician_id=<?= $tech['technician_id'] ?>"
                                    class="mt-auto text-center py-2 rounded-lg bg-accent text-primary-dark font-semibold">
                                    Create Work Order
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- TABLE VIEW -->
                    <div
                        id="tableView"
                        class="hidden glass-card rounded-xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-glass-stroke">
                                        <th class="p-4 text-left">
                                            Technician
                                        </th>

                                        <th class="p-4 text-left">
                                            Expertise
                                        </th>

                                        <th class="p-4 text-left">
                                            Status
                                        </th>

                                        <th class="p-4 text-left">
                                            Active Task
                                        </th>

                                        <th class="p-4 text-right">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    mysqli_data_seek($technicians, 0);
                                    while ($tech = mysqli_fetch_assoc($technicians)):
                                    ?>
                                        <tr class="border-b border-glass-stroke">
                                            <td class="p-4">
                                                <?= $tech['full_name'] ?>
                                            </td>
                                            <td class="p-4">
                                                <?= $tech['specialization'] ?>
                                            </td>
                                            <td class="p-4">
                                                <?= $tech['status'] ?>
                                            </td>
                                            <td class="p-4">
                                                <?= $tech['active_task'] ?: '-' ?>
                                            </td>
                                            <td class="p-4 text-right">
                                                <a
                                                    href="Work_Order.php?technician_id=<?= $tech['technician_id'] ?>"
                                                    class="text-accent">
                                                    Create WO
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        const cardViewBtn =
            document.getElementById("cardViewBtn");
        const tableViewBtn =
            document.getElementById("tableViewBtn");
        const cardContainer =
            document.getElementById("directoryContainer");
        const tableView =
            document.getElementById("tableView");
        cardViewBtn.addEventListener("click", () => {
            cardContainer.classList.remove("hidden");
            tableView.classList.add("hidden");
        });
        tableViewBtn.addEventListener("click", () => {

            cardContainer.classList.add("hidden");
            tableView.classList.remove("hidden");

        });
    </script>
    <script src="../Public/Asset/sidebar.js"></script>
</body>

</html>