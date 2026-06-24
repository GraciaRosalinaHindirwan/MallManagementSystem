<?php
// session_start();
// if(!isset($_SESSION['user_id'])){
//     header("Location: Login.php");
//     exit();
// }
require_once 'auth/checkSession.php';
$page_title = "Technician Management";
$page = "technician_management";

include '../config/konek.php';

$search = $_GET['search'] ?? '';
$expertise = $_GET['expertise'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "
    SELECT
        t.technician_id,
        t.NIK,
        t.photo,
        t.status,
        t.is_active,
        t.created_at,
        p.nama AS full_name,
        p.email,
        p.no_hp AS phone,
        p.spesialisasi AS specialization,
        p.sertifikasi AS certification,
        p.foto AS pegawai_photo,
        (SELECT COUNT(*)
         FROM 03_technician_skills ts
         WHERE ts.technician_id = t.technician_id) AS skill_count,
        (SELECT wo.work_order_number
         FROM 03_work_orders wo
         WHERE wo.technician_id = t.technician_id
           AND wo.work_status IN ('Assigned','In Progress')
         ORDER BY wo.assigned_at DESC
         LIMIT 1) AS active_task
    FROM 03_technicians t
    JOIN 07_pegawai p ON t.NIK = p.nik
    WHERE 1=1
";

if ($search != '') {
    $sql .= " AND (p.nama LIKE '%$search%' OR p.email LIKE '%$search%')";
}
if ($expertise != '') {
    $sql .= " AND p.spesialisasi = '$expertise'";
}
if ($status != '') {
    $sql .= " AND t.status = '$status'";
}
$sql .= " ORDER BY p.nama";

$technicians = mysqli_query($conn, $sql);

$skillMatrix = mysqli_query($conn, "
    SELECT
        skill_name,
        ROUND(AVG(proficiency_level)) AS coverage
    FROM 03_technician_skills
    GROUP BY skill_name
    ORDER BY coverage DESC
");

ob_start();
?>

<div class="content-body">
    <!-- Header -->
    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; margin-bottom:24px; gap:12px;">
        <div>
            <h2 style="font-size:24px; font-weight:700; color:var(--text, #F5F7FA); margin:0 0 4px 0;">Technician Directory</h2>
            <p style="color:rgba(245,247,250,0.6); margin:0;">Manage and monitor technician resources.</p>
        </div>
        <div style="display:flex; gap:12px;">
            <button id="cardViewBtn" class="btn btn-primary" style="background:var(--accent, #00D4D8); color:var(--primary-dark, #082A53);">Card View</button>
            <button id="tableViewBtn" class="btn" style="background:transparent; border:1px solid rgba(255,255,255,0.12);">Table View</button>
        </div>
    </div>

    <!-- Grid: Skill Matrix + Directory -->
    <div style="display:grid; grid-template-columns:1fr 2.5fr; gap:24px;">

        <!-- Skill Matrix -->
        <div class="card" style="align-self:start; padding:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-weight:600; color:var(--text); margin:0; font-size:16px;">Skill Proficiency Matrix</h3>
                <span style="color:var(--accent, #00D4D8); font-size:20px;">📊</span>
            </div>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <?php while ($skill = mysqli_fetch_assoc($skillMatrix)): ?>
                    <div>
                        <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                            <span style="color:rgba(245,247,250,0.8);"><?= htmlspecialchars($skill['skill_name']) ?></span>
                            <span style="color:var(--accent, #00D4D8); font-weight:600;"><?= $skill['coverage'] ?>%</span>
                        </div>
                        <div style="height:6px; background:var(--primary-dark, #082A53); border-radius:4px; overflow:hidden;">
                            <div style="height:100%; background:var(--accent, #00D4D8); width:<?= $skill['coverage'] ?>%; border-radius:4px;"></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Directory -->
        <div>
            <!-- Filter -->
            <form method="GET" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:center; background:var(--primary, #0B376D); padding:12px 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.06);">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search technician..." style="background:var(--primary-dark, #082A53); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:8px 14px; color:var(--text); flex:1; min-width:140px; outline:none; font-size:13px;">
                <select name="expertise" style="background:var(--primary-dark, #082A53); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:8px 14px; color:var(--text); font-size:13px; outline:none; cursor:pointer;">
                    <option value="">All Expertise</option>
                    <option value="HVAC" <?= $expertise == 'HVAC' ? 'selected' : '' ?>>HVAC</option>
                    <option value="Electrical" <?= $expertise == 'Electrical' ? 'selected' : '' ?>>Electrical</option>
                    <option value="Mechanical" <?= $expertise == 'Mechanical' ? 'selected' : '' ?>>Mechanical</option>
                    <option value="Plumbing" <?= $expertise == 'Plumbing' ? 'selected' : '' ?>>Plumbing</option>
                </select>
                <select name="status" style="background:var(--primary-dark, #082A53); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:8px 14px; color:var(--text); font-size:13px; outline:none; cursor:pointer;">
                    <option value="">All Status</option>
                    <option value="Available" <?= $status == 'Available' ? 'selected' : '' ?>>Available</option>
                    <option value="On-Duty" <?= $status == 'On-Duty' ? 'selected' : '' ?>>On-Duty</option>
                    <option value="Offline" <?= $status == 'Offline' ? 'selected' : '' ?>>Offline</option>
                </select>
                <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-size:13px;">Filter</button>
                <?php if ($search || $expertise || $status): ?>
                    <a href="Technician_Management.php" style="color:rgba(245,247,250,0.5); font-size:13px; text-decoration:none; padding:6px 12px; border-radius:6px; border:1px solid rgba(255,255,255,0.06);">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Card View -->
            <div id="directoryContainer" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:20px;">
                <?php while ($tech = mysqli_fetch_assoc($technicians)): ?>
                    <div class="card" style="display:flex; flex-direction:column; padding:20px; transition:all 0.2s; border:1px solid rgba(255,255,255,0.06);">
                        <!-- Header: Avatar + Status -->
                        <div style="display:flex; align-items:center; gap:16px; margin-bottom:16px;">
                            <img src="<?= !empty($tech['photo']) ? $tech['photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($tech['full_name']) ?>" 
                                 style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid var(--accent, #00D4D8); flex-shrink:0;">
                            <div style="flex:1; min-width:0;">
                                <h4 style="font-weight:600; margin:0 0 2px 0; font-size:16px; color:var(--text, #F5F7FA);"><?= htmlspecialchars($tech['full_name']) ?></h4>
                                <p style="color:var(--secondary, #167E80); font-size:13px; margin:0;"><?= htmlspecialchars($tech['specialization'] ?? 'General') ?></p>
                                <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                                    <span style="font-size:11px; color:rgba(245,247,250,0.4);"><?= htmlspecialchars($tech['NIK']) ?></span>
                                    <span class="badge <?= $tech['status'] == 'Available' ? 'badge-success' : ($tech['status'] == 'On-Duty' ? 'badge-warning' : 'badge-secondary') ?>" 
                                          style="font-size:10px; padding:2px 10px;"><?= htmlspecialchars($tech['status']) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Detail Grid: 2 kolom -->
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; background:var(--primary-dark, #082A53); border-radius:8px; padding:12px; margin-bottom:14px;">
                            <div>
                                <p style="font-size:10px; color:rgba(245,247,250,0.4); text-transform:uppercase; letter-spacing:0.5px; margin:0 0 2px 0;">Certification</p>
                                <p style="font-size:13px; margin:0; color:var(--text, #F5F7FA); font-weight:500;"><?= htmlspecialchars($tech['certification'] ?? '-') ?></p>
                            </div>
                            <div>
                                <p style="font-size:10px; color:rgba(245,247,250,0.4); text-transform:uppercase; letter-spacing:0.5px; margin:0 0 2px 0;">Active Task</p>
                                <p style="font-size:10px; margin:0; color:var(--text, #F5F7FA); font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($tech['active_task'] ?? 'None') ?>">
                                    <?= $tech['active_task'] ? htmlspecialchars($tech['active_task']) : 'None (Standby)' ?>
                                </p>
                            </div>
                        </div>

                        <!-- Kontak -->
                        <div style="display:flex; flex-direction:column; gap:4px; margin-bottom:14px; padding:0 4px;">
                            <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(245,247,250,0.7);">
                                <span style="font-size:14px;">📞</span>
                                <span><?= htmlspecialchars($tech['phone'] ?? '-') ?></span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(245,247,250,0.7); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <span style="font-size:14px;">✉</span>
                                <span style="overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($tech['email'] ?? '-') ?></span>
                            </div>
                        </div>

                        <!-- Footer: Skills + Button -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:auto; padding-top:14px; border-top:1px solid rgba(255,255,255,0.06);">
                            <span class="badge" style="background:rgba(0,212,216,0.12); color:var(--accent, #00D4D8); font-size:11px; padding:4px 12px;">
                                <?= $tech['skill_count'] ?> Skill<?= $tech['skill_count'] > 1 ? 's' : '' ?>
                            </span>
                            <a href="Work_Order.php?technician_id=<?= $tech['technician_id'] ?>" 
                               class="btn btn-primary" 
                               style="padding:6px 16px; font-size:12px; font-weight:600; border-radius:6px; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                <span style="font-size:14px;">+</span> Create WO
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Table View -->
            <div id="tableView" style="display:none;" class="card" style="padding:0; overflow:hidden;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Technician</th>
                                <th>Expertise</th>
                                <th>Status</th>
                                <th>Active Task</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            mysqli_data_seek($technicians, 0);
                            while ($tech = mysqli_fetch_assoc($technicians)):
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($tech['full_name']) ?></td>
                                    <td><?= htmlspecialchars($tech['specialization'] ?? '-') ?></td>
                                    <td><span class="badge <?= $tech['status'] == 'Available' ? 'badge-success' : ($tech['status'] == 'On-Duty' ? 'badge-warning' : 'badge-secondary') ?>"><?= htmlspecialchars($tech['status']) ?></span></td>
                                    <td><?= htmlspecialchars($tech['active_task'] ?? '-') ?></td>
                                    <td><a href="Work_Order.php?technician_id=<?= $tech['technician_id'] ?>" class="btn btn-primary" style="padding:4px 12px; font-size:12px;">Create WO</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include "../includes/navbarM03.php";
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cardViewBtn = document.getElementById('cardViewBtn');
        const tableViewBtn = document.getElementById('tableViewBtn');
        const cardContainer = document.getElementById('directoryContainer');
        const tableView = document.getElementById('tableView');

        function setActiveView(view) {
            if (view === 'card') {
                cardContainer.style.display = 'grid';
                tableView.style.display = 'none';
                cardViewBtn.style.background = 'var(--accent, #00D4D8)';
                cardViewBtn.style.color = 'var(--primary-dark, #082A53)';
                tableViewBtn.style.background = 'transparent';
                tableViewBtn.style.color = 'var(--text, #F5F7FA)';
                tableViewBtn.style.border = '1px solid rgba(255,255,255,0.12)';
            } else {
                cardContainer.style.display = 'none';
                tableView.style.display = 'block';
                tableViewBtn.style.background = 'var(--accent, #00D4D8)';
                tableViewBtn.style.color = 'var(--primary-dark, #082A53)';
                cardViewBtn.style.background = 'transparent';
                cardViewBtn.style.color = 'var(--text, #F5F7FA)';
                cardViewBtn.style.border = '1px solid rgba(255,255,255,0.12)';
            }
        }

        cardViewBtn.addEventListener('click', () => setActiveView('card'));
        tableViewBtn.addEventListener('click', () => setActiveView('table'));
    });
</script>
