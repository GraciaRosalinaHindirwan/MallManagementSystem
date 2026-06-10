<?php

$title = "Damage Report";
$page = "damage_report";

include '../Config/konek.php';
include '../Includes/head.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $ticket_id = $_POST['ticket_id'];
    $report_date = $_POST['report_date'];

    $asset_name = $_POST['asset_name'];
    $asset_category = $_POST['asset_category'];
    $asset_code = $_POST['asset_code'];

    $location = $_POST['location'];
    $floor_name = $_POST['floor_name'];
    $area_name = $_POST['area_name'];

    $damage_type = $_POST['damage_type'];
    $priority = $_POST['priority'];

    $severity_level = $_POST['severity_level'];
    $description = $_POST['description'];

    $attachment_file = null;

    if (
        isset($_FILES['attachment'])
        &&
        $_FILES['attachment']['error'] == 0
    ) {

        $uploadDir =
            "../uploads/damage_reports/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $attachment_file =
            time() .
            "_" .
            basename(
                $_FILES['attachment']['name']
            );

        move_uploaded_file(

            $_FILES['attachment']['tmp_name'],

            $uploadDir .
                $attachment_file

        );
    }

    $sql = "
    INSERT INTO damage_reports (

        ticket_id,
        report_date,

        asset_name,
        asset_category,
        asset_code,

        location,
        floor_name,
        area_name,

        damage_type,
        priority,

        severity_level,
        description,
        attachment_file

    )

   VALUES (
?,?,?,?,?,?,?,?,?,?,?,?,?
)
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(

        "ssssssssssiss",

        $ticket_id,
        $report_date,

        $asset_name,
        $asset_category,
        $asset_code,

        $location,
        $floor_name,
        $area_name,

        $damage_type,
        $priority,

        $severity_level,
        $description,
        $attachment_file

    );

    if ($stmt->execute()) {
        header(
            "Location: Damage_Report.php?success=1"
        );
        exit();
    } else {
        die("Database Error : "
            . $stmt->error);
    }
}

include '../Includes/head.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Damage Report Form</title>
</head>

<body>
    <main class="pt-8 min-h-screen">
        <div class="max-w-5xl mx-auto">

            <?php if (isset($_GET['success'])) : ?>
                <div
                    class="success-alert
                    mb-6
                    bg-green-500/10
                    border border-green-500/30
                    text-green-400
                    rounded-xl
                    px-4 py-3">
                    ✅ Laporan kerusakan berhasil dikirim dan disimpan ke database.

                </div>
            <?php endif; ?>

            <div class="mb-12 text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-accent mb-3">
                    Damage Report
                </h2>
                <p class="text-on-surface-variant text-lg max-w-3xl mx-auto">
                    Submit maintenance requests and damage reports for facility assets quickly and efficiently.
                </p>
            </div>

            <div class="glass-card rounded-2xl p-8">
                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-8">

                    <!-- Ticket Information -->
                    <div>
                        <h3 class="text-xl font-semibold text-accent mb-6">
                            General Information
                        </h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block mb-2">
                                    Ticket ID
                                </label>
                                <input
                                    type="text"
                                    name="ticket_id"
                                    readonly
                                    value="<?= 'TK-' . date('Ymd') . '-' . rand(1000, 9999); ?>"
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">
                            </div>

                            <div>
                                <label class="block mb-2">
                                    Report Date
                                </label>
                                <input
                                    type="date"
                                    name="report_date"
                                    value="<?= date('Y-m-d'); ?>"
                                    required
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">
                            </div>
                        </div>
                    </div>

                    <!-- Asset Information -->
                    <div>
                        <h3 class="text-xl font-semibold text-accent mb-6">
                            Asset Information
                        </h3>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div>
                                <label class="block mb-2">
                                    Asset Name
                                </label>
                                <input
                                    type="text"
                                    name="asset_name"
                                    required
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">
                            </div>

                            <div>
                                <label class="block mb-2">
                                    Asset Category
                                </label>
                                <select
                                    name="asset_category"
                                    required
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">

                                    <option value="HVAC">HVAC</option>
                                    <option value="Electrical">Electrical</option>
                                    <option value="Plumbing">Plumbing</option>
                                    <option value="Vertical Transport">Vertical Transport</option>
                                    <option value="Structural">Structural</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-2">
                                    Asset Code
                                </label>
                                <input
                                    type="text"
                                    name="asset_code"
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">
                            </div>
                        </div>
                    </div>

                    <!-- Location -->
                    <div>
                        <h3 class="text-xl font-semibold text-accent mb-6">
                            Location Information
                        </h3>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div>
                                <label class="block mb-2">
                                    Location
                                </label>
                                <input
                                    type="text"
                                    name="location"
                                    required
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">
                            </div>

                            <div>
                                <label class="block mb-2">
                                    Floor
                                </label>
                                <input
                                    type="text"
                                    name="floor_name"
                                    required
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">
                            </div>

                            <div>
                                <label class="block mb-2">
                                    Area
                                </label>
                                <input
                                    type="text"
                                    name="area_name"
                                    required
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">
                            </div>
                        </div>
                    </div>

                    <!-- Damage Details -->
                    <div>
                        <h3 class="text-xl font-semibold text-accent mb-6">
                            Damage Details
                        </h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block mb-2">
                                    Damage Type
                                </label>
                                <select
                                    name="damage_type"
                                    required
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">

                                    <option value="Mechanical Failure">Mechanical Failure</option>
                                    <option value="Cosmetic Damage">Cosmetic Damage</option>
                                    <option value="Water Leak">Water Leak</option>
                                    <option value="Power Outage">Power Outage</option>
                                    <option value="Safety Hazard">Safety Hazard</option>
                                </select>
                            </div>

                            <div>
                                <label class="block mb-2">
                                    Priority
                                </label>
                                <select
                                    name="priority"
                                    required
                                    class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">

                                    <option value="Low">Low</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical</option>
                                </select>

                            </div>
                        </div>
                        <div class="mt-6">
                            <label class="block mb-2">
                                Severity Level (1-10)
                            </label>
                            <input
                                type="range"
                                name="severity_level"
                                min="1"
                                max="10"
                                value="5"
                                class="w-full">
                        </div>

                        <div class="mt-6">
                            <label class="block mb-2">
                                Detailed Description
                            </label>
                            <textarea
                                name="description"
                                rows="5"
                                required
                                class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke"></textarea>
                        </div>
                    </div>

                    <!-- Attachment -->
                    <div>
                        <h3 class="text-xl font-semibold text-accent mb-6">
                            Attachment
                        </h3>
                        <input
                            type="file"
                            name="attachment"
                            accept=".jpg,.jpeg,.png,.pdf"
                            class="w-full px-4 py-3 rounded-xl bg-primary-dark border border-glass-stroke">
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="px-8 py-3 rounded-xl bg-accent text-primary-dark font-bold">
                            Submit Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="../Asset/sidebar.js"></script>
    <script>
        setTimeout(() => {
            const alertBox =
                document.querySelector('.success-alert');
            if (alertBox) {
                alertBox.remove();
            }
        }, 5000);
    </script>
</body>

</html>