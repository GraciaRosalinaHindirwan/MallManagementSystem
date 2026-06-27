<?php
// session_start();
// if(!isset($_SESSION['user_id'])){
//     header("Location: Login.php");
//     exit();
// }
require_once 'auth/checkSession.php';
$page_title = "Damage List";
$page = "damage_list";

include '../../config/konek.php';

// ============================================================
// 1. SINKRONISASI AMAN (INSERT IGNORE)
// ============================================================
$syncQuery = mysqli_query($conn, "
    SELECT 
        tk.id AS ticket_id,
        tk.created_at,
        tk.asset_code,
        tk.asset_name
    FROM 05_tiket tk
    LEFT JOIN 03_damage_reports dr ON tk.id = dr.ticket_id
    WHERE dr.ticket_id IS NULL
");

if ($syncQuery && mysqli_num_rows($syncQuery) > 0) {
    while ($row = mysqli_fetch_assoc($syncQuery)) {
        $asset_id = null;
        if (!empty($row['asset_code'])) {
            $assetQuery = mysqli_query($conn, "SELECT asset_id FROM 03_assets WHERE asset_code = '{$row['asset_code']}' LIMIT 1");
            if ($assetQuery && mysqli_num_rows($assetQuery) > 0) {
                $asset = mysqli_fetch_assoc($assetQuery);
                $asset_id = $asset['asset_id'];
            }
        }
        if (!$asset_id && !empty($row['asset_name'])) {
            $assetQuery = mysqli_query($conn, "SELECT asset_id FROM 03_assets WHERE name = '{$row['asset_name']}' LIMIT 1");
            if ($assetQuery && mysqli_num_rows($assetQuery) > 0) {
                $asset = mysqli_fetch_assoc($assetQuery);
                $asset_id = $asset['asset_id'];
            }
        }

        mysqli_query($conn, "
            INSERT IGNORE INTO 03_damage_reports 
            (ticket_id, created_at, asset_id, status)
            VALUES 
            ('{$row['ticket_id']}', '{$row['created_at']}', " . ($asset_id ? $asset_id : 'NULL') . ", 'Open')
        ");
    }
}

// ============================================================
// 2. PROSES UPDATE STATUS
// ============================================================
if (isset($_POST['update_status'])) {
    $report_id = (int)$_POST['report_id'];
    $new_status = $_POST['new_status'];
    $valid_status = ['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'];
    if (in_array($new_status, $valid_status)) {
        mysqli_query($conn, "UPDATE 03_damage_reports SET status='$new_status' WHERE report_id=$report_id");

        $wo_query = mysqli_query($conn, "SELECT work_order_id FROM 03_work_orders WHERE report_id=$report_id LIMIT 1");
        if ($wo = mysqli_fetch_assoc($wo_query)) {
            $work_order_id = $wo['work_order_id'];
            mysqli_query($conn, "
                INSERT INTO 03_work_order_activities 
                (work_order_id, activity_type, activity_note, employee_code, created_at)
                VALUES ($work_order_id, 'Status Changed', 'Status updated to: $new_status', '0', NOW())
            ");
        }

        header("Location: Damage_List.php?status_updated=1");
        exit();
    }
}

// ============================================================
// 3. PROSES TAMBAH UPDATE BERKALA (POST biasa)
// ============================================================
if (isset($_POST['add_update'])) {
    $report_id = (int)$_POST['report_id'];
    $update_note = trim($_POST['update_note']);
    
    if (!empty($update_note)) {
        // Cari work_order_id
        $wo_query = mysqli_query($conn, "SELECT work_order_id FROM 03_work_orders WHERE report_id=$report_id LIMIT 1");
        if ($wo = mysqli_fetch_assoc($wo_query)) {
            $work_order_id = $wo['work_order_id'];
            
            // Insert activity
            $insert = mysqli_query($conn, "
                INSERT INTO 03_work_order_activities 
                (work_order_id, activity_type, activity_note, employee_code, created_at)
                VALUES ($work_order_id, 'Update', '$update_note', '0', NOW())
            ");
            
            if ($insert) {
                header("Location: Damage_List.php?update_added=1");
                exit();
            } else {
                // Log error untuk debugging
                error_log("Gagal insert update: " . mysqli_error($conn));
                header("Location: Damage_List.php?update_error=1");
                exit();
            }
        } else {
            header("Location: Damage_List.php?update_error=1");
            exit();
        }
    } else {
        header("Location: Damage_List.php?update_error=1");
        exit();
    }
}

// ============================================================
// 4. PROSES CLOSE TICKET
// ============================================================
if (isset($_GET['close'])) {
    $report_id = (int)$_GET['close'];
    
    $woQuery = mysqli_query($conn, "
        SELECT work_order_id, technician_id
        FROM 03_work_orders
        WHERE report_id = $report_id
        ORDER BY work_order_id DESC
        LIMIT 1
    ");
    $wo = mysqli_fetch_assoc($woQuery);
    
    if ($wo) {
        $work_order_id = $wo['work_order_id'];
        $technician_id = $wo['technician_id'];
        mysqli_query($conn, "UPDATE 03_technicians SET status='Available' WHERE technician_id=$technician_id");
        mysqli_query($conn, "UPDATE 03_work_orders SET work_status='Completed' WHERE work_order_id=$work_order_id");
        mysqli_query($conn, "
            INSERT INTO 03_work_order_activities 
            (work_order_id, activity_type, activity_note, employee_code, created_at)
            VALUES ($work_order_id, 'Closed', 'Ticket closed by Facility Manager', '0', NOW())
        ");
    }
    mysqli_query($conn, "UPDATE 03_damage_reports SET status='Closed' WHERE report_id=$report_id");
    header("Location: Damage_List.php?status_updated=1");
    exit();
}

// ============================================================
// 5. QUERY UTAMA
// ============================================================
$tickets = mysqli_query($conn, "
    SELECT
        dr.report_id,
        dr.ticket_id,
        dr.status,
        dr.created_by,
        dr.created_at,
        dr.asset_id,
        a.asset_code,
        a.name AS asset_name,
        a.category AS asset_category,
        a.current_location AS location,
        tk.floor_name,
        tk.damage_type,
        wo.work_order_id,
        wo.work_order_number,
        wo.work_status,
        wo.priority,
        wo.sla_target,
        wo.assigned_at,
        p.nama AS technician_name,
        t.photo
    FROM 03_damage_reports dr
    LEFT JOIN 05_tiket tk ON dr.ticket_id = tk.id
    LEFT JOIN 03_assets a ON dr.asset_id = a.asset_id
    LEFT JOIN (
        SELECT wo1.*
        FROM 03_work_orders wo1
        INNER JOIN (
            SELECT report_id, MAX(work_order_id) AS latest_id
            FROM 03_work_orders
            GROUP BY report_id
        ) wo2 ON wo1.report_id = wo2.report_id AND wo1.work_order_id = wo2.latest_id
    ) wo ON dr.report_id = wo.report_id
    LEFT JOIN 03_technicians t ON wo.technician_id = t.technician_id
    LEFT JOIN 07_pegawai p ON t.NIK = p.nik
    ORDER BY dr.created_at DESC
");

// ============================================================
// 6. QUERY DETAIL DRAWER
// ============================================================
$ticketDetails = [];
$detailQuery = mysqli_query($conn, "
    SELECT
        dr.report_id,
        dr.ticket_id,
        dr.status,
        dr.created_by,
        dr.created_at,
        dr.asset_id,
        a.asset_code,
        a.name AS asset_name,
        a.category AS asset_category,
        a.current_location AS location,
        tk.floor_name,
        tk.damage_type,
        tk.deskripsi AS description,
        wo.work_order_id,
        wo.work_order_number,
        wo.work_status,
        wo.required_skill,
        wo.priority,
        wo.sla_target,
        wo.assigned_at,
        p.nama AS technician_name,
        (SELECT ts.skill_name
         FROM 03_technician_skills ts
         WHERE ts.technician_id = t.technician_id
         ORDER BY ts.proficiency_level DESC
         LIMIT 1) AS specialization,
        (SELECT woa.attachment_file
         FROM 03_work_order_activities woa
         WHERE woa.work_order_id = wo.work_order_id
           AND woa.attachment_file IS NOT NULL
         ORDER BY woa.created_at ASC
         LIMIT 1) AS attachment_file
    FROM 03_damage_reports dr
    LEFT JOIN 05_tiket tk ON dr.ticket_id = tk.id
    LEFT JOIN 03_assets a ON dr.asset_id = a.asset_id
    LEFT JOIN (
        SELECT wo1.*
        FROM 03_work_orders wo1
        INNER JOIN (
            SELECT report_id, MAX(work_order_id) AS latest_id
            FROM 03_work_orders
            GROUP BY report_id
        ) wo2 ON wo1.report_id = wo2.report_id AND wo1.work_order_id = wo2.latest_id
    ) wo ON dr.report_id = wo.report_id
    LEFT JOIN 03_technicians t ON wo.technician_id = t.technician_id
    LEFT JOIN 07_pegawai p ON t.NIK = p.nik
");
if ($detailQuery) {
    while ($row = mysqli_fetch_assoc($detailQuery)) {
        $ticketDetails[$row['report_id']] = $row;
    }
}

// ============================================================
// 7. TIMELINE
// ============================================================
$timelineData = [];
$timelineQuery = mysqli_query($conn, "
    SELECT woa.*, wo.report_id
    FROM 03_work_order_activities woa
    LEFT JOIN 03_work_orders wo ON woa.work_order_id = wo.work_order_id
    ORDER BY woa.created_at ASC
");
while ($row = mysqli_fetch_assoc($timelineQuery)) {
    $timelineData[$row['report_id']][] = $row;
}

// ============================================================
// 8. STATISTIK
// ============================================================
$criticalCount = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM 03_damage_reports dr
    LEFT JOIN 03_work_orders wo ON dr.report_id = wo.report_id
    WHERE wo.priority = 'Critical' AND dr.status NOT IN ('Resolved', 'Closed')
"))['total'] ?? 0;

$awaitingCount = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM 03_damage_reports
    WHERE status = 'Open'
"))['total'] ?? 0;

$completedToday = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM 03_damage_reports
    WHERE status = 'Resolved' AND DATE(created_at) = CURDATE()
"))['total'] ?? 0;

$totalTickets = mysqli_num_rows($tickets);

ob_start();
?>

<style>
    /* Drawer styles minimal */
    #drawerOverlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 1000;
        display: none;
    }
    #drawerOverlay:not(.hidden) { display: block; }

    #detailDrawer {
        position: fixed;
        top: 0;
        right: 0;
        height: 100%;
        width: 100%;
        max-width: 480px;
        background: var(--primary, #0B376D);
        border-left: 1px solid rgba(255,255,255,0.08);
        box-shadow: -5px 0 30px rgba(0,0,0,0.3);
        z-index: 1001;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        overflow-y: auto;
        padding: 0;
    }
    #detailDrawer.open { transform: translateX(0); }

    .drawer-header {
        position: sticky;
        top: 0;
        background: var(--primary, #0B376D);
        border-bottom: 1px solid rgba(255,255,255,0.08);
        padding: 20px 24px;
        z-index: 10;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .drawer-body { padding: 24px; }
    .drawer-section { margin-bottom: 24px; }
    .drawer-section-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--accent, #00D4D8);
        margin-bottom: 12px;
    }
    .drawer-grid-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    .drawer-flex-row {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .w-full { width: 100%; }
    .h-64 { height: 260px; }
    .object-cover { object-fit: cover; }

    /* Toast notification */
    #toastContainer {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 350px;
    }
    .toast {
        padding: 16px 20px;
        border-radius: 12px;
        background: var(--primary, #0B376D);
        border-left: 4px solid var(--accent, #00D4D8);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        color: var(--text, #F5F7FA);
        animation: slideIn 0.3s ease;
        transition: opacity 0.5s ease;
    }
    .toast.success { border-left-color: #22C55E; }
    .toast.error { border-left-color: #EF4444; }
    .toast .toast-title { font-weight: 600; font-size: 14px; }
    .toast .toast-message { font-size: 13px; opacity: 0.8; margin-top: 4px; }
    .toast .toast-timer { font-size: 11px; opacity: 0.5; margin-top: 6px; text-align: right; }
    @keyframes slideIn {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
</style>

<!-- CONTENT -->
<div class="content-body">
    <div style="margin-bottom: 24px;">
        <h2 style="font-size: 24px; font-weight: 700; color: var(--text, #F5F7FA); margin: 0 0 4px 0;">Incoming Tickets</h2>
        <nav style="font-size: 12px; color: rgba(245,247,250,0.6);">
            <span>Operations</span> / <span style="color: var(--accent, #00D4D8);">Tickets Queue</span>
        </nav>
    </div>

    <?php if (isset($_GET['status_updated'])): ?>
        <div style="background:rgba(34,197,94,0.1); border-left:4px solid #22C55E; padding:16px; margin-bottom:24px; border-radius:8px;">
            <span style="color:#22C55E;">✅ Status updated successfully.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['update_added'])): ?>
        <div style="background:rgba(34,197,94,0.1); border-left:4px solid #22C55E; padding:16px; margin-bottom:24px; border-radius:8px;">
            <span style="color:#22C55E;">✅ Update added successfully.</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['update_error'])): ?>
        <div style="background:rgba(239,68,68,0.1); border-left:4px solid #EF4444; padding:16px; margin-bottom:24px; border-radius:8px;">
            <span style="color:#EF4444;">❌ Failed to add update. Please try again.</span>
        </div>
    <?php endif; ?>

    <!-- Tabel -->
    <div class="card" style="overflow: hidden; padding: 0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Asset Name</th>
                        <th>Location</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Technician</th>
                        <th>SLA</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($tickets)): ?>
                        <tr style="cursor:pointer;" onclick="openDrawer(<?= $row['report_id'] ?>)">
                            <td><strong style="color: var(--accent, #00D4D8);"><?= htmlspecialchars($row['ticket_id'] ?? '') ?></strong></td>
                            <td>
                                <div>
                                    <div><?= htmlspecialchars($row['asset_name'] ?? '') ?></div>
                                    <div style="font-size:12px; color:rgba(245,247,250,0.6);"><?= htmlspecialchars($row['damage_type'] ?? '') ?></div>
                                </div>
                            </td>
                            <td style="color:rgba(245,247,250,0.6);">
                                <?= htmlspecialchars($row['location'] ?? '') ?>
                                <?= htmlspecialchars($row['floor_name'] ?? '') ?>
                            </td>
                            <td>
                                <?php
                                $priorityColor = '';
                                switch ($row['priority']) {
                                    case 'Critical': $priorityColor = 'danger'; break;
                                    case 'High':     $priorityColor = 'warning'; break;
                                    case 'Medium':   $priorityColor = 'success'; break;
                                    default:         $priorityColor = 'secondary';
                                }
                                ?>
                                <span class="badge badge-<?= $priorityColor ?>"><?= htmlspecialchars($row['priority'] ?? 'N/A') ?></span>
                            </td>
                            <td><span class="badge"><?= htmlspecialchars($row['status'] ?? '') ?></span></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if (!empty($row['technician_name'])): ?>
                                        <span style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; background:var(--secondary, #167E80); border-radius:50%; font-size:12px; font-weight:bold;"><?= strtoupper(substr($row['technician_name'], 0, 1)) ?></span>
                                        <span><?= htmlspecialchars($row['technician_name']) ?></span>
                                    <?php else: ?>
                                        <span style="display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; background:var(--secondary, #167E80); border-radius:50%; font-size:12px; font-weight:bold;">UC</span>
                                        <span style="color:rgba(245,247,250,0.6);">Unassigned</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $percent = 100;
                                if (!empty($row['sla_target']) && !empty($row['created_at'])) {
                                    $total = strtotime($row['sla_target']) - strtotime($row['created_at']);
                                    $remain = strtotime($row['sla_target']) - time();
                                    if ($total > 0) {
                                        $percent = max(0, min(100, ($remain / $total) * 100));
                                    }
                                }
                                ?>
                                <div>
                                    <div style="display:flex; justify-content:space-between; font-size:12px;">
                                        <span style="color:var(--accent, #00D4D8); font-weight:bold;"><?= round($percent) ?>%</span>
                                        <span style="color:rgba(245,247,250,0.6);">SLA</span>
                                    </div>
                                    <div style="height:6px; width:100%; background:var(--primary-dark, #082A53); border-radius:4px; overflow:hidden; margin-top:4px;">
                                        <div style="height:100%; background:linear-gradient(to right, var(--secondary, #167E80), var(--accent, #00D4D8)); width:<?= round($percent) ?>%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:12px; color:rgba(245,247,250,0.6);"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:16px 24px; border-top:1px solid rgba(255,255,255,0.08); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.03);">
            <span style="font-size:12px; color:rgba(245,247,250,0.6);">Showing 1 to <?= $totalTickets ?> of <?= $totalTickets ?> results</span>
            <div style="display:flex; gap:8px;">
                <button style="padding:4px 12px; border-radius:6px; opacity:0.3; cursor:not-allowed;" disabled>‹</button>
                <button style="width:32px; height:32px; border-radius:6px; background:var(--accent, #00D4D8); color:var(--primary-dark, #082A53); font-weight:bold; border:none;">1</button>
                <button style="padding:4px 12px; border-radius:6px; opacity:0.3; cursor:not-allowed;" disabled>›</button>
            </div>
        </div>
    </div>

    <!-- Statistik -->
    <div class="stats-grid" style="margin-top: 24px;">
        <div class="stat-card" style="border-left-color: #EF4444;">
            <div class="stat-icon" style="background:rgba(239,68,68,0.15); color:#EF4444;">
                <span style="font-size:22px;">⚠</span>
            </div>
            <div class="stat-info">
                <h3><?= $criticalCount ?></h3>
                <p>Critical Tickets</p>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: var(--accent, #00D4D8);">
            <div class="stat-icon" style="background:rgba(0,212,216,0.15); color:var(--accent, #00D4D8);">
                <span style="font-size:22px;">⏳</span>
            </div>
            <div class="stat-info">
                <h3><?= $awaitingCount ?></h3>
                <p>Awaiting Response</p>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: #22C55E;">
            <div class="stat-icon" style="background:rgba(34,197,94,0.15); color:#22C55E;">
                <span style="font-size:22px;">✔</span>
            </div>
            <div class="stat-info">
                <h3><?= $completedToday ?></h3>
                <p>Completed Today</p>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: var(--secondary, #167E80);">
            <div class="stat-icon" style="background:rgba(22,126,128,0.15); color:var(--secondary, #167E80);">
                <span style="font-size:22px;">🎫</span>
            </div>
            <div class="stat-info">
                <h3><?= $totalTickets ?></h3>
                <p>Total Tickets</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include "../../includes/navbar.php";
?>

<!-- Toast Container -->
<div id="toastContainer"></div>

<!-- DRAWER OVERLAY -->
<div id="drawerOverlay" class="hidden" onclick="closeDrawer()"></div>

<!-- DRAWER PANEL -->
<aside id="detailDrawer">
    <div class="drawer-header">
        <div>
            <span id="drawerTicketID" class="badge" style="background:rgba(0,212,216,0.15); color:var(--accent, #00D4D8);">TK-0000</span>
            <h3 id="drawerAssetName" style="margin-top:12px; font-size:20px; font-weight:700; color:var(--text, #F5F7FA);">Asset Name</h3>
        </div>
        <button onclick="closeDrawer()" style="background:none; border:none; color:rgba(245,247,250,0.6); font-size:24px; cursor:pointer;">✕</button>
    </div>

    <div style="padding:0 24px 16px; display:flex; gap:8px;">
        <span id="drawerPriority" class="badge badge-danger">Critical</span>
        <span id="drawerStatus" class="badge badge-success">Open</span>
    </div>

    <div class="drawer-body">
        <!-- Photo -->
        <div class="drawer-section">
            <div class="drawer-section-title">Photo Evidence</div>
            <div class="card" style="padding:0; overflow:hidden;">
                <img id="drawerPhoto" src="../Uploads/no-image.jpg" class="w-full h-64 object-cover">
            </div>
        </div>

        <!-- Asset Details -->
        <div class="drawer-section">
            <div class="drawer-section-title">Asset Details</div>
            <div class="card drawer-grid-2col">
                <div>
                    <div style="font-size:12px; color:rgba(245,247,250,0.6);">Asset Code</div>
                    <div id="drawerAssetCode" style="font-weight:600;">-</div>
                </div>
                <div>
                    <div style="font-size:12px; color:rgba(245,247,250,0.6);">Category</div>
                    <div id="drawerCategory" style="font-weight:600;">-</div>
                </div>
                <div>
                    <div style="font-size:12px; color:rgba(245,247,250,0.6);">Location</div>
                    <div id="drawerLocation" style="font-weight:600;">-</div>
                </div>
                <div>
                    <div style="font-size:12px; color:rgba(245,247,250,0.6);">Damage Type</div>
                    <div id="drawerDamageType" style="font-weight:600;">-</div>
                </div>
            </div>
        </div>

        <!-- SLA -->
        <div class="drawer-section">
            <div class="drawer-section-title">SLA Countdown</div>
            <div class="card">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="color:rgba(245,247,250,0.6);">Remaining SLA</span>
                    <span id="drawerSLA" style="font-weight:700; color:#EF4444;">--</span>
                </div>
                <div style="height:8px; background:var(--primary-dark, #082A53); border-radius:4px; overflow:hidden;">
                    <div id="slaBar" style="height:100%; background:linear-gradient(to right, #EF4444, var(--secondary, #167E80), #22C55E); width:100%;"></div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="drawer-section">
            <div class="drawer-section-title">Damage Description</div>
            <div class="card">
                <p id="drawerDescription" style="line-height:1.6; color:rgba(245,247,250,0.7);">-</p>
            </div>
        </div>

        <!-- Technician -->
        <div class="drawer-section">
            <div class="drawer-section-title">Assigned Technician</div>
            <div class="card drawer-flex-row">
                <div id="techAvatar" style="width:48px; height:48px; background:var(--accent, #00D4D8); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--primary-dark, #082A53);">T</div>
                <div>
                    <div id="drawerTechnician" style="font-weight:600;">Unassigned</div>
                    <div id="drawerSpecialization" style="font-size:12px; color:rgba(245,247,250,0.6);">-</div>
                </div>
            </div>
        </div>

        <!-- Work Order -->
        <div class="drawer-section">
            <div class="drawer-section-title">Work Order</div>
            <div class="card">
                <div id="drawerWorkOrder" style="font-weight:600;">Not Created</div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="drawer-section">
            <div class="drawer-section-title">Activity Timeline</div>
            <div id="timelineContainer" style="display:flex; flex-direction:column; gap:12px;">
                <div class="card">
                    <div style="font-weight:600;">Ticket Created</div>
                    <div style="font-size:12px; color:rgba(245,247,250,0.6);">Waiting for assignment</div>
                </div>
            </div>
        </div>

        <!-- ADD UPDATE SECTION (hanya muncul jika status = In Progress) -->
        <div class="drawer-section" id="updateSection" style="display:none;">
            <div class="drawer-section-title">Add Update</div>
            <div class="card">
                <form method="POST" action="">
                    <input type="hidden" name="report_id" id="updateReportId">
                    <div class="form-group">
                        <label>Update Note</label>
                        <textarea name="update_note" id="updateNote" style="width:100%; background:var(--primary-dark, #082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:10px 14px; color:var(--text); min-height:60px; resize:vertical;" placeholder="Write update note..." required></textarea>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                        <span style="font-size:12px; color:rgba(245,247,250,0.5);">Add progress note to timeline</span>
                        <button type="submit" name="add_update" class="btn btn-primary">Post Update</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:grid; grid-template-columns:1fr; gap:12px; margin-top:8px;">
            <a id="assignBtn" href="Work_Order.php" class="btn btn-primary" style="justify-content:center; width:100%;">Assign Technician</a>
            
            <!-- Change Status Form -->
            <form method="POST">
                <input type="hidden" name="report_id" id="statusReportId">
                <div style="display:flex; gap:8px;">
                    <select name="new_status" id="statusSelect" style="flex:1; background:var(--primary-dark, #082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:10px 14px; color:var(--text);">
                        <option value="Open">Open</option>
                        <option value="Assigned">Assigned</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-primary" style="white-space:nowrap;">Update</button>
                </div>
            </form>
        </div>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ticketData = <?= json_encode($ticketDetails, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
        const timelineData = <?= json_encode($timelineData, JSON_HEX_TAG | JSON_HEX_APOS) ?>;

        window.ticketData = ticketData;
        window.timelineData = timelineData;

        window.openDrawer = function(reportId) {
            const data = window.ticketData[reportId];
            if (!data) {
                console.warn('No data for report_id:', reportId);
                return;
            }

            document.getElementById('drawerTicketID').innerText = data.ticket_id || 'TK-0000';
            document.getElementById('drawerAssetName').innerText = data.asset_name || 'Asset Name';
            document.getElementById('drawerAssetCode').innerText = data.asset_code || '-';
            document.getElementById('drawerCategory').innerText = data.asset_category || '-';
            document.getElementById('drawerLocation').innerText = (data.location || '') + ' - ' + (data.floor_name || '');
            document.getElementById('drawerDamageType').innerText = data.damage_type || '-';
            document.getElementById('drawerDescription').innerText = data.description || 'Tidak ada deskripsi';
            document.getElementById('drawerPriority').innerText = data.priority || 'N/A';
            
            const statusBadge = document.getElementById('drawerStatus');
            statusBadge.innerText = data.status || 'N/A';
            statusBadge.className = 'badge';
            if (data.status === 'Open') statusBadge.classList.add('badge-warning');
            else if (data.status === 'Assigned' || data.status === 'In Progress') statusBadge.classList.add('badge-primary');
            else if (data.status === 'Resolved') statusBadge.classList.add('badge-success');
            else if (data.status === 'Closed') statusBadge.classList.add('badge-secondary');
            
            document.getElementById('drawerTechnician').innerText = data.technician_name || 'Unassigned';
            document.getElementById('drawerSpecialization').innerText = data.specialization || '-';
            document.getElementById('drawerWorkOrder').innerText = data.work_order_number || 'Not Created';

            // Set status dropdown
            const statusSelect = document.getElementById('statusSelect');
            if (data.status) {
                statusSelect.value = data.status;
            }
            document.getElementById('statusReportId').value = data.report_id;

            // Update section: hanya muncul jika status = In Progress dan ada work_order_id
            const updateSection = document.getElementById('updateSection');
            const updateReportId = document.getElementById('updateReportId');
            if (data.work_order_id && data.status === 'In Progress') {
                updateSection.style.display = 'block';
                updateReportId.value = data.report_id;
            } else {
                updateSection.style.display = 'none';
            }

            // SLA
            if (data.sla_target && data.assigned_at) {
                let start = new Date(data.assigned_at);
                let end = new Date(data.sla_target);
                let now = new Date();
                let total = end - start;
                let remain = end - now;
                let percent = Math.max(0, Math.min(100, (remain / total) * 100));
                let hours = Math.floor(remain / (1000 * 60 * 60));
                let mins = Math.floor((remain % (1000 * 60 * 60)) / (1000 * 60));
                document.getElementById('drawerSLA').innerText = hours + 'h ' + mins + 'm';
                document.getElementById('slaBar').style.width = percent + '%';
            } else {
                document.getElementById('drawerSLA').innerText = 'Not Assigned';
                document.getElementById('slaBar').style.width = '100%';
            }

            // Avatar
            let avatar = document.getElementById('techAvatar');
            if (data.technician_name && data.technician_name !== 'Unassigned') {
                avatar.innerText = data.technician_name.charAt(0).toUpperCase();
            } else {
                avatar.innerText = 'U';
            }

            // Photo
            if (data.attachment_file) {
                document.getElementById('drawerPhoto').src = '../uploads/damage_reports/' + data.attachment_file;
            } else {
                document.getElementById('drawerPhoto').src = '../Uploads/no-image.jpg';
            }

            // Assign button
            let assignBtn = document.getElementById('assignBtn');
            assignBtn.href = 'Work_Order.php?report_id=' + data.report_id;
            if (data.technician_name && data.technician_name !== 'Unassigned') {
                assignBtn.innerHTML = '🔄 Reassign Technician';
            } else {
                assignBtn.innerHTML = '➕ Assign Technician';
            }

            // Timeline
            let timeline = window.timelineData[reportId] || [];
            let html = `<div class="card"><div style="font-weight:600;">Ticket Created</div><div style="font-size:12px; color:rgba(245,247,250,0.6);">${data.created_at || ''}</div></div>`;
            timeline.forEach(item => {
                let typeLabel = item.activity_type || 'Activity';
                html += `
                    <div class="card">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="font-weight:600;">${typeLabel}</span>
                            <span style="font-size:11px; color:rgba(245,247,250,0.4);">${item.created_at || ''}</span>
                        </div>
                        <div style="font-size:14px; color:rgba(245,247,250,0.7); margin-top:4px;">
                            ${item.activity_note || ''}
                        </div>
                    </div>
                `;
            });
            document.getElementById('timelineContainer').innerHTML = html;

            document.getElementById('detailDrawer').classList.add('open');
            document.getElementById('drawerOverlay').classList.remove('hidden');
        };

        window.closeDrawer = function() {
            document.getElementById('detailDrawer').classList.remove('open');
            document.getElementById('drawerOverlay').classList.add('hidden');
        };
    });
</script>