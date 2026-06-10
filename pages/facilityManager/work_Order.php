<?php
// session_start();

// if(!isset($_SESSION['user_id'])){

//     header("Location: Login.php");
//     exit();
// }

$title = "Work Order";
$page = "work_order";

include '../Config/konek.php';

/*
| Generate Work Order Number
*/

$work_order_number =
    "WO-" .
    date("Ymd") .
    "-" .
    rand(1000, 9999);

/*
| Save Work Order
*/
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $work_order_number = $_POST['work_order_number'];
    $report_id = $_POST['report_id'];
    $technician_id = $_POST['technician_id'];
    $required_skill = $_POST['required_skill'];
    $priority = $_POST['priority'];
    $sla_target = $_POST['sla_target'];
    $due_date = $_POST['due_date'];
    $assigned_by = 1;
    $stmt = $conn->prepare(
        "
    INSERT INTO work_orders(
        work_order_number,
        report_id,
        technician_id,
        required_skill,
        priority,
        sla_target,
        due_date,
        assigned_by
    )
    VALUES(
        ?,?,?,?,?,?,?,?
    )
    "
    );
    $stmt->bind_param(
        "siissssi",
        $work_order_number,
        $report_id,
        $technician_id,
        $required_skill,
        $priority,
        $sla_target,
        $due_date,
        $assigned_by
    );
   if ($stmt->execute()) {

    $work_order_id =
        mysqli_insert_id($conn);

    $techResult =
        mysqli_query(
            $conn,
            "
            SELECT full_name
            FROM technicians
            WHERE technician_id=$technician_id
            "
        );

    $tech =
        mysqli_fetch_assoc($techResult);

    $technician_name =
        $tech['full_name'];

    $note =
        'Assigned to ' .
        $technician_name;

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
            'Assigned',
            '$note',
            NOW()
        )
        "
    );

    mysqli_query(
        $conn,
        "
        UPDATE damage_reports
        SET status='Assigned'
        WHERE report_id=$report_id
        "
    );

    mysqli_query(
        $conn,
        "
        UPDATE technicians
        SET status='On-Duty'
        WHERE technician_id=$technician_id
        "
    );

    header(
        "Location: Work_Order.php?success=1"
    );

    exit();
}
}

/*
| Open Tickets
*/
$ticketQuery = mysqli_query(
    $conn,
    "
SELECT
    report_id,
    ticket_id,
    asset_name,
    damage_type,
    priority
FROM damage_reports
WHERE status='Open'
ORDER BY created_at DESC
"
);

/*
| Technician Recommendation
*/
$selectedSkill =
    $_GET['skill']
    ?? '';
// echo $selectedSkill;
if (!empty($selectedSkill)) {

    $sql = "
    SELECT
    t.*,
    ts.skill_name,
    ts.proficiency_level,

    (
        SELECT COUNT(*)
        FROM work_orders wo
        WHERE wo.technician_id=t.technician_id
        AND wo.work_status IN
        ('Assigned','In Progress')
    ) AS active_tasks

    FROM technicians t

    JOIN technician_skills ts
    ON t.technician_id=ts.technician_id

    WHERE ts.skill_name='$selectedSkill'

    ORDER BY ts.proficiency_level DESC
    ";
} else {

    $sql = "
    SELECT
    *
    FROM technicians
    ORDER BY full_name
    ";
}

$techQuery =
    mysqli_query(
        $conn,
        $sql
    );

$technicians = [];
while ($row = mysqli_fetch_assoc($techQuery)) {
    $score = $row['proficiency_level'] ?? 50;
    if (
        $row['status']
        ==
        'Available'
    ) {
        $score += 10;
    }
    $activeTasks = $row['active_tasks'] ?? 0;
    $score -= ($activeTasks * 3);
    $row['match_score'] =
        max(
            0,
            min(100, $score)
        );
    $technicians[] = $row;
}

usort(
    $technicians,
    function ($a, $b) {
        return
            $b['match_score']
            -
            $a['match_score'];
    }
);

