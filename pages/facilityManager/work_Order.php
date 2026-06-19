<?php
// session_start();
// if(!isset($_SESSION['user_id'])){
//     header("Location: Login.php");
//     exit();
// }
require_once 'auth/checkSession.php';
$page_title = "Work Order";
$page = "work_order";

include '../config/konek.php';

// ============================================================
// Ambil nilai dari GET (untuk mempertahankan form saat filter skill)
// ============================================================
$selectedSkill = $_GET['skill'] ?? '';
$selectedReportId = $_GET['report_id'] ?? '';
$selectedPriority = $_GET['priority'] ?? 'Medium';
$selectedSlaTarget = $_GET['sla_target'] ?? '';
$selectedDueDate = $_GET['due_date'] ?? '';
$selectedRequiredSkill = $_GET['required_skill'] ?? '';

// Jika ada POST (submit form), proses assign
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_id = (int)$_POST['report_id'];
    $technician_id = (int)$_POST['technician_id'];
    $required_skill = $_POST['required_skill'];
    $priority = $_POST['priority'];
    $sla_target = $_POST['sla_target'];
    $due_date = $_POST['due_date'];
    $assigned_by = 1; // nanti ambil dari session

    // Generate work order number unik
    function generateWorkOrderNumber($report_id) {
        return "WO-" . date("Ymd") . "-" . str_pad($report_id, 4, '0', STR_PAD_LEFT) . "-" . rand(100, 999);
    }
    $work_order_number = generateWorkOrderNumber($report_id);
    // Cegah duplikat (hampir tidak mungkin)
    $check = mysqli_query($conn, "SELECT work_order_number FROM 03_work_orders WHERE work_order_number = '$work_order_number'");
    while (mysqli_num_rows($check) > 0) {
        $work_order_number = generateWorkOrderNumber($report_id) . rand(10, 99);
        $check = mysqli_query($conn, "SELECT work_order_number FROM 03_work_orders WHERE work_order_number = '$work_order_number'");
    }

    $stmt = $conn->prepare("
        INSERT INTO 03_work_orders (
            work_order_number, report_id, technician_id, required_skill,
            priority, sla_target, due_date, assigned_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("siissssi", $work_order_number, $report_id, $technician_id, $required_skill, $priority, $sla_target, $due_date, $assigned_by);

    if ($stmt->execute()) {
        $work_order_id = mysqli_insert_id($conn);

        // Ambil nama teknisi dari 07_pegawai via NIK
        $techResult = mysqli_query($conn, "
            SELECT p.nama
            FROM 03_technicians t
            JOIN 07_pegawai p ON t.NIK = p.nik
            WHERE t.technician_id = $technician_id
        ");
        $tech = mysqli_fetch_assoc($techResult);
        $technician_name = $tech['nama'] ?? 'Unknown';
        $note = 'Assigned to ' . $technician_name;

        // Catat aktivitas
        mysqli_query($conn, "
            INSERT INTO 03_work_order_activities
            (work_order_id, activity_type, activity_note, employee_code, created_at)
            VALUES ($work_order_id, 'Assigned', '$note', '0', NOW())
        ");

        // Update status damage report
        mysqli_query($conn, "
            UPDATE 03_damage_reports
            SET status='Assigned'
            WHERE report_id=$report_id
        ");

        // Update status teknisi
        mysqli_query($conn, "
            UPDATE 03_technicians
            SET status='On-Duty'
            WHERE technician_id=$technician_id
        ");

        header("Location: Work_Order.php?success=1");
        exit();
    } else {
        $error = "Gagal menyimpan work order: " . $stmt->error;
    }
}

// ============================================================
// Ambil daftar ticket Open (dari 03_damage_reports)
// ============================================================
$ticketQuery = mysqli_query($conn, "
    SELECT
        dr.report_id,
        dr.ticket_id,
        a.name AS asset_name
    FROM 03_damage_reports dr
    LEFT JOIN 03_assets a ON dr.asset_id = a.asset_id
    WHERE dr.status = 'Open'
    ORDER BY dr.created_at DESC
");

// ============================================================
// Technician Recommendation (dengan join ke 07_pegawai)
// ============================================================
if (!empty($selectedSkill)) {
    $sql = "
        SELECT
            t.*,
            ts.skill_name,
            ts.proficiency_level,
            p.nama AS full_name,
            p.foto AS photo,
            (
                SELECT COUNT(*)
                FROM 03_work_orders wo
                WHERE wo.technician_id = t.technician_id
                AND wo.work_status IN ('Assigned','In Progress')
            ) AS active_tasks
        FROM 03_technicians t
        JOIN 07_pegawai p ON t.NIK = p.nik
        JOIN 03_technician_skills ts ON t.technician_id = ts.technician_id
        WHERE ts.skill_name = '$selectedSkill'
        ORDER BY ts.proficiency_level DESC
    ";
} else {
    $sql = "
        SELECT
            t.*,
            p.nama AS full_name,
            p.foto AS photo,
            NULL AS skill_name,
            NULL AS proficiency_level,
            0 AS active_tasks
        FROM 03_technicians t
        JOIN 07_pegawai p ON t.NIK = p.nik
        ORDER BY p.nama
    ";
}

$techQuery = mysqli_query($conn, $sql);
$technicians = [];
while ($row = mysqli_fetch_assoc($techQuery)) {
    $score = $row['proficiency_level'] ?? 50;
    if ($row['status'] == 'Available') {
        $score += 10;
    }
    $activeTasks = $row['active_tasks'] ?? 0;
    $score -= ($activeTasks * 3);
    $row['match_score'] = max(0, min(100, $score));
    $row['specialization'] = $row['skill_name'] ?? 'General';
    $technicians[] = $row;
}
usort($technicians, function($a, $b) {
    return $b['match_score'] - $a['match_score'];
});

// ============================================================
// Generate Work Order Number untuk ditampilkan (sementara)
// ============================================================
$work_order_number = "WO-" . date("Ymd") . "-" . rand(1000, 9999);

ob_start();
?>

<div class="content-body">
    <?php if (isset($_GET['success'])): ?>
        <div style="background:rgba(34,197,94,0.1); border-left:4px solid #22C55E; padding:16px; margin-bottom:24px; border-radius:8px;">
            <span style="color:#22C55E;">✅ Work Order berhasil dibuat dan ditugaskan.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div style="background:rgba(239,68,68,0.1); border-left:4px solid #EF4444; padding:16px; margin-bottom:24px; border-radius:8px;">
            <span style="color:#EF4444;">❌ <?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="technician_id" id="selectedTechnician">

        <!-- Bawa semua nilai form sebagai hidden agar tidak hilang saat submit -->
        <?php if ($selectedReportId): ?>
            <input type="hidden" name="report_id" value="<?= $selectedReportId ?>">
        <?php endif; ?>
        <?php if ($selectedRequiredSkill): ?>
            <input type="hidden" name="required_skill" value="<?= $selectedRequiredSkill ?>">
        <?php endif; ?>
        <?php if ($selectedPriority): ?>
            <input type="hidden" name="priority" value="<?= $selectedPriority ?>">
        <?php endif; ?>
        <?php if ($selectedSlaTarget): ?>
            <input type="hidden" name="sla_target" value="<?= $selectedSlaTarget ?>">
        <?php endif; ?>
        <?php if ($selectedDueDate): ?>
            <input type="hidden" name="due_date" value="<?= $selectedDueDate ?>">
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">

            <!-- KOLOM KIRI: Form -->
            <div>
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                        <h2 class="card-title" style="margin:0; display:flex; align-items:center; gap:10px;">
                            <span style="color:var(--accent, #00D4D8);">⚙️</span> Configure Work Order
                        </h2>
                        <span class="badge">DRAFT</span>
                    </div>

                    <!-- Work Order ID (readonly) -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label>Work Order ID</label>
                            <input type="text" readonly name="work_order_number" value="<?= $work_order_number ?>" style="background:var(--primary-dark, #082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:10px 14px; color:var(--text);">
                        </div>
                        <div class="form-group">
                            <label>Ticket Number</label>
                            <select name="report_id" id="report_id" required>
                                <option value="">Select Ticket</option>
                                <?php while ($ticket = mysqli_fetch_assoc($ticketQuery)): ?>
                                    <option value="<?= $ticket['report_id'] ?>" <?= ($selectedReportId == $ticket['report_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ticket['ticket_id']) ?> - <?= htmlspecialchars($ticket['asset_name'] ?? 'No Asset') ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Skill Required -->
                    <div class="form-group">
                        <label>Skill Required</label>
                        <select name="required_skill" id="skillSelect" required>
                            <option value="">Select Skill</option>
                            <option value="HVAC" <?= ($selectedSkill == 'HVAC' || $selectedRequiredSkill == 'HVAC') ? 'selected' : '' ?>>HVAC</option>
                            <option value="Electrical" <?= ($selectedSkill == 'Electrical' || $selectedRequiredSkill == 'Electrical') ? 'selected' : '' ?>>Electrical</option>
                            <option value="Plumbing" <?= ($selectedSkill == 'Plumbing' || $selectedRequiredSkill == 'Plumbing') ? 'selected' : '' ?>>Plumbing</option>
                            <option value="Fire Safety" <?= ($selectedSkill == 'Fire Safety' || $selectedRequiredSkill == 'Fire Safety') ? 'selected' : '' ?>>Fire Safety</option>
                            <option value="Structural" <?= ($selectedSkill == 'Structural' || $selectedRequiredSkill == 'Structural') ? 'selected' : '' ?>>Structural</option>
                            <option value="Mechanical" <?= ($selectedSkill == 'Mechanical' || $selectedRequiredSkill == 'Mechanical') ? 'selected' : '' ?>>Mechanical</option>
                        </select>
                    </div>

                    <!-- Priority & SLA -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label>Priority Level</label>
                            <select name="priority" id="priority" required>
                                <option value="Critical" <?= ($selectedPriority == 'Critical') ? 'selected' : '' ?>>Critical</option>
                                <option value="High" <?= ($selectedPriority == 'High') ? 'selected' : '' ?>>High</option>
                                <option value="Medium" <?= ($selectedPriority == 'Medium' || !$selectedPriority) ? 'selected' : '' ?>>Medium</option>
                                <option value="Low" <?= ($selectedPriority == 'Low') ? 'selected' : '' ?>>Low</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>SLA Target</label>
                            <input type="datetime-local" name="sla_target" id="sla_target" value="<?= $selectedSlaTarget ?>" required>
                        </div>
                    </div>

                    <!-- Due Date -->
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" id="due_date" value="<?= $selectedDueDate ?>" required>
                    </div>

                    <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:20px; display:flex; justify-content:flex-end; gap:12px; margin-top:8px;">
                        <button type="reset" style="padding:8px 20px; border-radius:8px; background:transparent; color:rgba(245,247,250,0.6); border:1px solid rgba(255,255,255,0.1); cursor:pointer;">Reset</button>
                        <button type="submit" class="btn btn-primary">Assign Task</button>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: Daftar Teknisi -->
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="font-size:18px; font-weight:600; color:var(--text); display:flex; align-items:center; gap:8px;">
                        <span style="color:var(--secondary, #167E80);">🧠</span> Intelligent Matching
                    </h3>
                </div>

                <div style="max-height:750px; overflow-y:auto; padding-right:8px;">
                    <?php if (count($technicians) > 0): ?>
                        <?php foreach ($technicians as $tech): ?>
                            <div class="card tech-card" style="cursor:pointer; border-left:4px solid var(--accent, #00D4D8); margin-bottom:12px; transition:all 0.2s;"
                                 data-id="<?= $tech['technician_id'] ?>"
                                 onclick="selectTechnician(this)">
                                <div style="display:flex; align-items:flex-start; gap:16px;">
                                    <div>
                                        <img src="<?= !empty($tech['photo']) ? $tech['photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($tech['full_name']) ?>"
                                             style="width:48px; height:48px; border-radius:50%; object-fit:cover;">
                                    </div>
                                    <div style="flex:1;">
                                        <div style="display:flex; justify-content:space-between; align-items:center;">
                                            <h4 style="font-weight:600; margin:0;"><?= htmlspecialchars($tech['full_name']) ?></h4>
                                            <span style="font-weight:700; color:#22C55E;"><?= $tech['match_score'] ?>%</span>
                                        </div>
                                        <p style="font-size:14px; color:rgba(245,247,250,0.6); margin:4px 0;">
                                            <?= htmlspecialchars($tech['specialization'] ?? 'General') ?>
                                        </p>
                                        <div style="margin-top:8px;">
                                            <span style="font-size:12px; color:rgba(245,247,250,0.6);">Status</span>
                                            <p style="font-weight:500; margin:0;"><?= htmlspecialchars($tech['status']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card" style="text-align:center; padding:40px 20px; color:rgba(245,247,250,0.6);">
                            Tidak ada teknisi yang cocok dengan skill yang dipilih.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include "../includes/navbarM03.php";
?>

<script>
    function selectTechnician(element) {
        document.querySelectorAll('.tech-card').forEach(c => {
            c.style.background = 'var(--primary, #0B376D)';
            c.style.borderLeftColor = 'var(--accent, #00D4D8)';
        });
        element.style.background = 'rgba(0,212,216,0.1)';
        element.style.borderLeftColor = '#00D4D8';
        document.getElementById('selectedTechnician').value = element.dataset.id;
    }

    // Saat skill berubah, redirect dengan membawa semua nilai form yang sudah diisi
    document.getElementById('skillSelect').addEventListener('change', function() {
        var skill = this.value;
        var report_id = document.getElementById('report_id').value;
        var priority = document.getElementById('priority').value;
        var sla_target = document.getElementById('sla_target').value;
        var due_date = document.getElementById('due_date').value;
        var required_skill = this.value;

        var url = 'Work_Order.php?skill=' + encodeURIComponent(skill);
        if (report_id) url += '&report_id=' + encodeURIComponent(report_id);
        if (priority) url += '&priority=' + encodeURIComponent(priority);
        if (sla_target) url += '&sla_target=' + encodeURIComponent(sla_target);
        if (due_date) url += '&due_date=' + encodeURIComponent(due_date);
        if (required_skill) url += '&required_skill=' + encodeURIComponent(required_skill);
        window.location.href = url;
    });

    // Validasi sebelum submit
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!document.getElementById('selectedTechnician').value) {
            e.preventDefault();
            alert('Pilih teknisi terlebih dahulu.');
        }
        if (!document.getElementById('report_id').value) {
            e.preventDefault();
            alert('Pilih ticket terlebih dahulu.');
        }
    });
</script>
