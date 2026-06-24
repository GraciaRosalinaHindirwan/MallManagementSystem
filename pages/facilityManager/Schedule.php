<?php

$page_title = "Maintenance Schedule";
$page = "schedule";

include '../../config/konek.php';

/* ==========================================
   ADD SCHEDULE
========================================== */

if(isset($_POST['add_schedule']))
{
    $asset_id   = (int)$_POST['asset_id'];
    $tanggal    = mysqli_real_escape_string($conn,$_POST['tanggal']);
    $frekuensi  = mysqli_real_escape_string($conn,$_POST['frekuensi']);

    mysqli_query($conn,"
        INSERT INTO 03_maintenance_schedule
        (
            asset_id,
            tanggal,
            frekuensi,
            status
        )
        VALUES
        (
            '$asset_id',
            '$tanggal',
            '$frekuensi',
            'pending'
        )
    ");

    header("Location: Schedule.php");
    exit;
}

/* ==========================================
   EDIT SCHEDULE
========================================== */

if(isset($_POST['edit_schedule']))
{
    $id         = (int)$_POST['id'];
    $asset_id   = (int)$_POST['asset_id'];
    $tanggal    = mysqli_real_escape_string($conn,$_POST['tanggal']);
    $frekuensi  = mysqli_real_escape_string($conn,$_POST['frekuensi']);
    $status     = mysqli_real_escape_string($conn,$_POST['status']);

    mysqli_query($conn,"
        UPDATE 03_maintenance_schedule
        SET
            asset_id='$asset_id',
            tanggal='$tanggal',
            frekuensi='$frekuensi',
            status='$status'
        WHERE id='$id'
    ");

    header("Location: Schedule.php");
    exit;
}

/* ==========================================
   DELETE
========================================== */

if(isset($_GET['delete']))
{
    $id = (int)$_GET['delete'];

    mysqli_query($conn,"
        DELETE FROM 03_maintenance_schedule
        WHERE id='$id'
    ");

    header("Location: Schedule.php");
    exit;
}

/* ==========================================
   QUERY SCHEDULE
========================================== */

$query = mysqli_query($conn,"
    SELECT
        ms.*,
        a.asset_code,
        a.name,
        a.current_location

    FROM 03_maintenance_schedule ms

    LEFT JOIN 03_assets a
        ON ms.asset_id = a.asset_id

    ORDER BY ms.tanggal ASC
");

if(!$query)
{
    die(mysqli_error($conn));
}

/* ==========================================
   ASSET DROPDOWN
========================================== */

$assetQuery = mysqli_query($conn,"
    SELECT
        asset_id,
        asset_code,
        name
    FROM 03_assets
    ORDER BY name ASC
");

/* ==========================================
   STATISTIK
========================================== */

$totalSchedule = mysqli_num_rows($query);

$scheduledCount = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) total
    FROM 03_maintenance_schedule
    WHERE LOWER(status)='scheduled'
"))['total'];

$completedCount = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) total
    FROM 03_maintenance_schedule
    WHERE LOWER(status)='completed'
"))['total'];

$overdueCount = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT COUNT(*) total
    FROM 03_maintenance_schedule
    WHERE tanggal < CURDATE()
    AND LOWER(status) <> 'completed'
"))['total'];

/* ==========================================
   REMINDER HARI INI
========================================== */

$todayReminderQuery = mysqli_query($conn,"
    SELECT
        a.name
    FROM 03_maintenance_schedule ms

    LEFT JOIN 03_assets a
        ON ms.asset_id = a.asset_id

    WHERE DATE(ms.tanggal)=CURDATE()
    AND LOWER(ms.status) <> 'completed'
");

$todayPM = [];

while($row = mysqli_fetch_assoc($todayReminderQuery))
{
    $todayPM[] = $row;
}

$totalTodayPM = count($todayPM);

ob_start();
?>
<style>

.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    z-index:9999;
}

.modal-content{
    background:#021F42;
    width:500px;
    max-width:90%;
    margin:50px auto;
    border-radius:10px;
    padding:20px;
}

.modal-content h3{
    margin-bottom:15px;
}

.form-group{
    margin-bottom:15px;
}

.form-group label{
    display:block;
    margin-bottom:5px;
    font-weight:bold;
}

.form-group input,
.form-group select{
    width:100%;
    padding:10px;
}

.action-btn{
    display:flex;
    gap:8px;
}

</style>

<div class="content-body">

    <!-- HEADER -->

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">

        <div>

            <h2 style="margin:0;">
                Maintenance Schedule
            </h2>

            <small>
                Facility Manager / Preventive Maintenance
            </small>

        </div>

        <button
            class="btn btn-primary"
            onclick="openAddModal()"
        >
            + Add Schedule
        </button>

    </div>

    <!-- STATISTIK -->

    <div class="stats-grid" style="margin-bottom:25px;">

        <div class="stat-card">
            <div class="stat-info">
                <h3><?= $scheduledCount ?></h3>
                <p>Scheduled</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3><?= $completedCount ?></h3>
                <p>Completed</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3><?= $overdueCount ?></h3>
                <p>Overdue</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3><?= $totalSchedule ?></h3>
                <p>Total Schedule</p>
            </div>
        </div>

    </div>

    <!-- TABLE -->

    <div class="card" style="padding:0;overflow:hidden;">

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>Asset Code</th>
                        <th>Asset Name</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Frequency</th>
                        <th>Status</th>
                        <th width="180">Action</th>

                    </tr>

                </thead>

                <tbody>

                <?php mysqli_data_seek($query,0); ?>

                <?php if(mysqli_num_rows($query)>0): ?>

                    <?php while($row=mysqli_fetch_assoc($query)): ?>

                        <?php

                        $status = strtolower($row['status']);

                        $badge = "secondary";

                        if($status=="scheduled")
                        {
                            $badge = "info";
                        }
                        elseif($status=="completed")
                        {
                            $badge = "success";
                        }
                        elseif($status=="pending")
                        {
                            $badge = "warning";
                        }

                        ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($row['asset_code']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['name']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['current_location']) ?>
                            </td>

                            <td>
                                <?= date('d M Y',strtotime($row['tanggal'])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['frekuensi']) ?>
                            </td>

                            <td>

                                <span class="badge badge-<?= $badge ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>

                            </td>

                            <td>

                                <div class="action-btn">

                                    <button
                                        class="btn"
                                        onclick='openEditModal(
                                            <?= $row['id'] ?>,
                                            <?= $row['asset_id'] ?>,
                                            "<?= $row['tanggal'] ?>",
                                            "<?= $row['frekuensi'] ?>",
                                            "<?= $row['status'] ?>"
                                        )'
                                    >
                                        Edit
                                    </button>

                                    <a
                                        href="?delete=<?= $row['id'] ?>"
                                        class="btn"
                                        onclick="return confirm('Hapus schedule ini?')"
                                    >
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7" style="text-align:center;padding:30px;">
                            No Schedule Found
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- ==========================================
     ADD MODAL
========================================== -->

<div id="addModal" class="modal">

    <div class="modal-content">

        <h3>Add Schedule</h3>

        <form method="POST">

            <div class="form-group">

                <label>Asset</label>

                <select
                    name="asset_id"
                    required
                >

                    <option value="">
                        -- Select Asset --
                    </option>

                    <?php
                    mysqli_data_seek($assetQuery,0);

                    while($asset=mysqli_fetch_assoc($assetQuery)):
                    ?>

                        <option
                            value="<?= $asset['asset_id'] ?>"
                        >
                            <?= $asset['asset_code'] ?>
                            -
                            <?= $asset['name'] ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Date</label>

                <input
                    type="date"
                    name="tanggal"
                    required
                >

            </div>

            <div class="form-group">

                <label>Frequency</label>

                <select
                    name="frekuensi"
                    required
                >

                    <option value="Daily">Daily</option>

                    <option value="Weekly">Weekly</option>

                    <option value="Monthly">Monthly</option>

                    <option value="Quarterly">
                        Quarterly
                    </option>

                    <option value="Yearly">
                        Yearly
                    </option>

                </select>

            </div>

            <div
                style="
                display:flex;
                gap:10px;
                justify-content:flex-end;
                "
            >

                <button
                    type="button"
                    class="btn"
                    onclick="closeAddModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="add_schedule"
                    class="btn btn-primary"
                >
                    Save
                </button>

            </div>

        </form>

    </div>

</div>

<!-- ==========================================
     EDIT MODAL
========================================== -->

<div id="editModal" class="modal">

    <div class="modal-content">

        <h3>Edit Schedule</h3>

        <form method="POST">

            <input
                type="hidden"
                name="id"
                id="edit_id"
            >

            <div class="form-group">

                <label>Asset</label>

                <select
                    name="asset_id"
                    id="edit_asset"
                    required
                >

                    <?php
                    mysqli_data_seek($assetQuery,0);

                    while($asset=mysqli_fetch_assoc($assetQuery)):
                    ?>

                        <option
                            value="<?= $asset['asset_id'] ?>"
                        >
                            <?= $asset['asset_code'] ?>
                            -
                            <?= $asset['name'] ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Date</label>

                <input
                    type="date"
                    name="tanggal"
                    id="edit_tanggal"
                    required
                >

            </div>

            <div class="form-group">

                <label>Frequency</label>

                <input
                    type="text"
                    name="frekuensi"
                    id="edit_frekuensi"
                    required
                >

            </div>

            <div class="form-group">

                <label>Status</label>

                <select
                    name="status"
                    id="edit_status"
                    required
                >

                    <option value="pending">
                        Pending
                    </option>

                    <option value="scheduled">
                        Scheduled
                    </option>

                    <option value="completed">
                        Completed
                    </option>

                </select>

            </div>

            <div
                style="
                display:flex;
                gap:10px;
                justify-content:flex-end;
                "
            >

                <button
                    type="button"
                    class="btn"
                    onclick="closeEditModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    name="edit_schedule"
                    class="btn btn-primary"
                >
                    Update
                </button>

            </div>

        </form>

    </div>

</div>
<?php if($totalTodayPM > 0): ?>

<div id="pmReminder">

    <div class="pm-header">
        🔔 Preventive Maintenance Reminder
    </div>

    <div class="pm-body">

        <p>
            Ada
            <strong><?= $totalTodayPM ?></strong>
            jadwal preventive maintenance hari ini.
        </p>

        <hr>

        <?php foreach($todayPM as $item): ?>

            <div class="pm-item">
                • <?= htmlspecialchars($item['name']) ?>
            </div>

        <?php endforeach; ?>

        <button
            class="pm-close btn btn-primary"
            onclick="closeReminder()"
        >
            Tutup
        </button>

    </div>

</div>

<?php endif; ?>

<style>

#pmReminder{

    position:fixed;

    left:20px;
    bottom:20px;

    width:350px;

    background:#0B376D;
    color:white;

    border-radius:12px;

    overflow:hidden;

    box-shadow:0 10px 25px rgba(0,0,0,.3);

    z-index:99999;

    display:none;
}

.pm-header{

    background:#082A53;

    padding:12px 15px;

    font-weight:bold;
}

.pm-body{

    padding:15px;
}

.pm-item{

    padding:5px 0;
}

.pm-close{

    margin-top:15px;
}

</style>

<script>

/* ==========================
   ADD MODAL
========================== */

function openAddModal()
{
    document.getElementById('addModal').style.display='block';
}

function closeAddModal()
{
    document.getElementById('addModal').style.display='none';
}

/* ==========================
   EDIT MODAL
========================== */

function openEditModal(
    id,
    asset,
    tanggal,
    frekuensi,
    status
)
{
    document.getElementById('edit_id').value=id;
    document.getElementById('edit_asset').value=asset;
    document.getElementById('edit_tanggal').value=tanggal;
    document.getElementById('edit_frekuensi').value=frekuensi;
    document.getElementById('edit_status').value=status.toLowerCase();

    document.getElementById('editModal').style.display='block';
}

function closeEditModal()
{
    document.getElementById('editModal').style.display='none';
}

/* ==========================
   CLOSE MODAL IF CLICK OUTSIDE
========================== */

window.onclick = function(event)
{
    let addModal =
        document.getElementById('addModal');

    let editModal =
        document.getElementById('editModal');

    if(event.target == addModal)
    {
        closeAddModal();
    }

    if(event.target == editModal)
    {
        closeEditModal();
    }
}

/* ==========================
   REMINDER
========================== */

const totalPM = <?= $totalTodayPM ?>;

function showReminder()
{
    const popup =
        document.getElementById('pmReminder');

    if(popup)
    {
        popup.style.display='block';
    }
}

function closeReminder()
{
    const popup =
        document.getElementById('pmReminder');

    if(popup)
    {
        popup.style.display='none';
    }
}

document.addEventListener(
    'DOMContentLoaded',
    function()
    {
        const currentHour =
            new Date().getHours();

        if(
            currentHour >= 7 &&
            totalPM > 0
        )
        {
            showReminder();
        }
    }
);

</script>

<?php

$content = ob_get_clean();

include '../../includes/navbar.php';

?>