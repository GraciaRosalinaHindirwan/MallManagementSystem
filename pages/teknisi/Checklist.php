<?php
require_once 'auth/checkSession.php';
$page_title = "Checklist Inspection";
$page = "checklist";

include '../MallManagementSystem/config/konek.php';

/* ==========================================
   UPLOAD FOLDER
========================================== */

$uploadDir = "../../public/asset/images/";

/* ==========================================
   ADD CHECKLIST
========================================== */

if(isset($_POST['add_checklist']))
{
    $schedule_id       = (int)$_POST['schedule_id'];
    $kondisi           = mysqli_real_escape_string($conn,$_POST['kondisi']);
    $catatan           = mysqli_real_escape_string($conn,$_POST['catatan']);
    $tanggal_inspeksi  = date('Y-m-d');

    $fotoName = '';

    if(
        isset($_FILES['foto']) &&
        $_FILES['foto']['error'] == 0
    )
    {
        $ext = pathinfo(
            $_FILES['foto']['name'],
            PATHINFO_EXTENSION
        );

        $fotoName =
            "checklist_" .
            time() .
            "." .
            $ext;

        move_uploaded_file(
            $_FILES['foto']['tmp_name'],
            $uploadDir . $fotoName
        );
    }

    mysqli_query($conn,"
        INSERT INTO 03_checklist
        (
            schedule_id,
            kondisi,
            catatan,
            foto,
            tanggal_inspeksi
        )
        VALUES
        (
            '$schedule_id',
            '$kondisi',
            '$catatan',
            '$fotoName',
            '$tanggal_inspeksi'
        )
    ");

    /* ==========================
       AUTO COMPLETE SCHEDULE
    ========================== */

    mysqli_query($conn,"
        UPDATE 03_maintenance_schedule
        SET status='completed'
        WHERE id='$schedule_id'
    ");

    header("Location: Checklist.php");
    exit;
}

/* ==========================================
   DELETE CHECKLIST
========================================== */

if(isset($_GET['delete']))
{
    $id = (int)$_GET['delete'];

    $getFoto = mysqli_query($conn,"
        SELECT foto
        FROM 03_checklist
        WHERE id='$id'
    ");

    $foto = mysqli_fetch_assoc($getFoto);

    if(
        !empty($foto['foto']) &&
        file_exists(
            $uploadDir .
            $foto['foto']
        )
    )
    {
        unlink(
            $uploadDir .
            $foto['foto']
        );
    }

    mysqli_query($conn,"
        DELETE FROM 03_checklist
        WHERE id='$id'
    ");

    header("Location: Checklist.php");
    exit;
}

/* ==========================================
   DROPDOWN SCHEDULE
========================================== */

$scheduleQuery = mysqli_query($conn,"
    SELECT
        ms.id,
        ms.tanggal,
        ms.frekuensi,

        a.asset_code,
        a.name

    FROM 03_maintenance_schedule ms

    LEFT JOIN 03_assets a
        ON ms.asset_id = a.asset_id

    WHERE LOWER(ms.status) <> 'completed'

    ORDER BY ms.tanggal ASC
");

/* ==========================================
   LIST CHECKLIST
========================================== */

$checklistQuery = mysqli_query($conn,"
    SELECT

        c.*,

        ms.tanggal,
        ms.frekuensi,

        a.asset_code,
        a.name,
        a.current_location

    FROM 03_checklist c

    LEFT JOIN 03_maintenance_schedule ms
        ON c.schedule_id = ms.id

    LEFT JOIN 03_assets a
        ON ms.asset_id = a.asset_id

    ORDER BY c.id DESC
");

if(!$checklistQuery)
{
    die(mysqli_error($conn));
}

/* ==========================================
   STATISTIK
========================================== */

$totalChecklist =
mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total FROM 03_checklist"
    )
)['total'];

$goodCount =
mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "
        SELECT COUNT(*) total
        FROM 03_checklist
        WHERE kondisi='Good'
        "
    )
)['total'];

$repairCount =
mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "
        SELECT COUNT(*) total
        FROM 03_checklist
        WHERE kondisi='Need Repair'
        "
    )
)['total'];