include '../Includes/head.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WORK ORDER</title>
</head>

<body>
    <?php include '../Includes/sidebar.php'; ?>
    <?php include '../Includes/topbar.php'; ?>
    <main>
        <main class="pt-24 px-4 md:px-8 pb-12 min-h-screen">
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 bg-green-500/10 border border-green-500/30 text-green-400 rounded-xl px-4 py-3">
                    ✅ Work Order berhasil dibuat dan ditugaskan.
                </div>
            <?php endif; ?>

            <form method="POST">
                <input
                    type="hidden"
                    name="technician_id"
                    id="selectedTechnician">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- LEFT -->
                    <section class="col-span-12 lg:col-span-7 flex flex-col gap-6">
                        <div class="glass-card rounded-xl p-8 shadow-xl">
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="material-symbols-outlined text-accent"
                                        style="font-variation-settings:'FILL' 1">
                                        assignment_add
                                    </span>
                                    <h2 class="font-headline-h2 text-headline-h2 text-text-main">
                                        Configure Work Order
                                    </h2>
                                </div>
                                <span
                                    class="px-3 py-1 bg-primary-container text-primary text-caption-sm font-medium rounded-full border border-primary/30">
                                    DRAFT
                                </span>
                            </div>
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block mb-2 text-on-surface-variant">
                                            Work Order ID
                                        </label>
                                        <input
                                            type="text"
                                            readonly
                                            name="work_order_number"
                                            value="<?= $work_order_number ?>"
                                            class="w-full bg-surface-container-low border border-glass-stroke rounded-lg px-4 py-3">
                                    </div>
                                    <div>
                                        <label class="block mb-2 text-on-surface-variant">
                                            Ticket Number
                                        </label>
                                        <select
                                            name="report_id"
                                            required
                                            class="w-full bg-primary-dark border border-glass-stroke rounded-lg px-4 py-3">
                                            <option value="">
                                                Select Ticket
                                            </option>
                                            <?php while ($ticket = mysqli_fetch_assoc($ticketQuery)): ?>
                                                <option
                                                    value="<?= $ticket['report_id'] ?>">
                                                    <?= $ticket['ticket_id'] ?>
                                                    -
                                                    <?= $ticket['asset_name'] ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block mb-2 text-on-surface-variant">
                                        Skill Required
                                    </label>
                                    <select
                                        name="required_skill"
                                        id="skillSelect"
                                        required
                                        class="w-full bg-primary-dark border border-glass-stroke rounded-lg px-4 py-3">

                                        <option value="">Select Skill</option>

                                        <option value="HVAC"
                                            <?= $selectedSkill == 'HVAC' ? 'selected' : '' ?>>
                                            HVAC
                                        </option>

                                        <option value="Electrical"
                                            <?= $selectedSkill == 'Electrical' ? 'selected' : '' ?>>
                                            Electrical
                                        </option>

                                        <option value="Plumbing"
                                            <?= $selectedSkill == 'Plumbing' ? 'selected' : '' ?>>
                                            Plumbing
                                        </option>

                                        <option value="Fire Safety"
                                            <?= $selectedSkill == 'Fire Safety' ? 'selected' : '' ?>>
                                            Fire Safety
                                        </option>

                                        <option value="Structural"
                                            <?= $selectedSkill == 'Structural' ? 'selected' : '' ?>>
                                            Structural
                                        </option>

                                        <option value="Mechanical"
                                            <?= $selectedSkill == 'Mechanical' ? 'selected' : '' ?>>
                                            Mechanical
                                        </option>

                                    </select>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block mb-2 text-on-surface-variant">
                                            Priority Level
                                        </label>
                                        <select
                                            name="priority"
                                            required
                                            class="w-full bg-primary-dark border border-glass-stroke rounded-lg px-4 py-3">
                                            <option value="Critical">
                                                Critical
                                            </option>
                                            <option value="High">
                                                High
                                            </option>
                                            <option value="Medium" selected>
                                                Medium
                                            </option>
                                            <option value="Low">
                                                Low
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block mb-2 text-on-surface-variant">
                                            SLA Target
                                        </label>
                                        <input
                                            type="datetime-local"
                                            name="sla_target"
                                            required
                                            class="w-full bg-primary-dark border border-glass-stroke rounded-lg px-4 py-3">
                                    </div>
                                </div>
                                <div>
                                    <label class="block mb-2 text-on-surface-variant">
                                        Due Date
                                    </label>
                                    <input
                                        type="date"
                                        name="due_date"
                                        required
                                        class="w-full bg-primary-dark border border-glass-stroke rounded-lg px-4 py-3">
                                </div>
                                <div class="pt-8 border-t border-glass-stroke flex justify-end gap-4">
                                    <button
                                        type="reset"
                                        class="px-6 py-2.5 rounded-lg text-on-surface-variant hover:bg-white/5">
                                        Reset
                                    </button>
                                    <button
                                        type="submit"
                                        class="px-8 py-2.5 rounded-lg bg-gradient-to-r from-primary to-secondary text-on-primary font-bold">
                                        Assign Task
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- RIGHT -->
                    <section class="col-span-12 lg:col-span-5 space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="font-subheading text-subheading text-text-main flex items-center gap-2">
                                <span class="material-symbols-outlined text-secondary">
                                    psychology
                                </span>
                                Intelligent Matching
                            </h3>
                        </div>
                        <div class="space-y-4 max-h-[750px] overflow-y-auto pr-2 custom-scrollbar">
                            <?php foreach ($technicians as $tech): ?>
                                <div
                                    class="tech-card glass-card rounded-xl p-5 border-l-4 border-accent hover:bg-glass-fill/60 transition-all cursor-pointer"
                                    data-id="<?= $tech['technician_id'] ?>"
                                    data-skill="<?= strtolower($tech['specialization']) ?>">
                                    <div class="flex items-start gap-4">
                                        <div>
                                            <img
                                                src="<?= !empty($tech['photo']) ? $tech['photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($tech['full_name']) ?>"
                                                class="w-12 h-12 rounded-full object-cover">
                                        </div>
                                        <div class="flex-grow">
                                            <div class="flex items-center justify-between">
                                                <h4 class="font-semibold text-text-main">
                                                    <?= htmlspecialchars($tech['full_name']) ?>
                                                </h4>
                                                <span class="text-success font-semibold">
                                                    <?= $tech['match_score'] ?>%
                                                </span>
                                            </div>
                                            <p class="text-sm text-on-surface-variant">
                                                <?= htmlspecialchars($tech['specialization']) ?>
                                            </p>
                                            <div class="mt-3">
                                                <span class="text-xs text-on-surface-variant">
                                                    Status
                                                </span>
                                                <p class="font-medium">
                                                    <?= htmlspecialchars($tech['status']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </form>
        </main>
        <script src="../Public/Asset/sidebar.js"></script>
        <script>
            document
                .getElementById('skillSelect')
                .addEventListener(
                    'change',
                    function() {

                        window.location.href =
                            'Work_Order.php?skill=' +
                            encodeURIComponent(
                                this.value
                            );

                    }
                );
            const selectedTechnician =
                document.getElementById(
                    'selectedTechnician'
                );
            document
                .querySelectorAll('.tech-card')
                .forEach(card => {
                    card.addEventListener(
                        'click',
                        () => {
                            document
                                .querySelectorAll('.tech-card')
                                .forEach(c => {
                                    c.classList.remove(
                                        'ring-2',
                                        'ring-accent',
                                        'bg-accent/10'
                                    );
                                });
                            card.classList.add(
                                'ring-2',
                                'ring-accent',
                                'bg-accent/10'
                            );
                            selectedTechnician.value =
                                card.dataset.id;
                        });
                });
            document
                .querySelector('form')
                .addEventListener(
                    'submit',
                    function(e) {
                        if (
                            !selectedTechnician.value
                        ) {
                            e.preventDefault();
                            alert(
                                'Pilih teknisi terlebih dahulu.'
                            );
                        }
                    }
                );
        </script>
</body>

</html>