$criticalCount =
mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "
        SELECT COUNT(*) total
        FROM 03_checklist
        WHERE kondisi='Critical'
        "
    )
)['total'];

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
    width:600px;
    max-width:95%;
    margin:40px auto;
    border-radius:10px;
    padding:20px;
}

.action-btn{
    display:flex;
    gap:8px;
}

</style>

<div class="content-body">

    <!-- HEADER -->

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:25px;
    ">

        <div>

            <h2 style="margin:0;">
                Inspection Checklist
            </h2>

            <small>
                Facility Manager / Technician Inspection
            </small>

        </div>

        <button
            class="btn btn-primary"
            onclick="openAddModal()"
        >
            + Add Checklist
        </button>

    </div>

    <!-- STATISTIK -->

    <div
        class="stats-grid"
        style="margin-bottom:25px;"
    >

        <div class="stat-card">
            <div class="stat-info">
                <h3><?= $totalChecklist ?></h3>
                <p>Total Checklist</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3><?= $goodCount ?></h3>
                <p>Good</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3><?= $repairCount ?></h3>
                <p>Need Repair</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h3><?= $criticalCount ?></h3>
                <p>Critical</p>
            </div>
        </div>

    </div>

    <!-- TABLE -->

    <div
        class="card"
        style="
            padding:0;
            overflow:hidden;
        "
    >

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>Asset Code</th>
                        <th>Asset Name</th>
                        <th>Location</th>
                        <th>Condition</th>
                        <th>Inspection Date</th>
                        <th width="220">
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if(mysqli_num_rows($checklistQuery) > 0): ?>

                    <?php while($row=mysqli_fetch_assoc($checklistQuery)): ?>

                        <?php

                        $badge = "secondary";

                        if($row['kondisi']=="Good")
                        {
                            $badge = "success";
                        }

                        if($row['kondisi']=="Need Repair")
                        {
                            $badge = "warning";
                        }

                        if($row['kondisi']=="Critical")
                        {
                            $badge = "danger";
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

                                <span class="badge badge-<?= $badge ?>">
                                    <?= htmlspecialchars($row['kondisi']) ?>
                                </span>

                            </td>

                            <td>
                                <?= date('d M Y',strtotime($row['tanggal_inspeksi'])) ?>
                            </td>

                            <td>

                                <div class="action-btn">

                                    <button
                                        class="btn"
                                        onclick='showDetail(
                                            <?= json_encode($row['asset_code']) ?>,
                                            <?= json_encode($row['name']) ?>,
                                            <?= json_encode($row['current_location']) ?>,
                                            <?= json_encode($row['kondisi']) ?>,
                                            <?= json_encode($row['catatan']) ?>,
                                            <?= json_encode($row['foto']) ?>,
                                            <?= json_encode($row['tanggal_inspeksi']) ?>
                                        )'
                                    >
                                        Detail
                                    </button>

                                    <a
                                        href="?delete=<?= $row['id'] ?>"
                                        class="btn"
                                        onclick="return confirm('Hapus checklist ini?')"
                                    >
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="6"
                            style="
                                text-align:center;
                                padding:25px;
                            "
                        >
                            No Checklist Found
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- ==========================================
     ADD CHECKLIST MODAL
========================================== -->

<div id="addModal" class="modal">

    <div class="modal-content">

        <h3>Add Inspection Checklist</h3>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="form-group">

                <label>
                    Maintenance Schedule
                </label>

                <select
                    name="schedule_id"
                    required
                    style="width:100%;padding:10px;"
                >

                    <option value="">
                        -- Select Schedule --
                    </option>

                    <?php
                    mysqli_data_seek($scheduleQuery,0);

                    while($schedule=mysqli_fetch_assoc($scheduleQuery)):
                    ?>

                    <option
                        value="<?= $schedule['id'] ?>"
                    >
                        <?= $schedule['asset_code'] ?>
                        -
                        <?= $schedule['name'] ?>
                        |
                        <?= date('d M Y',strtotime($schedule['tanggal'])) ?>
                    </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <br>

            <div class="form-group">

                <label>
                    Condition
                </label>

                <select
                    name="kondisi"
                    required
                    style="width:100%;padding:10px;"
                >

                    <option value="Good">
                        Good
                    </option>

                    <option value="Need Repair">
                        Need Repair
                    </option>

                    <option value="Critical">
                        Critical
                    </option>

                </select>

            </div>

            <br>

            <div class="form-group">

                <label>
                    Inspection Note
                </label>

                <textarea
                    name="catatan"
                    rows="5"
                    style="
                        width:100%;
                        padding:10px;
                    "
                ></textarea>

            </div>

            <br>

            <div class="form-group">

                <label>
                    Inspection Photo
                </label>

                <input
                    type="file"
                    name="foto"
                    accept="image/*"
                >

            </div>

            <br>

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
                    name="add_checklist"
                    class="btn btn-primary"
                >
                    Save Checklist
                </button>

            </div>

        </form>

    </div>

</div>

<!-- ==========================================
     DETAIL MODAL
========================================== -->

<div id="detailModal" class="modal">

    <div class="modal-content">

        <h3>
            Checklist Detail
        </h3>

        <table
            style="
            width:100%;
            border-collapse:collapse;
            "
        >

            <tr>
                <td width="150">
                    Asset Code
                </td>
                <td id="detail_asset_code">
                </td>
            </tr>

            <tr>
                <td>
                    Asset Name
                </td>
                <td id="detail_asset_name">
                </td>
            </tr>

            <tr>
                <td>
                    Location
                </td>
                <td id="detail_location">
                </td>
            </tr>

            <tr>
                <td>
                    Condition
                </td>
                <td id="detail_condition">
                </td>
            </tr>

            <tr>
                <td>
                    Inspection Date
                </td>
                <td id="detail_date">
                </td>
            </tr>

        </table>

        <hr>

        <h4>
            Inspection Note
        </h4>

        <div
            id="detail_note"
            style="
            min-height:80px;
            "
        >
        </div>

        <hr>

        <h4>
            Inspection Photo
        </h4>

        <img
            id="detail_photo"
            src=""
            style="
                width:100%;
                max-height:350px;
                object-fit:contain;
                display:none;
            "
        >

        <div
            id="no_photo"
            style="
            color:#999;
            "
        >
            No Photo Available
        </div>

        <br>

        <div
            style="
            text-align:right;
            "
        >

            <button
                class="btn"
                onclick="closeDetailModal()"
            >
                Close
            </button>

        </div>

    </div>

</div>
<script>

/* ==========================================
   ADD MODAL
========================================== */

function openAddModal()
{
    document.getElementById('addModal').style.display = 'block';
}

function closeAddModal()
{
    document.getElementById('addModal').style.display = 'none';
}

/* ==========================================
   DETAIL MODAL
========================================== */

function showDetail(
    assetCode,
    assetName,
    location,
    condition,
    note,
    photo,
    inspectionDate
)
{
    document.getElementById(
        'detail_asset_code'
    ).innerHTML = assetCode;

    document.getElementById(
        'detail_asset_name'
    ).innerHTML = assetName;

    document.getElementById(
        'detail_location'
    ).innerHTML = location;

    document.getElementById(
        'detail_condition'
    ).innerHTML = condition;

    document.getElementById(
        'detail_date'
    ).innerHTML = inspectionDate;

    document.getElementById(
        'detail_note'
    ).innerHTML =
        note && note.length > 0
        ? note
        : '-';

    const photoElement =
        document.getElementById(
            'detail_photo'
        );

    const noPhoto =
        document.getElementById(
            'no_photo'
        );

    if(photo && photo !== '')
    {
        photoElement.src =
            '../../public/asset/images/' +
            photo;

        photoElement.style.display =
            'block';

        noPhoto.style.display =
            'none';
    }
    else
    {
        photoElement.style.display =
            'none';

        noPhoto.style.display =
            'block';
    }

    document.getElementById(
        'detailModal'
    ).style.display = 'block';
}

function closeDetailModal()
{
    document.getElementById(
        'detailModal'
    ).style.display = 'none';
}

/* ==========================================
   CLOSE MODAL IF CLICK OUTSIDE
========================================== */

window.onclick = function(event)
{
    const addModal =
        document.getElementById(
            'addModal'
        );

    const detailModal =
        document.getElementById(
            'detailModal'
        );

    if(event.target == addModal)
    {
        closeAddModal();
    }

    if(event.target == detailModal)
    {
        closeDetailModal();
    }
}

</script>

<?php

$content = ob_get_clean();

include '../../includes/navbarM03(2).php';

?>